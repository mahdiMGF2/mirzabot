<?php
$version = file_get_contents('version');
date_default_timezone_set('Asia/Tehran');
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');
ini_set('max_execution_time', '600');
$rootPath = filter_input(INPUT_SERVER, 'DOCUMENT_ROOT');
$PHP_SELF = filter_input(INPUT_SERVER, 'PHP_SELF');
$Pathfile = dirname(dirname($PHP_SELF, 2));
$Pathfiles = rtrim($rootPath . $Pathfile, '/\\') . '/';
require_once 'config.php';
require_once $Pathfiles . 'function.php';
require_once $Pathfiles . 'config.php';
require_once $Pathfiles . 'jdf.php';
require_once $Pathfiles . 'panels.php';
require_once 'func.php';
require_once 'botapi.php';
require_once 'keyboard.php';
require_once $Pathfiles . 'vendor/autoload.php';
$ManagePanel = new ManagePanel();

$text_bot_var = json_decode(file_get_contents('text.json'), true);
if (!checktelegramip())
    die("Unauthorized access");

$textbotlang = languagechange();
$dataBase = select("botsaz", "*", "bot_token", $ApiToken, "select");
$agentWebhookSecret = ensureAgentWebhookSecret($dataBase);
if (!$agentWebhookSecret['created'] && $agentWebhookSecret['secret'] !== '' && !webhookSecretMatches($agentWebhookSecret['secret']))
    die("Unauthorized access");
$admin_ids = json_decode($dataBase['admin_ids']);
$setting = json_decode($dataBase['setting'], true);
if (!empty($setting['channel'])) {
    $channel = channel_check("@" . $setting['channel']);
    if (count($channel) != 0) {
        $keyboardchannel = [
            'inline_keyboard' => [
                [
                    ['text' => "عضویت در کانال", 'url' => "https://t.me/" . $setting['channel']]
                ],
                [
                    ['text' => "✅ عضو شدم", 'callback_data' => "confirmchannel"]
                ],
            ]
        ];
        $keyboardchannel = json_encode($keyboardchannel);
        sendmessage($from_id, "📌 جهت استفاده از تمامی قابلیت های ربات در کنال زیر عضو شده و سپس روی دکمه عضو شدم کلیک کنید", $keyboardchannel, "html");
        return;
    }
    if ($datain == "confirmchannel") {
        deletemessage($from_id, $message_id);
        sendmessage($from_id, "✅  عضویت شما با موفقیت تایید شد", $keyboard, 'HTML');
    }
}

if (!isset($setting['show_product'])) {
    $setting['show_product'] = false;
    update("botsaz", "setting", json_encode($setting), "bot_token", $ApiToken);
}
if (!isset($setting['active_step_note'])) {
    $setting['active_step_note'] = false;
    update("botsaz", "setting", json_encode($setting), "bot_token", $ApiToken);
}
$settingmain = select("setting", "*", null, null, "select");
$showcard = 1;
$users_ids = select("user", "*", "bottype", $ApiToken, "FETCH_COLUMN");
if (!is_dir('data')) {
    mkdir('data');
}
if (!in_array($from_id, $users_ids) && $settingmain['statusnewuser'] == "onnewuser" && $from_id != 0) {

    $newuser = sprintf($textbotlang['Admin']['reportgroup']['newUser'], $first_name, $username, "<a href = \"tg://user?id=$from_id\">$from_id</a>");
    foreach ($admin_ids as $admin) {
        sendmessage($admin, $newuser, null, 'HTML');
    }
}

if ($from_id != 0) {
    $randomString = bin2hex(random_bytes(6));
    $date = time();
    $valueverify = 1;
    if (!is_dir("data/$from_id")) {
        mkdir("data/$from_id");
        $data_user = json_encode(array(
            "Balance" => 0,
        ));
        file_put_contents("data/$from_id/$from_id.json", $data_user);
    }
    $stmt = $pdo->prepare("INSERT IGNORE INTO user (id , step,limit_usertest,User_Status,number,Balance,pagenumber,username,agent,message_count,last_message_time,affiliates,affiliatescount,cardpayment,number_username,namecustom,register,verify,codeInvitation,pricediscount,maxbuyagent,joinchannel,score,bottype,status_cron) VALUES (:from_id, 'none',:limit_usertest_all,'Active','none','0','1',:username,'f','0','0','0','0',:showcard,'100','none',:date,:verifycode,:codeInvitation,'0','0','0','0',:bottype,'1')");
    $stmt->bindParam(':bottype', $ApiToken);
    $stmt->bindParam(':from_id', $from_id);
    $stmt->bindParam(':limit_usertest_all', $settingmain['limit_usertest_all']);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':showcard', $showcard);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':verifycode', $valueverify);
    $stmt->bindParam(':codeInvitation', $randomString);
    $stmt->execute();
}
$user = select("user", "*", "id", $from_id, "select");
$user['Balance'] = json_decode(file_get_contents("data/$from_id/$from_id.json"), true)['Balance'];
$usernameinvoice = select("invoice", "username", null, null, "FETCH_COLUMN");
$buyreport = select("topicid", "idreport", "report", "buyreport", "select")['idreport'];
$reportnight = select("topicid", "idreport", "report", "reportnight", "select")['idreport'];
$reporttest = select("topicid", "idreport", "report", "reporttest", "select")['idreport'];
$errorreport = select("topicid", "idreport", "report", "errorreport", "select")['idreport'];
$porsantreport = select("topicid", "idreport", "report", "porsantreport", "select")['idreport'];
$reportcron = select("topicid", "idreport", "report", "reportcron", "select")['idreport'];
$otherservice = select("topicid", "idreport", "report", "otherservice", "select")['idreport'];

