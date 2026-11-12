<?php
chdir(__DIR__);
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../function.php';
require __DIR__ . '/../vendor/autoload.php';
$ManagePanel = new ManagePanel();
$setting = config_all('setting');
$textbotlang = languagechange();
$month_date_time_start = time() - 86400;
$month_date_time_start = date('Y/m/d H:i:s',$month_date_time_start);
$stmt = $pdo->prepare("SELECT * FROM payment_report WHERE time < :mp1 AND payment_Status = 'Unpaid'");
$stmt->execute([':mp1' => $month_date_time_start]);

$expireStmt = $pdo->prepare("UPDATE payment_report SET payment_Status = 'expire' WHERE id_order = ? AND payment_Status = 'Unpaid'");
while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $expireStmt->execute([$result['id_order']]);
    if ($expireStmt->rowCount() === 0) {
        continue;
    }
    deletemessage($result['id_user'], $result['message_id']);
}