#!/usr/bin/env php
<?php

declare(strict_types=1);

$opts = getopt('', ['user::', 'scan::', 'roots::', 'dry-run', 'apply', 'help']);
if (isset($opts['help'])) {
    fwrite(STDOUT, <<<TXT
Usage:
  php migrate-crontab.php [--dry-run] [--apply] [--user=www-data] [--scan=/var/www,/home] [--roots=/a,/b]

  --dry-run   Show discovered bots and new crontab (default if --apply is omitted)
  --apply     Write crontab after backup
  --user      Crontab owner (default: www-data)
  --scan      Comma-separated dirs to search for bot installs
  --roots     Explicit bot install roots (skips discovery)

TXT);
    exit(0);
}

$user = $opts['user'] ?? 'www-data';
$apply = isset($opts['apply']);
$dryRun = !$apply || isset($opts['dry-run']);
if (isset($opts['apply']) && isset($opts['dry-run'])) {
    $dryRun = true;
    $apply = false;
}

$crontabBin = trim((string) shell_exec('command -v crontab 2>/dev/null'));
if ($crontabBin === '') {
    foreach (['/usr/bin/crontab', '/usr/sbin/crontab'] as $candidate) {
        if (is_executable($candidate)) {
            $crontabBin = $candidate;
            break;
        }
    }
}
if ($crontabBin === '') {
    fwrite(STDERR, "crontab not found\n");
    exit(1);
}

$phpBin = PHP_BINDIR . '/php';
if (!is_executable($phpBin)) {
    $phpBin = is_executable('/usr/bin/php') ? '/usr/bin/php' : 'php';
}

$existing = mirza_read_crontab($crontabBin, $user);
$roots = [];

if (isset($opts['roots']) && trim((string) $opts['roots']) !== '') {
    foreach (explode(',', (string) $opts['roots']) as $root) {
        $root = rtrim(trim($root), '/');
        if ($root !== '' && mirza_is_bot_root($root)) {
            $roots[$root] = true;
        }
    }
} else {
    $scanDirs = ['/var/www', '/home', dirname(__DIR__, 2)];
    if (isset($opts['scan']) && trim((string) $opts['scan']) !== '') {
        $scanDirs = array_map(static function ($dir) {
            return rtrim(trim($dir), '/');
        }, explode(',', (string) $opts['scan']));
    }

    foreach (mirza_roots_from_crontab($existing) as $root) {
        $roots[$root] = true;
    }
    foreach (mirza_roots_from_vhosts() as $root) {
        $roots[$root] = true;
    }
    foreach ($scanDirs as $dir) {
        if ($dir === '' || !is_dir($dir)) {
            continue;
        }
        foreach (mirza_scan_bot_roots($dir, 5) as $root) {
            $roots[$root] = true;
        }
    }
}

$roots = array_keys($roots);
sort($roots);

$hostMap = mirza_vhost_host_map();
$domainRoots = [];
foreach ($roots as $root) {
    $domain = mirza_domain_from_config($root);
    if ($domain !== '') {
        $domainRoots[$domain] = $root;
    }
}

$kept = [];
$removed = 0;
foreach ($existing as $line) {
    if (mirza_is_cronbot_line($line)) {
        $removed++;
        $discovered = mirza_root_from_cron_line($line, $hostMap, $domainRoots);
        if ($discovered !== null && mirza_is_bot_root($discovered)) {
            $roots[] = $discovered;
        }
        continue;
    }
    $kept[] = $line;
}

$roots = array_values(array_unique($roots));
sort($roots);

$newLines = $kept;
$added = 0;
$skipped = [];
foreach ($roots as $root) {
    $run = $root . '/cronbot/run.php';
    if (!is_file($run)) {
        $skipped[] = $root . ' (missing cronbot/run.php)';
        continue;
    }
    $seed = mirza_domain_from_config($root);
    if ($seed === '') {
        $seed = $root;
    }
    $sleep = (int) (sprintf('%u', crc32($seed)) % 20);
    $line = '* * * * * sleep ' . $sleep . '; ' . $phpBin . ' ' . $run . ' >/dev/null 2>&1';
    if (!in_array($line, $newLines, true)) {
        $newLines[] = $line;
        $added++;
    }
}

