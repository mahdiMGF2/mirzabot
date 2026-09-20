<?php
ini_set('error_log', 'error_log');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Marzban.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../panels.php';
$ManagePanel = new ManagePanel();
header('Content-Type: text/plain; charset=utf-8');
$url = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$parts = explode("/sub/", (string) $url);
$token = trim($parts[1] ?? '');
if ($token === '' || !preg_match('/^[a-f0-9]{4,64}$/i', $token)) {
    echo "ERROR!";
    exit;
}
try {
    $nameloc = select("invoice", "*", "id_invoice", $token, "select");
    if (!is_array($nameloc)) {
        echo "ERROR!";
        exit;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    $config = "";
    if (is_array($DataUserOut) && isset($DataUserOut['links']) && is_array($DataUserOut['links'])) {
        foreach ($DataUserOut['links'] as $Links) {
            $config .= $Links . "\r\r";
        }
    }
    echo $config;
} catch (Throwable $e) {
    error_log('sub endpoint error: ' . $e->getMessage());
    echo "Error!";
}
