<?php
chdir(__DIR__);
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../keyboard.php';
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../jdf.php';
$ManagePanel = new ManagePanel();
$setting = select("setting", "*");
if ($setting['Bot_Status'] == "botstatusoff")
    return;
$autoconfirm = select("PaySetting", "ValuePay", "NamePay", "autoconfirmcart", "select")['ValuePay'];
if ($autoconfirm != "onauto")
    return;
$paymentreports = select("topicid", "idreport", "report", "paymentreport", "select")['idreport'];
$textbotlang = languagechange();
$list_Exceptions = select("PaySetting", "ValuePay", "NamePay", "Exception_auto_cart", "select")['ValuePay'];
$list_Exceptions = is_string($list_Exceptions) ? json_decode($list_Exceptions, true) : [];
if (!is_array($list_Exceptions)) {
    $list_Exceptions = [];
}
$timecheck = $setting['timeauto_not_verify'] * 60;
$stmt = $pdo->prepare("SELECT * FROM Payment_report WHERE payment_Status = 'waiting' AND (Payment_Method = 'cart to cart' OR Payment_Method = 'arze digital offline') AND bottype IS NULL");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($row['at_updated'] == null)
        continue;
    $since_start = time() - strtotime($row['at_updated']);
    if ($since_start >= 3600)
        continue;
    if ($since_start <= $timecheck)
        continue;
    $Payment_report = $row;
    if (in_array($Payment_report['id_user'], $list_Exceptions))
        continue;
    if ($Payment_report['payment_Status'] == "paid") {
        continue;
    }
    $stmtPaid = $pdo->prepare("UPDATE Payment_report SET payment_Status = 'paid', dec_not_confirmed = ? WHERE id_order = ? AND payment_Status = 'waiting'");
    $stmtPaid->execute([$textbotlang['common']['labels']['autoConfirmedByBot'], $Payment_report['id_order']]);
    clearSelectCache('Payment_report');
    if ($stmtPaid->rowCount() === 0)
        continue;
    DirectPayment($Payment_report['id_order'], "../images.jpg");
    $pricecashback = select("PaySetting", "ValuePay", "NamePay", "chashbackcart", "select")['ValuePay'];
    $Balance_id = select("user", "*", "id", $Payment_report['id_user'], "select");
    if ($pricecashback != "0") {
        $result = ($Payment_report['price'] * $pricecashback) / 100;
        $Balance_confrim = intval($Balance_id['Balance']) + $result;
        update("user", "Balance", $Balance_confrim, "id", $Balance_id['id']);
        $pricecashback = number_format($pricecashback);
        $text_report = sprintf($textbotlang['users']['Balance']['giftDeposit'], $result);
        sendmessage($Balance_id['id'], $text_report, null, 'HTML');
    }
    $text_reportpayment = sprintf($textbotlang['Admin']['reportgroup']['newPaymentAutoConfirm'], $Balance_id['id'], $Payment_report['price'], $Payment_report['Payment_Method']);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $paymentreports,
            'text' => $text_reportpayment,
            'parse_mode' => "HTML"
        ]);
    }
}