fwrite(STDOUT, 'User: ' . $user . PHP_EOL);
fwrite(STDOUT, 'Mode: ' . ($apply ? 'apply' : 'dry-run') . PHP_EOL);
fwrite(STDOUT, 'Bots found: ' . count($roots) . PHP_EOL);
fwrite(STDOUT, 'Cronbot lines removed: ' . $removed . PHP_EOL);
fwrite(STDOUT, 'Dispatcher lines added: ' . $added . PHP_EOL);
if ($skipped !== []) {
    fwrite(STDOUT, "Skipped:\n- " . implode("\n- ", $skipped) . PHP_EOL);
}
fwrite(STDOUT, "\nDiscovered roots:\n");
foreach ($roots as $root) {
    fwrite(STDOUT, '- ' . $root . PHP_EOL);
}
fwrite(STDOUT, "\nNew crontab:\n");
fwrite(STDOUT, ($newLines === [] ? "(empty)\n" : implode(PHP_EOL, $newLines) . PHP_EOL));

if (!$apply) {
    fwrite(STDOUT, "\nDry-run only. Re-run with --apply to write.\n");
    exit(0);
}

$backupDir = is_dir('/root') && is_writable('/root') ? '/root' : (getenv('HOME') ?: sys_get_temp_dir());
$backup = rtrim($backupDir, '/') . '/www-data.cron.bak.' . date('YmdHis');
file_put_contents($backup, ($existing === [] ? '' : implode(PHP_EOL, $existing) . PHP_EOL));
fwrite(STDOUT, 'Backup: ' . $backup . PHP_EOL);

