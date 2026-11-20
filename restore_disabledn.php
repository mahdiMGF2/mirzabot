<?php
if (PHP_SAPI !== 'cli') {
    exit(1);
}
chdir(__DIR__);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/botapi.php';
require_once __DIR__ . '/panels.php';
require_once __DIR__ . '/function.php';
require_once __DIR__ . '/keyboard.php';
require_once __DIR__ . '/jdf.php';
require __DIR__ . '/vendor/autoload.php';
$ManagePanel = new ManagePanel();
$location = null;
$apply = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--apply') {
        $apply = true;
    } elseif (str_starts_with($arg, '--location=')) {
        $location = substr($arg, strlen('--location='));
    }
}
$query = "SELECT id_invoice, username, Service_location FROM invoice WHERE Status = 'disabledn'";
$params = [];
if ($location !== null) {
    $query .= " AND Service_location = :loc";
    $params[':loc'] = $location;
}
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$checked = 0;
$exists = 0;
$missing = 0;
$error = 0;
foreach ($rows as $row) {
    $checked++;
    $result = $ManagePanel->DataUser($row['Service_location'], $row['username']);
    if (($result['status'] ?? 'Unsuccessful') != 'Unsuccessful') {
        $exists++;
        $classification = 'exists';
        if ($apply) {
            update("invoice", "Status", "active", "id_invoice", $row['id_invoice']);
        }
    } elseif (($result['msg'] ?? '') == 'User not found') {
        $missing++;
        $classification = 'missing';
    } else {
        $error++;
        $classification = 'error';
    }
    echo "{$row['id_invoice']} | {$row['username']} | {$classification}\n";
}
echo "\nchecked: {$checked}\n";
echo "exists: {$exists}" . ($apply ? " (restored)" : "") . "\n";
echo "missing: {$missing}\n";
echo "error: {$error}\n";
if (!$apply) {
    echo "dry run: nothing was changed\n";
}
