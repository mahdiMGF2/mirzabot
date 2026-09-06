<?php

date_default_timezone_set('Asia/Tehran');
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');
ini_set('memory_limit', '512M');
require_once 'config.php';
require_once 'botapi.php';
require_once 'jdf.php';
require_once 'function.php';
mirzaEnsureInstallerRemoved();
require_once 'keyboard.php';
require_once 'vendor/autoload.php';
require_once 'panels.php';
$textbotlang = languagechange();
$text = restoreCustomEmojiLabel($text);
#-----------telegram_ip_ranges------------#
if (!checktelegramip())
    die("Unauthorized access");
#-----------end telegram_ip_ranges------------#
if ($is_bot)
    return;
if (isset($update['chat_member'])) {
    $status = $update['chat_member']['new_chat_member']['status'];
    $from_id = $update['chat_member']['new_chat_member']['user']['id'];
    $user = select("user", "id", $from_id);
    $keyboard_channel_left = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['rejoin'], 'url' => "https://t.me/{$update['chat_member']['chat']['username']}"],
            ],
        ]
    ]);
    if (in_array($status, ['left', 'kicked', 'restricted'])) {
        sendmessage($from_id, $textbotlang['users']['channel']['left_channel'], $keyboard_channel_left, 'html');
        return;
    }
}
if (!in_array($Chat_type, ["private", "supergroup"]))
    return;
if (isset($chat_member))
    return;
$first_name = sanitizeUserName($first_name);
$setting = select("setting", "*");
$ManagePanel = new ManagePanel();
$keyboard_check = json_decode($setting['keyboardmain'], true);
if (is_array($keyboard_check) && preg_match('/[\x{600}-\x{6FF}\x{FB50}-\x{FDFF}]/u', $keyboard_check['keyboard'][0][0]['text'])) {
    $keyboardmain = '{"keyboard":[[{"text":"text_sell"},{"text":"text_extend"}],[{"text":"text_usertest"},{"text":"text_wheel_luck"}],[{"text":"text_Purchased_services"},{"text":"accountwallet"}],[{"text":"text_affiliates"},{"text":"text_Tariff_list"}],[{"text":"text_support"},{"text":"text_help"}]]}';
    update("setting", "keyboardmain", $keyboardmain, null, null);
}

if (intval($from_id) == 0)
    return;
#-------------Variable----------#
$otherreport = select("topicid", "idreport", "report", "otherreport", "select")['idreport'];
if ($setting['statusnewuser'] == "onnewuser" && !rowExists("user", "id", $from_id)) {
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['manageUser']['manageUserBtn'], 'callback_data' => 'manageuser_' . $from_id],
            ],
        ]
    ]);
    $newuser = sprintf($textbotlang['Admin']['reportgroup']['newUser'], $first_name, $username, "<a href = \"tg://user?id=$from_id\">$from_id</a>");
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherreport,
            'text' => $newuser,
            'reply_markup' => $Response,
            'parse_mode' => "HTML"
        ]);
    }
}
$date = time();
if ($from_id != 0) {
    if ($setting['verifystart'] != "onverify") {
        $valueverify = 1;
    } else {
        $valueverify = 0;
    }
    $randomString = bin2hex(random_bytes(6));
    $stmt = $pdo->prepare("INSERT IGNORE INTO user (id , step,limit_usertest,User_Status,number,Balance,pagenumber,username,agent,message_count,last_message_time,affiliates,affiliatescount,cardpayment,number_username,namecustom,register,verify,codeInvitation,pricediscount,maxbuyagent,joinchannel,score,status_cron) VALUES (:from_id, 'none',:limit_usertest_all,'Active','none','0','1',:username,'f','0','0','0','0',:showcard,'100','none',:date,:verifycode,:codeInvitation,'0','0','0','0','1')");
    $stmt->bindParam(':from_id', $from_id);
    $stmt->bindParam(':limit_usertest_all', $setting['limit_usertest_all']);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':showcard', $setting['showcard']);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':verifycode', $valueverify);
    $stmt->bindParam(':codeInvitation', $randomString);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        clearSelectCache('user');
    }
}
$user = select("user", "*", "id", $from_id, "select");
if ($user == false) {
    $user = array();
    $user = array(
        'step' => '',
        'Processing_value' => '',
        'User_Status' => '',
        'agent' => '',
        'username' => '',
        'limit_usertest' => '',
        'message_count' => '',
        'affiliates' => '',
        'last_message_time' => '',
        'cardpayment' => '',
        'roll_Status' => '',
        'number_username' => '',
        'number' => '',
        'register' => '',
        'codeInvitation' => '',
        'pricediscount' => '',
        'joinchannel' => '',
        'score' => "",
        'limitchangeloc' => ''
    );
}
$admin_ids = select("admin", "id_admin", null, null, "FETCH_COLUMN");
if (!is_array($admin_ids)) {
    $admin_ids = [];
}
$channels_id = select("channels", "link", null, null, "FETCH_COLUMN");
$topic_id = select("topicid", "*", null, null, "fetchAll");
$statusnote = false;
foreach ($topic_id as $topic) {
    if ($topic['report'] == "reportnight")
        $reportnight = $topic['idreport'];
    if ($topic['report'] == 'reporttest')
        $reporttest = $topic['idreport'];
    if ($topic['report'] == 'errorreport')
        $errorreport = $topic['idreport'];
    if ($topic['report'] == 'porsantreport')
        $porsantreport = $topic['idreport'];
    if ($topic['report'] == 'reportcron')
        $reportcron = $topic['idreport'];
    if ($topic['report'] == 'backupfile')
        $reportbackup = $topic['idreport'];
    if ($topic['report'] == 'buyreport')
        $buyreport = $topic['idreport'];
    if ($topic['report'] == 'otherservice')
        $otherservice = $topic['idreport'];
    if ($topic['report'] == 'paymentreport')
        $paymentreports = $topic['idreport'];

}
if ($setting['statusnamecustom'] == 'onnamecustom')
    $statusnote = true;
if ($setting['statusnoteforf'] == "0" && $user['agent'] == "f")
    $statusnote = false;
if ($user['username'] == "none" || $user['username'] == null || $user['username'] != $username) {
    update("user", "username", $username, "id", $from_id);
}
$lang_array = ['fa', 'en', 'ru', 'zh'];
if (!in_array($user['lang'], $lang_array)) {
    update("user", "lang", 'fa', "id", $from_id);
}
if ($user['register'] == "none") {
    update("user", "register", time(), "id", $from_id);
}
if (!in_array($user['agent'], ["n", "n2", "f"]))
    update("user", "agent", "f", "id", $from_id);
#-----------User_Status------------#
if ($user['User_Status'] == "block" && !in_array($from_id, $admin_ids)) {
    $textblock = sprintf($textbotlang['users']['block']['descriptions'], $user['description_blocking']);
    sendmessage($from_id, $textblock, null, 'html');
    return;
}
#---------anti spam--------------#
$timebot = time();
$TimeLastMessage = $timebot - intval($user['last_message_time']);
if (floor($TimeLastMessage / 60) >= 1) {
    $stmt = $pdo->prepare("UPDATE user SET last_message_time = ?, message_count = '1' WHERE id = ?");
    $stmt->execute([$timebot, $from_id]);
    clearSelectCache('user');
} else {
    if (!in_array($from_id, $admin_ids)) {
        $addmessage = intval($user['message_count']) + 1;
        update("user", "message_count", $addmessage, "id", $from_id);
        if (intval($addmessage) >= 35) {
            $User_Status = "block";
            $textblok = sprintf($textbotlang['users']['spam']['spamedReport'], $from_id);
            $Response = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['Admin']['manageUser']['manageUserBtn'], 'callback_data' => 'manageuser_' . $from_id],
                    ],
                ]
            ]);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $otherservice,
                    'text' => $textblok,
                    'parse_mode' => "HTML",
                    'reply_markup' => $Response
                ]);
            }
            update("user", "User_Status", $User_Status, "id", $from_id);
            update("user", "description_blocking", $textbotlang['users']['spam']['spamed'], "id", $from_id);
            sendmessage($from_id, $textbotlang['users']['spam']['spamedMessage'], null, 'html');
            return;
        }
    }
}


if (strpos($text, "/start ") !== false && $user['step'] != "gettextSystemMessage") {
    $affiliatesid = explode(" ", $text)[1];
    if (!in_array($affiliatesid, ['start', "usertest", "/start", "buy", "help"])) {
        isValidInvitationCode($setting, $from_id, $user['verify']);
        if ($setting['affiliatesstatus'] == "offaffiliates") {
            sendmessage($from_id, $textbotlang['users']['affiliates']['offaffiliates'], $keyboard, 'HTML');
            return;
        }
        if (is_numeric($affiliatesid) && rowExists("user", "id", $affiliatesid)) {
            if ($affiliatesid == $from_id) {
                sendmessage($from_id, $textbotlang['users']['affiliates']['invalidaffiliates'], null, 'html');
                return;
            }
            $user = select("user", "*", "id", $from_id, "select");
            if (intval($user['affiliates']) != 0) {
                sendmessage($from_id, $textbotlang['users']['affiliates']['affiliateedago'], null, 'html');
                return;
            }
            update("user", "affiliates", $affiliatesid, "id", $from_id);
            $useraffiliates = select("user", "*", 'id', $affiliatesid, "select");
            sendmessage($from_id, sprintf($textbotlang['users']['affiliates']['welcomeInvited'], $useraffiliates['username']), $keyboard, 'html');
            sendmessage($affiliatesid, sprintf($textbotlang['users']['affiliates']['newReferralJoined'], $username), $keyboard, 'html');
            $addcountaffiliates = intval($useraffiliates['affiliatescount']) + 1;
            update("user", "affiliatescount", $addcountaffiliates, "id", $affiliatesid);
            $stmt = $pdo->prepare("INSERT IGNORE INTO reagent_report (user_id, get_gift,time,reagent) VALUES (?, ?,?, ?)");
            $dateacc = date('Y/m/d H:i:s');
            $type_gift = false;
            $stmt->execute([$from_id, $type_gift, $dateacc, $affiliatesid]);
        } else {
            sendmessage($from_id, $textbotlang['users']['text_start'], $keyboard, 'html');
            update("user", "Processing_value", "0", "id", $from_id);
            update("user", "Processing_value_one", "0", "id", $from_id);
            update("user", "Processing_value_tow", "0", "id", $from_id);
            update("user", "Processing_value_four", "0", "id", $from_id);
            step('home', $from_id);
        }
    } else {
        $text = $affiliatesid;
    }
}
if (intval($user['verify']) == 0 && !in_array($from_id, $admin_ids) && $setting['verifystart'] == "onverify") {
    $textverify = sprintf($textbotlang['users']['account']['notVerifiedNotice'], $setting['id_support']);
    sendmessage($from_id, $textverify, null, 'html');
    return;
}
;

#-----------roll------------#
if ($setting['roll_Status'] == "rolleon" && $user['roll_Status'] == 0 && ($text != $textbotlang['keyboard']['acceptRulesButton'] and $datain != "acceptrule") && !in_array($from_id, $admin_ids)) {
    sendmessage($from_id, $textbotlang['textbot']['rules'], $confrimrolls, 'html');
    return;
}
if ($text == $textbotlang['keyboard']['acceptRules'] or $datain == "acceptrule") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['users']['Rules'], $keyboard, 'html');
    $confrim = true;
    update("user", "roll_Status", $confrim, "id", $from_id);
}