$paymentreports = select("topicid", "idreport", "report", "paymentreport", "select")['idreport'];
$admin_idsmain = select("admin", "id_admin", null, null, "FETCH_COLUMN");
$id_invoice = select("invoice", "id_invoice", null, null, "FETCH_COLUMN");
$userbot = select("user", "*", "id", $dataBase['id_user'], "select");
if ($user['bottype'] != $ApiToken) {
    update("user", "bottype", $ApiToken, "id", $from_id);
}
if ($user['username'] != $username) {
    update("user", "username", $username, "id", $from_id);
}
if ($text == "/start") {
    $textstart = "✋سلام $first_name عزیز به ربات ما خوش اومدی.

برای ادامه  یک بخش را انتخاب کنید:";
    if (!in_array($from_id, $admin_ids)) {
        if ($setting['minpricetime'] > $setting['pricetime'] or $setting['minpricevolume'] > $setting['pricevolume']) {
            foreach ($admin_ids as $admin) {
                sendmessage($admin, "❌ ادمین عزیز قیمت حجم یا زمان بروزرسانی شده است جهت فعالسازی ربات به پنل ادمین مراجعه و قیمت های جدید را اعمال کنید.", null, 'HTML');
            }
            sendmessage($from_id, "❌ درحال حاضر ربات در حال بروزرسانی است ساعتی دیگر مراجعه نمایید.", null, 'HTML');
            return;
        }
    }
    sendmessage($from_id, $textstart, $keyboard, 'html');
    update("user", "Processing_value", "0", "id", $from_id);
    update("user", "Processing_value_one", "0", "id", $from_id);
    update("user", "Processing_value_tow", "0", "id", $from_id);
    update("user", "Processing_value_four", "0", "id", $from_id);
    step('home', $from_id);
    return;
} elseif ($text == "🏠 بازگشت به منوی اصلی" || $datain == "backuser") {
    if ($datain == "backuser")
        deletemessage($from_id, $message_id);
    sendmessage($from_id, "▶️ به منوی اصلی بازگشتید!", $keyboard, 'html');
    step('home', $from_id);
    update("user", "Processing_value", "0", "id", $from_id);
    update("user", "Processing_value_one", "0", "id", $from_id);
    update("user", "Processing_value_tow", "0", "id", $from_id);
    update("user", "Processing_value_four", "0", "id", $from_id);
    return;
} elseif ($text == $text_bot_var['btn_keyboard']['wallet'] or $datain == "account") {
    $dateacc = jdate('Y/m/d');
    $current_time = time();
    $timeacc = jdate('H:i:s', $current_time);
    $first_name = htmlspecialchars($first_name);
    $Balanceuser = number_format($user['Balance'], 0);
    $stmt = $pdo->prepare("SELECT * FROM Payment_report WHERE id_user = :from_id AND payment_Status = 'paid' AND bottype = :apibot");
    $stmt->execute([
        ':from_id' => $from_id,
        ':apibot' => $ApiToken
    ]);
    $countpayment = $stmt->rowCount();
    $userjoin = jdate('Y/m/d H:i:s', $user['register']);
    $text_account = "
🗂 اطلاعات حساب کاربری شما :


👤 نام: <code>$first_name</code>
⌚️زمان ثبت نام : $userjoin
💡 شناسه کاربری: <code>$from_id</code>
💰 موجودی: $Balanceuser تومان
💵 تعداد فاکتور های پرداخت شده : $countpayment عدد

📆 $dateacc → ⏰ $timeacc";
    if ($datain == "account") {
        step("home", $from_id);
        Editmessagetext($from_id, $message_id, $text_account, $KeyboardBalance);
    } else {
        sendmessage($from_id, $text_account, $KeyboardBalance, 'HTML');
    }
    return;
} elseif ($text == $text_bot_var['btn_keyboard']['my_service'] or $datain == "backorder") {
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :id_user AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND bottype = :apibot");
    $stmt->bindParam(':id_user', $from_id);
    $stmt->bindParam(':apibot', $ApiToken);
    $stmt->execute();
    $invoices = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($stmt->rowCount() == 0) {
        sendmessage($from_id, "⛔️ شما هیچ سرویسی فعالی ندارید", null, 'html');
        return;
    }
    $pages = 1;
    update("user", "pagenumber", $pages, "id", $from_id);
    $page = 1;
    $items_per_page = 20;
    $start_index = ($page - 1) * $items_per_page;
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :mp1 AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR status = 'send_on_hold') AND bottype = :mp2 ORDER BY time_sell DESC LIMIT :mp3, :mp4");
    $stmt->execute([':mp1' => $from_id, ':mp2' => $ApiToken, ':mp3' => $start_index, ':mp4' => $items_per_page]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data = "";
        if ($row != null)
            $data = " | {$row['note']}";
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => "✨" . $row['username'] . $data . "✨",
                'callback_data' => "product_" . $row['id_invoice']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => "بعدی",
            'callback_data' => 'next_page'
        ]
    ];
    $backuser = [
        [
            'text' => "🔙 بازگشت به منوی اصلی",
            'callback_data' => 'backuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backuser;
    $keyboard_json = json_encode($keyboardlists);
    if ($datain == "backorder") {
        Editmessagetext($from_id, $message_id, "🛍 برای مشاهده اطلاعات سرویس خود از لیست زیر سرویس خود را انتخاب نمایید", $keyboard_json);
    } else {
        sendmessage($from_id, "🛍 برای مشاهده اطلاعات سرویس خود از لیست زیر سرویس خود را انتخاب نمایید", $keyboard_json, 'html');
    }
} elseif ($datain == 'next_page') {
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :id_user AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND bottype = :apibot");
    $stmt->bindParam(':id_user', $from_id);
    $stmt->bindParam(':apibot', $ApiToken);
    $stmt->execute();
    $numpage = $stmt->rowCount();
    $page = $user['pagenumber'];
    $items_per_page = 20;
    $sum = $user['pagenumber'] * $items_per_page;
    if ($sum > $numpage) {
        $next_page = 1;
    } else {
        $next_page = $page + 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :mp5 AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR status = 'send_on_hold') AND bottype = :mp6 ORDER BY time_sell DESC LIMIT :mp7, :mp8");
    $stmt->execute([':mp5' => $from_id, ':mp6' => $ApiToken, ':mp7' => $start_index, ':mp8' => $items_per_page]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => "✨" . $row['username'] . "✨",
                'callback_data' => "product_" . $row['id_invoice']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => "بعدی",
            'callback_data' => 'next_page'
        ],
        [
            'text' => "قبلی",
            'callback_data' => 'previous_page'
        ]
    ];
    $backuser = [
        [
            'text' => "🔙 بازگشت به منوی اصلی",
            'callback_data' => 'backuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backuser;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, "🛍 برای مشاهده اطلاعات سرویس خود از لیست زیر سرویس خود را انتخاب نمایید", $keyboard_json);
} elseif ($datain == 'previous_page') {
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :id_user AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND bottype = :apibot");
    $stmt->bindParam(':id_user', $from_id);
    $stmt->bindParam(':apibot', $ApiToken);
    $stmt->execute();
    $numpage = $stmt->rowCount();
    $page = $user['pagenumber'];
    $items_per_page = 20;
    $sum = $user['pagenumber'] * $items_per_page;
    if ($sum > $numpage) {
        $previous_page = 1;
    } else {
        $previous_page = $page - 1;
    }
    $start_index = ($previous_page - 1) * $items_per_page;
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :mp9 AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR status = 'send_on_hold') AND bottype = :mp10 ORDER BY time_sell DESC LIMIT :mp11, :mp12");
    $stmt->execute([':mp9' => $from_id, ':mp10' => $ApiToken, ':mp11' => $start_index, ':mp12' => $items_per_page]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => "✨" . $row['username'] . "✨",
                'callback_data' => "product_" . $row['id_invoice']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => "بعدی",
            'callback_data' => 'next_page'
        ],
        [
            'text' => "قبلی",
            'callback_data' => 'previous_page'
        ]
    ];
    $backuser = [
        [
            'text' => "🔙 بازگشت به منوی اصلی",
            'callback_data' => 'backuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backuser;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $previous_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, "🛍 برای مشاهده اطلاعات سرویس خود از لیست زیر سرویس خود را انتخاب نمایید", $keyboard_json);
} elseif ($text == $text_bot_var['btn_keyboard']['support']) {
    $textsupport = "📞 برای ارتباط با ما  روی دکمه زیر کلیک کنید";
    $Keyboardsupport = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "📞 ارتباط با پشتیبانی", 'url' => 'https://t.me/' . $setting['support_username']],
            ],
        ]
    ]);
    sendmessage($from_id, $textsupport, $Keyboardsupport, 'html');
} elseif ($text == $text_bot_var['btn_keyboard']['test']) {
    $locationproduct = select("marzban_panel", "*", "TestAccount", "ONTestAccount", "count");
    if ($locationproduct == 0) {
        sendmessage($from_id, "❌ سرویس تست درحال حاضر غیرفعال می باشد.", null, 'HTML');
        return;
    }
    if ($locationproduct != 1) {
        if ($user['limit_usertest'] <= 0) {
            sendmessage($from_id, "⚠️ محدودیت دریافت اکانت تست شما به پایان رسیده است .", $keyboard, 'html');
            return;
        }
        sendmessage($from_id, "📌 موقعیت سرویس خود را انتخاب کنید.", $list_marzban_usertest, 'html');
    }
}
if ($user['step'] == "createusertest" || preg_match('/locationtest_(.*)/', $datain, $dataget) || ($text == $text_bot_var['btn_keyboard']['test'])) {
    $userlimit = select("user", "*", "id", $from_id, "select");
    if ($userlimit['limit_usertest'] <= 0) {
        sendmessage($from_id, "⚠️ محدودیت دریافت اکانت تست شما به پایان رسیده است .", $keyboard, 'html');
        return;
    }
    $locationproduct = select("marzban_panel", "*", "TestAccount", "ONTestAccount", "count");
    if ($locationproduct == 1) {
        $panel = select("marzban_panel", "*", "TestAccount", "ONTestAccount", "select");
        if ($panel['hide_user'] != null) {
            $list_user = json_decode($panel['hide_user'], true);
            if (in_array($from_id, $list_user)) {
                sendmessage($from_id, "❌ سرویس تست درحال حاضر غیرفعال می باشد.", null, 'HTML');
                return;
            }
        }
        $location = $panel['code_panel'];
    } else {
        if (isset($dataget[1])) {
            $location = $dataget[1];
        } else {
            if ($user['step'] != "createusertest") {
                return;
            } else {
                $location = $user['Processing_value_one'];
            }
        }
    }
    $marzban_list_get = select("marzban_panel", "*", "code_panel", $location, "select");
    if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
        if ($user['step'] != "createusertest") {
            step('createusertest', $from_id);
            update("user", "Processing_value_one", $location, "id", $from_id);
            sendmessage($from_id, $textbotlang['users']['selectusername'], $backuser, 'html');
            return;
        }
    } else {
        $name_panel = $location;
    }
    if ($user['step'] == "createusertest") {
        $name_panel = $user['Processing_value_one'];
        if (!preg_match('~(?!_)^[a-z][a-z\d_]{2,32}(?<!_)$~i', $text)) {
            sendmessage($from_id, $textbotlang['users']['invalidusername'], $backuser, 'HTML');
            return;
        }
    } else {
        deletemessage($from_id, $message_id);
    }
    if ($marzban_list_get['type'] == "Manualsale") {
        $stmt = $pdo->prepare("SELECT * FROM manualsell WHERE codepanel = :codepanel AND codeproduct = :codeproduct AND status = 'active'");
        $value = "usertest";
        $stmt->bindParam(':codepanel', $marzban_list_get['code_panel']);
        $stmt->bindParam(':codeproduct', $value);
        $stmt->execute();
        $configexits = $stmt->rowCount();
        if (intval($configexits) == 0) {
            sendmessage($from_id, "❌ موجودی این سرویس به پایان رسیده.", null, 'HTML');
            return;
        }
    }
    $limit_usertest = $userlimit['limit_usertest'] - 1;
    update("user", "limit_usertest", $limit_usertest, "id", $from_id);
    $randomString = bin2hex(random_bytes(4));
    $text = strtolower($text);
    $marzban_list_get = select("marzban_panel", "*", "code_panel", $name_panel, "select");
    $text = strtolower($text);
    $username_ac = generateUsername($from_id, $marzban_list_get['MethodUsername'], $user['username'], $randomString, $text, $marzban_list_get['namecustom'], $user['namecustom']);
    $username_ac = strtolower($username_ac);
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $username_ac);
    $random_number = rand(1000000, 9999999);
    if (isset($DataUserOut['username']) || in_array($username_ac, $usernameinvoice)) {
        $username_ac = $random_number . "_" . $username_ac;
    }
    $datac = array(
        'expire' => strtotime(date("Y-m-d H:i:s", strtotime("+" . $marzban_list_get['time_usertest'] . "hours"))),
        'data_limit' => $marzban_list_get['val_usertest'] * 1048576,
        'from_id' => $from_id,
        'username' => $username,
        'type' => 'usertest_' . $dataBase['username']
    );
    $date = time();
    $notifctions = json_encode(array(
        'volume' => false,
        'time' => false,
    ));
    $stmt = $pdo->prepare("INSERT IGNORE INTO invoice (id_user, id_invoice, username,time_sell, Service_location, name_product, price_product, Volume, Service_time,Status,bottype,notifctions) VALUES (?, ?, ?, ?, ?, ?, ?,?,?,?,?,?)");
    $Status = "active";
    $info_product['name_product'] = "سرویس تست";
    $info_product['price_product'] = "0";
    $Status = "active";
    $stmt->execute([$from_id, $randomString, $username_ac, $date, $marzban_list_get['name_panel'], $info_product['name_product'], $info_product['price_product'], $marzban_list_get['val_usertest'], $marzban_list_get['time_usertest'], $Status, $ApiToken, $notifctions]);
    $dataoutput = $ManagePanel->createUser($marzban_list_get['name_panel'], "usertest", $username_ac, $datac);
    if ($dataoutput['username'] == null) {
        $dataoutput['msg'] = json_encode($dataoutput['msg']);
        sendmessage($from_id, $textbotlang['users']['usertest']['errorcreat'], $keyboard, 'html');
        $texterros = "
⭕️ یک کاربر قصد دریافت اکانت  تست داشت که ساخت کانفیگ با خطا مواجه شده و به کاربر کانفیگ داده نشد
✍️ دلیل خطا : 
{$dataoutput['msg']}
آیدی کابر : $from_id
نام کاربری کاربر : @$username
نام پنل : {$marzban_list_get['name_panel']}";
        if (strlen($settingmain['Channel_Report']) > 0) {
            telegram('sendmessage', [
                'chat_id' => $settingmain['Channel_Report'],
                'message_thread_id' => $errorreport,
                'text' => $texterros,
                'parse_mode' => "HTML"
            ], $APIKEY);
        }
        step('home', $from_id);
        update("invoice", "Status", "Unsuccessful", "id_invoice", $randomString);
        return;
    }
    $output_config_link = "";
    $config = "";
    if ($marzban_list_get['sublink'] == "onsublink") {
        $output_config_link = $dataoutput['subscription_url'];
    }
    if ($marzban_list_get['config'] == "onconfig") {
        foreach ($dataoutput['configs'] as $configs) {
            $config .= "\n" . $configs;
        }
    }
    $datatextbot['textaftertext'] = "✅ سرویس با موفقیت ایجاد شد

👤 نام کاربری سرویس : {username}
🌿 نام سرویس:  {name_service}
‏🇺🇳 لوکیشن: {location}
⏳ مدت زمان: {day}  ساعت
🗜 حجم سرویس:  {volume} مگابایت

