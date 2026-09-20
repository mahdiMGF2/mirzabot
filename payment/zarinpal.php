<?php
ini_set('error_log', 'error_log');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jdf.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../Marzban.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../keyboard.php';
require_once __DIR__ . '/../panels.php';
require __DIR__ . '/../vendor/autoload.php';

$ManagePanel = new ManagePanel();

$Authority = htmlspecialchars($_GET['Authority'] ?? '', ENT_QUOTES, 'UTF-8');
$StatusPayment = htmlspecialchars($_GET['Status'] ?? '', ENT_QUOTES, 'UTF-8');
if ($Authority === '') {
    http_response_code(400);
    exit('invalid request');
}
$setting = select("setting", "*");
$PaySetting = select("PaySetting", "ValuePay", "NamePay", "merchant_zarinpal","select")['ValuePay'];
$Payment_reports = select("Payment_report", "*", "dec_not_confirmed", $Authority,"select");
if (!is_array($Payment_reports)) {
    http_response_code(404);
    exit('order not found');
}
if ($Payment_reports['payment_Status'] == "expire") {
    exit('order expired');
}
$price = $Payment_reports['price'];
$invoice_id = $Payment_reports['id_order'];
// verify Transaction
$dec_payment_status = "";
$payment_status = "";
if($StatusPayment == "OK"){
        $curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.zarinpal.com/pg/v4/payment/verify.json',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Accept: application/json'
  ),
));
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
  "merchant_id" => $PaySetting,
  "amount"=> $price,
  "authority" => $Authority,
  "description" => $Payment_reports['id_user']
        ]));
$response = curl_exec($curl);
curl_close($curl);
$response = json_decode($response,true);
$payment_status = $textbotlang['paymentGateway']['zarinpalErrors'][$response['errors']['code'] ?? ''] ?? '';
 if(($response['data']['message'] ?? '') == "Verified" || ($response['data']['message'] ?? '') == "Paid"){
    $payment_status = $textbotlang['paymentGateway']['statusSuccess'];
    $dec_payment_status = $textbotlang['paymentGateway']['descThanks'];
    $Payment_report = select("Payment_report", "*", "id_order", $invoice_id,"select");
    if(claimPaymentPaid($invoice_id)){
    $textbotlang = languagechange();
    try {
        DirectPayment($invoice_id,"../images.jpg");
    } catch (Throwable $directPaymentError) {
        error_log("DirectPayment failed for order {$invoice_id}: " . $directPaymentError->getMessage());
        return;
    }
    $pricecashback = select("PaySetting", "ValuePay", "NamePay", "chashbackzarinpal","select")['ValuePay'];
    $Balance_id = select("user","*","id",$Payment_report['id_user'],"select");
    if($pricecashback != "0"){
        $result = ($Payment_report['price'] * $pricecashback) / 100;
        $Balance_confrim = intval($Balance_id['Balance']) +$result;
        update("user","Balance",$Balance_confrim, "id",$Balance_id['id']); 
        $pricecashback =  number_format($pricecashback);
        $text_report = sprintf($textbotlang['paymentGateway']['giftReport'], $result);
        sendmessage($Balance_id['id'], $text_report, null, 'HTML');
    }
    $paymentreports = select("topicid","idreport","report","paymentreport","select")['idreport'];
    $refcode = $response['data']['ref_id'];
    $cart_number = $response['data']['card_pan'];
    $price = number_format($price);
$text_report = sprintf($textbotlang['paymentGateway']['reportZarinpal'], $Payment_report['id_user'], $Balance_id['username'], $price, $refcode, $cart_number);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage',[
        'chat_id' => $setting['Channel_Report'],
        'message_thread_id' => $paymentreports,
        'text' => $text_report,
        'parse_mode' => "HTML"
        ]);
    }
}
}else {
        $payment_status = $textbotlang['paymentGateway']['zarinpalResultCodes'][$response['errors']['code'] ?? ''] ?? $textbotlang['paymentGateway']['statusFailed'];
     $dec_payment_status = "";
}
}
?>
<html>
<head>
    <title><?php echo $textbotlang['paymentGateway']['invoiceTitle'] ?></title>
    <style>
    @font-face {
    font-family: 'vazir';
    src: url('/Vazir.eot');
    src: local('☺'), url('../fonts/Vazir.woff') format('woff'), url('../fonts/Vazir.ttf') format('truetype');
}

        body {
            font-family:vazir;
            background-color: #f2f2f2;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .confirmation-box {
            background-color: #ffffff;
            border-radius: 8px;
            width:25%;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
        }

        h1 {
            color: #333333;
            margin-bottom: 20px;
        }

        p {
            color: #666666;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="confirmation-box">
        <h1><?php echo $payment_status ?></h1>
        <p><?php echo $textbotlang['paymentGateway']['invoiceTransactionNo'] ?><span><?php echo $invoice_id ?></span></p>
        <p><?php echo $textbotlang['paymentGateway']['invoiceAmount'] ?>  <span><?php echo  $price ?></span><?php echo $textbotlang['paymentGateway']['invoiceAmountUnit'] ?></p>
        <p><?php echo $textbotlang['paymentGateway']['invoiceDate'] ?> <span>  <?php echo jdate('Y/m/d')  ?>  </span></p>
        <p><?php echo $dec_payment_status ?></p>
    </div>
</body>
</html>
