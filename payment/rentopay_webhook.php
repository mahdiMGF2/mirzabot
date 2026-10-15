<?php

ini_set('error_log', 'error_log');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels/Api/Marzban.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../keyboard.php';
require_once __DIR__ . '/../jdf.php';
require __DIR__ . '/../vendor/autoload.php';

$ManagePanel = new ManagePanel();

$textbotlang = languagechange();

function rentopay_webhook_respond(int $code, string $msg): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

// Raw body is what the HMAC is computed over — never use $_POST here.
$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
    rentopay_webhook_respond(400, 'empty body');
}

$sigHeader = $_SERVER['HTTP_X_SIGNATURE'] ?? $_SERVER['HTTP_X_RENTOPAY_SIGNATURE'] ?? '';
if ($sigHeader === '' && function_exists('getallheaders')) {
    $headers = getallheaders();
    foreach ($headers as $k => $v) {
        $lower = strtolower($k);
        if ($lower === 'x-signature' || $lower === 'x-rentopay-signature') {
            $sigHeader = $v;
            break;
        }
    }
}

$secret = trim((string) getPaySettingValue('rentopay_secret_key', ''));
if ($secret === '' || $secret === '0') {
    error_log('rentopay webhook: rentopay_secret_key not configured');
    rentopay_webhook_respond(500, 'not configured');
}

$provided = trim($sigHeader);
if (str_starts_with($provided, 'sha256=')) {
    $provided = substr($provided, 7);
}

$expected = hash_hmac('sha256', $raw, $secret);
if (!hash_equals($expected, $provided)) {
    error_log('rentopay webhook: signature mismatch');
    rentopay_webhook_respond(400, 'invalid signature');
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    rentopay_webhook_respond(400, 'invalid json');
}

$event = $data['event'] ?? '';

// Handle ping event
if ($event === 'ping') {
    rentopay_webhook_respond(200, 'pong');
}

$token = (string) ($data['token'] ?? '');
$orderId = (string) ($data['orderId'] ?? $data['order_id'] ?? '');
$amountRials = (int) ($data['amountRials'] ?? $data['amount_rials'] ?? 0);
$status = (string) ($data['status'] ?? '');

// Find payment row
$payment = null;
if ($orderId !== '') {
    $payment = select("Payment_report", "*", "id_order", $orderId, "select");
}
if (!$payment && $token !== '') {
    $payment = select("Payment_report", "*", "dec_not_confirmed", $token, "select");
    if (!$payment) {
        $payment = select("Payment_report", "*", "id_invoice", $token, "select");
    }
}

if (!$payment) {
    error_log("rentopay webhook: order not found (orderId: {$orderId}, token: {$token})");
    rentopay_webhook_respond(404, 'order not found');
}

// Handle rejection or expiration
if ($event === 'payment.rejected' || $status === 'REJECTED') {
    if ($payment['payment_Status'] !== 'paid') {
        update("Payment_report", "payment_Status", "reject", "id_order", $payment['id_order']);

        $reason = trim((string) ($data['rejectionReason'] ?? $data['rejection_reason'] ?? ''));
        if ($reason === '') {
            $reason = $textbotlang['paymentGateway']['defaultRejectionReason'] ?? 'رسید پرداخت توسط مدیریت درگاه تایید نشد.';
        }

        $userId = $payment['id_user'] ?? null;
        if (!empty($userId)) {
            $buyer = select("user", "*", "id", $userId, "select");
            $userLang = $buyer['Language'] ?? 'fa';
            $langPath = __DIR__ . '/../lang/' . (in_array($userLang, ['fa', 'en', 'ru', 'zh']) ? $userLang : 'fa') . '.php';
            $langData = file_exists($langPath) ? require $langPath : [];
            $rejTemplate = $langData['users']['Balance']['rejectedNotice'] ?? "❌ کاربر گرامی پرداخت شما به دلیل زیر رد گردید.\n✍️ %s\n🛒 کد پیگیری پرداخت: %s";
            $rejMsg = sprintf($rejTemplate, $reason, $payment['id_order']);
            sendmessage($userId, $rejMsg, null, 'HTML');
        }
    }
    rentopay_webhook_respond(200, 'rejected noted');
}

if ($event === 'payment.expired' || $status === 'EXPIRED') {
    if ($payment['payment_Status'] !== 'paid') {
        update("Payment_report", "payment_Status", "expire", "id_order", $payment['id_order']);
    }
    rentopay_webhook_respond(200, 'expired noted');
}

if ($event !== 'payment.verified' && $status !== 'PAID') {
    rentopay_webhook_respond(200, 'ignored event');
}

// Already settled — idempotent
if ($payment['payment_Status'] === 'paid') {
    rentopay_webhook_respond(200, 'already paid');
}

// Amount check: Mirza stores prices in Tomans; gateway sends amountRials
$billedTomans = (int) $payment['price'];
$billedRials = $billedTomans * 10;

if ($amountRials > 0 && $amountRials < $billedRials) {
    error_log("rentopay webhook: amount mismatch for {$orderId}: billed {$billedRials} Rials, got {$amountRials} Rials");
    rentopay_webhook_respond(400, 'amount mismatch');
}

// Dual verification with official RentoPay API verify endpoint
if (!empty($token) && function_exists('verifyPayRentopay')) {
    $verifyRes = verifyPayRentopay($token);
    if (isset($verifyRes['success']) && $verifyRes['success'] === false) {
        error_log("rentopay webhook: verify endpoint failed for token {$token}: " . json_encode($verifyRes));
        rentopay_webhook_respond(400, 'verification failed');
    }
    if (isset($verifyRes['verified']) && $verifyRes['verified'] === false) {
        error_log("rentopay webhook: token {$token} is not verified");
        rentopay_webhook_respond(400, 'not verified');
    }
}

$order_id = $payment['id_order'];

// Prevent duplicate processing
if (!claimPaymentPaid($order_id)) {
    rentopay_webhook_respond(200, 'already claimed');
}

try {
    DirectPayment($order_id, __DIR__ . "/../images.jpg");
} catch (Throwable $e) {
    error_log("rentopay webhook: DirectPayment failed for {$order_id}: " . $e->getMessage());
    rentopay_webhook_respond(500, 'delivery failed');
}

// Cashback
$cashback = intval(getPaySettingValue('chashbackrentopay', '0'));
if ($cashback > 0) {
    $buyer = select("user", "*", "id", $payment['id_user'], "select");
    if ($buyer) {
        $reward = intval($billedTomans * $cashback / 100);
        if ($reward > 0) {
            update("user", "Balance", intval($buyer['Balance']) + $reward, "id", $payment['id_user']);
            $text_gift = sprintf($textbotlang['paymentGateway']['giftReport'], $reward);
            sendmessage($payment['id_user'], $text_gift, null, 'HTML');
        }
    }
}

// Report to Channel_Report
$setting = select("setting", "*");
$paymentreports = select("topicid", "idreport", "report", "paymentreport", "select")['idreport'] ?? null;
if (!empty($setting['Channel_Report']) && $paymentreports) {
    $buyer = select("user", "*", "id", $payment['id_user'], "select");
    $text_report = sprintf(
        $textbotlang['paymentGateway']['reportRentopay'],
        $buyer['username'] ?? '-',
        $payment['id_user'],
        number_format($billedTomans),
        $order_id,
        $token
    );
    telegram('sendmessage', [
        'chat_id' => $setting['Channel_Report'],
        'message_thread_id' => $paymentreports,
        'text' => $text_report,
        'parse_mode' => "HTML"
    ]);
}

rentopay_webhook_respond(200, 'ok');