لینک اتصال:
{config}";
    if ($marzban_list_get['type'] == "WGDashboard") {
        $datatextbot['textaftertext'] = "✅ سرویس با موفقیت ایجاد شد

👤 نام کاربری سرویس : {username}
🌿 نام سرویس:  {name_service}
‏🇺🇳 لوکیشن: {location}
⏳ مدت زمان: {day}  ساعت
🗜 حجم سرویس:  {volume} مگابایت

🧑‍🦯 شما میتوانید شیوه اتصال را  با فشردن دکمه زیر و انتخاب سیستم عامل خود را دریافت کنید";
    }
    if ($marzban_list_get['type'] == "ibsng") {
        $datatextbot['textafterpay'] = $datatextbot['textafterpayibsng'];
    }
    $textcreatuser = str_replace('{username}', $dataoutput['username'], $datatextbot['textaftertext']);
    $textcreatuser = str_replace('{name_service}', "تست", $textcreatuser);
    $textcreatuser = str_replace('{location}', $marzban_list_get['name_panel'], $textcreatuser);
    $textcreatuser = str_replace('{day}', $marzban_list_get['time_usertest'], $textcreatuser);
    $textcreatuser = str_replace('{volume}', $marzban_list_get['val_usertest'], $textcreatuser);
    $textcreatuser = str_replace('{config}', "<code>{$config}{$output_config_link}</code>", $textcreatuser);
    if ($marzban_list_get['type'] == "ibsng" || $marzban_list_get['type'] == "ibsng") {
        $textcreatuser = str_replace('{password}', $dataoutput['subscription_url'], $textcreatuser);
        update("invoice", "user_info", $dataoutput['subscription_url'], "id_invoice", $randomString);
    }
    if ($marzban_list_get['sublink'] == "onsublink") {
        if ($marzban_list_get['type'] == "WGDashboard") {
            $urlimage = "{$marzban_list_get['inboundid']}_{$dataoutput['username']}.conf";
            file_put_contents($urlimage, $output_config_link);
            telegram('senddocument', [
                'chat_id' => $from_id,
                'document' => new CURLFile($urlimage),
                'caption' => $textcreatuser,
                'parse_mode' => "HTML",
            ]);
            unlink($urlimage);
        } else {
            $urlimage = "$from_id$randomString.png";
            $qrCode = createqrcode($output_config_link);
            file_put_contents($urlimage, $qrCode->getString());
            addBackgroundImage($urlimage, $qrCode, $Pathfiles . 'images.jpg');
            telegram('sendphoto', [
                'chat_id' => $from_id,
                'photo' => new CURLFile($urlimage),
                'caption' => $textcreatuser,
                'parse_mode' => "HTML",
            ]);
            unlink($urlimage);
        }
    } elseif ($marzban_list_get['config'] == "onconfig") {
        if (count($dataoutput['configs']) == 1) {
            $urlimage = "$from_id$randomString.png";
            $qrCode = createqrcode($config);
            file_put_contents($urlimage, $qrCode->getString());
            addBackgroundImage($urlimage, $qrCode, $Pathfiles . 'images.jpg');
            telegram('sendphoto', [
                'chat_id' => $from_id,
                'photo' => new CURLFile($urlimage),
                'caption' => $textcreatuser,
                'parse_mode' => "HTML",
            ]);
            unlink($urlimage);
        } else {
            sendmessage($from_id, $textcreatuser, $usertestinfo, 'HTML');
        }
    } else {
        sendmessage($from_id, $textcreatuser, $usertestinfo, 'HTML');
    }
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboard, 'HTML');
    step('home', $from_id);
    if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customTextSequential', 'usernameSequential', 'numericIdSequential', 'agentCustomTextSequential'], true)) {
        $value = intval($user['number_username']) + 1;
        update("user", "number_username", $value, "id", $from_id);
        if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customTextSequential', 'agentCustomTextSequential'], true)) {
            $value = intval($settingmain['numbercount']) + 1;
            update("setting", "numbercount", $value);
        }
    }
    $timejalali = jdate('Y/m/d H:i:s');
    $text_report_admin = "📣 جزئیات ساخت اکانت تست در ربات نماینده ثبت شد .
