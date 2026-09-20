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

$invoice_id = htmlspecialchars($_POST['invoice_id'] ?? '', ENT_QUOTES, 'UTF-8');
$transid = $_POST['transid'] ?? '';
if ($invoice_id === '' || $transid === '') {
    http_response_code(400);
    exit('invalid request');
}
$setting = select("setting", "*");
$PaySetting = select("PaySetting", "ValuePay", "NamePay", "merchant_id_aqayepardakht","select")['ValuePay'];
$payment_row = select("Payment_report", "*", "id_order", $invoice_id, "select");
if (!is_array($payment_row)) {
    http_response_code(404);
    exit('order not found');
}
if ($payment_row['payment_Status'] == "expire") {
    exit('order expired');
}
$Payment_report = $payment_row['price'];
$price = $Payment_report;
// verify Transaction

$data = [
'pin'    => $PaySetting,
'amount'    => $Payment_report,
'transid' => $transid,
];
$data = json_encode($data);
$ch = curl_init('https://panel.aqayepardakht.ir/api/v2/verify');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLINFO_HEADER_OUT, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
'Content-Type: application/json',
'Content-Length: ' . strlen($data))
);
$result = curl_exec($ch);
curl_close($ch);
$result = json_decode($result);
if (is_object($result) && $result->code == "1") {
    $payment_status = $textbotlang['paymentGateway']['statusSuccess'];
    $price = $Payment_report;
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
    $pricecashback = select("PaySetting", "ValuePay", "NamePay", "chashbackaqaypardokht","select")['ValuePay'];
    $__q16 = $pdo->prepare("SELECT * FROM user WHERE id = ? LIMIT 1");
    $__q16->bindValue(1, $Payment_report['id_user'], PDO::PARAM_STR);
    $__q16->execute();
    $Balance_id = $__q16->fetch(PDO::FETCH_ASSOC);
    if($pricecashback != "0"){
        $result = ($Payment_report['price'] * $pricecashback) / 100;
        $Balance_confrim = intval($Balance_id['Balance']) +$result;
        update("user","Balance",$Balance_confrim, "id",$Balance_id['id']); 
        $pricecashback =  number_format($pricecashback);
        $text_report = sprintf($textbotlang['paymentGateway']['giftReport'], $result);
        sendmessage($Balance_id['id'], $text_report, null, 'HTML');
    }
    $paymentreports = select("topicid","idreport","report","paymentreport","select")['idreport'];

$text_report = sprintf($textbotlang['paymentGateway']['reportAqayepardakht'], $Payment_report['id_user'], $Balance_id['username'], $price);
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
        $payment_status = $textbotlang['paymentGateway']['statusFailed'];
     $dec_payment_status = "";
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
        <p><?php echo $textbotlang['paymentGateway']['invoiceAmount'] ?>  <span><?php echo  $price; ?></span><?php echo $textbotlang['paymentGateway']['invoiceAmountUnit'] ?></p>
        <p><?php echo $textbotlang['paymentGateway']['invoiceDate'] ?> <span>  <?php echo jdate('Y/m/d')  ?>  </span></p>
        <p><?php echo $dec_payment_status ?></p>
    </div>
</body>
</html>
