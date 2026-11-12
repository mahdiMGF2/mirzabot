<?php
ini_set('error_log', 'error_log');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jdf.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels/Api/Marzban.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/../keyboard.php';
require_once __DIR__ . '/../panels.php';
require __DIR__ . '/../vendor/autoload.php';

$ManagePanel = new ManagePanel();
$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data) || !isset($data['hashid'], $data['authority'], $data['status'])) {
    http_response_code(400);
    exit('invalid request');
}
$hashid = $data['hashid'];
$authority = $data['authority'];
$StatusPayment = $data['status'];
$setting = config_all('setting');
$PaySetting = config_get('pay', "marchent_floypay");
$Payment_reports = select("payment_report", "*", "id_order", $hashid, "select");
if (!is_array($Payment_reports) || $Payment_reports['Payment_Method'] !== "Currency Rial 1") {
    http_response_code(404);
    exit('order not found');
}
if ($Payment_reports['payment_Status'] == "expire") {
    exit('order expired');
}
$invoice_id = $Payment_reports['id_order'];
$price = $Payment_reports['price'];
// verify Transaction
$dec_payment_status = "";
$payment_status = "";
if ($StatusPayment == 100) {
    $curl = curl_init();
    $data = [
        "ApiKey" => $PaySetting,
        "authority" => $authority,
        "hashid" => $invoice_id,
    ];
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://tetra98.com/api/verify",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Accept: application/json'
        ),
    ));
    $response = curl_exec($curl);
    $response = json_decode($response, true);
    if (!empty($response['status']) && $response['status'] == 100) {
        $payment_status = $textbotlang['paymentGateway']['statusSuccess'];
        $dec_payment_status = $textbotlang['paymentGateway']['descThanks'];
        $Payment_report = select("payment_report", "*", "id_order", $invoice_id, "select");
        if (claimPaymentPaid($invoice_id)) {
            $textbotlang = languagechange();
            try {
                DirectPayment($invoice_id, "../images.jpg");
            } catch (Throwable $directPaymentError) {
                error_log("DirectPayment failed for order {$invoice_id}: " . $directPaymentError->getMessage());
                return;
            }
            $pricecashback = config_get('pay', "chashbackiranpay1");
            $Balance_id = select("user", "*", "id", $Payment_report['id_user'], "select");
            if ($pricecashback != "0") {
                $result = ($Payment_report['price'] * $pricecashback) / 100;
                addBalance($Balance_id['id'], $result);
                $pricecashback = number_format($pricecashback);
                $text_report = sprintf($textbotlang['paymentGateway']['giftReport'], $result);
                sendmessage($Balance_id['id'], $text_report, null, 'HTML');
            }
            $paymentreports = config_get('topic', "paymentreport");
            $price = number_format($price);
            $text_report = sprintf($textbotlang['paymentGateway']['reportIranpay'], $Payment_report['id_user'], $Balance_id['username'], $price);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $paymentreports,
                    'text' => $text_report,
                    'parse_mode' => "HTML"
                ]);
            }
        }
    } else {
        $payment_status = $textbotlang['paymentGateway']['statusFailed'];
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
            font-family: vazir;
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
            width: 25%;
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
        <p><?php echo $textbotlang['paymentGateway']['invoiceAmount'] ?> <span><?php echo $price ?></span><?php echo $textbotlang['paymentGateway']['invoiceAmountUnit'] ?></p>
        <p><?php echo $textbotlang['paymentGateway']['invoiceDate'] ?> <span> <?php echo jdate('Y/m/d') ?> </span></p>
        <p><?php echo $dec_payment_status ?></p>
    </div>
</body>

</html>