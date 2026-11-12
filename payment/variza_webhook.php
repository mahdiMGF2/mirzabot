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

function variza_webhook_respond(int $code, string $msg): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

// Raw body is what the HMAC is over — never use $_POST here.
$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
    variza_webhook_respond(400, 'empty body');
}

$sigHeader = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
// Cloudflare/NGINX may lowercase headers; check both forms.
if ($sigHeader === '' && function_exists('getallheaders')) {
    $headers = getallheaders();
    foreach ($headers as $k => $v) {
        if (strtolower($k) === 'x-webhook-signature') {
            $sigHeader = $v;
            break;
        }
    }
}

$secret = trim((string) getPaySettingValue('variza_webhook_secret', ''));
if ($secret === '' || $secret === '0') {
    error_log('variza webhook: webhook_secret not configured');
    variza_webhook_respond(500, 'not configured');
}

$provided = $sigHeader;
if (str_starts_with($provided, 'sha256=')) {
    $provided = substr($provided, 7);
}

$expected = hash_hmac('sha256', $raw, $secret);
if (!hash_equals($expected, $provided)) {
    error_log('variza webhook: signature mismatch');
    variza_webhook_respond(400, 'invalid signature');
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    variza_webhook_respond(400, 'invalid json');
}

if (($data['event'] ?? '') !== 'payment.paid') {
    variza_webhook_respond(200, 'ignored');
}

$slug = (string) ($data['slug'] ?? '');
$amount = (int) ($data['amount'] ?? 0);
// attempt_code available as $data['attempt_code'] for audit if needed

if ($slug === '') {
    variza_webhook_respond(400, 'missing slug');
}

// Variza stores slug in dec_not_confirmed (like zarinpal Authority).
$stmt = $pdo->prepare("SELECT * FROM payment_report WHERE dec_not_confirmed = ? AND Payment_Method = 'variza' LIMIT 1");
$stmt->execute([$slug]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$payment) {
    // Fallback: some installs may have slug in id_invoice; try there too.
    $payment = select("payment_report", "*", "id_invoice", $slug, "select");
}
if (!$payment) {
    error_log("variza webhook: unknown slug {$slug}");
    variza_webhook_respond(404, 'order not found');
}

// Already settled — idempotent. The retry policy sends up to 5 times.
if ($payment['payment_Status'] === 'paid') {
    variza_webhook_respond(200, 'already paid');
}

// Amount check — both in Toman, do not credit if gateway reports less than billed.
$billed = (int) $payment['price'];
if ($amount < $billed) {
    error_log("variza webhook: amount mismatch for {$slug}: billed {$billed}, got {$amount}");
    variza_webhook_respond(400, 'amount mismatch');
}

$order_id = $payment['id_order'];

// One credit per order, whatever the path.
if (!claimPaymentPaid($order_id)) {
    variza_webhook_respond(200, 'already claimed');
}

try {
    DirectPayment($order_id, __DIR__ . "/../images.jpg");
} catch (Throwable $e) {
    error_log("variza webhook: DirectPayment failed for {$order_id}: " . $e->getMessage());
    variza_webhook_respond(500, 'delivery failed');
}

// Cashback like every other gateway.
$cashback = intval(getPaySettingValue('chashbackvariza', '0'));
if ($cashback > 0) {
    $buyer = select("user", "*", "id", $payment['id_user'], "select");
    if ($buyer) {
        $reward = intval($billed * $cashback / 100);
        if ($reward > 0) {
            addBalance($payment['id_user'], $reward);
            $text_gift = sprintf($textbotlang['paymentGateway']['giftReport'], $reward);
            sendmessage($payment['id_user'], $text_gift, null, 'HTML');
        }
    }
}

// Report to Channel_Report like zarinpal does.
$setting = config_all('setting');
$paymentreports = config_get('topic', "paymentreport") ?? null;
if (!empty($setting['Channel_Report']) && $paymentreports) {
    $buyer = select("user", "*", "id", $payment['id_user'], "select");
    $text_report = sprintf(
        $textbotlang['paymentGateway']['reportVariza'],
        $buyer['username'] ?? '-',
        $payment['id_user'],
        number_format($billed),
        $order_id,
        $slug
    );
    telegram('sendmessage', [
        'chat_id' => $setting['Channel_Report'],
        'message_thread_id' => $paymentreports,
        'text' => $text_report,
        'parse_mode' => "HTML"
    ]);
}

variza_webhook_respond(200, 'ok');