▫️آیدی عددی کاربر : <code>$from_id</code>
▫️آیدی عددی نماینده : <code>{$userbot['id']}</code>
▫️نام کاربری ربات نماینده :@{$dataBase['username']}
▫️نام کاربری کاربر :@$username
▫️نام کاربری کانفیگ :$username_ac
▫️نام کاربر : $first_name
▫️موقعیت سرویس سرویس : {$marzban_list_get['name_panel']}
▫️زمان خریداری شده : {$marzban_list_get['time_usertest']} ساعت
▫️حجم خریداری شده : {$marzban_list_get['val_usertest']} MB
▫️کد پیگیری: $randomString
▫️زمان خرید : $timejalali";
    if (strlen($settingmain['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $settingmain['Channel_Report'],
            'message_thread_id' => $reporttest,
            'text' => $text_report_admin,
            'parse_mode' => "HTML"
        ], $APIKEY);
    }
}
if ($text == $text_bot_var['btn_keyboard']['buy'] && $setting['active_step_note']) {
    sendmessage($from_id, $textbotlang['users']['sell']['notestep'], $backuser, 'HTML');
    step("statusnamecustom", $from_id);
    return;
} elseif ($text == $text_bot_var['btn_keyboard']['buy'] || $user['step'] == "statusnamecustom") {
    $locationproduct = $pdo->prepare("SELECT * FROM marzban_panel  WHERE status = 'active' AND (agent = ? OR agent = 'all')");
    $locationproduct->bindValue(1, $userbot['agent'], PDO::PARAM_STR);
    $locationproduct->execute();
    if (($locationproduct)->rowCount() == 0) {
        sendmessage($from_id, $textbotlang['users']['sell']['nullPanel'], null, 'HTML');
        return;
    }
    if (($locationproduct)->rowCount() == 1) {
        $location = ($locationproduct)->fetch(PDO::FETCH_ASSOC)['name_panel'];
        $locationproduct = select("marzban_panel", "*", "name_panel", $location, "select");
        $query = "SELECT * FROM product WHERE (Location = '{$locationproduct['name_panel']}' OR Location = '/all')AND agent= '{$userbot['agent']}'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $productnotexits = $stmt->rowCount();
        if ($locationproduct['hide_user'] != null) {
            $list_user = json_decode($locationproduct['hide_user'], true);
            if (in_array($from_id, $list_user)) {
                sendmessage($from_id, $textbotlang['users']['sell']['nullPanel'], null, 'HTML');
                return;
            }
        }
        $stmt = $pdo->prepare("SELECT * FROM invoice WHERE status = 'active' OR status = 'end_of_time' OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold'");
        $stmt->execute();
        $countinovoice = $stmt->rowCount();
        if ($locationproduct['limit_panel'] != "unlimited") {
            if ($countinovoice >= $locationproduct['limit_panel']) {
                sendmessage($from_id, $textbotlang['users']['sell']['capacityFull'], null, 'HTML');
                return;
            }
        }
        if ($user['step'] == "statusnamecustom") {
            savedata('clear', "note", $text);
            savedata('save', "name_panel", $location);
            step("home", $from_id);
        } else {
            savedata('clear', "name_panel", $location);
        }
        $marzban_list_get = $locationproduct;
        if ($productnotexits != 0 and $setting['show_product'] == false) {
            if ($settingmain['statuscategorygenral'] == "offcategorys") {
                $statuscustomvolume = json_decode($locationproduct['customvolume'], true)[$userbot['agent']];
                if ($statuscustomvolume == "1" && $locationproduct['type'] != "Manualsale") {
                    $statuscustom = true;
                } else {
                    $statuscustom = false;
                }
                if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
                    $keyboarddata = "selectproductbuyy_";
                } else {
                    $keyboarddata = "selectproductbuy_";
                }
                $prodcut = KeyboardProduct($marzban_list_get['name_panel'], $query, 0, $keyboarddata, $statuscustom, "backuser", null, $customvolume = "customvolumebuy");
                sendmessage($from_id, "🛍️ لطفاً سرویسی که می‌خواهید خریداری کنید را انتخاب کنید!", $prodcut, 'HTML');
                return;
            } else {
                $nullproduct = select("product", "*", "agent", $userbot['agent'], "count");
                if ($nullproduct == 0) {
                    sendmessage($from_id, $textbotlang['users']['sell']['nullProduct'], null, 'HTML');
                    return;
                }
                sendmessage($from_id, "📌 دسته بندی خود را انتخاب نمایید!", KeyboardCategory($marzban_list_get['name_panel'], $userbot['agent'], "backuser"), 'HTML');
                return;
            }
        } else {
            $marzban_list_get = $locationproduct;
            $eextraprice = $setting['pricevolume'];
            $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
            $mainvolume = $mainvolume[$userbot['agent']];
            $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
            $maxvolume = $maxvolume[$userbot['agent']];
            $textcustom = "📌 حجم درخواستی خود را ارسال کنید.
        🔔قیمت هر گیگ حجم $eextraprice تومان می باشد.
        🔔 حداقل حجم $mainvolume گیگابایت و حداکثر $maxvolume گیگابایت می باشد.";
            sendmessage($from_id, $textcustom, $backuser, 'html');
            step('gettimecustomvol', $from_id);
            return;
        }
    }
    if ($user['step'] == "statusnamecustom") {
        savedata('clear', "note", $text);
        step("home", $from_id);
    }
    sendmessage($from_id, "📌 موقعیت سرویس خود را انتخاب کنید", $list_marzban_panel_user, 'HTML');
} elseif ($datain == "customvolumebuy") {
    $userdate = json_decode($user['Processing_value'], true);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    $eextraprice = $setting['pricevolume'];
    $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
    $mainvolume = $mainvolume[$userbot['agent']];
    $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
    $maxvolume = $maxvolume[$userbot['agent']];
    $textcustom = "📌 حجم درخواستی خود را ارسال کنید.
🔔قیمت هر گیگ حجم $eextraprice تومان می باشد.
🔔 حداقل حجم $mainvolume گیگابایت و حداکثر $maxvolume گیگابایت می باشد.";
    sendmessage($from_id, $textcustom, $backuser, 'html');
    step('gettimecustomvol', $from_id);
} elseif (preg_match('/^location_(.*)/', $datain, $dataget)) {
    $userdate = json_decode($user['Processing_value'], true);
    $locationproduct = select("marzban_panel", "*", "code_panel", $dataget[1], "select");
    if (isset($userdate['note'])) {
        savedata("save", "name_panel", $locationproduct['name_panel']);
    } else {
        savedata("clear", "name_panel", $locationproduct['name_panel']);
    }
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE (status = 'active' OR status = 'end_of_time' OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND  Service_location = :mp13");
    $stmt->execute([':mp13' => $locationproduct['name_panel']]);
    $countinovoice = $stmt->rowCount();
    if ($locationproduct['limit_panel'] != "unlimited") {
        if ($countinovoice >= $locationproduct['limit_panel']) {
            sendmessage($from_id, $textbotlang['users']['sell']['panelCapacityFull'], null, 'HTML');
            return;
        }
    }
    $query = "SELECT * FROM product WHERE (Location = '{$locationproduct['name_panel']}' OR Location = '/all')AND agent= '{$userbot['agent']}'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $productnotexits = $stmt->rowCount();
    if ($productnotexits != 0 and $setting['show_product'] == false) {
        if ($settingmain['statuscategorygenral'] == "offcategorys") {
            $statuscustomvolume = json_decode($locationproduct['customvolume'], true)[$userbot['agent']];
            if ($statuscustomvolume == "1" && $locationproduct['type'] != "Manualsale") {
                $statuscustom = true;
            } else {
                $statuscustom = false;
            }
            if (in_array(usernameMethodKey($locationproduct['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
                $keyboarddata = "selectproductbuyy_";
            } else {
                $keyboarddata = "selectproductbuy_";
            }
            $prodcut = KeyboardProduct($locationproduct['name_panel'], $query, 0, $keyboarddata, $statuscustom, "backuser", null, $customvolume = "customvolumebuy");
            Editmessagetext($from_id, $message_id, "🛍️ لطفاً سرویسی که می‌خواهید خریداری کنید را انتخاب کنید!", $prodcut, 'HTML');
        } else {
            $nullproduct = select("product", "*", "agent", $userbot['agent'], "count");
            if ($nullproduct == 0) {
                sendmessage($from_id, $textbotlang['users']['sell']['nullProduct'], null, 'HTML');
                return;
            }
            Editmessagetext($from_id, $message_id, "📌 دسته بندی خود را انتخاب نمایید!", KeyboardCategory($locationproduct['name_panel'], $userbot['agent'], "backuser"));
        }
    } else {
        deletemessage($from_id, $message_id);
        $marzban_list_get = $locationproduct;
        $eextraprice = $setting['pricevolume'];
        $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
        $mainvolume = $mainvolume[$userbot['agent']];
        $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
        $maxvolume = $maxvolume[$userbot['agent']];
        $textcustom = "📌 حجم درخواستی خود را ارسال کنید.
    🔔قیمت هر گیگ حجم $eextraprice تومان می باشد.
    🔔 حداقل حجم $mainvolume گیگابایت و حداکثر $maxvolume گیگابایت می باشد.";
        sendmessage($from_id, $textcustom, $backuser, 'html');
        step('gettimecustomvol', $from_id);
        return;
    }
} elseif (preg_match('/^categorynames_(.*)/', $datain, $dataget)) {
    $categorynames = $dataget[1];
    $categorynames = select("category", "remark", "id", $categorynames, "select")['remark'];
    $userdate = json_decode($user['Processing_value'], true);
    $locationproduct = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "seelct");
    $query = "SELECT * FROM product WHERE (Location = '{$locationproduct['name_panel']}' OR Location = '/all') AND category = '$categorynames' AND agent= '{$userbot['agent']}' ";
    $statuscustomvolume = json_decode($locationproduct['customvolume'], true)[$userbot['agent']];
    if ($statuscustomvolume == "1" && $locationproduct['type'] != "Manualsale") {
        $statuscustom = true;
    } else {
        $statuscustom = false;
    }
    if (in_array(usernameMethodKey($locationproduct['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
        $keyboarddata = "selectproductbuyy_";
    } else {
        $keyboarddata = "selectproductbuy_";
    }
    $prodcut = KeyboardProduct($locationproduct['name_panel'], $query, 0, $keyboarddata, $statuscustom, "backuser", null, $customvolume = "customvolumebuy");
    Editmessagetext($from_id, $message_id, "🛍️ لطفاً سرویسی که می‌خواهید خریداری کنید را انتخاب کنید!", $prodcut, 'HTML');
} elseif ($user['step'] == "gettimecustomvol") {
    $userdate = json_decode($user['Processing_value'], true);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
    $mainvolume = $mainvolume[$userbot['agent']];
    $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
    $maxvolume = $maxvolume[$userbot['agent']];
    $maintime = json_decode($marzban_list_get['maintime'], true);
    $maintime = $maintime[$userbot['agent']];
    $maxtime = json_decode($marzban_list_get['maxtime'], true);
    $maxtime = $maxtime[$userbot['agent']];
    if ($text > intval($maxvolume) || $text < intval($mainvolume)) {
        $texttime = "❌ حجم نامعتبر است.\n🔔 حداقل حجم $mainvolume گیگابایت و حداکثر $maxvolume گیگابایت می باشد";
        sendmessage($from_id, $texttime, $backuser, 'HTML');
        return;
    }
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidVolume'], $backuser, 'HTML');
        return;
    }
    $customtimevalueprice = $setting['pricetime'];
    savedata("save", "volume", $text);
    $textcustom = "⌛️ زمان سرویس خود را انتخاب نمایید 
📌 تعرفه هر روز  : $customtimevalueprice  تومان
⚠️ حداقل زمان $maintime روز  و حداکثر $maxtime روز  می توانید تهیه کنید";
    sendmessage($from_id, $textcustom, $backuser, 'html');
    if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
        step('getvolumecustomusername', $from_id);
    } else {
        step('getvolumecustomuser', $from_id);
    }
} elseif ($user['step'] == "getvolumecustomusername" || preg_match('/selectproductbuyy_(.*)/', $datain, $dataget)) {
    $userdate = json_decode($user['Processing_value'], true);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    if ($user['step'] == "getvolumecustomusername") {
        if (!ctype_digit($text)) {
            sendmessage($from_id, $textbotlang['common']['invalidTime'], $backuser, 'HTML');
            return;
        }
        $maintime = json_decode($marzban_list_get['maintime'], true);
        $maintime = $maintime[$userbot['agent']];
        $maxtime = json_decode($marzban_list_get['maxtime'], true);
        $maxtime = $maxtime[$userbot['agent']];
        if (intval($text) > intval($maxtime) || intval($text) < intval($maintime)) {
            $texttime = "❌ زمان ارسال شده نامعتبر است . زمان باید بین $maintime روز تا $maxtime روز باشد";
            sendmessage($from_id, $texttime, $backuser, 'HTML');
            return;
        }
        step('endstepuserscustom', $from_id);
        savedata("save", "time", $text);
    } else {
        $prodcut = $dataget[1];
        savedata("save", "code_product", $prodcut);
        step('endstepusers', $from_id);
    }
    sendmessage($from_id, $textbotlang['users']['selectusername'], $backuser, 'html');
} elseif ($user['step'] == "endstepusers" || $user['step'] == "endstepuserscustom" || $user['step'] == "getvolumecustomuser" || preg_match('/selectproductbuy_(.*)/', $datain, $dataget)) {
    $userdate = json_decode($user['Processing_value'], true);
    if ($user['step'] == "getvolumecustomuser") {
        if (!ctype_digit($text)) {
            sendmessage($from_id, "زمان نامعتبر است", $backuser, 'HTML');
            return;
        }
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
        $maintime = json_decode($marzban_list_get['maintime'], true);
        $maintime = $maintime[$userbot['agent']];
        $maxtime = json_decode($marzban_list_get['maxtime'], true);
        $maxtime = $maxtime[$userbot['agent']];
        if (intval($text) > intval($maxtime) || intval($text) < intval($maintime)) {
            $texttime = "❌ زمان ارسال شده نامعتبر است . زمان باید بین $maintime روز تا $maxtime روز باشد";
            sendmessage($from_id, $texttime, $backuser, 'HTML');
            return;
        }
        savedata("save", "time", $text);
        $userdate['time'] = $text;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    if ($marzban_list_get['status'] == "disable") {
        sendmessage($from_id, "❌ این پنل در دسترس نیست لطفا از پنل دیگری خرید را انجام دهید.", $backuser, 'html');
        step("home", $from_id);
        return;
    }
    if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
        if (!preg_match('~(?!_)^[a-z][a-z\d_]{2,32}(?<!_)$~i', $text)) {
            sendmessage($from_id, $textbotlang['users']['invalidusername'], $backuser, 'HTML');
            return;
        }
        if ($user['step'] == "endstepusers") {
            $code_product = $userdate['code_product'];
        }
    } else {
        $code_product = $dataget[1];
    }
    if (!in_array($user['step'], ["endstepuserscustom", "getvolumecustomuser"])) {
        $product = select("product", "*", "code_product", $code_product);
        if ($product == false) {
            sendmessage($from_id, "❌ خطایی در هنگام خرید رخ داده لطفا مراحل را از اول طی کنید", $keyboard, 'html');
            step("home", $from_id);
            return;
        }
        savedata("save", "code_product", $code_product);
        $productlist = json_decode(file_get_contents('product.json'), true);
        if (isset($productlist[$product['code_product']])) {
            $product['price_product'] = $productlist[$product['code_product']];
        }
        $datapish = array(
            "Volume_constraint" => $product['Volume_constraint'],
            "name_product" => $product['name_product'],
            "code_product" => $product['code_product'],
            "Service_time" => $product['Service_time'],
            "price_product" => $product['price_product']
        );
    } else {
        $custompricevalue = $setting['pricevolume'];
        $customtimevalueprice = $setting['pricetime'];
        $datapish = array(
            "Volume_constraint" => $userdate['volume'],
            "name_product" => $textbotlang['users']['customSellVolume']['title'],
            "code_product" => "customvolume",
            "Service_time" => $userdate['time'],
            "price_product" => ($userdate['volume'] * $custompricevalue) + ($userdate['time'] * $customtimevalueprice)
        );
    }
    $randomString = bin2hex(random_bytes(2));
    $username_ac = generateUsername($from_id, $marzban_list_get['MethodUsername'], $username, $randomString, $text, $marzban_list_get['namecustom'], $user['namecustom']);
    $username_ac = strtolower($username_ac);
    savedata("save", "username", $username_ac);
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $username_ac);
    $random_number = rand(1000000, 9999999);
    if (isset($DataUserOut['username']) || in_array($username_ac, $usernameinvoice)) {
        $username_ac = $random_number . "_" . $username_ac;
    }
    if (intval($datapish['Volume_constraint']) == 0)
        $datapish['Volume_constraint'] = $textbotlang['users']['status']['unlimited'];
    if (intval($datapish['Service_time']) == 0)
        $datapish['Service_time'] = $textbotlang['users']['status']['unlimited'];
    $info_product_price_product = number_format($datapish['price_product']);
    $userBalance = number_format($user['Balance']);
    $replacements = [
        '{username}' => $username_ac,
        '{Service_time}' => $datapish['Service_time'],
        '{price}' => $info_product_price_product,
        '{Volume}' => $datapish['Volume_constraint'],
        '{userBalance}' => $userBalance
    ];
    $textpishfactor = "📇 پیش فاکتور شما:
👤 نام کاربری:  {username}
📆 مدت اعتبار: {Service_time} روز
💶 قیمت:  {price} تومان
👥 حجم اکانت: {Volume} گیگ
💵 موجودی کیف پول شما : {userBalance}
          
💰 سفارش شما آماده پرداخت است";
    $textin = strtr($textpishfactor, $replacements);
    if (intval($datapish['Volume_constraint']) == 0) {
        $textin = str_replace('گیگ', "", $textin);
    }
    if ($user['step'] != "getvolumecustomuser" && !in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
        Editmessagetext($from_id, $message_id, $textin, $payment);
    } else {
        sendmessage($from_id, $textin, $payment, 'HTML');
    }
    step('payment', $from_id);
} elseif ($user['step'] == "payment" && $datain == "confirmandgetservice") {
    $userdate = json_decode($user['Processing_value'], true);
    Editmessagetext($from_id, $message_id, $text_inline, json_encode(['inline_keyboard' => []]));
    if (!isset($userdate['name_panel'])) {
        sendmessage($from_id, "❌ خطایی رخ داده است مراحل خرید را از اول انجام دهید", $keyboard, 'html');
        step("home", $from_id);
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    if ($marzban_list_get == false) {
        sendmessage($from_id, "❌ خطایی رخ داده است مراحل خرید را از اول انجام دهید", $keyboard, 'html');
        step("home", $from_id);
        return;
    }
    if ($marzban_list_get['status'] == "disable") {
        sendmessage($from_id, "❌ این پنل در دسترس نیست لطفا از پنل دیگری خرید را انجام دهید.", $backuser, 'html');
        step("home", $from_id);
        return;
    }
    if (isset($userdate['code_product'])) {
        $product = $userdate['code_product'];
        $product = select("product", "*", "code_product", $product);
        if ($product == false) {
            sendmessage($from_id, "❌ خطایی رخ داده است مراحل خرید را از اول انجام دهید", $keyboard, 'html');
            step("home", $from_id);
            return;
        }
        $priceBot = $product['price_product'];
        $productlist = json_decode(file_get_contents('product.json'), true);
        if (isset($productlist[$product['code_product']])) {
            $product['price_product'] = $productlist[$product['code_product']];
        }
        $pricevalue = $product['price_product'];
        $datafactor = array(
            "Volume_constraint" => $product['Volume_constraint'],
            "name_product" => $product['name_product'],
            "Service_time" => $product['Service_time'],
            "code_product" => $product['code_product'],
            "price_product" => $product['price_product'],
            "price_productMain" => $priceBot,
            "data_limit_reset" => $product['data_limit_reset']
        );
    } else {
        $custompricevalue = $setting['pricevolume'];
        $customtimevalueprice = $setting['pricetime'];
        $custompricevalueBot = $setting['minpricevolume'];
        $customtimevaluepriceBot = $setting['minpricetime'];
        $datafactor = array(
            "Volume_constraint" => $userdate['volume'],
            "name_product" => $textbotlang['users']['customSellVolume']['title'],
            "Service_time" => $userdate['time'],
            "code_product" => "customvolume",
            "price_product" => ($userdate['volume'] * $custompricevalue) + ($userdate['time'] * $customtimevalueprice),
            "price_productMain" => intval(($userdate['volume'] * $custompricevalueBot) + ($userdate['time'] * $customtimevaluepriceBot)),
            "data_limit_reset" => "no_reset"
        );
    }
    if (!ctype_digit($datafactor['Volume_constraint']) || !ctype_digit($datafactor['Service_time'])) {
        sendmessage($from_id, "❌ خطایی رخ داده است مراحل خرید را از اول انجام دهید", $keyboard, 'html');
        step("home", $from_id);
        return;
    }
    $botbalance = select("botsaz", "*", "bot_token", $ApiToken, "select");
    $userbotbalance = select("user", "*", "id", $botbalance['id_user'], "select");
    if (($datafactor['price_productMain'] > $userbotbalance['Balance']) && $userbotbalance['agent'] != "n2") {
        sendmessage($from_id, "❌ خطایی در خرید رخ داده است برای رفع مشکل با پشتیبانی در ارتباط باشید", $keyboard, 'HTML');
        step("home", $from_id);
        foreach ($admin_ids as $admin) {
            sendmessage($admin, "❌ ادمین عزیز موجودی شما به پایان رسید برای فعالسازی به ربات اصلی مراجعه و ربات خود را شارژ نمایید.", null, 'HTML');
        }
        return;
    }
    if (intval($userbotbalance['maxbuyagent']) != 0 and $userbotbalance['agent'] == "n2") {
        $pricecapcheck = $datafactor['price_productMain'];
        if (intval($userbotbalance['pricediscount']) != 0) {
            $pricecapcheck = $pricecapcheck - (($pricecapcheck * $userbotbalance['pricediscount']) / 100);
        }
        if (($userbotbalance['Balance'] - $pricecapcheck) < -intval($userbotbalance['maxbuyagent'])) {
            sendmessage($from_id, "❌ خطایی در خرید رخ داده است برای رفع مشکل با پشتیبانی در ارتباط باشید", $keyboard, 'HTML');
            step("home", $from_id);
            foreach ($admin_ids as $admin) {
                sendmessage($admin, "❌ ادمین عزیز شما به حداکثر سقف خرید خود رسیده اید برای ادامه فروش ابتدا حساب خود را در ربات اصلی شارژ نمایید.", null, 'HTML');
            }
            return;
        }
    }
    $username_ac = strtolower($userdate['username']);
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $username_ac);
    $random_number = rand(1000000, 9999999);
    if (isset($DataUserOut['username']) || in_array($username_ac, $usernameinvoice)) {
        $username_ac = $random_number . "_" . $username_ac;
    }
    $date = time();
    $randomString = bin2hex(random_bytes(4));
    $random_number = rand(1000000, 9999999);
    if (in_array($randomString, $id_invoice)) {
        $randomString = $random_number . $randomString;
    }
    if ($marzban_list_get['type'] == "Manualsale") {
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
        $stmt = $pdo->prepare("SELECT * FROM manualsell WHERE codepanel = :codepanel AND codeproduct = :codeproduct AND status = 'active'");
        $stmt->bindParam(':codepanel', $marzban_list_get['code_panel']);
        $stmt->bindParam(':codeproduct', $datafactor['code_product']);
        $stmt->execute();
        $configexits = $stmt->rowCount();
        if (intval($configexits) == 0) {
            sendmessage($from_id, "❌ موجودی این سرویس به پایان رسیده لطفا سرویسی دیگر را خریداری کنید.", null, 'HTML');
            return;
        }
    }
    $notifctions = json_encode(array(
        'volume' => false,
        'time' => false,
    ));
    $stmt = $pdo->prepare("INSERT IGNORE INTO invoice (id_user, id_invoice, username,time_sell, Service_location, name_product, price_product, Volume, Service_time,Status,bottype,note,notifctions) VALUES (?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?)");
    $Status = "unpaid";
    $stmt->execute([$from_id, $randomString, $username_ac, $date, $marzban_list_get['name_panel'], $datafactor['name_product'], $datafactor['price_product'], $datafactor['Volume_constraint'], $datafactor['Service_time'], $Status, $ApiToken, $userdate['note'], $notifctions]);
    if ($datafactor['price_product'] > $user['Balance'] && intval($datafactor['price_product']) != 0) {
        $marzbandirectpay = select("shopSetting", "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        $Balance_prim = $datafactor['price_product'] - $user['Balance'];
        if ($Balance_prim <= 1)
            $Balance_prim = 0;
        $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$userbot['agent']]);
        $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$userbot['agent']]);
        $bakinfos = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "account"],
                ]
            ]
        ]);
        Editmessagetext($from_id, $message_id, "❌ موجودی شما برای خرید سرویس کافی نمی باشد.
💸  برای افزایش موجودی مبلغ را  به تومان وارد کنید:
✅  حداقل مبلغ $minbalance حداکثر مبلغ $maxbalance تومان می باشد", $bakinfos, 'HTML');
        step('get_price', $from_id);
        return;
    }
    Editmessagetext($from_id, $message_id, "♻️ در حال ساختن سرویس شما...", null);
    $datetimestep = strtotime("+" . $datafactor['Service_time'] . "days");
    if ($datafactor['Service_time'] == 0) {
        $datetimestep = 0;
    } else {
        $datetimestep = strtotime(date("Y-m-d H:i:s", $datetimestep));
    }
    $datac = array(
        'expire' => $datetimestep,
        'data_limit' => $datafactor['Volume_constraint'] * pow(1024, 3),
        'from_id' => $from_id,
        'username' => $username,
        'type' => 'buy_agent_user_bot'
    );
    $dataoutput = $ManagePanel->createUser($marzban_list_get['name_panel'], $datafactor['code_product'], $username_ac, $datac);
    if ($dataoutput['username'] == null) {
        $dataoutput['msg'] = json_encode($dataoutput['msg']);
        sendmessage($from_id, $textbotlang['users']['sell']['errorConfig'], $keyboard, 'HTML');
        $texterros = "⭕️ خطای ساخت اشتراک  در ربات نماینده
✍️ دلیل خطا : 
{$dataoutput['msg']}
آیدی کابر : $from_id
نام کاربری کاربر : @$username
نام پنل : {$marzban_list_get['name_panel']}";
        if (strlen($settingmain['Channel_Report']) > 0) {
            telegram('sendmessage', [
                'chat_id' => $settingmain['Channel_Report'],
                'message_thread_id' => $errorreport,
                'text' => $texterros,
                'parse_mode' => "HTML"
            ], $APIKEY);
        }
        step('home', $from_id);
        return;
    }
    update("invoice", "Status", "active", "username", $username_ac);
    $configqr = "";
    $output_config_link = "";
    $config = "";
    if ($marzban_list_get['sublink'] == "onsublink") {
        $output_config_link = $dataoutput['subscription_url'];
    }
    if ($marzban_list_get['config'] == "onconfig") {
        if (isset($dataoutput['configs']) and count($dataoutput['configs']) != 0) {
            foreach ($dataoutput['configs'] as $configs) {
                $config .= "\n" . $configs;
                $configqr .= $configs;
            }
        } else {
            $config .= "";
            $configqr .= "";
        }
    }
    $textafterpay = "✅ سرویس با موفقیت ایجاد شد

