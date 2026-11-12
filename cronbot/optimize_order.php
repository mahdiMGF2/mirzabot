<?php
chdir(__DIR__);
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../function.php';
$setting = config_all('setting');
$textbotlang = languagechange();

$staleInvoice = "Status IN ('unpaid', 'Unsuccessful') AND CAST(time_sell AS UNSIGNED) < :olderThan AND NOT EXISTS (SELECT 1 FROM payment_report pr WHERE pr.id_order = invoice.id_payment AND pr.payment_Status IN ('Unpaid', 'waiting'))";
$stmt = $pdo->prepare("SELECT id_user, discount_code FROM invoice WHERE $staleInvoice AND discount_code IS NOT NULL AND discount_code != ''");
$stmt->execute([':olderThan' => time() - 86400]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    releaseDiscountCode($row['discount_code'], $row['id_user']);
}
$stmt = $pdo->prepare("DELETE FROM invoice WHERE $staleInvoice");
$stmt->execute([':olderThan' => time() - 86400]);
$deletedInvoices = $stmt->rowCount();

$staleService = "status = 'unpaid' AND time < :olderThan AND NOT EXISTS (SELECT 1 FROM payment_report pr WHERE pr.id_order = service_other.id_payment AND pr.payment_Status IN ('Unpaid', 'waiting'))";
$stmt = $pdo->prepare("SELECT id_user, value FROM service_other WHERE $staleService");
$stmt->execute([':olderThan' => date('Y/m/d H:i:s', time() - 86400)]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $code = json_decode((string) $row['value'], true)['discount_code'] ?? '';
    if ($code !== '') {
        releaseDiscountCode($code, $row['id_user']);
    }
}
$stmt = $pdo->prepare("DELETE FROM service_other WHERE $staleService");
$stmt->execute([':olderThan' => date('Y/m/d H:i:s', time() - 86400)]);
$deletedInvoices += $stmt->rowCount();

$stmt = $pdo->prepare("DELETE FROM logs_api WHERE time < :olderThan");
$stmt->execute([':olderThan' => date('Y/m/d H:i:s', time() - 7 * 86400)]);
$deletedLogs = $stmt->rowCount();

if (($deletedInvoices > 0 || $deletedLogs > 0) && !isTelegramChatIdEmpty($setting['Channel_Report'] ?? '')) {
    telegram('sendmessage', [
        'chat_id' => $setting['Channel_Report'],
        'message_thread_id' => config_get('topic', "otherreport"),
        'text' => sprintf($textbotlang['Admin']['report']['autoCleanup'], $deletedInvoices, $deletedLogs),
        'parse_mode' => "HTML",
    ]);
}
