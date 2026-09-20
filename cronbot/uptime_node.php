<?php
chdir(__DIR__);
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Marzban.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../function.php';
$textbotlang = languagechange();



$errorreport = select("topicid","idreport","report","errorreport","select")['idreport'];
$setting = select("setting", "*");
$status_cron = json_decode($setting['cron_status'],true);
if(!$status_cron['uptime_node'])return;
$marzbanlist = select("marzban_panel", "*","type" ,"marzban" ,"fetchAll");
$inbounds = [];
foreach ($marzbanlist as $location) {
    $Getdnodes = Get_Nodes($location['name_panel']);
    if (!empty($Getdnodes['error']))
        continue;
    if (!empty($Getdnodes['status']) && $Getdnodes['status'] != 200)
        continue;
    $Getdnodes = json_decode($Getdnodes['body'], true);
    if (count($Getdnodes) == 0)
        return;
    if ($location['version_panel'] == "1") {
        $Getdnodes = $Getdnodes['nodes'];
    }
    foreach ($Getdnodes as $data) {
        if (!in_array($data['status'], ["connected", "disabled"])) {
            $textnode = sprintf($textbotlang['Admin']['report']['nodeDown'], $data['name'], $data['status'], $data['message']);
        if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage',[
        'chat_id' => $setting['Channel_Report'],
        'message_thread_id' => $errorreport,
        'text' => $textnode,
        'parse_mode' => "HTML"
        ]);
    }
    }
}
}