👤 نام کاربری سرویس : {username}
🌿 نام سرویس:  {name_service}
‏🇺🇳 لوکیشن: {location}
⏳ مدت زمان: {day}  روز
🗜 حجم سرویس:  {volume} گیگابایت

لینک اتصال:
{config}
{links}
";
    $textmanual = "✅ سرویس با موفقیت ایجاد شد

👤 نام کاربری سرویس : {username}
🌿 نام سرویس:  {name_service}
🇺🇳 لوکیشن: {location}

 اطلاعات سرویس :
{config}
";
    if ($marzban_list_get['type'] == "ibsng") {
        $datatextbot['textafterpay'] = $datatextbot['textafterpayibsng'];
    }
    if ($marzban_list_get['type'] == "Manualsale") {
        $textafterpay = $textmanual;
    }
    if ($marzban_list_get['type'] == "WGDashboard") {
        $datatextbot['textafterpay'] = "✅ سرویس با موفقیت ایجاد شد

👤 نام کاربری سرویس : {username}
🌿 نام سرویس:  {name_service}
‏🇺🇳 لوکیشن: {location}
⏳ مدت زمان: {day}  روز
🗜 حجم سرویس:  {volume} گیگابایت

🧑‍🦯 شما میتوانید شیوه اتصال را  با فشردن دکمه زیر و انتخاب سیستم عامل خود را دریافت کنید";
    }
    if (intval($datafactor['Service_time']) == 0)
        $datafactor['Service_time'] = $textbotlang['users']['status']['unlimited'];
    if (intval($datafactor['Volume_constraint']) == 0)
        $datafactor['Volume_constraint'] = $textbotlang['users']['status']['unlimited'];
    $textcreatuser = str_replace('{username}', "<code>{$dataoutput['username']}</code>", $textafterpay);
    $textcreatuser = str_replace('{name_service}', $datafactor['name_product'], $textcreatuser);
    $textcreatuser = str_replace('{location}', $marzban_list_get['name_panel'], $textcreatuser);
    $textcreatuser = str_replace('{day}', $datafactor['Service_time'], $textcreatuser);
    $textcreatuser = str_replace('{volume}', $datafactor['Volume_constraint'], $textcreatuser);
    $textcreatuser = str_replace('{config}', "<code>{$output_config_link}</code>", $textcreatuser);
    $textcreatuser = str_replace('{links}', "<code>{$config}</code>", $textcreatuser);
    if (intval($datafactor['Volume_constraint']) == 0) {
        $textcreatuser = str_replace('گیگابایت', "", $textcreatuser);
    }
    if ($marzban_list_get['type'] == "ibsng") {
        $textcreatuser = str_replace('{password}', $dataoutput['subscription_url'], $textcreatuser);
        update("invoice", "user_info", $dataoutput['subscription_url'], "id_invoice", $randomString);
    }
    if ($marzban_list_get['type'] == "Manualsale" | $marzban_list_get['type'] == "ibsng") {
        sendmessage($from_id, $textcreatuser, null, 'HTML');
    } else {
        if (count($dataoutput['configs']) != 1 and $marzban_list_get['config'] == "onconfig") {
            sendmessage($from_id, $textcreatuser, null, 'HTML');
        } else {
            if ($marzban_list_get['sublink'] == "offsublink") {
                $output_config_link = $configqr;
            }
            if ($marzban_list_get['type'] == "WGDashboard") {
                $urlimage = "{$marzban_list_get['inboundid']}_{$dataoutput['username']}.conf";
                file_put_contents($urlimage, $output_config_link);
                telegram('senddocument', [
                    'chat_id' => $from_id,
                    'document' => new CURLFile($urlimage),
                    'caption' => $textcreatuser,
                    'parse_mode' => "HTML",
                ]);
                unlink($urlimage);
            } else {
                $urlimage = "$from_id$randomString.png";
                $qrCode = createqrcode($output_config_link);
                file_put_contents($urlimage, $qrCode->getString());
                addBackgroundImage($urlimage, $qrCode, $Pathfiles . 'images.jpg');
                telegram('sendphoto', [
                    'chat_id' => $from_id,
                    'photo' => new CURLFile($urlimage),
                    'caption' => $textcreatuser,
                    'parse_mode' => "HTML",
                ]);
                unlink($urlimage);
            }
        }
    }
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboard, 'HTML');
    if (intval($userbotbalance['pricediscount']) != 0) {
        $resultper = ($datafactor['price_productMain'] * $userbotbalance['pricediscount']) / 100;
        $datafactor['price_productMain'] = $datafactor['price_productMain'] - $resultper;
    }
    if (intval($datafactor['price_product']) != 0) {
        $Balance_prim = $user['Balance'] - $datafactor['price_product'];
        $userbalance = json_decode(file_get_contents("data/$from_id/$from_id.json"), true);
        $userbalance['Balance'] = $Balance_prim;
        file_put_contents("data/$from_id/$from_id.json", json_encode($userbalance));
    }
    $Balancebot = $userbotbalance['Balance'] - $datafactor['price_productMain'];
    $stmt = $pdo->prepare("UPDATE user SET Balance = Balance - :price WHERE id = :id");
    $stmt->execute([':price' => $datafactor['price_productMain'], ':id' => $userbotbalance['id']]);
    if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customTextSequential', 'usernameSequential', 'numericIdSequential', 'agentCustomTextSequential'], true)) {
        $value = intval($user['number_username']) + 1;
        update("user", "number_username", $value, "id", $from_id);
        if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customTextSequential', 'agentCustomTextSequential'], true)) {
            $value = intval($settingmain['numbercount']) + 1;
            update("setting", "numbercount", $value);
        }
    }
    $balanceformatsell = number_format(select("user", "Balance", "id", $from_id, "select")['Balance'], 0);
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE name_product != 'سرویس تست'  AND id_user = :id_user");
    $stmt->bindParam(':id_user', $from_id);
    $stmt->execute();
    $countinvoice = $stmt->rowCount();
    $textonebuy = "";
    if ($countinvoice == 1) {
        $textonebuy = "📌 خرید اول کاربر";
    }
    $balanceformatsellbefore = number_format($user['Balance'], 0);
    $balanceagent_before = number_format($userbotbalance['Balance'], 0);
    $balanceagent_after = number_format($Balancebot, 0);
    $balance_after = number_format($Balance_prim, 0);
    $timejalali = jdate('Y/m/d H:i:s');
    $text_report = "📣 جزئیات ساخت اکانت در ربات نماینده شما ثبت شد .

