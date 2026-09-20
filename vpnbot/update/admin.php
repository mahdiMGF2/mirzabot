<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
#----------------[  admin section  ]------------------#

$textadmin = ["panel", "/panel", "پنل مدیریت", "ادمین", "👨‍💼 پنل مدیریت"];
if (!in_array($from_id, $admin_idsmain) and !in_array($from_id, $admin_ids)) {
    return;
}
if (in_array($text, $textadmin) || $datain == "admin") {
    $text_admin = "Version Bot : $version
Panel Admin";
    sendmessage($from_id, $text_admin, $keyboardadmin, 'HTML');
    step("home", $from_id);
    return;
}
if ($text == "بازگشت به منوی ادمین") {
    sendmessage($from_id, "به منوی ادمین بازگشتید", $keyboardadmin, 'HTML');
    step("home", $from_id);
    return;
}
if ($text == "📞 تنظیم نام کاربری پشتیبانی") {
    sendmessage($from_id, "📌 نام کاربری جدید خود را بدون @ ارسال کنید", $backadmin, 'HTML');
    step("getusernamesupport", $from_id);
} elseif ($user['step'] == "getusernamesupport") {
    sendmessage($from_id, "✅ نام کاربری پشتیبانی برای شما با موفقیت تنظیم گردید.", $keyboardadmin, 'HTML');
    step("home", $from_id);
    $setting['support_username'] = $text;
    update("botsaz", "setting", json_encode($setting), "bot_token", $ApiToken);
} elseif ($text == "🔋 قیمت حجم") {
    sendmessage($from_id, "📌 قیمت هر گیگ حجم را ارسال نمایید. 
قیمت پایه حجم. : {$setting['minpricevolume']} تومان
قیمت فعلی حجم. : {$setting['pricevolume']} تومان", $backadmin, 'HTML');
    step("getpricvolumeadmin", $from_id);
} elseif ($user['step'] == "getpricvolumeadmin") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    if (intval($text) < intval($setting['minpricevolume'])) {
        sendmessage($from_id, "❌ قیمت حجم باید بزرگ تر از قیمت پایه حجم باشد.", $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, "✅ قیمت حجم با موفقیت تنظیم گردید.", $keyboardprice, 'HTML');
    step("home", $from_id);
    $setting['pricevolume'] = $text;
    update("botsaz", "setting", json_encode($setting), "bot_token", $ApiToken);
} elseif ($text == "⌛️ قیمت زمان") {
    sendmessage($from_id, "
📌 قیمت هر روز زمان را ارسال نمایید.
 قیمت پایه زمان. : {$setting['minpricetime']} تومان
قیمت فعلی شما : {$setting['pricetime']} تومان", $backadmin, 'HTML');
    step("getpricvtimeadmin", $from_id);
} elseif ($user['step'] == "getpricvtimeadmin") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    if (intval($text) < intval($setting['minpricetime'])) {
        sendmessage($from_id, "❌ قیمت زمان باید بزرگ تر از قیمت پایه زمان باشد.", $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, "✅ قیمت زمان با موفقیت تنظیم گردید.", $keyboardprice, 'HTML');
    step("home", $from_id);
    $setting['pricetime'] = $text;
    update("botsaz", "setting", json_encode($setting), "bot_token", $ApiToken);
} elseif (preg_match('/Confirm_pay_(\w+)/', $datain, $dataget)) {
    $order_id = $dataget[1];
    $Confirm_pay = json_encode([
        'inline_keyboard' => [
            [],
            [
                ['text' => "✅ تایید شده", 'callback_data' => "confirmpaid"],
            ]
        ]
    ]);
    $Payment_report = select("Payment_report", "*", "id_order", $order_id, "select");
    if ($Payment_report == false) {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => "تراکنش حذف شده است",
            'show_alert' => true,
            'cache_time' => 5,
        ));
        return;
    }
    $format_price_cart = number_format($Payment_report['price']);
    $Balance_id = select("user", "*", "id", $Payment_report['id_user'], "select");
    if ($Payment_report['payment_Status'] == "paid" || $Payment_report['payment_Status'] == "reject") {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['Admin']['Payment']['reviewedPayment'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
        $textconfrom = "✅. پرداخت توسط ادمین دیگری تایید شده
👤 شناسه کاربر: <code>{$Balance_id['id']}</code>
🛒 کد پیگیری پرداخت: {$Payment_report['id_order']}
⚜️ نام کاربری: @{$Balance_id['username']}
    💸 مبلغ پرداختی: $format_price_cart تومان
";
        Editmessagetext($from_id, $message_id, $textconfrom, $Confirm_pay);
        return;
    }
    DirectPaymentbot($order_id);
    $Payment_report['price'] = number_format($Payment_report['price']);
    $text_report = "📣 نماینده رسیبد پرداخت کارت به کارت را تایید کرد.
        
اطلاعات :
👤آیدی عددی  ادمین تایید کننده : $from_id
💰 مبلغ پرداخت : {$Payment_report['price']}
👤 ایدی عددی کاربر : <code>{$Payment_report['id_user']}</code>
👤 نام کاربری کاربر : @{$Balance_id['username']} 
        کد پیگیری پرداحت : $order_id";
    if (strlen($settingmain['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $settingmain['Channel_Report'],
            'message_thread_id' => $paymentreports,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
    update("Payment_report", "payment_Status", "paid", "id_order", $Payment_report['id_order']);
    update("user", "Processing_value_one", "none", "id", $Balance_id['id']);
    update("user", "Processing_value_tow", "none", "id", $Balance_id['id']);
    update("user", "Processing_value_four", "none", "id", $Balance_id['id']);
} elseif (preg_match('/reject_pay_(\w+)/', $datain, $datagetr)) {
    $id_order = $datagetr[1];
    $Payment_report = select("Payment_report", "*", "id_order", $id_order, "select");
    if ($Payment_report == false) {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => "تراکنش حذف شده است",
            'show_alert' => true,
            'cache_time' => 5,
        ));
        return;
    }
    update("user", "Processing_value", $Payment_report['id_user'], "id", $from_id);
    update("user", "Processing_value_one", $id_order, "id", $from_id);
    if ($Payment_report['payment_Status'] == "reject" || $Payment_report['payment_Status'] == "paid") {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['Admin']['Payment']['reviewedPayment'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
        return;
    }
    update("Payment_report", "payment_Status", "reject", "id_order", $id_order);

    sendmessage($from_id, $textbotlang['Admin']['Payment']['reasonRejecting'], $backadmin, 'HTML');
    step('reject-dec', $from_id);
    Editmessagetext($from_id, $message_id, $text_inline, null);
} elseif ($user['step'] == "reject-dec") {
    $Payment_report = select("Payment_report", "*", "id_order", $user['Processing_value_one'], "select");
    update("Payment_report", "dec_not_confirmed", $text, "id_order", $user['Processing_value_one']);
    $text_reject = "❌ کاربر گرامی پرداخت شما به دلیل زیر رد گردید.
✍️ $text
🛒 کد پیگیری پرداخت: {$user['Processing_value_one']}
                ";
    sendmessage($from_id, $textbotlang['Admin']['Payment']['rejected'], $keyboardadmin, 'HTML');
    sendmessage($user['Processing_value'], $text_reject, null, 'HTML');
    step('home', $from_id);
    $text_report = "❌ یک ادمین رسید پرداخت کارت به کارت را رد کرد.
        
اطلاعات :
👤آیدی عددی  ادمین تایید کننده : $from_id
نام کاربری ادمین تایید کننده : @$username
💰 مبلغ پرداخت : {$Payment_report['price']}
دلیل رد کردن : $text
👤 ایدی عددی کاربر: {$Payment_report['id_user']}";
    if (strlen($settingmain['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $settingmain['Channel_Report'],
            'message_thread_id' => $paymentreports,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
} elseif ($text == "👨‍🔧  مدیریت ادمین ها") {
    $keyboardadmin = ['inline_keyboard' => []];
    foreach ($admin_ids as $admin) {
        $keyboardadmin['inline_keyboard'][] = [
            ['text' => "❌", 'callback_data' => "removeadmin_" . $admin],
            ['text' => $admin, 'callback_data' => "adminlist"],
        ];
    }
    $keyboardadmin['inline_keyboard'][] = [
        ['text' => "👨‍💻 اضافه کردن ادمین", 'callback_data' => "addnewadmin"],
    ];
    $keyboardadmin = json_encode($keyboardadmin);
    sendmessage($from_id, "📌 در بخش زیر می توانید لیست ادمین ها را مشاهده کنید همچنین با زدن دکمه ضربدر می توانید یک ادمین را حذف کنید", $keyboardadmin, 'HTML');
} elseif ($datain == "addnewadmin") {
    sendmessage($from_id, $textbotlang['Admin']['manageadmin']['getId'], $backadmin, 'HTML');
    step('addadmin', $from_id);
} elseif ($user['step'] == "addadmin") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['manageadmin']['addAdminSet'], $keyboardadmin, 'HTML');
    sendmessage($user['Processing_value'], $textbotlang['Admin']['manageadmin']['adminAddedSendUser'], null, 'HTML');
    step('home', $from_id);
    $admin_ids[] = $text;
    update("botsaz", "admin_ids", json_encode($admin_ids), "bot_token", $ApiToken);
} elseif (preg_match('/removeadmin_(\w+)/', $datain, $dataget)) {
    $idadmin = $dataget[1];
    $count = 0;
    foreach ($admin_ids as $admin) {
        if ($admin == $idadmin) {
            unset($admin_ids[$count]);
            break;
        }
        $count += 1;
    }
    unset($admin_ids[$idadmin]);
    $admin_ids = array_values($admin_ids);
    update("botsaz", "admin_ids", json_encode($admin_ids), "bot_token", $ApiToken);
    sendmessage($from_id, "✅ ادمین با موفقیت حذف گردید", null, 'HTML');
} elseif ($text == "🔍 جستجو کاربر") {
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['getIdUserUnblock'], $backadmin, 'HTML');
    step('show_info', $from_id);
} elseif ($user['step'] == "show_info" || strpos($text, "/user ") !== false) {
    if (explode(" ", $text)[0] == "/user") {
        $id_user = explode(" ", $text)[1];
    } else {
        $id_user = $text;
    }
    if (!in_array($id_user, $users_ids)) {
        sendmessage($from_id, $textbotlang['Admin']['notUser'], null, 'HTML');
        return;
    }
    $date = date("Y-m-d");
    $__q28 = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND id_user = ? AND bottype = ?");
    $__q28->bindValue(1, $id_user, PDO::PARAM_STR);
    $__q28->bindValue(2, $ApiToken, PDO::PARAM_STR);
    $__q28->execute();
    $dayListSell = $__q28->fetch(PDO::FETCH_ASSOC);
    $__q29 = $pdo->prepare("SELECT SUM(price) FROM Payment_report WHERE payment_Status = 'paid' AND id_user = ? AND Payment_Method != 'low balance by admin' AND bottype = ?");
    $__q29->bindValue(1, $id_user, PDO::PARAM_STR);
    $__q29->bindValue(2, $ApiToken, PDO::PARAM_STR);
    $__q29->execute();
    $balanceall = $__q29->fetch(PDO::FETCH_ASSOC);
    $__q30 = $pdo->prepare("SELECT SUM(price_product) FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND id_user = ? AND bottype = ?");
    $__q30->bindValue(1, $id_user, PDO::PARAM_STR);
    $__q30->bindValue(2, $ApiToken, PDO::PARAM_STR);
    $__q30->execute();
    $subbuyuser = $__q30->fetch(PDO::FETCH_ASSOC);
    $__q31 = $pdo->prepare("SELECT count(*) FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND id_user = ? AND bottype = ?");
    $__q31->bindValue(1, $id_user, PDO::PARAM_STR);
    $__q31->bindValue(2, $ApiToken, PDO::PARAM_STR);
    $__q31->execute();
    $invoicecount = $__q31->fetch(PDO::FETCH_ASSOC)['count(*)'];
    if ($invoicecount == 0) {
        $sumvolume['SUM(Volume)'] = 0;
    } else {
        $__q32 = $pdo->prepare("SELECT SUM(Volume) FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND id_user = ? AND name_product != 'سرویس تست'");
        $__q32->bindValue(1, $id_user, PDO::PARAM_STR);
        $__q32->execute();
        $sumvolume = $__q32->fetch(PDO::FETCH_ASSOC);
    }
    $user = select("user", "*", "id", $id_user, "select");
    $roll_Status = [
        '1' => $textbotlang['Admin']['manageUser']['acceptedPhone'],
        '0' => $textbotlang['Admin']['manageUser']['failedPhone'],
    ][$user['roll_Status']];
    if ($subbuyuser['SUM(price_product)'] == null)
        $subbuyuser['SUM(price_product)'] = 0;
    $user['Balance'] = number_format($user['Balance']);
    if ($user['register'] != "none") {
        if ($user['register'] == null)
            return;
        $userjoin = jdate('Y/m/d H:i:s', $user['register']);
    } else {
        $userjoin = "نامشخص";
    }
    if ($user['last_message_time'] == null) {
        $lastmessage = "";
    } else {
        $lastmessage = jdate('Y/m/d H:i:s', $user['last_message_time']);
    }
    $datefirst = time() - 86400;
    $desired_date_time_start = time() - 3600;
    $month_date_time_start = time() - 2592000;
    $sql = "SELECT * FROM invoice WHERE time_sell > :requestedDate AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND name_product != 'سرویس تست' AND id_user = :id_user AND bottype = '$ApiToken'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_user', $id_user);
    $stmt->bindParam(':requestedDate', $desired_date_time_start);
    $stmt->execute();
    $listhours = $stmt->rowCount();
    $sql = "SELECT SUM(price_product) FROM invoice WHERE time_sell > :requestedDate AND (Status = 'active' OR Status = 'end_of_time'  OR Status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND name_product != 'سرویس تست' AND id_user = :id_user AND bottype = '$ApiToken'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_user', $id_user);
    $stmt->bindParam(':requestedDate', $desired_date_time_start);
    $stmt->execute();
    $suminvoicehours = $stmt->fetchColumn();
    if ($suminvoicehours == null) {
        $suminvoicehours = "0";
    }
    $sql = "SELECT * FROM invoice WHERE time_sell > :requestedDate AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND name_product != 'سرویس تست' AND id_user = :id_user AND bottype = '$ApiToken'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_user', $id_user);
    $stmt->bindParam(':requestedDate', $month_date_time_start);
    $stmt->execute();
    $listmonth = $stmt->rowCount();
    $sql = "SELECT SUM(price_product) FROM invoice WHERE time_sell > :requestedDate AND (Status = 'active' OR Status = 'end_of_time'  OR Status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND name_product != 'سرویس تست' AND id_user = :id_user AND bottype = '$ApiToken'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_user', $id_user);
    $stmt->bindParam(':requestedDate', $month_date_time_start);
    $stmt->execute();
    $suminvoicemonth = $stmt->fetchColumn();
    $keyboardmanage = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "افزایش موجودی", 'callback_data' => 'addbalanceuser_' . $text],
                ['text' => "کم کردن موجودی", 'callback_data' => 'lowbalanceuser_' . $text],
            ],
        ]
    ]);
    $userbalance = number_format(json_decode(file_get_contents("data/$id_user/$id_user.json"), true)['Balance']);
    if ($suminvoicemonth == null) {
        $suminvoicemonth = "0";
    }
    $textinfouser = "👀 اطلاعات کاربر:

🔗 اطلاعات کاربری کاربر

⭕️ وضعیت کاربر : {$user['User_Status']}
⭕️ نام کاربری کاربر : @{$user['username']}
⭕️ آیدی عددی کاربر :  <a href = \"tg://user?id=$id_user\">$id_user</a>
⭕️ زمان عضویت کاربر : $userjoin
⭕️ آخرین زمان  استفاده کاربر از ربات : $lastmessage
⭕️ محدودیت اکانت تست :  {$user['limit_usertest']} 
⭕️  مجموع حجم خریداری شده فعال ( برای آمار دقیق حجم باید کرون روشن باشد): {$sumvolume['SUM(Volume)']}

💎 گزارشات مالی

🔰 موجودی کاربر : $userbalance
🔰 تعداد خرید کل کاربر : {$dayListSell['COUNT(*)']}
🔰️ مبلغ کل پرداختی  :  {$balanceall['SUM(price)']}
🔰 جمع کل خرید : {$subbuyuser['SUM(price_product)']}
🔰 تعداد فروش یک ساعت گذشته : $listhours عدد
🔰 مجموع فروش یک ساعت گذشته : $suminvoicehours تومان
🔰 تعداد فروش یک ماه گذشته : $listmonth عدد
🔰 مجموع فروش یک ماه گذشته : $suminvoicemonth تومان
";
    sendmessage($from_id, $textinfouser, $keyboardmanage, 'HTML');
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboardadmin, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/addbalanceuser_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    update("user", "Processing_value", $iduser, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['addBalanceUserDesc'], $backadmin, 'html');
    step('addbalanceusercurrent', $from_id);
} elseif ($user['step'] == "addbalanceusercurrent") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['invalidPrice'], $backadmin, 'HTML');
        return;
    }
    if ($text > 100000000) {
        sendmessage($from_id, "❌ حداکثر مبلغ 100 میلیون تومان می باشد", $backadmin, 'HTML');
        return;
    }
    $dateacc = date('Y/m/d H:i:s');
    $randomString = bin2hex(random_bytes(5));
    $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice,bottype) VALUES (?,?,?,?,?,?,?,?)");
    $payment_Status = "paid";
    $Payment_Method = "add balance by admin";
    $invoice = null;
    $stmt->execute([$user['Processing_value'], $randomString, $dateacc, $text, $payment_Status, $Payment_Method, $invoice, $ApiToken]);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['addBalanced'], $keyboardadmin, 'html');
    $userbalance = json_decode(file_get_contents("data/{$user['Processing_value']}/{$user['Processing_value']}.json"), true);
    $Balance_add_user = $userbalance['Balance'] + $text;
    $userbalance['Balance'] = $Balance_add_user;
    file_put_contents("data/{$user['Processing_value']}/{$user['Processing_value']}.json", json_encode($userbalance));
    $heibalanceuser = number_format($text, 0);
    $textadd = "💎 کاربر عزیز مبلغ $heibalanceuser تومان به موجودی کیف پول تان اضافه گردید.";
    sendmessage($user['Processing_value'], $textadd, null, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/lowbalanceuser_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    update("user", "Processing_value", $iduser, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['lowBalanceUserDesc'], $backadmin, 'html');
    step('addbalanceuser', $from_id);
} elseif ($user['step'] == "addbalanceuser") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['invalidPrice'], $backadmin, 'HTML');
        return;
    }
    if ($text > 100000000) {
        sendmessage($from_id, "❌ حداکثر مبلغ 100 میلیون تومان می باشد", $backadmin, 'HTML');
        return;
    }
    $dateacc = date('Y/m/d H:i:s');
    $randomString = bin2hex(random_bytes(5));
    $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice,bottype) VALUES (?,?,?,?,?,?,?,?)");
    $payment_Status = "paid";
    $Payment_Method = "low balance by admin";
    $invoice = null;
    $stmt->execute([$user['Processing_value'], $randomString, $dateacc, $text, $payment_Status, $Payment_Method, $invoice, $ApiToken]);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['lowBalanced'], $keyboardadmin, 'html');
    $userbalance = json_decode(file_get_contents("data/{$user['Processing_value']}/{$user['Processing_value']}.json"), true);
    $Balance_add_user = intval($userbalance['Balance']) - intval($text);
    $userbalance['Balance'] = $Balance_add_user;
    file_put_contents("data/{$user['Processing_value']}/{$user['Processing_value']}.json", json_encode($userbalance));
    $lowbalanceuser = number_format($text, 0);
    $textkam = "❌ کاربر عزیز مبلغ $lowbalanceuser تومان از  موجودی کیف پول تان کسر گردید.";
    sendmessage($user['Processing_value'], $textkam, null, 'HTML');
    step('home', $from_id);
    $statistics = select("user", "*", "bottype", $ApiToken, "count");
    $Balance_user_afters = number_format(select("user", "*", "id", $user['Processing_value'], "select")['Balance']);
} elseif ($text == "📊 آمار ربات") {
    $statistics = select("user", "*", "bottype", $ApiToken, "count");
    $stmt2 = $pdo->prepare("SELECT COUNT( DISTINCT id_user) as count FROM `invoice` WHERE name_product = 'سرویس تست' AND  bottype = :mp1");
    $stmt2->execute([':mp1' => $ApiToken]);
    $statisticsorder = $stmt2->fetch(PDO::FETCH_ASSOC)['count'];
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE name_product = 'سرویس تست' AND bottype = :mp1");
    $stmt->execute([':mp1' => $ApiToken]);
    $count_usertest = $stmt->rowCount();
    $sql1 = "SELECT COUNT(*) AS invoice_count FROM invoice WHERE (status = 'active' OR status = 'end_of_time' OR status = 'end_of_volume' OR status = 'sendedwarn' OR status = 'send_on_hold') AND name_product != 'سرویس تست' AND bottype = :mp1";
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute([':mp1' => $ApiToken]);
    $invoice = $stmt1->fetch(PDO::FETCH_ASSOC)['invoice_count'];
    $sql2 = "SELECT SUM(price_product) AS total_price FROM invoice WHERE (status = 'active' OR status = 'end_of_time' OR status = 'end_of_volume' OR status = 'sendedwarn' OR status = 'send_on_hold') AND name_product != 'سرویس تست' AND bottype = :mp1";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([':mp1' => $ApiToken]);
    $invoicesum = number_format($stmt2->fetch(PDO::FETCH_ASSOC)['total_price'], 0);
    $statisticsall = "
📊 آمار کلی ربات  

📌 تعداد کاربران : $statistics نفر
📌 تعداد کاربرانی که خرید داشتند : $statisticsorder نفر
📌 تعداد اکانت های تست گرفته شده : $count_usertest نفر
📌 تعداد فروش کل : $invoice عدد
📌 جمع فروش کل : $invoicesum تومان
";
    sendmessage($from_id, $statisticsall, null, 'HTML');
} elseif ($text == "💰 تنظیم قیمت محصول") {
    if (!is_file('product.json')) {
        file_put_contents('product.json', "{}");
    }
    $product = [];
    $getdataproduct = $pdo->prepare("SELECT * FROM product WHERE agent = ?");
    $getdataproduct->bindValue(1, $userbot['agent'], PDO::PARAM_STR);
    $getdataproduct->execute();
    while ($row = ($getdataproduct)->fetch(PDO::FETCH_ASSOC)) {
        $panel = select("marzban_panel", "*", "name_panel", $row['Location'], "select");
        if (in_array($panel['name_panel'], $hide_panel))
            continue;
        $product[] = [$row['name_product']];
    }
    $list_product = [
        'keyboard' => [],
        'resize_keyboard' => true,
    ];
    $list_product['keyboard'][] = [
        ['text' => "بازگشت به منوی ادمین"],
    ];
    foreach ($product as $button) {
        $list_product['keyboard'][] = [
            ['text' => $button[0]]
        ];
    }
    $json_list_product_list_admin = json_encode($list_product);
    sendmessage($from_id, "از لیست زیر محصولی که می خواهید قیمت تنظیم نمایید را انتخاب کنید", $json_list_product_list_admin, 'HTML');
    step("selectproductprice", $from_id);
} elseif ($user['step'] == "selectproductprice") {
    $product = select("product", "*", "name_product", $text, "select");
    if ($product == false) {
        sendmessage($from_id, "❌ محصول انتخابی وجود ندارد.", null, 'HTML');
        return;
    }
    savedata("clear", "code_product", $product['code_product']);
    step("getpriceproduct", $from_id);
    if (intval($userbot['pricediscount']) != 0) {
        $resultper = ($product['price_product'] * $userbot['pricediscount']) / 100;
        $product['price_product'] = $product['price_product'] - $resultper;
    }
    sendmessage($from_id, "📌  قیمت خود را ارسال کنید
قیمت پایه :{$product['price_product']}", $backadmin, 'HTML');
} elseif ($user['step'] == "getpriceproduct") {
    $userdata = json_decode($user['Processing_value'], true);
    $product = select("product", "*", "code_product", $userdata['code_product'], "select");
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], null, 'HTML');
        return;
    }
    if (intval($text) < intval($product['price_product'])) {
        sendmessage($from_id, "❌ قیمت شما کوچیک تر از قیمت پایه است.", null, 'HTML');
        return;
    }
    $productlist = json_decode(file_get_contents('product.json'), true);
    $productlist[$product['code_product']] = intval($text);
    file_put_contents('product.json', json_encode($productlist));
    step("home", $from_id);
    sendmessage($from_id, "✅ قیمت با موفقیت تنظیم گردید.", $keyboardprice, 'HTML');
} elseif ($text == "💰 تنظیمات فروشگاه") {
    sendmessage($from_id, "📌 یک گزینه را انتخاب کنید.", $keyboardprice, 'HTML');
} elseif ($text == "⚙️ وضعیت قابلیت ها") {
    $status_custom = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['show_product']];
    $status_note = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['active_step_note']];
    $Bot_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['Status']['statusSubject'], 'callback_data' => "subjectde"],
                ['text' => $textbotlang['Admin']['Status']['subject'], 'callback_data' => "subject"],
            ],
            [
                ['text' => $status_custom, 'callback_data' => "editstsuts-statusvolume-{$setting['show_product']}"],
                ['text' => "🛍 فروش  حجم دلخواه", 'callback_data' => "statuscustomvolume"],
            ],
            [
                ['text' => $status_note, 'callback_data' => "editstsuts-statusnote-{$setting['active_step_note']}"],
                ['text' => "✏️ یادداشت ", 'callback_data' => "statusnote"],
            ]
        ]
    ]);
    sendmessage($from_id, "در این بخش می توانید قابلیت های زیر را خاموش یا روشن کنید", $Bot_Status, 'HTML');
} elseif (preg_match('/^editstsuts-(.*)-(.*)/', $datain, $dataget)) {
    $type = $dataget[1];
    $value = $dataget[2];
    if ($type == "statusvolume") {
        if ($value == false) {
            $valuenew = true;
        } else {
            $valuenew = false;
        }
        $setting['show_product'] = $valuenew;
        update("botsaz", "setting", json_encode($setting), "bot_token", $ApiToken);
    } elseif ($type == "statusnote") {
        if ($value == false) {
            $valuenew = true;
        } else {
            $valuenew = false;
        }
        $setting['active_step_note'] = $valuenew;
        update("botsaz", "setting", json_encode($setting), "bot_token", $ApiToken);
    }
    $dataBase = select("botsaz", "*", "bot_token", $ApiToken, "select");
    $setting = json_decode($dataBase['setting'], true);
    $status_custom = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['show_product']];
    $status_note = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['active_step_note']];
    $Bot_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['Status']['statusSubject'], 'callback_data' => "subjectde"],
                ['text' => $textbotlang['Admin']['Status']['subject'], 'callback_data' => "subject"],
            ],
            [
                ['text' => $status_custom, 'callback_data' => "editstsuts-statusvolume-{$setting['show_product']}"],
                ['text' => "🛍 فروش  حجم دلخواه", 'callback_data' => "statuscustomvolume"],
            ],
            [
                ['text' => $status_note, 'callback_data' => "editstsuts-statusnote-{$setting['active_step_note']}"],
                ['text' => "✏️ یادداشت ", 'callback_data' => "statusnote"],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, "در این بخش می توانید قابلیت های زیر را خاموش یا روشن کنید", $Bot_Status);
} elseif ($text == "📝 تنظیم متون") {
    sendmessage($from_id, "📌 برای تغییر متن یکی از گزینه های زیر را انتخاب نمایید", $keyboard_change_price, 'HTML');
} elseif ($text == "💎 متن کارت") {
    sendmessage($from_id, "📌 جهت تنظیم متن شماره کارت متن جدید را ارسال نمایید. توضیحات فعلی :", $backadmin, 'HTML');
    sendmessage($from_id, $setting['cart_info'], $backadmin, 'HTML');
    step("getcartinfo", $from_id);
} elseif ($user['step'] == "getcartinfo") {
    sendmessage($from_id, "✅ توضیحات با موفقیت ذخیره گردید.", $keyboard_change_price, 'HTML');
    $setting['cart_info'] = $text;
    update("botsaz", "setting", json_encode($setting), "bot_token", $ApiToken);
    step("home", $from_id);
} elseif ($text == "🛍 دکمه خرید") {
    sendmessage($from_id, "📌 جهت تنظیم متن جدید را ارسال نمایید. توضیحات فعلی :", $backadmin, 'HTML');
    sendmessage($from_id, $text_bot_var['btn_keyboard']['buy'], $backadmin, 'HTML');
    step("gettext_buy", $from_id);
} elseif ($user['step'] == "gettext_buy") {
    sendmessage($from_id, "✅ متن با موفقیت ذخیره گردید.", $keyboard_change_price, 'HTML');
    $text_bot_var['btn_keyboard']['buy'] = $text;
    file_put_contents('text.json', json_encode($text_bot_var, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    step("home", $from_id);
} elseif ($text == "🔑 دکمه تست") {
    sendmessage($from_id, "📌 جهت تنظیم متن جدید را ارسال نمایید. توضیحات فعلی :", $backadmin, 'HTML');
    sendmessage($from_id, $text_bot_var['btn_keyboard']['test'], $backadmin, 'HTML');
    step("gettext_test", $from_id);
} elseif ($user['step'] == "gettext_test") {
    sendmessage($from_id, "✅ متن با موفقیت ذخیره گردید.", $keyboard_change_price, 'HTML');
    $text_bot_var['btn_keyboard']['test'] = $text;
    file_put_contents('text.json', json_encode($text_bot_var, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    step("home", $from_id);
} elseif ($text == "🛒 دکمه سرویس های من") {
    sendmessage($from_id, "📌 جهت تنظیم متن جدید را ارسال نمایید. توضیحات فعلی :", $backadmin, 'HTML');
    sendmessage($from_id, $text_bot_var['btn_keyboard']['my_service'], $backadmin, 'HTML');
    step("gettext_my_service", $from_id);
} elseif ($user['step'] == "gettext_my_service") {
    sendmessage($from_id, "✅ متن با موفقیت ذخیره گردید.", $keyboard_change_price, 'HTML');
    $text_bot_var['btn_keyboard']['my_service'] = $text;
    file_put_contents('text.json', json_encode($text_bot_var, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    step("home", $from_id);
} elseif ($text == "👤 دکمه حساب کاربری") {
    sendmessage($from_id, "📌 جهت تنظیم متن جدید را ارسال نمایید. توضیحات فعلی :", $backadmin, 'HTML');
    sendmessage($from_id, $text_bot_var['btn_keyboard']['wallet'], $backadmin, 'HTML');
    step("gettext_wallet", $from_id);
} elseif ($user['step'] == "gettext_wallet") {
    sendmessage($from_id, "✅ متن با موفقیت ذخیره گردید.", $keyboard_change_price, 'HTML');
    $text_bot_var['btn_keyboard']['wallet'] = $text;
    file_put_contents('text.json', json_encode($text_bot_var, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    step("home", $from_id);
} elseif ($text == "☎️ متن دکمه پشتیبانی") {
    sendmessage($from_id, "📌 جهت تنظیم متن جدید را ارسال نمایید. توضیحات فعلی :", $backadmin, 'HTML');
    sendmessage($from_id, $text_bot_var['btn_keyboard']['support'], $backadmin, 'HTML');
    step("gettext_support", $from_id);
} elseif ($user['step'] == "gettext_support") {
    sendmessage($from_id, "✅ متن با موفقیت ذخیره گردید.", $keyboard_change_price, 'HTML');
    $text_bot_var['btn_keyboard']['support'] = $text;
    file_put_contents('text.json', json_encode($text_bot_var, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    step("home", $from_id);
} elseif ($text == "💸 متن مرحله افزایش موجودی") {
    sendmessage($from_id, "📌 جهت تنظیم متن جدید را ارسال نمایید. توضیحات فعلی :", $backadmin, 'HTML');
    sendmessage($from_id, $text_bot_var['text_account']['add_balance'], $backadmin, 'HTML');
    step("gettext_add_balance", $from_id);
} elseif ($user['step'] == "gettext_add_balance") {
    sendmessage($from_id, "✅ متن با موفقیت ذخیره گردید.", $keyboard_change_price, 'HTML');
    $text_bot_var['text_account']['add_balance'] = $text;
    file_put_contents('text.json', json_encode($text_bot_var, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    step("home", $from_id);
} elseif ($text == "📣 جوین اجباری") {
    sendmessage($from_id, "📌 کانال خود را جهت تنظیم جوین اجباری ارسال کنید
⚠️ ربات باید ادمین کانال باشد در غیراینصورت این قابلیت فعال نخواهد شد
⚠️ نام کاربری کانال باید بدون @ ارسال شود", $backadmin, 'HTML');
    step("get_channel_id", $from_id);
} elseif ($user['step'] == "get_channel_id") {
    sendmessage($from_id, "✅ کانال با موفقیت ذخیره گردید.", $keyboardadmin, 'HTML');
    $setting['channel'] = $text;
    update("botsaz", "setting", json_encode($setting), "bot_token", $ApiToken);
    step("home", $from_id);
} elseif ($text == "✏️ تنظیم نام محصول") {
    if (!is_file('product_name.json')) {
        file_put_contents('product_name.json', "{}");
    }
    $product = [];
    $getdataproduct = $pdo->prepare("SELECT * FROM product WHERE agent = ?");
    $getdataproduct->bindValue(1, $userbot['agent'], PDO::PARAM_STR);
    $getdataproduct->execute();
    while ($row = ($getdataproduct)->fetch(PDO::FETCH_ASSOC)) {
        $panel = select("marzban_panel", "*", "name_panel", $row['Location'], "select");
        if (in_array($panel['name_panel'], $hide_panel))
            continue;
        $product[] = [$row['name_product']];
    }
    $list_product = [
        'keyboard' => [],
        'resize_keyboard' => true,
    ];
    foreach ($product as $button) {
        $list_product['keyboard'][] = [
            ['text' => $button[0]]
        ];
    }
    $list_product['keyboard'][] = [
        ['text' => "بازگشت به منوی ادمین"],
    ];
    $json_list_product_list_admin = json_encode($list_product);
    sendmessage($from_id, "از لیست زیر محصولی که می خواهید نام تنظیم نمایید را انتخاب کنید", $json_list_product_list_admin, 'HTML');
    step("get_product_for_edit_name", $from_id);
} elseif ($user['step'] == "get_product_for_edit_name") {
    $product = select("product", "*", "name_product", $text, "select");
    if ($product == false) {
        sendmessage($from_id, "❌ محصول انتخابی وجود ندارد.", null, 'HTML');
        return;
    }
    savedata("clear", "code_product", $product['code_product']);
    step("get_new_name", $from_id);
    sendmessage($from_id, "📌  نام خود را ارسال کنید", $backadmin, 'HTML');
} elseif ($user['step'] == "get_new_name") {
    $userdata = json_decode($user['Processing_value'], true);
    $product = select("product", "*", "code_product", $userdata['code_product'], "select");
    $productlist = json_decode(file_get_contents('product_name.json'), true);
    $productlist[$product['code_product']] = $text;
    file_put_contents('product_name.json', json_encode($productlist));
    step("home", $from_id);
    sendmessage($from_id, "✅ نام با موفقیت تنظیم گردید.", $keyboardprice, 'HTML');
} elseif ($text == "🆕 آپدیت ربات") {
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "نسخه پایدار", 'callback_data' => 'update'],
            ],
        ]
    ]);
    $textupdate = "📍 مدیر عزیز  برای آپدیت گزینه زیر را انتخاب نمایید";
    sendmessage($from_id, $textupdate, $Response, 'HTML');
} elseif ($datain == "update") {
    if (!isShellExecAvailable()) {
        sendmessage($from_id, "❌ آپدیت روی این سرور امکان‌پذیر نیست.\n\nتابع shell_exec غیرفعال است و فایل‌های نسخه جدید بدون آن کپی نمی‌شوند. برای آپدیت باید ربات روی سروری با دسترسی shell_exec اجرا شود.", null, 'HTML');
        return;
    }

    $source = dirname(__DIR__) . "/update";
    $getversionnow = file_get_contents($source . '/version');

    if ($getversionnow == $version) {
        sendmessage($from_id, "کاربر عزیز نسخه جدیدی منتشر نشده است", null, 'HTML');
        return;
    }

    sendmessage($from_id, "✅ ربات شما با موفقیت از نسخه  $version به نسخه $getversionnow آپدیت گردید.", null, 'HTML');

    $old_text = null;
    $has_old_text = false;

    if (is_file('text.json')) {
        $old_text = json_decode(file_get_contents('text.json'), true);
        $has_old_text = true;
    }

    $destination = getcwd();
    $command = "cp -r $source/* $destination 2>&1";
    $output = shell_exec($command);
    if ($has_old_text) {
        $new_text = json_decode(file_get_contents('text.json'), true);
        $merged = array_replace_recursive($new_text, $old_text);
        file_put_contents('text.json', json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}