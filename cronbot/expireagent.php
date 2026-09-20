<?php
chdir(__DIR__);
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../function.php';
$textbotlang = languagechange();

$setting = select("setting", "*");
$otherreport = select("topicid","idreport","report","otherreport","select")['idreport'];
// buy service 
$stmt = $pdo->prepare("SELECT id, username FROM user WHERE expire IS NOT NULL AND CAST(expire AS UNSIGNED) < :now");
$stmt->execute([':now' => time()]);
while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $textexpire = $textbotlang['users']['agent']['expiredNotice'];
    sendmessage($user['id'],$textexpire, null, 'HTML');
    $stmtExpire = $pdo->prepare("UPDATE user SET agent = 'f', expire = NULL WHERE id = ?");
    $stmtExpire->execute([$user['id']]);
    clearSelectCache('user');
    $textreport = sprintf($textbotlang['Admin']['reportgroup']['agentExpiredGroupChanged'], $user['id'], $user['username']);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage',[
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherreport,
            'text' => $textreport,
            'parse_mode' => "HTML"
        ]);
    }
}