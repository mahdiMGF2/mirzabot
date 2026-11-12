<?php
chdir(__DIR__);
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../function.php';
$setting = config_all('setting');
$textbotlang = languagechange();
if ($setting['alert_balance'] != "1" || $setting['Bot_Status'] == "botstatusoff") {
    return;
}
$threshold = intval($setting['balance_value_alert']);
$pdo->prepare("UPDATE user SET low_balance_alert_sent = 0 WHERE low_balance_alert_sent = 1 AND Balance > :threshold")->execute([':threshold' => $threshold]);
$stmt = $pdo->prepare("SELECT id, Balance FROM user WHERE Balance > 0 AND Balance <= :threshold AND (low_balance_alert_sent = 0 OR low_balance_alert_sent IS NULL) AND User_Status != 'block' LIMIT 20");
$stmt->execute([':threshold' => $threshold]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $lowBalanceUser) {
    update("user", "low_balance_alert_sent", "1", "id", $lowBalanceUser['id']);
    sendmessage($lowBalanceUser['id'], sprintf($textbotlang['users']['status']['lowBalanceAlert'], number_format($lowBalanceUser['Balance'])), null, 'HTML');
}
