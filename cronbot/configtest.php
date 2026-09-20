<?php
chdir(__DIR__);
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../function.php';
$ManagePanel = new ManagePanel();
$textbotlang = languagechange();
        $stmt = $pdo->prepare("SELECT * FROM invoice WHERE status != 'disabled' AND name_product = :mp1 ORDER BY RAND() LIMIT 15");
        $stmt->execute([':mp1' => $textbotlang['common']['labels']['testServiceName']]);
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $resultt  = trim($result['username']);
        $marzban_list_get = select("marzban_panel","*","name_panel",$result['Service_location'],"select");
        if($marzban_list_get == false)continue;
        $user = select("user","*","id",$result['id_user'],"select");
        $get_username_Check = $ManagePanel->DataUser($result['Service_location'],$result['username']);
    if (!in_array($get_username_Check['status'],['active','on_hold',"Unsuccessful","disabled"])) {
            $ManagePanel->RemoveUser($result['Service_location'],$resultt);
        update("invoice","status","disabled","username",$resultt);
        if(intval($user['status_cron']) != 0){
         $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['buyService'], 'callback_data' => 'buy'],
            ],
        ]
    ]);
        $textexpire = str_replace('{username}', $resultt, $textbotlang['textbot']['testExpired']);
        sendmessage($result['id_user'], $textexpire, $Response, 'HTML');
        }
    }
}