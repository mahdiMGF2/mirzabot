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
$setting = config_all('setting');
$paymentreports = config_get('topic', "paymentreport");

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
$list_service = $pdo->prepare("SELECT * FROM payment_report WHERE payment_Status = 'Unpaid' AND Payment_Method = 'plisio'");
$list_service->execute();
$textbotlang = languagechange();
$statusCheck = $pdo->prepare("SELECT payment_Status FROM payment_report WHERE id_order = ? LIMIT 1");
while ($Payment_report = ($list_service)->fetch(PDO::FETCH_ASSOC)) {
    $statusCheck->execute([$Payment_report['id_order']]);
    if ($statusCheck->fetchColumn() != "Unpaid")
        continue;
    if (!isset($Payment_report['dec_not_confirmed']) or $Payment_report['dec_not_confirmed'] == null)
        continue;
    $StatusPayment = statusplisio($Payment_report['id_order']);
    if (!is_array($StatusPayment) || !isset($StatusPayment['data']['operations']) || !is_array($StatusPayment['data']['operations']))
        continue;
    $operation = [];
    foreach ($StatusPayment['data']['operations'] as $op) {
        if (in_array($Payment_report['dec_not_confirmed'], [$op['txn_id'] ?? null, $op['id'] ?? null], true)
            || ($op['params']['order_number'] ?? $op['order_number'] ?? null) === $Payment_report['id_order']) {
            $operation = $op;
            break;
        }
    }
    $operationStatus = $operation['status'] ?? null;
    if (in_array($operationStatus, ["cancelled", "expired"], true)) {
        $textexpire = sprintf($textbotlang['users']['Balance']['plisioExpired'], $Payment_report['id_order'], $Payment_report['price']);
        sendmessage($Payment_report['id_user'], $textexpire, null, 'html');
        update("payment_report", "payment_Status", "expire", "id_order", $Payment_report['id_order']);
    }
    if ($operationStatus == "completed") {
        if (!claimPaymentPaid($Payment_report['id_order']))
            continue;
        DirectPayment($Payment_report['id_order'], "../images.jpg");
        $pricecashback = config_get('pay', "chashbackplisio");
        $__q18 = $pdo->prepare("SELECT * FROM user WHERE id = ? LIMIT 1");
        $__q18->bindValue(1, $Payment_report['id_user'], PDO::PARAM_STR);
        $__q18->execute();
        $Balance_id = $__q18->fetch(PDO::FETCH_ASSOC);
        if ($pricecashback != "0") {
            $result = ($Payment_report['price'] * $pricecashback) / 100;
            addBalance($Balance_id['id'], $result);
            $pricecashback = number_format($pricecashback);
            $text_report = sprintf($textbotlang['users']['Balance']['giftDepositPlisio'], $result);
            sendmessage($Balance_id['id'], $text_report, null, 'HTML');
        }
        $text_reportpayment = sprintf($textbotlang['Admin']['reportgroup']['newPaymentPlisio'], $Balance_id['username'], $Balance_id['id'], $Payment_report['price'], $operation['tx_url'][0] ?? '', $operation['invoice_url'] ?? '', $operation['invoice_total_sum'] ?? '');
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