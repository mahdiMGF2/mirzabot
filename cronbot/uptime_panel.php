<?php
chdir(__DIR__);
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../function.php';
$textbotlang = languagechange();



$admin_ids = select("admin", "id_admin",null,null,"FETCH_COLUMN");
$marzbanlist = select("marzban_panel", "*",null ,null ,"fetchAll");
$setting = select("setting", "*");
$status_cron = json_decode($setting['cron_status'],true);
if(!$status_cron['uptime_panel'])return;
$inbounds = [];
foreach($marzbanlist as $location){
    $parsed_url = parse_url($location['url_panel']);
    if ($parsed_url && isset($parsed_url['host'])) {
    $address = $parsed_url['host'];
    $port = empty($parsed_url['port']) ? 443 : $parsed_url['port'];
    if (!checkConnection($address, $port)) {
       foreach ($admin_ids as $admin) {
            $textnode = sprintf($textbotlang['Admin']['report']['panelDown'], $location['name_panel']);
        sendmessage($admin, $textnode, null, 'html');
    }
    }
    }
}