$tmp = tempnam(sys_get_temp_dir(), 'mirza-cron');
if ($tmp === false) {
    fwrite(STDERR, "Unable to create temp file\n");
    exit(1);
}
file_put_contents($tmp, $newLines === [] ? '' : implode(PHP_EOL, $newLines) . PHP_EOL);
$cmd = escapeshellarg($crontabBin) . ' -u ' . escapeshellarg($user) . ' ' . escapeshellarg($tmp) . ' 2>&1';
exec($cmd, $out, $code);
unlink($tmp);
if ($code !== 0) {
    fwrite(STDERR, "crontab write failed:\n" . implode(PHP_EOL, $out) . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "Crontab updated.\n");
exit(0);

/**
 * @return list<string>
 */
function mirza_read_crontab(string $bin, string $user): array
{
    $cmd = escapeshellarg($bin) . ' -u ' . escapeshellarg($user) . ' -l 2>/dev/null';
    $raw = (string) shell_exec($cmd);
    $raw = trim($raw);
    if ($raw === '' || stripos($raw, 'no crontab') !== false) {
        return [];
    }

    $lines = preg_split('/\r?\n/', $raw) ?: [];
    return array_values(array_filter(array_map('trim', $lines), static function ($line) {
        return $line !== '' && strpos($line, '#') !== 0;
    }));
}

function mirza_is_bot_root(string $root): bool
{
    $root = rtrim($root, '/');
    return is_file($root . '/config.php')
        && is_file($root . '/function.php')
        && is_dir($root . '/cronbot')
        && strpos($root, '/vpnbot/') === false;
}

function mirza_is_cronbot_line(string $line): bool
{
    return strpos($line, '/cronbot/') !== false;
}

/**
 * @param array<string, string> $hostMap
 * @param array<string, string> $domainRoots
 */
function mirza_root_from_cron_line(string $line, array $hostMap, array $domainRoots): ?string
{
    if (preg_match('#(/[^\\s]+)/cronbot/(?:run\\.php|[A-Za-z0-9_]+\\.php)#', $line, $m)) {
        $root = $m[1];
        if (mirza_is_bot_root($root)) {
            return $root;
        }
    }
    if (preg_match('#https?://([^/\\s]+)/cronbot/#', $line, $m)) {
        $host = strtolower($m[1]);
        if (isset($domainRoots[$host])) {
            return $domainRoots[$host];
        }
        if (isset($hostMap[$host]) && mirza_is_bot_root($hostMap[$host])) {
            return $hostMap[$host];
        }
    }

    return null;
}

/**
 * @param list<string> $lines
 * @return list<string>
 */
function mirza_roots_from_crontab(array $lines): array
{
    $roots = [];
    foreach ($lines as $line) {
        if (preg_match('#(/[^\\s]+)/cronbot/(?:run\\.php|[A-Za-z0-9_]+\\.php)#', $line, $m)) {
            $root = $m[1];
            if (mirza_is_bot_root($root)) {
                $roots[] = $root;
            }
        }
    }

    return $roots;
}

/**
 * @return list<string>
 */
function mirza_roots_from_vhosts(): array
{
    $roots = [];
    foreach (mirza_vhost_host_map() as $root) {
        if (mirza_is_bot_root($root)) {
            $roots[] = $root;
        }
    }

    return $roots;
}

/**
 * @return array<string, string> host => document root
 */
function mirza_vhost_host_map(): array
{
    static $map;
    if (is_array($map)) {
        return $map;
    }

    $map = [];
    $dirs = [
        '/etc/apache2/sites-enabled',
        '/etc/httpd/conf.d',
        '/etc/nginx/sites-enabled',
        '/etc/nginx/conf.d',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        foreach (glob($dir . '/*') ?: [] as $file) {
            if (!is_file($file) && !is_link($file)) {
                continue;
            }
            $content = @file_get_contents($file);
            if (!is_string($content) || $content === '') {
                continue;
            }
            $hosts = [];
            $root = '';
            if (preg_match_all('/^\s*(?:ServerName|server_name)\s+([^;]+);?\s*$/mi', $content, $hm)) {
                foreach ($hm[1] as $chunk) {
                    foreach (preg_split('/\s+/', trim($chunk)) ?: [] as $host) {
                        $host = strtolower(rtrim($host, ';'));
                        if ($host !== '' && $host !== '_') {
                            $hosts[] = $host;
                        }
                    }
                }
            }
            if (preg_match('/^\s*(?:DocumentRoot|root)\s+([^;]+);?\s*$/mi', $content, $rm)) {
                $root = rtrim(trim($rm[1], " \t\"';"), '/');
            }
            if ($root === '' || !is_dir($root)) {
                continue;
            }
            foreach ($hosts as $host) {
                $map[$host] = $root;
            }
        }
    }

    return $map;
}

/**
 * @return list<string>
 */
function mirza_scan_bot_roots(string $dir, int $maxDepth, int $depth = 0): array
{
    $found = [];
    if ($depth > $maxDepth || !is_dir($dir)) {
        return $found;
    }

    $base = basename($dir);
    if (in_array($base, ['vendor', 'vpnbot', 'node_modules', '.git', 'storage'], true)) {
        return $found;
    }

    if (mirza_is_bot_root($dir)) {
        return [$dir];
    }

    $entries = @scandir($dir);
    if ($entries === false) {
        return $found;
    }
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir . '/' . $entry;
        if (!is_dir($path) || is_link($path)) {
            continue;
        }
        foreach (mirza_scan_bot_roots($path, $maxDepth, $depth + 1) as $root) {
            $found[] = $root;
        }
    }

    return $found;
}

function mirza_domain_from_config(string $root): string
{
    $config = @file_get_contents($root . '/config.php');
    if (!is_string($config)) {
        return '';
    }
    if (preg_match('/\\$domainhosts\\s*=\\s*[\'"]([^\'"]+)[\'"]/', $config, $m)) {
        return strtolower($m[1]);
    }

    return '';
}
