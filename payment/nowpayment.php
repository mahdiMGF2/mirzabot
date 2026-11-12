<?php
ini_set('error_log', 'error_log');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../keyboard.php';
require_once __DIR__ . '/../jdf.php';
require __DIR__ . '/../vendor/autoload.php';
$ManagePanel = new ManagePanel();
$setting = config_all('setting');
$paymentreports = config_get('topic', "paymentreport");
$textbotlang = languagechange();
$data = json_decode(file_get_contents("php://input"), true);
if (isset($data['payment_status']) && $data['payment_status'] == "finished") {
    $pay = StatusPayment($data['payment_id']);
    if ($pay['payment_status'] != "finished")
        return;
    $Payment_report = select("payment_report", "*", "id_order", (string) ($pay['order_id'] ?? ''), "select");
    if ($Payment_report && $Payment_report['Payment_Method'] === "nowpayment" && (string) $Payment_report['dec_not_confirmed'] === (string) ($pay['invoice_id'] ?? '')) {
        if (!claimPaymentPaid($Payment_report['id_order']))
            return;
        try {
            DirectPayment($Payment_report['id_order'], "../images.jpg");
        } catch (Throwable $directPaymentError) {
            error_log("DirectPayment failed for order {$Payment_report['id_order']}: " . $directPaymentError->getMessage());
            return;
        }
        $pricecashback = config_get('pay', "cashbacknowpayment");
        $Balance_id = select("user", "*", "id", $Payment_report['id_user'], "select");
        if ($pricecashback != "0") {
            $result = ($Payment_report['price'] * $pricecashback) / 100;
            addBalance($Balance_id['id'], $result);
            $pricecashback = number_format($pricecashback);
            $text_report = sprintf($textbotlang['paymentGateway']['giftReport'], $result);
            sendmessage($Balance_id['id'], $text_report, null, 'HTML');
        }
        $text_reportpayment = sprintf($textbotlang['paymentGateway']['reportNowpayment'], $Balance_id['username'], $Balance_id['id'], $Payment_report['price'], $pay['actually_paid']);
        if (strlen($setting['Channel_Report']) > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $paymentreports,
                'text' => $text_reportpayment,
                'parse_mode' => "HTML"
            ]);
        }
    }
}