<?php
/**
 * Where a buyer lands after tapping “I paid” on Variza.
 *
 * Variza verifies card-to-card via SMS, often minutes after the buyer
 * has closed the tab, so this page never trusts what it sees — it only
 * reports what this bot knows. The real settlement is the push to
 * variza_webhook.php; this page is just a waiting room that turns into
 * a receipt if the push has already arrived.
 */

ini_set('error_log', 'error_log');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../Marzban.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../keyboard.php';
require_once __DIR__ . '/../jdf.php';
require __DIR__ . '/../vendor/autoload.php';

$textbotlang = languagechange();

$order_id = trim((string) ($_GET['order'] ?? $_GET['order_id'] ?? ''));

function variza_finish(bool $ok, string $title, string $detail): never
{
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<body style="font-family:Tahoma,sans-serif;text-align:center;padding:48px 20px">'
        . '<div style="font-size:44px">' . ($ok ? '&#10003;' : '&#8986;') . '</div>'
        . '<h2 style="color:' . ($ok ? '#2ecc71' : '#3498db') . '">'
        . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
        . '<p style="color:#666">' . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . '</p>'
        . '</body></html>';
    exit;
}

$failedTitle = $textbotlang['paymentGateway']['statusFailed'] ?? 'پرداخت ناموفق';
$successTitle = $textbotlang['paymentGateway']['statusSuccess'] ?? 'پرداخت موفق';
$waitingTitle = 'در انتظار تأیید';

if ($order_id === '') {
    variza_finish(false, $failedTitle, 'شناسه سفارش ارسال نشد.');
}

$payment = select("Payment_report", "*", "id_order", $order_id, "select");
if (!$payment) {
    variza_finish(false, $failedTitle, 'این سفارش پیدا نشد.');
}

if ($payment['payment_Status'] === 'paid') {
    variza_finish(true, $successTitle, 'پرداخت شما با موفقیت تایید شد.');
}

if ($payment['Payment_Method'] !== 'variza') {
    variza_finish(false, $failedTitle, 'این سفارش مربوط به واریزا نیست.');
}

// Still Unpaid/waiting — SMS may not have arrived yet. The push to
// variza_webhook.php will settle it; this page just explains the wait.
variza_finish(false, $waitingTitle, 'واریز شما دریافت شد و در انتظار تأیید خودکار واریزا است. پس از تایید، سرویس به صورت خودکار فعال خواهد شد.');
