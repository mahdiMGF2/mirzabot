<?php

ini_set('error_log', 'error_log');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../Marzban.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../keyboard.php';
require_once __DIR__ . '/../jdf.php';
require __DIR__ . '/../vendor/autoload.php';

$ManagePanel = new ManagePanel();
$textbotlang = languagechange();

$order_id = trim((string) ($_GET['order_id'] ?? $_POST['order_id'] ?? ''));
$authority = trim((string) ($_GET['authority'] ?? $_POST['authority'] ?? ''));

function iranpay4_finish(bool $ok, string $title, string $detail): never
{
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<body style="font-family:Tahoma,sans-serif;text-align:center;padding:48px 20px">'
        . '<div style="font-size:44px">' . ($ok ? '&#10003;' : '&#10005;') . '</div>'
        . '<h2 style="color:' . ($ok ? '#2ecc71' : '#e74c3c') . '">'
        . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
        . '<p style="color:#666">' . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . '</p>'
        . '</body></html>';
    exit;
}

$failedTitle = $textbotlang['paymentGateway']['statusFailed'] ?? 'پرداخت ناموفق';
$successTitle = $textbotlang['paymentGateway']['statusSuccess'] ?? 'پرداخت موفق';

if ($order_id === '') {
    iranpay4_finish(false, $failedTitle, 'شناسه سفارش ارسال نشد.');
}

$payment = select("Payment_report", "*", "id_order", $order_id, "select");
if (!$payment) {
    iranpay4_finish(false, $failedTitle, 'این سفارش پیدا نشد.');
}

if ($payment['payment_Status'] === 'paid') {
    iranpay4_finish(true, $successTitle, 'این پرداخت قبلاً تایید شده است.');
}

$api_key = trim((string) getPaySettingValue('apiiranpay4', ''));
$endpoint = abangatewayEndpoint();
if ($api_key === '' || $api_key === '0' || $endpoint === null) {
    iranpay4_finish(false, $failedTitle, 'درگاه پیکربندی نشده است.');
}

$price = intval($payment['price']);

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $endpoint . '/verify',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 25,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $api_key,
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'order_id' => $order_id,
        'authority' => $authority,
        'amount' => $price,
    ], JSON_UNESCAPED_UNICODE),
]);
$result = curl_exec($curl);
$httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

$response = is_string($result) ? json_decode($result, true) : null;

$answeredForThisOrder = is_array($response)
    && isset($response['order_id'], $response['amount'])
    && (string) $response['order_id'] === (string) $order_id
    && intval($response['amount']) >= $price;

$accepted = $httpCode === 200
    && !empty($response['success'])
    && $answeredForThisOrder;

if (!$accepted) {
    iranpay4_finish(false, $failedTitle, 'پرداخت تایید نشد.');
}

if (!claimPaymentPaid($order_id)) {
    iranpay4_finish(true, $successTitle, 'این پرداخت قبلاً تایید شده است.');
}

try {
    DirectPayment($order_id, "../images.jpg");
} catch (Throwable $error) {
    error_log("iranpay4: DirectPayment failed for {$order_id}: " . $error->getMessage());
    iranpay4_finish(false, $failedTitle, 'پرداخت تایید شد ولی تحویل سرویس خطا داد. با پشتیبانی تماس بگیرید.');
}

$statement = $pdo->prepare(
    "UPDATE Payment_report SET dec_not_confirmed = :answer WHERE id_order = :id_order"
);
$statement->bindValue(':answer', json_encode($response, JSON_UNESCAPED_UNICODE));
$statement->bindValue(':id_order', $order_id);
$statement->execute();

$buyer = select("user", "*", "id", $payment['id_user'], "select");

$cashback = intval(getPaySettingValue('chashbackiranpay4', '0'));
if ($cashback > 0 && $buyer) {
    $reward = intval($price * $cashback / 100);
    if ($reward > 0) {
        update("user", "Balance", intval($buyer['Balance']) + $reward, "id", $payment['id_user']);
        sendmessage(
            $buyer['id'],
            sprintf($textbotlang['paymentGateway']['giftReport'], number_format($reward)),
            null,
            'HTML'
        );
    }
}

if ($buyer && strlen((string) ($setting['Channel_Report'] ?? '')) > 0) {
    $paymentreports = select("topicid", "idreport", "report", "paymentreport", "select")['idreport'];
    telegram('sendmessage', [
        'chat_id' => $setting['Channel_Report'],
        'message_thread_id' => $paymentreports,
        'text' => sprintf(
            $textbotlang['paymentGateway']['reportAbanGateway'],
            $buyer['username'],
            $buyer['id'],
            number_format($price)
        ),
        'parse_mode' => 'HTML',
    ]);
}

iranpay4_finish(true, $successTitle, 'پرداخت شما با موفقیت انجام شد.');
