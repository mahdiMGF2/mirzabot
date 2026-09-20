<?php
chdir(__DIR__);
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../function.php';
$ManagePanel = new ManagePanel();


$stmt = $pdo->prepare("SELECT id FROM user WHERE checkstatus = '2' ORDER BY RAND() LIMIT 10");
$stmt->execute();
while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stmts = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :mp1 AND (Status = 'active' OR Status = 'end_of_time'  OR Status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold')  ORDER BY RAND() LIMIT 10");
        $stmts->execute([':mp1' => $result['id']]);
        $selectinvoice = $stmts->fetchAll();
        if($stmts->rowCount() == 0){
            update("user","checkstatus","0","id",$result['id']);
            continue;
            }
        foreach ($selectinvoice as $invoice){
        $get_username_Check = $ManagePanel->DataUser($invoice['Service_location'],$invoice['username']);
        $panelSynced = true;
        if($get_username_Check['status'] == "active"){
        $userchengestatus = $ManagePanel->Change_status($invoice['username'],$invoice['Service_location']);
        $panelSynced = is_array($userchengestatus) && ($userchengestatus['status'] ?? '') != "Unsuccessful";
        }
        if($panelSynced){
        update("invoice","Status","disablebyadmin","id_invoice",$invoice['id_invoice']);
        }
        }
    }