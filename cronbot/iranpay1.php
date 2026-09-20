<?php
chdir(__DIR__);
ini_set('error_log', 'error_log');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../jdf.php';
require_once __DIR__ . '/../function.php';
require __DIR__ . '/../vendor/autoload.php';
$ManagePanel = new ManagePanel();
$setting = select("setting", "*", null, null, "select");
$paymentreports = select("topicid", "idreport", "report", "paymentreport", "select")['idreport'];
$textbotlang = languagechange();
$list_service = $pdo->prepare("SELECT * FROM Payment_report WHERE payment_Status = 'Unpaid' AND Payment_Method = 'Currency Rial 3' ORDER BY RAND() LIMIT 10");
$list_service->execute();
while ($Payment_report = ($list_service)->fetch(PDO::FETCH_ASSOC)) {
    $StatusPayment = verifpay($Payment_report['dec_not_confirmed']);
    if (!is_string($StatusPayment))
        continue;
    $StatusPayment = json_decode($StatusPayment, true);
    if (!is_array($StatusPayment))
        continue;
    if (!$StatusPayment['success'])
        continue;
    if ($StatusPayment['data']['status'] != "approved")
        continue;
    if (!claimPaymentPaid($Payment_report['id_order']))
        continue;
    update("Payment_report", "dec_not_confirmed", json_encode($StatusPayment['data']), "id_order", $Payment_report['id_order']);
    DirectPayment($Payment_report['id_order']);
    $pricecashback = select("PaySetting", "ValuePay", "NamePay", "chashbackiranpay1", "select")['ValuePay'];
    $Balance_id = select("user", "*", "id", $Payment_report['id_user'], "select");
    if ($pricecashback != "0") {
        $result = ($Payment_report['price'] * $pricecashback) / 100;
        $Balance_confrim = intval($Balance_id['Balance']) + $result;
        update("user", "Balance", $Balance_confrim, "id", $Balance_id['id']);
        $text_report = sprintf($textbotlang['users']['Balance']['giftDepositIranpay'], $result);
        sendmessage($Balance_id['id'], $text_report, null, 'HTML');
    }
    $text_reportpayment = sprintf($textbotlang['Admin']['reportgroup']['newPaymentIranpay'], $Balance_id['username'], $Balance_id['id'], $Payment_report['price']);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $paymentreports,
            'text' => $text_reportpayment,
            'parse_mode' => "HTML"
        ]);
    }
    update("Payment_report", "dec_not_confirmed", json_encode($StatusPayment), "id_order", $Payment_report['id_order']);
}