$textonebuy
▫️آیدی عددی کاربر : <code>$from_id</code>
▫️آیدی عددی نماینده : <code>{$userbot['id']}</code>
▫️نام کاربری کاربر :@$username
▫️نام کاربری ربات نماینده :@{$dataBase['username']}
▫️نام کاربری کانفیگ :$username_ac
▫️نام کاربر : $first_name
▫️موقعیت سرویس سرویس : {$userdate['name_panel']}
▫️زمان خریداری شده :{$datafactor['Service_time']} روز
▫️حجم خریداری شده : {$datafactor['Volume_constraint']} GB
▫️موجودی قبل خرید : $balanceformatsellbefore تومان
▫️موجودی بعد خرید : $balance_after تومان
▫️موجودی نماینده قبل از خرید :$balanceagent_before تومان
▫️موجودی نماینده قبل از خرید :$balanceagent_after
▫️کد پیگیری: $randomString
▫️قیمت محصول : {$datafactor['price_product']} تومان
▫️زمان خرید : $timejalali";
    if (strlen($settingmain['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $settingmain['Channel_Report'],
            'message_thread_id' => $buyreport,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ], $APIKEY);
    }
    update("user", "Processing_value_four", "none", "id", $from_id);
    step('home', $from_id);
} elseif ($datain == "AddBalance") {
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "account"],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $text_bot_var['text_account']['add_balance'], $bakinfos);
    step("get_price", $from_id);
} elseif ($user['step'] == "get_price") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backuser, 'HTML');
        return;
    }
    $dateacc = date('Y/m/d H:i:s');
    $randomString = bin2hex(random_bytes(5));
    $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice,bottype) VALUES (?,?,?,?,?,?,?,?)");
    $payment_Status = "Unpaid";
    $Payment_Method = "cart to cart";
    $invoice = "0 | 0";
    $stmt->execute([$from_id, $randomString, $dateacc, $text, $payment_Status, $Payment_Method, $invoice, $ApiToken]);
    sendmessage($from_id, $setting['cart_info'], $backuser, 'HTML');
    step("getresidcart", $from_id);
    savedata("clear", "id_order", $randomString);
} elseif ($user['step'] == "getresidcart") {
    $userdate = json_decode($user['Processing_value'], true);
    $PaymentReport = select("Payment_report", '*', "id_order", $userdate['id_order'], "select");
    $Confirm_pay = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['Balance']['confirmPaying'], 'callback_data' => "Confirm_pay_{$userdate['id_order']}"],
                ['text' => $textbotlang['users']['Balance']['rejectPay'], 'callback_data' => "reject_pay_{$userdate['id_order']}"],
            ]
        ]
    ]);
    $format_price_cart = number_format($PaymentReport['price']);
    $textsendrasid = "
⭕️ یک پرداخت جدید انجام شده است .
افزایش موجودی            
👤 شناسه کاربر:  <a href = \"tg://user?id=$from_id\">$from_id</a>
🛒 کد پیگیری پرداخت: {$PaymentReport['id_order']}
⚜️ نام کاربری: @$username
💸 مبلغ پرداختی: $format_price_cart تومان
                
