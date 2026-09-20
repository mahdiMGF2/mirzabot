<?php
chdir(__DIR__);
require_once '../config.php';
require_once '../function.php';
$textbotlang = languagechange();
require_once '../botapi.php';

function backupCollectFiles($source, $localName, &$map)
{
    if (is_file($source)) {
        $map[$localName] = $source;
        return;
    }
    if (!is_dir($source)) {
        return;
    }
    foreach (scandir($source) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        backupCollectFiles($source . '/' . $item, $localName . '/' . $item, $map);
    }
}

function backupCreateArchive(array $sources, $archiveBasePath)
{
    $map = [];
    foreach ($sources as $source) {
        if (file_exists($source)) {
            backupCollectFiles($source, basename($source), $map);
        }
    }
    if (!$map) {
        return null;
    }

    if (class_exists('ZipArchive')) {
        $zipPath = $archiveBasePath . '.zip';
        @unlink($zipPath);
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($map as $localName => $filePath) {
                $zip->addFile($filePath, $localName);
            }
            $zip->close();
            if (is_file($zipPath) && filesize($zipPath) > 0) {
                return $zipPath;
            }
            @unlink($zipPath);
        }
    }

    if (isShellExecAvailable()) {
        $zipPath = $archiveBasePath . '.zip';
        @unlink($zipPath);
        $arguments = '';
        foreach ($sources as $source) {
            if (file_exists($source)) {
                $arguments .= ' ' . escapeshellarg($source);
            }
        }
        runShellCommand('zip -r ' . escapeshellarg($zipPath) . $arguments . ' 2>&1');
        if (is_file($zipPath) && filesize($zipPath) > 0) {
            return $zipPath;
        }
        @unlink($zipPath);
    }

    if (class_exists('PharData')) {
        $tarPath = $archiveBasePath . '.tar';
        $gzPath = $tarPath . '.gz';
        @unlink($tarPath);
        @unlink($gzPath);
        try {
            $phar = new PharData($tarPath);
            $phar->buildFromIterator(new ArrayIterator($map));
            $phar->compress(Phar::GZ);
            unset($phar);
            @unlink($tarPath);
            if (is_file($gzPath) && filesize($gzPath) > 0) {
                return $gzPath;
            }
        } catch (Exception $e) {
            error_log('PharData backup failed: ' . $e->getMessage());
            @unlink($tarPath);
            @unlink($gzPath);
        }
    }

    return null;
}

function backupDumpDatabaseWithPdo(PDO $pdo, $targetFile)
{
    $handle = @fopen($targetFile, 'w');
    if (!$handle) {
        error_log('Unable to open backup file for writing: ' . $targetFile);
        return false;
    }

    try {
        fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");
        $tables = $pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM);
        foreach ($tables as $table) {
            $name = '`' . str_replace('`', '``', $table[0]) . '`';
            $isView = isset($table[1]) && strtoupper($table[1]) === 'VIEW';
            $create = $pdo->query('SHOW CREATE TABLE ' . $name)->fetch(PDO::FETCH_NUM);
            fwrite($handle, 'DROP ' . ($isView ? 'VIEW' : 'TABLE') . ' IF EXISTS ' . $name . ";\n" . $create[1] . ";\n\n");
            if ($isView) {
                continue;
            }

            $offset = 0;
            $chunkSize = 500;
            do {
                $rows = $pdo->query('SELECT * FROM ' . $name . ' LIMIT ' . $chunkSize . ' OFFSET ' . $offset)->fetchAll(PDO::FETCH_ASSOC);
                if (!$rows) {
                    break;
                }
                $columns = [];
                foreach (array_keys($rows[0]) as $column) {
                    $columns[] = '`' . str_replace('`', '``', $column) . '`';
                }
                $values = [];
                foreach ($rows as $row) {
                    $cells = [];
                    foreach ($row as $cell) {
                        $cells[] = $cell === null ? 'NULL' : $pdo->quote((string) $cell);
                    }
                    $values[] = '(' . implode(',', $cells) . ')';
                }
                fwrite($handle, 'INSERT INTO ' . $name . ' (' . implode(',', $columns) . ") VALUES\n" . implode(",\n", $values) . ";\n");
                $offset += $chunkSize;
            } while (count($rows) === $chunkSize);
            fwrite($handle, "\n");
        }
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
    } catch (Exception $e) {
        error_log('PDO database backup failed: ' . $e->getMessage());
        fclose($handle);
        @unlink($targetFile);
        return false;
    }

    fclose($handle);

    return is_file($targetFile) && filesize($targetFile) > 0;
}

$reportbackup = select("topicid", "idreport", "report", "backupfile", "select")['idreport'];
$destination = getcwd();
$setting = select("setting", "*");
$canSendReport = !isTelegramChatIdEmpty($setting['Channel_Report'] ?? '');
if (!$canSendReport) {
    return;
}
$sourcefir = dirname($destination);
$botlist = select("botsaz", "*", null, null, "fetchAll");
if ($botlist) {
    foreach ($botlist as $bot) {
        $botFolder = $sourcefir . '/vpnbot/' . $bot['id_user'] . $bot['username'];
        $archive = backupCreateArchive([
            $botFolder . '/data',
            $botFolder . '/product.json',
            $botFolder . '/product_name.json',
        ], $destination . '/file');
        if ($archive === null) {
            continue;
        }
        telegram('sendDocument', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $reportbackup,
            'document' => new CURLFile($archive),
            'caption' => "@{$bot['username']} | {$bot['id_user']}",
        ]);
        unlink($archive);
    }
}

$backup_file_name = 'backup_' . date("Y-m-d") . '.sql';
$dbhost = empty($dbhost) ? "localhost" : $dbhost;
$isDumped = false;

if (isExecAvailable()) {
    $command = 'mysqldump -h ' . escapeshellarg($dbhost)
        . ' -u ' . escapeshellarg($usernamedb)
        . ' -p' . escapeshellarg($passworddb)
        . ' --no-tablespaces --ssl-mode=DISABLED ' . escapeshellarg($dbname)
        . ' > ' . escapeshellarg($backup_file_name) . ' 2>/dev/null';
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    $isDumped = $return_var === 0 && is_file($backup_file_name) && filesize($backup_file_name) > 0;
    if (!$isDumped) {
        @unlink($backup_file_name);
    }
}

if (!$isDumped) {
    $isDumped = backupDumpDatabaseWithPdo($pdo, $backup_file_name);
}

if (!$isDumped) {
    telegram('sendmessage', [
        'chat_id' => $setting['Channel_Report'],
        'message_thread_id' => $reportbackup,
        'text' => $textbotlang['keyboard']['backupError'],
    ]);
    return;
}

telegram('sendDocument', [
    'chat_id' => $setting['Channel_Report'],
    'message_thread_id' => $reportbackup,
    'document' => new CURLFile($backup_file_name),
    'caption' => $textbotlang['Admin']['report']['backupCaption'],
]);
unlink($backup_file_name);