#-----------Bot_Status------------#
if ($setting['Bot_Status'] == "botstatusoff" && !in_array($from_id, $admin_ids)) {
    sendmessage($from_id, $textbotlang['textbot']['botOff'], null, 'html');
    return;
}
#-----------/start------------#
if ($user['joinchannel'] != "active") {
    if (count($channels_id) != 0) {
        $channels = channel($channels_id);
        if ($datain == "confirmchannel") {
            if (count($channels) == 0) {
                deletemessage($from_id, $message_id);
                sendmessage($from_id, $textbotlang['users']['text_start'], $keyboard, 'html');
                telegram('answerCallbackQuery', [
                    'callback_query_id' => $callback_query_id,
                    'text' => $textbotlang['users']['channel']['confirmed'],
                    'show_alert' => false,
                    'cache_time' => 5,
                ]);
                return;
            }
            $keyboardchannel = [
                'inline_keyboard' => [],
            ];
            foreach ($channels as $channel) {
                $channelremark = select("channels", "*", 'link', $channel, "select");
                if ($channelremark['remark'] == null)
                    continue;
                if ($channelremark['linkjoin'] == null)
                    continue;
                $keyboardchannel['inline_keyboard'][] = [
                    [
                        'text' => "{$channelremark['remark']}",
                        'url' => $channelremark['linkjoin']
                    ],
                ];
            }
            $keyboardchannel['inline_keyboard'][] = [['text' => $textbotlang['users']['channel']['confirmjoin'], 'callback_data' => "confirmchannel"]];
            $keyboardchannel = json_encode($keyboardchannel);
            Editmessagetext($from_id, $message_id, $textbotlang['textbot']['channel'], $keyboardchannel);
            telegram('answerCallbackQuery', [
                'callback_query_id' => $callback_query_id,
                'text' => $textbotlang['users']['channel']['notconfirmed'],
                'show_alert' => true,
                'cache_time' => 5,
            ]);
            $partsaffiliates = explode("_", $user['Processing_value_four']);
            if ($partsaffiliates[0] == "affiliates") {
                $affiliatesid = $partsaffiliates[1];
                if (!rowExists("user", "id", $affiliatesid)) {
                    sendmessage($from_id, $textbotlang['users']['affiliates']['affiliatesidyou'], null, 'html');
                    return;
                }
                if ($affiliatesid == $from_id) {
                    sendmessage($from_id, $textbotlang['users']['affiliates']['invalidaffiliates'], null, 'html');
                    return;
                }
                $marzbanDiscountaffiliates = select("affiliates", "*", null, null, "select");
                $useraffiliates = select("user", "*", 'id', $affiliatesid, "select");
                if ($marzbanDiscountaffiliates['Discount'] == "onDiscountaffiliates") {
                    $marzbanDiscountaffiliates = select("affiliates", "*", null, null, "select");
                    $Balance_add_user = $useraffiliates['Balance'] + $marzbanDiscountaffiliates['price_Discount'];
                    update("user", "Balance", $Balance_add_user, "id", $affiliatesid);
                    $addbalancediscount = number_format($marzbanDiscountaffiliates['price_Discount'], 0);
                    sendmessage($affiliatesid, strtr($textbotlang['users']['affiliates']['balanceGift'], ['{addbalancediscount}' => $addbalancediscount, '{from_id}' => $from_id]), null, 'html');
                }
                sendmessage($from_id, $textbotlang['users']['text_start'], $keyboard, 'html');
                $addcountaffiliates = intval($useraffiliates['affiliatescount']) + 1;
                update("user", "affiliates", $affiliatesid, "id", $from_id);
                update("user", "Processing_value_four", "none", "id", $from_id);
                update("user", "affiliatescount", $addcountaffiliates, "id", $affiliatesid);
            }
            return;
        }
        if (count($channels) != 0 && !in_array($from_id, $admin_ids)) {
            $keyboardchannel = [
                'inline_keyboard' => [],
            ];
            foreach ($channels as $channel) {
                $channelremark = select("channels", "*", 'link', $channel, "select");
                if ($channelremark['remark'] == null)
                    continue;
                if ($channelremark['linkjoin'] == null)
                    continue;
                $keyboardchannel['inline_keyboard'][] = [
                    [
                        'text' => "{$channelremark['remark']}",
                        'url' => $channelremark['linkjoin']
                    ],
                ];
            }
            $keyboardchannel['inline_keyboard'][] = [['text' => $textbotlang['users']['channel']['confirmjoin'], 'callback_data' => "confirmchannel"]];
            $keyboardchannel = json_encode($keyboardchannel);
            sendmessage($from_id, $textbotlang['textbot']['channel'], $keyboardchannel, 'html');
            return;
        }
    }
}
if ($text == "/start" || $datain == "start" || $text == "start") {
    sendmessage($from_id, $textbotlang['users']['text_start'], $keyboard, "html");
    update("user", "Processing_value", "0", "id", $from_id);
    update("user", "Processing_value_one", "0", "id", $from_id);
    update("user", "Processing_value_tow", "0", "id", $from_id);
    update("user", "Processing_value_four", "0", "id", $from_id);
    step('home', $from_id);
    return;
} elseif ($text == "version") {
    sendmessage($from_id, file_get_contents('version'), null, 'html');
} elseif ($text == $textbotlang['users']['backbtn'] || $datain == "backuser") {
    if ($datain == "backuser")
        deletemessage($from_id, $message_id);
    $message_id = sendmessage($from_id, $textbotlang['users']['back'], $keyboard, 'html');
    step('home', $from_id);
    update("user", "Processing_value", "0", "id", $from_id);
    update("user", "Processing_value_one", "0", "id", $from_id);
    update("user", "Processing_value_tow", "0", "id", $from_id);
    update("user", "Processing_value_four", "0", "id", $from_id);
    return;
} elseif ($user['step'] == 'get_number') {
    if (empty($user_phone)) {
        sendmessage($from_id, $textbotlang['users']['number']['false'], $request_contact, 'html');
        return;
    }
    if ($contact_id != $from_id) {
        sendmessage($from_id, $textbotlang['users']['number']['warning'], $request_contact, 'html');
        return;
    }
    if ($setting['iran_number'] == "onAuthenticationiran" && !preg_match("/989[0-9]{9}$/", $user_phone)) {
        sendmessage($from_id, $textbotlang['users']['number']['erroriran'], $request_contact, 'html');
        return;
    }
    sendmessage($from_id, $textbotlang['users']['number']['active'], json_encode(['inline_keyboard' => [], 'remove_keyboard' => true]), 'html');
    sendmessage($from_id, $textbotlang['users']['text_start'], $keyboard, 'html');
    update("user", "number", $user_phone, "id", $from_id);
    step('home', $from_id);
} elseif ($text == $textbotlang['textbot']['purchasedServices'] || $datain == "backorder" || $text == "/services") {
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :id_user AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold')");
    $stmt->bindParam(':id_user', $from_id);
    $stmt->execute();
    $invoices = $stmt->fetch(PDO::FETCH_ASSOC);
    if (is_null($invoices) && $setting['NotUser'] == "offnotuser") {
        sendmessage($from_id, $textbotlang['users']['sell']['service_not_available'], null, 'html');
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
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :from_id AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR status = 'send_on_hold') ORDER BY time_sell DESC LIMIT :start_index, :items_per_page");
    $stmt->bindParam(':from_id', $from_id, PDO::PARAM_STR);
    $stmt->bindParam(':start_index', $start_index, PDO::PARAM_INT);
    $stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    if ($setting['statusnamecustom'] == 'onnamecustom') {
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
    } else {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . "✨",
                    'callback_data' => "product_" . $row['id_invoice']
                ],
            ];
        }
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_page'
        ],
        ['text' => $textbotlang['users']['search']['title'], 'callback_data' => 'searchservice']
    ];
    $backuser = [
        [
            'text' => $textbotlang['keyboard']['backToMainMenu'],
            'callback_data' => 'backuser'
        ]
    ];
    if ($setting['NotUser'] == "onnotuser") {
        $keyboardlists['inline_keyboard'][] = [['text' => $textbotlang['users']['page']['notusernameme'], 'callback_data' => 'notusernameme']];
    }
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backuser;
    $keyboard_json = json_encode($keyboardlists);
    if ($datain == "backorder") {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['service_sell'], $keyboard_json);
    } else {
        sendmessage($from_id, $textbotlang['users']['sell']['service_sell'], $keyboard_json, 'html');
    }
} elseif ($datain == 'next_page') {
    $numpage = select("invoice", "id_user", "id_user", $from_id, "count");
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
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :from_id AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR status = 'send_on_hold') ORDER BY time_sell DESC LIMIT :start_index, :items_per_page");
    $stmt->bindParam(':from_id', $from_id, PDO::PARAM_STR);
    $stmt->bindParam(':start_index', $start_index, PDO::PARAM_INT);
    $stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    if ($setting['statusnamecustom'] == 'onnamecustom') {
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
    } else {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . "✨",
                    'callback_data' => "product_" . $row['id_invoice']
                ],
            ];
        }
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_page'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_page'
        ]
    ];
    $backuser = [
        [
            'text' => $textbotlang['keyboard']['backToMainMenu'],
            'callback_data' => 'backuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = [['text' => $textbotlang['users']['search']['title'], 'callback_data' => 'searchservice']];
    if ($setting['NotUser'] == "onnotuser") {
        $keyboardlists['inline_keyboard'][] = [['text' => $textbotlang['users']['page']['notusernameme'], 'callback_data' => 'notusernameme']];
    }
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backuser;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['service_sell'], $keyboard_json);
} elseif ($datain == 'previous_page') {
    $numpage = select("invoice", "id_user", "id_user", $from_id, "count");
    $page = $user['pagenumber'];
    $items_per_page = 20;
    $sum = $user['pagenumber'] * $items_per_page;
    if ($sum > $numpage) {
        $previous_page = 1;
    } else {
        $previous_page = $page - 1;
    }
    if ($previous_page < 1) {
        $previous_page = 1;
    }
    $start_index = ($previous_page - 1) * $items_per_page;
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :from_id AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR status = 'send_on_hold') ORDER BY time_sell DESC LIMIT :start_index, :items_per_page");
    $stmt->bindParam(':from_id', $from_id, PDO::PARAM_STR);
    $stmt->bindParam(':start_index', $start_index, PDO::PARAM_INT);
    $stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();
    if ($setting['statusnamecustom'] == 'onnamecustom') {
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
    } else {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . "✨",
                    'callback_data' => "product_" . $row['id_invoice']
                ],
            ];
        }
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_page'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_page'
        ]
    ];
    $backuser = [
        [
            'text' => $textbotlang['keyboard']['backToMainMenu'],
            'callback_data' => 'backuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = [['text' => $textbotlang['users']['search']['title'], 'callback_data' => 'searchservice']];
    if ($setting['NotUser'] == "onnotuser") {
        $keyboardlists['inline_keyboard'][] = [['text' => $textbotlang['users']['page']['notusernameme'], 'callback_data' => 'notusernameme']];
    }
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backuser;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $previous_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['service_sell'], $keyboard_json);
} elseif ($datain == "notusernameme") {
    sendmessage($from_id, $textbotlang['users']['status']['sendUsername'], $backuser, 'html');
    step('getusernameinfo', $from_id);
} elseif ($user['step'] == "getusernameinfo") {
    if (empty($text))
        return;
    $usernameconfig = $text;
    update("user", "Processing_value", $usernameconfig, "id", $from_id);
    sendmessage($from_id, $textbotlang['textbot']['selectLocation'], $list_marzban_panel_user, 'html');
    step('getdata', $from_id);
} elseif (preg_match('/locationnotuser_(.*)/', $datain, $dataget)) {
    $marzban_list_get = select("marzban_panel", "*", "code_panel", $dataget[1]);
    update("user", "Processing_value_four", $marzban_list_get['code_panel'], "id", $from_id);
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $user['Processing_value']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        if ($DataUserOut['msg'] == "User not found") {
            sendmessage($from_id, $textbotlang['users']['status']['notUsernameGet'], $keyboard, 'html');
            step('home', $from_id);
            return;
        }
        sendmessage($from_id, $textbotlang['users']['status']['error'], $keyboard, 'html');
        step('home', $from_id);
        return;
    }
    #-------------[ status ]----------------#
    $status = $DataUserOut['status'];
    $status_var = [
        'active' => $textbotlang['users']['status']['active'],
        'limited' => $textbotlang['users']['status']['limited'],
        'disabled' => $textbotlang['users']['status']['disabled'],
        'deactivev' => $textbotlang['users']['status']['disabled'],
        'expired' => $textbotlang['users']['status']['expired'],
        'on_hold' => $textbotlang['users']['status']['on_hold'],
        'Unknown' => $textbotlang['users']['status']['unknown']
    ][$status];
    #--------------[ expire ]---------------#
    $expirationDate = $DataUserOut['expire'] ? jdate('Y/m/d', $DataUserOut['expire']) : $textbotlang['users']['status']['unlimited'];
    #-------------[ data_limit ]----------------#
    $LastTraffic = $DataUserOut['data_limit'] ? formatBytes($DataUserOut['data_limit']) : $textbotlang['users']['status']['unlimited'];
    #---------------[ RemainingVolume ]--------------#
    $output = $DataUserOut['data_limit'] - $DataUserOut['used_traffic'];
    $RemainingVolume = $DataUserOut['data_limit'] ? formatBytes($output) : $textbotlang['common']['labels']['unlimitedShort'];
    #---------------[ used_traffic ]--------------#
    $usedTrafficGb = $DataUserOut['used_traffic'] ? formatBytes($DataUserOut['used_traffic']) : $textbotlang['users']['status']['notConsumed'];
    #--------------[ day ]---------------#
    $timeDiff = $DataUserOut['expire'] - time();
    $day = $DataUserOut['expire'] ? floor($timeDiff / 86400) . $textbotlang['users']['status']['day'] : $textbotlang['users']['status']['unlimited'];
    #-----------------------------#


    $keyboardinfo = [
        'inline_keyboard' => [
            [
                ['text' => $DataUserOut['username'], 'callback_data' => "username"],
                ['text' => $textbotlang['users']['status']['username'], 'callback_data' => 'username'],
            ],
            [
                ['text' => $status_var, 'callback_data' => 'status_var'],
                ['text' => $textbotlang['users']['status']['stateus'], 'callback_data' => 'status_var'],
            ],
            [
                ['text' => $expirationDate, 'callback_data' => 'expirationDate'],
                ['text' => $textbotlang['users']['status']['expirationDate'], 'callback_data' => 'expirationDate'],
            ],
            [],
            [
                ['text' => $day, 'callback_data' => $textbotlang['common']['units']['dayShort']],
                ['text' => $textbotlang['users']['status']['daysleft'], 'callback_data' => 'day'],
            ],
            [
                ['text' => $LastTraffic, 'callback_data' => 'LastTraffic'],
                ['text' => $textbotlang['users']['status']['lastTraffic'], 'callback_data' => 'LastTraffic'],
            ],
            [
                ['text' => $usedTrafficGb, 'callback_data' => 'expirationDate'],
                ['text' => $textbotlang['users']['status']['usedTrafficGb'], 'callback_data' => 'expirationDate'],
            ],
            [
                ['text' => $RemainingVolume, 'callback_data' => 'RemainingVolume'],
                ['text' => $textbotlang['users']['status']['remainingVolume'], 'callback_data' => 'RemainingVolume'],
            ]
        ]
    ];
    $marzbanstatusextra = select("shopSetting", "*", "Namevalue", "statusextra", "select")['value'];
    if ($marzbanstatusextra == "onextra") {
        $keyboardinfo['inline_keyboard'][] = [
            ['text' => $textbotlang['users']['extend']['title'], 'callback_data' => 'extends_' . $DataUserOut['username'] . "_" . $dataget[1]],
            ['text' => $textbotlang['users']['extraVolume']['sellextra'], 'callback_data' => 'Extra_volumes_' . $DataUserOut['username'] . '_' . $dataget[1]],
        ];
    } else {
        $keyboardinfo['inline_keyboard'][] = [['text' => $textbotlang['users']['extend']['title'], 'callback_data' => 'extends_' . $DataUserOut['username'] . "_" . $dataget[1]]];
    }
    $keyboardinfo = json_encode($keyboardinfo);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['status']['info'], $keyboardinfo);
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboard, 'html');
    step('home', $from_id);
} elseif (preg_match('/^product_(\w+)/', $datain, $dataget) || preg_match('/updateproduct_(\w+)/', $datain, $dataget) || $user['step'] == "getuseragnetservice" || $datain == "productcheckdata") {
    if ($user['step'] == "getuseragnetservice") {
        $username = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $sql = "SELECT * FROM invoice WHERE (username LIKE CONCAT('%', :username, '%') OR note  LIKE CONCAT('%', :notes, '%') OR Volume LIKE CONCAT('%',:Volume, '%') OR Service_time LIKE CONCAT('%',:Service_time, '%')) AND id_user = :id_user AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold')";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':Service_time', $username, PDO::PARAM_STR);
        $stmt->bindParam(':Volume', $username, PDO::PARAM_STR);
        $stmt->bindParam(':notes', $username, PDO::PARAM_STR);
        $stmt->bindParam(':id_user', $from_id);
        $stmt->execute();
    } elseif ($datain == "productcheckdata") {
        $username = $user['Processing_value'];
        $sql = "SELECT * FROM invoice WHERE username = :username AND id_user = :id_user";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':id_user', $from_id);
        $stmt->execute();
    } elseif ($datain[0] == "u") {
        $username = $dataget[1];
        $sql = "SELECT * FROM invoice WHERE id_invoice = :username AND id_user = :id_user";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':id_user', $from_id);
        $stmt->execute();
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['keyboard']['infoRefreshed'],
            'show_alert' => false,
            'cache_time' => 5,
        ));
    } else {
        $username = $dataget[1];
        $sql = "SELECT * FROM invoice WHERE id_invoice = :username AND id_user = :id_user";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':id_user', $from_id);
        $stmt->execute();
    }
    if ($user['step'] == "getuseragnetservice" && $stmt->rowCount() > 1) {
        $countservice = $stmt->rowCount();
        $pages = 1;
        update("user", "pagenumber", $pages, "id", $from_id);
        $page = 1;
        $items_per_page = 20;
        $start_index = ($page - 1) * $items_per_page;
        $keyboardlists = [
            'inline_keyboard' => [],
        ];
        if ($setting['statusnamecustom'] == 'onnamecustom') {
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
        } else {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $keyboardlists['inline_keyboard'][] = [
                    [
                        'text' => "✨" . $row['username'] . "✨",
                        'callback_data' => "product_" . $row['id_invoice']
                    ],
                ];
            }
        }
        $backuser = [
            [
                'text' => $textbotlang['keyboard']['backToMainMenu'],
                'callback_data' => 'backuser'
            ]
        ];
        if ($setting['NotUser'] == "onnotuser") {
            $keyboardlists['inline_keyboard'][] = [['text' => $textbotlang['users']['page']['notusernameme'], 'callback_data' => 'notusernameme']];
        }
        $keyboardlists['inline_keyboard'][] = $backuser;
        $keyboard_json = json_encode($keyboardlists);
        sendmessage($from_id, strtr($textbotlang['users']['status']['servicesFound'], ['{countservice}' => $countservice]), $keyboard_json, 'html');
        step("home", $from_id);
        return;
    }
    $nameloc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$nameloc) {
        sendmessage($from_id, $textbotlang['users']['status']['infoUnavailable'], $keyboard, 'html');
        step('home', $from_id);
        return;
    }
    $username = $nameloc['id_invoice'];
    if (!in_array($nameloc['Status'], ['active', 'end_of_time', 'end_of_volume', 'sendedwarn', 'send_on_hold'])) {
        sendmessage($from_id, $textbotlang['users']['status']['infoUnavailable'], $keyboard, 'html');
        step('home', $from_id);
        return;
    }
    $marzban = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban['name_panel'] != null) {
        update("user", "Processing_value_four", $marzban['name_panel'], "id", $from_id);
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
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
        $lastonline = $textbotlang['common']['connection']['onlineAlt'];
    } elseif ($DataUserOut['online_at'] == "offline") {
        $lastonline = $textbotlang['common']['connection']['offlineAlt'];
    } else {
        if (isset($DataUserOut['online_at']) && $DataUserOut['online_at'] !== null) {
            $dateTime = new DateTime($DataUserOut['online_at'], new DateTimeZone('UTC'));
            $dateTime->setTimezone(new DateTimeZone('Asia/Tehran'));
            $lastonline = jdate('Y/m/d H:i:s', $dateTime->getTimestamp());
        } else {
            $lastonline = $textbotlang['common']['connection']['notConnectedAlt'];
        }
    }
    #-------------status----------------#
    $status = $DataUserOut['status'] ?? 'Unknown';
    $status_var = [
        'active' => $textbotlang['users']['status']['active'],
        'limited' => $textbotlang['users']['status']['limited'],
        'disabled' => $textbotlang['users']['status']['disabled'],
        'expired' => $textbotlang['users']['status']['expired'],
        'on_hold' => $textbotlang['users']['status']['on_hold'],
        'Unknown' => $textbotlang['users']['status']['unknown'],
        'deactivev' => $textbotlang['users']['status']['disabled'],
    ][$status] ?? $textbotlang['users']['status']['unknown'];
    #--------------[ expire ]---------------#
    $expirationDate = $DataUserOut['expire'] ? jdate('Y/m/d', $DataUserOut['expire']) : $textbotlang['users']['status']['unlimited'];
    #-------------[ data_limit ]----------------#
    $LastTraffic = $DataUserOut['data_limit'] ? formatBytes($DataUserOut['data_limit']) : $textbotlang['users']['status']['unlimited'];
    #---------------[ RemainingVolume ]--------------#
    $output = $DataUserOut['data_limit'] - $DataUserOut['used_traffic'];
    $RemainingVolume = $DataUserOut['data_limit'] ? formatBytes($output) : $textbotlang['common']['labels']['unlimitedShort'];
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
        $day .= $textbotlang['common']['labels']['remainingSuffix'];
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
    $keyboardsetting = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backlist'], 'callback_data' => 'backorder'],
            ]
        ]
    ]);
    if ($marzban['type'] == "ibsng" || $marzban['type'] == "mikrotik") {
        $userpassword = strtr($textbotlang['users']['status']['servicePassword'], ['{subscription_url}' => $DataUserOut['subscription_url']]);
    } else {
        $userpassword = "";
    }
    if ($marzban['type'] == "Manualsale") {
        $userinfo = select("manualsell", "*", "username", $nameloc['username'], "select");
        $textinfo = sprintf($textbotlang['users']['status']['infoBasic'], $status_var, $DataUserOut['username'], $nameloc['id_invoice'], $userinfo['contentrecord']);
        if ($user['step'] == "getuseragnetservice") {
            sendmessage($from_id, $textinfo, $keyboardsetting, 'html');
        } elseif ($datain == "productcheckdata") {
            deletemessage($from_id, $message_id);
            sendmessage($from_id, $textinfo, $keyboardsetting, 'html');
        } else {
            Editmessagetext($from_id, $message_id, $textinfo, $keyboardsetting);
        }
        return;
    }
    $nameconfig = "";
    if ($nameloc['note'] != null) {
        $nameconfig = strtr($textbotlang['users']['status']['configNote'], ['{note}' => $nameloc['note']]);
    }
    $stmt = $pdo->prepare("SELECT value FROM service_other WHERE username = :username AND type = 'extend_user' AND status = 'paid' ORDER BY time DESC");
    $stmt->execute([
        ':username' => $nameloc['username'],
    ]);
    if ($stmt->rowCount() != 0) {
        $service_other = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!($service_other == false || !(is_string($service_other['value']) && is_array(json_decode($service_other['value'], true))))) {
            $service_other = json_decode($service_other['value'], true);
            $codeproduct = select("product", "*", "code_product", $service_other['code_product'], "select");
            if ($codeproduct != false) {
                $nameloc['name_product'] = $codeproduct['name_product'];
                $nameloc['Volume'] = $codeproduct['Volume_constraint'];
                $nameloc['Service_time'] = $codeproduct['Service_time'];
            }
        }
    }
    #-----------------------------#
    $statustimeextra = select("shopSetting", "*", "Namevalue", "statustimeextra", "select")['value'];
    $marzbanstatusextra = select("shopSetting", "*", "Namevalue", "statusextra", "select")['value'];
    $statusdisorder = select("shopSetting", "*", "Namevalue", "statusdisorder", "select")['value'];
    $statuschangeservice = select("shopSetting", "*", "Namevalue", "statuschangeservice", "select")['value'];
    $statusshowconfig = select("shopSetting", "*", "Namevalue", "configshow", "select")['value'];
    $statusremoveserveice = select("shopSetting", "*", "Namevalue", "backserviecstatus", "select")['value'];
    if (!in_array($status, ["active", "on_hold", "disabled", "Unknown"])) {
        $textinfo = sprintf($textbotlang['users']['status']['infoDetailed'], $status_var, $DataUserOut['username'], $nameloc['Service_location'], $nameloc['name_product'], $lastonline, $LastTraffic, $usedTrafficGb, $RemainingVolume, $Percent, $expirationDate, $day, $nameconfig);

        $keyboardsetting = [
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['extend']['title'], 'callback_data' => 'extend_' . $username],
                    ['text' => $textbotlang['users']['extraVolume']['sellextra'], 'callback_data' => 'Extra_volume_' . $username],
                ],
                [
                    ['text' => $textbotlang['keyboard']['deleteService'], 'callback_data' => 'removeauto-' . $username],
                    ['text' => $textbotlang['users']['extraTime']['title'], 'callback_data' => 'Extra_time_' . $username],
                ],
                [
                    ['text' => $textbotlang['users']['status']['backlist'], 'callback_data' => 'backorder'],
                ]
            ]
        ];
        if ($marzban['type'] == "ibsng" || $marzban['type'] == "mikrotik") {
            unset($keyboardsetting['inline_keyboard'][1][1]);
            unset($keyboardsetting['inline_keyboard'][0]);
        }
        if ($statustimeextra == "offtimeextraa")
            unset($keyboardsetting['inline_keyboard'][1][1]);
        if ($marzbanstatusextra == "offextra")
            unset($keyboardsetting['inline_keyboard'][0][1]);
        $keyboardsetting['inline_keyboard'] = array_values($keyboardsetting['inline_keyboard']);
        $keyboardsetting = json_encode($keyboardsetting);
    } else {
        $marzbancount = select("marzban_panel", "*", "status", "active", "count");
        if ($DataUserOut['status'] == "active") {
            $namestatus = $textbotlang['users']['status']['btnTurnOff'];
        } else {
            $namestatus = $textbotlang['users']['status']['btnTurnOn'];
        }
        $keyboarddate = array(
            'updateinfo' => array(
                'text' => $textbotlang['keyboard']['refreshInfo'],
                'callback_data' => "updateproduct_"
            ),
            'linksub' => array(
                'text' => $textbotlang['users']['status']['linksub'],
                'callback_data' => "subscriptionurl_"
            ),
            'config' => array(
                'text' => $textbotlang['users']['status']['config'],
                'callback_data' => "config_"
            ),
            'extend' => array(
                'text' => $textbotlang['users']['extend']['title'],
                'callback_data' => "extend_"
            ),
            'changelink' => array(
                'text' => $textbotlang['users']['changeLink']['btnTitle'],
                'callback_data' => "changelink_"
            ),
            'removeservice' => array(
                'text' => $textbotlang['users']['status']['removeservice'],
                'callback_data' => "removeserviceuser_"
            ),
            'changenameconfig' => array(
                'text' => $textbotlang['users']['status']['btnEditNote'],
                'callback_data' => "changenote_"
            ),
            'Extra_volume' => array(
                'text' => $textbotlang['users']['extraVolume']['sellextra'],
                'callback_data' => "Extra_volume_"
            ),
            'Extra_time' => array(
                'text' => $textbotlang['users']['extraTime']['title'],
                'callback_data' => "Extra_time_"
            ),
            'changestatus' => array(
                'text' => $namestatus,
                'callback_data' => "changestatus_"
            ),
            'transfor' => array(
                'text' => $textbotlang['users']['transfer']['title'],
                'callback_data' => "transfer_"
            ),
            'change-location' => array(
                'text' => $textbotlang['users']['changeLocation']['title'],
                'callback_data' => "changeloc_"
            ),
            'ekhtelal' => array(
                'text' => $textbotlang['keyboard']['sendDisruptionReport'],
                'callback_data' => "disorder-"
            )
        );
        if ($nameloc['name_product'] == $textbotlang['common']['labels']['testService1']) {
            unset($keyboarddate['transfor']);
            unset($keyboarddate['Extra_time']);
            unset($keyboarddate['removeservice']);
        }
        if ($marzban['type'] == "ibsng" || $marzban['type'] == "mikrotik") {
            unset($keyboarddate['linksub']);
            unset($keyboarddate['config']);
            unset($keyboarddate['extend']);
            unset($keyboarddate['changestatus']);
            unset($keyboarddate['change-location']);
            unset($keyboarddate['changelink']);
            unset($keyboarddate['Extra_volume']);
            unset($keyboarddate['Extra_time']);
        }
        if ($marzban['type'] == "WGDashboard") {
            unset($keyboarddate['config']);
            unset($keyboarddate['changestatus']);
            unset($keyboarddate['change-location']);
            unset($keyboarddate['changelink']);
        }
        if ($marzban['status_extend'] == "off_extend") {
            unset($keyboarddate['Extra_time']);
            unset($keyboarddate['Extra_volume']);
            unset($keyboarddate['extend']);
        }
        if ($statusremoveserveice == "off")
            unset($keyboarddate['removeservice']);
        if ($statusshowconfig == "offconfig")
            unset($keyboarddate['config']);
        if ($marzban['type'] == "hiddify") {
            unset($keyboarddate['changelink']);
            unset($keyboarddate['changestatus']);
            unset($keyboarddate['config']);
        }
        if ($statusdisorder == "offdisorder")
            unset($keyboarddate['ekhtelal']);
        if ($nameloc['Service_time'] == "0")
            unset($keyboarddate['Extra_time']);
        if ($nameloc['Volume'] == "0") {
            unset($keyboarddate['Extra_volume']);
            unset($keyboarddate['Extra_time']);
        }
        if ($statuschangeservice == "offstatus")
            unset($keyboarddate['changestatus']);
        if ($setting['statusnamecustom'] == 'offnamecustom')
            unset($keyboarddate['changenameconfig']);
        if ($marzbancount == 1)
            unset($keyboarddate['change-location']);
        if ($marzban['changeloc'] == "offchangeloc")
            unset($keyboarddate['change-location']);
        if ($statustimeextra == "offtimeextraa")
            unset($keyboarddate['Extra_time']);
        if ($marzbanstatusextra == "offextra")
            unset($keyboarddate['Extra_volume']);
        $tempArray = [];
        $keyboardsetting = ['inline_keyboard' => []];
        foreach ($keyboarddate as $keyboardtext) {
            $tempArray[] = ['text' => $keyboardtext['text'], 'callback_data' => $keyboardtext['callback_data'] . $username];
            if (count($tempArray) == 2 or $keyboardtext['text'] == $textbotlang['users']['status']['btnRefresh']) {
                $keyboardsetting['inline_keyboard'][] = $tempArray;
                $tempArray = [];
            }
        }
        if (count($tempArray) > 0) {
            $keyboardsetting['inline_keyboard'][] = $tempArray;
        }
        $keyboardsetting['inline_keyboard'][] = [['text' => $textbotlang['users']['status']['backlist'], 'callback_data' => 'backorder']];
        $keyboardsetting = json_encode($keyboardsetting);
        if ($DataUserOut['sub_updated_at'] !== null) {
            $textconnect = sprintf($textbotlang['users']['status']['connectionInfo'], $lastonline, $lastupdate, $DataUserOut['sub_last_user_agent']);
        } elseif ($marzban['type'] == "WGDashboard") {
            $textconnect = "";
        } else {
            $textconnect = strtr($textbotlang['users']['status']['lastOnline'], ['{lastonline}' => $lastonline]);
        }
        $textinfo = sprintf($textbotlang['users']['status']['infoFull'], $status_var, $DataUserOut['username'], $userpassword, $nameconfig, $nameloc['Service_location'], $nameloc['name_product'], $LastTraffic, $usedTrafficGb, $RemainingVolume, $Percent, $expirationDate, $day, $textconnect);
    }
    if ($user['step'] == "getuseragnetservice") {
        sendmessage($from_id, $textinfo, $keyboardsetting, 'html');
    } elseif ($datain == "productcheckdata") {
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textinfo, $keyboardsetting, 'html');
    } else {
        Editmessagetext($from_id, $message_id, $textinfo, $keyboardsetting);
    }
    step('home', $from_id);
    return;
} elseif (preg_match('/subscriptionurl_(\w+)/', $datain, $dataget) || strpos($text, "/sub ") !== false) {
    if (!empty($text) && $text[0] == "/") {
        $id_invoice = explode(' ', $text)[1];
        $nameloc = select("invoice", "*", "username", $id_invoice, "select");
        if (!$nameloc || $nameloc['id_user'] != $from_id) {
            $nameloc = false;
        }
    } else {
        $id_invoice = $dataget[1];
        $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
        if ($nameloc && $nameloc['id_user'] != $from_id) {
            $nameloc = false;
        }
    }
    if ($nameloc == false)
        return;
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        return;
    }
    $subscriptionurl = $DataUserOut['subscription_url'];
    if ($marzban_list_get['type'] == "WGDashboard") {
        $textsub = $textbotlang['users']['status']['subscriptionFile'];
        $bakinfos = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "productcheckdata"],
                ]
            ]
        ]);
        update("user", "Processing_value", $nameloc['username'], "id", $from_id);
        $subscriptionurl = $DataUserOut['subscription_url'];
        $urlimage = qrTempPath("{$marzban_list_get['inboundid']}_{$nameloc['username']}.conf");
        file_put_contents($urlimage, $subscriptionurl);
        telegram('senddocument', [
            'chat_id' => $from_id,
            'document' => new CURLFile($urlimage),
            'reply_markup' => $bakinfos,
            'caption' => $textsub,
            'parse_mode' => "HTML",
        ]);
        unlink($urlimage);
    } else {
        $textsub = "
{$textbotlang['users']['status']['linksub']}
           
<code>$subscriptionurl</code>";
        $bakinfos = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "productcheckdata"],
                ]
            ]
        ]);
        update("user", "Processing_value", $nameloc['username'], "id", $from_id);
        $subscriptionurl = $DataUserOut['subscription_url'];
        $randomString = bin2hex(random_bytes(3));
        $urlimage = qrTempPath("$from_id$randomString.png");
        $qrCode = createqrcode($subscriptionurl);
        if ($qrCode === null || @file_put_contents($urlimage, $qrCode->getString()) === false) {
            sendmessage($from_id, $textsub, $bakinfos, 'HTML');
            return;
        }
        addBackgroundImage($urlimage, $qrCode, 'images.jpg');
        telegram('sendphoto', [
            'chat_id' => $from_id,
            'photo' => new CURLFile($urlimage),
            'reply_markup' => $bakinfos,
            'caption' => $textsub,
            'parse_mode' => "HTML",
        ]);
        @unlink($urlimage);
    }
} elseif (preg_match('/removeauto-(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if (!$nameloc || $nameloc['id_user'] != $from_id) {
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $ManagePanel->RemoveUser($nameloc['Service_location'], $nameloc['username']);
    update('invoice', 'status', 'removebyuser', 'id_invoice', $id_invoice);
    $tetremove = sprintf($textbotlang['Admin']['reportgroup']['userDeletedService'], $nameloc['username']);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherreport,
            'text' => $tetremove,
            'parse_mode' => "HTML"
        ]);
    }
    sendmessage($from_id, $textbotlang['users']['status']['deletedSuccess'], null, 'html');
} elseif (preg_match('/config_(\w+)/', $datain, $dataget) || strpos($text, "/link ") !== false) {
    if (!empty($text) && $text[0] == "/") {
        $id_invoice = explode(' ', $text)[1];
        $nameloc = select("invoice", "*", "username", $id_invoice, "select");
        if (!$nameloc || $nameloc['id_user'] != $from_id) {
            $nameloc = false;
        }
    } else {
        $id_invoice = $dataget[1];
        $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
        if ($nameloc && $nameloc['id_user'] != $from_id) {
            $nameloc = false;
        }
    }
    if ($nameloc == false) {
        sendmessage($from_id, $textbotlang['users']['status']['userNotFound'], null, 'html');
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        return;
    }
    if (!is_array($DataUserOut['links'])) {
        sendmessage($from_id, $textbotlang['users']['status']['configReadError'], null, 'html');
        return;
    }
    Editmessagetext($from_id, $message_id, $textbotlang['users']['status']['selectConfig'], keyboard_config($DataUserOut['links'], $nameloc['id_invoice']));
} elseif (preg_match('/configget_(.*)_(.*)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if ($nameloc && $nameloc['id_user'] != $from_id) {
        $nameloc = false;
    }
    if ($nameloc == false) {
        sendmessage($from_id, $textbotlang['users']['status']['userNotFound'], null, 'html');
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "productcheckdata"],
            ]
        ]
    ]);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        return;
    }
    $config = "";
    if ($dataget[2] == "1520") {
        for ($i = 0; $i < count($DataUserOut['links']); ++$i) {
            $randomString = bin2hex(random_bytes(3));
            $urlimage = qrTempPath("$from_id$randomString.png");
            $qrCode = createqrcode($DataUserOut['links'][$i]);
            if ($qrCode === null || @file_put_contents($urlimage, $qrCode->getString()) === false) {
                sendmessage($from_id, "<code>{$DataUserOut['links'][$i]}</code>", null, 'HTML');
                continue;
            }
            addBackgroundImage($urlimage, $qrCode, 'images.jpg');
            telegram('sendphoto', [
                'chat_id' => $from_id,
                'photo' => new CURLFile($urlimage),
                'caption' => "<code>{$DataUserOut['links'][$i]}</code>",
                'parse_mode' => "HTML",
            ]);
            @unlink($urlimage);
        }
        return;
    }
    $randomString = bin2hex(random_bytes(3));
    $urlimage = qrTempPath("$from_id$randomString.png");
    $qrCode = createqrcode($DataUserOut['links'][$dataget[2]]);
    if ($qrCode === null || @file_put_contents($urlimage, $qrCode->getString()) === false) {
        sendmessage($from_id, "<code>{$DataUserOut['links'][$dataget[2]]}</code>", null, 'HTML');
        return;
    }
    addBackgroundImage($urlimage, $qrCode, 'images.jpg');
    telegram('sendphoto', [
        'chat_id' => $from_id,
        'photo' => new CURLFile($urlimage),
        'caption' => "<code>{$DataUserOut['links'][$dataget[2]]}</code>",
        'parse_mode' => "HTML",
    ]);
    @unlink($urlimage);
} elseif (preg_match('/changestatus_(\w+)/', $datain, $dataget)) {
    $statuschangeservice = select("shopSetting", "*", "Namevalue", "statuschangeservice", "select")['value'];
    if ($statuschangeservice == "offstatus") {
        sendmessage($from_id, $textbotlang['users']['featureUnavailable'], null, 'html');
        return;
    }
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if (!$nameloc || $nameloc['id_user'] != $from_id) {
        return;
    }
    if ($nameloc['Status'] == "disablebyadmin") {
        sendmessage($from_id, $textbotlang['users']['featureUnavailable'], null, 'html');
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "on_hold") {
        sendmessage($from_id, $textbotlang['users']['status']['notConnectedCannotChangeStatus'], null, 'html');
        return;
    }
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        return;
    }
    if ($DataUserOut['status'] == "active") {
        $confirmdisableaccount = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['status']['btnConfirmDisableAlt'], 'callback_data' => "confirmaccountdisable_" . $id_invoice],
                ],
                [
                    ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
                ]
            ]
        ]);
        Editmessagetext($from_id, $message_id, $textbotlang['users']['status']['confirmDisableConfig'], $confirmdisableaccount);
    } else {
        $confirmdisableaccount = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['status']['btnConfirmEnableAlt'], 'callback_data' => "confirmaccountdisable_" . $id_invoice],
                ],
                [
                    ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
                ]
            ]
        ]);
        Editmessagetext($from_id, $message_id, $textbotlang['users']['status']['confirmEnableConfig'], $confirmdisableaccount);
    }
} elseif (preg_match('/confirmaccountdisable_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if (!$nameloc || $nameloc['id_user'] != $from_id) {
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    $dataoutput = $ManagePanel->Change_status($nameloc['username'], $nameloc['Service_location']);
    if ($dataoutput['status'] == "Unsuccessful") {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['status']['notchanged'], $bakinfos);
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "active") {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['status']['activedconfig'], $bakinfos);
    } else {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['status']['disabledconfig'], $bakinfos);
    }
} elseif (preg_match('/extend_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if ($nameloc == false || !invoiceBelongsToUser($nameloc, $from_id)) {
        sendmessage($from_id, $textbotlang['users']['extend']['error'], null, 'HTML');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban_list_get['status_extend'] == "off_extend") {
        sendmessage($from_id, $textbotlang['users']['extend']['notSupportedPanel'], null, 'html');
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        return;
    }
    if ($DataUserOut['status'] == "on_hold") {
        sendmessage($from_id, $textbotlang['users']['extend']['connectFirst'], null, 'html');
        return;
    }
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
    $mainvolume = $mainvolume[$user['agent']];
    $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
    $maxvolume = $maxvolume[$user['agent']];
    $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :service_location OR Location = '/all') AND agent = :agent AND one_buy_status = '0'");
    $stmt->execute([
        ':service_location' => $marzban_list_get['name_panel'],
        ':agent' => $user['agent'],
    ]);
    $product = $stmt->rowCount();
    savedata("clear", "id_invoice", $nameloc['id_invoice']);
    if ($product == 0) {
        $textcustom = sprintf($textbotlang['users']['sell']['customVolumePrompt'], $custompricevalue, $mainvolume, $maxvolume);
        sendmessage($from_id, $textcustom, $backuser, 'html');
        deletemessage($from_id, $message_id);
        step('gettimecustomvolomforextend', $from_id);
        return;
    }
    if ($nameloc['name_product'] == $textbotlang['users']['customSellVolume']['btnVolume'] || $nameloc['name_product'] == $textbotlang['users']['customSellVolume']['btnService']) {
        $textcustom = sprintf($textbotlang['users']['sell']['customVolumePrompt2'], $custompricevalue, $mainvolume, $maxvolume);
        sendmessage($from_id, $textcustom, $backuser, 'html');
        deletemessage($from_id, $message_id);
        step('gettimecustomvolomforextend', $from_id);
        return;
    }
    if ($setting['statuscategory'] == "offcategory") {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :service_location OR Location = '/all') AND agent = :agent AND one_buy_status = '0'");
        $stmt->execute([
            ':service_location' => $nameloc['Service_location'],
            ':agent' => $user['agent'],
        ]);
        $productextend = ['inline_keyboard' => []];
        $statusshowprice = select("shopSetting", "*", "Namevalue", "statusshowprice", "select")['value'];
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $hide_panel = json_decode($result['hide_panel'], true);
            if (in_array($nameloc['Service_location'], $hide_panel))
                continue;
            if (intval($user['pricediscount']) != 0) {
                $resultper = ($result['price_product'] * $user['pricediscount']) / 100;
                $result['price_product'] = $result['price_product'] - $resultper;
            }
            if ($statusshowprice == "offshowprice") {
                $namekeyboard = $result['name_product'];
            } else {
                $result['price_product'] = number_format($result['price_product']);
                $namekeyboard = $result['name_product'] . " - " . $result['price_product'] . $textbotlang['common']['labels']['tomanUnit'];
            }
            $productextend['inline_keyboard'][] = [
                ['text' => $namekeyboard, 'callback_data' => "serviceextendselect_" . $result['code_product']]
            ];
        }
        $productextend['inline_keyboard'][] = [
            ['text' => $textbotlang['keyboard']['renewCurrentPlan'], 'callback_data' => "exntedagei"]
        ];
        $productextend['inline_keyboard'][] = [
            ['text' => $textbotlang['keyboard']['backToServiceInfo'], 'callback_data' => "product_" . $nameloc['id_invoice']]
        ];

        $json_list_product_lists = json_encode($productextend);
        Editmessagetext($from_id, $message_id, $textbotlang['users']['extend']['selectservice'], $json_list_product_lists);
    } else {
        $monthkeyboard = keyboardTimeCategory($nameloc['Service_location'], $user['agent'], "productextendmonths_", "product_$id_invoice", false, true);
        Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['selectDuration'], $monthkeyboard);
    }
} elseif ($user['step'] == "gettimecustomvolomforextend") {
    $userdate = json_decode($user['Processing_value'], true);
    $nameloc = select("invoice", "*", "id_invoice", $userdate['id_invoice'], "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
    $mainvolume = $mainvolume[$user['agent']];
    $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
    $maxvolume = $maxvolume[$user['agent']];
    $maintime = json_decode($marzban_list_get['maintime'], true);
    $maintime = $maintime[$user['agent']];
    $maxtime = json_decode($marzban_list_get['maxtime'], true);
    $maxtime = $maxtime[$user['agent']];
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidVolume'], $backuser, 'HTML');
        return;
    }
    if ($text > intval($maxvolume) || $text < intval($mainvolume)) {
        $texttime = strtr($textbotlang['users']['customSellVolume']['invalidVolume'], ['{mainvolume}' => $mainvolume, '{maxvolume}' => $maxvolume]);
        sendmessage($from_id, $texttime, $backuser, 'HTML');
        return;
    }
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    savedata("save", "volume", $text);
    $textcustom = sprintf($textbotlang['users']['sell']['customTimePrompt'], $customtimevalueprice, $maintime, $maxtime);
    sendmessage($from_id, $textcustom, $backuser, 'html');
    step('getvolumecustomuserforextend', $from_id);
} elseif (preg_match('/productextendmonths_(\w+)/', $datain, $dataget)) {
    $monthenumber = $dataget[1];
    $userdate = json_decode($user['Processing_value'], true);
    $nameloc = select("invoice", "*", "id_invoice", $userdate['id_invoice'], "select");
    $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :service_location OR Location = '/all') AND agent = :agent AND Service_time = :monthe AND one_buy_status = '0'");
    $stmt->execute([
        ':service_location' => $nameloc['Service_location'],
        ':agent' => $user['agent'],
        'monthe' => $monthenumber
    ]);
    $productextend = ['inline_keyboard' => []];
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $statusshowprice = select("shopSetting", "*", "Namevalue", "statusshowprice", "select")['value'];
    while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (intval($user['pricediscount']) != 0) {
            $resultper = ($result['price_product'] * $user['pricediscount']) / 100;
            $result['price_product'] = $result['price_product'] - $resultper;
        }
        if ($statusshowprice == "offshowprice") {
            $namekeyboard = $result['name_product'];
        } else {
            $result['price_product'] = number_format($result['price_product']);
            $namekeyboard = $result['name_product'] . " - " . $result['price_product'] . $textbotlang['common']['labels']['tomanUnit'];
        }
        $productextend['inline_keyboard'][] = [
            ['text' => $namekeyboard, 'callback_data' => "serviceextendselect_" . $result['code_product']]
        ];
    }
    if ($nameloc['name_product'] == $textbotlang['users']['customSellVolume']['btnVolume'] || $nameloc['name_product'] == $textbotlang['users']['customSellVolume']['btnService']) {
        $productextend['inline_keyboard'][] = [
            ['text' => $textbotlang['keyboard']['selectCurrentService'], 'callback_data' => "serviceextendselect_pre"]
        ];
    }
    $productextend['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['backToServiceInfo'], 'callback_data' => "product_" . $nameloc['id_invoice']]
    ];

    $json_list_product_lists = json_encode($productextend);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['extend']['selectservice'], $json_list_product_lists);
} elseif (preg_match('/^serviceextendselect_(.*)/', $datain, $dataget) || $user['step'] == "getvolumecustomuserforextend" || $datain == "exntedagei") {
    $userdate = json_decode($user['Processing_value'], true);
    $nameloc = select("invoice", "*", "id_invoice", $userdate['id_invoice'], "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($user['step'] == "getvolumecustomuserforextend") {
        if (!ctype_digit($text)) {
            sendmessage($from_id, $textbotlang['common']['invalidTime'], $backuser, 'HTML');
            return;
        }
        $maintime = json_decode($marzban_list_get['maintime'], true);
        $maintime = $maintime[$user['agent']];
        $maxtime = json_decode($marzban_list_get['maxtime'], true);
        $maxtime = $maxtime[$user['agent']];
        if (intval($text) > intval($maxtime) || intval($text) < intval($maintime)) {
            $texttime = strtr($textbotlang['users']['customSellVolume']['invalidTimeRange'], ['{maintime}' => $maintime, '{maxtime}' => $maxtime]);
            sendmessage($from_id, $texttime, $backuser, 'HTML');
            return;
        }
    } elseif ($datain == "exntedagei") {
        $stmt = $pdo->prepare("SELECT value FROM service_other WHERE username = :username AND type = 'extend_user' AND status = 'paid' ORDER BY time DESC");
        $stmt->execute([
            ':username' => $nameloc['username'],
        ]);
        if ($stmt->rowCount() == 0) {
            $codeproduct = select("product", "*", "name_product", $nameloc['name_product']);
        } else {
            $service_other = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($service_other == false || !(is_string($service_other['value']) && is_array(json_decode($service_other['value'], true)))) {
                sendmessage($from_id, $textbotlang['users']['extend']['planNotAvailable'], $keyboard, 'HTML');
                return;
            }
            $service_other = json_decode($service_other['value'], true);
            $codeproduct = select("product", "code_product", "code_product", $service_other['code_product'], "select");
        }
        if ($codeproduct == false) {
            sendmessage($from_id, $textbotlang['users']['extend']['planNotAvailable'], $keyboard, 'HTML');
            return;
        }
        $codeproduct = $codeproduct['code_product'];
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    } else {
        $codeproduct = $dataget[1];
    }
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    if ($user['step'] == "getvolumecustomuserforextend") {
        $product['name_product'] = $nameloc['name_product'];
        $product['code_product'] = "customvolume";
        $product['note'] = "";
        $product['price_product'] = (intval($userdate['volume']) * $custompricevalue) + ($text * $customtimevalueprice);
        $product['Service_time'] = $text;
        $product['Volume_constraint'] = $userdate['volume'];
        step("home", $from_id);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :service_location OR Location = '/all') AND agent = :agent AND code_product = :code_product");
        $stmt->execute([
            ':service_location' => $nameloc['Service_location'],
            ':agent' => $user['agent'],
            ':code_product' => $codeproduct,
        ]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if ($product == false) {
        sendmessage($from_id, $textbotlang['users']['extend']['restartError'], $keyboard, 'HTML');
        return;
    }
    savedata("save", "time", $product['Service_time']);
    savedata("save", "data_limit", $product['Volume_constraint']);
    savedata("save", "price_product", $product['price_product']);
    savedata("save", "code_product", $product['code_product']);
    $keyboardextend = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['extend']['confirm'], 'callback_data' => "confirmserivce"],
                ['text' => $textbotlang['users']['extend']['discount'], 'callback_data' => "discountextend"],
            ],
            [
                ['text' => $textbotlang['users']['backbtn'], 'callback_data' => "backuser"]
            ]
        ]
    ]);
    if (intval($user['pricediscount']) != 0) {
        $result = ($product['price_product'] * $user['pricediscount']) / 100;
        $pricelastextend = number_format(round($product['price_product'] - $result, 0));
    } else {
        $pricelastextend = $product['price_product'];
    }
    $textextend = sprintf($textbotlang['users']['extend']['invoiceCreated'], $nameloc['username'], $product['name_product'], $pricelastextend, $product['Service_time'], $product['Volume_constraint'], $product['note'], $user['Balance']);
    if ($user['step'] == "getvolumecustomuserforextend") {
        sendmessage($from_id, $textextend, $keyboardextend, 'HTML');
    } else {
        Editmessagetext($from_id, $message_id, $textextend, $keyboardextend);
    }
} elseif ($datain == "discountextend") {
    sendmessage($from_id, $textbotlang['users']['Discount']['getcodesell'], $backuser, 'HTML');
    step('getcodesellDiscountextend', $from_id);
    deletemessage($from_id, $message_id);
} elseif ($user['step'] == "getcodesellDiscountextend") {
    $userdate = json_decode($user['Processing_value'], true);
    $nameloc = select("invoice", "*", "id_invoice", $userdate['id_invoice'], "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if (!rowExists("DiscountSell", "codeDiscount", $text)) {
        sendmessage($from_id, $textbotlang['users']['Discount']['notcode'], $backuser, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM DiscountSell WHERE (code_product = :code_product OR code_product = 'all') AND (code_panel = :code_panel OR code_panel = '/all') AND codeDiscount = :codeDiscount AND (agent = :agent OR agent = 'allusers') AND (type = 'all' OR type = 'extend')");
    $stmt->bindParam(':code_product', $userdate['code_product'], PDO::PARAM_STR);
    $stmt->bindParam(':code_panel', $marzban_list_get['code_panel'], PDO::PARAM_STR);
    $stmt->bindParam(':agent', $user['agent'], PDO::PARAM_STR);
    $stmt->bindParam(':codeDiscount', $text, PDO::PARAM_STR);
    $stmt->execute();
    $SellDiscountlimit = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT * FROM Giftcodeconsumed WHERE id_user = :from_id AND code = :code");
    $stmt->bindParam(':from_id', $from_id, PDO::PARAM_STR);
    $stmt->bindParam(':code', $text, PDO::PARAM_STR);
    $stmt->execute();
    $Checkcodesql = $stmt->rowCount();
    if (intval($SellDiscountlimit['time']) != 0 and time() >= intval($SellDiscountlimit['time'])) {
        sendmessage($from_id, $textbotlang['users']['Discount']['expired'], null, 'HTML');
        return;
    }
    if ($SellDiscountlimit == 0) {
        sendmessage($from_id, $textbotlang['users']['Discount']['invalidCode'], null, 'HTML');
        return;
    }
    if (($SellDiscountlimit['limitDiscount'] <= $SellDiscountlimit['usedDiscount'])) {
        sendmessage($from_id, $textbotlang['users']['Discount']['errorLimit'], null, 'HTML');
        return;
    }
    if (intval($Checkcodesql) >= $SellDiscountlimit['useuser']) {
        $textoncode = strtr($textbotlang['users']['Discount']['useLimit'], ['{useuser}' => $SellDiscountlimit['useuser']]);
        sendmessage($from_id, $textoncode, $keyboard, 'HTML');
        step('home', $from_id);
        return;
    }
    if ($SellDiscountlimit['usefirst'] == "1") {
        $countinvoice = select("invoice", "*", "id_user", $from_id, "count");
        if ($countinvoice != 0) {
            sendmessage($from_id, $textbotlang['users']['Discount']['firstdiscount'], null, 'HTML');
            return;
        }
    }
    sendmessage($from_id, strtr($textbotlang['users']['Discount']['applied'], ['{discount_price}' => $SellDiscountlimit['price']]), $keyboard, 'HTML');
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    if ($nameloc['name_product'] == $textbotlang['users']['customSellVolume']['btnVolume'] || $nameloc['name_product'] == $textbotlang['users']['customSellVolume']['btnService']) {
        $info_product['code_product'] = "pre";
        $info_product['name_product'] = $nameloc['name_product'];
        $info_product['price_product'] = ($userdate['data_limit'] * $custompricevalue) + ($userdate['time'] * $customtimevalueprice);
        $info_product['Service_time'] = $userdate['time'];
        $info_product['Volume_constraint'] = $userdate['data_limit'];
    } else {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE code_product = :code_product AND (Location = :Location or Location = '/all') LIMIT 1");
        $stmt->bindParam(':code_product', $userdate['code_product'], PDO::PARAM_STR);
        $stmt->bindParam(':Location', $marzban_list_get['name_panel'], PDO::PARAM_STR);
        $stmt->execute();
        $info_product = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    $result = ($SellDiscountlimit['price'] / 100) * $info_product['price_product'];
    $info_product['price_product'] = $info_product['price_product'] - $result;
    $info_product['price_product'] = round($info_product['price_product']);
    if (intval($info_product['Service_time']) == 0)
        $info_product['Service_time'] = $textbotlang['users']['status']['unlimited'];
    if ($info_product['price_product'] < 0)
        $info_product['price_product'] = 0;
    $textextend = sprintf($textbotlang['users']['extend']['invoiceCreated2'], $nameloc['username'], $info_product['name_product'], $info_product['price_product'], $info_product['Service_time'], $info_product['Volume_constraint'], $info_product['note'], $user['Balance']);
    $keyboardextend = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['extend']['confirm'], 'callback_data' => "confirmserdiscount"],
            ]
        ]
    ]);
    sendmessage($from_id, $textextend, $keyboardextend, 'HTML');
    $parametrsendvalue = "dis_" . $text . "_" . $info_product['price_product'];
    update("user", "Processing_value_four", $parametrsendvalue, "id", $from_id);
    step("home", $from_id);
} elseif ($datain == "confirmserivce" || $datain == "confirmserdiscount") {
    Editmessagetext($from_id, $message_id, $text_inline, json_encode(['inline_keyboard' => []]));
    $partsdic = explode("_", $user['Processing_value_four']);
    $userdata = json_decode($user['Processing_value'], true);
    $id_invoice = $userdata['id_invoice'];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if ($nameloc == false) {
        sendmessage($from_id, $textbotlang['users']['extend']['error'], null, 'HTML');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban_list_get['status_extend'] == "off_extend") {
        sendmessage($from_id, $textbotlang['users']['extend']['notSupportedPanel'], null, 'html');
        return;
    }
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    $randomString = bin2hex(random_bytes(2));
    if ($nameloc['name_product'] == $textbotlang['users']['customSellVolume']['btnVolume'] || $nameloc['name_product'] == $textbotlang['users']['customSellVolume']['btnService']) {
        $prodcut['code_product'] = "custom_volume";
        $prodcut['name_product'] = $nameloc['name_product'];
        $prodcut['price_product'] = ($userdata['data_limit'] * $custompricevalue) + ($userdata['time'] * $customtimevalueprice);
        $prodcut['Service_time'] = $userdata['time'];
        $prodcut['Volume_constraint'] = $userdata['data_limit'];
        $prodcut['inbounds'] = $marzban_list_get['inboundid'];
    } else {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :service_location OR Location = '/all') AND agent = :agent AND code_product = :code_product");
        $stmt->execute([
            ':service_location' => $nameloc['Service_location'],
            ':agent' => $user['agent'],
            ':code_product' => $userdata['code_product'],
        ]);
        $prodcut = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    $pricelastextend = $prodcut['price_product'];
    if ($prodcut == false || !in_array($nameloc['Status'], ['active', 'end_of_time', 'end_of_volume', 'sendedwarn', 'send_on_hold'])) {
        sendmessage($from_id, $textbotlang['users']['extend']['error'], null, 'HTML');
        return;
    }
    if ($datain == "confirmserdiscount") {
        $SellDiscountlimit = select("DiscountSell", "*", "codeDiscount", $partsdic[1], "select");
        if ($SellDiscountlimit != false) {
            $pricelastextend = $partsdic[2];
        }
    }
    if (intval($user['pricediscount']) != 0) {
        $result = ($pricelastextend * $user['pricediscount']) / 100;
        $pricelastextend = $pricelastextend - $result;
        sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($user['Balance'] < $pricelastextend && $user['agent'] != "n2" && intval($pricelastextend) != 0) {
        $marzbandirectpay = select('shopSetting', "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        if ($marzbandirectpay == "offdirectbuy") {
            $minbalance = json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']];
            $maxbalance = json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']];
            $minbalance = number_format($minbalance);
            $maxbalance = number_format($maxbalance);
            $bakinfos = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "account"],
                    ]
                ]
            ]);
            Editmessagetext($from_id, $message_id, sprintf($textbotlang['users']['Balance']['insufficientbalance'], $minbalance, $maxbalance), $bakinfos, 'HTML');
            step('getprice', $from_id);
            return;
        } else {
            $Balance_prim = $pricelastextend - $user['Balance'];
            update("user", "Processing_value", $Balance_prim, "id", $from_id);
            sendmessage($from_id, $textbotlang['users']['sell']['noCredit'], $step_payment, 'HTML');
            step('get_step_payment', $from_id);
            $stmt = $pdo->prepare("INSERT IGNORE INTO service_other (id_user, username,value,type,time,price,output,status) VALUES (?, ?,?, ?, ?,?,?,?)");
            $dateacc = date('Y/m/d H:i:s');
            $value = json_encode(array(
                "volumebuy" => $prodcut['Volume_constraint'],
                "Service_time" => $prodcut['Service_time'],
                "oldvolume" => $DataUserOut['data_limit'],
                "oldtime" => $DataUserOut['expire'],
                'code_product' => $prodcut['code_product'],
                'id_order' => $randomString
            ));
            $type = "extend_user";
            $status = "unpaid";
            $extend = '';
            $stmt->execute([$from_id, $nameloc['username'], $value, $type, $dateacc, $prodcut['price_product'], $extend, $status]);
            update("user", "Processing_value_one", "{$nameloc['username']}%$randomString", "id", $from_id);
            update("user", "Processing_value_tow", "getextenduser", "id", $from_id);
            return;
        }
    }
    if ($datain == "confirmserdiscount") {
        $SellDiscountlimit = select("DiscountSell", "*", "codeDiscount", $partsdic[1], "select");
        if ($SellDiscountlimit != false) {
            $value = intval($SellDiscountlimit['usedDiscount']) + 1;
            update("DiscountSell", "usedDiscount", $value, "codeDiscount", $partsdic[1]);
            $stmt = $pdo->prepare("INSERT INTO Giftcodeconsumed (id_user,code) VALUES (?,?)");
            $stmt->execute([$from_id, $partsdic[1]]);
            $text_report = strtr($textbotlang['Admin']['reportgroup']['discountUsedRenew'], ['{username}' => $username, '{from_id}' => $from_id, '{discount_code}' => $partsdic[1]]);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $otherreport,
                    'text' => $text_report,
                    'parse_mode' => "HTML"
                ]);
            }
        }
    }
    if (intval($user['maxbuyagent']) != 0 and $user['agent'] == "n2") {
        if (($user['Balance'] - $pricelastextend) < intval("-" . $user['maxbuyagent'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['maxpurchasereached'], null, 'HTML');
            return;
        }
    }
    if ($nameloc['name_product'] == $textbotlang['common']['labels']['testService2']) {
        update("invoice", "name_product", $prodcut['name_product'], "id_invoice", $nameloc['id_invoice']);
        update("invoice", "price_product", $prodcut['price_product'], "id_invoice", $nameloc['id_invoice']);
    }
    $extend = $ManagePanel->extend($marzban_list_get['Methodextend'], $prodcut['Volume_constraint'], $prodcut['Service_time'], $nameloc['username'], $prodcut['code_product'], $marzban_list_get['code_panel']);
    if ($extend['status'] == false) {
        $extend['msg'] = json_encode($extend['msg']);
        $textreports = sprintf($textbotlang['Admin']['reportgroup']['errorRenewService'], $marzban_list_get['name_panel'], $nameloc['username'], $extend['msg']);
        sendmessage($from_id, $textbotlang['users']['extend']['errorSupport'], null, 'HTML');
        if (strlen($setting['Channel_Report']) > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $errorreport,
                'text' => $textreports,
                'parse_mode' => "HTML"
            ]);
        }
        return;
    }
    if ($user['agent'] == "f") {
        $valurcashbackextend = select("shopSetting", "*", "Namevalue", "chashbackextend", "select")['value'];
    } else {
        $valurcashbackextend = json_decode(select("shopSetting", "*", "Namevalue", "chashbackextend_agent", "select")['value'], true)[$user['agent']];
    }
    if (intval($valurcashbackextend) != 0 and intval($pricelastextend) != 0) {
        $result = ($pricelastextend * $valurcashbackextend) / 100;
        $pricelastextend = $pricelastextend - $result;
        if ($pricelastextend < 0) {
            $pricelastextend = 0;
        }
        sendmessage($from_id, sprintf($textbotlang['users']['extend']['giftCharged'], $result), null, 'HTML');
    }
    $Balance_Low_user = $user['Balance'] - $pricelastextend;
    update("user", "Balance", $Balance_Low_user, "id", $from_id);
    $stmt = $pdo->prepare("INSERT IGNORE INTO service_other (id_user, username,value,type,time,price,output,status) VALUES (?, ?, ?, ?,?,?,?,?)");
    $dateacc = date('Y/m/d H:i:s');
    $value = json_encode(array(
        "volumebuy" => $prodcut['Volume_constraint'],
        "Service_time" => $prodcut['Service_time'],
        "oldvolume" => $DataUserOut['data_limit'],
        "oldtime" => $DataUserOut['expire'],
        'code_product' => $prodcut['code_product'],
        'id_order' => $randomString
    ));
    $type = "extend_user";
    $status = "paid";
    $extend_json = json_encode($extend);
    $stmt->execute([$from_id, $nameloc['username'], $value, $type, $dateacc, $prodcut['price_product'], $extend_json, $status]);
    update("invoice", "Status", "active", "id_invoice", $id_invoice);
    if (intval($setting['scorestatus']) == 1 and !in_array($from_id, $admin_ids)) {
        sendmessage($from_id, $textbotlang['users']['affiliates']['pointsEarned2Alt'], null, 'html');
        $scorenew = $user['score'] + 2;
        update("user", "score", $scorenew, "id", $from_id);
    }
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
    $priceproductformat = number_format($pricelastextend);
    $balanceformatsell = number_format(select("user", "Balance", "id", $from_id, "select")['Balance'], 0);
    $balanceformatsellbefore = number_format($user['Balance'], 0);
    $textextend = sprintf($textbotlang['users']['extend']['success'], $nameloc['username'], $prodcut['name_product'], $priceproductformat);
    sendmessage($from_id, $textextend, $keyboardextendfnished, 'HTML');
    $timejalali = jdate('Y/m/d H:i:s');
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['manageUser']['manageUserBtn'], 'callback_data' => 'manageuser_' . $from_id],
            ],
        ]
    ]);
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['renewed'], $from_id, $username, $nameloc['username'], $first_name, $nameloc['Service_location'], $prodcut['name_product'], $prodcut['Volume_constraint'], $prodcut['Service_time'], $prodcut['price_product'], $balanceformatsellbefore, $balanceformatsell, $timejalali);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $text_report,
            'parse_mode' => "HTML",
            'reply_markup' => $Response
        ]);
    }
} elseif (preg_match('/changelink_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if (!invoiceBelongsToUser($nameloc, $from_id)) {
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        return;
    }
    if ($DataUserOut['status'] == "disabled" || $DataUserOut['status'] == "on_hold") {
        sendmessage($from_id, $textbotlang['users']['changeLink']['serviceInactive'], null, 'html');
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
    if (!invoiceBelongsToUser($nameloc, $from_id)) {
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->Revoke_sub($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['changeLink']['error'], null, 'HTML');
        return;
    }
    $textconfig = $textbotlang['users']['changeLink']['updated'];
    if ($marzban_list_get['sublink'] == "onsublink") {
        $output_config_link = $DataUserOut['subscription_url'];
        $textconfig .= strtr($textbotlang['users']['status']['subscriptionLine'], ['{output_config_link}' => $output_config_link]);
    }
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    if ($marzban_list_get['config'] == "onconfig") {
        Editmessagetext($from_id, $message_id, $textconfig, keyboard_config($DataUserOut['configs'], $nameloc['id_invoice'], true));
    } else {
        Editmessagetext($from_id, $message_id, $textconfig, $bakinfos);
    }
    $timejalali = jdate('Y/m/d H:i:s');
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['linkChanged'], $from_id, $username, $nameloc['username'], $first_name, $marzban_list_get['name_panel'], $user['agent'], $timejalali);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $text_report,
            'parse_mode' => "HTML",
        ]);
    }
} elseif (preg_match('/Extra_volume_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if (!invoiceBelongsToUser($nameloc, $from_id)) {
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban_list_get['status_extend'] == "off_extend") {
        sendmessage($from_id, $textbotlang['users']['extraVolume']['notSupportedPanel'], null, 'html');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        return;
    }
    $eextraprice = json_decode($marzban_list_get['priceextravolume'], true);
    $extrapricevalue = $eextraprice[$user['agent']];
    update("user", "Processing_value", $nameloc['id_invoice'], "id", $from_id);
    $textextra = sprintf($textbotlang['users']['extraVolume']['prompt'], $extrapricevalue);
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textextra, $bakinfos);
    step('getvolumeextra', $from_id);
} elseif ($user['step'] == "getvolumeextra") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidVolume'], $backuser, 'HTML');
        return;
    }
    if ($text < 1) {
        sendmessage($from_id, $textbotlang['users']['extraVolume']['invalidprice'], $backuser, 'HTML');
        return;
    }
    $nameloc = select("invoice", "*", "id_invoice", $user['Processing_value'], "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $eextraprice = json_decode($marzban_list_get['priceextravolume'], true);
    $extrapricevalue = $eextraprice[$user['agent']];
    $priceextra = $extrapricevalue * $text;
    $keyboardsetting = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['extraVolume']['extracheck'], 'callback_data' => 'confirmaextra-' . $extrapricevalue * $text],
            ]
        ]
    ]);
    $priceextra = number_format($priceextra, 0);
    $extrapricevalues = number_format($extrapricevalue, 0);
    $textextra = sprintf($textbotlang['users']['extraVolume']['invoiceCreated'], $extrapricevalues, $text, $priceextra);
    sendmessage($from_id, $textextra, $keyboardsetting, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/confirmaextra-(\w+)/', $datain, $dataget)) {
    $volume = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $user['Processing_value'], "select");
    if (!invoiceBelongsToUser($nameloc, $from_id)) {
        return;
    }
    if (!in_array($nameloc['Status'], ['active', 'end_of_time', 'end_of_volume', 'sendedwarn', 'send_on_hold'])) {
        sendmessage($from_id, $textbotlang['users']['sell']['purchaseError'], null, 'HTML');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $eextraprice = json_decode($marzban_list_get['priceextravolume'], true);
    $extrapricevalue = $eextraprice[$user['agent']];
    if ($user['Balance'] < $volume && $user['agent'] != "n2") {
        $marzbandirectpay = select('shopSetting', "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        if ($marzbandirectpay == "offdirectbuy") {
            $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']]);
            $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']]);
            $bakinfos = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "account"],
                    ]
                ]
            ]);
            Editmessagetext($from_id, $message_id, sprintf($textbotlang['users']['Balance']['insufficientbalance'], $minbalance, $maxbalance), $bakinfos, 'HTML');
            step('getprice', $from_id);
            return;
        } else {
            $valuevolume = intval($volume) / intval($extrapricevalue);
            if (intval($user['pricediscount']) != 0) {
                $result = ($volume * $user['pricediscount']) / 100;
                $volume = $volume - $result;
                sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
            }
            $Balance_prim = $volume - $user['Balance'];
            update("user", "Processing_value", $Balance_prim, "id", $from_id);
            Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['noCredit'], $step_payment);
            step('get_step_payment', $from_id);
            update("user", "Processing_value_one", "{$nameloc['username']}%{$valuevolume}", "id", $from_id);
            update("user", "Processing_value_tow", "getextravolumeuser", "id", $from_id);
            return;
        }
    }
    deletemessage($from_id, $message_id);
    $volumepricelast = $volume;
    if (intval($user['pricediscount']) != 0) {
        $result = ($volume * $user['pricediscount']) / 100;
        $volumepricelast = $volume - $result;
        sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
    }
    if (intval($user['maxbuyagent']) != 0 and $user['agent'] == "n2") {
        if (($user['Balance'] - $volumepricelast) < intval("-" . $user['maxbuyagent'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['maxpurchasereached'], null, 'HTML');
            return;
        }
    }
    $Balance_Low_user = $user['Balance'] - $volumepricelast;
    update("user", "Balance", $Balance_Low_user, "id", $from_id);
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    $data_for_database = json_encode(array(
        'volume_value' => intval($volume) / intval($extrapricevalue),
        'priceـper_gig' => $extrapricevalue,
        'old_volume' => $DataUserOut['data_limit'],
        'expire_old' => $DataUserOut['expire']
    ));
    $data_limit = intval($volume) / intval($extrapricevalue);
    $extra_volume = $ManagePanel->extra_volume($nameloc['username'], $marzban_list_get['code_panel'], $data_limit);
    if ($extra_volume['status'] == false) {
        $extra_volume['msg'] = json_encode($extra_volume['msg']);
        $textreports = sprintf($textbotlang['Admin']['reportgroup']['errorExtraVolume'], $marzban_list_get['name_panel'], $nameloc['username'], $extra_volume['msg']);
        sendmessage($from_id, $textbotlang['users']['extraVolume']['serviceError'], null, 'HTML');
        if (strlen($setting['Channel_Report']) > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $errorreport,
                'text' => $textreports,
                'parse_mode' => "HTML"
            ]);
        }
        return;
    }
    $stmt = $pdo->prepare("INSERT IGNORE INTO service_other (id_user, username, value, type, time, price, output) VALUES (:id_user, :username, :value, :type, :time, :price, :output)");
    $value = $data_for_database;
    $dateacc = date('Y/m/d H:i:s');
    $type = "extra_user";
    $stmt->execute([
        ':id_user' => $from_id,
        ':username' => $nameloc['username'],
        ':value' => $value,
        ':type' => $type,
        ':time' => $dateacc,
        ':price' => $volumepricelast,
        ':output' => json_encode($extra_volume),
    ]);
    $keyboardextrafnished = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backservice'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    if (intval($setting['scorestatus']) == 1 and !in_array($from_id, $admin_ids)) {
        sendmessage($from_id, $textbotlang['users']['affiliates']['pointsEarned1Alt'], null, 'html');
        $scorenew = $user['score'] + 1;
        update("user", "score", $scorenew, "id", $from_id);
    }
    $volumesformat = number_format($volumepricelast, 0);
    $volumes = $volume / $extrapricevalue;
    $textvolume = sprintf($textbotlang['users']['extraVolume']['success'], $nameloc['username'], $volumes, $volumesformat);
    sendmessage($from_id, $textvolume, $keyboardextrafnished, 'HTML');
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['extraVolume'], $from_id, $volumes, $volumesformat, $nameloc['username'], $user['Balance']);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
} elseif (preg_match('/changeloc_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $limitchangeloc = json_decode($setting['limitnumber'], true);
    if ($user['limitchangeloc'] > $limitchangeloc['all'] and intval($setting['statuslimitchangeloc']) == 1) {
        sendmessage($from_id, $textbotlang['users']['changeLocation']['limitReached'], null, 'html');
        return;
    }
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if (!invoiceBelongsToUser($nameloc, $from_id)) {
        return;
    }
    update("user", "Processing_value", $nameloc['id_invoice'], "id", $from_id);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban_list_get['changeloc'] == "offchangeloc") {
        sendmessage($from_id, $textbotlang['users']['featureUnavailable2'], null, 'html');
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful" || $DataUserOut['status'] == "disabled") {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        return;
    }
    Editmessagetext($from_id, $message_id, $textbotlang['textbot']['selectLocation'], $list_marzban_panel_userschange);
} elseif (preg_match('/changelocselectlo-(\w+)/', $datain, $dataget)) {
    update("user", "Processing_value_one", $dataget[1], "id", $from_id);
    $limitchangeloc = json_decode($setting['limitnumber'], true);
    $userlimitlast = $limitchangeloc['all'] - $user['limitchangeloc'];
    $userlimitlastfree = $limitchangeloc['free'] - $user['limitchangeloc'];
    if ($userlimitlastfree < 0)
        $userlimitlastfree = 0;
    $Pricechange = select("marzban_panel", "*", "code_panel", $dataget[1], "select")['priceChangeloc'];
    $textchange = sprintf($textbotlang['users']['changeLocation']['confirmPrompt'], $Pricechange, $userlimitlast, $userlimitlastfree);
    $keyboardextend = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['changeLocation']['confirm'], 'callback_data' => "confirmchangeloccha_" . $user['Processing_value']],
            ],
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_" . $user['Processing_value']],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textchange, $keyboardextend);
} elseif (preg_match('/confirmchangeloccha_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if (!invoiceBelongsToUser($nameloc, $from_id)) {
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $marzban_list_get_new = select("marzban_panel", "*", "code_panel", $user['Processing_value_one'], "select");
    $limitchangeloc = json_decode($setting['limitnumber'], true);
    $limitfree = true;
    if ($user['limitchangeloc'] < $limitchangeloc['free'] and intval($setting['statuslimitchangeloc']) == 1) {
        $limitfree = false;
    }
    if ($user['limitchangeloc'] >= $limitchangeloc['all'] and intval($setting['statuslimitchangeloc']) == 1) {
        sendmessage($from_id, $textbotlang['users']['changeLocation']['limitReached'], null, 'html');
        return;
    }
    if ($marzban_list_get_new['changeloc'] == "offchangeloc") {
        sendmessage($from_id, $textbotlang['users']['featureUnavailable2'], null, 'html');
        return;
    }
    if ($marzban_list_get_new == false) {
        sendmessage($from_id, $textbotlang['users']['genericRestart'], null, 'html');
        return;
    }
    $Pricechange = $marzban_list_get_new['priceChangeloc'];
    if ($nameloc['name_product'] == $textbotlang['users']['customSellVolume']['btnVolume'] || $nameloc['name_product'] == $textbotlang['users']['customSellVolume']['btnService']) {
        $prodcut['code_product'] = $textbotlang['users']['customSellVolume']['btnVolume'];
        $product['inbounds'] = null;
    } else {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :service_location OR Location = '/all') AND agent= :agent AND name_product = :name_product");
        $stmt->execute([
            ':service_location' => $nameloc['Service_location'],
            ':agent' => $user['agent'],
            'name_product' => $nameloc['name_product']
        ]);
        $prodcut = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if ($product['inbounds'] != null) {
        $marzban_list_get_new['inboundid'] = $prodcut['inbounds'];
    }
    if ($marzban_list_get_new['type'] == "Manualsale" && $marzban_list_get['url_panel'] == $marzban_list_get_new['url_panel']) {
        sendmessage($from_id, $textbotlang['users']['changeLocation']['notPossible'], null, 'html');
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "on_hold") {
        sendmessage($from_id, $textbotlang['users']['changeLocation']['configUnused'], null, 'html');
        return;
    }
    if ($DataUserOut['status'] != "active") {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        return;
    }
    if ($limitfree == false) {
        $Pricechange = 0;
    }
    if ($user['Balance'] < $Pricechange && $user['agent'] != "n2" && $limitfree) {
        $marzbandirectpay = select('shopSetting', "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        if ($marzbandirectpay == "offdirectbuy") {
            $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']]);
            $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']]);
            $bakinfos = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "account"],
                    ]
                ]
            ]);
            Editmessagetext($from_id, $message_id, sprintf($textbotlang['users']['Balance']['insufficientbalance'], $minbalance, $maxbalance), $bakinfos, 'HTML');
            step('getprice', $from_id);
            return;
        } else {
            if (intval($user['pricediscount']) != 0) {
                $result = ($Pricechange * $user['pricediscount']) / 100;
                $Pricechange = $Pricechange - $result;
                sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
            }
            if (intval($Pricechange) != 0) {
                $Balance_prim = $Pricechange - $user['Balance'];
                update("user", "Processing_value", $Balance_prim, "id", $from_id);
                Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['noCredit'], $step_payment);
                step('get_step_payment', $from_id);
                return;
            }
        }
    }
    if (intval($user['pricediscount']) != 0 and intval($Pricechange) != 0) {
        $result = ($Pricechange * $user['pricediscount']) / 100;
        $Pricechange = $Pricechange - $result;
        sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
    }
    if (intval($user['maxbuyagent']) != 0 and $user['agent'] == "n2") {
        if (($user['Balance'] - $Pricechange) < intval("-" . $user['maxbuyagent'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['maxpurchasereached'], null, 'HTML');
            return;
        }
    }
    $keyboardextend = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    $value = json_encode(array(
        "old_panel" => $marzban_list_get['code_panel'],
        "new_panel" => $marzban_list_get_new['code_panel'],
        "volume" => $DataUserOut['data_limit'],
        "used_traffic" => $DataUserOut['used_traffic'],
        "expire" => $DataUserOut['expire'],
        "stateus" => $DataUserOut['status']
    ));
    $stmt = $pdo->prepare("INSERT IGNORE INTO service_other (id_user, username,value,type,time,price) VALUES (?, ?, ?, ?,?,?)");
    $dateacc = date('Y/m/d H:i:s');
    $type = "change_location";
    $stmt->execute([$from_id, $nameloc['username'], $value, $type, $dateacc, $prodcut['price_product']]);
    if ($DataUserOut['data_limit'] == 0 || $DataUserOut['data_limit'] == null) {
        $data_limit = 0;
    } else {
        $data_limit = $DataUserOut['data_limit'] - $DataUserOut['used_traffic'];
    }
    $datac = array(
        'expire' => $DataUserOut['expire'],
        'data_limit' => $data_limit,
        'from_id' => $from_id,
        'username' => $username,
        'type' => 'usertest'
    );
    $expirationDate = $DataUserOut['expire'] ? jdate('Y/m/d', $DataUserOut['expire']) : $textbotlang['users']['status']['unlimited'];
    $timeDiff = $DataUserOut['expire'] - time();
    $day = $DataUserOut['expire'] ? floor($timeDiff / 86400) . $textbotlang['users']['status']['day'] : $textbotlang['users']['status']['unlimited'];
    $output = $DataUserOut['data_limit'] - $DataUserOut['used_traffic'];
    $RemainingVolume = $DataUserOut['data_limit'] ? formatBytes($output) : $textbotlang['common']['labels']['unlimitedShort'];
    if ($marzban_list_get['url_panel'] == $marzban_list_get_new['url_panel']) {
        $remove = $ManagePanel->RemoveUser($nameloc['Service_location'], $nameloc['username']);
        $dataoutput = $ManagePanel->createUser($marzban_list_get_new['name_panel'], "usertest", $DataUserOut['username'], $datac);
        if ($dataoutput['username'] == null) {
            $dataoutput['msg'] = json_encode($dataoutput['msg']);
            sendmessage($from_id, $textbotlang['users']['sell']['errorConfig'], $keyboard, 'HTML');
            $texterros = sprintf($textbotlang['Admin']['reportgroup']['errorChangeLocation'], $dataoutput['msg'], $from_id, $username, $marzban_list_get['name_panel'], $marzban_list_get_new['name_panel']);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $texterros,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
    } else {
        $dataoutput = $ManagePanel->createUser($marzban_list_get_new['name_panel'], "usertest", $DataUserOut['username'], $datac);
        if ($dataoutput['username'] == null) {
            $dataoutput['msg'] = json_encode($dataoutput['msg']);
            sendmessage($from_id, $textbotlang['users']['sell']['errorConfig'], $keyboard, 'HTML');
            $texterros = sprintf($textbotlang['Admin']['reportgroup']['errorChangeLocation'], $dataoutput['msg'], $from_id, $username, $marzban_list_get['name_panel'], $marzban_list_get_new['name_panel']);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $texterros,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        $remove = $ManagePanel->RemoveUser($nameloc['Service_location'], $nameloc['username']);
    }
    $output_config_link = "";
    if ($marzban_list_get_new['sublink'] == "onsublink") {
        $output_config_link = $dataoutput['subscription_url'];
    }
    if ($marzban_list_get_new['config'] == "onconfig") {
        if (is_array($dataoutput['configs'])) {
            foreach ($dataoutput['configs'] as $configs) {
                $output_config_link .= "\n" . $configs;
            }
        }
    }
    $limitnew = $user['limitchangeloc'] + 1;
    update("user", "limitchangeloc", $limitnew, "id", $from_id);
    $textchangeloc = sprintf($textbotlang['users']['changeLocation']['success'], $marzban_list_get_new['name_panel'], $nameloc['username'], $RemainingVolume, $expirationDate, $day, $output_config_link);
    if (intval($Pricechange) != 0) {
        $Balance_Low_user = $user['Balance'] - $Pricechange;
        update("user", "Balance", $Balance_Low_user, "id", $from_id);
    }
    update("invoice", "Service_location", $marzban_list_get_new['name_panel'], "username", $nameloc['username']);
    if ($marzban_list_get_new['inboundid'] != null) {
        update("invoice", "inboundid", $marzban_list_get_new['inboundid'], "username", $nameloc['username']);
    }
    Editmessagetext($from_id, $message_id, $textchangeloc, $keyboardextend);
    $balanceformatsell = number_format(select("user", "Balance", "id", $from_id, "select")['Balance'], 0);
    $format_byte = formatBytes($data_limit);
    $textreport = sprintf($textbotlang['Admin']['reportgroup']['locationChanged'], $from_id, $username, $marzban_list_get['name_panel'], $marzban_list_get_new['name_panel'], $nameloc['username'], $format_byte, $balanceformatsell);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $textreport,
            'parse_mode' => "HTML"
        ]);
    }
} elseif (preg_match('/disorder-(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if (!invoiceBelongsToUser($nameloc, $from_id)) {
        return;
    }
    update("user", "Processing_value", $id_invoice, "id", $from_id);
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        return;
    }
    $textdisorder = $textbotlang['users']['support']['disruptionPrompt'];
    $keyboarddisorder = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_" . $id_invoice],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textdisorder, $keyboarddisorder);
    step("getdesdisorder", $from_id);
} elseif ($user['step'] == "getdesdisorder") {
    update("user", "Processing_value", $text, "id", $from_id);
    $nameloc = select("invoice", "*", "id_invoice", $user['Processing_value'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        return;
    }
    $textdisorder = $textbotlang['users']['support']['disruptionConfirm'];
    $keyboarddisorder = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['confirmDisruptionReport'], 'callback_data' => "confirmdisorders-" . $user['Processing_value']],
            ],
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_" . $user['Processing_value']],
            ]
        ]
    ]);
    sendmessage($from_id, $textdisorder, $keyboarddisorder, 'html');
    step("home", $from_id);
} elseif (preg_match('/confirmdisorders-(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if (!invoiceBelongsToUser($nameloc, $from_id)) {
        return;
    }
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['support']['answermessage'], 'callback_data' => 'Response_' . $from_id],
            ],
        ]
    ]);
    $textdisorder = sprintf($textbotlang['Admin']['reportgroup']['disruption'], $username, $from_id, $nameloc['username'], $nameloc['name_product'], $nameloc['Service_location'], $user['Processing_value']);
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['online_at'] == "online") {
        $lastonline = $textbotlang['common']['connection']['onlineAlt'];
    } elseif ($DataUserOut['online_at'] == "offline") {
        $lastonline = $textbotlang['common']['connection']['offlineAlt'];
    } else {
        if (isset($DataUserOut['online_at']) && $DataUserOut['online_at'] !== null) {
            $dateString = $DataUserOut['online_at'];
            $lastonline = jdate('Y/m/d H:i:s', strtotime($dateString));
        } else {
            $lastonline = $textbotlang['common']['connection']['notConnectedAlt'];
        }
    }
    #-------------status----------------#
    $status = $DataUserOut['status'] ?? 'Unknown';
    $status_var = [
        'active' => $textbotlang['users']['status']['active'],
        'limited' => $textbotlang['users']['status']['limited'],
        'disabled' => $textbotlang['users']['status']['disabled'],
        'expired' => $textbotlang['users']['status']['expired'],
        'on_hold' => $textbotlang['users']['status']['on_hold'],
        'Unknown' => $textbotlang['users']['status']['unknown'],
        'deactivev' => $textbotlang['users']['status']['disabled'],
    ][$status] ?? $textbotlang['users']['status']['unknown'];
    #--------------[ expire ]---------------#
    $expirationDate = $DataUserOut['expire'] ? jdate('Y/m/d', $DataUserOut['expire']) : $textbotlang['users']['status']['unlimited'];
    #-------------[ data_limit ]----------------#
    $LastTraffic = $DataUserOut['data_limit'] ? formatBytes($DataUserOut['data_limit']) : $textbotlang['users']['status']['unlimited'];
    #---------------[ RemainingVolume ]--------------#
    $output = $DataUserOut['data_limit'] - $DataUserOut['used_traffic'];
    $RemainingVolume = $DataUserOut['data_limit'] ? formatBytes($output) : $textbotlang['common']['labels']['unlimitedShort'];
    #---------------[ used_traffic ]--------------#
    $usedTrafficGb = $DataUserOut['used_traffic'] ? formatBytes($DataUserOut['used_traffic']) : $textbotlang['users']['status']['notConsumed'];
    #--------------[ day ]---------------#
    $timeDiff = $DataUserOut['expire'] - time();
    $day = $DataUserOut['expire'] ? floor($timeDiff / 86400) . $textbotlang['users']['status']['day'] : $textbotlang['users']['status']['unlimited'];
    #--------------[ subsupdate ]---------------#
    if ($DataUserOut['sub_updated_at'] !== null) {
        $sub_updated = $DataUserOut['sub_updated_at'];
        $dateTime = new DateTime($sub_updated, new DateTimeZone('UTC'));
        $dateTime->setTimezone(new DateTimeZone('Asia/Tehran'));
        $lastupdate = jdate('Y/m/d H:i:s', $dateTime->getTimestamp());
    }
    if ($DataUserOut['data_limit'] != null && $DataUserOut['used_traffic'] != null) {
        $Percent = ($DataUserOut['data_limit'] - $DataUserOut['used_traffic']) * 100 / $DataUserOut['data_limit'];
    } else {
        $Percent = "100";
    }
    if ($Percent < 0)
        $Percent = -($Percent);
    $Percent = round($Percent, 2);
    $textdisorder .= sprintf($textbotlang['users']['status']['summary'], $status_var, $LastTraffic, $usedTrafficGb, $RemainingVolume, $Percent, $expirationDate, $day, $DataUserOut['subscription_url'], $lastonline, $lastupdate, $DataUserOut['sub_last_user_agent']);
    foreach ($admin_ids as $admin) {
        $adminrulecheck = select("admin", "*", "id_admin", $admin, "select");
        if ($adminrulecheck['rule'] == "Seller")
            continue;
        sendmessage($admin, $textdisorder, $Response, 'html');
    }
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_$id_invoice"],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['support']['requestSubmitted'], $bakinfos, 'html');
} elseif (preg_match('/Extra_time_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if (!invoiceBelongsToUser($nameloc, $from_id)) {
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban_list_get['status_extend'] == "off_extend") {
        sendmessage($from_id, $textbotlang['users']['extraTime']['notSupportedPanel'], null, 'html');
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        return;
    }
    if ($DataUserOut['status'] == "on_hold") {
        sendmessage($from_id, $textbotlang['users']['extend']['connectFirst'], null, 'html');
        return;
    }
    $eextraprice = json_decode($marzban_list_get['priceextratime'], true);
    $extratimepricevalue = $eextraprice[$user['agent']];
    update("user", "Processing_value", $nameloc['id_invoice'], "id", $from_id);
    $textextra = sprintf($textbotlang['users']['extraTime']['prompt'], $extratimepricevalue);
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textextra, $bakinfos);
    step('gettimeextra', $from_id);
} elseif ($user['step'] == "gettimeextra") {
    if (!ctype_digit($text) || $text < 1) {
        sendmessage($from_id, $textbotlang['common']['invalidTime'], $backuser, 'HTML');
        return;
    }
    $nameloc = select("invoice", "*", "id_invoice", $user['Processing_value'], "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $eextraprice = json_decode($marzban_list_get['priceextratime'], true);
    $extratimepricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['priceextravolume'], true);
    $extrapricevalue = $eextraprice[$user['agent']];
    $priceextratime = $extratimepricevalue * $text;
    $keyboardsetting = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['extraTime']['extratimecheck'], 'callback_data' => 'confirmaextratime-' . $extratimepricevalue * $text],
            ]
        ]
    ]);
    $priceextratime = number_format($priceextratime, 0);
    $extrapricevalues = number_format($extrapricevalue, 0);
    $textextra = sprintf($textbotlang['users']['extraTime']['invoiceCreated'], $extratimepricevalue, $text, $priceextratime);
    sendmessage($from_id, $textextra, $keyboardsetting, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/confirmaextratime-(\w+)/', $datain, $dataget)) {
    $tmieextra = $dataget[1];
    $pricelasttime = $tmieextra;
    $nameloc = select("invoice", "*", "id_invoice", $user['Processing_value'], "select");
    if (!invoiceBelongsToUser($nameloc, $from_id)) {
        return;
    }
    if (!in_array($nameloc['Status'], ['active', 'end_of_time', 'end_of_volume', 'sendedwarn', 'send_on_hold'])) {
        sendmessage($from_id, $textbotlang['users']['sell']['purchaseError'], null, 'HTML');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $eextraprice = json_decode($marzban_list_get['priceextratime'], true);
    $extratimepricevalue = $eextraprice[$user['agent']];
    if ($user['Balance'] < $tmieextra && $user['agent'] != "n2") {
        $marzbandirectpay = select('shopSetting', "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        if ($marzbandirectpay == "offdirectbuy") {
            $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']]);
            $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']]);
            $bakinfos = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "account"],
                    ]
                ]
            ]);
            Editmessagetext($from_id, $message_id, sprintf($textbotlang['users']['Balance']['insufficientbalance'], $minbalance, $maxbalance), $bakinfos, 'HTML');
            step('getprice', $from_id);
            return;
        } else {
            $valuetime = $tmieextra / $extratimepricevalue;
            if (intval($user['pricediscount']) != 0) {
                $result = ($tmieextra * $user['pricediscount']) / 100;
                $pricelasttime = $tmieextra - $result;
                sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
            }
            if (intval($pricelasttime) != 0) {
                $Balance_prim = $pricelasttime - $user['Balance'];
                update("user", "Processing_value", $Balance_prim, "id", $from_id);
                Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['noCredit'], $step_payment);
                step('get_step_payment', $from_id);
                update("user", "Processing_value_one", "{$nameloc['username']}%{$valuetime}", "id", $from_id);
                update("user", "Processing_value_tow", "getextratimeuser", "id", $from_id);
                return;
            }
        }
    }
    deletemessage($from_id, $message_id);
    if (intval($user['pricediscount']) != 0 and intval($pricelasttime) != 0) {
        $result = ($tmieextra * $user['pricediscount']) / 100;
        $pricelasttime = $tmieextra - $result;
        sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
    }
    $Balance_Low_user = $user['Balance'] - $pricelasttime;
    if (intval($user['maxbuyagent']) != 0 and $user['agent'] == "n2") {
        if ($Balance_Low_user < intval("-" . $user['maxbuyagent'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['maxpurchasereached'], null, 'HTML');
            return;
        }
    }
    update("invoice", "Status", "active", "id_invoice", $nameloc['id_invoice']);
    $extratimeday = $tmieextra / $extratimepricevalue;
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    $data_for_database = json_encode(array(
        'day' => $extratimeday,
        'priceـper_day' => $extratimeday,
        'old_volume' => $DataUserOut['data_limit'],
        'expire_old' => $DataUserOut['expire']
    ));
    $timeservice = $DataUserOut['expire'] - time();
    $day = floor($timeservice / 86400);
    $extra_time = $ManagePanel->extra_time($nameloc['username'], $marzban_list_get['code_panel'], $extratimeday);
    if ($extra_time['status'] == false) {
        $extra_time['msg'] = json_encode($extra_time['msg']);
        $textreports = sprintf($textbotlang['Admin']['reportgroup']['errorExtraTime'], $marzban_list_get['name_panel'], $nameloc['username'], $extra_time['msg']);
        sendmessage($from_id, $textbotlang['users']['extraVolume']['serviceError'], null, 'HTML');
        if (strlen($setting['Channel_Report']) > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $errorreport,
                'text' => $textreports,
                'parse_mode' => "HTML"
            ]);
        }
        return;
    }
    update("user", "Balance", $Balance_Low_user, "id", $from_id);
    $stmt = $pdo->prepare("INSERT IGNORE INTO service_other (id_user, username, value, type, time, price, output) VALUES (:id_user, :username, :value, :type, :time, :price, :output)");
    $value = $data_for_database;
    $dateacc = date('Y/m/d H:i:s');
    $type = "extra_time_user";
    $output = json_encode($extra_time);
    $stmt->execute([
        ':id_user' => $from_id,
        ':username' => $nameloc['username'],
        ':value' => $value,
        ':type' => $type,
        ':time' => $dateacc,
        ':price' => $pricelasttime,
        ':output' => $output,
    ]);
    $keyboardextrafnished = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backservice'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    if (intval($setting['scorestatus']) == 1 and !in_array($from_id, $admin_ids)) {
        sendmessage($from_id, $textbotlang['users']['affiliates']['pointsEarned1Alt'], null, 'html');
        $scorenew = $user['score'] + 1;
        update("user", "score", $scorenew, "id", $from_id);
    }
    $volumesformat = number_format($tmieextra);
    $textextratime = sprintf($textbotlang['users']['extraTime']['success'], $nameloc['username'], $extratimeday, $volumesformat);
    sendmessage($from_id, $textextratime, $keyboardextrafnished, 'HTML');
    $volumes = $tmieextra / $extratimepricevalue;
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['extraTime'], $from_id, $volumes, $volumesformat, $nameloc['username']);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
} elseif (preg_match('/removeserviceuser_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if (!invoiceBelongsToUser($nameloc, $from_id)) {
        return;
    }
    savedata("clear", "id_invoice", $id_invoice);
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['status']['askDeleteReason'], $bakinfos);
    step("getdisdeleteconfig", $from_id);
} elseif ($user['step'] == "getdisdeleteconfig") {
    $userdata = json_decode($user['Processing_value'], true);
    $id_invoice = $userdata['id_invoice'];
    savedata("save", "descritionsremove", $text);
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if ($nameloc['name_product'] == $textbotlang['common']['labels']['testService3']) {
        sendmessage($from_id, $textbotlang['users']['status']['errorusertest'], null, 'html');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if (isset($DataUserOut['status']) && in_array($DataUserOut['status'], ["expired", "limited", "disabled"])) {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        step("home", $from_id);
        return;
    }
    $requestcheck = select("cancel_service", "*", "username", $nameloc['username'], "count");
    if ($requestcheck != 0) {
        sendmessage($from_id, $textbotlang['users']['status']['errorexits'], null, 'html');
        return;
    }
    $confirmremove = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['confirmDeleteService'], 'callback_data' => "confirmremoveservices-$id_invoice"],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['users']['status']['descriptionsRemoveService'], $confirmremove, "html");
    step("home", $from_id);
} elseif (preg_match('/confirmremoveservices-(\w+)/', $datain, $dataget)) {
    $userdata = json_decode($user['Processing_value'], true);
    $stmt = $pdo->prepare("SELECT * FROM cancel_service WHERE id_user = :from_id AND status = 'waiting'");
    $stmt->execute([
        ':from_id' => $from_id
    ]);
    $checkcancelservicecount = $stmt->rowCount();
    if ($checkcancelservicecount != 0) {
        sendmessage($from_id, $textbotlang['users']['status']['exitsRequests'], null, 'HTML');
        return;
    }
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $stmt = $pdo->prepare("INSERT IGNORE INTO cancel_service (id_user, username,description,status) VALUES (?, ?, ?, ?)");
    $descriptions = "0";
    $Status = "waiting";
    $stmt->execute([$from_id, $nameloc['username'], $descriptions, $Status]);
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if (isset($DataUserOut['msg']) && $DataUserOut['msg'] == "User not found") {
        sendmessage($from_id, $textbotlang['users']['status']['userNotFound'], null, 'html');
        step('home', $from_id);
        return;
    }
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['status']['panelNotConnected'], null, 'html');
        step('home', $from_id);
        return;
    }
    #-------------status----------------#
    if ($DataUserOut['online_at'] == "online") {
        $lastonline = $textbotlang['common']['connection']['onlineAlt'];
    } elseif ($DataUserOut['online_at'] == "offline") {
        $lastonline = $textbotlang['common']['connection']['offlineAlt'];
    } else {
        if (isset($DataUserOut['online_at']) && $DataUserOut['online_at'] !== null) {
            $dateString = $DataUserOut['online_at'];
            $lastonline = jdate('Y/m/d H:i:s', strtotime($dateString));
        } else {
            $lastonline = $textbotlang['common']['connection']['notConnectedAlt'];
        }
    }
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
    $RemainingVolume = $DataUserOut['data_limit'] ? formatBytes($output) : $textbotlang['common']['labels']['unlimitedShort'];
    #---------------[ used_traffic ]--------------#
    $usedTrafficGb = $DataUserOut['used_traffic'] ? formatBytes($DataUserOut['used_traffic']) : $textbotlang['users']['status']['notConsumed'];
    #--------------[ day ]---------------#
    $timeDiff = $DataUserOut['expire'] - time();
    $day = $DataUserOut['expire'] ? floor($timeDiff / 86400) . $textbotlang['users']['status']['day'] : $textbotlang['users']['status']['unlimited'];
    #-----------------------------#
    $textinfoadmin = sprintf($textbotlang['Admin']['reportgroup']['deleteServiceRequest'], $from_id, $username, $nameloc['username'], $status_var, $nameloc['Service_location'], $nameloc['id_invoice'], $lastonline, $usedTrafficGb, $LastTraffic, $RemainingVolume, $expirationDate, $day, $userdata['descritionsremove']);
    $confirmremoveadmin = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['manualDelete'], 'callback_data' => "remoceserviceadminmanual-{$nameloc['id_invoice']}"],
                ['text' => $textbotlang['keyboard']['deleteServiceAlt'], 'callback_data' => "remoceserviceadmin-{$nameloc['id_invoice']}"],
                ['text' => $textbotlang['keyboard']['rejectDelete'], 'callback_data' => "rejectremoceserviceadmin-{$nameloc['id_invoice']}"],
            ],
        ]
    ]);
    foreach ($admin_ids as $admin) {
        sendmessage($admin, $textinfoadmin, $confirmremoveadmin, 'html');
        step("home", $admin);
    }
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['users']['status']['sendrequestsremove'], $keyboard, 'html');
} elseif (preg_match('/transfer_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if (!invoiceBelongsToUser($nameloc, $from_id)) {
        return;
    }
    if ($nameloc['name_product'] == $textbotlang['common']['labels']['testService4']) {
        sendmessage($from_id, $textbotlang['users']['transfer']['transferNotValid'], null, 'html');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if (isset($DataUserOut['status']) && in_array($DataUserOut['status'], ["expired", "limited", "disabled"])) {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        return;
    }
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_" . $nameloc['id_invoice']],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['transfer']['description'], $bakinfos);
    step("getidfortransfer", $from_id);
    update("user", "Processing_value_one", $nameloc['username'], "id", $from_id);
    update("user", "Processing_value_tow", $nameloc['id_invoice'], "id", $from_id);
} elseif ($user['step'] == "getidfortransfer") {
    if (!rowExists("user", "id", $text)) {
        sendmessage($from_id, $textbotlang['users']['transfer']['notUserTrans'], $backuser, 'HTML');
        return;
    }
    update("user", "Processing_value_one", $text, "id", $from_id);
    $confirmtransfer = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['confirmTransferService'], 'callback_data' => "confrimtransfers_{$user['Processing_value_tow']}"],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['users']['transfer']['confirm'], $confirmtransfer, 'HTML');
    step("home", $from_id);
} elseif (preg_match('/confrimtransfers_(\w+)/', $datain, $dataget)) {
    if ($from_id == $user['Processing_value_one']) {
        sendmessage($from_id, $textbotlang['users']['transfer']['notSendServiceYou'], $keyboard, 'HTML');
        return;
    }
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if (!invoiceBelongsToUser($nameloc, $from_id)) {
        return;
    }
    update("invoice", "id_user", $user['Processing_value_one'], "id_invoice", $id_invoice);
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "backorder"],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['transfer']['confirmed'], $bakinfos);
    $texttransfer = strtr($textbotlang['users']['transfer']['receivedNotice'], ['{service_username}' => $nameloc['username'], '{from_id}' => $from_id]);
    sendmessage($user['Processing_value_one'], $texttransfer, $keyboard, 'HTML');
    $stmt = $pdo->prepare("INSERT IGNORE INTO service_other (id_user, username,value,type,time,price) VALUES (?, ?, ?, ?,?,?)");
    $value = $user['Processing_value_one'];
    $dateacc = date('Y/m/d H:i:s');
    $type = "transfertouser";
    $price = "0";
    $stmt->execute([$from_id, $nameloc['username'], $value, $type, $dateacc, $price]);
} elseif ($text == $textbotlang['textbot']['userTest'] || $datain == "usertestbtn" || $text == "usertest") {
    if (!check_active_btn($setting['keyboardmain'], "text_usertest")) {
        sendmessage($from_id, $textbotlang['users']['usertest']['unavailable'], null, 'HTML');
        return;
    }
    $locationproduct = select("marzban_panel", "*", "TestAccount", "ONTestAccount", "count");
    if ($locationproduct == 0) {
        sendmessage($from_id, $textbotlang['users']['sell']['nullPanel'], null, 'HTML');
        return;
    }
    if ($locationproduct != 1) {
        if ($setting['get_number'] == "onAuthenticationphone" && $user['step'] != "get_number" && $user['number'] == "none") {
            sendmessage($from_id, $textbotlang['users']['number']['confirming'], $request_contact, 'HTML');
            step('get_number', $from_id);
        }
        if ($user['number'] == "none" && $setting['get_number'] == "onAuthenticationphone")
            return;
        if ($user['limit_usertest'] <= 0 && !in_array($from_id, $admin_ids)) {
            sendmessage($from_id, $textbotlang['users']['usertest']['limitwarning'], $keyboard_buy, 'html');
            return;
        }
        sendmessage($from_id, $textbotlang['textbot']['selectLocation'], $list_marzban_usertest, 'html');
    }
}
if ($user['step'] == "createusertest" || preg_match('/locationtest_(.*)/', $datain, $dataget) || ($text == $textbotlang['textbot']['userTest'] || $datain == "usertestbtn" || $text == "usertest")) {
    if (!check_active_btn($setting['keyboardmain'], "text_usertest")) {
        sendmessage($from_id, $textbotlang['users']['usertest']['unavailable'], null, 'HTML');
        return;
    }
    $userlimit = select("user", "*", "id", $from_id, "select");
    if ($userlimit['limit_usertest'] <= 0 && !in_array($from_id, $admin_ids)) {
        sendmessage($from_id, $textbotlang['users']['usertest']['limitwarning'], $keyboard_buy, 'html');
        return;
    }
    if ($setting['get_number'] == "onAuthenticationphone" && $user['step'] != "get_number" && $user['number'] == "none") {
        sendmessage($from_id, $textbotlang['users']['number']['confirming'], $request_contact, 'HTML');
        step('get_number', $from_id);
    }
    if ($user['number'] == "none" && $setting['get_number'] == "onAuthenticationphone")
        return;
    $locationproduct = select("marzban_panel", "*", "TestAccount", "ONTestAccount", "count");
    if ($locationproduct == 1) {
        $panel = select("marzban_panel", "*", "TestAccount", "ONTestAccount", "select");
        if ($panel['hide_user'] != null) {
            $list_user = json_decode($panel['hide_user'], true);
            if (in_array($from_id, $list_user)) {
                sendmessage($from_id, $textbotlang['users']['sell']['nullPanel'], null, 'HTML');
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
            sendmessage($from_id, $textbotlang['users']['sell']['stockFinished'], null, 'HTML');
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
    if (isset($DataUserOut['username']) || rowExists("invoice", "username", $username_ac)) {
        $username_ac = $random_number . "_" . $username_ac;
    }
    $datac = array(
        'expire' => strtotime(date("Y-m-d H:i:s", strtotime("+" . $marzban_list_get['time_usertest'] . "hours"))),
        'data_limit' => $marzban_list_get['val_usertest'] * 1048576,
        'from_id' => $from_id,
        'username' => $username,
        'type' => 'usertest'
    );
    $date = time();
    $notifctions = json_encode(array(
        'volume' => false,
        'time' => false,
    ));
    $stmt = $pdo->prepare("INSERT IGNORE INTO invoice (id_user, id_invoice, username,time_sell, Service_location, name_product, price_product, Volume, Service_time,Status,notifctions) VALUES (?, ?,  ?, ?, ?, ?, ?,?,?,?,?)");
    $Status = "active";
    $info_product['name_product'] = $textbotlang['common']['labels']['testService5'];
    $info_product['price_product'] = "0";
    $Status = "active";
    $stmt->execute([$from_id, $randomString, $username_ac, $date, $marzban_list_get['name_panel'], $info_product['name_product'], $info_product['price_product'], $marzban_list_get['val_usertest'], $marzban_list_get['time_usertest'], $Status, $notifctions]);
    $dataoutput = $ManagePanel->createUser($marzban_list_get['name_panel'], "usertest", $username_ac, $datac);
    if (!is_array($dataoutput) || empty($dataoutput['username'])) {
        $dataoutput['msg'] = json_encode($dataoutput['msg'] ?? $dataoutput ?? 'unknown error');
        sendmessage($from_id, $textbotlang['users']['usertest']['errorcreat'], $keyboard, 'html');
        $texterros = sprintf($textbotlang['Admin']['reportgroup']['errorTestAccountCreate'], $dataoutput['msg'], $from_id, $username, $marzban_list_get['name_panel']);
        if (strlen($setting['Channel_Report']) > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $errorreport,
                'text' => $texterros,
                'parse_mode' => "HTML"
            ]);
        }
        step('home', $from_id);
        update("invoice", "Status", "Unsuccessful", "id_invoice", $randomString);
        return;
    }
    $output_config_link = "";
    $config = "";
    $output_config_link = $marzban_list_get['sublink'] == "onsublink" ? $dataoutput['subscription_url'] : "";
    if ($marzban_list_get['config'] == "onconfig" && is_array($dataoutput['configs'])) {
        for ($i = 0; $i < count($dataoutput['configs']); ++$i) {
            $output_config_link .= "\n" . $dataoutput['configs'][$i];
        }
    }

    $usertestinfo = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['help']['btninlinebuy'], 'callback_data' => "helpbtn"],
            ]
        ]
    ]);
    if ($marzban_list_get['type'] == "WGDashboard") {
        $textbotlang['textbot']['afterText'] = $textbotlang['users']['sell']['created'];
    }
    $textbotlang['textbot']['afterText'] = $marzban_list_get['type'] == "ibsng" || $marzban_list_get['type'] == "mikrotik" ? $textbotlang['textbot']['afterPayIbsng'] : $textbotlang['textbot']['afterText'];
    $textcreatuser = str_replace('{username}', $dataoutput['username'], $textbotlang['textbot']['afterText']);
    $textcreatuser = str_replace('{name_service}', $textbotlang['common']['labels']['test'], $textcreatuser);
    $textcreatuser = str_replace('{location}', $marzban_list_get['name_panel'], $textcreatuser);
    $textcreatuser = str_replace('{day}', $marzban_list_get['time_usertest'], $textcreatuser);
    $textcreatuser = str_replace('{volume}', $marzban_list_get['val_usertest'], $textcreatuser);
    $textcreatuser = str_replace('{config}', "<code>{$output_config_link}</code>", $textcreatuser);
    $textcreatuser = str_replace('{links}', $config, $textcreatuser);
    $textcreatuser = str_replace('{links2}', $output_config_link, $textcreatuser);
    if ($marzban_list_get['type'] == "ibsng" || $marzban_list_get['type'] == "mikrotik") {
        $textcreatuser = str_replace('{password}', $dataoutput['subscription_url'], $textcreatuser);
        update("invoice", "user_info", $dataoutput['subscription_url'], "id_invoice", $randomString);
    }
    sendMessageService($marzban_list_get, $dataoutput['configs'], $output_config_link, $dataoutput['username'], $usertestinfo, $textcreatuser, $randomString);
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboard, 'HTML');
    step('home', $from_id);
    if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customTextSequential', 'usernameSequential', 'numericIdSequential', 'agentCustomTextSequential'], true)) {
        $value = intval($user['number_username']) + 1;
        update("user", "number_username", $value, "id", $from_id);
        if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customTextSequential', 'agentCustomTextSequential'], true)) {
            $value = intval($setting['numbercount']) + 1;
            update("setting", "numbercount", $value);
        }
    }
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['manageUser']['manageUserBtn'], 'callback_data' => 'manageuser_' . $from_id],
            ],
        ]
    ]);
    $timejalali = jdate('Y/m/d H:i:s');
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['testAccountCreated'], $from_id, $username, $username_ac, $first_name, $marzban_list_get['name_panel'], $marzban_list_get['time_usertest'], $marzban_list_get['val_usertest'], $randomString, $user['agent'], $user['number'], $timejalali);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $reporttest,
            'text' => $text_report,
            'parse_mode' => "HTML",
            'reply_markup' => $Response
        ]);
    }
} elseif ($text == $textbotlang['textbot']['help'] || $datain == "helpbtn" || $datain == "helpbtns" || $text == "/help" || $text == "help") {
    if (!check_active_btn($setting['keyboardmain'], "text_help")) {
        sendmessage($from_id, $textbotlang['users']['help']['disablehelp'], null, 'HTML');
        return;
    }
    if ($setting['categoryhelp'] == "1") {
        if ($datain == "helpbtns") {
            Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['selectCategoryShort'], $json_list_helpـcategory, 'HTML');
        } else {
            sendmessage($from_id, $textbotlang['users']['sell']['selectCategoryShort'], $json_list_helpـcategory, 'HTML');
        }
    } else {
        $helplist = select("help", "*", null, null, "fetchAll");
        $helpidos = ['inline_keyboard' => []];
        foreach ($helplist as $result) {
            $helpidos['inline_keyboard'][] = [
                ['text' => $result['name_os'], 'callback_data' => "helpos_{$result['id']}"]
            ];
        }
        if ($setting['linkappstatus'] == "1") {
            $helpidos['inline_keyboard'][] = [
                ['text' => $textbotlang['keyboard']['appDownloadLink'], 'callback_data' => "linkappdownlod"],
            ];
        }
        $helpidos['inline_keyboard'][] = [
            ['text' => $textbotlang['users']['backmenu'], 'callback_data' => "backuser"],
        ];
        $json_list_help = json_encode($helpidos);
        if ($datain == "helpbtns") {
            Editmessagetext($from_id, $message_id, $textbotlang['users']['selectoption'], $json_list_help, 'HTML');
        } else {
            sendmessage($from_id, $textbotlang['users']['selectoption'], $json_list_help, 'HTML');
        }
    }
} elseif (preg_match('/^helpctg_([0-9a-f]+)/', $datain, $dataget) || preg_match('/^helpctgoryـ(.*)/', $datain, $dataget)) {
    if (isset($helpCategoryMap) && isset($helpCategoryMap[$dataget[1]])) {
        $helpCategoryName = $helpCategoryMap[$dataget[1]];
    } else {
        $helpCategoryName = $dataget[1];
    }
    $helplist = select("help", "*", "category", $helpCategoryName, "fetchAll");
    $helpidos = ['inline_keyboard' => []];
    foreach ($helplist as $result) {
        $helpidos['inline_keyboard'][] = [
            ['text' => $result['name_os'], 'callback_data' => "helpos_{$result['id']}"]
        ];
    }
    $helpidos['inline_keyboard'][] = [
        ['text' => $textbotlang['users']['backmenu'], 'callback_data' => "helpbtns"],
    ];
    $json_list_help = json_encode($helpidos);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['selectoption'], $json_list_help, 'HTML');
} elseif (preg_match('/^helpos_(.*)/', $datain, $dataget)) {
    deletemessage($from_id, $message_id);
    $backinfoss = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "helpbtns"],
            ]
        ]
    ]);
    $helpid = $dataget[1];
    $helpdata = select("help", "*", "id", $helpid, "select");
    if ($helpdata !== false) {
        if (strlen($helpdata['Media_os']) != 0) {
            if ($helpdata['type_Media_os'] == "video") {
                $backinfoss = json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "helpbtn"],
                        ]
                    ]
                ]);
                telegram('sendvideo', [
                    'chat_id' => $from_id,
                    'video' => $helpdata['Media_os'],
                    'caption' => $helpdata['Description_os'],
                    'reply_markup' => $backinfoss,
                    'parse_mode' => "HTML"
                ]);
            } elseif ($helpdata['type_Media_os'] == "document") {
                $backinfoss = json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "helpbtn"],
                        ]
                    ]
                ]);
                telegram('sendDocument', [
                    'chat_id' => $from_id,
                    'document' => $helpdata['Media_os'],
                    'caption' => $helpdata['Description_os'],
                    'reply_markup' => $backinfoss,
                    'parse_mode' => "HTML"
                ]);
            } elseif ($helpdata['type_Media_os'] == "photo") {
                $backinfoss = json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "helpbtn"],
                        ]
                    ]
                ]);
                telegram('sendphoto', [
                    'chat_id' => $from_id,
                    'photo' => $helpdata['Media_os'],
                    'caption' => $helpdata['Description_os'],
                    'reply_markup' => $backinfoss,
                    'parse_mode' => "HTML"
                ]);
            }
        } else {
            sendmessage($from_id, $helpdata['Description_os'], $backinfoss, 'HTML');
        }
    }
} elseif ($text == $textbotlang['textbot']['support'] || $datain == "supportbtns" || $text == "/support") {
    if (!check_active_btn($setting['keyboardmain'], "text_support")) {
        sendmessage($from_id, $textbotlang['users']['buttonDisabled'], null, 'HTML');
        return;
    }
    if ($datain == "supportbtns") {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['support']['btnsupport'], $supportoption);
    } else {
        sendmessage($from_id, $textbotlang['users']['support']['btnsupport'], $supportoption, 'HTML');
    }
} elseif ($datain == "support") {
    Editmessagetext($from_id, $message_id, $textbotlang['users']['support']['selectDepartment'], $list_departman, 'HTML');
} elseif (preg_match('/^departman_(.*)/', $datain, $dataget)) {
    $iddeparteman = $dataget[1];
    savedata("clear", "iddeparteman", $iddeparteman);
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['users']['support']['sendMessage'], $backuser, 'HTML');
    step("gettextticket", $from_id);
} elseif ($user['step'] == "gettextticket" && $text) {
    $userdata = json_decode($user['Processing_value'], true);
    $departeman = select("departman", "*", "id", $userdata['iddeparteman'], "select");
    $time = date('Y/m/d H:i:s');
    $timejalali = jdate('Y/m/d H:i:s');
    $randomString = bin2hex(random_bytes(4));
    $stmt = $pdo->prepare("INSERT IGNORE INTO support_message (Tracking,idsupport,iduser,name_departman,text,time,status) VALUES (:Tracking,:idsupport,:iduser,:name_departman,:text,:time,:status)");
    $status = "Unseen";
    $stmt->bindParam(':Tracking', $randomString);
    $stmt->bindParam(':idsupport', $departeman['idsupport']);
    $stmt->bindParam(':iduser', $from_id);
    $stmt->bindParam(':name_departman', $departeman['name_departman']);
    $stmt->bindParam(':text', $text, PDO::PARAM_STR);
    $stmt->bindParam(':time', $time);
    $stmt->bindParam(':status', $status);
    $stmt->execute();
    if ($photo) {
        sendphoto($departeman['idsupport'], $photoid, null);
    }
    if ($video) {
        sendvideo($departeman['idsupport'], $videoid, null);
    }
    $textsuppoer = sprintf($textbotlang['Admin']['reportgroup']['supportMessage'], $from_id, $from_id, $timejalali, $username, $departeman['name_departman'], $text, $caption);
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['support']['answermessage'], 'callback_data' => 'Responsesupport_' . $randomString],
            ],
        ]
    ]);
    sendmessage($departeman['idsupport'], $textsuppoer, $Response, 'HTML');
    sendmessage($from_id, $textbotlang['users']['support']['sentForReview'], $keyboard, 'HTML');
    step("home", $from_id);
    step("home", $departeman['idsupport']);
} elseif (preg_match('/Responsesupport_(\w+)/', $datain, $dataget)) {
    $idtraking = $dataget[1];
    $trakingdetail = select("support_message", "*", "Tracking", $idtraking);
    if ($trakingdetail['status'] == "Answered") {
        sendmessage($from_id, $textbotlang['Admin']['messageBulk']['answeredByOther'], null, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['users']['support']['sendMessageText'], $backuser, 'HTML');
    update("user", "Processing_value", $idtraking, "id", $from_id);
    step("getextsupport", $from_id);
} elseif ($user['step'] == "getextsupport") {
    $trakingdetail = select("support_message", "*", "Tracking", $user['Processing_value']);
    $time = date('Y/m/d H:i:s');
    update("support_message", "status", "Answered", "Tracking", $user['Processing_value']);
    update("support_message", "result", $text, "Tracking", $user['Processing_value']);
    $textSendAdminToUser = sprintf($textbotlang['users']['support']['messageFromAdmin'], $text);
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['support']['answermessage'], 'callback_data' => 'Responsesusera_' . $trakingdetail['Tracking']],
            ],
        ]
    ]);
    sendmessage($trakingdetail['iduser'], $textSendAdminToUser, $Response, 'HTML');
    sendmessage($from_id, $textbotlang['users']['support']['sentSuccess'], null, 'HTML');
    step("home", $from_id);
} elseif (preg_match('/Responsesusera_(\w+)/', $datain, $dataget)) {
    $idtraking = $dataget[1];
    sendmessage($from_id, $textbotlang['users']['support']['sendMessageText'], $backuser, 'HTML');
    update("user", "Processing_value", $idtraking, "id", $from_id);
    step("getextuserfors", $from_id);
} elseif ($user['step'] == "getextuserfors") {
    $trakingdetail = select("support_message", "*", "Tracking", $user['Processing_value']);
    step("home", $from_id);
    $time = date('Y/m/d H:i:s');
    $timejalali = jdate('Y/m/d H:i:s');
    Editmessagetext($from_id, $message_id, $text_inline, json_encode(['inline_keyboard' => []]));
    $randomString = bin2hex(random_bytes(4));
    $stmt = $pdo->prepare("INSERT IGNORE INTO support_message (Tracking,idsupport,iduser,name_departman,text,time,status) VALUES (:Tracking,:idsupport,:iduser,:name_departman,:text,:time,:status)");
    $status = "Customerresponse";
    $stmt->bindParam(':Tracking', $randomString);
    $stmt->bindParam(':idsupport', $trakingdetail['idsupport']);
    $stmt->bindParam(':iduser', $trakingdetail['iduser']);
    $stmt->bindParam(':name_departman', $trakingdetail['name_departman']);
    $stmt->bindParam(':text', $text, PDO::PARAM_STR);
    $stmt->bindParam(':time', $time);
    $stmt->bindParam(':status', $status);
    $stmt->execute();
    $textsuppoer = sprintf($textbotlang['Admin']['reportgroup']['supportMessage2'], $from_id, $from_id, $timejalali, $username, $trakingdetail['name_departman'], $text);
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['support']['answermessage'], 'callback_data' => 'Responsesupport_' . $randomString],
            ],
        ]
    ]);
    if ($photo) {
        sendphoto($trakingdetail['idsupport'], $photoid, null);
    }
    if ($video) {
        sendvideo($trakingdetail['idsupport'], $videoid, null);
    }
    sendmessage($trakingdetail['idsupport'], $textsuppoer, $Response, 'HTML');
    sendmessage($from_id, $textbotlang['users']['support']['sentForRequestReview'], null, 'HTML');
} elseif ($datain == "fqQuestions") {
    sendmessage($from_id, $textbotlang['textbot']['faqDesc'], null, 'HTML');
} elseif ($text == $textbotlang['textbot']['accountWallet'] || $datain == "account" || $text == "/wallet") {
    $dateacc = jdate('Y/m/d');
    $current_time = time();
    $timeacc = jdate('H:i:s', $current_time);
    if ($user['codeInvitation'] == null) {
        $randomString = bin2hex(random_bytes(6));
        update("user", "codeInvitation", $randomString, "id", $from_id);
        $user['codeInvitation'] = $randomString;
    }
    $first_name = htmlspecialchars($first_name);
    $Balanceuser = number_format($user['Balance'], 0);
    if ($user['number'] == "none") {
        $numberphone = $textbotlang['common']['labels']['notSent'];
    } else {
        $numberphone = $user['number'];
    }
    if ($user['number'] == "confrim number by admin") {
        $numberphone = $textbotlang['common']['labels']['confirmedByAdminAlt'];
    } else {
        $numberphone = $numberphone;
    }
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :id_user AND name_product != :mp1 AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold')");
    $stmt->bindValue(':mp1', $textbotlang['common']['labels']['testServiceName'], PDO::PARAM_STR);
    $stmt->bindValue(':id_user', $from_id, PDO::PARAM_INT);
    $stmt->execute();
    $countorder = $stmt->rowCount();
    $stmt = $pdo->prepare("SELECT * FROM Payment_report WHERE id_user = :from_id AND payment_Status = 'paid'");
    $stmt->execute([
        ':from_id' => $from_id
    ]);
    $countpayment = $stmt->rowCount();
    $groupuser = [
        'f' => $textbotlang['common']['roles']['normalAlt'],
        'n' => $textbotlang['common']['roles']['agentAlt'],
        'n2' => $textbotlang['common']['roles']['advancedAgentAlt'],
    ][$user['agent']];
    $userjoin = jdate('Y/m/d H:i:s', $user['register']);
    if (intval($setting['scorestatus']) == 1) {
        $textscore = strtr($textbotlang['users']['affiliates']['accountScore'], ['{score}' => $user['score']]);
    } else {
        $textscore = "";
    }
    $textinvite = "";
    if ($setting['verifybucodeuser'] == "onverify" and $setting['verifystart'] == "onverify") {
        $textscore = sprintf($textbotlang['users']['affiliates']['referralLink'], $usernamebot, $user['codeInvitation']);
    }
    $text_account = sprintf($textbotlang['users']['account']['info'], $from_id, $first_name, $user['codeInvitation'], $numberphone, $userjoin, $Balanceuser, $countorder, $countpayment, $user['affiliatescount'], $groupuser, $textscore, $textinvite, $dateacc, $timeacc);
    if ($datain == "account") {
        Editmessagetext($from_id, $message_id, $text_account, $keyboardPanel);
    } else {
        sendmessage($from_id, $text_account, $keyboardPanel, 'HTML');
    }
    step('home', $from_id);
    return;
} elseif (($text == $textbotlang['textbot']['sell'] || $datain == "buy" || $datain == "buyback" || $text == "/buy" || $text == "buy") && $statusnote) {
    if ($setting['get_number'] == "onAuthenticationphone" && $user['step'] != "get_number" && $user['number'] == "none") {
        sendmessage($from_id, $textbotlang['users']['number']['confirming'], $request_contact, 'HTML');
        step('get_number', $from_id);
    }
    if ($user['number'] == "none" && $setting['get_number'] == "onAuthenticationphone")
        return;
    if (!check_active_btn($setting['keyboardmain'], "text_sell")) {
        sendmessage($from_id, $textbotlang['users']['buttonDisabled'], null, 'HTML');
        return;
    }
    if ($datain == "buy") {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['notestep'], $backuser);
    } elseif ($datain == "buyback") {
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['sell']['notestep'], $backuser, 'HTML');
    } else {
        sendmessage($from_id, $textbotlang['users']['sell']['notestep'], $backuser, 'HTML');
    }
    step("statusnamecustom", $from_id);
    return;
} elseif ($text == $textbotlang['textbot']['sell'] || $datain == "buy" || $datain == "buybacktow" || $datain == "buyback" || $text == "/buy" || $text == "buy" || $user['step'] == "statusnamecustom") {
    if (!check_active_btn($setting['keyboardmain'], "text_sell")) {
        sendmessage($from_id, $textbotlang['users']['buttonDisabled'], null, 'HTML');
        return;
    }
    $locationproduct = $pdo->prepare("SELECT * FROM marzban_panel  WHERE status = 'active' AND (agent = ? OR agent = 'all')");
    $locationproduct->bindValue(1, $user['agent'], PDO::PARAM_STR);
    $locationproduct->execute();
    if (($locationproduct)->rowCount() == 0) {
        sendmessage($from_id, $textbotlang['users']['sell']['nullPanel'], null, 'HTML');
        return;
    }
    if ($setting['get_number'] == "onAuthenticationphone" && $user['step'] != "get_number" && $user['number'] == "none") {
        sendmessage($from_id, $textbotlang['users']['number']['confirming'], $request_contact, 'HTML');
        step('get_number', $from_id);
    }
    if ($user['number'] == "none" && $setting['get_number'] == "onAuthenticationphone")
        return;
    #-----------------------#
    if (($locationproduct)->rowCount() == 1) {
        $location = ($locationproduct)->fetch(PDO::FETCH_ASSOC)['name_panel'];
        $locationproduct = select("marzban_panel", "*", "name_panel", $location, "select");
        if ($locationproduct['hide_user'] != null) {
            $list_user = json_decode($locationproduct['hide_user'], true);
            if (in_array($from_id, $list_user)) {
                sendmessage($from_id, $textbotlang['users']['sell']['nullPanel'], null, 'HTML');
                return;
            }
        }
        $stmt = $pdo->prepare("SELECT * FROM invoice WHERE (status = 'active' OR status = 'end_of_time' OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND Service_location = :mp2");
        $stmt->execute([':mp2' => $location]);
        $countinovoice = $stmt->rowCount();
        if (is_numeric($locationproduct['limit_panel'])) {
            if ($countinovoice >= intval($locationproduct['limit_panel'])) {
                sendmessage($from_id, $textbotlang['users']['sell']['capacityFull'], null, 'HTML');
                return;
            }
        }
        if ($user['step'] == "statusnamecustom") {
            savedata('clear', "nameconfig", $text);
            savedata('save', "name_panel", $location);
            step("home", $from_id);
        } else {
            savedata('clear', "name_panel", $location);
        }
        if ($setting['statuscategory'] == "offcategory") {
            $marzban_list_get = $locationproduct;
            $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
            $custompricevalue = $eextraprice[$user['agent']];
            $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
            $mainvolume = $mainvolume[$user['agent']];
            $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
            $maxvolume = $maxvolume[$user['agent']];
            $nullproduct = select("product", "*", null, null, "count");
            if ($nullproduct == 0) {
                $textcustom = sprintf($textbotlang['users']['sell']['customVolumePrompt3'], $custompricevalue, $mainvolume, $maxvolume);
                sendmessage($from_id, $textcustom, $backuser, 'html');
                step('gettimecustomvol', $from_id);
                return;
            }
            if ($setting['statuscategorygenral'] == "oncategorys") {
                $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
                if ($setting['statusnamecustom'] == 'onnamecustom') {
                    $backtarget = "buyback";
                } else {
                    $backtarget = "backuser";
                }
                if ($datain == "buy") {
                    Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['selectCategory'], KeyboardCategory($location, $user['agent'], $backtarget));
                } else {
                    sendmessage($from_id, $textbotlang['users']['sell']['selectCategory'], KeyboardCategory($location, $user['agent'], $backtarget), 'HTML');
                }
            } else {
                $query = "SELECT * FROM product WHERE (Location = :loc OR Location = '/all') AND agent = :agent";
                $queryParams = [':loc' => $location, ':agent' => $user['agent']];
                $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
                $statuscustomvolume = json_decode($marzban_list_get['customvolume'], true)[$user['agent']];
                if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
                    $datakeyboard = "prodcutservices_";
                } else {
                    $datakeyboard = "prodcutservice_";
                }
                if ($statuscustomvolume == "1" && $marzban_list_get['type'] != "Manualsale") {
                    $statuscustom = true;
                } else {
                    $statuscustom = false;
                }
                $textproduct = $textbotlang['users']['sell']['serviceSelectFirst'];
                if ($datain == "buy") {
                    Editmessagetext($from_id, $message_id, $textproduct, KeyboardProduct($marzban_list_get['name_panel'], $query, $user['pricediscount'], $datakeyboard, $statuscustom, "backuser", null, "customsellvolume", $queryParams));
                } else {
                    sendmessage($from_id, $textproduct, KeyboardProduct($marzban_list_get['name_panel'], $query, $user['pricediscount'], $datakeyboard, $statuscustom, "backuser", null, "customsellvolume", $queryParams), 'HTML');
                }
            }
        } else {
            $nullproduct = select("product", "*", null, null, "count");
            if ($nullproduct == 0) {
                sendmessage($from_id, $textbotlang['users']['sell']['nullProduct'], null, 'HTML');
                return;
            }
            $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
            $statuscustom = false;
            $statuscustomvolume = json_decode($marzban_list_get['customvolume'], true)[$user['agent']];
            if ($statuscustomvolume == "1" && $marzban_list_get['type'] != "Manualsale")
                $statuscustom = true;
            if ($statusnote) {
                $back = "buyback";
            } else {
                $back = "backuser";
            }
            $monthkeyboard = keyboardTimeCategory($marzban_list_get['name_panel'], $user['agent'], "productmonth_", $back, $statuscustom, false);
            if ($datain == "buy" || $datain == "buybacktow") {
                Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['selectDuration'], $monthkeyboard);
            } else {
                sendmessage($from_id, $textbotlang['users']['sell']['selectDuration'], $monthkeyboard, 'HTML');
            }
        }
        return;
    }
    if ($user['step'] == "statusnamecustom") {
        savedata('clear', "nameconfig", $text);
        step("home", $from_id);
    }
    if ($datain == "buy" || $datain == "buybacktow" || $datain == "buyback") {
        Editmessagetext($from_id, $message_id, $textbotlang['textbot']['selectLocation'], $list_marzban_panel_user);
    } else {
        sendmessage($from_id, $textbotlang['textbot']['selectLocation'], $list_marzban_panel_user, 'HTML');
    }
} elseif (preg_match('/^location_(.*)/', $datain, $dataget) || $datain == "backproduct") {
    $userdate = json_decode($user['Processing_value'], true);
    if ($datain != "backproduct") {
        $location = select("marzban_panel", "*", "code_panel", $dataget[1], "select")['name_panel'];
    } else {
        $location = $userdate['name_panel'];
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    $locationproductcount = select("marzban_panel", "*", "name_panel", $location, "count");
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE (status = 'active' OR status = 'end_of_time' OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND  Service_location = :mp2");
    $stmt->execute([':mp2' => $marzban_list_get['name_panel']]);
    $countinovoice = $stmt->rowCount();
    if (is_numeric($marzban_list_get['limit_panel'])) {
        if ($countinovoice >= intval($marzban_list_get['limit_panel'])) {
            sendmessage($from_id, $textbotlang['users']['sell']['panelCapacityFull'], null, 'HTML');
            return;
        }
    }
    if ($statusnote) {
        savedata('save', "name_panel", $location);
    } else {
        savedata('clear', "name_panel", $location);
    }
    $nullproduct = select("product", "*", null, null, "count");
    if ($nullproduct == 0) {
        $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
        $custompricevalue = $eextraprice[$user['agent']];
        $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
        $mainvolume = $mainvolume[$user['agent']];
        $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
        $maxvolume = $maxvolume[$user['agent']];
        $textcustom = sprintf($textbotlang['users']['sell']['customVolumePrompt4'], $custompricevalue, $mainvolume, $maxvolume);
        sendmessage($from_id, $textcustom, $backuser, 'html');
        step('gettimecustomvol', $from_id);
        return;
    }
    if ($setting['statuscategory'] == "offcategory") {
        if ($setting['statuscategorygenral'] == "oncategorys") {
            $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
            Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['selectCategory'], KeyboardCategory($location, $user['agent'], "buybacktow"));
        } else {
            $query = "SELECT * FROM product WHERE (Location = :loc OR Location = '/all') AND agent = :agent";
            $queryParams = [':loc' => $location, ':agent' => $user['agent']];
            $statuscustomvolume = json_decode($marzban_list_get['customvolume'], true)[$user['agent']];
            if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
                $datakeyboard = "prodcutservices_";
            } else {
                $datakeyboard = "prodcutservice_";
            }
            if ($statuscustomvolume == "1" && $marzban_list_get['type'] != "Manualsale") {
                $statuscustom = true;
            } else {
                $statuscustom = false;
            }
            if (isset($userdate['nameconfig'])) {
                $back = "buybacktow";
            } else {
                $back = "buyback";
            }
            Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['serviceSelect'], KeyboardProduct($marzban_list_get['name_panel'], $query, $user['pricediscount'], $datakeyboard, $statuscustom, $back, null, "customsellvolume", $queryParams));
        }
    } else {
        $nullproduct = select("product", "*", null, null, "count");
        if ($nullproduct == 0) {
            sendmessage($from_id, $textbotlang['users']['sell']['nullProduct'], null, 'HTML');
            return;
        }
        $statuscustom = false;
        $statuscustomvolume = json_decode($marzban_list_get['customvolume'], true)[$user['agent']];
        if ($statuscustomvolume == "1" && $marzban_list_get['type'] != "Manualsale")
            $statuscustom = true;
        $monthkeyboard = keyboardTimeCategory($marzban_list_get['name_panel'], $user['agent'], "productmonth_", "buybacktow", $statuscustom, false);
        Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['selectDuration'], $monthkeyboard);
    }
} elseif (preg_match('/^categorynames_(.*)/', $datain, $dataget)) {
    $categorynames = $dataget[1];
    $categorynames = select("category", "remark", "id", $categorynames, "select")['remark'];
    $userdate = json_decode($user['Processing_value'], true);
    if (isset($userdate['monthproduct'])) {
        $query = "SELECT * FROM product WHERE (Location = :loc OR Location = '/all') AND agent = :agent AND category = :category AND Service_time = :stime";
        $queryParams = [':loc' => $userdate['name_panel'], ':agent' => $user['agent'], ':category' => $categorynames, ':stime' => $userdate['monthproduct']];
    } else {
        $query = "SELECT * FROM product WHERE (Location = :loc OR Location = '/all') AND agent = :agent AND category = :category";
        $queryParams = [':loc' => $userdate['name_panel'], ':agent' => $user['agent'], ':category' => $categorynames];
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    $statuscustomvolume = json_decode($marzban_list_get['customvolume'], true)[$user['agent']];
    if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
        $datakeyboard = "prodcutservices_";
    } else {
        $datakeyboard = "prodcutservice_";
    }
    if ($statuscustomvolume == "1" && $marzban_list_get['type'] != "Manualsale") {
        $statuscustom = true;
    } else {
        $statuscustom = false;
    }
    Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['serviceSelectFirst'], KeyboardProduct($marzban_list_get['name_panel'], $query, $user['pricediscount'], $datakeyboard, $statuscustom, "backuser", null, "customsellvolume", $queryParams));
} elseif (preg_match('/^productmonth_(\w+)/', $datain, $dataget)) {
    $monthenumber = $dataget[1];
    $userdate = json_decode($user['Processing_value'], true);
    if (!is_array($userdate) || empty($userdate['name_panel'])) {
        sendmessage($from_id, $textbotlang['users']['sell']['panelUnavailable'], $keyboard, 'HTML');
        step('home', $from_id);
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    if (empty($marzban_list_get)) {
        sendmessage($from_id, $textbotlang['users']['sell']['panelUnavailable'], $keyboard, 'HTML');
        step('home', $from_id);
        return;
    }
    if ($setting['statuscategorygenral'] == "oncategorys") {
        savedata("save", "monthproduct", $monthenumber);
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
        $stmt = $pdo->prepare("SELECT * FROM marzban_panel  WHERE status = 'active' AND (agent = :mp3 OR agent = 'all')");
        $stmt->execute([':mp3' => $user['agent']]);
        $count_panel = $stmt->rowCount();
        if ($count_panel == 1) {
            $back = "buybacktow";
        } else {
            $back = "location_{$marzban_list_get['code_panel']}";
        }
        Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['selectCategory'], KeyboardCategory($marzban_list_get['name_panel'], $user['agent'], $back));
    } else {
        $query = "SELECT * FROM product WHERE (Location = :loc OR Location = '/all') AND agent = :agent AND Service_time = :stime";
        $queryParams = [':loc' => $userdate['name_panel'], ':agent' => $user['agent'], ':stime' => $monthenumber];
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
        $statuscustomvolume = json_decode($marzban_list_get['customvolume'] ?? '[]', true)[$user['agent']] ?? null;
        if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
            $datakeyboard = "prodcutservices_";
        } else {
            $datakeyboard = "prodcutservice_";
        }
        if ($statuscustomvolume == "1" && $marzban_list_get['type'] != "Manualsale") {
            $statuscustom = true;
        } else {
            $statuscustom = false;
        }
        Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['serviceSelectFirst'], KeyboardProduct($marzban_list_get['name_panel'], $query, $user['pricediscount'], $datakeyboard, $statuscustom, "backuser", null, "customsellvolume", $queryParams));
    }
} elseif ($datain == "customsellvolume") {
    $userdate = json_decode($user['Processing_value'], true);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
    $mainvolume = $mainvolume[$user['agent']];
    $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
    $maxvolume = $maxvolume[$user['agent']];
    $textcustom = sprintf($textbotlang['users']['sell']['customVolumePrompt5'], $custompricevalue, $mainvolume, $maxvolume);
    sendmessage($from_id, $textcustom, $backuser, 'html');
    deletemessage($from_id, $message_id);
    step('gettimecustomvol', $from_id);
} elseif ($user['step'] == "gettimecustomvol") {
    $userdate = json_decode($user['Processing_value'], true);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
    $mainvolume = $mainvolume[$user['agent']];
    $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
    $maxvolume = $maxvolume[$user['agent']];
    $maintime = json_decode($marzban_list_get['maintime'], true);
    $maintime = $maintime[$user['agent']];
    $maxtime = json_decode($marzban_list_get['maxtime'], true);
    $maxtime = $maxtime[$user['agent']];
    if ($text > intval($maxvolume) || $text < intval($mainvolume)) {
        $texttime = strtr($textbotlang['users']['customSellVolume']['invalidVolume'], ['{mainvolume}' => $mainvolume, '{maxvolume}' => $maxvolume]);
        sendmessage($from_id, $texttime, $backuser, 'HTML');
        return;
    }
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidVolume'], $backuser, 'HTML');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    update("user", "Processing_value_one", $text, "id", $from_id);
    $textcustom = sprintf($textbotlang['users']['sell']['customTimePrompt2'], $customtimevalueprice, $maintime, $maxtime);
    sendmessage($from_id, $textcustom, $backuser, 'html');
    if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
        step('getvolumecustomusername', $from_id);
    } else {
        step('getvolumecustomuser', $from_id);
    }
} elseif ($user['step'] == "getvolumecustomusername" || preg_match('/^prodcutservices_(.*)/', $datain, $dataget)) {
    $prodcut = $dataget[1];
    $userdate = json_decode($user['Processing_value'], true);
    if ($user['step'] == "getvolumecustomusername") {
        if (!ctype_digit($text)) {
            sendmessage($from_id, $textbotlang['common']['invalidTime'], $backuser, 'HTML');
            return;
        }
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
        $maintime = json_decode($marzban_list_get['maintime'], true);
        $maintime = $maintime[$user['agent']];
        $maxtime = json_decode($marzban_list_get['maxtime'], true);
        $maxtime = $maxtime[$user['agent']];
        if (intval($text) > intval($maxtime) || intval($text) < intval($maintime)) {
            $texttime = strtr($textbotlang['users']['customSellVolume']['invalidTimeRange'], ['{maintime}' => $maintime, '{maxtime}' => $maxtime]);
            sendmessage($from_id, $texttime, $backuser, 'HTML');
            return;
        }
        $customvalue = "customvolume_" . $text . "_" . $user['Processing_value_one'];
        update("user", "Processing_value_one", $customvalue, "id", $from_id);
        step('endstepusers', $from_id);
    } else {
        update("user", "Processing_value_one", $prodcut, "id", $from_id);
        step('endstepuser', $from_id);
        deletemessage($from_id, $message_id);
    }
    sendmessage($from_id, $textbotlang['users']['selectusername'], $backuser, 'html');
} elseif ($user['step'] == "endstepuser" || $user['step'] == "endstepusers" || preg_match('/prodcutservice_(.*)/', $datain, $dataget) || $user['step'] == "getvolumecustomuser") {
    $userdate = json_decode($user['Processing_value'], true);
    if (!is_array($userdate) || empty($userdate['name_panel'])) {
        sendmessage($from_id, $textbotlang['users']['sell']['panelUnavailable'], $keyboard, 'HTML');
        step('home', $from_id);
        return;
    }
    if ($user['step'] == "getvolumecustomuser") {
        if (!ctype_digit($text)) {
            sendmessage($from_id, $textbotlang['users']['customSellVolume']['invalidTime'], $backuser, 'HTML');
            return;
        }
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
        $maintime = json_decode($marzban_list_get['maintime'], true);
        $maintime = $maintime[$user['agent']];
        $maxtime = json_decode($marzban_list_get['maxtime'], true);
        $maxtime = $maxtime[$user['agent']];
        if (intval($text) > intval($maxtime) || intval($text) < intval($maintime)) {
            $texttime = strtr($textbotlang['users']['customSellVolume']['invalidTimeRange'], ['{maintime}' => $maintime, '{maxtime}' => $maxtime]);
            sendmessage($from_id, $texttime, $backuser, 'HTML');
            return;
        }
        $prodcut = "customvolume_" . $text . "_" . $user['Processing_value_one'];
    } elseif ($user['step'] == "endstepusers" || $user['step'] == "endstepuser") {
        $prodcut = $user['Processing_value_one'];
    } else {
        $prodcut = $dataget[1];
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    if ($marzban_list_get['status'] == "disable") {
        sendmessage($from_id, $textbotlang['users']['sell']['panelUnavailable'], $backuser, 'html');
        step("home", $from_id);
        return;
    }
    if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
        if (!preg_match('~(?!_)^[a-z][a-z\d_]{2,32}(?<!_)$~i', $text)) {
            sendmessage($from_id, $textbotlang['users']['invalidusername'], $backuser, 'HTML');
            return;
        }
        $loc = $user['Processing_value_one'];
    } else {
        $loc = $prodcut;
    }
    update("user", "Processing_value_one", $loc, "id", $from_id);
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    $parts = explode("_", $loc);
    if ($parts[0] == "customvolume") {
        $info_product['Volume_constraint'] = $parts[2];
        $info_product['name_product'] = $textbotlang['users']['customSellVolume']['title'];
        $info_product['code_product'] = $textbotlang['users']['customSellVolume']['title'];
        $info_product['Service_time'] = $parts[1];
        $info_product['price_product'] = ($parts[2] * $custompricevalue) + ($parts[1] * $customtimevalueprice);
    } else {
        $info_product = $pdo->prepare("SELECT * FROM product WHERE code_product = :code_product AND (Location = :location OR Location = '/all') LIMIT 1");
        $info_product->bindValue(':code_product', $loc, PDO::PARAM_STR);
        $info_product->bindValue(':location', $userdate['name_panel'], PDO::PARAM_STR);
        $info_product->execute();
        $info_product = $info_product->fetch(PDO::FETCH_ASSOC);
    }
    if (!isset($info_product['price_product'])) {
        sendmessage($from_id, $textbotlang['users']['Balance']['confirmError'], $keyboard, 'HTML');
        return;
    }
    if (intval($user['pricediscount']) != 0) {
        $resultper = ($info_product['price_product'] * $user['pricediscount']) / 100;
        $info_product['price_product'] = $info_product['price_product'] - $resultper;
    }
    $randomString = bin2hex(random_bytes(2));
    $text = strtolower($text);
    $username_ac = generateUsername($from_id, $marzban_list_get['MethodUsername'], $username, $randomString, $text, $marzban_list_get['namecustom'], $user['namecustom']);
    $username_ac = strtolower($username_ac);
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $username_ac);
    $random_number = rand(1000000, 9999999);
    if (isset($DataUserOut['username']) || rowExists("invoice", "username", $username_ac)) {
        $username_ac = $random_number . "_" . $username_ac;
    }
    if (isset($username_ac))
        update("user", "Processing_value_tow", $username_ac, "id", $from_id);
    if (intval($info_product['Volume_constraint']) == 0)
        $info_product['Volume_constraint'] = $textbotlang['users']['status']['unlimited'];
    if (intval($info_product['Service_time']) == 0)
        $info_product['Service_time'] = $textbotlang['users']['status']['unlimited'];
    $info_product_price_product = number_format($info_product['price_product']);
    $userBalance = number_format($user['Balance']);
    $replacements = [
        '{username}' => $username_ac,
        '{name_product}' => $info_product['name_product'],
        '{Service_time}' => $info_product['Service_time'],
        '{note}' => $info_product['note'] ?? '',
        '{price}' => $info_product_price_product,
        '{Volume}' => $info_product['Volume_constraint'],
        '{userBalance}' => $userBalance
    ];
    $textin = strtr($textbotlang['textbot']['preInvoice'], $replacements);
    if (intval($info_product['Volume_constraint']) == 0) {
        $textin = str_replace($textbotlang['common']['units']['gb'], "", $textin);
    }
    if ($user['step'] != "getvolumecustomuser" && !in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
        Editmessagetext($from_id, $message_id, $textin, $payment);
    } else {
        sendmessage($from_id, $textin, $payment, 'HTML');
    }
    step('payment', $from_id);
} elseif ($user['step'] == "payment" && ($datain == "confirmandgetservice" || $datain == "confirmandgetserviceDiscount")) {
    $userdate = json_decode($user['Processing_value'], true);
    Editmessagetext($from_id, $message_id, $text_inline, json_encode(['inline_keyboard' => []]));
    // $pats for customm service
    $parts = explode("_", $user['Processing_value_one']);
    // $partsdic for discount value
    $partsdic = explode("_", $user['Processing_value_four']);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    if ($marzban_list_get['status'] == "disable") {
        sendmessage($from_id, $textbotlang['users']['sell']['panelUnavailable'], $backuser, 'html');
        step("home", $from_id);
        return;
    }
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    if ($parts[0] == "customvolume") {
        $info_product['Volume_constraint'] = $parts[2];
        $info_product['name_product'] = $textbotlang['users']['customSellVolume']['title'];
        $info_product['code_product'] = "customvolume";
        $info_product['Service_time'] = $parts[1];
        $info_product['price_product'] = ($parts[2] * $custompricevalue) + ($parts[1] * $customtimevalueprice);
        $info_product['data_limit_reset'] = "no_reset";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE code_product = :code_product AND (Location = :location OR Location = '/all') LIMIT 1");
        $stmt->execute([
            ':code_product' => $user['Processing_value_one'],
            ':location' => $userdate['name_panel']
        ]);
        $info_product = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (!isset($info_product['price_product']))
        return;
    if ($datain == "confirmandgetserviceDiscount") {
        $discountcode = select("DiscountSell", "*", "codeDiscount", $partsdic[0], "count");
        if ($discountcode == 0) {
            sendmessage($from_id, $textbotlang['users']['Discount']['notAllowed'], null, 'HTML');
            return;
        }
        $priceproduct = $partsdic[1];
    } else {
        $priceproduct = $info_product['price_product'];
    }
    $username_ac = strtolower($user['Processing_value_tow']);
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $username_ac);
    if (isset($DataUserOut['username']) || rowExists("invoice", "username", $username_ac)) {
        sendmessage($from_id, $textbotlang['users']['sell']['restartProcess'], null, 'HTML');
        return;
    }
    $date = time();
    $randomString = bin2hex(random_bytes(4));
    $random_number = rand(1000000, 9999999);
    if (rowExists("invoice", "id_invoice", $randomString)) {
        $randomString = $random_number . $randomString;
    }
    if ($marzban_list_get['type'] == "Manualsale") {
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
        $stmt = $pdo->prepare("SELECT * FROM manualsell WHERE codepanel = :codepanel AND codeproduct = :codeproduct AND status = 'active'");
        $stmt->bindParam(':codepanel', $marzban_list_get['code_panel']);
        $stmt->bindParam(':codeproduct', $info_product['code_product']);
        $stmt->execute();
        $configexits = $stmt->rowCount();
        if (intval($configexits) == 0) {
            sendmessage($from_id, $textbotlang['users']['sell']['stockFinishedBuyAnother'], null, 'HTML');
            return;
        }
    }
    if (intval($user['pricediscount']) != 0) {
        $result = ($priceproduct * $user['pricediscount']) / 100;
        $priceproduct = $priceproduct - $result;
        sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
    }
    $notifctions = json_encode(array(
        'volume' => false,
        'time' => false,
    ));
    $stmt = $pdo->prepare("INSERT IGNORE INTO invoice (id_user, id_invoice, username,time_sell, Service_location, name_product, price_product, Volume, Service_time,Status,note,refral,notifctions) VALUES (?,  ?, ?, ?, ?, ?, ?,?,?,?,?,?,?)");
    $Status = "unpaid";
    $stmt->execute([$from_id, $randomString, $username_ac, $date, $marzban_list_get['name_panel'], $info_product['name_product'], $priceproduct, $info_product['Volume_constraint'], $info_product['Service_time'], $Status, $userdate['nameconfig'] ?? null, $user['affiliates'], $notifctions]);
    if ($priceproduct > $user['Balance'] && $user['agent'] != "n2" && intval($priceproduct) != 0) {
        $marzbandirectpay = select("shopSetting", "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        $Balance_prim = $priceproduct - $user['Balance'];
        if ($Balance_prim <= 1)
            $Balance_prim = 0;
        if ($marzbandirectpay == "offdirectbuy") {
            $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']]);
            $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']]);
            $bakinfos = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "account"],
                    ]
                ]
            ]);
            Editmessagetext($from_id, $message_id, sprintf($textbotlang['users']['Balance']['insufficientbalance'], $minbalance, $maxbalance), $bakinfos, 'HTML');
            step('getprice', $from_id);
        } else {
            update("user", "Processing_value", $Balance_prim, "id", $from_id);
            sendmessage($from_id, $textbotlang['users']['sell']['noCredit'], $step_payment, 'HTML');
            step('get_step_payment', $from_id);
            update("user", "Processing_value_one", $username_ac, "id", $from_id);
            update("user", "Processing_value_tow", "getconfigafterpay", "id", $from_id);
            if ($datain == "confirmandgetserviceDiscount")
                update("user", "Processing_value_four", "dis_{$partsdic[0]}", "id", $from_id);
        }
        return;
    }
    if (intval($user['maxbuyagent']) != 0 and $user['agent'] == "n2") {
        if (intval($user['Balance'] - $priceproduct) < intval("-" . $user['maxbuyagent'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['maxpurchasereached'], null, 'HTML');
            return;
        }
    }
    Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['creating'], null);
    if ($datain == "confirmandgetserviceDiscount") {
        $SellDiscountlimit = select("DiscountSell", "*", "codeDiscount", $partsdic[0], "select");
        if ($SellDiscountlimit != false) {
            $value = intval($SellDiscountlimit['usedDiscount']) + 1;
            $stmt = $pdo->prepare("INSERT INTO Giftcodeconsumed (id_user,code) VALUES (?,?)");
            $stmt->execute([$from_id, $partsdic[0]]);
            update("DiscountSell", "usedDiscount", $value, "codeDiscount", $partsdic[0]);
            $text_report = strtr($textbotlang['Admin']['reportgroup']['discountUsed'], ['{username}' => $username, '{from_id}' => $from_id, '{discount_code}' => $partsdic[0]]);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $otherreport,
                    'text' => $text_report,
                    'parse_mode' => "HTML"
                ]);
            }
        }
    }
    $datetimestep = strtotime("+" . $info_product['Service_time'] . "days");
    if ($info_product['Service_time'] == 0) {
        $datetimestep = 0;
    } else {
        $datetimestep = strtotime(date("Y-m-d H:i:s", $datetimestep));
    }
    $datac = array(
        'expire' => $datetimestep,
        'data_limit' => $info_product['Volume_constraint'] * pow(1024, 3),
        'from_id' => $from_id,
        'username' => $username,
        'type' => 'buy'
    );
    $Shoppinginfo = [
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['help']['btninlinebuy'], 'callback_data' => "helpbtn"],
            ]
        ]
    ];
    $dataoutput = $ManagePanel->createUser($marzban_list_get['name_panel'], $info_product['code_product'], $username_ac, $datac);
    if (!isset($dataoutput['username']) || $dataoutput['username'] === null || $dataoutput['username'] === '') {
        $errorMessage = $dataoutput['msg'] ?? 'unknown error';
        if (is_array($errorMessage) || is_object($errorMessage)) {
            $errorMessage = json_encode($errorMessage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $errorMessage = (string) $errorMessage;
        }
        $dataoutput['msg'] = $errorMessage;
        sendmessage($from_id, $textbotlang['users']['sell']['errorConfig'], $keyboard, 'HTML');
        $texterros = sprintf($textbotlang['Admin']['reportgroup']['errorSubscriptionCreate'], $dataoutput['msg'], $from_id, $username, $marzban_list_get['name_panel']);
        if (strlen($setting['Channel_Report']) > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $errorreport,
                'text' => $texterros,
                'parse_mode' => "HTML"
            ]);
        }
        step('home', $from_id);
        return;
    }
    update("invoice", "Status", "active", "username", $username_ac);
    $output_config_link = "";
    $config = "";
    $output_config_link = $marzban_list_get['sublink'] == "onsublink" ? $dataoutput['subscription_url'] : "";
    if ($marzban_list_get['config'] == "onconfig" && is_array($dataoutput['configs'])) {
        foreach ($dataoutput['configs'] as $link) {
            $config .= "\n" . $link;
        }
    }
    $Shoppinginfo = json_encode($Shoppinginfo);
    $textbotlang['textbot']['afterPay'] = $marzban_list_get['type'] == "Manualsale" ? $textbotlang['textbot']['manual'] : $textbotlang['textbot']['afterPay'];
    $textbotlang['textbot']['afterPay'] = $marzban_list_get['type'] == "WGDashboard" ? $textbotlang['textbot']['wgDashboard'] : $textbotlang['textbot']['afterPay'];
    $textbotlang['textbot']['afterPay'] = $marzban_list_get['type'] == "ibsng" || $marzban_list_get['type'] == "mikrotik" ? $textbotlang['textbot']['afterPayIbsng'] : $textbotlang['textbot']['afterPay'];
    if (intval($info_product['Service_time']) == 0)
        $info_product['Service_time'] = $textbotlang['users']['status']['unlimited'];
    if (intval($info_product['Volume_constraint']) == 0)
        $info_product['Volume_constraint'] = $textbotlang['users']['status']['unlimited'];
    $textcreatuser = str_replace('{username}', "<code>{$dataoutput['username']}</code>", $textbotlang['textbot']['afterPay']);
    $textcreatuser = str_replace('{name_service}', $info_product['name_product'], $textcreatuser);
    $textcreatuser = str_replace('{location}', $marzban_list_get['name_panel'], $textcreatuser);
    $textcreatuser = str_replace('{day}', $info_product['Service_time'], $textcreatuser);
    $textcreatuser = str_replace('{volume}', $info_product['Volume_constraint'], $textcreatuser);
    $textcreatuser = str_replace('{config}', "<code>{$output_config_link}</code>", $textcreatuser);
    $textcreatuser = str_replace('{links}', $config, $textcreatuser);
    $textcreatuser = str_replace('{links2}', $output_config_link, $textcreatuser);
    if (intval($info_product['Volume_constraint']) == 0) {
        $textcreatuser = str_replace($textbotlang['common']['units']['gigabyte'], "", $textcreatuser);
    }
    if ($marzban_list_get['type'] == "Manualsale" || $marzban_list_get['type'] == "ibsng" || $marzban_list_get['type'] == "mikrotik") {
        $textcreatuser = str_replace('{password}', $dataoutput['subscription_url'], $textcreatuser);
        update("invoice", "user_info", $dataoutput['subscription_url'], "id_invoice", $randomString);
    }
    sendMessageService($marzban_list_get, $dataoutput['configs'], $output_config_link, $dataoutput['username'], $Shoppinginfo, $textcreatuser, $randomString);
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboard, 'HTML');
    if (intval($priceproduct) != 0) {
        $Balance_prim = $user['Balance'] - $priceproduct;
        update("user", "Balance", $Balance_prim, "id", $from_id);
    }
    if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customTextSequential', 'usernameSequential', 'numericIdSequential', 'agentCustomTextSequential'], true)) {
        $value = intval($user['number_username']) + 1;
        update("user", "number_username", $value, "id", $from_id);
        if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customTextSequential', 'agentCustomTextSequential'], true)) {
            $value = intval($setting['numbercount']) + 1;
            update("setting", "numbercount", $value);
        }
    }
    $affiliatescommission = select("affiliates", "*", null, null, "select");
    $marzbanporsant_one_buy = $affiliatescommission;
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE name_product != :name_product  AND id_user = :id_user AND Status != 'Unpaid'");
    $stmt->bindParam(':id_user', $from_id);
    $stmt->bindParam(':name_product', $textbotlang['common']['labels']['testServiceName']);
    $stmt->execute();
    $countinvoice = $stmt->rowCount();
    if ($affiliatescommission['status_commission'] == "oncommission" && ($user['affiliates'] != null && intval($user['affiliates']) != 0)) {
        if ($marzbanporsant_one_buy['porsant_one_buy'] == "on_buy_porsant") {
            if ($countinvoice == 1) {
                $result = ($priceproduct * $setting['affiliatespercentage']) / 100;
                $user_Balance = select("user", "*", "id", $user['affiliates'], "select");
                $Balance_prim = $user_Balance['Balance'] + $result;
                if (intval($setting['scorestatus']) == 1 and !in_array($user['affiliates'], $admin_ids)) {
                    sendmessage($user['affiliates'], $textbotlang['users']['affiliates']['pointsEarned2Alt'], null, 'html');
                    $scorenew = $user_Balance['score'] + 2;
                    update("user", "score", $scorenew, "id", $user['affiliates']);
                }
                update("user", "Balance", $Balance_prim, "id", $user['affiliates']);
                $result = number_format($result);
                $dateacc = date('Y/m/d H:i:s');
                $textadd = sprintf($textbotlang['users']['affiliates']['commissionPaid'], $result);
                $textreportport = sprintf($textbotlang['Admin']['reportgroup']['commissionPaid'], $result, $user['affiliates'], $from_id, $dateacc);
                if (strlen($setting['Channel_Report']) > 0) {
                    telegram('sendmessage', [
                        'chat_id' => $setting['Channel_Report'],
                        'message_thread_id' => $porsantreport,
                        'text' => $textreportport,
                        'parse_mode' => "HTML"
                    ]);
                }
                sendmessage($user['affiliates'], $textadd, null, 'HTML');
            }
        } else {

            $result = ($priceproduct * $setting['affiliatespercentage']) / 100;
            $user_Balance = select("user", "*", "id", $user['affiliates'], "select");
            $Balance_prim = $user_Balance['Balance'] + $result;
            if (intval($setting['scorestatus']) == 1 and !in_array($user['affiliates'], $admin_ids)) {
                sendmessage($user['affiliates'], $textbotlang['users']['affiliates']['pointsEarned2Alt'], null, 'html');
                $scorenew = $user_Balance['score'] + 2;
                update("user", "score", $scorenew, "id", $user['affiliates']);
            }
            update("user", "Balance", $Balance_prim, "id", $user['affiliates']);
            $result = number_format($result);
            $dateacc = date('Y/m/d H:i:s');
            $textadd = sprintf($textbotlang['users']['affiliates']['commissionPaid2'], $result);
            $textreportport = sprintf($textbotlang['Admin']['reportgroup']['commissionPaid2'], $result, $user['affiliates'], $from_id, $dateacc);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $porsantreport,
                    'text' => $textreportport,
                    'parse_mode' => "HTML"
                ]);
            }
            sendmessage($user['affiliates'], $textadd, null, 'HTML');
        }
    }
    if (intval($setting['scorestatus']) == 1 and !in_array($from_id, $admin_ids)) {
        sendmessage($from_id, $textbotlang['users']['affiliates']['pointsEarned1Alt'], null, 'html');
        $scorenew = $user['score'] + 1;
        update("user", "score", $scorenew, "id", $from_id);
    }
    $balanceformatsell = number_format(select("user", "Balance", "id", $from_id, "select")['Balance'], 0);
    $textonebuy = "";
    if ($countinvoice == 1) {
        $textonebuy = $textbotlang['common']['labels']['firstPurchaseAlt'];
    }
    $balanceformatsellbefore = number_format($user['Balance'], 0);
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['manageUser']['manageUserBtn'], 'callback_data' => 'manageuser_' . $from_id],
            ],
        ]
    ]);
    $timejalali = jdate('Y/m/d H:i:s');
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['accountCreated'], $textonebuy, $from_id, $username, $username_ac, $first_name, $userdate['name_panel'], $info_product['name_product'], $info_product['Service_time'], $info_product['Volume_constraint'], $balanceformatsellbefore, $balanceformatsell, $randomString, $user['agent'], $user['number'], $info_product['category'] ?? '', $info_product['price_product'], $priceproduct, $timejalali);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $buyreport,
            'text' => $text_report,
            'parse_mode' => "HTML",
            'reply_markup' => $Response
        ]);
    }
    update("user", "Processing_value_four", "none", "id", $from_id);
    step('home', $from_id);
} elseif ($datain == "aptdc") {
    sendmessage($from_id, $textbotlang['users']['Discount']['getcodesell'], $backuser, 'HTML');
    step('getcodesellDiscount', $from_id);
    deletemessage($from_id, $message_id);
} elseif ($user['step'] == "getcodesellDiscount") {
    $userdate = json_decode($user['Processing_value'], true);
    if (!isset($userdate['name_panel'])) {
        sendmessage($from_id, $textbotlang['users']['sell']['restartFromStart'], $keyboard, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM product WHERE code_product = :code_product AND (Location = :Location or Location = '/all') LIMIT 1");
    $stmt->bindParam(':code_product', $user['Processing_value_one'], PDO::PARAM_STR);
    $stmt->bindParam(':Location', $userdate['name_panel'], PDO::PARAM_STR);
    $stmt->execute();
    $info_product = $stmt->fetch(PDO::FETCH_ASSOC);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $userdate['name_panel'], "select");
    if (!rowExists("DiscountSell", "codeDiscount", $text)) {
        sendmessage($from_id, $textbotlang['users']['Discount']['notcode'], $backuser, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM DiscountSell WHERE (code_product = :code_product OR code_product = 'all') AND (code_panel = :code_panel OR code_panel = '/all') AND codeDiscount = :codeDiscount AND (agent = :agent OR agent = 'allusers') AND (type = 'all' OR type = 'buy')");
    $stmt->bindParam(':code_product', $info_product['code_product'], PDO::PARAM_STR);
    $stmt->bindParam(':code_panel', $marzban_list_get['code_panel'], PDO::PARAM_STR);
    $stmt->bindParam(':agent', $user['agent'], PDO::PARAM_STR);
    $stmt->bindParam(':codeDiscount', $text, PDO::PARAM_STR);
    $stmt->execute();
    $SellDiscountlimit = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT * FROM Giftcodeconsumed WHERE id_user = :from_id AND code = :code");
    $stmt->bindParam(':from_id', $from_id, PDO::PARAM_STR);
    $stmt->bindParam(':code', $text, PDO::PARAM_STR);
    $stmt->execute();
    $Checkcodesql = $stmt->rowCount();
    if ($SellDiscountlimit == 0) {
        sendmessage($from_id, $textbotlang['users']['Discount']['invalidCode'], null, 'HTML');
        return;
    }
    if (intval($SellDiscountlimit['time']) != 0 and time() >= intval($SellDiscountlimit['time'])) {
        sendmessage($from_id, $textbotlang['users']['Discount']['expired'], null, 'HTML');
        return;
    }
    if (($SellDiscountlimit['limitDiscount'] <= $SellDiscountlimit['usedDiscount'])) {
        sendmessage($from_id, $textbotlang['users']['Discount']['errorLimit'], null, 'HTML');
        return;
    }
    if ($Checkcodesql >= $SellDiscountlimit['useuser']) {
        $textoncode = strtr($textbotlang['users']['Discount']['useLimit'], ['{useuser}' => $SellDiscountlimit['useuser']]);
        sendmessage($from_id, $textoncode, $keyboard, 'HTML');
        step('home', $from_id);
        return;
    }
    if ($SellDiscountlimit['usefirst'] == "1") {
        $countinvoice = $pdo->prepare("SELECT * FROM invoice WHERE id_user = ? AND name_product != ? AND  (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold')");
        $countinvoice->execute([$from_id, $textbotlang['common']['labels']['testServiceName']]);
        if (($countinvoice)->rowCount() != 0) {
            sendmessage($from_id, $textbotlang['users']['Discount']['firstdiscount'], null, 'HTML');
            return;
        }
    }
    sendmessage($from_id, strtr($textbotlang['users']['Discount']['applied'], ['{discount_price}' => $SellDiscountlimit['price']]), null, 'HTML');
    step('payment', $from_id);
    $parts = explode("_", $user['Processing_value_one']);
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    if ($parts[0] == "customvolume") {
        $info_product['Volume_constraint'] = $parts[2];
        $info_product['name_product'] = $textbotlang['users']['customSellVolume']['title'];
        $info_product['code_product'] = $textbotlang['users']['customSellVolume']['title'];
        $info_product['Service_time'] = $parts[1];
        $info_product['price_product'] = ($parts[2] * $custompricevalue) + ($parts[1] * $customtimevalueprice);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE code_product = :code_product AND (Location = :location OR Location = '/all') LIMIT 1");
        $stmt->bindValue(':code_product', $user['Processing_value_one'], PDO::PARAM_STR);
        $stmt->bindValue(':location', $userdate['name_panel'], PDO::PARAM_STR);
        $stmt->execute();
        $info_product = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    $result = ($SellDiscountlimit['price'] / 100) * $info_product['price_product'];

    $info_productmain = $info_product['price_product'];
    $info_product['price_product'] = $info_product['price_product'] - $result;
    $info_product['price_product'] = round($info_product['price_product']);
    if ($info_product['Service_time'] == 0)
        $info_product['Service_time'] = $textbotlang['users']['status']['unlimited'];
    if (intval($info_product['Volume_constraint']) == 0)
        $info_product['Volume_constraint'] = $textbotlang['users']['status']['unlimited'];
    if ($info_product['price_product'] < 0)
        $info_product['price_product'] = 0;
    $textin = sprintf($textbotlang['users']['sell']['preInvoice'], $user['Processing_value_tow'], $info_product['name_product'], $info_product['Service_time'], $info_productmain, $info_product['price_product'], $info_product['Volume_constraint'], $user['Balance']);
    $paymentDiscount = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['keyboard']['payAndGetService'], 'callback_data' => "confirmandgetserviceDiscount"]],
            [['text' => $textbotlang['users']['backbtn'], 'callback_data' => "backuser"]]
        ]
    ]);
    $parametrsendvalue = $text . "_" . $info_product['price_product'];
    update("user", "Processing_value_four", $parametrsendvalue, "id", $from_id);
    sendmessage($from_id, $textin, $paymentDiscount, 'HTML');
} elseif ($text == $textbotlang['keyboard']['bulkPurchase'] || $datain == "kharidanbuh") {
    if ($setting['bulkbuy'] == "offbulk") {
        sendmessage($from_id, $textbotlang['users']['Major']['disabled'], null, 'HTML');
        return;
    }
    $PaySetting = select("PaySetting", "*", "NamePay", "minbalancebuybulk", "select")['ValuePay'];
    if ($user['Balance'] < $PaySetting) {
        sendmessage($from_id, strtr($textbotlang['users']['Major']['minBalance'], ['{PaySetting}' => $PaySetting]), null, 'HTML');
        return;
    }
    $locationproduct = $pdo->prepare("SELECT * FROM marzban_panel");
    $locationproduct->execute();
    if (($locationproduct)->rowCount() == 0) {
        sendmessage($from_id, $textbotlang['users']['sell']['nullPanel'], null, 'HTML');
        return;
    }
    if ($setting['get_number'] == "onAuthenticationphone" && $user['step'] != "get_number" && $user['number'] == "none") {
        sendmessage($from_id, $textbotlang['users']['number']['confirming'], $request_contact, 'HTML');
        step('get_number', $from_id);
    }
    if ($user['number'] == "none" && $setting['get_number'] == "onAuthenticationphone")
        return;
    #-----------------------#
    if ($datain == "kharidanbuh") {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['Major']['title'], $backuser, 'HTML');
    } else {
        sendmessage($from_id, $textbotlang['users']['Major']['title'], $backuser, 'HTML');
    }
    step('getcountconfig', $from_id);
} elseif ($user['step'] == "getcountconfig") {
    if (intval($text) > 15 || intval($text) < 1)
        return sendmessage($from_id, $textbotlang['common']['invalidInput'], $backuser, 'HTML');
    if (!is_numeric($text))
        return sendmessage($from_id, $textbotlang['users']['Balance']['errorprice'], null, 'HTML');
    sendmessage($from_id, $textbotlang['textbot']['selectLocation'], $list_marzban_panel_userom, 'HTML');
    update("user", "Processing_value_four", $text, "id", $from_id);
    step('home', $from_id);
} elseif (preg_match('/^locationom_(.*)/', $datain, $dataget)) {
    $location = select("marzban_panel", "*", "code_panel", $dataget[1], "select")['name_panel'];
    $marzban_list_get = select("marzban_panel", "*", "code_panel", $dataget[1], "select");
    $nullproduct = select("product", "*", null, null, "count");
    if ($nullproduct == 0) {
        sendmessage($from_id, $textbotlang['users']['sell']['nullProduct'], null, 'HTML');
        return;
    }
    update("user", "Processing_value", $location, "id", $from_id);
    $statuscustomvolume = json_decode($marzban_list_get['customvolume'], true)[$user['agent']];
    if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
        $datakeyboard = "prodcutservicesom_";
    } else {
        $datakeyboard = "prodcutserviceom_";
    }
    if ($statuscustomvolume == "1" && $marzban_list_get['type'] != "Manualsale") {
        $statuscustom = true;
    } else {
        $statuscustom = false;
    }
    $query = "SELECT * FROM product WHERE (Location = :loc OR Location = '/all') AND agent = :agent";
    $queryParams = [':loc' => $location, ':agent' => $user['agent']];
    Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['serviceSelect'], KeyboardProduct($marzban_list_get['name_panel'], $query, $user['pricediscount'], $datakeyboard, $statuscustom, "backuser", null, "customsellvolumeom", $queryParams));
} elseif ($datain == "customsellvolumeom") {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $textcustom = sprintf($textbotlang['users']['sell']['volumePrompt'], $custompricevalue);
    sendmessage($from_id, $textcustom, $backuser, 'html');
    deletemessage($from_id, $message_id);
    step('gettimecustomvolom', $from_id);
} elseif ($user['step'] == "gettimecustomvolom") {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    $mainvolume = json_decode($marzban_list_get['mainvolume'], true);
    $mainvolume = $mainvolume[$user['agent']];
    $maxvolume = json_decode($marzban_list_get['maxvolume'], true);
    $maxvolume = $maxvolume[$user['agent']];
    $maintime = json_decode($marzban_list_get['maintime'], true);
    $maintime = $maintime[$user['agent']];
    $maxtime = json_decode($marzban_list_get['maxtime'], true);
    $maxtime = $maxtime[$user['agent']];
    if ($text > intval($maxvolume) || $text < intval($mainvolume)) {
        $texttime = strtr($textbotlang['users']['customSellVolume']['invalidVolume'], ['{mainvolume}' => $mainvolume, '{maxvolume}' => $maxvolume]);
        sendmessage($from_id, $texttime, $backuser, 'HTML');
        return;
    }
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidVolume'], $backuser, 'HTML');
        return;
    }
    update("user", "Processing_value_one", $text, "id", $from_id);
    $textcustom = sprintf($textbotlang['users']['sell']['timePrompt'], $customtimevalueprice, $maintime, $maxtime);
    sendmessage($from_id, $textcustom, $backuser, 'html');
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
        step('getvolumecustomusernameom', $from_id);
    } else {
        step('getvolumecustomuserom', $from_id);
    }
} elseif ($user['step'] == "getvolumecustomusernameom" || preg_match('/^prodcutservicesom_(.*)/', $datain, $dataget)) {
    $prodcut = $dataget[1];
    if ($user['step'] == "getvolumecustomusernameom") {
        if (!ctype_digit($text)) {
            sendmessage($from_id, $textbotlang['users']['customSellVolume']['invalidTime'], $backuser, 'HTML');
            return;
        }
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
        $maintime = json_decode($marzban_list_get['maintime'], true);
        $maintime = $maintime[$user['agent']];
        $maxtime = json_decode($marzban_list_get['maxtime'], true);
        $maxtime = $maxtime[$user['agent']];
        if (intval($text) > intval($maxtime) || intval($text) < intval($maintime)) {
            $texttime = strtr($textbotlang['users']['customSellVolume']['invalidTimeRange'], ['{maintime}' => $maintime, '{maxtime}' => $maxtime]);
            sendmessage($from_id, $texttime, $backuser, 'HTML');
            return;
        }
        $customvalue = "customvolume_" . $text . "_" . $user['Processing_value_one'];
        update("user", "Processing_value_one", $customvalue, "id", $from_id);
        step('endstepusersom', $from_id);
    } else {
        update("user", "Processing_value_one", $prodcut, "id", $from_id);
        step('endstepuserom', $from_id);
    }
    sendmessage($from_id, $textbotlang['users']['selectusername'], $backuser, 'html');
} elseif ($user['step'] == "endstepuserom" || $user['step'] == "endstepusersom" || preg_match('/prodcutserviceom_(.*)/', $datain, $dataget) || $user['step'] == "getvolumecustomuserom") {
    if ($user['step'] == "getvolumecustomuserom") {
        if (!ctype_digit($text)) {
            sendmessage($from_id, $textbotlang['users']['customSellVolume']['invalidTime'], $backuser, 'HTML');
            return;
        }
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
        $maintime = json_decode($marzban_list_get['maintime'], true);
        $maintime = $maintime[$user['agent']];
        $maxtime = json_decode($marzban_list_get['maxtime'], true);
        $maxtime = $maxtime[$user['agent']];
        if (intval($text) > $maxtime || intval($text) < $maintime) {
            $texttime = strtr($textbotlang['users']['customSellVolume']['invalidTimeRange'], ['{maintime}' => $maintime, '{maxtime}' => $maxtime]);
            sendmessage($from_id, $texttime, $backuser, 'HTML');
            return;
        }
        $prodcut = "customvolume_" . $text . "_" . $user['Processing_value_one'];
    } elseif ($user['step'] == "endstepusersom" || $user['step'] == "endstepuserom") {
        $prodcut = $user['Processing_value_one'];
    } else {
        $prodcut = $dataget[1];
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
        if (!preg_match('~(?!_)^[a-z][a-z\d_]{2,32}(?<!_)$~i', $text)) {
            sendmessage($from_id, $textbotlang['users']['invalidusername'], $backuser, 'HTML');
            return;
        }
        $loc = $user['Processing_value_one'];
    } else {
        $loc = $prodcut;
    }
    update("user", "Processing_value_one", $loc, "id", $from_id);
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    $parts = explode("_", $loc);
    if ($parts[0] == "customvolume") {
        $info_product['Volume_constraint'] = $parts[2];
        $info_product['name_product'] = $textbotlang['users']['customSellVolume']['title'];
        $info_product['code_product'] = $textbotlang['users']['customSellVolume']['title'];
        $info_product['Service_time'] = $parts[1];
        $info_product['price_product'] = ($parts[2] * $custompricevalue) + ($parts[1] * $customtimevalueprice);
    } else {
        $__q8 = $pdo->prepare("SELECT * FROM product WHERE code_product = ? AND (Location = ? or Location = '/all') LIMIT 1");
        $__q8->bindValue(1, $loc, PDO::PARAM_STR);
        $__q8->bindValue(2, $user['Processing_value'], PDO::PARAM_STR);
        $__q8->execute();
        $info_product = $__q8->fetch(PDO::FETCH_ASSOC);
    }
    $randomString = bin2hex(random_bytes(2));
    $username_ac = generateUsername($from_id, $marzban_list_get['MethodUsername'], $username, $randomString, $text, $marzban_list_get['namecustom'], $user['namecustom']);
    $username_ac = strtolower($username_ac);
    update("user", "Processing_value_tow", $username_ac, "id", $from_id);
    if ($info_product['Volume_constraint'] == 0)
        $info_product['Volume_constraint'] = $textbotlang['users']['status']['unlimited'];
    if ($info_product['Service_time'] == 0)
        $info_product['Service_time'] = $textbotlang['users']['status']['unlimited'];
    $info_product['price_product'] = intval($info_product['price_product']) * intval($user['Processing_value_four']);
    $price_product_format = number_format($info_product['price_product']);
    $userbalancepish = number_format($user['Balance']);
    $textin = sprintf($textbotlang['users']['sell']['preInvoice2'], $username_ac, $info_product['name_product'], $info_product['Service_time'], $price_product_format, $info_product['Volume_constraint'], $userbalancepish, $user['Processing_value_four']);
    sendmessage($from_id, $textin, $paymentom, 'HTML');
    step('payments', $from_id);
} elseif ($user['step'] == "payments" && $datain == "confirmandgetservice") {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
    $custompricevalue = $eextraprice[$user['agent']];
    $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
    $customtimevalueprice = $eextraprice[$user['agent']];
    $parts = explode("_", $user['Processing_value_one']);
    if ($parts[0] == "customvolume") {
        $info_product['Volume_constraint'] = $parts[2];
        $info_product['name_product'] = $textbotlang['users']['customSellVolume']['title'];
        $info_product['code_product'] = "customvolume";
        $info_product['Service_time'] = $parts[1];
        $info_product['price_product'] = ($parts[2] * $custompricevalue) + ($parts[1] * $customtimevalueprice);
        $info_product['data_limit_reset'] = "no_reset";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE code_product = :code_product AND (Location = :location OR Location = '/all') LIMIT 1");
        $stmt->bindValue(':code_product', $user['Processing_value_one'], PDO::PARAM_STR);
        $stmt->bindValue(':location', $user['Processing_value'], PDO::PARAM_STR);
        $stmt->execute();
        $info_product = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if (empty($info_product['price_product']) || empty($info_product['price_product']))
        return;
    $priceproduct = $info_product['price_product'] * $user['Processing_value_four'];
    Editmessagetext($from_id, $message_id, $text_inline, null);
    $username_ac = $user['Processing_value_tow'];
    $date = time();
    if (intval($user['pricediscount']) != 0) {
        $result = ($priceproduct * $user['pricediscount']) / 100;
        $priceproduct = $priceproduct - $result;
        sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
    }
    $single_price = intval($user['Processing_value_four']) != 0 ? $priceproduct / intval($user['Processing_value_four']) : $priceproduct;
    if ($priceproduct > $user['Balance'] && $user['agent'] != "n2") {
        $marzbandirectpay = select('shopSetting', "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        if ($marzbandirectpay == "offdirectbuy") {
            $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']]);
            $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']]);
            $bakinfos = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "account"],
                    ]
                ]
            ]);
            Editmessagetext($from_id, $message_id, sprintf($textbotlang['users']['Balance']['insufficientbalance'], $minbalance, $maxbalance), $bakinfos, 'HTML');
            step('getprice', $from_id);
            return;
        } else {
            $Balance_prim = $priceproduct - $user['Balance'];
            $Balance_prims = $user['Balance'] - $priceproduct;
            if ($Balance_prims <= 1)
                $Balance_prims = 0;
            update("user", "Processing_value", $Balance_prim, "id", $from_id);
            sendmessage($from_id, $textbotlang['users']['sell']['noCredit'], $step_payment, 'HTML');
            step('get_step_payment', $from_id);
            return;
        }
    }
    if (intval($user['maxbuyagent']) != 0 and $user['agent'] == "n2") {
        if (($user['Balance'] - $priceproduct) < intval("-" . $user['maxbuyagent'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['maxpurchasereached'], null, 'HTML');
            return;
        }
    }
    $datep = strtotime("+" . $info_product['Service_time'] . "days");
    if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customTextSequential', 'usernameSequential', 'numericIdSequential', 'agentCustomTextSequential'], true)) {
        $value = intval($user['number_username']) + $user['Processing_value_four'];
        update("user", "number_username", $value, "id", $from_id);
        if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customTextSequential', 'agentCustomTextSequential'], true)) {
            $value = intval($setting['numbercount']) + $user['Processing_value_four'];
            update("setting", "numbercount", $value);
        }
    }
    if ($info_product['Service_time'] == 0) {
        $datep = 0;
    } else {
        $datep = strtotime(date("Y-m-d H:i:s", $datep));
    }
    $datac = array(
        'expire' => strtotime(date("Y-m-d H:i:s", $datep)),
        'data_limit' => $info_product['Volume_constraint'] * pow(1024, 3),
        'from_id' => $from_id,
        'username' => $username,
        'type' => 'buyomdh'
    );
    if ($info_product['inbounds'] != null) {
        $marzban_list_get['inboundid'] = $info_product['inbounds'];
    }
    $notifctions = json_encode(array(
        'volume' => false,
        'time' => false,
    ));
    $Shoppinginfo = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['help']['btninlinebuy'], 'callback_data' => "helpbtn"],
            ]
        ]
    ]);
    for ($i = 0; $i < $user['Processing_value_four']; $i++) {
        $random_number = rand(1000000, 9999999);
        $username_acc = $username_ac . "_" . $i;
        $get_username_Check = $ManagePanel->DataUser($marzban_list_get['name_panel'], $username_acc);
        if (isset($get_username_Check['username']) || rowExists("invoice", "username", $username_acc)) {
            $username_acc = $random_number . "_" . $username_acc;
        }
        $randomString = bin2hex(random_bytes(4));
        if (rowExists("invoice", "id_invoice", $randomString)) {
            $randomString = $random_number . $randomString;
        }
        $dataoutput = $ManagePanel->createUser($marzban_list_get['name_panel'], $info_product['code_product'], $username_acc, $datac);
        if ($dataoutput['username'] == null) {
            $dataoutput['msg'] = json_encode($dataoutput['msg']);
            sendmessage($from_id, $textbotlang['users']['sell']['errorConfig'], $keyboard, 'HTML');
            $texterros = sprintf($textbotlang['Admin']['reportgroup']['errorBulkAccountCreate'], $dataoutput['msg'], $from_id, $username, $marzban_list_get['name_panel']);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $texterros,
                    'parse_mode' => "HTML"
                ]);
            }
            step('home', $from_id);
            return;
        }
        $stmt = $pdo->prepare("INSERT IGNORE INTO invoice (id_user, id_invoice, username,time_sell, Service_location, name_product, price_product, Volume, Service_time,Status,notifctions) VALUES (?, ?, ?, ?, ?, ?, ?,?,?,?,?)");
        $Status = "active";
        $stmt->execute([$from_id, $randomString, $username_acc, $date, $user['Processing_value'], $info_product['name_product'], $info_product['price_product'], $info_product['Volume_constraint'], $info_product['Service_time'], $Status, $notifctions]);
        $user_Balance = select("user", "*", "id", $from_id, "select");
        update("user", "Balance", $user_Balance['Balance'] - $single_price, "id", $from_id);
        $config = "";
        $output_config_link = $marzban_list_get['sublink'] == "onsublink" ? $dataoutput['subscription_url'] : "";
        if ($marzban_list_get['config'] == "onconfig") {
            if (is_array($dataoutput['configs'])) {
                foreach ($dataoutput['configs'] as $configs) {
                    $config .= $configs;
                }
            }
        }
        $textbotlang['textbot']['afterPay'] = $marzban_list_get['type'] == "Manualsale" ? $textbotlang['textbot']['manual'] : $textbotlang['textbot']['afterPay'];
        if ($marzban_list_get['type'] == "WGDashboard") {
            $textbotlang['textbot']['afterPay'] = $textbotlang['users']['sell']['created2'];
        }
        $textcreatuser = str_replace('{username}', "<code>{$dataoutput['username']}</code>", $textbotlang['textbot']['afterPay']);
        $textcreatuser = str_replace('{name_service}', $info_product['name_product'], $textcreatuser);
        $textcreatuser = str_replace('{location}', $marzban_list_get['name_panel'], $textcreatuser);
        $textcreatuser = str_replace('{day}', $info_product['Service_time'], $textcreatuser);
        $textcreatuser = str_replace('{volume}', $info_product['Volume_constraint'], $textcreatuser);
        $textcreatuser = str_replace('{config}', "<code>{$output_config_link}</code>", $textcreatuser);
        $textcreatuser = str_replace('{links}', "<code>{$config}</code>", $textcreatuser);
        $textcreatuser = str_replace('{links2}', "{$output_config_link}", $textcreatuser);
        sendMessageService($marzban_list_get, $dataoutput['configs'], $output_config_link, $dataoutput['username'], $Shoppinginfo, $textcreatuser, $randomString);
    }
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboard, 'HTML');
    $balanceformatsell = number_format(select("user", "Balance", "id", $from_id, "select")['Balance'], 0);
    $balanceformatsellbefore = number_format($user['Balance'], 0);
    $pricebulk = $info_product['price_product'] * intval($user['Processing_value_four']);
    $count_service = $user['Processing_value_four'];
    $timejalali = jdate('Y/m/d H:i:s');
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['bulkAccountCreated'], $from_id, $username, $username_ac, $count_service, $first_name, $user['Processing_value'], $info_product['name_product'], $info_product['Service_time'], $info_product['Volume_constraint'], $balanceformatsellbefore, $balanceformatsell, $randomString, $user['agent'], $user['number'], $info_product['price_product'], $info_product['price_product'], $user['Processing_value_four'], $timejalali);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $buyreport,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
    step('home', $from_id);
} elseif ($datain == "Add_Balance") {
    update("user", "Processing_value", "0", "id", $from_id);
    update("user", "Processing_value_one", "0", "id", $from_id);
    update("user", "Processing_value_tow", "0", "id", $from_id);
    update("user", "Processing_value_four", "0", "id", $from_id);
    step('home', $from_id);
    if ($setting['get_number'] == "onAuthenticationphone" && $user['step'] != "get_number" && $user['number'] == "none") {
        sendmessage($from_id, $textbotlang['users']['number']['confirming'], $request_contact, 'HTML');
        step('get_number', $from_id);
    }
    if ($user['number'] == "none" && $setting['get_number'] == "onAuthenticationphone")
        return;
    $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']]);
    $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']]);
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "account"],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, sprintf($textbotlang['users']['Balance']['enterAmount'], $minbalance, $maxbalance), $bakinfos, 'HTML');
    step('getprice', $from_id);
    update("user", 'Processing_value', $message_id, "id", $from_id);
} elseif ($user['step'] == "getprice") {
    deletemessage($from_id, $user['Processing_value']);
    if (!is_numeric($text))
        return sendmessage($from_id, $textbotlang['users']['Balance']['errorprice'], null, 'HTML');
    $minbalance = json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']];
    $maxbalance = json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']];
    $balancelast = $text;
    if ($text > $maxbalance or $text < $minbalance) {
        $minbalance = number_format($minbalance);
        $maxbalance = number_format($maxbalance);
        sendmessage($from_id, sprintf($textbotlang['users']['Balance']['amountRangeError'], $minbalance, $maxbalance), null, 'HTML');
        return;
    }
    if ($user['Balance'] < 0 and intval($setting['Debtsettlement']) == 1) {
        $balancruser = abs($user['Balance']);
        if ($text < $balancruser) {
            sendmessage($from_id, sprintf($textbotlang['users']['Balance']['debtRequired'], $balancruser), null, 'HTML');
            return;
        }
    }
    update("user", "Processing_value", $balancelast, "id", $from_id);
    sendmessage($from_id, $textbotlang['users']['Balance']['selectPayment'], $step_payment, 'HTML');
    step('get_step_payment', $from_id);
} elseif ($user['step'] == "get_step_payment") {
    if ($datain == "cart_to_offline") {
        $checkpay = $pdo->prepare("SELECT * FROM Payment_report WHERE id_user = :user_id AND payment_Status = 'waiting'");
        $checkpay->bindValue(':user_id', $from_id, PDO::PARAM_STR);
        $checkpay->execute();
        if (($checkpay)->rowCount() != 0) {
            sendmessage($from_id, $textbotlang['users']['Balance']['pendingPayment'], null, 'HTML');
            return;
        }
        $mainbalance = select("PaySetting", "ValuePay", "NamePay", "minbalancecart", "select")['ValuePay'];
        $maxbalance = select("PaySetting", "ValuePay", "NamePay", "maxbalancecart", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalance || $user['Processing_value'] > $maxbalance) {
            $mainbalance = number_format($mainbalance);
            $maxbalance = number_format($maxbalance);
            sendmessage($from_id, strtr($textbotlang['users']['Balance']['depositRange'], ['{mainbalance}' => $mainbalance, '{maxbalance}' => $maxbalance]), null, 'HTML');
            return;
        }
        $cardQuery = $pdo->prepare("SELECT * FROM card_number  ORDER BY RAND() LIMIT 1");
        $cardQuery->execute();
        if ($cardQuery === false) {
            error_log('Failed to fetch card_number data: ' . implode(' ', $pdo->errorInfo()));
            sendmessage($from_id, $textbotlang['users']['Balance']['cardRetrieveError'], null, 'HTML');
            return;
        }

        $card_info = ($cardQuery)->fetch(PDO::FETCH_ASSOC);
        if (!$card_info || empty($card_info['cardnumber']) || empty($card_info['namecard'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['noActiveCard'], null, 'HTML');
            /* freed */
            $cardQuery = null;
            return;
        }

        $card_number = $card_info['cardnumber'];
        $PaySettingname = $card_info['namecard'];
        /* freed */
        $cardQuery = null;
        $price_copy = $user['Processing_value'];
        $valueprice = number_format($user['Processing_value']);
        $replacements = [
            '{price}' => $valueprice,
            '{card_number}' => $card_number,
            '{name_card}' => $PaySettingname,
        ];
        $price_copy = intval($user['Processing_value'] . "0");
        $textcart = strtr($textbotlang['textbot']['cart'], $replacements);
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "cart to cart";
        $stmt->execute([$from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice]);
        deletemessage($from_id, $message_id);
        if ($setting['statuscopycart'] == "1") {
            $sendresidcart = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['keyboard']['copyCardNumber'], 'copy_text' => ["text" => $card_number]],
                        ['text' => $textbotlang['keyboard']['copyAmount'], 'copy_text' => ["text" => $price_copy]]
                    ],
                    [
                        ['text' => $textbotlang['keyboard']['paidSendReceipt'], 'callback_data' => "sendresidcart-" . $randomString]
                    ]
                ]
            ]);
        } else {
            $sendresidcart = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['keyboard']['paidSendReceipt'], 'callback_data' => "sendresidcart-" . $randomString]
                    ]
                ]
            ]);
        }
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpcart", "select")['ValuePay'];
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], $data['text']);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], $data['text']);
            }
        }
        $message_id = telegram('sendmessage', [
            'chat_id' => $from_id,
            'text' => $textcart,
            'reply_markup' => $sendresidcart,
            'parse_mode' => "html",
        ]);
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "aqayepardakht") {
        if ($user['Processing_value'] < 5000) {
            sendmessage($from_id, $textbotlang['users']['Balance']['zarinpal'], null, 'HTML');
            return;
        }
        $mainbalance = select("PaySetting", "ValuePay", "NamePay", "minbalanceaqayepardakht", "select")['ValuePay'];
        $maxbalance = select("PaySetting", "ValuePay", "NamePay", "maxbalanceaqayepardakht", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalance || $user['Processing_value'] > $maxbalance) {
            $mainbalance = number_format($mainbalance);
            $maxbalance = number_format($maxbalance);
            sendmessage($from_id, strtr($textbotlang['users']['Balance']['depositRange'], ['{mainbalance}' => $mainbalance, '{maxbalance}' => $maxbalance]), null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $pay = createPayaqayepardakht($user['Processing_value'], $randomString);
        if ($pay['status'] != "success") {
            $text_error = json_encode($pay);
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = sprintf($textbotlang['Admin']['reportgroup']['errorAqayePardakhtLink'], $text_error, $from_id, $username);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "aqayepardakht";
        $stmt->execute([$from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice]);
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['payments'], 'url' => "https://panel.aqayepardakht.ir/startpay/" . $pay['transid']],
                ]
            ]
        ]);
        $price_format = number_format($user['Processing_value'], 0);
        $textnowpayments = sprintf($textbotlang['users']['Balance']['invoiceCreated'], $randomString, $price_format);
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpaqayepardakht", "select")['ValuePay'];
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        $message_id = sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "zarinpal") {
        if ($user['Processing_value'] < 5000) {
            sendmessage($from_id, $textbotlang['users']['Balance']['zarinpal'], null, 'HTML');
            return;
        }
        $mainbalance = select("PaySetting", "ValuePay", "NamePay", "minbalancezarinpal", "select")['ValuePay'];
        $maxbalance = select("PaySetting", "ValuePay", "NamePay", "maxbalancezarinpal", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalance || $user['Processing_value'] > $maxbalance) {
            $mainbalance = number_format($mainbalance);
            $maxbalance = number_format($maxbalance);
            sendmessage($from_id, strtr($textbotlang['users']['Balance']['depositRange'], ['{mainbalance}' => $mainbalance, '{maxbalance}' => $maxbalance]), null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $randomString = bin2hex(random_bytes(5));
        $pay = createPayZarinpal($user['Processing_value'], $randomString);
        if ($pay['data']['code'] != 100) {
            $text_error = json_encode($pay['errors']);
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = sprintf($textbotlang['Admin']['reportgroup']['errorZarinpalLink'], $text_error, $from_id, $username);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $dateacc = date('Y/m/d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice,dec_not_confirmed) VALUES (?,?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "zarinpal";
        $stmt->execute([$from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice, $pay['data']['authority']]);
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['payments'], 'url' => "https://www.zarinpal.com/pg/StartPay/" . $pay['data']['authority']],
                ]
            ]
        ]);
        $price_format = number_format($user['Processing_value'], 0);
        $textnowpayments = sprintf($textbotlang['users']['Balance']['invoiceCreated2'], $randomString, $price_format);
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpzarinpal", "select")['ValuePay'];
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        $message_id = sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "variza") {
        if ($user['Processing_value'] < 5000) {
            sendmessage($from_id, $textbotlang['users']['Balance']['variza'] ?? $textbotlang['users']['Balance']['zarinpal'], null, 'HTML');
            return;
        }
        $mainbalance = select("PaySetting", "ValuePay", "NamePay", "minbalancevariza", "select")['ValuePay'];
        $maxbalance = select("PaySetting", "ValuePay", "NamePay", "maxbalancevariza", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalance || $user['Processing_value'] > $maxbalance) {
            $mainbalance = number_format($mainbalance);
            $maxbalance = number_format($maxbalance);
            sendmessage($from_id, strtr($textbotlang['users']['Balance']['depositRange'], ['{mainbalance}' => $mainbalance, '{maxbalance}' => $maxbalance]), null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $randomString = bin2hex(random_bytes(5));
        $pay = createPayVariza($user['Processing_value'], $randomString);
        if (!isset($pay['pay_url']) || empty($pay['pay_url'])) {
            $text_error = isset($pay['error']) ? $pay['error'] : json_encode($pay);
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = sprintf($textbotlang['Admin']['reportgroup']['errorZarinpalLink'] ?? 'Variza link error: %s user %s', $text_error, $from_id);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $dateacc = date('Y/m/d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice,dec_not_confirmed) VALUES (?,?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "variza";
        $stmt->execute([$from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice, $pay['slug'] ?? $randomString]);
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['payments'], 'url' => $pay['pay_url']],
                ]
            ]
        ]);
        $price_format = number_format($user['Processing_value'], 0);
        $textnowpayments = sprintf($textbotlang['users']['Balance']['invoiceCreated2'], $randomString, $price_format);
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpvariza", "select")['ValuePay'];
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        $message_id = sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "plisio") {
        $rates = rate_arze();
        if ($rates === null) {
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            return;
        }
        $trx = $rates['TRX'];
        $usd = $rates['USD'];
        $trxprice = $user['Processing_value'] / $trx;
        $usdprice = $user['Processing_value'] / $usd;
        if ($usdprice <= 1) {
            sendmessage($from_id, $textbotlang['users']['Balance']['nowpayments'], null, 'HTML');
            return;
        }
        $mainbalanceplisio = select("PaySetting", "ValuePay", "NamePay", "minbalanceplisio", "select")['ValuePay'];
        $maxbalanceplisio = select("PaySetting", "ValuePay", "NamePay", "maxbalanceplisio", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalanceplisio || $user['Processing_value'] > $maxbalanceplisio) {
            $mainbalanceplisio = number_format($mainbalanceplisio);
            $maxbalanceplisio = number_format($maxbalanceplisio);
            sendmessage($from_id, strtr($textbotlang['users']['Balance']['depositRangePlisio'], ['{mainbalance}' => $mainbalanceplisio, '{maxbalance}' => $maxbalanceplisio]), null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $pay = plisio($randomString, $usdprice, $from_id);
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice,dec_not_confirmed) VALUES (?,?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "plisio";
        $stmt->execute([$from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice, $pay['txn_id']]);
        if (isset($pay['message'])) {
            $text_error = $pay['message'];
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = sprintf($textbotlang['Admin']['reportgroup']['errorCryptoLink'], $text_error, $from_id, $username);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['payments'], 'url' => $pay['invoice_url']],
                ]
            ]
        ]);
        $price_format = number_format($user['Processing_value'], 0);
        $USD = number_format($usd);
        $textnowpayments = sprintf($textbotlang['users']['Balance']['cryptoInstruction'], $randomString, $price_format, $USD);
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpplisio", "select")['ValuePay'];
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        $message_id = sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "nowpayment") {
        $rates = rate_arze();
        if ($rates === null) {
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            return;
        }
        $trx = $rates['TRX'];
        $usd = $rates['USD'];
        $trxprice = $user['Processing_value'] / $trx;
        $usdprice = $user['Processing_value'] / $usd;
        $mainbalance = select("PaySetting", "ValuePay", "NamePay", "minbalancenowpayment", "select")['ValuePay'];
        $maxbalance = select("PaySetting", "ValuePay", "NamePay", "maxbalancenowpayment", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalance || $user['Processing_value'] > $maxbalance) {
            $mainbalance = number_format($mainbalance);
            $maxbalance = number_format($maxbalance);
            sendmessage($from_id, strtr($textbotlang['users']['Balance']['depositRangePlisio'], ['{mainbalance}' => $mainbalance, '{maxbalance}' => $maxbalance]), null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $pay = nowPayments('invoice', $usdprice, $randomString, 'TopUp - ' . $from_id);
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice,dec_not_confirmed) VALUES (?,?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "nowpayment";
        $stmt->execute([$from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice, $pay['id']]);
        if (!isset($pay['id'])) {
            $text_error = json_encode($pay);
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = sprintf($textbotlang['Admin']['reportgroup']['errorCryptoLink2'], $text_error, $from_id, $username);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['payments'], 'url' => $pay['invoice_url']],
                ]
            ]
        ]);
        $price_format = number_format($user['Processing_value'], 0);
        $USD = number_format($usd);
        $textnowpayments = sprintf($textbotlang['users']['Balance']['cryptoInstruction2'], $randomString, $price_format, $USD);
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpnowpayment", "select")['ValuePay'];
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        $message_id = sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "iranpay1") {
        $rates = rate_arze();
        if ($rates === null || empty($rates['TRX']) || empty($rates['USD'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            return;
        }
        $trx = $rates['TRX'];
        $usd = $rates['USD'];
        $trxprice = round($user['Processing_value'] / $trx, 2);
        $usdprice = $user['Processing_value'] / $usd;
        $mainbalance = select("PaySetting", "ValuePay", "NamePay", "minbalanceiranpay1", "select")['ValuePay'];
        $maxbalance = select("PaySetting", "ValuePay", "NamePay", "maxbalanceiranpay1", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalance || $user['Processing_value'] > $maxbalance) {
            $mainbalance = number_format($mainbalance);
            $maxbalance = number_format($maxbalance);
            sendmessage($from_id, strtr($textbotlang['users']['Balance']['depositRangePlisio'], ['{mainbalance}' => $mainbalance, '{maxbalance}' => $maxbalance]), null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "Currency Rial 1";
        $stmt->execute([$from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice]);
        $pay = createInvoiceiranpay1($user['Processing_value'], $randomString);
        if ($pay['status'] != "100") {
            $text_error = $pay['message'];
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = sprintf($textbotlang['Admin']['reportgroup']['errorPaymentLink'], $text_error, $from_id, $Payment_Method, $username);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        update("Payment_report", "dec_not_confirmed", $pay['Authority'], "id_order", $randomString);
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['keyboard']['payment'], 'url' => $pay['payment_url_bot']]
                ]
            ]
        ]);
        $pricetoman = number_format($user['Processing_value'], 0);
        $textnowpayments = sprintf($textbotlang['users']['Balance']['transactionCreated'], $randomString, $pricetoman);
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpiranpay1", "select")['ValuePay'];
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        $message_id = sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "iranpay2") {
        $mainbalance = select("PaySetting", "ValuePay", "NamePay", "minbalanceiranpay2", "select")['ValuePay'];
        $maxbalance = select("PaySetting", "ValuePay", "NamePay", "maxbalanceiranpay2", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalance || $user['Processing_value'] > $maxbalance) {
            $mainbalance = number_format($mainbalance);
            $maxbalance = number_format($maxbalance);
            sendmessage($from_id, strtr($textbotlang['users']['Balance']['depositRangePlisio'], ['{mainbalance}' => $mainbalance, '{maxbalance}' => $maxbalance]), null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "Currency Rial 2";
        $stmt->execute([$from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice]);
        $payment = trnado($randomString, $user['Processing_value']);
        if (empty($payment['success'])) {
            $text_error = json_encode($payment);
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = sprintf($textbotlang['Admin']['reportgroup']['errorPaymentLink2'], $text_error, $from_id, $Payment_Method, $username);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['payments'], 'url' => $payment['payment_link']]
                ]
            ]
        ]);
        $pricetoman = number_format($user['Processing_value'], 0);
        $textnowpayments = sprintf($textbotlang['users']['Balance']['transactionCreated2'], $randomString, $pricetoman);
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpiranpay2", "select")['ValuePay'];
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        $message_id = sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "iranpay4") {
        $mainbalance = getPaySettingValue('minbalanceiranpay4', '0');
        $maxbalance = getPaySettingValue('maxbalanceiranpay4', '0');
        if ($user['Processing_value'] < $mainbalance || $user['Processing_value'] > $maxbalance) {
            $mainbalance = number_format($mainbalance);
            $maxbalance = number_format($maxbalance);
            sendmessage($from_id, strtr($textbotlang['users']['Balance']['depositRangePlisio'], ['{mainbalance}' => $mainbalance, '{maxbalance}' => $maxbalance]), null, 'HTML');
            return;
        }

        // The daily spend throttle every other rial gateway has. Scoped to this
        // gateway's own rows — the copy on `iranpay3` reads `Currency Rial 1`,
        // which throttles the wrong gateway, so it is not copied verbatim.
        $dateacc = date('Y/m/d');
        $stmt = $pdo->prepare("SELECT SUM(price) as price FROM Payment_report WHERE Payment_Method = 'AbanGateway' AND time LIKE :today");
        $stmt->execute([':today' => '%' . $dateacc . '%']);
        $sumpayment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (intval($sumpayment['price']) > 1000000) {
            sendmessage($from_id, $textbotlang['users']['Balance']['queueBusy'], null, 'HTML');
            return;
        }

        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";

        // The row is written before the link is asked for. A confirmation that
        // arrives while the gateway is still answering finds an order to attach
        // itself to instead of being told it does not exist.
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
        $Payment_Method = "AbanGateway";
        $stmt->execute([$from_id, $randomString, $dateacc, $user['Processing_value'], "Unpaid", $Payment_Method, $invoice]);

        $pay = createPayiranpay4($user['Processing_value'], $randomString);
        if (empty($pay['success']) || empty($pay['payment_link'])) {
            $text_error = json_encode($pay);
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = sprintf($textbotlang['Admin']['reportgroup']['errorPaymentLink3'], $text_error, $from_id, $Payment_Method, $username);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }

        $pricetoman = number_format($user['Processing_value'], 0);
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['payments'], 'url' => $pay['payment_link']]
                ]
            ]
        ]);
        $textiranpay4 = sprintf($textbotlang['users']['Balance']['transactionCreated3'], $randomString, $pricetoman);
        $message_id = sendmessage($from_id, $textiranpay4, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
        step('home', $from_id);
    } elseif ($datain == "iranpay3") {
        $dateacc = date('Y/m/d');
        $query = "SELECT SUM(price) as price FROM Payment_report WHERE  Payment_Method = 'Currency Rial 3' AND  time LIKE :dateacc";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':dateacc' => "%$dateacc%"]);
        $sumpayment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (intval($sumpayment['price']) > 1000000) {
            sendmessage($from_id, $textbotlang['users']['Balance']['queueBusy'], null, 'HTML');
            return;
        }
        $rates = rate_arze();
        if ($rates === null) {
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            return;
        }
        $trx = $rates['TRX'];
        $usd = $rates['USD'];
        $trxprice = $user['Processing_value'] / $trx;
        $usdprice = $user['Processing_value'] / $usd;
        $mainbalance = select("PaySetting", "ValuePay", "NamePay", "minbalanceiranpay", "select")['ValuePay'];
        $maxbalance = select("PaySetting", "ValuePay", "NamePay", "maxbalanceiranpay", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalance || $user['Processing_value'] > $maxbalance) {
            $mainbalance = number_format($mainbalance);
            $maxbalance = number_format($maxbalance);
            sendmessage($from_id, strtr($textbotlang['users']['Balance']['depositRangePlisio'], ['{mainbalance}' => $mainbalance, '{maxbalance}' => $maxbalance]), null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], null, 'HTML');
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "Currency Rial 3";
        $stmt->execute([$from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice]);
        $paylink = createInvoice($trxprice);
        if (!$paylink['success']) {
            $text_error = $paylink['message'];
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = sprintf($textbotlang['Admin']['reportgroup']['errorPaymentLink3'], $text_error, $from_id, $Payment_Method, $username);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        update("Payment_report", "dec_not_confirmed", $paylink['data']['id'], "id_order", $randomString);
        $pricetoman = number_format($user['Processing_value'], 0);
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['keyboard']['diamondPayment'], 'url' => "t.me/AvidTrx_Bot?start=" . $paylink['data']['id']]
                ],
            ]
        ]);
        $textnowpayments = sprintf($textbotlang['users']['Balance']['transactionCreated3'], $randomString, $pricetoman);
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpiranpay3", "select")['ValuePay'];
        if ($gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        step("getvoocherx", $from_id);
        savedata("clear", "id_payment", $randomString);
    } elseif ($datain == "digitaltron") {
        $rates = rate_arze();
        if ($rates === null) {
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            return;
        }
        $trx = $rates['TRX'];
        $usd = $rates['USD'];
        $trxprice = round($user['Processing_value'] / $trx, 2);
        $usdprice = round($user['Processing_value'] / $usd, 2);
        if ($trxprice <= 1) {
            sendmessage($from_id, $textbotlang['users']['Balance']['changeto'], null, 'HTML');
            return;
        }
        $mainbalancedigitaltron = select("PaySetting", "ValuePay", "NamePay", "minbalancedigitaltron", "select")['ValuePay'];
        $maxbalancedigitaltron = select("PaySetting", "ValuePay", "NamePay", "maxbalancedigitaltron", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalancedigitaltron || $user['Processing_value'] > $maxbalancedigitaltron) {
            $mainbalance = number_format($mainbalancedigitaltron);
            $maxbalance = number_format($maxbalancedigitaltron);
            sendmessage($from_id, strtr($textbotlang['users']['Balance']['depositRangePlisio'], ['{mainbalance}' => $mainbalance, '{maxbalance}' => $maxbalance]), null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "arze digital offline";
        $stmt->execute([$from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice]);
        $affilnecurrency = select("PaySetting", "*", "NamePay", "walletaddress", "select")['ValuePay'];
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['keyboard']['sendDepositLink'], 'callback_data' => "sendresidarze-{$randomString}"]
                ]
            ]
        ]);
        $formatprice = number_format($user['Processing_value'], 0);
        $textnowpayments = sprintf($textbotlang['users']['Balance']['transactionCreatedTron'], $randomString, $affilnecurrency, $trxprice, $formatprice);
        $gethelp = getPaySettingValue('helpofflinearze');
        if ($gethelp !== null && $gethelp != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        $message_id = sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    } elseif ($datain == "startelegrams") {
        $rates = rate_arze(['USD', 'Ton']);
        if ($rates === null) {
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            return;
        }
        $usd = $rates['USD'];
        $ton = $rates['Ton'];
        $usdprice = round($user['Processing_value'] / $usd, 2);
        $starAmount = $usd * 0.016;
        $starAmount = intval($user['Processing_value'] / $starAmount);
        $mainbalance = select("PaySetting", "ValuePay", "NamePay", "minbalancestar", "select")['ValuePay'];
        $maxbalance = select("PaySetting", "ValuePay", "NamePay", "maxbalancestar", "select")['ValuePay'];
        if ($user['Processing_value'] < $mainbalance || $user['Processing_value'] > $maxbalance) {
            $mainbalance = number_format($mainbalance);
            $maxbalance = number_format($maxbalance);
            sendmessage($from_id, strtr($textbotlang['users']['Balance']['depositRange'], ['{mainbalance}' => $mainbalance, '{maxbalance}' => $maxbalance]), null, 'HTML');
            return;
        }
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['Balance']['linkpayments'], $keyboard, 'HTML');
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
        $payment_Status = "Unpaid";
        $Payment_Method = "Star Telegram";
        $stmt->execute([$from_id, $randomString, $dateacc, $user['Processing_value'], $payment_Status, $Payment_Method, $invoice]);
        $affilnecurrency = select("PaySetting", "*", "NamePay", "walletaddress", "select")['ValuePay'];
        $straCreateLink = telegram('createInvoiceLink', [
            'title' => "Buy for Price {$user['Processing_value']}",
            'description' => "Buy price",
            'payload' => $randomString,
            'currency' => "XTR",
            'prices' => json_encode(array(
                array(
                    'label' => "Price",
                    'amount' => $starAmount
                )
            ))
        ]);
        if ($straCreateLink['ok'] == false) {
            $text_error = json_encode($straCreateLink);
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            $ErrorsLinkPayment = sprintf($textbotlang['Admin']['reportgroup']['errorStarInvoice'], $text_error, $from_id, $Payment_Method, $username);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $ErrorsLinkPayment,
                    'parse_mode' => "HTML"
                ]);
            }
            return;
        }
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['payments'], 'url' => $straCreateLink['result']]
                ]
            ]
        ]);
        $formatprice = number_format($user['Processing_value'], 0);
        $textstar = sprintf($textbotlang['users']['Balance']['transactionCreatedStar'], $randomString, $starAmount, $formatprice, $formatprice);
        $gethelp = select("PaySetting", "ValuePay", "NamePay", "helpstar", "select")['ValuePay'];
        if (intval($gethelp) != 2) {
            $data = json_decode($gethelp, true);
            if ($data['type'] == "text") {
                sendmessage($from_id, $data['text'], null, 'HTML');
            } elseif ($data['type'] == "photo") {
                sendphoto($from_id, $data['photoid'], null);
            } elseif ($data['type'] == "video") {
                sendvideo($from_id, $data['videoid'], null);
            }
        }
        $message_id = sendmessage($from_id, $textstar, $paymentkeyboard, 'HTML');
        updatePaymentMessageId($message_id, $randomString);
    }
}
if (preg_match('/^sendresidcart-(.*)/', $datain, $dataget)) {
    $timefivemin = time() - 120;
    $timefivemin = date('Y/m/d H:i:s', intval($timefivemin));
    $sql = "SELECT * FROM Payment_report WHERE id_user = :from_id AND Payment_Method = 'cart to cart' AND at_updated > :timefivemin";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':from_id', $from_id, PDO::PARAM_STR);
    $stmt->bindParam(':timefivemin', $timefivemin, PDO::PARAM_STR);
    $stmt->execute();
    $paymentcount = $stmt->rowCount();
    if ($paymentcount != 0 and !in_array($from_id, $admin_ids)) {
        sendmessage($from_id, $textbotlang['users']['Balance']['receiptCooldown'], null, 'HTML');
        return;
    }
    $payemntcheck = select("Payment_report", "*", "id_order", $dataget[1], "select");
    if (!is_array($payemntcheck) || (string) $payemntcheck['id_user'] !== (string) $from_id) {
        return;
    }
    if ($payemntcheck['payment_Status'] == "paid") {
        sendmessage($from_id, $textbotlang['users']['Balance']['alreadyConfirmed'], null, 'HTML');
        return;
    }
    if ($payemntcheck['payment_Status'] == "expire") {
        sendmessage($from_id, $textbotlang['users']['Balance']['transactionExpired'], null, 'HTML');
        return;
    }
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['users']['Balance']['askReceiptImage'], $backuser, 'HTML');
    step('cart_to_cart_user', $from_id);
    update("user", "Processing_value", $dataget[1], "id", $from_id);
} elseif (preg_match('/^sendresidarze-(.*)/', $datain, $dataget) and $text_inline != null) {
    $payemntcheck = select("Payment_report", "*", "id_order", $dataget[1], "select");
    if (!is_array($payemntcheck) || (string) $payemntcheck['id_user'] !== (string) $from_id) {
        return;
    }
    if ($payemntcheck['payment_Status'] == "paid") {
        sendmessage($from_id, $textbotlang['users']['Balance']['alreadyConfirmed'], null, 'HTML');
        return;
    }
    if ($payemntcheck['payment_Status'] == "expire") {
        sendmessage($from_id, $textbotlang['users']['Balance']['transactionExpired'], null, 'HTML');
        return;
    }
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['users']['Balance']['askReceiptOrTron'], $backuser, 'HTML');
    step('getresidcurrency', $from_id);
    update("user", "Processing_value", $dataget[1], "id", $from_id);
} elseif ($user['step'] == "getresidcurrency") {
    $format_balance = number_format($user['Balance'], 0);
    step('home', $from_id);
    $PaymentReport = select("Payment_report", "*", "id_order", $user['Processing_value'], "select");
    $Paymentusercount = select("Payment_report", "*", "id_user", $PaymentReport['id_user'], "count");
    if ($PaymentReport == false) {
        sendmessage($from_id, $textbotlang['users']['Balance']['restartPurchaseOrPay'], $keyboard, 'HTML');
        return;
    }
    $Confirm_pay = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['Balance']['confirmPaying'], 'callback_data' => "Confirm_pay_{$PaymentReport['id_order']}"],
                ['text' => $textbotlang['users']['Balance']['rejectPay'], 'callback_data' => "reject_pay_{$PaymentReport['id_order']}"],
            ],
            [
                ['text' => $textbotlang['users']['Balance']['addBalanceUser'], 'callback_data' => "addbalamceuser_{$PaymentReport['id_order']}"],
                ['text' => $textbotlang['users']['Balance']['blockedfake'], 'callback_data' => "blockuserfake_{$PaymentReport['id_user']}"],
            ]
        ]
    ]);
    $textdiscount = "";
    $format_price_cart = number_format($PaymentReport['price'], 0);
    if ($user['Processing_value_tow'] == "getconfigafterpay") {
        $get_invoice = select("invoice", "*", "username", $user['Processing_value_one'], "select");
        if ($get_invoice == false) {
            sendmessage($from_id, $textbotlang['users']['Balance']['restartPurchaseOrPay'], $keyboard, 'HTML');
            return;
        }
        $textsendrasid = sprintf($textbotlang['Admin']['reportgroup']['newPaymentService'], $get_invoice['username'], $get_invoice['name_product'], $get_invoice['Volume'], $get_invoice['Service_time'], $first_name, $from_id, $from_id, $format_balance, $PaymentReport['id_order'], $username, $Paymentusercount, $format_price_cart, $caption, $text);
    } elseif ($user['Processing_value_tow'] == "getextenduser") {
        $partsdic = explode("%", $user['Processing_value_one']);
        $usernamepanel = $partsdic[0];
        $sql = "SELECT * FROM service_other WHERE username = :username  AND value  LIKE CONCAT('%', :value, '%') AND id_user = :id_user ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $usernamepanel, PDO::PARAM_STR);
        $stmt->bindParam(':value', $partsdic[1], PDO::PARAM_STR);
        $stmt->bindParam(':id_user', $from_id);
        $stmt->execute();
        $service_other = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($service_other == false) {
            sendmessage($from_id, $textbotlang['users']['infoFetchErrorRestart'], $keyboard, 'HTML');
            return;
        }
        $service_other = json_decode($service_other['value'], true);
        $nameloc = select("invoice", "*", "username", $usernamepanel, "select");
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
        $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
        $custompricevalue = $eextraprice[$user['agent']];
        $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
        $customtimevalueprice = $eextraprice[$user['agent']];
        $codeproduct = $service_other['code_product'];
        if ($codeproduct == "custom_volume") {
            $prodcut['code_product'] = "custom_volume";
            $prodcut['name_product'] = $nameloc['name_product'];
            $prodcut['price_product'] = ($service_other['volumebuy'] * $custompricevalue) + ($nameloc['Service_time'] * $customtimevalueprice);
            $prodcut['Service_time'] = $service_other['Service_time'];
            $prodcut['Volume_constraint'] = $service_other['volumebuy'];
        } else {
            $nameloc = select("invoice", "*", "username", $usernamepanel, "select");
            $__q11 = $pdo->prepare("SELECT * FROM product WHERE (Location = ? OR Location = '/all') AND agent= ? AND code_product = ?");
            $__q11->bindValue(1, $nameloc['Service_location'], PDO::PARAM_STR);
            $__q11->bindValue(2, $user['agent'], PDO::PARAM_STR);
            $__q11->bindValue(3, $codeproduct, PDO::PARAM_STR);
            $__q11->execute();
            $prodcut = $__q11->fetch(PDO::FETCH_ASSOC);
        }
        $Confirm_pay = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['confirmPaying'], 'callback_data' => "Confirm_pay_{$PaymentReport['id_order']}"],
                    ['text' => $textbotlang['users']['Balance']['rejectPay'], 'callback_data' => "reject_pay_{$PaymentReport['id_order']}"],
                ],
                [
                    ['text' => $textbotlang['users']['Balance']['addBalanceUser'], 'callback_data' => "addbalamceuser_{$PaymentReport['id_order']}"],
                    ['text' => $textbotlang['users']['Balance']['blockedfake'], 'callback_data' => "blockuserfake_{$PaymentReport['id_user']}"],
                ],
                [
                    ['text' => $textbotlang['keyboard']['configInfo'], 'callback_data' => "manageinvoice_{$nameloc['id_invoice']}"],
                ]
            ]
        ]);
        $textsendrasid = sprintf($textbotlang['Admin']['reportgroup']['newPaymentRenew'], $usernamepanel, $prodcut['name_product'], $first_name, $from_id, $from_id, $format_balance, $PaymentReport['id_order'], $username, $Paymentusercount, $format_price_cart, $caption, $text);
    } elseif ($user['Processing_value_tow'] == "getextravolumeuser") {
        $partsdic = explode("%", $user['Processing_value_one']);
        $usernamepanel = $partsdic[0];
        $volumes = $partsdic[1];
        $textsendrasid = sprintf($textbotlang['Admin']['reportgroup']['newPaymentExtraVolume'], $usernamepanel, $volumes, $first_name, $from_id, $from_id, $format_balance, $PaymentReport['id_order'], $username, $Paymentusercount, $format_price_cart, $caption, $text);
    } elseif ($user['Processing_value_tow'] == "getextratimeuser") {
        $partsdic = explode("%", $user['Processing_value_one']);
        $usernamepanel = $partsdic[0];
        $time = $partsdic[1];
        $textsendrasid = sprintf($textbotlang['Admin']['reportgroup']['newPaymentExtraTime'], $usernamepanel, $time, $first_name, $from_id, $from_id, $format_balance, $PaymentReport['id_order'], $username, $Paymentusercount, $format_price_cart, $caption, $text);
    } else {

        $textsendrasid = sprintf($textbotlang['Admin']['reportgroup']['newPaymentBalance'], $first_name, $from_id, $from_id, $format_balance, $PaymentReport['id_order'], $username, $Paymentusercount, $format_price_cart, $caption, $text);
    }
    foreach ($admin_ids as $id_admin) {
        $adminrulecheck = select("admin", "*", "id_admin", $id_admin, "select");
        if ($adminrulecheck['rule'] == "support")
            continue;
        if ($photo) {
            telegram('sendphoto', [
                'chat_id' => $id_admin,
                'photo' => $photoid,
                'caption' => $textbotlang['users']['Balance']['receiptimage'],
                'parse_mode' => "HTML",
            ]);
        }
        sendmessage($id_admin, $textsendrasid, $Confirm_pay, 'HTML');
    }
    if ($user['Processing_value_tow'] == "getconfigafterpay") {
        sendmessage($from_id, $textbotlang['users']['Balance']['sendReceiptAndConfig'], $keyboard, 'HTML');
    } else {
        sendmessage($from_id, $textbotlang['users']['Balance']['sendReceipt'], $keyboard, 'HTML');
    }
    update("Payment_report", "payment_Status", "waiting", "id_order", $PaymentReport['id_order']);
    update("Payment_report", "dec_not_confirmed", "$text $caption", "id_order", $PaymentReport['id_order']);
    $dateacc = date('Y/m/d H:i:s');
    update("Payment_report", "at_updated", $dateacc, "id_order", $PaymentReport['id_order']);
} elseif ($user['step'] == "cart_to_cart_user") {
    $format_balance = number_format($user['Balance'], 0);
    if (!$photo or isset($update['message']['media_group_id'])) {
        sendmessage($from_id, $textbotlang['users']['Balance']['onlyOneImage'], null, 'HTML');
        return;
    }
    step('home', $from_id);
    $PaymentReport = select("Payment_report", "*", "id_order", $user['Processing_value']);
    if ($PaymentReport == false) {
        sendmessage($from_id, $textbotlang['users']['infoFetchErrorRestart'], $keyboard, 'HTML');
        return;
    }
    $Confirm_pay = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['Balance']['confirmPaying'], 'callback_data' => "Confirm_pay_{$PaymentReport['id_order']}"],
                ['text' => $textbotlang['users']['Balance']['rejectPay'], 'callback_data' => "reject_pay_{$PaymentReport['id_order']}"],
            ],
            [
                ['text' => $textbotlang['users']['Balance']['addBalanceUser'], 'callback_data' => "addbalamceuser_{$PaymentReport['id_order']}"],
                ['text' => $textbotlang['users']['Balance']['blockedfake'], 'callback_data' => "blockuserfake_{$PaymentReport['id_user']}"],
            ]
        ]
    ]);
    $format_price_cart = number_format($PaymentReport['price'], 0);
    $split_data = explode('|', $PaymentReport['id_invoice']);
    if ($split_data[0] == "getconfigafterpay") {
        $get_invoice = select("invoice", "*", "username", $split_data[1], "select");
        if ($get_invoice == false) {
            sendmessage($from_id, $textbotlang['users']['Balance']['restartPurchaseOrPay'], $keyboard, 'HTML');
            return;
        }
        $textdiscount = "";
        $textsendrasid = sprintf($textbotlang['Admin']['reportgroup']['newPaymentService2'], $get_invoice['username'], $get_invoice['name_product'], $get_invoice['Volume'], $get_invoice['Service_time'], $first_name, $from_id, $from_id, $format_balance, $PaymentReport['id_order'], $username, $format_price_cart);
        sendmessage($from_id, $textbotlang['users']['Balance']['sendReceiptAndConfig'], $keyboard, 'HTML');
    } elseif ($split_data[0] == "getextenduser") {
        $partsdic = explode("%", $split_data[1]);
        $usernamepanel = $partsdic[0];
        $sql = "SELECT * FROM service_other WHERE username = :username  AND value  LIKE CONCAT('%', :value, '%') AND id_user = :id_user ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $usernamepanel, PDO::PARAM_STR);
        $stmt->bindParam(':value', $partsdic[1], PDO::PARAM_STR);
        $stmt->bindParam(':id_user', $from_id);
        $stmt->execute();
        $service_other = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($service_other == false) {
            sendmessage($from_id, $textbotlang['users']['infoFetchErrorRestart'], $keyboard, 'HTML');
            return;
        }
        $service_other = json_decode($service_other['value'], true);
        $nameloc = select("invoice", "*", "username", $usernamepanel, "select");
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
        $eextraprice = json_decode($marzban_list_get['pricecustomvolume'], true);
        $custompricevalue = $eextraprice[$user['agent']];
        $eextraprice = json_decode($marzban_list_get['pricecustomtime'], true);
        $customtimevalueprice = $eextraprice[$user['agent']];
        $codeproduct = $service_other['code_product'];
        if ($codeproduct == "custom_volume") {
            $prodcut['code_product'] = "custom_volume";
            $prodcut['name_product'] = $nameloc['name_product'];
            $prodcut['price_product'] = ($service_other['volumebuy'] * $custompricevalue) + ($service_other['Service_time'] * $customtimevalueprice);
            $prodcut['Service_time'] = $service_other['Service_time'];
            $prodcut['Volume_constraint'] = $service_other['volumebuy'];
        } else {
            $nameloc = select("invoice", "*", "username", $usernamepanel, "select");
            $__q12 = $pdo->prepare("SELECT * FROM product WHERE (Location = ? OR Location = '/all') AND agent= ? AND code_product = ?");
            $__q12->bindValue(1, $nameloc['Service_location'], PDO::PARAM_STR);
            $__q12->bindValue(2, $user['agent'], PDO::PARAM_STR);
            $__q12->bindValue(3, $codeproduct, PDO::PARAM_STR);
            $__q12->execute();
            $prodcut = $__q12->fetch(PDO::FETCH_ASSOC);
        }
        $Confirm_pay = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['confirmPaying'], 'callback_data' => "Confirm_pay_{$PaymentReport['id_order']}"],
                    ['text' => $textbotlang['users']['Balance']['rejectPay'], 'callback_data' => "reject_pay_{$PaymentReport['id_order']}"],
                ],
                [
                    ['text' => $textbotlang['users']['Balance']['addBalanceUser'], 'callback_data' => "addbalamceuser_{$PaymentReport['id_order']}"],
                    ['text' => $textbotlang['users']['Balance']['blockedfake'], 'callback_data' => "blockuserfake_{$PaymentReport['id_user']}"],
                ],
                [
                    ['text' => $textbotlang['keyboard']['configInfo'], 'callback_data' => "manageinvoice_{$nameloc['id_invoice']}"],
                ]
            ]
        ]);
        $textsendrasid = sprintf($textbotlang['Admin']['reportgroup']['newPaymentRenew2'], $usernamepanel, $prodcut['name_product'], $first_name, $from_id, $from_id, $format_balance, $PaymentReport['id_order'], $username, $format_price_cart);
        sendmessage($from_id, $textbotlang['users']['Balance']['receiptSentRenew'], $keyboard, 'HTML');
    } elseif ($split_data[0] == "getextravolumeuser") {
        $partsdic = explode("%", $split_data[1]);
        $usernamepanel = $partsdic[0];
        $volumes = $partsdic[1];
        $textsendrasid = sprintf($textbotlang['Admin']['reportgroup']['newPaymentExtraVolume2'], $usernamepanel, $volumes, $first_name, $from_id, $from_id, $format_balance, $PaymentReport['id_order'], $username, $format_price_cart);
        sendmessage($from_id, $textbotlang['users']['Balance']['receiptSentExtraVolume'], $keyboard, 'HTML');
    } elseif ($split_data[0] == "getextratimeuser") {
        $partsdic = explode("%", $split_data[1]);
        $usernamepanel = $partsdic[0];
        $time = $partsdic[1];
        $textsendrasid = sprintf($textbotlang['Admin']['reportgroup']['newPaymentExtraTime2'], $usernamepanel, $time, $first_name, $from_id, $from_id, $format_balance, $PaymentReport['id_order'], $username, $format_price_cart);
        sendmessage($from_id, $textbotlang['users']['Balance']['receiptSentExtraTime'], $keyboard, 'HTML');
    } else {

        $textsendrasid = sprintf($textbotlang['Admin']['reportgroup']['newPaymentBalance2'], $first_name, $from_id, $from_id, $format_balance, $PaymentReport['id_order'], $username, $format_price_cart);
        sendmessage($from_id, $textbotlang['users']['Balance']['sendReceipt'], $keyboard, 'HTML');
    }
    foreach ($admin_ids as $id_admin) {
        $adminrulecheck = select("admin", "*", "id_admin", $id_admin, "select");
        if ($adminrulecheck['rule'] == "support")
            continue;
        telegram('sendphoto', [
            'chat_id' => $id_admin,
            'photo' => $photoid,
            'caption' => $caption,
            'parse_mode' => "HTML",
        ]);
        sendmessage($id_admin, $textsendrasid, $Confirm_pay, 'HTML');
    }
    update("Payment_report", "payment_Status", "waiting", "id_order", $PaymentReport['id_order']);
    $dateacc = date('Y/m/d H:i:s');
    update("Payment_report", "at_updated", $dateacc, "id_order", $PaymentReport['id_order']);
} elseif ($datain == "Discount") {
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "account"],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['Discount']['getcode'], $bakinfos);
    step('get_code_user', $from_id);
} elseif ($user['step'] == "get_code_user") {
    if (!rowExists("Discount", "code", $text)) {
        sendmessage($from_id, $textbotlang['users']['Discount']['notcode'], null, 'HTML');
        return;
    }
    $checklimit = select("Discount", "*", "code", $text, "select");
    if ($checklimit['limitused'] >= $checklimit['limituse']) {
        sendmessage($from_id, $textbotlang['users']['Discount']['errorLimitDiscount'], $backuser, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM Giftcodeconsumed WHERE id_user = :from_id AND code = :code");
    $stmt->bindParam(':from_id', $from_id, PDO::PARAM_STR);
    $stmt->bindParam(':code', $text, PDO::PARAM_STR);
    $stmt->execute();
    $Checkcodesql = $stmt->rowCount();
    if ($Checkcodesql != 0) {
        sendmessage($from_id, $textbotlang['users']['Discount']['giftcodeonce'], $keyboard, 'HTML');
        step('home', $from_id);
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM Discount WHERE code = :code LIMIT 1");
    $stmt->bindParam(':code', $text);
    $stmt->execute();
    $get_codesql = $stmt->fetch(PDO::FETCH_ASSOC);
    $balance_user = $user['Balance'] + $get_codesql['price'];
    update("user", "Balance", $balance_user, "id", $from_id);
    $discountlimitadd = intval($checklimit['limitused']) + 1;
    update("Discount", "limitused", $discountlimitadd, "code", $text);
    step('home', $from_id);
    $text_balance_code = sprintf($textbotlang['users']['Discount']['giftcodesuccess'], $get_codesql['price']);
    sendmessage($from_id, $text_balance_code, $keyboard, 'HTML');
    $stmt = $pdo->prepare("INSERT INTO Giftcodeconsumed (id_user, code) VALUES (:id_user, :code)");
    $stmt->execute([
        ':id_user' => $from_id,
        ':code' => $text,
    ]);
    $text_report = sprintf($textbotlang['users']['Discount']['giftcodeused'], $username, $from_id, $text);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherreport,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
} elseif ($text == $textbotlang['textbot']['tariffList'] || $datain == "Tariff_list") {
    sendmessage($from_id, $textbotlang['textbot']['tariffListDesc'], null, 'HTML');
} elseif ($datain == "colselist") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['users']['back'], $keyboard, 'HTML');
} elseif ($text == $textbotlang['textbot']['affiliates'] || $datain == "affiliatesbtn") {
    if (!check_active_btn($setting['keyboardmain'], "text_affiliates")) {
        sendmessage($from_id, $textbotlang['users']['buttonDisabled'], null, 'HTML');
        return;
    }
    if ($setting['affiliatesstatus'] == "offaffiliates") {
        sendmessage($from_id, $textbotlang['users']['affiliates']['offaffiliates'], null, 'HTML');
        return;
    }
    $affiliates = select("affiliates", "*", null, null, "select");
    $textaffiliates = "{$affiliates['description']}\n\n🔗 https://t.me/$usernamebot?start=$from_id";
    if (strlen($affiliates['id_media']) >= 5) {
        telegram('sendphoto', [
            'chat_id' => $from_id,
            'photo' => $affiliates['id_media'],
            'caption' => $textaffiliates,
            'parse_mode' => "HTML",
        ]);
    }
    $affiliatescommission = select("affiliates", "*", null, null, "select");
    $sqlPanel = sprintf("SELECT COUNT(*) AS orders, SUM(price_product) AS total_price\n                 FROM invoice \n                 WHERE Status IN ('active', 'end_of_time', 'sendedwarn', 'send_on_hold') \n                 AND refral = '%s'\n                 AND name_product != '{$textbotlang['common']['labels']['testServiceName']}'", $from_id);
    $stmt = $pdo->prepare($sqlPanel);
    $stmt->execute();
    $inforefral = $stmt->fetch(PDO::FETCH_ASSOC);
    $inforefral['total_price'] = ($inforefral['total_price'] * $setting['affiliatespercentage']) / 100;
    $keyboard_share = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['receiveMembershipGift'], 'callback_data' => "get_gift_start"],
                ['text' => $textbotlang['keyboard']['shareLink'], 'url' => "https://t.me/share/url?url=https://t.me/$usernamebot?start=$from_id"],
            ],
        ]
    ]);
    $text_start = "";
    $text_porsant = "";
    $Percent_porsant = $setting['affiliatespercentage'];
    $sum_order = number_format($inforefral['total_price'], 0);
    if ($affiliatescommission['Discount'] == "onDiscountaffiliates") {
        $text_start = sprintf($textbotlang['users']['affiliates']['membershipGiftInfo'], $affiliatescommission['price_Discount']);
    }
    if ($affiliatescommission['status_commission'] == "oncommission") {
        $text_porsant = sprintf($textbotlang['users']['affiliates']['purchaseCommissionInfo'], $Percent_porsant);
    }
    $textaffiliates = sprintf($textbotlang['users']['affiliates']['welcomeGiftInfo'], $text_start, $text_porsant, $user['affiliatescount'], $inforefral['orders'], $sum_order);

    sendmessage($from_id, $textaffiliates, $keyboard_share, 'HTML');
} elseif ($datain == "get_gift_start") {
    $gift_status = select("affiliates", "*", null, null, "select");
    if ($gift_status['Discount'] == "offDiscountaffiliates") {
        sendmessage($from_id, $textbotlang['users']['sectionDisabled'], $keyboard, 'HTML');
        return;
    }
    if (!rowExists("user", "id", $user['affiliates'])) {
        sendmessage($from_id, $textbotlang['users']['affiliates']['notReferral'], $keyboard, 'HTML');
        return;
    }
    $reagent = select("reagent_report", "*", "user_id", $from_id, "select");
    if (!$reagent) {
        sendmessage($from_id, $textbotlang['users']['affiliates']['notReferral'], $keyboard, 'HTML');
        return;
    }
    update("reagent_report", "get_gift", true, "user_id", $from_id);
    if ($reagent['get_gift']) {
        sendmessage($from_id, $textbotlang['users']['affiliates']['membershipGiftClaimed'], $keyboard, 'HTML');
        return;
    }
    $reagent['get_gift'] = true;
    $price_gift_Start = select("affiliates", "*", null, null, "select");
    $price_gift_Start = intval($price_gift_Start['price_Discount']) / 2;
    $useraffiliates = select("user", "*", 'id', $reagent['reagent'], "select");
    $Balance_add_regent = $useraffiliates['Balance'] + $price_gift_Start;
    update("user", "Balance", $Balance_add_regent, "id", $reagent['reagent']);
    $Balance_add_user = $user['Balance'] + $price_gift_Start;
    update("user", "Balance", $Balance_add_user, "id", $from_id);
    $addbalancediscount = number_format($price_gift_Start, 0);
    sendmessage($reagent['reagent'], $textbotlang['users']['affiliates']['joinedGift'], null, 'html');
    sendmessage($from_id, $textbotlang['users']['affiliates']['joinGiftActivated'], null, 'html');
    $report_join_gift = sprintf($textbotlang['Admin']['reportgroup']['membershipGiftPaid'], $from_id, $username, $reagent['reagent'], $user['Balance'], $Balance_add_user, $useraffiliates['Balance'], $Balance_add_regent);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $porsantreport,
            'text' => $report_join_gift,
            'parse_mode' => "HTML"
        ]);
    }
} elseif (preg_match('/Extra_volumes_(\w+)_(.*)/', $datain, $dataget)) {
    $usernamepanel = $dataget[1];
    $invoice_owner = select("invoice", "*", "username", $usernamepanel, "select");
    if (!invoiceBelongsToUser($invoice_owner, $from_id)) {
        return;
    }
    $locations = select("marzban_panel", "*", "code_panel", $dataget[2], "select");
    $location = $locations['name_panel'];
    $eextraprice = json_decode($locations['priceextravolume'], true);
    $extrapricevalue = $eextraprice[$user['agent']];
    update("user", "Processing_value", $usernamepanel, "id", $from_id);
    update("user", "Processing_value_one", $location, "id", $from_id);

    $textextra = sprintf($textbotlang['users']['extraVolume']['enterextravolume'], $extrapricevalue);
    sendmessage($from_id, $textextra, $backuser, 'HTML');
    step('getvolumeextras', $from_id);
} elseif ($user['step'] == "getvolumeextras") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidVolume'], $backuser, 'HTML');
        return;
    }
    if ($text < 1) {
        sendmessage($from_id, $textbotlang['users']['extraVolume']['invalidprice'], $backuser, 'HTML');
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value_one'], "select");
    $eextraprice = json_decode($marzban_list_get['priceextravolume'], true);
    $extrapricevalue = $eextraprice[$user['agent']];
    $priceextra = $extrapricevalue * $text;
    $keyboardsetting = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['extraVolume']['extracheck'], 'callback_data' => 'confirmaextras_' . $priceextra],
            ]
        ]
    ]);
    $priceextra = number_format($priceextra, 0);
    $extrapricevalues = number_format($extrapricevalue, 0);
    $textextra = sprintf($textbotlang['users']['extraVolume']['extravolumeinvoice'], $extrapricevalues, $priceextra, $text);
    sendmessage($from_id, $textextra, $keyboardsetting, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/confirmaextras_(\w+)/', $datain, $dataget)) {
    $volume = $dataget[1];
    $invoice_owner = select("invoice", "*", "username", $user['Processing_value'], "select");
    if (!invoiceBelongsToUser($invoice_owner, $from_id)) {
        return;
    }
    if ($user['Balance'] < $volume && $user['agent'] != "n2") {
        $marzbandirectpay = select('shopSetting', "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        if ($marzbandirectpay == "offdirectbuy") {
            $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']]);
            $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']]);
            $bakinfos = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "account"],
                    ]
                ]
            ]);
            Editmessagetext($from_id, $message_id, sprintf($textbotlang['users']['Balance']['insufficientbalance'], $minbalance, $maxbalance), $bakinfos, 'HTML');
            step('getprice', $from_id);
            return;
        } else {
            if (intval($user['pricediscount']) != 0) {
                $result = ($volume * $user['pricediscount']) / 100;
                $volume = $volume - $result;
                sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
            }
            $Balance_prim = $volume - $user['Balance'];
            update("user", "Processing_value", $Balance_prim, "id", $from_id);
            sendmessage($from_id, $textbotlang['users']['sell']['noCredit'], $step_payment, 'HTML');
            step('get_step_payment', $from_id);
            return;
        }
    }
    if (intval($user['maxbuyagent']) != 0 and $user['agent'] == "n2") {
        if (($user['Balance'] - $volume) < intval("-" . $user['maxbuyagent'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['maxpurchasereached'], null, 'HTML');
            return;
        }
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value_one'], "select");
    if ($marzban_list_get == false) {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        return;
    }
    $eextraprice = json_decode($marzban_list_get['priceextravolume'], true);
    $extrapricevalue = $eextraprice[$user['agent']];
    deletemessage($from_id, $message_id);
    if (intval($user['pricediscount']) != 0) {
        $result = ($volume * $user['pricediscount']) / 100;
        $volume = $volume - $result;
        sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
    }

    $DataUserOut = $ManagePanel->DataUser($user['Processing_value_one'], $user['Processing_value']);
    $data_limit = $DataUserOut['data_limit'] + (intval($volume) / intval($extrapricevalue) * pow(1024, 3));
    $stmt = $pdo->prepare("INSERT IGNORE INTO service_other (id_user, username, value, type, time, price) VALUES (:id_user, :username, :value, :type, :time, :price)");
    $value = $data_limit;
    $dateacc = date('Y/m/d H:i:s');
    $type = "extra_not_user";
    $stmt->execute([
        ':id_user' => $from_id,
        ':username' => $user['Processing_value'],
        ':value' => $value,
        ':type' => $type,
        ':time' => $dateacc,
        ':price' => $volume,
    ]);
    $data_limit_new = (intval($volume) / intval($extrapricevalue));
    $extra_volume = $ManagePanel->extra_volume($user['Processing_value'], $marzban_list_get['code_panel'], $data_limit_new);
    if ($extra_volume['status'] == false) {
        $extra_volume['msg'] = json_encode($extra_volume['msg']);
        $textreports = sprintf($textbotlang['Admin']['reportgroup']['errorExtraVolume2'], $user['Processing_value_one'], $user['Processing_value'], $extra_volume['msg']);
        sendmessage($from_id, $textbotlang['users']['extraVolume']['serviceError'], null, 'HTML');
        if (strlen($setting['Channel_Report']) > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $errorreport,
                'text' => $textreports,
                'parse_mode' => "HTML"
            ]);
        }
        return;
    }
    $Balance_Low_user = $user['Balance'] - $volume;
    update("user", "Balance", $Balance_Low_user, "id", $from_id);
    $back = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['backbtn'], 'callback_data' => 'backuser'],
            ]
        ]
    ]);
    sendmessage($from_id, $textbotlang['users']['extend']['thanks'], $back, 'HTML');
    $volumes = $volume / $extrapricevalue;
    $volumes = number_format($volumes, 0);
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['volumePurchase'], $from_id, $volumes, $volume, $user['Balance'], $user['Processing_value']);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
} elseif ($datain == "searchservice") {
    sendmessage($from_id, $textbotlang['users']['search']['usernamgeget'], $backuser, 'HTML');
    step('getuseragnetservice', $from_id);
} elseif ($datain == "Responseuser") {
    step('getmessageAsuser', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['getTextResponse'], $backuser, 'HTML');
} elseif ($user['step'] == "getmessageAsuser") {
    sendmessage($from_id, $textbotlang['users']['support']['sendmessageadmin'], $keyboard, 'HTML');
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['support']['answermessage'], 'callback_data' => 'Response_' . $from_id],
            ],
        ]
    ]);
    foreach ($admin_ids as $id_admin) {
        $adminrulecheck = select("admin", "*", "id_admin", $id_admin, "select");
        if ($adminrulecheck['rule'] == "Seller")
            continue;
        if ($text) {
            $textsendadmin = sprintf($textbotlang['Admin']['messageBulk']['userMessage'], $from_id, $username, $caption . $text);
            sendmessage($id_admin, $textsendadmin, $Response, 'HTML');
        }
        if ($photo) {
            $textsendadmin = sprintf($textbotlang['Admin']['messageBulk']['userResponse'], $from_id, $username, $caption);
            telegram('sendphoto', [
                'chat_id' => $id_admin,
                'photo' => $photoid,
                'reply_markup' => $Response,
                'caption' => $textsendadmin,
                'parse_mode' => "HTML",
            ]);
        }
    }
    step('home', $from_id);
} elseif (($text == $textbotlang['textbot']['agentPanel'] || $datain == "agentpanel") && $user['agent'] != "f") {
    if ($setting['inlinebtnmain'] == "oninline") {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['agent']['welcome'], $keyboardagent, 'HTML');
    } else {
        sendmessage($from_id, $textbotlang['users']['agent']['welcome'], $keyboardagent, 'HTML');
    }
} elseif ($text == $textbotlang['users']['agent']['customnameusername'] || $datain == "selectname") {
    sendmessage($from_id, $textbotlang['users']['selectusername'], $backuser, 'html');
    step('selectusernamecustom', $from_id);
} elseif ($user['step'] == "selectusernamecustom") {
    if (!preg_match('~(?!_)^[a-z][a-z\d_]{2,32}(?<!_)$~i', $text)) {
        sendmessage($from_id, $textbotlang['users']['invalidusername'], $backuser, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['users']['agent']['usernameSaved'], $keyboardagent, 'html');
    update("user", "namecustom", $text, "id", $from_id);
    step("home", $from_id);
} elseif ($text == $textbotlang['textbot']['requestAgent'] || $datain == "requestagent") {
    if ($user['Balance'] < $setting['agentreqprice']) {
        $priceagent = number_format($setting['agentreqprice']);
        sendmessage($from_id, sprintf($textbotlang['users']['agent']['insufficientbalanceagent'], $priceagent), $backuser, 'HTML');
        return;
    }
    $countagentrequest = select("Requestagent", "*", "id", $from_id, "count");
    if ($countagentrequest != 0) {
        sendmessage($from_id, $textbotlang['users']['agent']['requestreport'], null, 'html');
        return;
    }
    if ($user['agent'] != "f") {
        sendmessage($from_id, $textbotlang['users']['agent']['isagent'], null, 'html');
        return;
    }
    if ($datain == "requestagent") {
        Editmessagetext($from_id, $message_id, $textbotlang['textbot']['agentRequestDesc'], $backuser);
    } else {
        sendmessage($from_id, $textbotlang['textbot']['agentRequestDesc'], $backuser, 'html');
    }
    step("getagentrequest", $from_id);
} elseif ($user['step'] == "getagentrequest" && $text) {
    $balancelow = $user['Balance'] - $setting['agentreqprice'];
    update("user", "Balance", $balancelow, "id", $from_id);
    sendmessage($from_id, $textbotlang['users']['agent']['endrequest'], $keyboard, 'html');
    step("home", $from_id);
    $stmt = $pdo->prepare("INSERT INTO Requestagent (id, username, time, Description, status, type) VALUES (:id, :username, :time, :description, :status, :type)");
    $status = "waiting";
    $type = "None";
    $current_time = time();
    $description = $text;
    $requestAgentInserted = false;
    try {
        $stmt->execute([
            ':id' => $from_id,
            ':username' => $username,
            ':time' => $current_time,
            ':description' => $description,
            ':status' => $status,
            ':type' => $type,
        ]);
        $requestAgentInserted = true;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Incorrect string value') !== false) {
            $tableConverted = ensureTableUtf8mb4('Requestagent');
            if ($tableConverted) {
                try {
                    $stmt->execute([
                        ':id' => $from_id,
                        ':username' => $username,
                        ':time' => $current_time,
                        ':description' => $description,
                        ':status' => $status,
                        ':type' => $type,
                    ]);
                    $requestAgentInserted = true;
                } catch (PDOException $retryException) {
                    error_log('Retry after charset conversion failed: ' . $retryException->getMessage());
                }
            }

            if (!$requestAgentInserted) {
                $sanitisedDescription = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $description);
                if ($sanitisedDescription !== $description) {
                    $stmt->execute([
                        ':id' => $from_id,
                        ':username' => $username,
                        ':time' => $current_time,
                        ':description' => $sanitisedDescription,
                        ':status' => $status,
                        ':type' => $type,
                    ]);
                    $requestAgentInserted = true;
                } else {
                    throw $e;
                }
            }
        } else {
            throw $e;
        }
    }

    if (!$requestAgentInserted) {
        throw new RuntimeException('Failed to persist agent request description.');
    }
    $textrequestagent = sprintf($textbotlang['users']['agent']['agentRequest'], $from_id, $username, $first_name, $text);
    $keyboardmanage = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['users']['agent']['acceptrequest'], 'callback_data' => "addagentrequest_" . $from_id], ['text' => $textbotlang['users']['agent']['rejectrequest'], 'callback_data' => "rejectrequesta_" . $from_id]],
            [
                ['text' => $textbotlang['users']['SendMessage'], 'callback_data' => 'Response_' . $from_id],
            ],
        ]
    ]);
    foreach ($admin_ids as $admin) {
        sendmessage($admin, $textrequestagent, $keyboardmanage, 'HTML');
    }
} elseif ($text == "/privacy") {
    sendmessage($from_id, $textbotlang['textbot']['rules'], null, 'HTML');
} elseif ($text == $textbotlang['textbot']['wheelLuck'] || $datain == "wheel_luck" || $text == "/gift") {
    if (!check_active_btn($setting['keyboardmain'], "text_wheel_luck")) {
        sendmessage($from_id, $textbotlang['users']['buttonDisabled'], null, 'HTML');
        return;
    }
    if ($setting['wheelagent'] == "0" and $user['agent'] != "f") {
        sendmessage($from_id, $textbotlang['users']['buttonDisabledForYou'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE name_product != :name_product  AND id_user = :id_user AND status != 'Unpaid'");
    $stmt->bindParam(':id_user', $from_id);
    $stmt->bindParam(':name_product', $textbotlang['common']['labels']['testServiceName']);
    $stmt->execute();
    $countinvoice = $stmt->rowCount();
    if (intval($setting['statusfirstwheel']) == 1 and $countinvoice != 0) {
        sendmessage($from_id, $textbotlang['users']['sell']['noPurchaseUsersOnly'], null, 'HTML');
        return;
    }
    if ($setting['wheelـluck'] == "0" or ($setting['wheelagent'] == "0" and $user['agent'] != "f")) {
        sendmessage($from_id, $textbotlang['users']['wheelLuck']['featureDisabled'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM wheel_list  WHERE id_user = :from_id ORDER BY time DESC LIMIT 1");
    $stmt->bindParam(':from_id', $from_id);
    $stmt->execute();
    $USER = $stmt->fetch(PDO::FETCH_ASSOC);
    $timelast = isset($USER['time']) ? strtotime($USER['time']) : false;
    if ($USER && $timelast !== false && (time() - $timelast) <= 86400) {
        sendmessage($from_id, $textbotlang['users']['wheelLuck']['alreadyParticipated'], null, 'HTML');
        return;
    }
    if (intval($setting['Dice']) == 1) {
        $diceResponse = telegram('sendDice', [
            'chat_id' => $from_id,
            'emoji' => "🎲",
        ]);
        sleep((int) 4.5);
    } else {
        $diceResponse = telegram('sendDice', [
            'chat_id' => $from_id,
            'emoji' => "🎰",
        ]);
        sleep(2);
    }
    if (!is_array($diceResponse) || empty($diceResponse['ok']) || !isset($diceResponse['result']['dice']['value'])) {
        $errorContext = is_array($diceResponse) ? json_encode($diceResponse) : (is_string($diceResponse) ? $diceResponse : 'empty response');
        error_log('Failed to receive dice value for wheel_luck: ' . $errorContext);
        sendmessage($from_id, $textbotlang['users']['wheelLuck']['error'] ?? $textbotlang['users']['wheelLuck']['resultError'], null, 'HTML');
        return;
    }
    $diceValue = (int) $diceResponse['result']['dice']['value'];
    $dateacc = date('Y/m/d H:i:s');
    $stmt = $pdo->prepare("SELECT * FROM wheel_list  WHERE id_user = :from_id ORDER BY time DESC LIMIT 1");
    $stmt->bindParam(':from_id', $from_id);
    $stmt->execute();
    $USER = $stmt->fetch(PDO::FETCH_ASSOC);
    $timelast = isset($USER['time']) ? strtotime($USER['time']) : false;
    if ($USER && $timelast !== false && (time() - $timelast) <= 86400) {
        sendmessage($from_id, $textbotlang['users']['wheelLuck']['alreadyParticipated'], null, 'HTML');
        return;
    }
    $status = false;
    if (intval($setting['Dice']) == 1) {
        if ($diceValue === 6) {
            $status = true;
        }
    } else {
        if (in_array($diceValue, [1, 43, 64, 22], true)) {
            $status = true;
        }
    }
    if ($status) {
        $balance_last = intval($setting['wheelـluck_price']) + $user['Balance'];
        update("user", "Balance", $balance_last, "id", $from_id);
        $price = number_format($setting['wheelـluck_price']);
        sendmessage($from_id, sprintf($textbotlang['users']['wheelLuck']['winnerCongratulations'], $price), null, 'HTML');
        $pricelast = $setting['wheelـluck_price'];
        if (strlen($setting['Channel_Report']) > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $otherreport,
                'text' => sprintf($textbotlang['users']['wheelLuck']['wheelWinner'], $username, $from_id),
                'parse_mode' => "HTML"
            ]);
        }
    } else {
        sendmessage($from_id, $textbotlang['users']['wheelLuck']['notWinner'], null, 'HTML');
        $pricelast = 0;
    }
    $stmt = $pdo->prepare("INSERT IGNORE INTO wheel_list (id_user,first_name,wheel_code,time,price) VALUES (:id_user,:first_name,:wheel_code,:time,:price)");
    $stmt->bindParam(':id_user', $from_id);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':wheel_code', $diceValue);
    $stmt->bindParam(':time', $dateacc);
    $stmt->bindParam(':price', $pricelast);
    $stmt->execute();
} elseif ($text == "/tron") {
    $rates = rate_arze(['TRX']);
    if ($rates === null) {
        sendmessage($from_id, $textbotlang['users']['priceArze']['fetchError'], null, 'HTML');
        return;
    }
    $price = $rates['TRX'];
    sendmessage($from_id, sprintf($textbotlang['users']['priceArze']['tronPrice'], $price), null, 'HTML');
} elseif ($text == "/usd") {
    $rates = rate_arze(['USD']);
    if ($rates === null) {
        sendmessage($from_id, $textbotlang['users']['priceArze']['fetchError'], null, 'HTML');
        return;
    }
    $price = $rates['USD'];
    sendmessage($from_id, sprintf($textbotlang['users']['priceArze']['tetherPrice'], $price), null, 'HTML');
} elseif ($text == $textbotlang['textbot']['extend'] or $datain == "extendbtn") {
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :id_user AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold')");
    $stmt->bindParam(':id_user', $from_id);
    $stmt->execute();
    $invoices = $stmt->rowCount();
    if ($invoices == 0) {
        sendmessage($from_id, $textbotlang['users']['extend']['emptyServiceforExtend'], null, 'html');
        return;
    }
    $pages = 1;
    update("user", "pagenumber", $pages, "id", $from_id);
    $page = 1;
    $items_per_page = 20;
    $start_index = ($page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :from_id AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR status = 'send_on_hold') ORDER BY time_sell DESC LIMIT :start_index, :items_per_page");
    $result->bindParam(':from_id', $from_id, PDO::PARAM_STR);
    $result->bindParam(':start_index', $start_index, PDO::PARAM_INT);
    $result->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    if ($statusnote) {
        while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
            $data = "";
            if ($row != null)
                $data = " | {$row['note']}";
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . $data . "✨",
                    'callback_data' => "extend_" . $row['id_invoice']
                ],
            ];
        }
    } else {
        while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . "✨",
                    'callback_data' => "extend_" . $row['id_invoice']
                ],
            ];
        }
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_page_extends'
        ]
    ];
    $backuser = [
        [
            'text' => $textbotlang['users']['backbtn'],
            'callback_data' => 'backuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backuser;
    $keyboard_json = json_encode($keyboardlists);
    if ($datain == "backorder") {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['extend']['selectOrderDirect'], $keyboard_json);
    } else {
        sendmessage($from_id, $textbotlang['users']['extend']['selectOrderDirect'], $keyboard_json, 'html');
    }
} elseif ($datain == 'next_page_extends') {
    $numpage = select("invoice", "id_user", "id_user", $from_id, "count");
    $page = $user['pagenumber'];
    $items_per_page = 20;
    $sum = $user['pagenumber'] * $items_per_page;
    if ($sum > $numpage) {
        $next_page = 1;
    } else {
        $next_page = $page + 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :from_id AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') ORDER BY time_sell DESC LIMIT :start_index, :items_per_page");
    $result->bindParam(':from_id', $from_id, PDO::PARAM_STR);
    $result->bindParam(':start_index', $start_index, PDO::PARAM_INT);
    $result->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    if ($statusnote) {
        while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
            $data = "";
            if ($row != null)
                $data = " | {$row['note']}";
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . $data . "✨",
                    'callback_data' => "extend_" . $row['id_invoice']
                ],
            ];
        }
    } else {
        while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . "✨",
                    'callback_data' => "extend_" . $row['id_invoice']
                ],
            ];
        }
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_page_extends'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_page_extends'
        ]
    ];
    $backuser = [
        [
            'text' => $textbotlang['users']['backbtn'],
            'callback_data' => 'backuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backuser;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['extend']['selectOrderDirect'], $keyboard_json);
} elseif ($datain == 'previous_page_extends') {
    $numpage = select("invoice", "id_user", "id_user", $from_id, "count");
    $page = $user['pagenumber'];
    $items_per_page = 20;
    $sum = $user['pagenumber'] * $items_per_page;
    if ($sum > $numpage) {
        $previous_page = 1;
    } else {
        $previous_page = $page - 1;
    }
    if ($previous_page < 1) {
        $previous_page = 1;
    }
    $start_index = ($previous_page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :from_id AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') ORDER BY time_sell DESC LIMIT :start_index, :items_per_page");
    $result->bindParam(':from_id', $from_id, PDO::PARAM_STR);
    $result->bindParam(':start_index', $start_index, PDO::PARAM_INT);
    $result->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    if ($statusnote) {
        while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
            $data = "";
            if ($row != null)
                $data = " | {$row['note']}";
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . $data . "✨",
                    'callback_data' => "extend_" . $row['id_invoice']
                ],
            ];
        }
    } else {
        while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => "✨" . $row['username'] . "✨",
                    'callback_data' => "extend_" . $row['id_invoice']
                ],
            ];
        }
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_page_extends'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_page_extends'
        ]
    ];
    $backuser = [
        [
            'text' => $textbotlang['users']['backbtn'],
            'callback_data' => 'backuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backuser;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $previous_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['extend']['selectOrderDirect'], $keyboard_json);
} elseif ($datain == "linkappdownlod") {
    $countapp = select("app", "*", null, null, "count");
    if ($countapp == 0) {
        sendmessage($from_id, $textbotlang['users']['app']['appempty'], $json_list_helpـlink, "html");
        return;
    }
    sendmessage($from_id, $textbotlang['users']['app']['selectapp'], $json_list_helpـlink, "html");
} elseif (preg_match('/changenote_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if (!invoiceBelongsToUser($nameloc, $from_id)) {
        return;
    }
    update("user", "Processing_value", $id_invoice, "id", $from_id);
    $backinfoss = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_" . $id_invoice],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['note']['sendNote'], $backinfoss);
    step("getnotedit", $from_id);
} elseif ($user['step'] == "getnotedit") {
    $invoice = select("invoice", "*", "id_invoice", $user['Processing_value'], "select");
    if (!invoiceBelongsToUser($invoice, $from_id)) {
        return;
    }
    if (strlen($text) > 150) {
        sendmessage($from_id, $textbotlang['users']['note']['errorLongNote'], $keyboard, "html");
        return;
    }
    $text = sanitizeUserName($text);
    $id_invoice = $user['Processing_value'];
    $backinfoss = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "product_" . $id_invoice],
            ]
        ]
    ]);
    update("invoice", "note", $text, "id_invoice", $id_invoice);
    sendmessage($from_id, $textbotlang['users']['note']['changednote'], $backinfoss, "html");
    step("home", $from_id);
    $timejalali = jdate('Y/m/d H:i:s');
    $textreport = sprintf($textbotlang['Admin']['reportgroup']['noteChanged'], $invoice['username'], $invoice['note'], $text, $timejalali);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherreport,
            'text' => $textreport,
            'parse_mode' => "HTML"
        ]);
    }
}
if (isset($update['pre_checkout_query'])) {
    $userid = $update['pre_checkout_query']['from']['id'];
    $id_order = $update['pre_checkout_query']['invoice_payload'];
    $Payment_report = select("Payment_report", "*", "id_order", $id_order, "select");
    if ($Payment_report == false) {
        return;
    } else {
        telegram('answerPreCheckoutQuery', [
            'pre_checkout_query_id' => $update['pre_checkout_query']['id'],
            'ok' => true,
        ]);
    }
    if ($Payment_report['payment_Status'] == "paid") {
        return;
    }
    update("Payment_report", "dec_not_confirmed", json_encode($update['pre_checkout_query']), "id_order", $Payment_report['id_order']);
}
if (isset($update['message']['successful_payment'])) {
    $id_order = $update['message']['successful_payment']['invoice_payload'];
    $Payment_report = select("Payment_report", "*", "id_order", $id_order, "select");
    if ($Payment_report == false) {
        return;
    }
    if ($Payment_report['payment_Status'] == "paid") {
        return;
    }
    update("Payment_report", "dec_not_confirmed", $Payment_report['dec_not_confirmed'] . json_encode($update['message']['successful_payment']), "id_order", $Payment_report['id_order']);
    DirectPayment($Payment_report['id_order']);
    $pricecashback = select("PaySetting", "ValuePay", "NamePay", "chashbackstar", "select")['ValuePay'];
    $Balance_id = select("user", "*", "id", $Payment_report['id_user'], "select");
    if ($pricecashback != "0") {
        $result = ($Payment_report['price'] * $pricecashback) / 100;
        $Balance_confrim = intval($Balance_id['Balance']) + $result;
        update("user", "Balance", $Balance_confrim, "id", $Balance_id['id']);
        $text_report = sprintf($textbotlang['users']['Discount']['gift-deposit'], $result);
        sendmessage($Balance_id['id'], $text_report, null, 'HTML');
    }
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $paymentreports,
            'text' => sprintf($textbotlang['Admin']['reportgroup']['newPaymentStar'], $Balance_id['username'], $Balance_id['id'], $Payment_report['price'], $update['pre_checkout_query']['total_amount']),
            'parse_mode' => "HTML"
        ]);
    }
    update("Payment_report", "payment_Status", "paid", "id_order", $Payment_report['id_order']);
} elseif (preg_match('/extends_(\w+)_(.*)/', $datain, $dataget)) {
    $username = $dataget[1];
    $location = select("marzban_panel", "*", "code_panel", $user['Processing_value_four'], "select");
    if ($location == false) {
        sendmessage($from_id, $textbotlang['users']['genericRestart2'], null, 'html');
        return;
    }
    $location = $location['name_panel'];
    update("user", "Processing_value", $location, "id", $from_id);
    $query = "SELECT * FROM product WHERE (Location = :loc OR Location = '/all') AND agent = :agent";
    $queryParams = [':loc' => $location, ':agent' => $user['agent']];
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $location, "select");
    if (empty($marzban_list_get)) {
        sendmessage($from_id, $textbotlang['users']['status']['infoUnavailable'], null, 'HTML');
        step('home', $from_id);
        return;
    }
    $statuscustomvolume = json_decode($marzban_list_get['customvolume'] ?? '[]', true)[$user['agent']] ?? null;
    if (in_array(usernameMethodKey($marzban_list_get['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
        $datakeyboard = "prodcutservicesom_";
    } else {
        $datakeyboard = "prodcutserviceom_";
    }
    if ($statuscustomvolume == "1" && $marzban_list_get['type'] != "Manualsale") {
        $statuscustom = true;
    } else {
        $statuscustom = false;
    }
    Editmessagetext($from_id, $message_id, $textbotlang['users']['extend']['selectservice'], KeyboardProduct($marzban_list_get['name_panel'], $query, $user['pricediscount'], "serviceextendselects-", false, "backuser", $username, "customsellvolume", $queryParams));
} elseif (preg_match('/^serviceextendselects-(.*)-(.*)/', $datain, $dataget)) {
    deletemessage($from_id, $message_id);
    $codeproduct = $dataget[1];
    $username = $dataget[2];
    $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :processing_value OR Location = '/all') AND agent = :agent AND code_product = :code_product");
    $stmt->execute([
        ':processing_value' => $user['Processing_value'],
        ':agent' => $user['agent'],
        ':code_product' => $codeproduct,
    ]);
    $prodcut = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($prodcut == false) {
        sendmessage($from_id, $textbotlang['users']['erroroccurred'], $keyboard, 'html');
        return;
    }
    $keyboardextend = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['extend']['confirm'], 'callback_data' => "confirmserivces-" . $codeproduct . "-" . $username],
            ]
        ]
    ]);
    sendmessage($from_id, sprintf($textbotlang['users']['extend']['renewalinvoice'], $username, $prodcut['name_product'], $prodcut['price_product'], $prodcut['Service_time'], $prodcut['Volume_constraint'], $prodcut['note'], $user['Balance']), $keyboardextend, 'html');
} elseif (preg_match('/^confirmserivces-(.*)-(.*)/', $datain, $dataget)) {
    $codeproduct = $dataget[1];
    $usernamePanelExtends = $dataget[2];
    deletemessage($from_id, $message_id);
    $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :processing_value OR Location = '/all') AND agent = :agent AND code_product = :code_product");
    $stmt->execute([
        ':processing_value' => $user['Processing_value'],
        ':agent' => $user['agent'],
        ':code_product' => $codeproduct,
    ]);
    $prodcut = $stmt->fetch(PDO::FETCH_ASSOC);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $usernamePanelExtends);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['extend']['renewalerror'], $keyboard, 'HTML');
        return;
    }
    if ($marzban_list_get == false) {
        sendmessage($from_id, $textbotlang['users']['extend']['renewalerror'], $keyboard, 'HTML');
        return;
    }
    if ($user['Balance'] < $prodcut['price_product'] && $user['agent'] != "n2") {
        $marzbandirectpay = select('shopSetting', "*", "Namevalue", "statusdirectpabuy", "select")['value'];
        if ($marzbandirectpay == "offdirectbuy") {
            $minbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "minbalance", "select")['ValuePay'], true)[$user['agent']]);
            $maxbalance = number_format(json_decode(select("PaySetting", "*", "NamePay", "maxbalance", "select")['ValuePay'], true)[$user['agent']]);
            $bakinfos = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "account"],
                    ]
                ]
            ]);
            Editmessagetext($from_id, $message_id, sprintf($textbotlang['users']['Balance']['insufficientbalance'], $minbalance, $maxbalance), $bakinfos, 'HTML');
            step('getprice', $from_id);
            return;
        } else {
            if (intval($user['pricediscount']) != 0) {
                $result = ($prodcut['price_product'] * $user['pricediscount']) / 100;
                $prodcut['price_product'] = $prodcut['price_product'] - $result;
                sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
            }
            $Balance_prim = $prodcut['price_product'] - $user['Balance'];
            update("user", "Processing_value", $Balance_prim, "id", $from_id);
            sendmessage($from_id, $textbotlang['users']['sell']['noCredit'], $step_payment, 'HTML');
            step('get_step_payment', $from_id);
            return;
        }
    }
    if (intval($user['maxbuyagent']) != 0 and $user['agent'] == "n2") {
        if (($user['Balance'] - $prodcut['price_product']) < intval("-" . $user['maxbuyagent'])) {
            sendmessage($from_id, $textbotlang['users']['Balance']['maxpurchasereached'], null, 'HTML');
            return;
        }
    }
    if (intval($user['pricediscount']) != 0) {
        $result = ($prodcut['price_product'] * $user['pricediscount']) / 100;
        $prodcut['price_product'] = $prodcut['price_product'] - $result;
        sendmessage($from_id, sprintf($textbotlang['users']['Discount']['discountapplied'], $user['pricediscount']), null, 'HTML');
    }
    $Balance_Low_user = $user['Balance'] - $prodcut['price_product'];
    update("user", "Balance", $Balance_Low_user, "id", $from_id);
    $extend = $ManagePanel->extend($marzban_list_get['Methodextend'], $prodcut['Volume_constraint'], $prodcut['Service_time'], $usernamePanelExtends, $prodcut['code_product'], $marzban_list_get['code_panel']);
    if ($extend['status'] == false) {
        $extend['msg'] = json_encode($extend['msg']);
        $textreports = sprintf($textbotlang['Admin']['reportgroup']['errorRenewService2'], $marzban_list_get['name_panel'], $usernamePanelExtends, $extend['msg']);
        sendmessage($from_id, $textbotlang['users']['extend']['errorSupport'], null, 'HTML');
        if (strlen($setting['Channel_Report']) > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'message_thread_id' => $errorreport,
                'text' => $textreports,
                'parse_mode' => "HTML"
            ]);
        }
        return;
    }
    $stmt = $pdo->prepare("INSERT IGNORE INTO service_other (id_user, username, value, type, time, price,output) VALUES (:id_user, :username, :value, :type, :time, :price,:output)");
    $value = json_encode(array(
        "volumebuy" => $prodcut['Volume_constraint'],
        "Service_time" => $prodcut['Service_time'],
        "oldvolume" => $DataUserOut['data_limit'],
        "oldtime" => $DataUserOut['expire'],
        'code_product' => $prodcut['code_product'],
    ));
    $dateacc = date('Y/m/d H:i:s');
    $type = "extends_not_user";
    $stmt->execute([
        ':id_user' => $from_id,
        ':username' => $usernamePanelExtends,
        ':value' => $value,
        ':type' => $type,
        ':time' => $dateacc,
        ':price' => $prodcut['price_product'],
        ':output' => json_encode($extend)
    ]);
    $prodcut['price_product'] = number_format($prodcut['price_product']);
    $balanceformatsell = number_format(select("user", "Balance", "id", $from_id, "select")['Balance'], 0);
    $textextend = sprintf($textbotlang['users']['extend']['success2'], $usernamePanelExtends, $prodcut['name_product'], $prodcut['price_product']);
    sendmessage($from_id, $textextend, $keyboard, 'HTML');
    $timejalali = jdate('Y/m/d H:i:s');
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['renewalDetails'], $from_id, $username, $usernamePanelExtends, $first_name, $marzban_list_get['name_panel'], $prodcut['name_product'], $prodcut['Volume_constraint'], $prodcut['Service_time'], $prodcut['price_product'], $balanceformatsell, $timejalali);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
} elseif ($datain == "ّchange_language") {
    $keyboard_language = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['language']['btnFa'], 'callback_data' => "setlang:fa"],
                ['text' => $textbotlang['language']['btnEn'], 'callback_data' => "setlang:en"],
            ],
            [
                ['text' => $textbotlang['language']['btnZh'], 'callback_data' => "setlang:zh"],
                ['text' => $textbotlang['language']['btnRu'], 'callback_data' => "setlang:ru"],
            ],
            [
                ['text' => $textbotlang['users']['backbtn'], 'callback_data' => "account"]
            ],
        ]
    ]);
    $text_change_lang = $textbotlang['language']['selectPrompt'];
    Editmessagetext($from_id, $message_id, $text_change_lang, $keyboard_language);

} elseif (preg_match('/^setlang:(.*)/', $datain, $dataget)) {
    $lang = $dataget[1];
    update("user", "lang", $lang, "id", $from_id);
    $keyboard_back = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['backbtn'], 'callback_data' => "account"]
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['language']['setSuccess'], $keyboard_back);
}
if (in_array($from_id, $admin_ids))
    require_once 'admin.php';

$pdo = null;