توضیحات: $caption $text
✍️ در صورت درست بودن رسید پرداخت را تایید نمایید.";
    foreach ($admin_ids as $id_admin) {
        if ($photo) {
            telegram('sendphoto', [
                'chat_id' => $id_admin,
                'photo' => $photoid,
                'caption' => "🖼 تصویر رسید ارسالی",
                'parse_mode' => "HTML",
            ]);
        }
        sendmessage($id_admin, $textsendrasid, $Confirm_pay, 'HTML');
        step('home', $id_admin);
    }
    step('home', $from_id);
    sendmessage($from_id, "💎 رسید شما ارسال و پس از بررسی حساب کاربری شما شارژ خواهد شد.", $keyboard, 'HTML');
} elseif (preg_match('/product_(\w+)/', $datain, $dataget)) {
    $username = $dataget[1];
    $sql = "SELECT * FROM invoice WHERE id_invoice = :username AND id_user = :id_user";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':id_user', $from_id);
    $stmt->execute();
    $nameloc = $stmt->fetch(PDO::FETCH_ASSOC);
    $username = $nameloc['id_invoice'];
    if (!in_array($nameloc['Status'], ['active', 'end_of_time', 'end_of_volume', 'sendedwarn', 'send_on_hold'])) {
        sendmessage($from_id, "❌ امکان مشاهده اطلاعات اکانت درحال حاضر وجود ندارد", $keyboard, 'html');
        step('home', $from_id);
        return;
    }
    $marzban = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban['name_panel'] != null) {
        update("user", "Processing_value_four", $marzban['name_panel'], "id", $from_id);
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    update("invoice", "user_info", json_encode($DataUserOut), "id_invoice", $nameloc['id_invoice']);
    if (isset($DataUserOut['msg']) && $DataUserOut['msg'] == "User not found") {
        update("invoice", "Status", "disabledn", "id_invoice", $nameloc['id_invoice']);
        sendmessage($from_id, $textbotlang['users']['status']['userNotFound'], $keyboard, 'html');
        step('home', $from_id);
        return;
    }
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['status']['panelNotConnected'], $keyboard, 'html');
        step('home', $from_id);
        return;
    }
    if ($DataUserOut['online_at'] == "online") {
        $lastonline = 'آنلاین';
    } elseif ($DataUserOut['online_at'] == "offline") {
        $lastonline = 'آفلاین';
    } else {
        if (isset($DataUserOut['online_at']) && $DataUserOut['online_at'] !== null) {
            $dateTime = new DateTime($DataUserOut['online_at'], new DateTimeZone('UTC'));
            $dateTime->setTimezone(new DateTimeZone('Asia/Tehran'));
            $lastonline = jdate('Y/m/d H:i:s', $dateTime->getTimestamp());
        } else {
            $lastonline = "متصل نشده";
        }
    }
    #-------------status----------------#
    $status = $DataUserOut['status'];
    $status_var = [
        'active' => $textbotlang['users']['status']['active'],
        'limited' => $textbotlang['users']['status']['limited'],
        'disabled' => $textbotlang['users']['status']['disabled'],
        'expired' => $textbotlang['users']['status']['expired'],
        'on_hold' => $textbotlang['users']['status']['on_hold'],
        'Unknown' => $textbotlang['users']['status']['unknown'],
        'deactivev' => $textbotlang['users']['status']['disabled'],
    ][$status];
    #--------------[ expire ]---------------#
    $expirationDate = $DataUserOut['expire'] ? jdate('Y/m/d', $DataUserOut['expire']) : $textbotlang['users']['status']['unlimited'];
    #-------------[ data_limit ]----------------#
    $LastTraffic = $DataUserOut['data_limit'] ? formatBytes($DataUserOut['data_limit']) : $textbotlang['users']['status']['unlimited'];
    #---------------[ RemainingVolume ]--------------#
    $output = $DataUserOut['data_limit'] - $DataUserOut['used_traffic'];
    $RemainingVolume = $DataUserOut['data_limit'] ? formatBytes($output) : "نامحدود";
    #---------------[ used_traffic ]--------------#
    $usedTrafficGb = $DataUserOut['used_traffic'] ? formatBytes($DataUserOut['used_traffic']) : $textbotlang['users']['status']['notConsumed'];
    #--------------[ day ]---------------#
    $timeDiff = $DataUserOut['expire'] - time();
    if ($timeDiff < 0) {
        $day = 0;
    } else {
        $day = "";
        $timemonth = floor($timeDiff / 2592000);
        if ($timemonth > 0) {
            $day .= $timemonth . $textbotlang['users']['status']['month'];
            $timeDiffday = $timeDiff - (2592000 * $timemonth);
        } else {
            $timeDiffday = $timeDiff;
        }
        $timereminday = floor($timeDiffday / 86400);
        if ($timereminday > 0) {
            $day .= $timereminday . $textbotlang['users']['status']['day'];
        }
        $timehoures = intval(($timeDiffday - ($timereminday * 86400)) / 3600);
        if ($timehoures > 0) {
            $day .= $timehoures . $textbotlang['users']['status']['hour'];
        }
        $timehoursall = $timeDiffday - ($timereminday * 86400);
        $timehoursall = $timehoursall - ($timehoures * 3600);
        $timeminuts = intval($timehoursall / 60);
        if ($timeminuts > 0) {
            $day .= $timeminuts . $textbotlang['users']['status']['min'];
        }
        $day .= " دیگر";
    }
    #--------------[ subsupdate ]---------------#
    if ($DataUserOut['sub_updated_at'] !== null) {
        $sub_updated = $DataUserOut['sub_updated_at'];
        $dateTime = new DateTime($sub_updated, new DateTimeZone('UTC'));
        $dateTime->setTimezone(new DateTimeZone('Asia/Tehran'));
        $lastupdate = jdate('Y/m/d H:i:s', $dateTime->getTimestamp());
    }
    #--------------[ Percent ]---------------#
    if ($DataUserOut['data_limit'] != null && $DataUserOut['used_traffic'] != null) {
        $Percent = ($DataUserOut['data_limit'] - $DataUserOut['used_traffic']) * 100 / $DataUserOut['data_limit'];
    } else {
        $Percent = "100";
    }
    if ($Percent < 0)
        $Percent = -($Percent);
    $Percent = round($Percent, 2);
    $keyboardsetting = ['inline_keyboard' => []];
    $keyboarddateservies = array(
        'extend' => array(
            'text' => $textbotlang['users']['extend']['title'],
            'callback_data' => "extend_"
        ),
        'changelink' => array(
            'text' => $textbotlang['users']['changeLink']['btnTitle'],
            'callback_data' => "changelink_"
        ),
    );
    if ($marzban['status_extend'] == "off_extend") {
        unset($keyboarddateservies['extend']);
    }
    if (count($keyboarddateservies) != 0) {
        $tempArrayservices = [];
        foreach ($keyboarddateservies as $keyboardtextservice) {
            $tempArrayservices[] = ['text' => $keyboardtextservice['text'], 'callback_data' => $keyboardtextservice['callback_data'] . $username];
            if (count($tempArrayservices) == 2) {
                $keyboardsetting['inline_keyboard'][] = $tempArrayservices;
                $tempArrayservices = [];
            }
        }
        if (count($tempArrayservices) > 0) {
            $keyboardsetting['inline_keyboard'][] = $tempArrayservices;
        }
    }
    $keyboardsetting['inline_keyboard'][] = [['text' => $textbotlang['users']['status']['backlist'], 'callback_data' => 'backorder']];
    if ($marzban['type'] == "Manualsale") {
        $userinfo = select("manualsell", "*", "username", $nameloc['username'], "select");
        $textinfo = "وضعیت سرویس : <b>$status_var</b>
    نام کاربری سرویس : {$DataUserOut['username']}
    📎 کد پیگیری سرویس : {$nameloc['id_invoice']}
    
    📌 اطلاعات سرویس : 
    {$userinfo['contentrecord']}";
        Editmessagetext($from_id, $message_id, $textinfo, $keyboardsetting);
        return;
    }
    $output = "";
    $config = "";
    if ($marzban['sublink'] == "onsublink") {
        $output = $DataUserOut['subscription_url'];
    }
    if ($marzban['config'] == "onconfig") {
        $config = $DataUserOut['links'][0];
    }
    #-----------------------------#
    $keyboardsetting = json_encode($keyboardsetting);
    if (!in_array($status, ["active", "on_hold", "disabled", "Unknown"])) {
        $textinfo = "وضعیت سرویس : <b>$status_var</b>
نام کاربری سرویس : {$DataUserOut['username']}
موقعیت سرویس :{$nameloc['Service_location']}
مدت زمان سرویس :{$nameloc['Service_time']} روز

📶 اخرین زمان اتصال شما : $lastonline

🔋 حجم سرویس : $LastTraffic
📥 حجم مصرفی : $usedTrafficGb
💢 حجم باقی مانده : $RemainingVolume ($Percent%)

📅 فعال تا تاریخ : $expirationDate ($day) 


لینک اتصال : 
    
<code>$config</code>

<code>$output</code>
";
    } else {
        if ($DataUserOut['sub_updated_at'] !== null) {
            $textinfo = "وضعیت سرویس : $status_var
👤 نام سرویس : {$DataUserOut['username']}
🌍 موقعیت سرویس :{$nameloc['Service_location']}
🖇 کد سرویس:{$nameloc['id_invoice']}

        
🔋 حجم سرویس : $LastTraffic
📥 حجم مصرفی : $usedTrafficGb
💢 حجم باقی مانده : $RemainingVolume ($Percent%)

📅 فعال تا تاریخ : $expirationDate ($day)


📶 اخرین زمان اتصال  : $lastonline
🔄 اخرین زمان آپدیت لینک اشتراک  : $lastupdate
#️⃣ کلاینت متصل شده :<code>{$DataUserOut['sub_last_user_agent']}</code>

لینک اتصال : 
    
$config
$output
";
        } else {
            $textinfo = "وضعیت سرویس : $status_var
👤 نام سرویس : {$DataUserOut['username']}
🌍 موقعیت سرویس :{$nameloc['Service_location']}
🖇 کد سرویس:{$nameloc['id_invoice']}

🔋 حجم سرویس : $LastTraffic
📥 حجم مصرفی : $usedTrafficGb
💢 حجم باقی مانده : $RemainingVolume ($Percent%)

📅 فعال تا تاریخ : $expirationDate ($day)

📶 اخرین زمان اتصال شما : $lastonline
        

لینک اتصال : 
    
<code>$config</code>

<code>$output</code>
";
        }
    }
    Editmessagetext($from_id, $message_id, $textinfo, $keyboardsetting);
} elseif (preg_match('/extend_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    savedata("clear", "id_invoice", $id_invoice);
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if ($nameloc == false) {
        sendmessage($from_id, "❌ تمدید با خطا مواجه گردید مراحل تمدید را مجددا انجام دهید.", null, 'HTML');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban_list_get['status_extend'] == "off_extend") {
        sendmessage($from_id, "❌ امکان تمدید در این پنل وجود ندارد", null, 'html');
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        return;
    }
    if ($DataUserOut['status'] == "on_hold") {
        sendmessage($from_id, "❌ هنوز به سرویس متصل نشده اید برای تمدید سرویس ابتدا به سرویس متصل شوید سپس اقدام به تمدید کنید", null, 'html');
        return;
    }
    savedata("save", "name_panel", $nameloc['Service_location']);
    deletemessage($from_id, $message_id);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $query = "SELECT * FROM product WHERE (Location = '{$nameloc['Service_location']}' OR Location = '/all')AND agent= '{$userbot['agent']}'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $productnotexits = $stmt->rowCount();
    if ($productnotexits != 0 and $setting['show_product'] == false) {
        $statuscustomvolume = json_decode($marzban_list_get['customvolume'], true)[$userbot['agent']];
        if ($statuscustomvolume == "1" && $marzban_list_get['type'] != "Manualsale") {
            $statuscustom = true;
        } else {
            $statuscustom = false;
        }
        $query = "SELECT * FROM product WHERE (Location = '{$marzban_list_get['name_panel']}' OR Location = '/all')AND agent= '{$userbot['agent']}'";
        $prodcut = KeyboardProduct($marzban_list_get['name_panel'], $query, 0, "selectproductextends_", $statuscustom, "backuser", null, $customvolume = "customvolumeextend");
        sendmessage($from_id, "🛍️ لطفاً سرویسی که می‌خواهید تمدید کنید را انتخاب کنید!", $prodcut, 'HTML');
    } else {
        $custompricevalue = $setting['pricevolume'];
        $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
        $mainvolume = $mainvolume[$userbot['agent']];
        $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
        $maxvolume = $maxvolume[$userbot['agent']];
        $textcustom = "📌 حجم درخواستی خود را ارسال کنید.
🔔قیمت هر گیگ حجم $custompricevalue تومان می باشد.
🔔 حداقل حجم $mainvolume گیگابایت و حداکثر $maxvolume گیگابایت می باشد.";
        sendmessage($from_id, $textcustom, $backuser, 'html');
        step('gettimecustomvolextend', $from_id);
    }
} elseif ($datain == "customvolumeextend") {
    $userdate = json_decode($user['Processing_value'], true);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    $custompricevalue = $setting['pricevolume'];
    $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
    $mainvolume = $mainvolume[$userbot['agent']];
    $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
    $maxvolume = $maxvolume[$userbot['agent']];
    $textcustom = "📌 حجم درخواستی خود را ارسال کنید.
🔔قیمت هر گیگ حجم $custompricevalue تومان می باشد.
🔔 حداقل حجم $mainvolume گیگابایت و حداکثر $maxvolume گیگابایت می باشد.";
    sendmessage($from_id, $textcustom, $backuser, 'html');
    step('gettimecustomvolextend', $from_id);
} elseif ($user['step'] == "gettimecustomvolextend") {
    savedata("save", "volume", $text);
    $userdate = json_decode($user['Processing_value'], true);
    $nameloc = select("invoice", "*", "id_invoice", $userdate['id_invoice'], "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
    $mainvolume = $mainvolume[$userbot['agent']];
    $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
    $maxvolume = $maxvolume[$userbot['agent']];
    $maintime = json_decode($marzban_list_get['maintime'], true);
    $maintime = $maintime[$userbot['agent']];
    $maxtime = json_decode($marzban_list_get['maxtime'], true);
    $maxtime = $maxtime[$userbot['agent']];
    if ($text > intval($maxvolume) || $text < intval($mainvolume)) {
        $texttime = "❌ حجم نامعتبر است.\n🔔 حداقل حجم $mainvolume گیگابایت و حداکثر $maxvolume گیگابایت می باشد";
        sendmessage($from_id, $texttime, $backuser, 'HTML');
        return;
    }
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidVolume'], $backuser, 'HTML');
        return;
    }
    $customtimevalueprice = $setting['pricetime'];
    $textcustom = "⌛️ زمان سرویس خود را انتخاب نمایید 
    📌 تعرفه هر روز  : $customtimevalueprice  تومان
    ⚠️ حداقل زمان $maintime روز  و حداکثر $maxtime روز  می توانید تهیه کنید";
    sendmessage($from_id, $textcustom, $backuser, 'html');
    step("gettimecustomextend", $from_id);
} elseif ($user['step'] == "gettimecustomextend" || preg_match('/^selectproductextends_(.*)/', $datain, $dataget)) {
    if ($user['step'] == "gettimecustomextend") {
        if (!ctype_digit($text)) {
            sendmessage($from_id, $textbotlang['users']['customSellVolume']['invalidTime'], $backuser, 'HTML');
            return;
        }
    }
    $userdate = json_decode($user['Processing_value'], true);
    $nameloc = select("invoice", "*", "id_invoice", $userdate['id_invoice'], "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($user['step'] == "gettimecustomextend") {
        $maintime = json_decode($marzban_list_get['maintime'], true);
        $maintime = $maintime[$userbot['agent']];
        $maxtime = json_decode($marzban_list_get['maxtime'], true);
        $maxtime = $maxtime[$userbot['agent']];
        if (intval($text) > intval($maxtime) || intval($text) < intval($maintime)) {
            $texttime = "❌ زمان ارسال شده نامعتبر است . زمان باید بین $maintime روز تا $maxtime روز باشد";
            sendmessage($from_id, $texttime, $backuser, 'HTML');
            return;
        }
        $custompricevalue = $setting['pricevolume'];
        $customtimevalueprice = $setting['pricetime'];
        $datapish = array(
            "Volume_constraint" => $userdate['volume'],
            "name_product" => $textbotlang['users']['customSellVolume']['title'],
            "code_product" => "customvolume",
            "Service_time" => $text,
            "price_product" => ($userdate['volume'] * $custompricevalue) + ($text * $customtimevalueprice)
        );
        savedata("save", "time", $text);
    } else {
        $product = $dataget[1];
        savedata("save", "code_product", $product);
        $product = select("product", "*", "code_product", $product);
        $productlist = json_decode(file_get_contents('product.json'), true);
        if (isset($productlist[$product['code_product']])) {
            $product['price_product'] = $productlist[$product['code_product']];
        }
        $datapish = array(
            "Volume_constraint" => $product['Volume_constraint'],
            "name_product" => $product['name_product'],
            "code_product" => $product['code_product'],
            "Service_time" => $product['Service_time'],
            "price_product" => $product['price_product']
        );
    }
    $textextend = "📜 فاکتور تمدید شما برای نام کاربری {$nameloc['username']} ایجاد شد.
        
💸 مبلغ تمدید :{$datapish['price_product']}
⏱ مدت زمان تمدید : {$datapish['Service_time']} روز
🔋 حجم تمدید :{$datapish['Volume_constraint']} گیگ
💸 موجودی کیف پول : {$user['Balance']}
✅ برای تایید و تمدید سرویس روی دکمه زیر کلیک کنید";
    $keyboardextend = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['extend']['confirm'], 'callback_data' => "confirmserivce-" . $nameloc['id_invoice']],
            ],
            [
                ['text' => $textbotlang['users']['backbtn'], 'callback_data' => "backuser"]
            ]
        ]
    ]);
    if ($user['step'] != "gettimecustomextend") {
        Editmessagetext($from_id, $message_id, $textextend, $keyboardextend, 'HTML');
    } else {
        sendmessage($from_id, $textextend, $keyboardextend, 'HTML');
    }
    step("home", $from_id);
} elseif (preg_match('/^confirmserivce-(.*)/', $datain, $dataget)) {
    Editmessagetext($from_id, $message_id, $text_inline, json_encode(['inline_keyboard' => []]));
    $id_invoice = $dataget[1];
    $userdate = json_decode($user['Processing_value'], true);
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban_list_get['status_extend'] == "off_extend") {
        sendmessage($from_id, "❌ امکان تمدید در این پنل وجود ندارد", null, 'html');
        return;
    }
    if (isset($userdate['code_product'])) {
        $product = $userdate['code_product'];
        $product = select("product", "*", "code_product", $product);
        $productlist = json_decode(file_get_contents('product.json'), true);
        $priceproductmain = $product['price_product'];
        if (isset($productlist[$product['code_product']])) {
            $product['price_product'] = $productlist[$product['code_product']];
        }
        $datafactor = array(
            "Volume_constraint" => $product['Volume_constraint'],
            "name_product" => $product['name_product'],
            "code_product" => $product['code_product'],
            "Service_time" => $product['Service_time'],
            "price_product" => $product['price_product'],
            "price_productMain" => $priceproductmain,
        );
    } else {
        $custompricevalue = $setting['pricevolume'];
        $customtimevalueprice = $setting['pricetime'];
        $custompricevalueBot = $setting['minpricevolume'];
        $customtimevaluepriceBot = $setting['minpricetime'];
        $datafactor = array(
            "Volume_constraint" => $userdate['volume'],
            "name_product" => $textbotlang['users']['customSellVolume']['title'],
            "Service_time" => $userdate['time'],
            "code_product" => "custom_volume",
            "price_product" => ($userdate['volume'] * $custompricevalue) + ($userdate['time'] * $customtimevalueprice),
            "price_productMain" => ($userdate['volume'] * $custompricevalueBot) + ($userdate['time'] * $customtimevaluepriceBot),
            "data_limit_reset" => "no_reset"
        );
    }
    $productlist_name = json_decode(file_get_contents('product_name.json'), true);
    $datafactor['name_product'] = empty($productlist_name[$datafactor['code_product']]) ? $datafactor['name_product'] : $productlist_name[$datafactor['code_product']];
    $botbalance = select("botsaz", "*", "bot_token", $ApiToken, "select");
    $userbotbalance = select("user", "*", "id", $botbalance['id_user'], "select");
    if ($datafactor['price_productMain'] >= $userbotbalance['Balance'] && $userbotbalance['agent'] != "n2") {
        sendmessage($from_id, "❌ خطایی در خرید رخ داده است برای رفع مشکل با پشتیبانی در ارتباط باشید", $keyboard, 'HTML');
        step("home", $from_id);
        foreach ($admin_ids as $admin) {
            sendmessage($admin, "❌ ادمین عزیز موجودی شما به پایان رسید برای فعالسازی به ربات اصلی مراجعه و ربات خود را شارژ نمایید.", null, 'HTML');
        }
        return;
    }
    if (intval($userbotbalance['maxbuyagent']) != 0 and $userbotbalance['agent'] == "n2") {
        $pricecapcheck = $datafactor['price_productMain'];
        if (intval($userbotbalance['pricediscount']) != 0) {
            $pricecapcheck = $pricecapcheck - (($pricecapcheck * $userbotbalance['pricediscount']) / 100);
        }
        if (($userbotbalance['Balance'] - $pricecapcheck) < -intval($userbotbalance['maxbuyagent'])) {
            sendmessage($from_id, "❌ خطایی در خرید رخ داده است برای رفع مشکل با پشتیبانی در ارتباط باشید", $keyboard, 'HTML');
            step("home", $from_id);
            foreach ($admin_ids as $admin) {
                sendmessage($admin, "❌ ادمین عزیز شما به حداکثر سقف خرید خود رسیده اید برای ادامه فروش ابتدا حساب خود را در ربات اصلی شارژ نمایید.", null, 'HTML');
            }
            return;
        }
    }
    if ($datafactor['price_product'] > $user['Balance'] && intval($datafactor['price_product']) != 0) {
        $marzbandirectpay = select("shopSetting", "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        $Balance_prim = $datafactor['price_product'] - $user['Balance'];
        if ($Balance_prim <= 1)
            $Balance_prim = 0;
        $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$userbot['agent']]);
        $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$userbot['agent']]);
        $bakinfos = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "account"],
                ]
            ]
        ]);
        Editmessagetext($from_id, $message_id, "❌ موجودی شما برای خرید سرویس کافی نمی باشد.
💸  برای افزایش موجودی مبلغ را  به تومان وارد کنید:
✅  حداقل مبلغ $minbalance حداکثر مبلغ $maxbalance تومان می باشد", $bakinfos, 'HTML');
        step('get_price', $from_id);
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    $extend = $ManagePanel->extend($marzban_list_get['Methodextend'], $datafactor['Volume_constraint'], $datafactor['Service_time'], $nameloc['username'], $datafactor['code_product'], $marzban_list_get['code_panel']);
    if ($extend['status'] == false) {
        $extend['msg'] = json_encode($extend['msg']);
        $textreports = "
خطای تمدید سرویس در ربات نماینده
نام پنل : {$marzban_list_get['name_panel']}
نام کاربری سرویس : {$nameloc['username']}
دلیل خطا : {$extend['msg']}";
        sendmessage($from_id, "❌خطایی در تمدید سرویس در ربات رخ داده با پشتیبانی در ارتباط باشید", null, 'HTML');
        if (strlen($settingmain['Channel_Report']) > 0) {
            telegram('sendmessage', [
                'chat_id' => $settingmain['Channel_Report'],
                'message_thread_id' => $errorreport,
                'text' => $textreports,
                'parse_mode' => "HTML"
            ], $APIKEY);
        }
        return;
    }
    $stmt = $pdo->prepare("INSERT IGNORE INTO service_other (id_user, username,value,type,time,price,output) VALUES (?, ?, ?, ?,?,?,?)");
    $dateacc = date('Y/m/d H:i:s');
    $value = $datafactor['Volume_constraint'] . "_" . $datafactor['Service_time'];
    $value = json_encode(array(
        "volumebuy" => $datafactor['Volume_constraint'],
        "Service_time" => $datafactor['Service_time'],
        "oldvolume" => $DataUserOut['data_limit'],
        "oldtime" => $DataUserOut['expire'],
        'code_product' => $datafactor['code_product'],
        'id_order' => $nameloc['id_invoice']
    ));
    $type = "extend_user";
    $stmt->execute([$from_id, $nameloc['username'], $value, $type, $dateacc, $datafactor['price_product'], json_encode($extend)]);
    update("invoice", "Status", "active", "id_invoice", $id_invoice);
    if (intval($datafactor['price_product']) != 0) {
        $Balance_prim = $user['Balance'] - $datafactor['price_product'];
        $userbalance = json_decode(file_get_contents("data/$from_id/$from_id.json"), true);
        $userbalance['Balance'] = $Balance_prim;
        file_put_contents("data/$from_id/$from_id.json", json_encode($userbalance));
    }
    if (intval($userbotbalance['pricediscount']) != 0) {
        $resultper = ($datafactor['price_productMain'] * $userbotbalance['pricediscount']) / 100;
        $datafactor['price_productMain'] = $datafactor['price_productMain'] - $resultper;
    }
    $Balancebot = $userbotbalance['Balance'] - $datafactor['price_productMain'];
    $stmt = $pdo->prepare("UPDATE user SET Balance = Balance - :price WHERE id = :id");
    $stmt->execute([':price' => $datafactor['price_productMain'], ':id' => $userbotbalance['id']]);
    $keyboardextendfnished = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backlist'], 'callback_data' => "backorder"],
            ],
            [
                ['text' => $textbotlang['users']['status']['backservice'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    $priceproductformat = number_format($datafactor['price_product']);
    $balanceformatsell = number_format($userbalance = json_decode(file_get_contents("data/$from_id/$from_id.json"), true)['Balance']);
    $balanceformatsellbefore = number_format($user['Balance'], 0);
    $textextend = "✅ تمدید برای سرویس شما با موفقیت صورت گرفت
 
▫️نام سرویس : {$nameloc['username']}
▫️نام محصول : {$datafactor['name_product']}
▫️مبلغ تمدید $priceproductformat تومان
";
    sendmessage($from_id, $textextend, $keyboardextendfnished, 'HTML');
    $timejalali = jdate('Y/m/d H:i:s');
    $text_report = "📣 جزئیات تمدید اکانت در ربات نماینده ثبت شد .
    
▫️آیدی عددی کاربر : <code>$from_id</code>
▫️آیدی عددی نماینده : <code>{$userbot['id']}</code>
▫️نام کاربری ربات نماینده :@{$dataBase['username']}

▫️نام کاربری کاربر :@$username
▫️نام کاربری کانفیگ :{$nameloc['username']}
▫️نام کاربر : $first_name
▫️موقعیت سرویس سرویس : {$nameloc['Service_location']}
▫️نام محصول : {$datafactor['name_product']}
▫️حجم محصول : {$datafactor['Volume_constraint']}
▫️زمان محصول : {$datafactor['Service_time']}
▫️مبلغ تمدید : {$datafactor['price_product']} تومان
▫️موجودی قبل از خرید : $balanceformatsellbefore تومان
▫️موجودی بعد از خرید : $balanceformatsell تومان
▫️زمان خرید : $timejalali";
    if (strlen($settingmain['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $settingmain['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ], $APIKEY);
    }
} elseif (preg_match('/changelink_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        return;
    }
    if ($DataUserOut['status'] == "disabled" || $DataUserOut['status'] == "on_hold") {
        sendmessage($from_id, "❌ سرویس غیرفعال است و امکان تعویض لینک برای سرویس وجود ندارد.", null, 'html');
        return;
    }
    $keyboardextend = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['changeLink']['confirm'], 'callback_data' => "confirmchange_" . $nameloc['id_invoice']],
            ],
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['changeLink']['warnchange'], $keyboardextend);
} elseif (preg_match('/confirmchange_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->Revoke_sub($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, '❌ خطایی در تغییر لینک رخ داده است.', null, 'HTML');
        return;
    }
    if ($marzban_list_get['sublink'] == "onsublink") {
        $output_config_link = $DataUserOut['subscription_url'];
    }
    if ($marzban_list_get['config'] == "onconfig") {
        if (!isset($DataUserOut['configs']))
            return;
        if (isset($DataUserOut['configs']) and count($DataUserOut['configs']) != 0) {
            foreach ($DataUserOut['configs'] as $configs) {
                $config .= "\n" . $configs;
            }
        } else {
            $config .= "";
        }
        $output_config_link = $config;
    }
    $textconfig = "✅ کانفیگ شما با موفقیت بروزرسانی گردید.
اشتراک شما : 
<code>$output_config_link</code>";
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textconfig, $bakinfos);
}
require_once 'admin.php';
