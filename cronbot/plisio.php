<?php
chdir(__DIR__);
ini_set('error_log', 'error_log');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../jdf.php';
require __DIR__ . '/../vendor/autoload.php';
$ManagePanel = new ManagePanel();
$setting = select("setting", "*");
$paymentreports = select("topicid", "idreport", "report", "paymentreport", "select")['idreport'];

function statusplisio($tx_id)
{
    $api_key = getPaySettingValue('apinowpayment');
    $url = 'https://api.plisio.net/api/v1/operations';
    $url .= '?api_key=' . urlencode($api_key);
    $url .= '&search=' . urlencode($tx_id);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $response = curl_exec($ch);
    if ($response === false) {
        return null;
    }
    return json_decode($response, true);
}
$list_service = $pdo->prepare("SELECT * FROM Payment_report WHERE payment_Status = 'Unpaid' AND Payment_Method = 'plisio'");
$list_service->execute();
$textbotlang = languagechange();
$statusCheck = $pdo->prepare("SELECT payment_Status FROM Payment_report WHERE id_order = ? LIMIT 1");
while ($Payment_report = ($list_service)->fetch(PDO::FETCH_ASSOC)) {
    $statusCheck->execute([$Payment_report['id_order']]);
    if ($statusCheck->fetchColumn() != "Unpaid")
        continue;
    if (!isset($Payment_report['dec_not_confirmed']) or $Payment_report['dec_not_confirmed'] == null)
        continue;
    $StatusPayment = statusplisio($Payment_report['id_order']);
    if (!is_array($StatusPayment) || !isset($StatusPayment['data']['operations']) || !is_array($StatusPayment['data']['operations']))
        continue;
    $operationStatus = $StatusPayment['data']['operations'][0]['status'] ?? null;
    if ($operationStatus == null || $operationStatus == "cancelled") {
        $textexpire = sprintf($textbotlang['users']['Balance']['plisioExpired'], $Payment_report['id_order'], $Payment_report['price']);
        sendmessage($Payment_report['id_user'], $textexpire, null, 'html');
        update("Payment_report", "payment_Status", "expire", "id_order", $Payment_report['id_order']);
    }
    if ($operationStatus == "completed") {
        if (!claimPaymentPaid($Payment_report['id_order']))
            continue;
        DirectPayment($Payment_report['id_order'], "../images.jpg");
        $pricecashback = select("PaySetting", "ValuePay", "NamePay", "chashbackplisio", "select")['ValuePay'];
        $__q18 = $pdo->prepare("SELECT * FROM user WHERE id = ? LIMIT 1");
        $__q18->bindValue(1, $Payment_report['id_user'], PDO::PARAM_STR);
        $__q18->execute();
        $Balance_id = $__q18->fetch(PDO::FETCH_ASSOC);
        if ($pricecashback != "0") {
            $result = ($Payment_report['price'] * $pricecashback) / 100;
            $Balance_confrim = intval($Balance_id['Balance']) + $result;
            update("user", "Balance", $Balance_confrim, "id", $Balance_id['id']);
            $pricecashback = number_format($pricecashback);
            $text_report = sprintf($textbotlang['users']['Balance']['giftDepositPlisio'], $result);
            sendmessage($Balance_id['id'], $text_report, null, 'HTML');
        }
        $text_reportpayment = sprintf($textbotlang['Admin']['reportgroup']['newPaymentPlisio'], $Balance_id['username'], $Balance_id['id'], $Payment_report['price'], $StatusPayment['tx_url'][0], $StatusPayment['invoice_url'], $StatusPayment['invoice_total_sum']);
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