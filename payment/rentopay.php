<?php

ini_set('error_log', 'error_log');

// If incoming request is a webhook POST from RentoPay (or carries signature headers), delegate to webhook handler.
$sigHeader = $_SERVER['HTTP_X_SIGNATURE'] ?? $_SERVER['HTTP_X_RENTOPAY_SIGNATURE'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($sigHeader)) {
    require __DIR__ . '/rentopay_webhook.php';
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels/Api/Marzban.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../keyboard.php';
require_once __DIR__ . '/../jdf.php';
require __DIR__ . '/../vendor/autoload.php';

$textbotlang = languagechange();

$order_id = trim((string) ($_GET['order'] ?? $_GET['order_id'] ?? ''));

function rentopay_finish(bool $ok, string $title, string $detail, ?string $btnText = null, ?string $btnUrl = null): never
{
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    $btnHtml = '';
    if (!empty($btnText) && !empty($btnUrl)) {
        $btnHtml = '<div style="margin-top:24px"><a href="' . htmlspecialchars($btnUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:10px 22px;background:#3498db;color:#fff;text-decoration:none;border-radius:8px;font-size:14px;font-weight:bold">' . htmlspecialchars($btnText, ENT_QUOTES, 'UTF-8') . '</a></div>';
    }
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>'
        . '<style>body{font-family:Tahoma,sans-serif;text-align:center;padding:48px 20px;background:#f8f9fa;color:#333}.box{max-width:480px;margin:0 auto;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08)}</style>'
        . '</head><body><div class="box">'
        . '<div style="font-size:48px;margin-bottom:16px">' . ($ok ? '&#10003;' : '&#8986;') . '</div>'
        . '<h2 style="color:' . ($ok ? '#2ecc71' : '#e74c3c') . ';margin:0 0 12px">'
        . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
        . '<p style="color:#666;line-height:1.6;margin:0">' . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . '</p>'
        . $btnHtml
        . '</div></body></html>';
    exit;
}

$failedTitle = $textbotlang['paymentGateway']['statusFailed'] ?? 'پرداخت ناموفق';
$successTitle = $textbotlang['paymentGateway']['statusSuccess'] ?? 'پرداخت موفق';
$waitingTitle = 'در انتظار تأیید';

if ($order_id === '') {
    rentopay_finish(false, $failedTitle, 'شناسه سفارش ارسال نشد.');
}

$payment = select("Payment_report", "*", "id_order", $order_id, "select");
if (!$payment) {
    rentopay_finish(false, $failedTitle, 'این سفارش پیدا نشد.');
}

if ($payment['payment_Status'] === 'paid') {
    rentopay_finish(true, $successTitle, 'پرداخت شما با موفقیت تایید و سفارش فعال شد.');
}

if ($payment['Payment_Method'] !== 'rentopay') {
    rentopay_finish(false, $failedTitle, 'این سفارش مربوط به رنتوپی نیست.');
}

if ($payment['payment_Status'] === 'reject') {
    rentopay_finish(false, $failedTitle, 'رسید پرداخت توسط مدیریت درگاه رنتوپی رد گردیده است.');
}

if ($payment['payment_Status'] === 'expire') {
    rentopay_finish(false, $failedTitle, 'مهلت پرداخت این فاکتور به پایان رسیده و منقضی شده است.');
}

$token = $payment['dec_not_confirmed'] ?? '';
if (!empty($token)) {
    // 1. Try public inquiry first (GET /api/v1/payments/{token})
    if (function_exists('inquiryPayRentopay')) {
        $inquiry = inquiryPayRentopay($token);
        if (isset($inquiry['success']) && $inquiry['success'] === true) {
            $inquiryStatus = strtoupper((string) ($inquiry['status'] ?? ''));
            if ($inquiryStatus === 'REJECTED') {
                update("Payment_report", "payment_Status", "reject", "id_order", $order_id);
                $rejReason = $inquiry['rejection_reason'] ?? 'رسید پرداخت توسط کارشناسان تایید نشد.';
                rentopay_finish(false, $failedTitle, 'پرداخت رد شد: ' . $rejReason);
            } elseif ($inquiryStatus === 'EXPIRED') {
                update("Payment_report", "payment_Status", "expire", "id_order", $order_id);
                rentopay_finish(false, $failedTitle, 'مهلت پرداخت فاکتور به پایان رسیده و منقضی شده است.');
            } elseif ($inquiryStatus === 'AWAITING_REVIEW') {
                rentopay_finish(false, $waitingTitle, 'رسید واریز شما ثبت شده و در انتظار بررسی و تایید کارشناسان رنتوپی است.');
            } elseif ($inquiryStatus === 'PENDING') {
                $payUrl = "https://t.me/rentopaybot?start={$token}";
                rentopay_finish(false, 'در انتظار پرداخت', 'فاکتور ایجاد شده است. لطفاً وارد ربات رنتوپی شوید، کارت به کارت را انجام داده و فیش را ارسال کنید.', 'ورود به ربات رنتوپی', $payUrl);
            }
        }
    }

    // 2. If inquiry showed PAID (or for direct check), call verify endpoint
    if (function_exists('verifyPayRentopay')) {
        $verifyRes = verifyPayRentopay($token);
        if (isset($verifyRes['verified']) && $verifyRes['verified'] === true && ($verifyRes['status'] ?? '') === 'PAID') {
            $amountRials = (int) ($verifyRes['amount_rials'] ?? 0);
            $billedTomans = (int) $payment['price'];
            $billedRials = $billedTomans * 10;
            if ($amountRials >= $billedRials && claimPaymentPaid($order_id)) {
                try {
                    DirectPayment($order_id, __DIR__ . "/../images.jpg");
                } catch (Throwable $e) {
                    error_log("rentopay callback: DirectPayment failed for {$order_id}: " . $e->getMessage());
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

                // Report
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

                rentopay_finish(true, $successTitle, 'پرداخت شما با موفقیت تایید و سفارش فعال شد.');
            }
        }
    }
}

rentopay_finish(false, $waitingTitle, 'واریز شما ثبت شد و در انتظار تأیید درگاه رنتوپی است. پس از تایید، سرویس به صورت خودکار فعال خواهد شد.');

