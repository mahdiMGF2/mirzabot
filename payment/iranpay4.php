<?php
/**
 * Where a buyer lands after paying through AbanGateway, and where the gateway
 * pushes a confirmation when the bank SMS settles.
 *
 * Both paths arrive here and both are treated the same: nothing in the request
 * is believed. The order id is a handle for looking the invoice up, not proof
 * of anything, so the file turns round and asks the gateway — and then checks
 * the answer against what this bot billed before crediting.
 *
 * Card-to-card often settles minutes after the buyer has closed the tab, which
 * is why the push exists at all; a pull-only design loses those payments.
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

$ManagePanel = new ManagePanel();
$textbotlang = languagechange();

// Explicit, not $_REQUEST: the buyer returns with a query string and the
// gateway pushes with one too, so there is no reason to also accept cookies as
// a source of an order id.
$order_id = trim((string) ($_GET['order_id'] ?? $_POST['order_id'] ?? ''));
$authority = trim((string) ($_GET['authority'] ?? $_POST['authority'] ?? ''));

/**
 * Draw the result the buyer sees, or answer the gateway, and stop.
 *
 * Both callers get the same verdict; only the wrapping differs. The message is
 * taken from the bot's own language files so a shop running in English does not
 * hand its buyer a Persian page.
 */
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

// Already settled by whichever path got here first. Saying so is not an error:
// a buyer who reloads the page, and a push that races the buyer's return, both
// land here and both should see the payment they made.
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
    // The amount travels with the question, the way zarinpal.php sends it, so
    // the gateway has to agree with this bot's figure rather than merely
    // reporting one of its own.
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

// Three things have to line up before anything is credited, and none of them
// is "the gateway said yes":
//   the call succeeded, the order id came back equal to the one asked about,
//   and the amount is at least what this bot billed.
// Without the last two, a gateway that answered `{"success":true}` to every
// question would settle every invoice in the table.
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

// One credit per order, whatever the path. `claimPaymentPaid()` is the only
// thing standing between a returning buyer and a pushed confirmation arriving
// together, and it is checked before delivery rather than after.
if (!claimPaymentPaid($order_id)) {
    iranpay4_finish(true, $successTitle, 'این پرداخت قبلاً تایید شده است.');
}

try {
    DirectPayment($order_id, "../images.jpg");
} catch (Throwable $error) {
    error_log("iranpay4: DirectPayment failed for {$order_id}: " . $error->getMessage());
    iranpay4_finish(false, $failedTitle, 'پرداخت تایید شد ولی تحویل سرویس خطا داد. با پشتیبانی تماس بگیرید.');
}

// The gateway's own answer, kept beside the order. When a shop later asks what
// the gateway actually said about a payment — the question card-to-card
// generates more than any other method — this is where it is, in their own
// database rather than in our logs. `zarinpal`, `nowpayment` and `iranpay2`
// all keep theirs the same way.
$statement = $pdo->prepare(
    "UPDATE Payment_report SET dec_not_confirmed = :answer WHERE id_order = :id_order"
);
$statement->bindValue(':answer', json_encode($response, JSON_UNESCAPED_UNICODE));
$statement->bindValue(':id_order', $order_id);
$statement->execute();

// Needed twice below — for the cashback message and for the shop's report —
// so it is read once rather than per use.
$buyer = select("user", "*", "id", $payment['id_user'], "select");

// Cashback, on the same terms as every other gateway here. Left out of the
// first version of this file, which quietly made this the one gateway where a
// shop's cashback setting did nothing.
$cashback = intval(getPaySettingValue('chashbackiranpay4', '0'));
if ($cashback > 0 && $buyer) {
    $reward = intval($price * $cashback / 100);
    if ($reward > 0) {
        update("user", "Balance", intval($buyer['Balance']) + $reward, "id", $payment['id_user']);
        // Crediting it in silence was the other half of the same omission: the
        // buyer saw their balance change with nothing saying why. `giftReport`
        // is what every other gateway sends, and it already exists in all four
        // languages.
        sendmessage(
            $buyer['id'],
            sprintf($textbotlang['paymentGateway']['giftReport'], number_format($reward)),
            null,
            'HTML'
        );
    }
}

// The shop's own record of the sale, posted to its report channel like every
// other gateway does. Shops that moved onto this slot from a borrowed one
// noticed the day it stopped arriving: it is how a seller watches the shop
// without opening the panel, and its absence read as "the gateway broke".
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
