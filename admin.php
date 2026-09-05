<?php
#----------------[  admin section  ]------------------#
$version = file_get_contents('version');
$textadmin = ["panel", "/panel", $textbotlang['Admin']['panelAdmin']];
$text_panel_admin_login_template = sprintf($textbotlang['Admin']['report']['aboutBot'], $version);

if (!in_array($from_id, $admin_ids))
    return;
$domainhostsEscaped = htmlspecialchars($domainhosts, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$miniAppInstructionText = sprintf($textbotlang['Admin']['webpanel']['miniAppHelp'], $domainhostsEscaped);

if (in_array($text, $textadmin) || $datain == "admin") {
    if ($datain == "admin")
        deletemessage($from_id, $message_id);
    if ($buyreport == "0" || $otherservice == "0" || $otherreport == "0" || $paymentreports == "0" || $reporttest == "0" || $errorreport == "0") {
        sendmessage($from_id, $textbotlang['Admin']['activeBotText'], $active_panell, 'HTML');
        return;
    }
    $version_mini_app = file_get_contents('app/version');
    activecron();
    $text_admin = sprintf($text_panel_admin_login_template, $version, $version_mini_app);
    sendmessage($from_id, $text_admin, $keyboardadmin, 'HTML');
    $miniAppInstructionHidden = isset($user['hide_mini_app_instruction']) ? (string) $user['hide_mini_app_instruction'] : '0';
    if ($miniAppInstructionHidden !== '1') {
        $miniAppInstructionKeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['Admin']['report']['btnDontShowAgain'], 'callback_data' => 'hide_mini_app_instruction'],
                ],
            ],
        ]);
        sendmessage($from_id, $miniAppInstructionText, $miniAppInstructionKeyboard, 'HTML');
    }
} elseif ($text == $textbotlang['Admin']['backAdminBtn']) {
    if ($buyreport == "0" || $otherservice == "0" || $otherreport == "0" || $paymentreports == "0" || $reporttest == "0" || $errorreport == "0") {
        sendmessage($from_id, $textbotlang['Admin']['activeBotText'], $active_panell, 'HTML');
        return;
    }
    $version_mini_app = file_get_contents('app/version');
    $text_admin = sprintf($text_panel_admin_login_template, $version, $version_mini_app);
    sendmessage($from_id, $text_admin, $keyboardadmin, 'HTML');
    step('home', $from_id);
    return;
} elseif ($datain == "hide_mini_app_instruction") {
    if (!in_array($from_id, $admin_ids))
        return;
    if (($user['hide_mini_app_instruction'] ?? '0') !== '1') {
        update("user", "hide_mini_app_instruction", "1", "id", $from_id);
        $user['hide_mini_app_instruction'] = '1';
    }
    $confirmationKeyboard = json_encode(['inline_keyboard' => []]);
    $confirmationText = $miniAppInstructionText . $textbotlang['Admin']['report']['msgHidden'];
    Editmessagetext($from_id, $message_id, $confirmationText, $confirmationKeyboard, 'HTML');
    return;
} elseif ($text == $textbotlang['Admin']['backMenuBtn']) {
    if ($buyreport == "0" || $otherservice == "0" || $otherreport == "0" || $paymentreports == "0" || $reporttest == "0" || $errorreport == "0") {
        sendmessage($from_id, $textbotlang['Admin']['activeBotText'], $setting_panel, 'HTML');
        return;
    }
    step('home', $from_id);
    if (in_array($user['step'], ["updatetime", "val_usertest", "getlimitnew", "GetusernameNew", "GeturlNew", "protocolset", "updatemethodusername", "GetNameNew", "getprotocol", "getprotocolremove", "GetpaawordNew", "updateextendmethod", "setpricechangelocation"])) {
        $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
        outtypepanel($typepanel['type'], $textbotlang['Admin']['backMenu']);
    } elseif (in_array($user['step'], ["selectloc", "get_limit", "selectlocedite", "GetPriceExtra", "GetPriceexstratime", "GetPricecustomtime", "GetPricecustomvolume", "get_code", "get_codesell", "minbalancebulk"])) {
        sendmessage($from_id, $textbotlang['Admin']['backMenu'], $shopkeyboard, 'HTML');
    } elseif (in_array($user['step'], ["addchannel", "removechannel"])) {
        sendmessage($from_id, $textbotlang['Admin']['backMenu'], $channelkeyboard, 'HTML');
    } else {
        sendmessage($from_id, $textbotlang['Admin']['backAdmin'], $keyboardadmin, 'HTML');
    }
    return;
} elseif ($text == $textbotlang['Admin']['channel']['title'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['channel']['changeChannel'], $backadmin, 'HTML');
    step('addchannel', $from_id);
} elseif ($user['step'] == "addchannel") {
    savedata("clear", "link", $text);
    sendmessage($from_id, $textbotlang['Admin']['channel']['askButtonName'], $backadmin, 'HTML');
    step('getremark', $from_id);
} elseif ($user['step'] == "getremark") {
    savedata("save", "remark", $text);
    sendmessage($from_id, $textbotlang['Admin']['channel']['askJoinLink'], $backadmin, 'HTML');
    step('getlinkjoin', $from_id);
} elseif ($user['step'] == "getlinkjoin") {
    if (!filter_var($text, FILTER_VALIDATE_URL)) {
        sendmessage($from_id, $textbotlang['Admin']['channel']['invalidJoinLink'], $backadmin, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    if (!is_array($userdata)) {
        $userdata = [];
    }

    $remark = isset($userdata['remark']) ? (string) $userdata['remark'] : '';
    $link = isset($userdata['link']) ? (string) $userdata['link'] : '';

    sendmessage($from_id, $textbotlang['Admin']['channel']['joinChannelSaved'], $channelkeyboard, 'HTML');
    step('home', $from_id);

    $insertChannel = function ($remarkValue) use ($pdo, $link, $text) {
        $stmt = $pdo->prepare("INSERT INTO channels (link, remark, linkjoin) VALUES (:link, :remark, :linkjoin)");
        $stmt->bindValue(':remark', $remarkValue, PDO::PARAM_STR);
        $stmt->bindValue(':link', $link, PDO::PARAM_STR);
        $stmt->bindValue(':linkjoin', $text, PDO::PARAM_STR);
        $stmt->execute();
    };

    try {
        $insertChannel($remark);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Incorrect string value') !== false) {
            ensureTableUtf8mb4('channels');
            try {
                $insertChannel($remark);
            } catch (PDOException $retryException) {
                if (strpos($retryException->getMessage(), 'Incorrect string value') === false) {
                    throw $retryException;
                }

                $sanitisedRemark = is_string($remark) ? @iconv('UTF-8', 'UTF-8//IGNORE', $remark) : '';
                if ($sanitisedRemark === false) {
                    $sanitisedRemark = '';
                }
                $insertChannel($sanitisedRemark);
            }
        } else {
            throw $e;
        }
    }
} elseif ($text == $textbotlang['Admin']['channel']['removeChannelBtn'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['channel']['removeChannel'], $list_channels_joins, 'HTML');
    step('removechannel', $from_id);
} elseif ($user['step'] == "removechannel") {
    sendmessage($from_id, $textbotlang['Admin']['channel']['removedChannel'], $channelkeyboard, 'HTML');
    step('home', $from_id);
    $stmt = $pdo->prepare("DELETE FROM channels WHERE link = :link");
    $stmt->bindParam(':link', $text, PDO::PARAM_STR);
    $stmt->execute();
} elseif ($datain == "addnewadmin" && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['manageadmin']['getId'], $backadmin, 'HTML');
    step('addadmin', $from_id);
} elseif ($user['step'] == "addadmin") {
    $adminId = trim($text);
    if ($adminId === '') {
        sendmessage($from_id, $textbotlang['Admin']['manageadmin']['getId'], $backadmin, 'HTML');
        return;
    }
    update("user", "Processing_value", $adminId, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['manageadmin']['setRule'], $adminrule, 'HTML');
    step('getrule', $from_id);
} elseif ($user['step'] == "getrule") {
    $rule = ['administrator', 'Seller', 'support'];
    if (!in_array($text, $rule)) {
        sendmessage($from_id, $textbotlang['Admin']['manageadmin']['invalidRule'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['manageadmin']['addAdminSet'], $keyboardadmin, 'HTML');
    sendmessage($user['Processing_value'], $textbotlang['Admin']['manageadmin']['adminAddedSendUser'], null, 'HTML');
    step('home', $from_id);
    $usernamepanel = "root";
    $randomString = bin2hex(random_bytes(5));
    $stmt = $pdo->prepare("INSERT INTO admin (id_admin, username, password, rule) VALUES (:id_admin, :username, :password, :rule)");
    $stmt->bindParam(':id_admin', $user['Processing_value'], PDO::PARAM_STR);
    $stmt->bindParam(':username', $usernamepanel, PDO::PARAM_STR);
    $stmt->bindParam(':password', $randomString, PDO::PARAM_STR);
    $stmt->bindParam(':rule', $text, PDO::PARAM_STR);
    $stmt->execute();
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['adminAdded'], $username, $from_id, $text, $user['Processing_value']);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherreport,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
} elseif (preg_match('/limitusertest_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    sendmessage($from_id, $textbotlang['Admin']['getlimitusertest']['getId'], $backadmin, 'HTML');
    update("user", "Processing_value", $iduser, "id", $from_id);
    step('get_number_limit', $from_id);
} elseif ($user['step'] == "get_number_limit") {
    sendmessage($from_id, $textbotlang['Admin']['getlimitusertest']['setLimit'], $keyboardadmin, 'HTML');
    $id_user_set = $text;
    step('home', $from_id);
    update("user", "limit_usertest", $text, "id", $user['Processing_value']);
} elseif ($text == $textbotlang['Admin']['getlimitusertest']['setLimitBtn'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['getlimitusertest']['limitAll'], $backadmin, 'HTML');
    step('limit_usertest_allusers', $from_id);
} elseif ($user['step'] == "limit_usertest_allusers") {
    sendmessage($from_id, $textbotlang['Admin']['getlimitusertest']['setLimitAll'], $keyboardadmin, 'HTML');
    step('home', $from_id);
    update("user", "limit_usertest", $text);
    update("setting", "limit_usertest_all", $text);
} elseif ($text == $textbotlang['keyboard']['channelSettings'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['channel']['description'], $channelkeyboard, 'HTML');
} elseif ($text == $textbotlang['Admin']['Status']['btn'] || $datain == "stat_all_bot") {
    $Balanceall = select("user", "SUM(Balance)", null, null, "select")['SUM(Balance)'];
    $statistics = select("user", "*", null, null, "count");
    $sumpanel = select("marzban_panel", "*", null, null, "count");
    $sql1 = "SELECT COUNT(id) AS count FROM user WHERE agent != 'f'";
    $stmt1 = $pdo->query($sql1);
    $agentsum = $stmt1->fetch(PDO::FETCH_ASSOC)['count'];
    $agentsumn = select("user", "COUNT(id)", "agent", "n", "select")['COUNT(id)'];
    $agentsumn2 = select("user", "COUNT(id)", "agent", "n2", "select")['COUNT(id)'];
    $sql1 = "SELECT COUNT(*) AS invoice_count FROM invoice WHERE (status = 'active' OR status = 'end_of_time' OR status = 'end_of_volume' OR status = 'sendedwarn' OR status = 'send_on_hold') AND name_product != '{$textbotlang['common']['labels']['testServiceName']}'";
    $stmt1 = $pdo->query($sql1);
    $invoiceactive = $stmt1->fetch(PDO::FETCH_ASSOC)['invoice_count'];
    $sqlall = "SELECT COUNT(*) AS invoice_count FROM invoice WHERE status != 'Unpaid' AND name_product != '{$textbotlang['common']['labels']['testServiceName']}'";
    $sqlall = $pdo->query($sqlall);
    $invoice = $sqlall->fetch(PDO::FETCH_ASSOC)['invoice_count'];
    $sql2 = "SELECT SUM(price_product) AS total_price FROM invoice WHERE (status = 'active' OR status = 'end_of_time' OR status = 'end_of_volume' OR status = 'sendedwarn' OR status = 'send_on_hold') AND name_product != '{$textbotlang['common']['labels']['testServiceName']}'";
    $stmt2 = $pdo->query($sql2);
    $invoicesum = $stmt2->fetch(PDO::FETCH_ASSOC)['total_price'];
    $sql33 = "SELECT SUM(price_product) AS total_price FROM invoice WHERE status!= 'Unpaid' AND name_product != '{$textbotlang['common']['labels']['testServiceName']}'";
    $sql33 = $pdo->query($sql33);
    $invoiceSumRow = $sql33->fetch(PDO::FETCH_ASSOC);
    $invoiceTotal = isset($invoiceSumRow['total_price']) ? (float) $invoiceSumRow['total_price'] : 0;
    $invoicesumall = number_format($invoiceTotal, 0);
    $sql3 = "SELECT SUM(price) AS total_extend FROM service_other WHERE type = 'extend_user'";
    $stmt3 = $pdo->query($sql3);
    $extendSumRow = $stmt3->fetch(PDO::FETCH_ASSOC);
    $extendsum = isset($extendSumRow['total_extend']) ? (float) $extendSumRow['total_extend'] : 0;
    $count_usertest = select("invoice", "*", "name_product", $textbotlang['common']['labels']['testServiceName'], "count");
    $timeacc = jdate('H:i:s', time());
    $stmt2 = $pdo->prepare("SELECT COUNT(DISTINCT id_user) as count FROM `invoice` WHERE Status != 'Unpaid'");
    $stmt2->execute();
    $statisticsorder = $stmt2->fetch(PDO::FETCH_ASSOC)['count'];
    $sqlsum = "SELECT SUM(price) AS sumpay , Payment_Method,COUNT(price) AS countpay FROM Payment_report WHERE payment_Status = 'paid' AND Payment_Method NOT IN ('add balance by admin','low balance by admin') GROUP BY  Payment_Method;";
    $stmt = $pdo->prepare($sqlsum);
    $stmt->execute();
    $statispay = $stmt->fetchAll();
    $date = date("Y-m-d");
    $timeacc = jdate('H:i:s', time());
    $start_time = date('d.m.Y', strtotime("-1 days")) . " 00:00:00";
    $end_time = date('d.m.Y', strtotime("-1 days")) . " 23:59:59";
    $start_time_timestamp = strtotime($start_time);
    $end_time_timestamp = strtotime($end_time);
    $sql = "SELECT SUM(price_product) FROM invoice WHERE (time_sell BETWEEN :requestedDate AND :requestedDateend) AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR Status = 'send_on_hold' OR Status = 'sendedwarn') AND name_product != '{$textbotlang['common']['labels']['testServiceName']}'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time_timestamp);
    $stmt->bindParam(':requestedDateend', $end_time_timestamp);
    $stmt->execute();
    $suminvoiceday = $stmt->fetch(PDO::FETCH_ASSOC)['SUM(price_product)'];
    $invoicesum = (float) ($invoicesum ?? 0);
    $extendsum = (float) ($extendsum ?? 0);
    $suminvoiceday = (float) ($suminvoiceday ?? 0);
    $statistics = (int) ($statistics ?? 0);
    $statisticsorder = (int) ($statisticsorder ?? 0);
    $paycount = "";
    $ratecustomer = $statistics > 0 ? round(($statisticsorder / $statistics) * 100, 2) : 0;
    $avgbuy_customer = $statisticsorder > 0 ? number_format($invoicesum / $statisticsorder) : '0';
    $monthe_buy = number_format($suminvoiceday * 30);
    $percent_of_extend = $invoicesum > 0 ? round(($extendsum / $invoicesum) * 100, 2) : 0;
    $percent_of_extend = $percent_of_extend > 100 ? 100 : $percent_of_extend;
    $extendsum = number_format($extendsum, 0);
    if (count($statispay) != 0) {
        foreach ($statispay as $tracepay) {
            $status_var = [
                'cart to cart' => $textbotlang['textbot']['cartToCart'],
                'aqayepardakht' => $textbotlang['textbot']['aqayePardakht'],
                'zarinpal' => $textbotlang['textbot']['zarinPal'],
                'plisio' => $textbotlang['textbot']['nowPayment'],
                'arze digital offline' => $textbotlang['textbot']['nowPaymentTron'],
                'Currency Rial 1' => $textbotlang['textbot']['iranPay2'],
                'Currency Rial 2' => $textbotlang['textbot']['iranPay3'],
                'Currency Rial 3' => $textbotlang['textbot']['iranPay1'],
                'paymentnotverify' => $textbotlang['textbot']['paymentNotVerify'],
                'Star Telegram' => $textbotlang['textbot']['starTelegram']

            ][$tracepay['Payment_Method']];
            $paycount .= sprintf($textbotlang['Admin']['report']['gatewayRow'], $status_var, $tracepay['countpay'], $tracepay['sumpay']);
        }
    }
    $statisticsall = sprintf($textbotlang['Admin']['stats']['overall'], $statistics, $statisticsorder, $count_usertest, $Balanceall, $invoice, $invoiceactive, $invoicesumall, $invoicesum, $extendsum, $ratecustomer, $avgbuy_customer, $monthe_buy, $percent_of_extend, $agentsum, $agentsumn, $agentsumn2, $sumpanel, $paycount);
    if ($datain == "stat_all_bot") {
        Editmessagetext($from_id, $message_id, $statisticsall, $keyboard_stat, 'HTML');
    } else {
        sendmessage($from_id, $statisticsall, $keyboard_stat, 'HTML');
    }
} elseif ($datain == "hoursago_stat") {
    $desired_date_time_start = time() - 3600;
    $sql = "SELECT COUNT(*) AS count,SUM(price_product) as sum FROM invoice WHERE (time_sell BETWEEN :requestedDate AND :requestedDateend) AND Status != 'Unpaid'  AND name_product != '{$textbotlang['common']['labels']['testServiceName']}'";
    $stmt = $pdo->prepare($sql);
    $time_current = time();
    $stmt->bindParam(':requestedDate', $desired_date_time_start);
    $stmt->bindParam(':requestedDateend', $time_current);
    $stmt->execute();
    $statorder = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_order = $statorder['count'];
    $sum_order = number_format($statorder['sum'], 0);
    $sql = "SELECT COUNT(*) AS count FROM invoice WHERE (time_sell BETWEEN :requestedDate AND :requestedDateend)  AND name_product = '{$textbotlang['common']['labels']['testServiceName']}'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $desired_date_time_start);
    $stmt->bindParam(':requestedDateend', $time_current);
    $stmt->execute();
    $count_test = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  time  >= NOW() - INTERVAL 1 HOUR AND type = 'extend_user' AND status != 'unpaid'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $extend_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extend = $extend_stat['count'];
    $sum_extend = number_format($extend_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  time  >= NOW() - INTERVAL 1 HOUR AND type = 'extra_user'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $extra_volume_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extra_volume = $extra_volume_stat['count'];
    $sum_extra_volume = number_format($extra_volume_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  time  >= NOW() - INTERVAL 1 HOUR AND type = 'extra_time_user'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $extra_time_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extra_time = $extra_time_stat['count'];
    $sum_extrat_time = number_format($extra_time_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  time  >= NOW() - INTERVAL 1 HOUR AND type = 'change_location'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $change_location_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_change_location = $change_location_stat['count'];
    $sum_change_location = number_format($change_location_stat['sum'], 0);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE  (register BETWEEN :requestedDate AND :requestedDateend)  AND register != 'none'");
    $stmt->bindParam(':requestedDate', $desired_date_time_start);
    $stmt->bindParam(':requestedDateend', $time_current);
    $stmt->execute();
    $countextendday = (int) $stmt->fetchColumn();
    $statisticsall = sprintf($textbotlang['Admin']['stats']['lastHour'], $count_order, $sum_order, $count_extend, $sum_extend, $count_extra_volume, $sum_extra_volume, $count_extra_time, $sum_extrat_time, $count_change_location, $sum_change_location, $count_test, $countextendday);
    Editmessagetext($from_id, $message_id, $statisticsall, $keyboard_stat, 'HTML');
} elseif ($datain == "yesterday_stat") {
    $start_time = date('Y/m/d', strtotime("-1 days")) . " 00:00:00";
    $end_time = date('Y/m/d', strtotime("-1 days")) . " 23:59:59";
    $start_time_timestamp = strtotime($start_time);
    $end_time_timestamp = strtotime($end_time);
    $sql = "SELECT COUNT(*) AS count,SUM(price_product) as sum FROM invoice WHERE (time_sell BETWEEN :requestedDate AND :requestedDateend) AND Status != 'Unpaid'  AND name_product != '{$textbotlang['common']['labels']['testServiceName']}'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time_timestamp);
    $stmt->bindParam(':requestedDateend', $end_time_timestamp);
    $stmt->execute();
    $statorder = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_order = $statorder['count'];
    $sum_order = number_format($statorder['sum'], 0);
    $sql = "SELECT COUNT(*) AS count FROM invoice WHERE (time_sell BETWEEN :requestedDate AND :requestedDateend)  AND name_product = '{$textbotlang['common']['labels']['testServiceName']}'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time_timestamp);
    $stmt->bindParam(':requestedDateend', $end_time_timestamp);
    $stmt->execute();
    $count_test = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'extend_user' AND status != 'unpaid'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $extend_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extend = $extend_stat['count'];
    $sum_extend = number_format($extend_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'extra_user'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $extra_volume_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extra_volume = $extra_volume_stat['count'];
    $sum_extra_volume = number_format($extra_volume_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'extra_time_user'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $extra_time_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extra_time = $extra_time_stat['count'];
    $sum_extrat_time = number_format($extra_time_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'change_location'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $change_location_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_change_location = $change_location_stat['count'];
    $sum_change_location = number_format($change_location_stat['sum'], 0);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE  (register BETWEEN :requestedDate AND :requestedDateend)  AND register != 'none'");
    $stmt->bindParam(':requestedDate', $start_time_timestamp);
    $stmt->bindParam(':requestedDateend', $end_time_timestamp);
    $stmt->execute();
    $countuser_new = (int) $stmt->fetchColumn();
    $statisticsall = sprintf($textbotlang['Admin']['stats']['yesterday'], $start_time, $end_time, $count_order, $sum_order, $count_extend, $sum_extend, $count_extra_volume, $sum_extra_volume, $count_extra_time, $sum_extrat_time, $count_change_location, $sum_change_location, $count_test, $countuser_new);
    Editmessagetext($from_id, $message_id, $statisticsall, $keyboard_stat, 'HTML');
} elseif ($datain == "today_stat") {
    $start_time = date('Y/m/d') . " 00:00:00";
    $end_time = date('Y/m/d H:i:s');
    $start_time_timestamp = strtotime($start_time);
    $end_time_timestamp = strtotime($end_time);
    $sql = "SELECT COUNT(*) AS count,SUM(price_product) as sum FROM invoice WHERE (time_sell BETWEEN :requestedDate AND :requestedDateend) AND Status != 'Unpaid' AND name_product != '{$textbotlang['common']['labels']['testServiceName']}'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time_timestamp);
    $stmt->bindParam(':requestedDateend', $end_time_timestamp);
    $stmt->execute();
    $statorder = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_order = $statorder['count'];
    $sum_order = number_format($statorder['sum'], 0);
    $sql = "SELECT COUNT(*) AS count FROM invoice WHERE (time_sell BETWEEN :requestedDate AND :requestedDateend)  AND name_product = '{$textbotlang['common']['labels']['testServiceName']}'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time_timestamp);
    $stmt->bindParam(':requestedDateend', $end_time_timestamp);
    $stmt->execute();
    $count_test = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'extend_user' AND status != 'unpaid'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $extend_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extend = $extend_stat['count'];
    $sum_extend = number_format($extend_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'extra_user'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $extra_volume_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extra_volume = $extra_volume_stat['count'];
    $sum_extra_volume = number_format($extra_volume_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'extra_time_user'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $extra_time_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extra_time = $extra_time_stat['count'];
    $sum_extrat_time = number_format($extra_time_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'change_location'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $change_location_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_change_location = $change_location_stat['count'];
    $sum_change_location = number_format($change_location_stat['sum'], 0);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE  (register BETWEEN :requestedDate AND :requestedDateend)  AND register != 'none'");
    $stmt->bindParam(':requestedDate', $start_time_timestamp);
    $stmt->bindParam(':requestedDateend', $end_time_timestamp);
    $stmt->execute();
    $countuser_new = (int) $stmt->fetchColumn();
    $statisticsall = sprintf($textbotlang['Admin']['stats']['today'], $start_time, $end_time, $count_order, $sum_order, $count_extend, $sum_extend, $count_extra_volume, $sum_extra_volume, $count_extra_time, $sum_extrat_time, $count_change_location, $sum_change_location, $count_test, $countuser_new);
    Editmessagetext($from_id, $message_id, $statisticsall, $keyboard_stat, 'HTML');
} elseif ($datain == "month_old_stat") {
    $firstDayLastMonth = new DateTime('first day of last month');
    $lastDayLastMonth = new DateTime('last day of last month');
    $start_time = $firstDayLastMonth->format('Y/m/d');
    $end_time = $lastDayLastMonth->format('Y/m/d');
    $start_time_timestamp = strtotime($start_time);
    $end_time_timestamp = strtotime($end_time);
    $sql = "SELECT COUNT(*) AS count,SUM(price_product) as sum FROM invoice WHERE (time_sell BETWEEN :requestedDate AND :requestedDateend) AND Status != 'Unpaid'  AND name_product != '{$textbotlang['common']['labels']['testServiceName']}'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time_timestamp);
    $stmt->bindParam(':requestedDateend', $end_time_timestamp);
    $stmt->execute();
    $statorder = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_order = $statorder['count'];
    $sum_order = number_format($statorder['sum'], 0);
    $sql = "SELECT COUNT(*) AS count FROM invoice WHERE (time_sell BETWEEN :requestedDate AND :requestedDateend)  AND name_product = '{$textbotlang['common']['labels']['testServiceName']}'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time_timestamp);
    $stmt->bindParam(':requestedDateend', $end_time_timestamp);
    $stmt->execute();
    $count_test = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'extend_user' AND status != 'unpaid'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $extend_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extend = $extend_stat['count'];
    $sum_extend = number_format($extend_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'extra_user'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $extra_volume_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extra_volume = $extra_volume_stat['count'];
    $sum_extra_volume = number_format($extra_volume_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'extra_time_user'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $extra_time_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extra_time = $extra_time_stat['count'];
    $sum_extrat_time = number_format($extra_time_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'change_location'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $change_location_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_change_location = $change_location_stat['count'];
    $sum_change_location = number_format($change_location_stat['sum'], 0);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE  (register BETWEEN :requestedDate AND :requestedDateend)  AND register != 'none'");
    $stmt->bindParam(':requestedDate', $start_time_timestamp);
    $stmt->bindParam(':requestedDateend', $end_time_timestamp);
    $stmt->execute();
    $countuser_new = (int) $stmt->fetchColumn();
    $statisticsall = sprintf($textbotlang['Admin']['stats']['lastMonth'], $start_time, $end_time, $count_order, $sum_order, $count_extend, $sum_extend, $count_extra_volume, $sum_extra_volume, $count_extra_time, $sum_extrat_time, $count_change_location, $sum_change_location, $count_test, $countuser_new);
    Editmessagetext($from_id, $message_id, $statisticsall, $keyboard_stat, 'HTML');
} elseif ($datain == "month_current_stat") {
    $firstDayLastMonth = new DateTime('first day of this month');
    $lastDayLastMonth = new DateTime('last day of this month');
    $start_time = $firstDayLastMonth->format('Y/m/d');
    $end_time = $lastDayLastMonth->format('Y/m/d');
    $start_time_timestamp = strtotime($start_time);
    $end_time_timestamp = strtotime($end_time);
    $sql = "SELECT COUNT(*) AS count,SUM(price_product) as sum FROM invoice WHERE (time_sell BETWEEN :requestedDate AND :requestedDateend) AND Status != 'Unpaid'  AND name_product != '{$textbotlang['common']['labels']['testServiceName']}'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time_timestamp);
    $stmt->bindParam(':requestedDateend', $end_time_timestamp);
    $stmt->execute();
    $statorder = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_order = $statorder['count'];
    $sum_order = number_format($statorder['sum'], 0);
    $sql = "SELECT COUNT(*) AS count FROM invoice WHERE (time_sell BETWEEN :requestedDate AND :requestedDateend)  AND name_product = '{$textbotlang['common']['labels']['testServiceName']}'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time_timestamp);
    $stmt->bindParam(':requestedDateend', $end_time_timestamp);
    $stmt->execute();
    $count_test = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'extend_user' AND status != 'unpaid'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $extend_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extend = $extend_stat['count'];
    $sum_extend = number_format($extend_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'extra_user'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $extra_volume_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extra_volume = $extra_volume_stat['count'];
    $sum_extra_volume = number_format($extra_volume_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'extra_time_user'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $extra_time_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extra_time = $extra_time_stat['count'];
    $sum_extrat_time = number_format($extra_time_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'change_location'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $change_location_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_change_location = $change_location_stat['count'];
    $sum_change_location = number_format($change_location_stat['sum'], 0);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE  (register BETWEEN :requestedDate AND :requestedDateend)  AND register != 'none'");
    $stmt->bindParam(':requestedDate', $start_time_timestamp);
    $stmt->bindParam(':requestedDateend', $end_time_timestamp);
    $stmt->execute();
    $countuser_new = (int) $stmt->fetchColumn();
    $statisticsall = sprintf($textbotlang['Admin']['stats']['thisMonth'], $start_time, $end_time, $count_order, $sum_order, $count_extend, $sum_extend, $count_extra_volume, $sum_extra_volume, $count_extra_time, $sum_extrat_time, $count_change_location, $sum_change_location, $count_test, $countuser_new);
    Editmessagetext($from_id, $message_id, $statisticsall, $keyboard_stat, 'HTML');
} elseif ($datain == "view_stat_time") {
    sendmessage($from_id, sprintf($textbotlang['Admin']['getStats'], date('Y/m/d')), $backadmin, 'HTML');
    step("get_time_start", $from_id);
} elseif ($user['step'] == "get_time_start") {
    if (!isValidDate($text)) {
        sendmessage($from_id, $textbotlang['Admin']['stats']['invalidDate'], null, 'HTML');
        return;
    }
    savedata("clear", "start_time", $text);
    sendmessage($from_id, $textbotlang['Admin']['stats']['askDate'], $backadmin, 'HTML');
    step("get_time_end", $from_id);
} elseif ($user['step'] == "get_time_end") {
    if (!isValidDate($text)) {
        sendmessage($from_id, $textbotlang['Admin']['stats']['invalidDate'], null, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    $start_time = $userdata['start_time'] . "00:00:00";
    $end_time = $text . "23:59:00";
    $start_time_timestamp = strtotime($start_time);
    $end_time_timestamp = strtotime($end_time);
    $sql = "SELECT COUNT(*) AS count,SUM(price_product) as sum FROM invoice WHERE (time_sell BETWEEN :requestedDate AND :requestedDateend)  AND  Status != 'Unpaid' AND name_product != '{$textbotlang['common']['labels']['testServiceName']}'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time_timestamp);
    $stmt->bindParam(':requestedDateend', $end_time_timestamp);
    $stmt->execute();
    $statorder = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_order = $statorder['count'];
    $sum_order = number_format($statorder['sum'], 0);
    $sql = "SELECT COUNT(*) AS count FROM invoice WHERE (time_sell BETWEEN :requestedDate AND :requestedDateend)  AND name_product = '{$textbotlang['common']['labels']['testServiceName']}'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time_timestamp);
    $stmt->bindParam(':requestedDateend', $end_time_timestamp);
    $stmt->execute();
    $count_test = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'extend_user' AND status != 'unpaid'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $extend_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extend = $extend_stat['count'];
    $sum_extend = number_format($extend_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'extra_user'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $extra_volume_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extra_volume = $extra_volume_stat['count'];
    $sum_extra_volume = number_format($extra_volume_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE  (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'extra_time_user'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $extra_time_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_extra_time = $extra_time_stat['count'];
    $sum_extrat_time = number_format($extra_time_stat['sum'], 0);
    $sql = "SELECT COUNT(*) AS count,SUM(price) as sum FROM service_other WHERE (time BETWEEN :requestedDate AND :requestedDateend) AND type = 'change_location'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':requestedDate', $start_time);
    $stmt->bindParam(':requestedDateend', $end_time);
    $stmt->execute();
    $change_location_stat = $stmt->fetch(PDO::FETCH_ASSOC);
    $count_change_location = $change_location_stat['count'];
    $sum_change_location = number_format($change_location_stat['sum'], 0);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE  (register BETWEEN :requestedDate AND :requestedDateend)  AND register != 'none'");
    $stmt->bindParam(':requestedDate', $start_time_timestamp);
    $stmt->bindParam(':requestedDateend', $end_time_timestamp);
    $stmt->execute();
    $countuser_new = (int) $stmt->fetchColumn();
    $statisticsall = sprintf($textbotlang['Admin']['stats']['selectedRange'], $start_time, $end_time, $count_order, $sum_order, $count_extend, $sum_extend, $count_extra_volume, $sum_extra_volume, $count_extra_time, $sum_extrat_time, $count_change_location, $sum_change_location, $count_test, $countuser_new);
    step('home', $from_id);
    sendmessage($from_id, $statisticsall, $keyboardadmin, 'HTML');
} elseif ($datain == "settingaffiliatesf") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $affiliates, 'HTML');
} elseif ($text == $textbotlang['Admin']['btnKeyboard']['addPanel'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['Inbound']['getPanelType'], $keyboardtypepanel, 'HTML');
} elseif (preg_match('/typepanel#(.*)/', $datain, $dataget)) {
    $typepanel = $dataget[1];
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['addPanelName'], $backadmin, 'HTML');
    step("add_name_panel", $from_id);
    deletemessage($from_id, $message_id);
    savedata("clear", "type", $typepanel);
} elseif ($user['step'] == "add_name_panel") {
    if (rowExists("marzban_panel", "name_panel", $text)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['repeatPanel'], $backadmin, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    savedata("save", "namepanel", $text);
    if ($userdata['type'] == "Manualsale") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['getLimitedPanel'], $backadmin, 'HTML');
        step('getlimitedpanel', $from_id);
        savedata("save", "url_panel", "null");
        savedata("save", "username", "null");
        savedata("save", "password", "null");
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['addPanelUrl'], $backadmin, 'HTML');
    step('add_link_panel', $from_id);
} elseif ($user['step'] == "add_link_panel") {
    $text = normalizePanelUrl($text);
    if (!filter_var($text, FILTER_VALIDATE_URL)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['invalidDomain'], $backadmin, 'HTML');
        return;
    }
    savedata("save", "url_panel", $text);
    $userdata = json_decode($user['Processing_value'], true);
    if ($userdata['type'] == "hiddify") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['getLimitedPanel'], $backadmin, 'HTML');
        step('getlimitedpanel', $from_id);
        savedata("save", "username", "null");
        savedata("save", "password", "null");
        return;
    } elseif ($userdata['type'] == "s_ui" || $userdata['type'] == "WGDashboard" || $userdata['type'] == "x-ui_single" || $userdata['type'] == "mirza_agent" || $userdata['type'] == "rebecca") {
        sendmessage($from_id, $textbotlang['Admin']['agentbot']['askToken'], $backadmin, 'HTML');
        step('add_password_panel', $from_id);
        savedata("save", "username", "null");
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['usernameSet'], $backadmin, 'HTML');
    step('add_username_panel', $from_id);
} elseif ($user['step'] == "add_username_panel") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['getPassword'], $backadmin, 'HTML');
    step('add_password_panel', $from_id);
    savedata("save", "username", $text);
} elseif ($user['step'] == "add_password_panel") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['getLimitedPanel'], $backadmin, 'HTML');
    step('getlimitedpanel', $from_id);
    savedata("save", "password", $text);
} elseif ($user['step'] == "getlimitedpanel") {
    savedata("save", "limitpanel", $text);
    $userdata = json_decode($user['Processing_value'], true);
    $randomString = bin2hex(random_bytes(2));
    if ($userdata['type'] == "x-ui_single" || $userdata['type'] == "alireza") {
        $marzbanprotocol = $randomString;
        $protocols = "vmess";
        $settingpanel = json_encode(array(
            'network' => 'ws',
            'security' => 'none',
            'externalProxy' => array(),
            'wsSettings' => array(
                'acceptProxyProtocol' => false,
                'path' => '/',
                'host' => '',
                'headers' => array()

            ),
        ));
    }
    $sublink = "onsublink";
    $configstatus = "offconfig";
    $methodusernameadd = 'numericIdRandom';
    $status = "active";
    $ONTestAccount = "ONTestAccount";
    $extendtextadd = "resetVolumeTime";
    $namecustoms = "none";
    $type = "marzban";
    $conecton = "offconecton";
    $inboundid = 1;
    $agent = "all";
    $time = "1";
    $valume = "100";
    $changeloc = "offchangeloc";
    $value = json_encode(array(
        'f' => "4000",
        'n' => "4000",
        'n2' => "4000"
    ));
    $valuemain = json_encode(array(
        'f' => "1",
        'n' => "1",
        'n2' => "1"
    ));
    $valuemax = json_encode(array(
        'f' => "1000",
        'n' => "1000",
        'n2' => "1000"
    ));
    $VALUE = json_encode(array(
        'f' => '0',
        'n' => '0',
        'n2' => '0'
    ));
    $version_panel = $userdata['type'] == "pasarguard" ? "1" : "0";
    $userdata['type'] = $userdata['type'] == "pasarguard" ? "marzban" : $userdata['type'];
    $stmt = $pdo->prepare("INSERT INTO marzban_panel (code_panel,name_panel,sublink,config,MethodUsername,TestAccount,status,limit_panel,namecustom,Methodextend,type,conecton,inboundid,agent,inbound_deactive,inboundstatus,url_panel,username_panel,password_panel,time_usertest,val_usertest,linksubx,priceextravolume,priceextratime,pricecustomvolume,pricecustomtime,mainvolume,maxvolume,maintime,maxtime,status_extend,subvip,changeloc,customvolume,on_hold_test,version_panel) VALUES (:code_panel,:name_panel,:sublink,:config,:MethodUsername,:TestAccount,:status,:limit_panel,:namecustom,:Methodextend,:type,:conecton,:inboundid,:agent,:inbound_deactive,'offinbounddisable',:url_panel,:username_panel,:password_panel,:val_usertest,:time_usertest,:linksubx,:priceextravolume,:priceextratime,:pricecustomvolume,:pricecustomtime,:mainvolume,:maxvolume,:maintime,:maxtime,'on_extend','offsubvip',:changeloc,:customvolume,'1',:version_panel)");
    $stmt->bindParam(':code_panel', $randomString);
    $stmt->bindParam(':name_panel', $userdata['namepanel'], PDO::PARAM_STR);
    $stmt->bindParam(':sublink', $sublink);
    $stmt->bindParam(':config', $configstatus);
    $stmt->bindParam(':MethodUsername', $methodusernameadd);
    $stmt->bindParam(':TestAccount', $ONTestAccount);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':limit_panel', $text);
    $stmt->bindParam(':namecustom', $namecustoms);
    $stmt->bindParam(':Methodextend', $extendtextadd);
    $stmt->bindParam(':type', $userdata['type'], PDO::PARAM_STR);
    $stmt->bindParam(':conecton', $conecton);
    $stmt->bindParam(':inboundid', $inboundid);
    $stmt->bindParam(':agent', $agent);
    $stmt->bindParam(':inbound_deactive', $inboundid);
    $stmt->bindParam(':url_panel', $userdata['url_panel']);
    $stmt->bindParam(':linksubx', $userdata['url_panel']);
    $stmt->bindParam(':username_panel', $userdata['username']);
    $stmt->bindParam(':password_panel', $userdata['password']);
    $stmt->bindParam(':val_usertest', $valume);
    $stmt->bindParam(':time_usertest', $time);
    $stmt->bindParam(':priceextravolume', $value);
    $stmt->bindParam(':priceextratime', $value);
    $stmt->bindParam(':pricecustomtime', $value);
    $stmt->bindParam(':pricecustomvolume', $value);
    $stmt->bindParam(':mainvolume', $valuemain);
    $stmt->bindParam(':maxvolume', $valuemax);
    $stmt->bindParam(':maintime', $valuemain);
    $stmt->bindParam(':maxtime', $valuemax);
    $stmt->bindParam(':changeloc', $changeloc);
    $stmt->bindParam(':customvolume', $VALUE);
    $stmt->bindParam(':version_panel', $version_panel);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['addedPanel'], $keyboardadmin, 'HTML');
    sendmessage($from_id, "🥳", $keyboardadmin, 'HTML');
    step("home", $from_id);
    if ($userdata['type'] == "x-ui_single" or $userdata['type'] == "alireza_single") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['noteSetInboundAndDomain'], null, 'HTML');
    } elseif ($userdata['type'] == "marzban") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['noteSetProtocolInbound'], null, 'HTML');
    } elseif ($userdata['type'] == "WGDashboard") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['noteSetInboundId'], null, 'HTML');
    } elseif ($userdata['type'] == "ibsng") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['noteSetGroupNameIbsng'], null, 'HTML');
    } elseif ($userdata['type'] == "mikrotik") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['noteMikrotikAccounting'], null, 'HTML');
    } elseif ($userdata['type'] == "hiddify") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['noteSetAdminUuid'], null, 'HTML');
    } elseif ($userdata['type'] == "s_ui") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['noteSendConfigUsername'], null, 'HTML');
    }
}
//_____________________[ message ]____________________________//
elseif ($datain == "systemsms") {
    if (is_file('cronbot/users.json')) {
        $userslist = json_decode(file_get_contents('cronbot/users.json'), true);
        if (is_array($userslist) and count($userslist) != 0) {
            sendmessage($from_id, $textbotlang['Admin']['messageBulk']['busy'], $keyboardadmin, 'HTML');
            return;
        }
    }
    $listbtn = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['broadcastSend'], 'callback_data' => 'typeservice-sendmessage'],
            ],
            [
                ['text' => $textbotlang['keyboard']['broadcastForward'], 'callback_data' => 'typeservice-forwardmessage'],
            ],
            [
                ['text' => $textbotlang['keyboard']['inactiveDays'], 'callback_data' => 'typeservice-xdaynotmessage'],
            ],
            [
                ['text' => $textbotlang['keyboard']['cancelPinnedMessages'], 'callback_data' => 'typeservice-unpinmessage'],
            ],
            [
                ['text' => $textbotlang['keyboard']['backToMain'], 'callback_data' => 'backlistuser'],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['selectoption'], $listbtn);
} elseif (preg_match('/^typeservice-(\w+)/', $datain, $dataget)) {
    $type = $dataget[1];
    savedata("clear", "typeservice", $type);
    if ($type == "unpinmessage") {
        deletemessage($from_id, $message_id);
        $typesend = [
            "unpinmessage" => $textbotlang['Admin']['messageBulk']['btnCancelPin']
        ][$type];
        $textconfirm = sprintf($textbotlang['Admin']['messageBulk']['confirmSummary'], $typesend);
        $startaction = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['keyboard']['confirmAndStart'], 'callback_data' => 'startaction'],
                ],
            ]
        ]);
        sendmessage($from_id, $textconfirm, $startaction, 'HTML');
        sendmessage($from_id, $textbotlang['Admin']['messageBulk']['confirmStart'], $keyboardadmin, 'HTML');
        step("home", $from_id);
        return;
    }
    $listbtn = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['allUsers'], 'callback_data' => 'typeusermessage-all'],
            ],
            [
                ['text' => $textbotlang['keyboard']['customersBought'], 'callback_data' => 'typeusermessage-customer'],
            ],
            [
                ['text' => $textbotlang['keyboard']['usersNotBought'], 'callback_data' => 'typeusermessage-nonecustomer'],
            ],
            [
                ['text' => $textbotlang['keyboard']['backToPrev'], 'callback_data' => 'systemsms'],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['gift']['askUserGroup'], $listbtn);
} elseif (preg_match('/^typeusermessage-(\w+)/', $datain, $dataget)) {
    $userdata = json_decode($user['Processing_value'], true);
    if (!isset($userdata['typeservice'])) {
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['Admin']['messageBulk']['errorRestart'], $keyboardadmin, 'HTML');
        return;
    }
    savedata("save", "typeusermessage", $dataget[1]);
    $listbtn = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['allUsers'], 'callback_data' => 'typeagent-all'],
            ],
            [
                ['text' => $textbotlang['keyboard']['usersGroupF'], 'callback_data' => 'typeagent-f'],
            ],
            [
                ['text' => $textbotlang['keyboard']['usersGroupN'], 'callback_data' => 'typeagent-n'],
            ],
            [
                ['text' => $textbotlang['keyboard']['usersGroupN2'], 'callback_data' => 'typeagent-n2'],
            ],
            [
                ['text' => $textbotlang['keyboard']['backToPrev'], 'callback_data' => 'typeservice-' . $userdata['typeservice']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['gift']['askUserCategory'], $listbtn);
} elseif (preg_match('/^typeagent-(\w+)/', $datain, $dataget)) {
    $type = $dataget[1];
    $userdata = json_decode($user['Processing_value'], true);
    if (!isset($userdata['typeservice'])) {
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['Admin']['messageBulk']['errorRestart'], $keyboardadmin, 'HTML');
        return;
    }
    savedata("save", "agent", $type);
    if ($userdata['typeusermessage'] == "customer") {
        $stmt = $pdo->prepare("SELECT * FROM marzban_panel WHERE agent = :agent OR agent = 'all'");
        $stmt->bindParam(':agent', $type);
        $stmt->execute();
        $list_panel = ['inline_keyboard' => []];
        $list_panel['inline_keyboard'][] = [['text' => $textbotlang['keyboard']['allPanelsList'], 'callback_data' => 'locationmessage_all']];
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $list_panel['inline_keyboard'][] = [
                ['text' => $result['name_panel'], 'callback_data' => "locationmessage_{$result['code_panel']}"]
            ];
        }
        $list_panel['inline_keyboard'][] = [['text' => $textbotlang['keyboard']['backToPrev'], 'callback_data' => 'typeusermessage-' . $userdata['typeusermessage']],];
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['messageBulk']['askPanelUsers'], json_encode($list_panel));
        return;
    }
    if ($userdata['typeservice'] == "xdaynotmessage" or $userdata['typeservice'] == "sendmessage" or $userdata['typeservice'] == "forwardmessage") {
        $listbtn = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['keyboard']['yes'], 'callback_data' => 'typepinmessage-yes'],
                    ['text' => $textbotlang['keyboard']['no'], 'callback_data' => 'typepinmessage-no'],
                ],
                [
                    ['text' => $textbotlang['keyboard']['backToPrev'], 'callback_data' => 'typeusermessage-' . $userdata['typeusermessage']],
                ],
            ]
        ]);
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['messageBulk']['askPin'], $listbtn);
        return;
    }
    step("gettextSystemMessage", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['messageBulk']['askText'], $backadmin, 'HTML');
} elseif (preg_match('/^locationmessage_(\w+)/', $datain, $dataget)) {
    $typeoanel = $dataget[1];
    $userdata = json_decode($user['Processing_value'], true);
    if (!isset($userdata['typeservice'])) {
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['Admin']['messageBulk']['errorRestart'], $keyboardadmin, 'HTML');
        return;
    }
    savedata("save", "selectpanel", $typeoanel);
    if ($userdata['typeservice'] == "xdaynotmessage" or $userdata['typeservice'] == "sendmessage" or $userdata['typeservice'] == "forwardmessage") {
        $listbtn = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['keyboard']['yes'], 'callback_data' => 'typepinmessage-yes'],
                    ['text' => $textbotlang['keyboard']['no'], 'callback_data' => 'typepinmessage-no'],
                ],
                [
                    ['text' => $textbotlang['keyboard']['backToPrev'], 'callback_data' => 'typeagent-' . $userdata['agent']],
                ],
            ]
        ]);
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['messageBulk']['askPin'], $listbtn);
        return;
    }
    step("gettextSystemMessage", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['messageBulk']['askText'], $backadmin, 'HTML');
} elseif (preg_match('/^typepinmessage-(\w+)/', $datain, $dataget)) {
    $type = $dataget[1];
    $userdata = json_decode($user['Processing_value'], true);
    if (!isset($userdata['typeservice'])) {
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['Admin']['messageBulk']['errorRestart'], $keyboardadmin, 'HTML');
        return;
    }
    savedata("save", "typepinmessage", $type);
    $listbtn = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['startBtn'], 'callback_data' => 'btntypemessage-start'],
                ['text' => $textbotlang['keyboard']['educationBtn'], 'callback_data' => 'btntypemessage-helpbtn'],
            ],
            [
                ['text' => $textbotlang['keyboard']['purchaseBtn'], 'callback_data' => 'btntypemessage-buy'],
                ['text' => $textbotlang['keyboard']['testAccountBtn'], 'callback_data' => 'btntypemessage-usertestbtn'],
            ],
            [
                ['text' => $textbotlang['keyboard']['affiliatesBtn'], 'callback_data' => 'btntypemessage-affiliatesbtn'],
                ['text' => $textbotlang['keyboard']['chargeWallet'], 'callback_data' => 'btntypemessage-addbalance'],
            ],
            [
                ['text' => $textbotlang['keyboard']['sendWithoutButton'], 'callback_data' => 'btntypemessage-none'],
            ],
            [
                ['text' => $textbotlang['keyboard']['backToPrev'], 'callback_data' => 'typeagent-' . $userdata['agent']],
            ],
        ]
    ]);
    if ($userdata['typeservice'] == "forwardmessage") {
        step("gettextSystemMessage", $from_id);
        sendmessage($from_id, $textbotlang['Admin']['messageBulk']['askText'], $backadmin, 'HTML');
        return;
    }
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['messageBulk']['askButton'], $listbtn);
} elseif (preg_match('/^btntypemessage-(\w+)/', $datain, $dataget)) {
    deletemessage($from_id, $message_id);
    $type = $dataget[1];
    savedata("save", "btntypemessage", $type);
    $userdata = json_decode($user['Processing_value'], true);
    if (!isset($userdata['typeservice'])) {
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['Admin']['messageBulk']['errorRestart'], $keyboardadmin, 'HTML');
        return;
    }
    if ($userdata['typeservice'] == "xdaynotmessage") {
        step("gettextday", $from_id);
        sendmessage($from_id, $textbotlang['Admin']['messageBulk']['askInactiveDays'], $backadmin, 'HTML');
        return;
    }
    step("gettextSystemMessage", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['messageBulk']['askText'], $backadmin, 'HTML');
} elseif ($user['step'] == "gettextday") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    if (!isset($userdata['typeservice'])) {
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['Admin']['messageBulk']['errorRestart'], $keyboardadmin, 'HTML');
        return;
    }
    savedata("save", "daynoyuse", $text);
    step("gettextSystemMessage", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['messageBulk']['askText'], $backadmin, 'HTML');
} elseif ($user['step'] == "gettextSystemMessage") {
    $userdata = json_decode($user['Processing_value'], true);
    if (!isset($userdata['typeservice'])) {
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['Admin']['messageBulk']['errorRestart'], $keyboardadmin, 'HTML');
        return;
    }
    if ($userdata['typeservice'] == "forwardmessage") {
        savedata("save", "message", $message_id);
    } elseif ($userdata['typeservice'] == "xdaynotmessage") {
        if ($text) {
            savedata("save", "message", $text);
        } else {
            sendmessage($from_id, $textbotlang['Admin']['messageBulk']['textOnlyInactive'], $backadmin, 'HTML');
            return;
        }
    } elseif ($userdata['typeservice'] == "sendmessage") {
        if ($text) {
            savedata("save", "message", $text);
        } else {
            sendmessage($from_id, $textbotlang['Admin']['messageBulk']['textOnlyBroadcast'], $backadmin, 'HTML');
            return;
        }
    }
    $typesend = [
        "xdaynotmessage" => $textbotlang['Admin']['messageBulk']['targetInactiveUsers'],
        "sendmessage" => $textbotlang['keyboard']['broadcastSend'],
        "forwardmessage" => $textbotlang['keyboard']['broadcastForward'],
        "unpinmessage" => $textbotlang['Admin']['messageBulk']['btnCancelPin']
    ][$userdata['typeservice'] ?? ''] ?? ($userdata['typeservice'] ?? '');
    $typeservice = [
        "all" => $textbotlang['Admin']['messageBulk']['targetAllUsers'],
        "customer" => $textbotlang['Admin']['messageBulk']['targetCustomers'],
        "nonecustomer" => $textbotlang['Admin']['messageBulk']['targetNoPurchase'],
    ][$userdata['typeusermessage'] ?? ''] ?? ($userdata['typeusermessage'] ?? '');
    if (($userdata['typeservice'] ?? '') == "xdaynotmessage") {
        $textday = sprintf($textbotlang['Admin']['messageBulk']['inactiveDaysLabel'], $userdata['daynoyuse'] ?? '');
    } else {
        $textday = "";
    }
    $textconfirm = sprintf($textbotlang['Admin']['messageBulk']['confirmSummary2'], $typesend, $typeservice, $userdata['agent'] ?? '', $textday);
    $startaction = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['confirmAndStart'], 'callback_data' => 'startaction'],
            ],
        ]
    ]);
    sendmessage($from_id, $textconfirm, $startaction, 'HTML');
    sendmessage($from_id, $textbotlang['Admin']['messageBulk']['confirmStart'], $keyboardadmin, 'HTML');
    step("home", $from_id);
} elseif ($datain == "startaction") {
    $userdata = json_decode($user['Processing_value'], true);
    if (!isset($userdata['typeservice'])) {
        sendmessage($from_id, $textbotlang['Admin']['messageBulk']['errorRestart'], $keyboardadmin, 'HTML');
        return;
    }
    $agent = $userdata['agent'];
    $typeservice = $userdata['typeservice'];
    $typeusermessage = $userdata['typeusermessage'];
    $text = $userdata['message'];
    $cancelmessage = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['cancelOperation'], 'callback_data' => 'cancel_sendmessage'],
            ],
        ]
    ]);

    if ($typeservice == "unpinmessage") {
        $userlist = json_encode(select("user", "id", null, null, "fetchAll"));
        $message_id = Editmessagetext($from_id, $message_id, $textbotlang['Admin']['messageBulk']['started'], $cancelmessage);
        $dataunpin = json_encode(array(
            "id_admin" => $from_id,
            'type' => "unpinmessage",
            "id_message" => $message_id['result']['message_id']
        ));
        file_put_contents("cronbot/users.json", $userlist);
        file_put_contents('cronbot/info', $dataunpin);
    } elseif ($typeservice == "sendmessage") {
        if ($agent == "all") {
            if ($typeusermessage == "all") {
                $userslist = json_encode(select("user", "id", "User_Status", "Active", "fetchAll"));
            } elseif ($typeusermessage == "customer") {
                if (($userdata['selectpanel'] ?? 'all') == "all") {
                    $stmt = $pdo->prepare("SELECT u.id FROM user u WHERE EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id) AND u.User_Status = 'Active'");
                    $stmt->execute();
                } else {
                    $panel = select("marzban_panel", "*", "code_panel", $userdata['selectpanel'], "select");
                    if (empty($panel['name_panel'])) {
                        sendmessage($from_id, $textbotlang['Admin']['messageBulk']['errorRestart'], $keyboardadmin, 'HTML');
                        return;
                    }
                    $stmt = $pdo->prepare("SELECT u.id FROM user u WHERE EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id AND i.Service_location = :mp1) AND u.User_Status = 'Active'");
                    $stmt->execute([':mp1' => $panel['name_panel']]);
                }
                $userslist = json_encode($stmt->fetchAll());
            } elseif ($typeusermessage == "nonecustomer") {
                $stmt = $pdo->prepare("SELECT u.id FROM user u WHERE NOT EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id) AND u.User_Status = 'Active'");
                $stmt->execute();
                $userslist = json_encode($stmt->fetchAll());
            }
        } else {
            if ($typeusermessage == "all") {
                $userslist = json_encode(select("user", "id", "agent", $agent, "fetchAll"));
            } elseif ($typeusermessage == "customer") {
                if (($userdata['selectpanel'] ?? 'all') == "all") {
                    $stmt = $pdo->prepare("SELECT u.id FROM user u WHERE u.agent =  :agent AND EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id) AND u.User_Status = 'Active'");
                    $stmt->bindParam(':agent', $agent, PDO::PARAM_STR);
                    $stmt->execute();
                } else {
                    $panel = select("marzban_panel", "*", "code_panel", $userdata['selectpanel'], "select");
                    if (empty($panel['name_panel'])) {
                        sendmessage($from_id, $textbotlang['Admin']['messageBulk']['errorRestart'], $keyboardadmin, 'HTML');
                        return;
                    }
                    $stmt = $pdo->prepare("SELECT u.id FROM user u WHERE  u.agent =  :agent AND EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id AND i.Service_location = :location) AND u.User_Status = 'Active'");
                    $stmt->bindParam(':agent', $agent, PDO::PARAM_STR);
                    $stmt->bindParam(':location', $panel['name_panel'], PDO::PARAM_STR);
                    $stmt->execute();
                }
                $userslist = json_encode($stmt->fetchAll());
            } elseif ($typeusermessage == "nonecustomer") {
                $stmt = $pdo->prepare("SELECT u.id FROM user u WHERE u.agent =  :agent AND NOT EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id) AND u.User_Status = 'Active'");
                $stmt->bindParam(':agent', $agent, PDO::PARAM_STR);
                $stmt->execute();
                $userslist = json_encode($stmt->fetchAll());
            }
        }
        $message_id = Editmessagetext($from_id, $message_id, $textbotlang['Admin']['messageBulk']['started'], $cancelmessage);
        $data = json_encode(array(
            "id_admin" => $from_id,
            'type' => "sendmessage",
            "id_message" => $message_id['result']['message_id'],
            "message" => $userdata['message'],
            "pingmessage" => $userdata['typepinmessage'],
            "btnmessage" => $userdata['btntypemessage']
        ));
        file_put_contents("cronbot/users.json", $userslist);
        file_put_contents('cronbot/info', $data);
    } elseif ($typeservice == "forwardmessage") {
        if ($agent == "all") {
            if ($typeusermessage == "all") {
                $userslist = json_encode(select("user", "id", "User_Status", "Active", "fetchAll"));
            } elseif ($typeusermessage == "customer") {
                if (($userdata['selectpanel'] ?? 'all') == "all") {
                    $stmt = $pdo->prepare("SELECT u.id FROM user u WHERE EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id) AND u.User_Status = 'Active'");
                    $stmt->execute();
                } else {
                    $panel = select("marzban_panel", "*", "code_panel", $userdata['selectpanel'], "select");
                    if (empty($panel['name_panel'])) {
                        sendmessage($from_id, $textbotlang['Admin']['messageBulk']['errorRestart'], $keyboardadmin, 'HTML');
                        return;
                    }
                    $stmt = $pdo->prepare("SELECT u.id FROM user u WHERE EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id AND i.Service_location = :mp3) AND u.User_Status = 'Active'");
                    $stmt->execute([':mp3' => $panel['name_panel']]);
                }
                $userslist = json_encode($stmt->fetchAll());
            } elseif ($typeusermessage == "nonecustomer") {
                $stmt = $pdo->prepare("SELECT u.id FROM user u WHERE NOT EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id) AND u.User_Status = 'Active'");
                $stmt->execute();
                $userslist = json_encode($stmt->fetchAll());
            }
        } else {
            if ($typeusermessage == "all") {
                $userslist = json_encode(select("user", "id", "agent", $agent, "fetchAll"));
            } elseif ($typeusermessage == "customer") {
                if (($userdata['selectpanel'] ?? 'all') == "all") {
                    $stmt = $pdo->prepare("SELECT u.id FROM user u WHERE u.agent =  :agent AND EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id) AND u.User_Status = 'Active'");
                    $stmt->bindParam(':agent', $agent, PDO::PARAM_STR);
                    $stmt->execute();
                } else {
                    $panel = select("marzban_panel", "*", "code_panel", $userdata['selectpanel'], "select");
                    if (empty($panel['name_panel'])) {
                        sendmessage($from_id, $textbotlang['Admin']['messageBulk']['errorRestart'], $keyboardadmin, 'HTML');
                        return;
                    }
                    $stmt = $pdo->prepare("SELECT u.id FROM user u WHERE u.agent =  :agent AND EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id AND i.Service_location = :location) AND u.User_Status = 'Active'");
                    $stmt->bindParam(':agent', $agent, PDO::PARAM_STR);
                    $stmt->bindParam(':location', $panel['name_panel'], PDO::PARAM_STR);
                    $stmt->execute();
                }
                $userslist = json_encode($stmt->fetchAll());
            } elseif ($typeusermessage == "nonecustomer") {
                $stmt = $pdo->prepare("SELECT u.id FROM user u WHERE u.agent =  :agent AND NOT EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id) AND u.User_Status = 'Active'");
                $stmt->bindParam(':agent', $agent, PDO::PARAM_STR);
                $stmt->execute();
                $userslist = json_encode($stmt->fetchAll());
            }
        }
        $message_id = Editmessagetext($from_id, $message_id, $textbotlang['Admin']['messageBulk']['started'], $cancelmessage);
        $data = json_encode(array(
            "id_admin" => $from_id,
            'type' => "forwardmessage",
            "id_message" => $message_id['result']['message_id'],
            "message" => $userdata['message'],
            "pingmessage" => $userdata['typepinmessage'],
        ));
        file_put_contents("cronbot/users.json", $userslist);
        file_put_contents('cronbot/info', $data);
    } elseif ($typeservice == "xdaynotmessage") {
        $timedaystamp = intval($userdata['daynoyuse']) * 86400;
        $timenouser = time() - $timedaystamp;
        if ($agent == "all") {
            $stmt = $pdo->prepare("SELECT id FROM user  WHERE last_message_time < $timenouser");
            $stmt->execute();
            $userslist = json_encode($stmt->fetchAll());
        } else {
            if ($typeusermessage == "all") {
                $stmt = $pdo->prepare("SELECT u.id FROM user u WHERE u.last_message_time < :time");
                $stmt->bindParam(':time', $timenouser, PDO::PARAM_STR);
                $stmt->execute();
                $userslist = json_encode($stmt->fetchAll());
            } elseif ($typeusermessage == "customer") {
                if ($userdata['selectpanel'] == "all") {
                    $stmt = $pdo->prepare("SELECT u.id FROM user u WHERE u.agent =  :agent AND u.last_message_time < :time AND EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id);");
                } else {
                    $panel = select("marzban_panel", "*", "code_panel", $userdata['selectpanel'], "select");
                    $stmt = $pdo->prepare("SELECT u.id FROM user u WHERE u.agent =  :agent AND u.last_message_time < :time AND EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id AND i.Service_location = :location);");
                    $stmt->bindParam(':location', $panel['name_panel'], PDO::PARAM_STR);
                }
                $stmt->bindParam(':agent', $agent, PDO::PARAM_STR);
                $stmt->bindParam(':time', $timenouser, PDO::PARAM_STR);
                $stmt->execute();
                $userslist = json_encode($stmt->fetchAll());
            } elseif ($typeusermessage == "nonecustomer") {
                $stmt = $pdo->prepare("SELECT u.id FROM user u WHERE u.agent =  :agent AND u.last_message_time < :time AND NOT EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id);");
                $stmt->bindParam(':agent', $agent, PDO::PARAM_STR);
                $stmt->bindParam(':time', $timenouser, PDO::PARAM_STR);
                $stmt->execute();
                $userslist = json_encode($stmt->fetchAll());
            }
        }
        $message_id = Editmessagetext($from_id, $message_id, $textbotlang['Admin']['messageBulk']['started'], $cancelmessage);
        $data = json_encode(array(
            "id_admin" => $from_id,
            'type' => "xdaynotmessage",
            "id_message" => $message_id['result']['message_id'],
            "message" => $userdata['message'],
            "pingmessage" => $userdata['typepinmessage'],
            "btnmessage" => $userdata['btntypemessage']
        ));
        file_put_contents("cronbot/users.json", $userslist);
        file_put_contents('cronbot/info', $data);
    }
} elseif ($datain == "cancel_sendmessage") {
    if (is_file('cronbot/users.json')) {
        unlink('cronbot/users.json');
    }
    if (is_file('cronbot/info')) {
        unlink('cronbot/info');
    }
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['messageBulk']['canceled'], null, 'HTML');
} elseif (preg_match('/sendmessageuser_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    savedata("clear", "iduser", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['messageBulk']['askTextOrImage'], $backadmin, 'HTML');
    step('sendmessagetext', $from_id);
} elseif ($user['step'] == "sendmessagetext") {
    if ($photo) {
        savedata("save", "type", "photo");
        savedata("save", "photoid", $photoid);
        savedata("save", "text", $caption);
    } else {
        savedata("save", "text", $text);
        savedata("save", "type", "text");
    }
    $textb = $textbotlang['Admin']['messageBulk']['askAllowReply'];
    sendmessage($from_id, $textb, $backadmin, 'HTML');
    step('sendmessagetid', $from_id);
} elseif ($user['step'] == "sendmessagetid") {
    $userdata = json_decode($user['Processing_value'], true);
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    $textsendadmin = sprintf($textbotlang['users']['support']['messageFromAdminAlt'], $userdata['text']);
    if (intval($text) == "1") {
        $Response = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['support']['answermessage'], 'callback_data' => 'Responseuser'],
                ],
            ]
        ]);
        if ($userdata['type'] == "photo") {
            telegram('sendphoto', [
                'chat_id' => $userdata['iduser'],
                'photo' => $userdata['photoid'],
                'caption' => $textsendadmin,
                'reply_markup' => $Response,
                'parse_mode' => "HTML",
            ]);
        } else {
            sendmessage($userdata['iduser'], $textsendadmin, $Response, 'HTML');
        }
    } else {
        if ($userdata['type'] == "photo") {
            telegram('sendphoto', [
                'chat_id' => $userdata['iduser'],
                'photo' => $userdata['photoid'],
                'caption' => $textsendadmin,
                'parse_mode' => "HTML",
            ]);
        } else {
            sendmessage($userdata['iduser'], $textsendadmin, null, 'HTML');
        }
    }
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['messageSent'], $keyboardadmin, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['Admin']['messageBulk']['btnForwardToUser']) {
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['getText'], $backadmin, 'HTML');
    step('getmessageforward', $from_id);
} elseif ($user['step'] == "getmessageforward") {
    savedata("clear", "messageid", $message_id);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['getIdMessage'], $backadmin, 'HTML');
    step('getbtnresponseforward', $from_id);
} elseif ($user['step'] == "getbtnresponseforward") {
    $userdata = json_decode($user['Processing_value'], true);
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    forwardMessage($from_id, $userdata['messageid'], $text);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['messageSent'], $keyboardadmin, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['educationSection'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboardhelpadmin, 'HTML');
} elseif ($text == $textbotlang['keyboard']['addEducation'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['getAddName'], $backadmin, 'HTML');
    step('add_name_help', $from_id);
} elseif ($user['step'] == "add_name_help") {
    if (strlen($text) >= 150) {
        sendmessage($from_id, $textbotlang['Admin']['Help']['nameTooLong'], null, 'HTML');
        return;
    }
    $helpexits = select("help", "*", "name_os", $text, "count");
    if ($helpexits != 0) {
        sendmessage($from_id, $textbotlang['Admin']['Help']['nameExists'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("INSERT IGNORE INTO help (name_os) VALUES (?)");
    $stmt->execute([$text]);
    update("user", "Processing_value", $text, "id", $from_id);
    if ($setting['categoryhelp'] == "0") {
        update("help", "category", "0", "name_os", $user['Processing_value']);
        sendmessage($from_id, $textbotlang['Admin']['Help']['getAddDesc'], $backadmin, 'HTML');
        step('add_dec', $from_id);
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Help']['askCategoryName'], $backadmin, 'HTML');
    step('getcatgoryhelp', $from_id);
} elseif ($user['step'] == "getcatgoryhelp") {
    update("help", "category", $text, "name_os", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Help']['getAddDesc'], $backadmin, 'HTML');
    step('add_dec', $from_id);
} elseif ($user['step'] == "add_dec") {
    if ($photo) {
        if (isset($photoid))
            update("help", "Media_os", $photoid, "name_os", $user['Processing_value']);
        if (isset($caption))
            update("help", "Description_os", $caption, "name_os", $user['Processing_value']);
        update("help", "type_Media_os", "photo", "name_os", $user['Processing_value']);
    } elseif ($text) {
        update("help", "Description_os", $text, "name_os", $user['Processing_value']);
    } elseif ($video) {
        if (isset($videoid))
            update("help", "Media_os", $videoid, "name_os", $user['Processing_value']);
        if (isset($caption))
            update("help", "Description_os", $caption, "name_os", $user['Processing_value']);
        update("help", "type_Media_os", "video", "name_os", $user['Processing_value']);
    } elseif ($document) {
        if (isset($fileid))
            update("help", "Media_os", $fileid, "name_os", $user['Processing_value']);
        if (isset($caption))
            update("help", "Description_os", $caption, "name_os", $user['Processing_value']);
        update("help", "type_Media_os", "document", "name_os", $user['Processing_value']);
    }
    sendmessage($from_id, $textbotlang['Admin']['Help']['saveHelp'], $keyboardadmin, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['deleteEducation'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['selectName'], $json_list_helpkey, 'HTML');
    step('remove_help', $from_id);
} elseif ($user['step'] == "remove_help") {
    $stmt = $pdo->prepare("DELETE FROM help WHERE name_os = :name_os");
    $stmt->bindParam(':name_os', $text, PDO::PARAM_STR);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Help']['removeHelp'], $keyboardhelpadmin, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/Response_(\w+)/', $datain, $dataget) && ($adminrulecheck['rule'] == "administrator" || $adminrulecheck['rule'] == "support")) {
    $iduser = $dataget[1];
    update("user", "Processing_value", $iduser, "id", $from_id);
    step('getmessageAsAdmin', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['getTextResponse'], $backadmin, 'HTML');
} elseif ($user['step'] == "getmessageAsAdmin") {
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['sendMessageUser'], null, 'HTML');
    $Respuseronse = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['support']['answermessage'], 'callback_data' => 'Responseuser'],
            ],
        ]
    ]);
    if ($text) {
        $textSendAdminToUser = sprintf($textbotlang['users']['support']['messageFromManagement'], $text);
        sendmessage($user['Processing_value'], $textSendAdminToUser, $Respuseronse, 'HTML');
    }
    if ($photo) {
        $textSendAdminToUser = sprintf($textbotlang['users']['support']['messageFromManagement2'], $caption);
        telegram('sendphoto', [
            'chat_id' => $user['Processing_value'],
            'photo' => $photoid,
            'reply_markup' => $Respuseronse,
            'caption' => $textSendAdminToUser,
            'parse_mode' => "HTML",
        ]);
    }
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['featureStatus'] && $adminrulecheck['rule'] == "administrator") {
    if ($setting['Bot_Status'] == $textbotlang['Admin']['Status']['botOn']) {
        update("setting", "Bot_Status", "botstatuson");
    } elseif ($setting['Bot_Status'] == $textbotlang['Admin']['Status']['botOff']) {
        update("setting", "Bot_Status", "botstatusoff");
    }
    if ($setting['roll_Status'] == $textbotlang['Admin']['Status']['rulesOn']) {
        update("setting", "roll_Status", "rolleon");
    } elseif ($setting['roll_Status'] == $textbotlang['Admin']['Status']['rulesOff']) {
        update("setting", "roll_Status", "rolleoff");
    }
    if ($setting['get_number'] == $textbotlang['Admin']['Status']['phoneVerifyOn']) {
        update("setting", "get_number", "onAuthenticationphone");
    } elseif ($setting['get_number'] == $textbotlang['Admin']['Status']['phoneVerifyOff']) {
        update("setting", "get_number", "offAuthenticationphone");
    }
    if ($setting['iran_number'] == $textbotlang['Admin']['Status']['iranPhoneOn']) {
        update("setting", "iran_number", "onAuthenticationiran");
    } elseif ($setting['iran_number'] == $textbotlang['Admin']['Status']['iranPhoneOff']) {
        update("setting", "iran_number", "offAuthenticationiran");
    }
    $status_cron = json_decode($setting['cron_status'], true);
    $setting = select("setting", "*", null, null, "select");
    $name_status = [
        'botstatuson' => $textbotlang['Admin']['Status']['statuson'],
        'botstatusoff' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['Bot_Status']];
    $name_status_username = [
        'onnotuser' => $textbotlang['Admin']['Status']['statuson'],
        'offnotuser' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['NotUser']];
    $name_status_notifnewuser = [
        'onnewuser' => $textbotlang['Admin']['Status']['statuson'],
        'offnewuser' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statusnewuser']];
    $name_status_showagent = [
        'onrequestagent' => $textbotlang['Admin']['Status']['statuson'],
        'offrequestagent' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statusagentrequest']];
    $name_status_role = [
        'rolleon' => $textbotlang['Admin']['Status']['statuson'],
        'rolleoff' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['roll_Status']];
    $Authenticationphone = [
        'onAuthenticationphone' => $textbotlang['Admin']['Status']['statuson'],
        'offAuthenticationphone' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['get_number']];
    $Authenticationiran = [
        'onAuthenticationiran' => $textbotlang['Admin']['Status']['statuson'],
        'offAuthenticationiran' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['iran_number']];
    $statusinline = [
        'oninline' => $textbotlang['Admin']['Status']['statuson'],
        'offinline' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['inlinebtnmain']];
    $statusverify = [
        'onverify' => $textbotlang['Admin']['Status']['statuson'],
        'offverify' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['verifystart']];
    $statuspvsupport = [
        'onpvsupport' => $textbotlang['Admin']['Status']['statuson'],
        'offpvsupport' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statussupportpv']];
    $statusnameconfig = [
        'onnamecustom' => $textbotlang['Admin']['Status']['statuson'],
        'offnamecustom' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statusnamecustom']];
    $statusnamebulk = [
        'onbulk' => $textbotlang['Admin']['Status']['statuson'],
        'offbulk' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['bulkbuy']];
    $statusverifybyuser = [
        'onverify' => $textbotlang['Admin']['Status']['statuson'],
        'offverify' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['verifybucodeuser']];
    $score = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['scorestatus']];
    $wheel_luck = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['wheelـluck']];
    $refralstatus = [
        'onaffiliates' => $textbotlang['Admin']['Status']['statuson'],
        'offaffiliates' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['affiliatesstatus']];
    $btnstatuscategory = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['categoryhelp']];
    $btnstatuslinkapp = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['linkappstatus']];
    $cronteststatustext = [
        true => $textbotlang['Admin']['Status']['statuson'],
        false => $textbotlang['Admin']['Status']['statusoff']
    ][$status_cron['test']];
    $crondaystatustext = [
        true => $textbotlang['Admin']['Status']['statuson'],
        false => $textbotlang['Admin']['Status']['statusoff']
    ][$status_cron['day']];
    $cronvolumestatustext = [
        true => $textbotlang['Admin']['Status']['statuson'],
        false => $textbotlang['Admin']['Status']['statusoff']
    ][$status_cron['volume']];
    $cronremovestatustext = [
        true => $textbotlang['Admin']['Status']['statuson'],
        false => $textbotlang['Admin']['Status']['statusoff']
    ][$status_cron['remove']];
    $cronremovevolumestatustext = [
        true => $textbotlang['Admin']['Status']['statuson'],
        false => $textbotlang['Admin']['Status']['statusoff']
    ][$status_cron['remove_volume']];
    $cronuptime_nodestatustext = [
        true => $textbotlang['Admin']['Status']['statuson'],
        false => $textbotlang['Admin']['Status']['statusoff']
    ][$status_cron['uptime_node']];
    $cronuptime_panelstatustext = [
        true => $textbotlang['Admin']['Status']['statuson'],
        false => $textbotlang['Admin']['Status']['statusoff']
    ][$status_cron['uptime_panel']];
    $cronon_holdtext = [
        true => $textbotlang['Admin']['Status']['statuson'],
        false => $textbotlang['Admin']['Status']['statusoff']
    ][$status_cron['on_hold']];
    $wheelagent = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['wheelagent']];
    $Lotteryagent = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['Lotteryagent']];
    $statusfirstwheel = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statusfirstwheel']];
    $statuslimitchangeloc = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statuslimitchangeloc']];
    $statusDebtsettlement = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['Debtsettlement']];
    $statusDice = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['Dice']];
    $statusnotef = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statusnoteforf']];
    $status_copy_cart = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statuscopycart']];
    $keyboard_config_text = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['status_keyboard_config']];
    $Bot_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['Status']['subject'], 'callback_data' => "subject"],
                ['text' => $textbotlang['Admin']['Status']['statusSubject'], 'callback_data' => "subjectde"],
            ],
            [
                ['text' => $name_status, 'callback_data' => "editstsuts-statusbot-{$setting['Bot_Status']}"],
                ['text' => $textbotlang['Admin']['Status']['statusBot'], 'callback_data' => "statusbot"],
            ],
            [
                ['text' => $name_status_username, 'callback_data' => "editstsuts-usernamebtn-{$setting['NotUser']}"],
                ['text' => $textbotlang['Admin']['Status']['statusUsernameBtn'], 'callback_data' => "usernamebtn"],
            ],
            [
                ['text' => $name_status_notifnewuser, 'callback_data' => "editstsuts-notifnew-{$setting['statusnewuser']}"],
                ['text' => $textbotlang['Admin']['Status']['statusNotifNewUser'], 'callback_data' => "statusnewuser"],
            ],
            [
                ['text' => $name_status_showagent, 'callback_data' => "editstsuts-showagent-{$setting['statusagentrequest']}"],
                ['text' => $textbotlang['Admin']['Status']['statusShowAgent'], 'callback_data' => "statusnewuser"],
            ],
            [
                ['text' => $name_status_role, 'callback_data' => "editstsuts-role-{$setting['roll_Status']}"],
                ['text' => $textbotlang['Admin']['Status']['statusRole'], 'callback_data' => "stautsrolee"],
            ],
            [
                ['text' => $Authenticationphone, 'callback_data' => "editstsuts-Authenticationphone-{$setting['get_number']}"],
                ['text' => $textbotlang['Admin']['Status']['Authenticationphone'], 'callback_data' => "Authenticationphone"],
            ],
            [
                ['text' => $Authenticationiran, 'callback_data' => "editstsuts-Authenticationiran-{$setting['iran_number']}"],
                ['text' => $textbotlang['Admin']['Status']['Authenticationiran'], 'callback_data' => "Authenticationiran"],
            ],
            [
                ['text' => $statusinline, 'callback_data' => "editstsuts-inlinebtnmain-{$setting['inlinebtnmain']}"],
                ['text' => $textbotlang['Admin']['Status']['inlinebtns'], 'callback_data' => "inlinebtnmain"],
            ],
            [
                ['text' => $statusverify, 'callback_data' => "editstsuts-verifystart-{$setting['verifystart']}"],
                ['text' => $textbotlang['keyboard']['authenticate'], 'callback_data' => "verify"],
            ],
            [
                ['text' => $statuspvsupport, 'callback_data' => "editstsuts-statussupportpv-{$setting['statussupportpv']}"],
                ['text' => $textbotlang['keyboard']['supportInPv'], 'callback_data' => "statussupportpv"],
            ],
            [
                ['text' => $statusnameconfig, 'callback_data' => "editstsuts-statusnamecustom-{$setting['statusnamecustom']}"],
                ['text' => $textbotlang['keyboard']['configNote'], 'callback_data' => "statusnamecustom"],
            ],
            [
                ['text' => $statusnotef, 'callback_data' => "editstsuts-statusnamecustomf-{$setting['statusnoteforf']}"],
                ['text' => $textbotlang['keyboard']['userNote'], 'callback_data' => "statusnamecustomf"],
            ],
            [
                ['text' => $statusnamebulk, 'callback_data' => "editstsuts-bulkbuy-{$setting['bulkbuy']}"],
                ['text' => $textbotlang['keyboard']['bulkPurchaseStatus'], 'callback_data' => "bulkbuy"],
            ],
            [
                ['text' => $statusverifybyuser, 'callback_data' => "editstsuts-verifybyuser-{$setting['verifybucodeuser']}"],
                ['text' => $textbotlang['keyboard']['authWithLink'], 'callback_data' => "verifybyuser"],
            ],
            [
                ['text' => $btnstatuscategory, 'callback_data' => "editstsuts-btn_status_category-{$setting['categoryhelp']}"],
                ['text' => $textbotlang['keyboard']['educationCategory'], 'callback_data' => "btn_status_category"],
            ],
            [
                ['text' => $wheelagent, 'callback_data' => "editstsuts-wheelagent-{$setting['wheelagent']}"],
                ['text' => $textbotlang['keyboard']['agentWheelOfLuck'], 'callback_data' => "wheelagent"],
            ],
            [
                ['text' => $keyboard_config_text, 'callback_data' => "editstsuts-keyconfig-{$setting['status_keyboard_config']}"],
                ['text' => $textbotlang['keyboard']['configKeyboard'], 'callback_data' => "keyconfig"],
            ],
            [
                ['text' => $statusDice, 'callback_data' => "editstsuts-Dice-{$setting['Dice']}"],
                ['text' => $textbotlang['keyboard']['showDice'], 'callback_data' => "Dice"],
            ],
            [
                ['text' => $statusfirstwheel, 'callback_data' => "editstsuts-wheelagentfirst-{$setting['statusfirstwheel']}"],
                ['text' => $textbotlang['keyboard']['firstPurchaseWheel'], 'callback_data' => "wheelagentfirst"],
            ],
            [
                ['text' => $Lotteryagent, 'callback_data' => "editstsuts-Lotteryagent-{$setting['Lotteryagent']}"],
                ['text' => $textbotlang['keyboard']['agentLottery'], 'callback_data' => "Lotteryagent"],
            ],
            [
                ['text' => $statusDebtsettlement, 'callback_data' => "editstsuts-Debtsettlement-{$setting['Debtsettlement']}"],
                ['text' => $textbotlang['keyboard']['settleDebt'], 'callback_data' => "Debtsettlement"],
            ],
            [
                ['text' => $status_copy_cart, 'callback_data' => "editstsuts-compycart-{$setting['statuscopycart']}"],
                ['text' => $textbotlang['keyboard']['copyCard'], 'callback_data' => "copycart"],
            ],
            [
                ['text' => $cronteststatustext, 'callback_data' => "editstsuts-crontest-{$status_cron['test']}"],
                ['text' => $textbotlang['keyboard']['cronTest'], 'callback_data' => "none"],
            ],
            [
                ['text' => $cronuptime_nodestatustext, 'callback_data' => "editstsuts-uptime_node-{$status_cron['uptime_node']}"],
                ['text' => $textbotlang['keyboard']['nodeUptime'], 'callback_data' => "none"],
            ],
            [
                ['text' => $cronuptime_panelstatustext, 'callback_data' => "editstsuts-uptime_panel-{$status_cron['uptime_panel']}"],
                ['text' => $textbotlang['keyboard']['panelUptime'], 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['keyboard']['timeAlert'], 'callback_data' => "settimecornday"],
                ['text' => $crondaystatustext, 'callback_data' => "editstsuts-cronday-{$status_cron['day']}"],
                ['text' => $textbotlang['keyboard']['cronTime'], 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['keyboard']['firstConnectTime'], 'callback_data' => "setting_on_holdcron"],
                ['text' => $cronon_holdtext, 'callback_data' => "editstsuts-on_hold-{$status_cron['on_hold']}"],
                ['text' => $textbotlang['keyboard']['cronFirstConnection'], 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['keyboard']['volumeAlert'], 'callback_data' => "settimecornvolume"],
                ['text' => $cronvolumestatustext, 'callback_data' => "editstsuts-cronvolume-{$status_cron['volume']}"],
                ['text' => $textbotlang['keyboard']['cronVolume'], 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['keyboard']['deleteTime'], 'callback_data' => "settimecornremove"],
                ['text' => $cronremovestatustext, 'callback_data' => "editstsuts-notifremove-{$status_cron['remove']}"],
                ['text' => $textbotlang['keyboard']['cronDelete'], 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['keyboard']['deleteTime'], 'callback_data' => "settimecornremovevolume"],
                ['text' => $cronremovevolumestatustext, 'callback_data' => "editstsuts-notifremove_volume-{$status_cron['remove_volume']}"],
                ['text' => $textbotlang['keyboard']['cronDeleteVolume'], 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "linkappsetting"],
                ['text' => $btnstatuslinkapp, 'callback_data' => "editstsuts-linkappstatus-{$setting['linkappstatus']}"],
                ['text' => $textbotlang['keyboard']['appDownloadLinkAlt'], 'callback_data' => "linkappstatus"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "scoresetting"],
                ['text' => $score, 'callback_data' => "editstsuts-score-{$setting['scorestatus']}"],
                ['text' => $textbotlang['keyboard']['nightLottery'], 'callback_data' => "score"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "gradonhshans"],
                ['text' => $wheel_luck, 'callback_data' => "editstsuts-wheel_luck-{$setting['wheelـluck']}"],
                ['text' => $textbotlang['keyboard']['wheelOfLuck'], 'callback_data' => "wheel_luck"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "settingaffiliatesf"],
                ['text' => $refralstatus, 'callback_data' => "editstsuts-affiliatesstatus-{$setting['affiliatesstatus']}"],
                ['text' => $textbotlang['keyboard']['affiliateGift'], 'callback_data' => "affiliatesstatus"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "changeloclimit"],
                ['text' => $statuslimitchangeloc, 'callback_data' => "editstsuts-changeloc-{$setting['statuslimitchangeloc']}"],
                ['text' => $textbotlang['keyboard']['locationChangeLimit'], 'callback_data' => "changeloc"],
            ]
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['botTitle'], $Bot_Status, 'HTML');
} elseif (preg_match('/^editstsuts-(.*)-(.*)/', $datain, $dataget)) {
    $status_cron = json_decode($setting['cron_status'], true);
    $type = $dataget[1];
    $value = $dataget[2];
    if ($type == "statusbot") {
        if ($value == "botstatuson") {
            $valuenew = "botstatusoff";
        } else {
            $valuenew = "botstatuson";
        }
        update("setting", "Bot_Status", $valuenew);
    } elseif ($type == "usernamebtn") {
        if ($value == "onnotuser") {
            $valuenew = "offnotuser";
        } else {
            $valuenew = "onnotuser";
        }
        update("setting", "NotUser", $valuenew);
    } elseif ($type == "notifnew") {
        if ($value == "onnewuser") {
            $valuenew = "offnewuser";
        } else {
            $valuenew = "onnewuser";
        }
        update("setting", "statusnewuser", $valuenew);
    } elseif ($type == "showagent") {
        if ($value == "onrequestagent") {
            $valuenew = "offrequestagent";
        } else {
            $valuenew = "onrequestagent";
        }
        update("setting", "statusagentrequest", $valuenew);
    } elseif ($type == "role") {
        if ($value == "rolleon") {
            $valuenew = "rolleoff";
        } else {
            $valuenew = "rolleon";
        }
        update("setting", "roll_Status", $valuenew);
    } elseif ($type == "Authenticationphone") {
        if ($value == "onAuthenticationphone") {
            $valuenew = "offAuthenticationphone";
        } else {
            $valuenew = "onAuthenticationphone";
        }
        update("setting", "get_number", $valuenew);
    } elseif ($type == "Authenticationiran") {
        if ($value == "onAuthenticationiran") {
            $valuenew = "offAuthenticationiran";
        } else {
            $valuenew = "onAuthenticationiran";
        }
        update("setting", "iran_number", $valuenew);
    } elseif ($type == "inlinebtnmain") {
        if ($value == "oninline") {
            $valuenew = "offinline";
        } else {
            $valuenew = "oninline";
        }
        update("setting", "inlinebtnmain", $valuenew);
    } elseif ($type == "verifystart") {
        if ($value == "onverify") {
            $valuenew = "offverify";
        } else {
            $valuenew = "onverify";
        }
        update("setting", "verifystart", $valuenew);
    } elseif ($type == "statussupportpv") {
        if ($value == "onpvsupport") {
            $valuenew = "offpvsupport";
        } else {
            $valuenew = "onpvsupport";
        }
        update("setting", "statussupportpv", $valuenew);
    } elseif ($type == "statusnamecustom") {
        if ($value == "onnamecustom") {
            $valuenew = "offnamecustom";
        } else {
            $valuenew = "onnamecustom";
        }
        update("setting", "statusnamecustom", $valuenew);
    } elseif ($type == "bulkbuy") {
        if ($value == "onbulk") {
            $valuenew = "offbulk";
        } else {
            $valuenew = "onbulk";
        }
        update("setting", "bulkbuy", $valuenew);
    } elseif ($type == "verifybyuser") {
        if ($value == "onverify") {
            $valuenew = "offverify";
        } else {
            $valuenew = "onverify";
        }
        update("setting", "verifybucodeuser", $valuenew);
    } elseif ($type == "wheelagent") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "wheelagent", $valuenew);
    } elseif ($type == "keyconfig") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "status_keyboard_config", $valuenew);
    } elseif ($type == "Lotteryagent") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "Lotteryagent", $valuenew);
    } elseif ($type == "compycart") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "statuscopycart", $valuenew);
    } elseif ($type == "score") {
        if ($value == "1") {
                $crontabBinary = getCrontabBinary();
                if ($crontabBinary === null) {
                    error_log('Unable to locate crontab executable; cannot remove lottery cron job.');
                } else {
                    $currentCronJobs = runShellCommand(sprintf('%s -l 2>/dev/null', escapeshellarg($crontabBinary)));
                    $jobToRemove = "*/1 * * * * curl https://$domainhosts/cronbot/lottery.php";
                    $newCronJobs = preg_replace('/' . preg_quote($jobToRemove, '/') . '/', '', (string) $currentCronJobs);
                    $tempCronFile = tempnam(sys_get_temp_dir(), 'cron');
                    if ($tempCronFile === false) {
                        error_log('Unable to create temporary file for lottery cron job removal.');
                    } else {
                        file_put_contents($tempCronFile, trim((string) $newCronJobs) . PHP_EOL);
                        runShellCommand(sprintf('%s %s', escapeshellarg($crontabBinary), escapeshellarg($tempCronFile)));
                        unlink($tempCronFile);
                    }
                }
            $valuenew = "0";
        } else {
            $phpFilePath = "https://$domainhosts/cronbot/lottery.php";
            $cronCommand = "*/1 * * * * curl $phpFilePath";
            if (!addCronIfNotExists($cronCommand)) {
                error_log('Unable to register lottery cron job because shell_exec is unavailable.');
            }
            $valuenew = "1";
        }
        update("setting", "scorestatus", $valuenew);
    } elseif ($type == "wheel_luck") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", 'wheelـluck', $valuenew);
    } elseif ($type == "affiliatesstatus") {
        if ($value == "onaffiliates") {
            $valuenew = "offaffiliates";
        } else {
            $valuenew = "onaffiliates";
        }
        update("setting", "affiliatesstatus", $valuenew);
    } elseif ($type == "btn_status_category") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "categoryhelp", $valuenew);
    } elseif ($type == "linkappstatus") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "linkappstatus", $valuenew);
    } elseif ($type == "wheelagentfirst") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "statusfirstwheel", $valuenew);
    } elseif ($type == "changeloc") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "statuslimitchangeloc", $valuenew);
    } elseif ($type == "Debtsettlement") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "Debtsettlement", $valuenew);
    } elseif ($type == "Dice") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "Dice", $valuenew);
    } elseif ($type == "statusnamecustomf") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "statusnoteforf", $valuenew);
    } elseif ($type == "crontest") {
        if ($value == true) {
            $valueneww = false;
        } else {
            $valueneww = true;
        }
        $status_cron['test'] = $valueneww;
        update("setting", "cron_status", json_encode($status_cron));
    } elseif ($type == "cronday") {
        if ($value == true) {
            $valueneww = false;
        } else {
            $valueneww = true;
        }
        $status_cron['day'] = $valueneww;
        update("setting", "cron_status", json_encode($status_cron));
    } elseif ($type == "cronvolume") {
        if ($value == true) {
            $valueneww = false;
        } else {
            $valueneww = true;
        }
        $status_cron['volume'] = $valueneww;
        update("setting", "cron_status", json_encode($status_cron));
    } elseif ($type == "notifremove") {
        if ($value == true) {
            $valueneww = false;
        } else {
            $valueneww = true;
        }
        $status_cron['remove'] = $valueneww;
        update("setting", "cron_status", json_encode($status_cron));
    } elseif ($type == "notifremove_volume") {
        if ($value == true) {
            $valueneww = false;
        } else {
            $valueneww = true;
        }
        $status_cron['remove_volume'] = $valueneww;
        update("setting", "cron_status", json_encode($status_cron));
    } elseif ($type == "uptime_node") {
        if ($value == true) {
            $valueneww = false;
        } else {
            $valueneww = true;
        }
        $status_cron['uptime_node'] = $valueneww;
        update("setting", "cron_status", json_encode($status_cron));
    } elseif ($type == "uptime_panel") {
        if ($value == true) {
            $valueneww = false;
        } else {
            $valueneww = true;
        }
        $status_cron['uptime_panel'] = $valueneww;
        update("setting", "cron_status", json_encode($status_cron));
    } elseif ($type == "on_hold") {
        if ($value == true) {
            $valueneww = false;
        } else {
            $valueneww = true;
        }
        $status_cron['on_hold'] = $valueneww;
        update("setting", "cron_status", json_encode($status_cron));
    }
    $setting = select("setting", "*");
    $status_cron = json_decode($setting['cron_status'], true);
    $name_status = [
        'botstatuson' => $textbotlang['Admin']['Status']['statuson'],
        'botstatusoff' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['Bot_Status']];
    $name_status_username = [
        'onnotuser' => $textbotlang['Admin']['Status']['statuson'],
        'offnotuser' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['NotUser']];
    $name_status_notifnewuser = [
        'onnewuser' => $textbotlang['Admin']['Status']['statuson'],
        'offnewuser' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statusnewuser']];
    $name_status_showagent = [
        'onrequestagent' => $textbotlang['Admin']['Status']['statuson'],
        'offrequestagent' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statusagentrequest']];
    $name_status_role = [
        'rolleon' => $textbotlang['Admin']['Status']['statuson'],
        'rolleoff' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['roll_Status']];
    $Authenticationphone = [
        'onAuthenticationphone' => $textbotlang['Admin']['Status']['statuson'],
        'offAuthenticationphone' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['get_number']];
    $Authenticationiran = [
        'onAuthenticationiran' => $textbotlang['Admin']['Status']['statuson'],
        'offAuthenticationiran' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['iran_number']];
    $statusinline = [
        'oninline' => $textbotlang['Admin']['Status']['statuson'],
        'offinline' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['inlinebtnmain']];
    $statusverify = [
        'onverify' => $textbotlang['Admin']['Status']['statuson'],
        'offverify' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['verifystart']];
    $statuspvsupport = [
        'onpvsupport' => $textbotlang['Admin']['Status']['statuson'],
        'offpvsupport' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statussupportpv']];
    $statusnameconfig = [
        'onnamecustom' => $textbotlang['Admin']['Status']['statuson'],
        'offnamecustom' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statusnamecustom']];
    $statusnamebulk = [
        'onbulk' => $textbotlang['Admin']['Status']['statuson'],
        'offbulk' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['bulkbuy']];
    $statusverifybyuser = [
        'onverify' => $textbotlang['Admin']['Status']['statuson'],
        'offverify' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['verifybucodeuser']];
    $score = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['scorestatus']];
    $wheel_luck = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['wheelـluck']];
    $refralstatus = [
        'onaffiliates' => $textbotlang['Admin']['Status']['statuson'],
        'offaffiliates' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['affiliatesstatus']];
    $btnstatuscategory = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['categoryhelp']];
    $btnstatuslinkapp = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['linkappstatus']];
    $cronteststatustext = [
        true => $textbotlang['Admin']['Status']['statuson'],
        false => $textbotlang['Admin']['Status']['statusoff']
    ][$status_cron['test']];
    $crondaystatustext = [
        true => $textbotlang['Admin']['Status']['statuson'],
        false => $textbotlang['Admin']['Status']['statusoff']
    ][$status_cron['day']];
    $cronvolumestatustext = [
        true => $textbotlang['Admin']['Status']['statuson'],
        false => $textbotlang['Admin']['Status']['statusoff']
    ][$status_cron['volume']];
    $cronremovestatustext = [
        true => $textbotlang['Admin']['Status']['statuson'],
        false => $textbotlang['Admin']['Status']['statusoff']
    ][$status_cron['remove']];
    $cronremovevolumestatustext = [
        true => $textbotlang['Admin']['Status']['statuson'],
        false => $textbotlang['Admin']['Status']['statusoff']
    ][$status_cron['remove_volume']];
    $cronuptime_nodestatustext = [
        true => $textbotlang['Admin']['Status']['statuson'],
        false => $textbotlang['Admin']['Status']['statusoff']
    ][$status_cron['uptime_node']];
    $cronuptime_panelstatustext = [
        true => $textbotlang['Admin']['Status']['statuson'],
        false => $textbotlang['Admin']['Status']['statusoff']
    ][$status_cron['uptime_panel']];
    $cronon_holdtext = [
        true => $textbotlang['Admin']['Status']['statuson'],
        false => $textbotlang['Admin']['Status']['statusoff']
    ][$status_cron['on_hold']];
    $wheelagent = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['wheelagent']];
    $Lotteryagent = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['Lotteryagent']];
    $statusfirstwheel = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statusfirstwheel']];
    $statuslimitchangeloc = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statuslimitchangeloc']];
    $statusDebtsettlement = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['Debtsettlement']];
    $statusDice = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['Dice']];
    $statusnotef = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statusnoteforf']];
    $status_copy_cart = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statuscopycart']];
    $keyboard_config_text = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['status_keyboard_config']];
    $Bot_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['Status']['subject'], 'callback_data' => "subject"],
                ['text' => $textbotlang['Admin']['Status']['statusSubject'], 'callback_data' => "subjectde"],
            ],
            [
                ['text' => $name_status, 'callback_data' => "editstsuts-statusbot-{$setting['Bot_Status']}"],
                ['text' => $textbotlang['Admin']['Status']['statusBot'], 'callback_data' => "statusbot"],
            ],
            [
                ['text' => $name_status_username, 'callback_data' => "editstsuts-usernamebtn-{$setting['NotUser']}"],
                ['text' => $textbotlang['Admin']['Status']['statusUsernameBtn'], 'callback_data' => "usernamebtn"],
            ],
            [
                ['text' => $name_status_notifnewuser, 'callback_data' => "editstsuts-notifnew-{$setting['statusnewuser']}"],
                ['text' => $textbotlang['Admin']['Status']['statusNotifNewUser'], 'callback_data' => "statusnewuser"],
            ],
            [
                ['text' => $name_status_showagent, 'callback_data' => "editstsuts-showagent-{$setting['statusagentrequest']}"],
                ['text' => $textbotlang['Admin']['Status']['statusShowAgent'], 'callback_data' => "statusnewuser"],
            ],
            [
                ['text' => $name_status_role, 'callback_data' => "editstsuts-role-{$setting['roll_Status']}"],
                ['text' => $textbotlang['Admin']['Status']['statusRole'], 'callback_data' => "stautsrolee"],
            ],
            [
                ['text' => $Authenticationphone, 'callback_data' => "editstsuts-Authenticationphone-{$setting['get_number']}"],
                ['text' => $textbotlang['Admin']['Status']['Authenticationphone'], 'callback_data' => "Authenticationphone"],
            ],
            [
                ['text' => $Authenticationiran, 'callback_data' => "editstsuts-Authenticationiran-{$setting['iran_number']}"],
                ['text' => $textbotlang['Admin']['Status']['Authenticationiran'], 'callback_data' => "Authenticationiran"],
            ],
            [
                ['text' => $statusinline, 'callback_data' => "editstsuts-inlinebtnmain-{$setting['inlinebtnmain']}"],
                ['text' => $textbotlang['Admin']['Status']['inlinebtns'], 'callback_data' => "inlinebtnmain"],
            ],
            [
                ['text' => $statusverify, 'callback_data' => "editstsuts-verifystart-{$setting['verifystart']}"],
                ['text' => $textbotlang['keyboard']['authenticate'], 'callback_data' => "verify"],
            ],
            [
                ['text' => $statuspvsupport, 'callback_data' => "editstsuts-statussupportpv-{$setting['statussupportpv']}"],
                ['text' => $textbotlang['keyboard']['supportInPv'], 'callback_data' => "statussupportpv"],
            ],
            [
                ['text' => $statusnameconfig, 'callback_data' => "editstsuts-statusnamecustom-{$setting['statusnamecustom']}"],
                ['text' => $textbotlang['keyboard']['configNote'], 'callback_data' => "statusnamecustom"],
            ],
            [
                ['text' => $statusnotef, 'callback_data' => "editstsuts-statusnamecustomf-{$setting['statusnoteforf']}"],
                ['text' => $textbotlang['keyboard']['userNote'], 'callback_data' => "statusnamecustomf"],
            ],
            [
                ['text' => $statusnamebulk, 'callback_data' => "editstsuts-bulkbuy-{$setting['bulkbuy']}"],
                ['text' => $textbotlang['keyboard']['bulkPurchaseStatus'], 'callback_data' => "bulkbuy"],
            ],
            [
                ['text' => $statusverifybyuser, 'callback_data' => "editstsuts-verifybyuser-{$setting['verifybucodeuser']}"],
                ['text' => $textbotlang['keyboard']['authWithLink'], 'callback_data' => "verifybyuser"],
            ],
            [
                ['text' => $btnstatuscategory, 'callback_data' => "editstsuts-btn_status_category-{$setting['categoryhelp']}"],
                ['text' => $textbotlang['keyboard']['educationCategory'], 'callback_data' => "btn_status_category"],
            ],
            [
                ['text' => $wheelagent, 'callback_data' => "editstsuts-wheelagent-{$setting['wheelagent']}"],
                ['text' => $textbotlang['keyboard']['agentWheelOfLuck'], 'callback_data' => "wheelagent"],
            ],
            [
                ['text' => $keyboard_config_text, 'callback_data' => "editstsuts-keyconfig-{$setting['status_keyboard_config']}"],
                ['text' => $textbotlang['keyboard']['configKeyboard'], 'callback_data' => "keyconfig"],
            ],
            [
                ['text' => $statusDice, 'callback_data' => "editstsuts-Dice-{$setting['Dice']}"],
                ['text' => $textbotlang['keyboard']['showDice'], 'callback_data' => "Dice"],
            ],
            [
                ['text' => $statusfirstwheel, 'callback_data' => "editstsuts-wheelagentfirst-{$setting['statusfirstwheel']}"],
                ['text' => $textbotlang['keyboard']['firstPurchaseWheel'], 'callback_data' => "wheelagentfirst"],
            ],
            [
                ['text' => $Lotteryagent, 'callback_data' => "editstsuts-Lotteryagent-{$setting['Lotteryagent']}"],
                ['text' => $textbotlang['keyboard']['agentLottery'], 'callback_data' => "Lotteryagent"],
            ],
            [
                ['text' => $statusDebtsettlement, 'callback_data' => "editstsuts-Debtsettlement-{$setting['Debtsettlement']}"],
                ['text' => $textbotlang['keyboard']['settleDebt'], 'callback_data' => "Debtsettlement"],
            ],
            [
                ['text' => $status_copy_cart, 'callback_data' => "editstsuts-compycart-{$setting['statuscopycart']}"],
                ['text' => $textbotlang['keyboard']['copyCard'], 'callback_data' => "copycart"],
            ],
            [
                ['text' => $cronteststatustext, 'callback_data' => "editstsuts-crontest-{$status_cron['test']}"],
                ['text' => $textbotlang['keyboard']['cronTest'], 'callback_data' => "none"],
            ],
            [
                ['text' => $cronuptime_nodestatustext, 'callback_data' => "editstsuts-uptime_node-{$status_cron['uptime_node']}"],
                ['text' => $textbotlang['keyboard']['nodeUptime'], 'callback_data' => "none"],
            ],
            [
                ['text' => $cronuptime_panelstatustext, 'callback_data' => "editstsuts-uptime_panel-{$status_cron['uptime_panel']}"],
                ['text' => $textbotlang['keyboard']['panelUptime'], 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['keyboard']['timeAlert'], 'callback_data' => "settimecornday"],
                ['text' => $crondaystatustext, 'callback_data' => "editstsuts-cronday-{$status_cron['day']}"],
                ['text' => $textbotlang['keyboard']['cronTime'], 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['keyboard']['firstConnectTime'], 'callback_data' => "setting_on_holdcron"],
                ['text' => $cronon_holdtext, 'callback_data' => "editstsuts-on_hold-{$status_cron['on_hold']}"],
                ['text' => $textbotlang['keyboard']['cronFirstConnection'], 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['keyboard']['volumeAlert'], 'callback_data' => "settimecornvolume"],
                ['text' => $cronvolumestatustext, 'callback_data' => "editstsuts-cronvolume-{$status_cron['volume']}"],
                ['text' => $textbotlang['keyboard']['cronVolume'], 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['keyboard']['deleteTime'], 'callback_data' => "settimecornremove"],
                ['text' => $cronremovestatustext, 'callback_data' => "editstsuts-notifremove-{$status_cron['remove']}"],
                ['text' => $textbotlang['keyboard']['cronDelete'], 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['keyboard']['deleteTime'], 'callback_data' => "settimecornremovevolume"],
                ['text' => $cronremovevolumestatustext, 'callback_data' => "editstsuts-notifremove_volume-{$status_cron['remove_volume']}"],
                ['text' => $textbotlang['keyboard']['cronDeleteVolume'], 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "linkappsetting"],
                ['text' => $btnstatuslinkapp, 'callback_data' => "editstsuts-linkappstatus-{$setting['linkappstatus']}"],
                ['text' => $textbotlang['keyboard']['appDownloadLinkAlt'], 'callback_data' => "linkappstatus"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "scoresetting"],
                ['text' => $score, 'callback_data' => "editstsuts-score-{$setting['scorestatus']}"],
                ['text' => $textbotlang['keyboard']['nightLottery'], 'callback_data' => "score"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "gradonhshans"],
                ['text' => $wheel_luck, 'callback_data' => "editstsuts-wheel_luck-{$setting['wheelـluck']}"],
                ['text' => $textbotlang['keyboard']['wheelOfLuck'], 'callback_data' => "wheel_luck"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "settingaffiliatesf"],
                ['text' => $refralstatus, 'callback_data' => "editstsuts-affiliatesstatus-{$setting['affiliatesstatus']}"],
                ['text' => $textbotlang['keyboard']['affiliateGift'], 'callback_data' => "affiliatesstatus"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "changeloclimit"],
                ['text' => $statuslimitchangeloc, 'callback_data' => "editstsuts-changeloc-{$setting['statuslimitchangeloc']}"],
                ['text' => $textbotlang['keyboard']['locationChangeLimit'], 'callback_data' => "changeloc"],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['botTitle'], $Bot_Status);
} elseif ($text == $textbotlang['keyboard']['botReports'] && $adminrulecheck['rule'] == "administrator") {
    $textreports = sprintf($textbotlang['Admin']['Channel']['askReportGroupId'], $setting['Channel_Report']);
    sendmessage($from_id, $textreports, $backadmin, 'HTML');
    step('addchannelid', $from_id);
} elseif ($user['step'] == "addchannelid") {
    $outputcheck = sendmessage($text, $textbotlang['Admin']['Channel']['testChannel'], null, 'HTML');
    if (!$outputcheck['ok']) {
        $texterror = sprintf($textbotlang['Admin']['Channel']['connectionFailed'], $outputcheck['description']);
        sendmessage($from_id, $texterror, null, 'HTML');
        return;
    }
    if ($outputcheck['result']['chat']['is_forum'] == false) {
        $texterror = $textbotlang['Admin']['Channel']['notForumGroup'];
        sendmessage($from_id, $texterror, null, 'HTML');
        return;
    }
    $createForumTopic = telegram('createForumTopic', [
        'chat_id' => $text,
        'name' => $textbotlang['Admin']['report']['btnPurchaseReports']
    ]);
    if (!$createForumTopic['ok']) {
        $texterror = $textbotlang['Admin']['Channel']['botNotGroupAdmin'];
        sendmessage($from_id, $texterror, null, 'HTML');
        return;
    }
    if ($buyreport != $createForumTopic['result']['message_thread_id']) {
        update("topicid", "idreport", $createForumTopic['result']['message_thread_id'], "report", "buyreport");
    }
    $createForumTopic = telegram('createForumTopic', [
        'chat_id' => $text,
        'name' => $textbotlang['Admin']['report']['btnServicePurchase']
    ]);
    if (!$createForumTopic['ok']) {
        $texterror = $textbotlang['Admin']['Channel']['botNotGroupAdmin'];
        sendmessage($from_id, $texterror, null, 'HTML');
        return;
    }
    if ($otherservice != $createForumTopic['result']['message_thread_id']) {
        update("topicid", "idreport", $createForumTopic['result']['message_thread_id'], "report", "otherservice");
    }
    $createForumTopic = telegram('createForumTopic', [
        'chat_id' => $text,
        'name' => $textbotlang['Admin']['report']['btnTestAccount']
    ]);
    if (!$createForumTopic['ok']) {
        $texterror = $textbotlang['Admin']['Channel']['botNotGroupAdmin'];
        sendmessage($from_id, $texterror, null, 'HTML');
        return;
    }
    if ($reporttest != $createForumTopic['result']['message_thread_id']) {
        update("topicid", "idreport", $createForumTopic['result']['message_thread_id'], "report", "reporttest");
    }
    $createForumTopic = telegram('createForumTopic', [
        'chat_id' => $text,
        'name' => $textbotlang['Admin']['report']['btnOther']
    ]);
    if (!$createForumTopic['ok']) {
        $texterror = $textbotlang['Admin']['Channel']['botNotGroupAdmin'];
        sendmessage($from_id, $texterror, null, 'HTML');
        return;
    }
    if ($otherreport != $createForumTopic['result']['message_thread_id']) {
        update("topicid", "idreport", $createForumTopic['result']['message_thread_id'], "report", "otherreport");
    }
    $createForumTopic = telegram('createForumTopic', [
        'chat_id' => $text,
        'name' => $textbotlang['Admin']['report']['btnErrors']
    ]);
    if (!$createForumTopic['ok']) {
        $texterror = $textbotlang['Admin']['Channel']['botNotGroupAdmin'];
        sendmessage($from_id, $texterror, null, 'HTML');
        return;
    }
    if ($errorreport != $createForumTopic['result']['message_thread_id']) {
        update("topicid", "idreport", $createForumTopic['result']['message_thread_id'], "report", "errorreport");
    }
    $createForumTopic = telegram('createForumTopic', [
        'chat_id' => $text,
        'name' => $textbotlang['Admin']['report']['btnFinancial']
    ]);
    if (!$createForumTopic['ok']) {
        $texterror = $textbotlang['Admin']['Channel']['botNotGroupAdmin'];
        sendmessage($from_id, $texterror, null, 'HTML');
        return;
    }

    if ($paymentreports != $createForumTopic['result']['message_thread_id']) {
        update("topicid", "idreport", $createForumTopic['result']['message_thread_id'], "report", "paymentreport");
    }
    $createForumTopic = telegram('createForumTopic', [
        'chat_id' => $text,
        'name' => $textbotlang['Admin']['affiliates']['titleTopic']
    ]);
    if (!$createForumTopic['ok']) {
        $texterror = $textbotlang['Admin']['Channel']['botNotGroupAdmin'];
        sendmessage($from_id, $texterror, null, 'HTML');
        return;
    }

    if ($porsantreport != $createForumTopic['result']['message_thread_id']) {
        update("topicid", "idreport", $createForumTopic['result']['message_thread_id'], "report", "porsantreport");
    }
    $createForumTopic = telegram('createForumTopic', [
        'chat_id' => $text,
        'name' => $textbotlang['Admin']['report']['reportNight']
    ]);
    if (!$createForumTopic['ok']) {
        $texterror = $textbotlang['Admin']['Channel']['botNotGroupAdmin'];
        sendmessage($from_id, $texterror, null, 'HTML');
        return;
    }

    if ($reportnight != $createForumTopic['result']['message_thread_id']) {
        update("topicid", "idreport", $createForumTopic['result']['message_thread_id'], "report", "reportnight");
    }
    $createForumTopic = telegram('createForumTopic', [
        'chat_id' => $text,
        'name' => $textbotlang['Admin']['report']['reportCron']
    ]);
    if (!$createForumTopic['ok']) {
        $texterror = $textbotlang['Admin']['Channel']['botNotGroupAdmin'];
        sendmessage($from_id, $texterror, null, 'HTML');
        return;
    }

    if ($reportcron != $createForumTopic['result']['message_thread_id']) {
        update("topicid", "idreport", $createForumTopic['result']['message_thread_id'], "report", "reportcron");
    }
    $createForumTopic = telegram('createForumTopic', [
        'chat_id' => $text,
        'name' => $textbotlang['Admin']['report']['btnBackup']
    ]);
    if (!$createForumTopic['ok']) {
        $texterror = $textbotlang['Admin']['Channel']['botNotGroupAdmin'];
        sendmessage($from_id, $texterror, null, 'HTML');
        return;
    }

    if ($reportbackup != $createForumTopic['result']['message_thread_id']) {
        update("topicid", "idreport", $createForumTopic['result']['message_thread_id'], "report", "backupfile");
    }
    sendmessage($from_id, $textbotlang['Admin']['Channel']['setChannelReport'], $setting_panel, 'HTML');
    update("setting", "Channel_Report", $text);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['shopSettings'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $shopkeyboard, 'HTML');
} elseif ($text == $textbotlang['keyboard']['addProduct'] && $adminrulecheck['rule'] == "administrator") {
    $locationproduct = select("marzban_panel", "*", null, null, "count");
    if ($locationproduct == 0) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['nullPanelAdmin'], null, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Product']['addProductStepOne'], $backadmin, 'HTML');
    step('get_limit', $from_id);
} elseif ($user['step'] == "get_limit") {
    if (strlen($text) > 150) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['nameTooLong'], $backadmin, 'HTML');
        return;
    }
    if (rowExists("product", "name_product", $text)) {
        sendmessage($from_id, sprintf($textbotlang['Admin']['Product']['nameExists'], $text), $backadmin, 'HTML');
        return;
    }
    savedata("clear", "name_product", $text);
    sendmessage($from_id, $textbotlang['Admin']['agent']['setAgentProduct'], $backadmin, 'HTML');
    step('get_agent', $from_id);
} elseif ($user['step'] == "get_agent") {
    $agent = ["n", "f", "n2"];
    if (!in_array($text, $agent)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    savedata("save", "agent", $text);
    sendmessage($from_id, $textbotlang['Admin']['Product']['serviceLocation'], $json_list_marzban_panel, 'HTML');
    step('get_location', $from_id);
} elseif ($user['step'] == "get_location") {
    if ($text != '/all' && !rowExists("marzban_panel", "name_panel", $text)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['invalidSelection'], null, 'HTML');
        return;
    }
    savedata("save", "Location", $text);
    if ($setting['statuscategorygenral'] == "oncategorys") {
        sendmessage($from_id, $textbotlang['Admin']['category']['askName'], KeyboardCategoryadmin(), 'HTML');
        step("getcategory", $from_id);
        return;
    }
    $panel = select("marzban_panel", "*", "name_panel", $text, "select");
    if ($panel['type'] == "Manualsale") {
        savedata("save", "Service_time", "0");
        savedata("save", "Volume_constraint", "0");
        sendmessage($from_id, $textbotlang['Admin']['Product']['getPrice'], $backadmin, 'HTML');
        step('gettimereset', $from_id);
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Product']['getLimit'], $backadmin, 'HTML');
    step('get_time', $from_id);
} elseif ($user['step'] == "getcategory") {
    $category = select("category", "*", "remark", $text, "count");
    if ($category == 0) {
        sendmessage($from_id, $textbotlang['Admin']['category']['notFoundAddFirst'], KeyboardCategoryadmin(), 'HTML');
        return;
    }
    savedata("save", "category", $text);
    $userdata = json_decode($user['Processing_value'], true);
    $panel = select("marzban_panel", "*", "name_panel", $userdata['Location'], "select");
    if ($panel['type'] == "Manualsale") {
        savedata("save", "Service_time", "0");
        savedata("save", "Volume_constraint", "0");
        sendmessage($from_id, $textbotlang['Admin']['Product']['getPrice'], $backadmin, 'HTML');
        step('gettimereset', $from_id);
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Product']['getLimit'], $backadmin, 'HTML');
    step('get_time', $from_id);
} elseif ($user['step'] == "get_time") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidVolume'], $backadmin, 'HTML');
        return;
    }
    savedata("save", "Volume_constraint", $text);
    sendmessage($from_id, $textbotlang['Admin']['Product']['getTime'], $backadmin, 'HTML');
    step('get_price', $from_id);
} elseif ($user['step'] == "get_price") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidTime'], $backadmin, 'HTML');
        return;
    }
    savedata("save", "Service_time", $text);
    sendmessage($from_id, $textbotlang['Admin']['Product']['getPrice'], $backadmin, 'HTML');
    step('gettimereset', $from_id);
} elseif ($user['step'] == "gettimereset") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['invalidPrice'], $backadmin, 'HTML');
        return;
    }
    savedata("save", "price_product", $text);
    $userdata = json_decode($user['Processing_value'], true);
    $panel = select("marzban_panel", "*", "name_panel", $userdata['Location'], "select");
    if ($panel['type'] == "marzban" || $panel['type'] == "marzneshin") {
        sendmessage($from_id, $textbotlang['Admin']['Product']['getTimeReset'], $keyboardtimereset, 'HTML');
        step('getnote', $from_id);
        return;
    }
    savedata("save", "data_limit_reset", "no_reset");
    sendmessage($from_id, $textbotlang['Admin']['Product']['askNote'], $backadmin, 'HTML');
    step('endstep', $from_id);
} elseif ($user['step'] == "getnote") {
    savedata("save", "data_limit_reset", $text);
    sendmessage($from_id, $textbotlang['Admin']['Product']['askNote2'], $backadmin, 'HTML');
    step('endstep', $from_id);
} elseif ($user['step'] == "endstep") {
    $userdata = json_decode($user['Processing_value'], true);
    $randomString = bin2hex(random_bytes(2));
    $varhide_panel = "{}";
    if (!isset($userdata['category']))
        $userdata['category'] = null;
    $stmt = $pdo->prepare("INSERT IGNORE INTO product (name_product,code_product,price_product,Volume_constraint,Service_time,Location,agent,data_limit_reset,note,category,hide_panel,one_buy_status) VALUES (:name_product,:code_product,:price_product,:Volume_constraint,:Service_time,:Location,:agent,:data_limit_reset,:note,:category,:hide_panel,'0')");
    $stmt->bindParam(':name_product', $userdata['name_product']);
    $stmt->bindParam(':code_product', $randomString);
    $stmt->bindParam(':price_product', $userdata['price_product']);
    $stmt->bindParam(':Volume_constraint', $userdata['Volume_constraint']);
    $stmt->bindParam(':Service_time', $userdata['Service_time']);
    $stmt->bindParam(':Location', $userdata['Location']);
    $stmt->bindParam(':agent', $userdata['agent']);
    $stmt->bindParam(':data_limit_reset', $userdata['data_limit_reset']);
    $stmt->bindParam(':category', $userdata['category'], PDO::PARAM_STR);
    $stmt->bindParam(':note', $text, PDO::PARAM_STR);
    $stmt->bindParam(':hide_panel', $varhide_panel, PDO::PARAM_STR);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Product']['saveProduct'], $shopkeyboard, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['adminSection'] && $adminrulecheck['rule'] == "administrator") {
    $list_admin = select("admin", "*", null, null, "fetchAll");
    $keyboardadmin = ['inline_keyboard' => []];
    foreach ($list_admin as $admin) {
        $adminId = isset($admin['id_admin']) ? trim($admin['id_admin']) : '';
        if ($adminId === '') {
            continue;
        }
        $keyboardadmin['inline_keyboard'][] = [
            ['text' => "❌", 'callback_data' => "removeadmin_" . $adminId],
            ['text' => $adminId, 'callback_data' => "adminlist"],
        ];
    }
    $keyboardadmin['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['addAdmin'], 'callback_data' => "addnewadmin"],
    ];
    $keyboardadmin = json_encode($keyboardadmin);
    sendmessage($from_id, $textbotlang['Admin']['manageadmin']['listAndDelete'], $keyboardadmin, 'HTML');
} elseif ($text == $textbotlang['keyboard']['generalSettings'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $setting_panel, 'HTML');
} elseif ($text == $textbotlang['keyboard']['supportSection'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $supportcenter, 'HTML');
} elseif (preg_match('/Confirm_pay_(\w+)/', $datain, $dataget) && ($adminrulecheck['rule'] == "administrator" || $adminrulecheck['rule'] == "Seller")) {
    $order_id = $dataget[1];
    $Payment_report = select("Payment_report", "*", "id_order", $order_id, "select");
    $Confirm_pay = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['confirmed'], 'callback_data' => "confirmpaid"],
            ],
            [
                ['text' => $textbotlang['keyboard']['userManagementBtn'], 'callback_data' => "manageuser_" . $Payment_report['id_user']],
            ]
        ]
    ]);
    if ($Payment_report == false) {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['keyboard']['transactionDeleted'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
        return;
    }
    $sql = "SELECT * FROM Payment_report WHERE id_user = '{$Payment_report['id_user']}' AND payment_Status != 'paid' AND payment_Status != 'Unpaid' AND payment_Status != 'expire' AND payment_Status != 'reject' AND  (id_invoice  LIKE CONCAT('%','getconfigafterpay', '%') OR id_invoice  LIKE CONCAT('%','getextenduser', '%') OR id_invoice  LIKE CONCAT('%','getextravolumeuser', '%') OR id_invoice  LIKE CONCAT('%','getextratimeuser', '%'))";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $countpay = $stmt->rowCount();
    $typepay = explode('|', $Payment_report['id_invoice']);
    if ($countpay > 0 and !in_array($typepay[0], ['getconfigafterpay', 'getextenduser', 'getextravolumeuser', 'getextratimeuser'])) {
        sendmessage($from_id, $textbotlang['Admin']['Payment']['reviewReceiptsFirst'], null, 'HTML');
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
        $textconfrom = sprintf($textbotlang['Admin']['Payment']['approvedByOther'], $Balance_id['id'], $Payment_report['id_order'], $Balance_id['username'], $Balance_id['Balance'], $format_price_cart);
        Editmessagetext($from_id, $message_id, $textconfrom, $Confirm_pay);
        return;
    }
    $claim = $pdo->prepare("UPDATE Payment_report SET payment_Status = 'paid' WHERE id_order = ? AND payment_Status NOT IN ('paid', 'reject')");
    $claim->execute([$order_id]);
    clearSelectCache('Payment_report');
    if ($claim->rowCount() === 0) {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['Admin']['Payment']['reviewedPayment'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
        $textconfrom = sprintf($textbotlang['Admin']['Payment']['approvedByOther'], $Balance_id['id'], $Payment_report['id_order'], $Balance_id['username'], $Balance_id['Balance'], $format_price_cart);
        Editmessagetext($from_id, $message_id, $textconfrom, $Confirm_pay);
        return;
    }
    DirectPayment($order_id);
    $pricecashback = select("PaySetting", "ValuePay", "NamePay", "chashbackcart", "select")['ValuePay'];
    $Balance_id = select("user", "*", "id", $Payment_report['id_user'], "select");
    if ($pricecashback != "0") {
        $result = ($Payment_report['price'] * $pricecashback) / 100;
        $Balance_confrim = intval($Balance_id['Balance']) + $result;
        update("user", "Balance", $Balance_confrim, "id", $Balance_id['id']);
        $pricecashback = number_format($pricecashback);
        $text_report = sprintf($textbotlang['users']['Balance']['giftDepositAlt'], $result);
        sendmessage($Balance_id['id'], $text_report, null, 'HTML');
    }
    $Payment_report['price'] = number_format($Payment_report['price']);
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['paymentApproved'], $Payment_report['Payment_Method'], $from_id, $Payment_report['price'], $Payment_report['id_user'], $Balance_id['username'], $order_id);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $paymentreports,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
    update("Payment_report", "payment_Status", "paid", "id_order", $Payment_report['id_order']);
    update("user", "Processing_value_one", "none", "id", $Balance_id['id']);
    update("user", "Processing_value_tow", "none", "id", $Balance_id['id']);
    update("user", "Processing_value_four", "none", "id", $Balance_id['id']);
} elseif (preg_match('/reject_pay_(\w+)/', $datain, $datagetr) && ($adminrulecheck['rule'] == "administrator" || $adminrulecheck['rule'] == "Seller")) {
    $id_order = $datagetr[1];
    $Payment_report = select("Payment_report", "*", "id_order", $id_order, "select");
    if ($Payment_report == false) {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['keyboard']['transactionDeleted'],
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
    $text_reject = sprintf($textbotlang['users']['Balance']['rejectedNotice'], $text, $user['Processing_value_one']);
    sendmessage($from_id, $textbotlang['Admin']['Payment']['rejected'], $keyboardadmin, 'HTML');
    sendmessage($user['Processing_value'], $text_reject, null, 'HTML');
    step('home', $from_id);
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['paymentRejected'], $Payment_report['Payment_Method'], $from_id, $username, $Payment_report['price'], $text, $Payment_report['id_user']);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $paymentreports,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
} elseif ($text == $textbotlang['keyboard']['deleteProduct'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Product']['removeLocation'], $json_list_marzban_panel, 'HTML');
    step('selectloc', $from_id);
} elseif ($user['step'] == "selectloc") {
    update("user", "Processing_value", $text, "id", $from_id);
    step('remove-product', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Product']['selectRemoveProduct'], $json_list_product_list_admin, 'HTML');
} elseif ($user['step'] == "remove-product") {
    if (!rowExists("product", "name_product", $text)) {
        sendmessage($from_id, $textbotlang['users']['sell']['errorProduct'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM product WHERE name_product =:name_product AND (Location= :Location or Location= '/all')");
    $stmt->bindParam(':name_product', $text, PDO::PARAM_STR);
    $stmt->bindParam(':Location', $user['Processing_value'], PDO::PARAM_STR);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Product']['removedProduct'], $shopkeyboard, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['editProduct'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Product']['removeLocation'], $list_marzban_panel_edit_product, 'HTML');
} elseif (preg_match('/locationedit_(\w+)/', $datain, $dataget)) {
    $location = $dataget[1];
    $location = $location == "all" ? "/all" : $location;
    update("user", "Processing_value_one", $location, "id", $from_id);
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['normalUser'], 'callback_data' => 'typeagenteditproduct_f'],
            ],
            [
                ['text' => $textbotlang['keyboard']['advancedAgent'], 'callback_data' => 'typeagenteditproduct_n2'],
                ['text' => $textbotlang['keyboard']['normalAgent'], 'callback_data' => 'typeagenteditproduct_n'],
            ],
            [
                ['text' => $textbotlang['keyboard']['back'], 'callback_data' => "admin"]
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['agent']['selectUserType'], $Response);
} elseif (preg_match('/^typeagenteditproduct_(\w+)/', $datain, $dataget)) {
    $typeagent = $dataget[1];
    update("user", "Processing_value_tow", $typeagent, "id", $from_id);
    $product = [];
    $panel = select("marzban_panel", "*", "code_panel", $user['Processing_value_one'], "select");
    $getdataproduct = $pdo->prepare("SELECT * FROM product WHERE (Location = ? or Location = '/all') AND agent = ?");
    $getdataproduct->bindValue(1, $panel['name_panel'], PDO::PARAM_STR);
    $getdataproduct->bindValue(2, $typeagent, PDO::PARAM_STR);
    $getdataproduct->execute();
    $list_product = [
        'inline_keyboard' => [],
    ];
    if (isset($getdataproduct)) {
        while ($row = ($getdataproduct)->fetch(PDO::FETCH_ASSOC)) {
            $list_product['inline_keyboard'][] = [
                ['text' => $row['name_product'], 'callback_data' => "productedit_" . $row['id']]
            ];
        }
        $list_product['inline_keyboard'][] = [
            ['text' => $textbotlang['keyboard']['backToPrevMenu2'], 'callback_data' => "locationedit_" . $user['Processing_value_one']],
        ];

        $json_list_product_list_admin = json_encode($list_product);
    }
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Product']['selectEditProduct'], $json_list_product_list_admin);
} elseif (preg_match('/^productedit_(\w+)/', $datain, $dataget)) {
    $id_product = $dataget[1];
    deletemessage($from_id, $message_id);
    update("user", "Processing_value", $id_product, "id", $from_id);
    $panel = select("marzban_panel", "*", "code_panel", $user['Processing_value_one'], "select");
    $__q36 = $pdo->prepare("SELECT * FROM product WHERE id = ?  AND agent = ? AND (Location = ? OR Location = '/all') LIMIT 1");
    $__q36->bindValue(1, $id_product, PDO::PARAM_STR);
    $__q36->bindValue(2, $user['Processing_value_tow'], PDO::PARAM_STR);
    $__q36->bindValue(3, $panel['name_panel'], PDO::PARAM_STR);
    $__q36->execute();
    $info_product = $__q36->fetch(PDO::FETCH_ASSOC);
    $count_invoice = select("invoice", "*", "name_product", $info_product['name_product'], "count");
    $infoproduct = sprintf($textbotlang['Admin']['Product']['editSummary'], $info_product['name_product'], $info_product['price_product'], $info_product['Volume_constraint'], $info_product['Location'], $info_product['Service_time'], $info_product['agent'], $info_product['data_limit_reset'], $info_product['note'], $info_product['category'], $count_invoice);
    sendmessage($from_id, $infoproduct, $change_product, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['price'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Product']['askNewPrice'], $backadmin, 'HTML');
    step('change_price', $from_id);
} elseif ($user['step'] == "change_price") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['invalidPrice'], $backadmin, 'HTML');
        return;
    }
    $panel = select("marzban_panel", "*", "code_panel", $user['Processing_value_one'], "select");
    $stmt = $pdo->prepare("UPDATE product SET price_product = :price_product WHERE id = :name_product AND (Location = :Location OR Location = '/all') AND agent = :agent");
    $stmt->bindParam(':price_product', $text);
    $stmt->bindParam(':name_product', $user['Processing_value']);
    $stmt->bindParam(':Location', $panel['name_panel']);
    $stmt->bindParam(':agent', $user['Processing_value_tow']);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Product']['priceUpdated'], $shopkeyboard, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['note'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Product']['askNewNote'], $backadmin, 'HTML');
    step('change_note', $from_id);
} elseif ($user['step'] == "change_note") {
    $panel = select("marzban_panel", "*", "code_panel", $user['Processing_value_one'], "select");
    $stmt = $pdo->prepare("UPDATE product SET note = :notes WHERE id = :name_product AND (Location = :Location OR Location = '/all') AND agent = :agent");
    $stmt->bindParam(':notes', $text);
    $stmt->bindParam(':name_product', $user['Processing_value']);
    $stmt->bindParam(':Location', $panel['name_panel']);
    $stmt->bindParam(':agent', $user['Processing_value_tow']);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Product']['noteUpdated'], $shopkeyboard, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['category'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Product']['selectNewCategory'], KeyboardCategoryadmin(), 'HTML');
    step('change_categroy', $from_id);
} elseif ($user['step'] == "change_categroy") {
    $category = select("category", "*", "remark", $text, "count");
    if ($category == 0) {
        sendmessage($from_id, $textbotlang['Admin']['category']['notFoundAddFirst2'], KeyboardCategoryadmin(), 'HTML');
        return;
    }
    $panel = select("marzban_panel", "*", "code_panel", $user['Processing_value_one'], "select");
    $stmt = $pdo->prepare("UPDATE product SET category = :categroy WHERE id = :name_product AND (Location = :Location OR Location = '/all') AND agent = :agent");
    $stmt->bindParam(':categroy', $text);
    $stmt->bindParam(':name_product', $user['Processing_value']);
    $stmt->bindParam(':Location', $panel['name_panel']);
    $stmt->bindParam(':agent', $user['Processing_value_tow']);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Product']['categoryUpdated'], $shopkeyboard, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['productName'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Product']['askNewName'], $backadmin, 'HTML');
    step('change_name', $from_id);
} elseif ($user['step'] == "change_name") {
    if (strlen($text) > 150) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['nameTooLong'], $backadmin, 'HTML');
        return;
    }
    if (rowExists("product", "name_product", $text)) {
        sendmessage($from_id, sprintf($textbotlang['Admin']['Product']['nameExists2'], $text), $backadmin, 'HTML');
        return;
    }
    $panel = select("marzban_panel", "*", "code_panel", $user['Processing_value_one'], "select");
    $stmt = $pdo->prepare("UPDATE product SET name_product = :name_products WHERE id = :name_product AND (Location = :Location OR Location = '/all') AND agent = :agent");
    $stmt->bindParam(':name_products', $text);
    $stmt->bindParam(':name_product', $user['Processing_value']);
    $stmt->bindParam(':Location', $panel['name_panel']);
    $stmt->bindParam(':agent', $user['Processing_value_tow']);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Product']['nameUpdated'], $change_product, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['userType'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Product']['askNewUserType'], $backadmin, 'HTML');
    step('change_type_agent', $from_id);
} elseif ($user['step'] == "change_type_agent") {
    if (!in_array($text, ['f', 'n', 'n2'])) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['invalidUserGroup'], null, 'HTML');
        return;
    }
    $panel = select("marzban_panel", "*", "code_panel", $user['Processing_value_one'], "select");
    $stmt = $pdo->prepare("UPDATE product SET agent = :agents WHERE id = :name_product AND (Location = :Location OR Location = '/all') AND agent = :agent");
    $stmt->bindParam(':agents', $text);
    $stmt->bindParam(':name_product', $user['Processing_value']);
    $stmt->bindParam(':Location', $panel['name_panel']);
    $stmt->bindParam(':agent', $user['Processing_value_tow']);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Product']['nameUpdated'], $shopkeyboard, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['volumeResetType'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Product']['askVolumeResetType'], $keyboardtimereset, 'HTML');
    step('change_reset_data', $from_id);
} elseif ($user['step'] == "change_reset_data") {
    $panel = select("marzban_panel", "*", "code_panel", $user['Processing_value_one'], "select");
    $stmt = $pdo->prepare("UPDATE product SET data_limit_reset = :data_limit_reset WHERE id = :name_product AND (Location = :Location OR Location = '/all') AND agent = :agent");
    $stmt->bindParam(':data_limit_reset', $text);
    $stmt->bindParam(':name_product', $user['Processing_value']);
    $stmt->bindParam(':Location', $panel['name_panel']);
    $stmt->bindParam(':agent', $user['Processing_value_tow']);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Product']['nameUpdated'], $shopkeyboard, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['productLocation'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Product']['selectNewLocation'], $json_list_marzban_panel, 'HTML');
    step('change_loc_data', $from_id);
} elseif ($user['step'] == "change_loc_data") {
    if ($text == "/all") {
        sendmessage($from_id, $textbotlang['Admin']['Product']['cannotChangeToAll'], $shopkeyboard, 'HTML');
        return;
    }
    $product = select("product", "*", "name_product", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "code_panel", $user['Processing_value_one'], "select");
    $stmt = $pdo->prepare("UPDATE product SET Location = :Location2 WHERE id = :name_product AND (Location = :Location OR Location = '/all') AND agent = :agent");
    $stmt->bindParam(':Location2', $text);
    $stmt->bindParam(':name_product', $user['Processing_value']);
    $stmt->bindParam(':Location', $panel['name_panel']);
    $stmt->bindParam(':agent', $user['Processing_value_tow']);
    $stmt->execute();
    $stmt = $pdo->prepare("UPDATE invoice SET Service_location = :Service_location WHERE name_product = :name_product AND Service_location = :Location ");
    $stmt->bindParam(':Service_location', $text);
    $stmt->bindParam(':name_product', $product['name_product']);
    $stmt->bindParam(':Location', $panel['name_panel']);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Product']['locationUpdated'], $shopkeyboard, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['volume'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Product']['askNewVolume'], $backadmin, 'HTML');
    step('change_val', $from_id);
} elseif ($user['step'] == "change_val") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidVolume'], $backadmin, 'HTML');
        return;
    }
    $product = select("product", "*", "id", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "code_panel", $user['Processing_value_one']);
    $stmt = $pdo->prepare("UPDATE product SET Volume_constraint = :Volume_constraint WHERE id = :name_product AND (Location = :Location OR Location = '/all') AND agent = :agent");
    $stmt->bindParam(':Volume_constraint', $text);
    $stmt->bindParam(':name_product', $product['id']);
    $stmt->bindParam(':Location', $panel['name_panel']);
    $stmt->bindParam(':agent', $user['Processing_value_tow']);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Product']['volumeUpdated'], $shopkeyboard, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['time'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Product']['newTime'], $backadmin, 'HTML');
    step('change_time', $from_id);
} elseif ($user['step'] == "change_time") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidTime'], $backadmin, 'HTML');
        return;
    }
    $panel = select("marzban_panel", "*", "code_panel", $user['Processing_value_one'], "select");
    $stmt = $pdo->prepare("UPDATE product SET Service_time = :Service_time WHERE id = :id_product AND (Location = :Location OR Location = '/all') AND agent = :agent");
    $stmt->bindParam(':Service_time', $text);
    $stmt->bindParam(':id_product', $user['Processing_value']);
    $stmt->bindParam(':Location', $panel['name_panel']);
    $stmt->bindParam(':agent', $user['Processing_value_tow']);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Product']['timeUpdated'], $shopkeyboard, 'HTML');
    step('home', $from_id);
} elseif ($datain == "balanceaddall") {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['addAllBalance'], $backadmin, 'HTML');
    step('add_Balance_all', $from_id);
} elseif ($user['step'] == "add_Balance_all") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['invalidPrice'], $backadmin, 'HTML');
        return;
    }
    step("home", $from_id);
    savedata("clear", "price", $text);
    $keyboardagent = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['allUsers'], 'callback_data' => 'typebalanceall_all'],
            ],
            [
                ['text' => $textbotlang['keyboard']['usersGroupF'], 'callback_data' => 'typebalanceall_f'],
                ['text' => $textbotlang['keyboard']['usersGroupN'], 'callback_data' => 'typebalanceall_nl'],
                ['text' => $textbotlang['keyboard']['usersGroupN2'], 'callback_data' => 'typebalanceall_n2'],
            ],
            [
                ['text' => $textbotlang['keyboard']['backToMain'], 'callback_data' => 'backuser'],
            ]
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askUserGroup'], $keyboardagent, 'HTML');
} elseif (preg_match('/typebalanceall_(\w+)/', $datain, $dataget)) {
    $typeagent = $dataget[1];
    savedata("save", "agent", $typeagent);
    $keyboardtypeuser = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['allUsers'], 'callback_data' => 'typecustomer_all'],
            ],
            [
                ['text' => $textbotlang['keyboard']['usersBought'], 'callback_data' => 'typecustomer_customer'],
            ],
            [
                ['text' => $textbotlang['keyboard']['usersNotBought'], 'callback_data' => 'typecustomer_notcustomer'],
            ],
            [
                ['text' => $textbotlang['keyboard']['backToMain'], 'callback_data' => 'backuser'],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Balance']['askTargetUsers'], $keyboardtypeuser);
} elseif (preg_match('/typecustomer_(\w+)/', $datain, $dataget)) {
    $typecustomer = $dataget[1];
    savedata("save", "typecustomer", $typecustomer);
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askNotify'], $backadmin, 'HTML');
    step("getmeesagestatus", $from_id);
} elseif ($user['step'] == "getmeesagestatus") {
    $userdata = json_decode($user['Processing_value'], true);
    sendmessage($from_id, $textbotlang['Admin']['Balance']['addBalanceUsers'], $keyboardadmin, 'HTML');
    if ($userdata['agent'] == "all") {
        if ($userdata['typecustomer'] == "customer") {
            $query_where = " WHERE EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id)";
        } elseif ($userdata['typecustomer'] == "notcustomer") {
            $query_where = " WHERE NOT EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id)";
        } else {
            $query_where = "";
        }
        $query_params = [];
    } else {
        if ($userdata['typecustomer'] == "customer") {
            $query_where = " WHERE u.agent = :agent AND EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id)";
        } elseif ($userdata['typecustomer'] == "notcustomer") {
            $query_where = " WHERE u.agent = :agent AND NOT EXISTS ( SELECT 1 FROM invoice i WHERE i.id_user = u.id)";
        } else {
            $query_where = " WHERE u.agent = :agent";
        }
        $query_params = [':agent' => $userdata['agent']];
    }
    $stmt = $pdo->prepare("SELECT u.id FROM user u" . $query_where);
    $stmt->execute($query_params);
    $Balance_user = $stmt->fetchAll();
    $stmt = $pdo->prepare("UPDATE user as u SET Balance = Balance + :price" . $query_where);
    $stmt->execute($query_params + [':price' => intval($userdata['price'])]);
    step('home', $from_id);
    if ($text == "1") {
        $cancelmessage = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['keyboard']['cancelOperation'], 'callback_data' => 'cancel_sendmessage'],
                ],
            ]
        ]);
        $textgift = sprintf($textbotlang['users']['Balance']['giftFromManagement'], $userdata['price']);
        $message_id = sendmessage($from_id, $textbotlang['Admin']['Balance']['operationStarted'], $cancelmessage, "html");
        $data = json_encode(array(
            "id_admin" => $from_id,
            'type' => "sendmessage",
            "id_message" => $message_id['result']['message_id'],
            "message" => $textgift,
            "pingmessage" => "no",
            "btnmessage" => "start"
        ));
        file_put_contents("cronbot/users.json", json_encode($Balance_user));
        file_put_contents('cronbot/info', $data);
    }
} elseif ($text == $textbotlang['Admin']['Balance']['btnDecrease']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['negativeBalance'], $backadmin, 'HTML');
    step('Negative_Balance', $from_id);
} elseif ($user['step'] == "Negative_Balance") {
    if (!rowExists("user", "id", $text)) {
        sendmessage($from_id, $textbotlang['Admin']['notUser'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['priceBalance'], $backadmin, 'HTML');
    update("user", "Processing_value", $text, "id", $from_id);
    step('get_price_Negative', $from_id);
} elseif ($user['step'] == "get_price_Negative") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['invalidPrice'], $backadmin, 'HTML');
        return;
    }
    if (intval($text) >= 100000000) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['maxAmountRial'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['negativeBalanceUser'], $keyboardadmin, 'HTML');
    $Balance_usersa = select("user", "*", "id", $user['Processing_value'], "select");
    $Balance_Low_userkam = $Balance_usersa['Balance'] - $text;
    update("user", "Balance", $Balance_Low_userkam, "id", $user['Processing_value']);
    $balances1 = number_format($text, 0);
    $Balance_user_afters = number_format(select("user", "*", "id", $user['Processing_value'], "select")['Balance']);
    $textkam = sprintf($textbotlang['users']['Balance']['deductedNotice'], $balances1);
    sendmessage($user['Processing_value'], $textkam, null, 'HTML');
    step('home', $from_id);
    if (strlen($setting['Channel_Report']) > 0) {
        $textaddbalance = sprintf($textbotlang['Admin']['reportgroup']['balanceDecreased'], $username, $from_id, $user['Processing_value'], $text, $Balance_user_afters);
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $paymentreports,
            'text' => $textaddbalance,
            'parse_mode' => "HTML"
        ]);
    }
} elseif ($datain == "searchuser") {
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['getIdUserUnblock'], $backadmin, 'HTML');
    step('show_info', $from_id);
} elseif ($user['step'] == "show_info" || preg_match('/manageuser_(\w+)/', $datain, $dataget) || preg_match('/updateinfouser_(\w+)/', $datain, $dataget) || strpos($text, "/user ") !== false || strpos($text, "/id ") !== false) {
    if ($user['step'] == "show_info") {
        $id_user = $text;
    } elseif (explode(" ", $text)[0] == "/user") {
        $id_user = explode(" ", $text)[1];
    } elseif (explode(" ", $text)[0] == "/id") {
        $id_user = explode(" ", $text)[1];
    } else {
        $id_user = $dataget[1];
    }
    if (!rowExists("user", "id", $id_user)) {
        sendmessage($from_id, $textbotlang['Admin']['notUser'], null, 'HTML');
        return;
    }
    $date = date("Y-m-d");
    $__q37 = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND id_user = ?");
    $__q37->bindValue(1, $id_user, PDO::PARAM_STR);
    $__q37->execute();
    $dayListSell = $__q37->fetch(PDO::FETCH_ASSOC);
    $__q38 = $pdo->prepare("SELECT SUM(price) FROM Payment_report WHERE payment_Status = 'paid' AND id_user = ? AND Payment_Method != 'low balance by admin'");
    $__q38->bindValue(1, $id_user, PDO::PARAM_STR);
    $__q38->execute();
    $balanceall = $__q38->fetch(PDO::FETCH_ASSOC);
    $__q39 = $pdo->prepare("SELECT SUM(price_product) FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND id_user = ?");
    $__q39->bindValue(1, $id_user, PDO::PARAM_STR);
    $__q39->execute();
    $subbuyuser = $__q39->fetch(PDO::FETCH_ASSOC);
    $invoicecount = select("invoice", '*', "id_user", $id_user, "count");
    if ($invoicecount == 0) {
        $sumvolume['SUM(Volume)'] = 0;
    } else {
        $__q40 = $pdo->prepare("SELECT SUM(Volume) FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND id_user = ? AND name_product != ?");
        $__q40->bindValue(1, $id_user, PDO::PARAM_STR);
        $__q40->bindValue(2, $textbotlang['common']['labels']['testServiceName'], PDO::PARAM_STR);
        $__q40->execute();
        $sumvolume = $__q40->fetch(PDO::FETCH_ASSOC);
    }
    $user = select("user", "*", "id", $id_user, "select");
    $roll_Status = [
        '1' => $textbotlang['Admin']['manageUser']['acceptedPhone'],
        '0' => $textbotlang['Admin']['manageUser']['failedPhone'],
    ][$user['roll_Status']];
    if ($subbuyuser['SUM(price_product)'] == null)
        $subbuyuser['SUM(price_product)'] = 0;
    $keyboardmanage = [
        'inline_keyboard' => [
            [['text' => $textbotlang['keyboard']['refreshInfoAlt'], 'callback_data' => "updateinfouser_" . $id_user],],
            [['text' => $textbotlang['Admin']['manageUser']['addBalanceUser'], 'callback_data' => "addbalanceuser_" . $id_user], ['text' => $textbotlang['Admin']['manageUser']['lowBalanceUser'], 'callback_data' => "lowbalanceuser_" . $id_user],],
            [['text' => $textbotlang['Admin']['manageUser']['banUserList'], 'callback_data' => "banuserlist_" . $id_user], ['text' => $textbotlang['Admin']['manageUser']['unbanUserList'], 'callback_data' => "unbanuserr_" . $id_user]],
            [['text' => $textbotlang['Admin']['manageUser']['addagent'], 'callback_data' => "addagent_" . $id_user], ['text' => $textbotlang['Admin']['manageUser']['removeagent'], 'callback_data' => "removeagent_" . $id_user]],
            [['text' => $textbotlang['Admin']['manageUser']['confirmNumber'], 'callback_data' => "confirmnumber_" . $id_user]],
            [['text' => $textbotlang['keyboard']['discountPercent'], 'callback_data' => "Percentlow_" . $id_user], ['text' => $textbotlang['keyboard']['sendMessageToUser'], 'callback_data' => "sendmessageuser_" . $id_user]],
            [['text' => $textbotlang['Admin']['manageUser']['viewOrderUser'], 'callback_data' => "vieworderuser_" . $id_user]],
            [['text' => $textbotlang['keyboard']['userAffiliates'], 'callback_data' => "affiliates-" . $id_user]],
            [['text' => $textbotlang['keyboard']['removeFromAffiliate'], 'callback_data' => "removeaffiliate-" . $id_user], ['text' => $textbotlang['keyboard']['deleteUserAffiliates'], 'callback_data' => "removeaffiliateuser-" . $id_user]],
            [['text' => $textbotlang['keyboard']['activateCard'], 'callback_data' => "showcarduser-" . $id_user]],
            [['text' => $textbotlang['keyboard']['authenticateUser'], 'callback_data' => "verify_" . $id_user], ['text' => $textbotlang['keyboard']['unauthUser'], 'callback_data' => "unverify-" . $id_user]],
            [['text' => $textbotlang['keyboard']['deactivateCard'], 'callback_data' => "carduserhide-" . $id_user]],
            [['text' => $textbotlang['keyboard']['addOrder'], 'callback_data' => "addordermanualـ" . $id_user], ['text' => $textbotlang['keyboard']['testAccountLimit'], 'callback_data' => "limitusertest_" . $id_user]],
            [['text' => $textbotlang['Admin']['manageUser']['viewPaymentUser'], 'callback_data' => "viewpaymentuser_" . $id_user], ['text' => $textbotlang['keyboard']['transferAccount'], 'callback_data' => "transferaccount_" . $id_user]],
            [['text' => $textbotlang['keyboard']['deactivateAccount'], 'callback_data' => "disableconfig-" . $id_user], ['text' => $textbotlang['keyboard']['activateAccount'], 'callback_data' => "activeconfig-" . $id_user]],
            [['text' => $textbotlang['keyboard']['verifyChannelMembership'], 'callback_data' => "confirmchannel-" . $id_user], ['text' => $textbotlang['keyboard']['zeroBalance'], 'callback_data' => "zerobalance-" . $id_user]],
            [['text' => $textbotlang['keyboard']['cronMessageStatus'], 'callback_data' => "statuscronuser-" . $id_user]],
        ]
    ];
    if ($user['agent'] == "n2")
        $keyboardmanage['inline_keyboard'][] = [['text' => $textbotlang['keyboard']['agentPurchaseCap'], 'callback_data' => "maxbuyagent_" . $id_user]];
    if ($user['agent'] != "f") {
        $keyboardmanage['inline_keyboard'][] = [
            ['text' => $textbotlang['keyboard']['activateSalesBot'], 'callback_data' => "createbot_" . $id_user],
            ['text' => $textbotlang['keyboard']['deleteSalesBot'], 'callback_data' => "removebotsell_" . $id_user]
        ];
    }
    if ($user['agent'] != "f") {
        $keyboardmanage['inline_keyboard'][] = [
            ['text' => $textbotlang['keyboard']['baseVolumePrice'], 'callback_data' => "setvolumesrc_" . $id_user],
            ['text' => $textbotlang['keyboard']['baseTimePrice'], 'callback_data' => "settimepricesrc_" . $id_user]
        ];
        $keyboardmanage['inline_keyboard'][] = [
            ['text' => $textbotlang['keyboard']['hidePanelForAgent'], 'callback_data' => "hidepanel_" . $id_user],
        ];
        $keyboardmanage['inline_keyboard'][] = [
            ['text' => $textbotlang['keyboard']['showHiddenPanels'], 'callback_data' => "removehide_" . $id_user],
        ];
        $keyboardmanage['inline_keyboard'][] = [
            ['text' => $textbotlang['keyboard']['agentExpireTime'], 'callback_data' => "expireset_" . $id_user],
        ];
    }
    if (intval($setting['statuslimitchangeloc']) == 1) {
        $keyboardmanage['inline_keyboard'][] = [
            ['text' => $textbotlang['keyboard']['changeLocationLimit'], 'callback_data' => "changeloclimitbyuser_" . $id_user]
        ];
    }
    $keyboardmanage = json_encode($keyboardmanage, JSON_UNESCAPED_UNICODE);
    $user['Balance'] = number_format($user['Balance']);
    if ($user['register'] != "none") {
        if ($user['register'] == null)
            return;
        $userjoin = jdate('Y/m/d H:i:s', $user['register']);
    } else {
        $userjoin = $textbotlang['Admin']['manageUser']['unknownName'];
    }
    $userverify = [
        '0' => $textbotlang['Admin']['manageUser']['notVerified'],
        '1' => $textbotlang['Admin']['manageUser']['verified']
    ][$user['verify']];
    $showcart = [
        '0' => $textbotlang['Admin']['managepanel']['hidden'],
        '1' => $textbotlang['Admin']['managepanel']['shown']
    ][$user['cardpayment']];
    if ($user['last_message_time'] == null) {
        $lastmessage = "";
    } else {
        $lastmessage = jdate('Y/m/d H:i:s', $user['last_message_time']);
    }
    $datefirst = time() - 86400;
    $desired_date_time_start = time() - 3600;
    $month_date_time_start = time() - 2592000;
    $sql = "SELECT COUNT(*) AS cnt, COALESCE(SUM(price_product),0) AS total FROM invoice WHERE time_sell > :requestedDate AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND name_product != '{$textbotlang['common']['labels']['testServiceName']}' AND id_user = :id_user";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_user', $id_user);
    $stmt->bindParam(':requestedDate', $desired_date_time_start);
    $stmt->execute();
    $hoursStat = $stmt->fetch(PDO::FETCH_ASSOC);
    $listhours = (int) $hoursStat['cnt'];
    $suminvoicehours = $hoursStat['total'] ?: "0";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_user', $id_user);
    $stmt->bindParam(':requestedDate', $month_date_time_start);
    $stmt->execute();
    $monthStat = $stmt->fetch(PDO::FETCH_ASSOC);
    $listmonth = (int) $monthStat['cnt'];
    $suminvoicemonth = $monthStat['total'] ?: "0";
    if ($user['agent'] != "f" && $user['expire'] != null) {
        $text_expie_agent = $textbotlang['Admin']['agent']['expiryDateLabel'] . jdate('Y/m/d H:i:s', $user['expire']);
    } else {
        $text_expie_agent = "";
    }
    $textinfouser = sprintf($textbotlang['Admin']['manageUser']['infoSummary'], $user['User_Status'], $user['username'], $id_user, $id_user, $user['codeInvitation'], $userjoin, $lastmessage, $user['limit_usertest'], $roll_Status, $user['number'], $user['agent'], $user['affiliatescount'], $user['affiliates'], $userverify, $showcart, $user['score'], $sumvolume['SUM(Volume)'], $text_expie_agent, $user['Balance'], $dayListSell['COUNT(*)'], $balanceall['SUM(price)'], $subbuyuser['SUM(price_product)'], $user['pricediscount'], $listhours, $suminvoicehours, $listmonth, $suminvoicemonth);
    if (isset($datain[0]) && $datain[0] == "u") {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['keyboard']['infoUpdated'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
        Editmessagetext($from_id, $message_id, $textinfouser, $keyboardmanage);
    } else {
        sendmessage($from_id, $textinfouser, $keyboardmanage, 'HTML');
        sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboardadmin, 'HTML');
    }
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['createGiftCode'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Discount']['getCode'], $backadmin, 'HTML');
    step('get_code', $from_id);
} elseif ($user['step'] == "get_code") {
    if (!preg_match('/^[A-Za-z\d]+$/', $text)) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['errorCode'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO Discount (code, limitused) VALUES (:code, :limitused)");
    $value = "0";
    $stmt->bindParam(':code', $text, PDO::PARAM_STR);
    $stmt->bindParam(':limitused', $value, PDO::PARAM_STR);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Discount']['priceCode'], null, 'HTML');
    step('get_price_code', $from_id);
    update("user", "Processing_value", $text, "id", $from_id);
} elseif ($user['step'] == "get_price_code") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['invalidPrice'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Discount']['setLimitUse'], $backadmin, 'HTML');
    update("Discount", "price", $text, "code", $user['Processing_value']);
    step('getlimitcodedis', $from_id);
} elseif ($user['step'] == "getlimitcodedis") {
    step("home", $from_id);
    update("Discount", "limituse", $text, "code", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Discount']['saveCode'], $keyboardadmin, 'HTML');
} elseif ($text == $textbotlang['keyboard']['deleteGiftCode'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Discount']['removeCode'], $json_list_Discount_list_admin, 'HTML');
    step('remove-Discount', $from_id);
} elseif ($user['step'] == "remove-Discount") {
    if (!rowExists("Discount", "code", $text)) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['notCode'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM Discount WHERE code = :code");
    $stmt->bindParam(':code', $text, PDO::PARAM_STR);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Discount']['removedCode'], $shopkeyboard, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['Admin']['Protocol']['btnDelete'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Protocol']['removeProtocol'], $keyboardprotocollist, 'HTML');
    step('removeprotocol', $from_id);
} elseif ($user['step'] == "removeprotocol") {
    if (!in_array($text, $protocoldata)) {
        sendmessage($from_id, $textbotlang['Admin']['Protocol']['invalidProtocol'], null, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Protocol']['removedProtocol'], $optionMarzban, 'HTML');
    $stmt = $pdo->prepare("DELETE FROM protocol WHERE NameProtocol = :protocol");
    $stmt->bindParam(':protocol', $text, PDO::PARAM_STR);
    $stmt->execute();
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['usernameMethod'] && $adminrulecheck['rule'] == "administrator") {
    $text_username = $textbotlang['Admin']['algorithmUsername']['selectMethod'];
    sendmessage($from_id, $text_username, $MethodUsername, 'HTML');
    step('updatemethodusername', $from_id);
} elseif ($user['step'] == "updatemethodusername") {
    $methodusername = usernameMethodKey($text, null);
    if ($methodusername === null) {
        sendmessage($from_id, $textbotlang['Admin']['algorithmUsername']['selectMethod'], $MethodUsername, 'HTML');
        return;
    }
    update("marzban_panel", "MethodUsername", $methodusername, "name_panel", $user['Processing_value']);
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if (in_array($methodusername, ['customTextRandom', 'customTextSequential', 'agentCustomTextSequential'], true)) {
        step('getnamecustom', $from_id);
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['customNameSend'], $backadmin, 'HTML');
        return;
    }
    if ($methodusername === 'usernameSequential') {
        step('getnamecustom', $from_id);
        sendmessage($from_id, $textbotlang['Admin']['algorithmUsername']['askFallbackName'], $backadmin, 'HTML');
        return;
    }
    outtypepanel($typepanel['type'], $textbotlang['Admin']['algorithmUsername']['saveData']);
    step('home', $from_id);
} elseif ($user['step'] == "getnamecustom") {
    if (!preg_match('/^\w{3,32}$/', $text)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['invalidName'], $backadmin, 'html');
        return;
    }
    update("marzban_panel", "namecustom", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['savedName']);
} elseif (($datain == "cartsetting" && $adminrulecheck['rule'] == "administrator") || $text == $textbotlang['keyboard']['backToCardSettings']) {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $CartManage, 'HTML');
} elseif ($text == $textbotlang['keyboard']['setCardNumber'] && $adminrulecheck['rule'] == "administrator") {
    $textcart = $textbotlang['Admin']['card']['askNumber'];
    sendmessage($from_id, $textcart, $backadmin, 'HTML');
    step('changecard', $from_id);
} elseif ($user['step'] == "changecard") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['card']['mustBeNumeric'], $backuser, 'HTML');
        return;
    }
    if (rowExists("card_number", "cardnumber", $text)) {
        sendmessage($from_id, $textbotlang['Admin']['card']['exists'], $backuser, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['SettingPayment']['getNameCard'], $backuser, 'HTML');
    update("user", "Processing_value", $text, "id", $from_id);
    step('getnamecard', $from_id);
} elseif ($user['step'] == "getnamecard") {
    try {
        if (function_exists('ensureCardNumberTableSupportsUnicode')) {
            ensureCardNumberTableSupportsUnicode();
        }

        $stmt = $pdo->prepare("INSERT INTO card_number (cardnumber,namecard) VALUES (?,?)");
        $stmt->execute([$user['Processing_value'], $text]);
        sendmessage($from_id, $textbotlang['Admin']['SettingPayment']['saveCard'], $CartManage, 'HTML');
        step('home', $from_id);
    } catch (\PDOException $e) {
        error_log('Failed to save card number: ' . $e->getMessage());
        if (stripos($e->getMessage(), 'Incorrect string value') !== false) {
            error_log('card_number insert failed due to charset mismatch. Please verify the table collation.');
        }
        sendmessage($from_id, $textbotlang['Admin']['card']['saveFailed'], $backadmin, 'HTML');
        step('home', $from_id);
    }
} elseif ($datain == "plisiosetting" && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $NowPaymentsManage, 'HTML');
} elseif ($text == "🧩 api plisio" && $adminrulecheck['rule'] == "administrator") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "apinowpayment")['ValuePay'];
    $textcart = sprintf($textbotlang['Admin']['gateway']['askPlisioApi'], $PaySetting);
    sendmessage($from_id, $textcart, $backadmin, 'HTML');
    step('apinowpayment', $from_id);
} elseif ($user['step'] == "apinowpayment") {
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $NowPaymentsManage, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "apinowpayment");
    step('home', $from_id);
} elseif ($datain == "iranpay1setting" && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $Swapinokey, 'HTML');
} elseif ($text == "API NOWPAYMENT") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "marchent_tronseller")['ValuePay'];
    $texttronseller = sprintf($textbotlang['Admin']['gateway']['askNowPaymentsApi'], $PaySetting);
    sendmessage($from_id, $texttronseller, $backadmin, 'HTML');
    step('marchent_tronseller', $from_id);
} elseif ($user['step'] == "marchent_tronseller") {
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $keyboardadmin, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "marchent_tronseller");
    step('home', $from_id);
} elseif ($datain == "aqayepardakhtsetting" && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $aqayepardakht, 'HTML');
} elseif ($datain == "zarinpalsetting" && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['selectOption'], $keyboardzarinpal, 'HTML');
} elseif ($text == $textbotlang['keyboard']['setAqayePardakhtMerchant'] && $adminrulecheck['rule'] == "administrator") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "merchant_id_aqayepardakht")['ValuePay'];
    $textaqayepardakht = sprintf($textbotlang['Admin']['gateway']['askAqayePardakhtMerchant'], $PaySetting);
    sendmessage($from_id, $textaqayepardakht, $backadmin, 'HTML');
    step('merchant_id_aqayepardakht', $from_id);
} elseif ($user['step'] == "merchant_id_aqayepardakht") {
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $aqayepardakht, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "merchant_id_aqayepardakht");
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['zarinPalMerchant'] && $adminrulecheck['rule'] == "administrator") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "merchant_zarinpal")['ValuePay'];
    $textaqayepardakht = sprintf($textbotlang['Admin']['gateway']['askZarinpalMerchant'], $PaySetting);
    sendmessage($from_id, $textaqayepardakht, $backadmin, 'HTML');
    step('merchant_zarinpal', $from_id);
} elseif ($user['step'] == "merchant_zarinpal") {
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $keyboardzarinpal, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "merchant_zarinpal");
    step('home', $from_id);
} elseif ($text == $textbotlang['Admin']['btnKeyboard']['managementPanel'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['getLoc'], $json_list_marzban_panel, 'HTML');
    step('GetLocationEdit', $from_id);
} elseif ($user['step'] == "GetLocationEdit") {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $text, "select");
    if ($marzban_list_get['type'] == "marzban") {
        $Check_token = token_panel($marzban_list_get['code_panel'], false);
        if (isset($Check_token['access_token'])) {
            $System_Stats = Get_System_Stats($text);
            if ($marzban_list_get['version_panel'] == "1") {
                $active_users = $System_Stats['active_users']
                    ?? $System_Stats['users_active']
                    ?? $System_Stats['online_users']
                    ?? 0;
            } else {
                $active_users = $System_Stats['users_active']
                    ?? $System_Stats['active_users']
                    ?? $System_Stats['online_users']
                    ?? 0;
            }
            $total_user = $System_Stats['total_user'];
            $mem_total = formatBytes($System_Stats['mem_total']);
            $mem_used = formatBytes($System_Stats['mem_used']);
            $bandwidth = formatBytes($System_Stats['outgoing_bandwidth'] + $System_Stats['incoming_bandwidth']);
            $__q41 = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND Service_location = ? AND name_product != ?");
            $__q41->bindValue(1, $marzban_list_get['name_panel'], PDO::PARAM_STR);
            $__q41->bindValue(2, $textbotlang['common']['labels']['testServiceName'], PDO::PARAM_STR);
            $__q41->execute();
            $ListSell = number_format($__q41->fetch(PDO::FETCH_ASSOC)['COUNT(*)'] ?? 0);
            $__q42 = $pdo->prepare("SELECT SUM(price_product) FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND Service_location = ? AND name_product != ?");
            $__q42->bindValue(1, $marzban_list_get['name_panel'], PDO::PARAM_STR);
            $__q42->bindValue(2, $textbotlang['common']['labels']['testServiceName'], PDO::PARAM_STR);
            $__q42->execute();
            $ListSellSUM = number_format($__q42->fetch(PDO::FETCH_ASSOC)['SUM(price_product)'] ?? 0);

            $Condition_marzban = "";
            $text_marzban = sprintf($textbotlang['Admin']['stats']['panelMarzban'], $total_user, $active_users, $System_Stats['version'], $mem_total, $mem_used, $bandwidth, $ListSell, $ListSellSUM, $marzban_list_get['agent']);
            if ($marzban_list_get['version_panel'] == "1") {
                $text_marzban = str_replace($textbotlang['keyboard']['marzban'], $textbotlang['keyboard']['passargadPanel'], $text_marzban);
            }
            sendmessage($from_id, $text_marzban, $optionMarzban, 'HTML');
        } elseif (isset($Check_token['detail']) && $Check_token['detail'] == "Incorrect username or password") {
            $text_marzban = $textbotlang['Admin']['managepanel']['invalidCredentials'];
            sendmessage($from_id, $text_marzban, $optionMarzban, 'HTML');
        } else {
            $text_marzban = (!empty($Check_token['error']) || !empty($Check_token['errror'])) ? panelErrorText($Check_token['error'] ?? $Check_token['errror']) : $textbotlang['Admin']['managepanel']['errorStatusPanel'] . json_encode($Check_token);
            sendmessage($from_id, $text_marzban, $optionMarzban, 'HTML');
        }
    } elseif ($marzban_list_get['type'] == "x-ui_single") {
        $status_server = status_server_xui($marzban_list_get);
        if (isset($status_server['status']) && $status_server['status'] != 200) {
            sendmessage($from_id, $textbotlang['Admin']['managepanel']['xuiErrorCode'] . $status_server['status'], $optionX_ui_single, 'HTML');
        } elseif (isset($status_server['error']) && $status_server['error'] != 200) {
            sendmessage($from_id, panelErrorText($status_server['error']), $optionX_ui_single, 'HTML');
        } else {
            $status_server = json_decode($status_server['body'], true);
            function percent($current, $total)
            {
                if ($total <= 0)
                    return 0;
                return round(($current / $total) * 100, 1);
            }
            $text_x_ui = $textbotlang['Admin']['stats']['server'];
            $o = $status_server['obj'];
            $replace = [
                '{cpu}' => round($o['cpu'], 1),
                '{cpuCores}' => $o['cpuCores'],
                '{logicalPro}' => $o['logicalPro'],
                '{cpuSpeed}' => round($o['cpuSpeedMhz'] / 1000, 2),
                '{load1}' => $o['loads'][0],
                '{load5}' => $o['loads'][1],
                '{load15}' => $o['loads'][2],
                '{memUsed}' => formatBytes($o['mem']['current']),
                '{memTotal}' => formatBytes($o['mem']['total']),
                '{memPercent}' => percent($o['mem']['current'], $o['mem']['total']),
                '{diskUsed}' => formatBytes($o['disk']['current']),
                '{diskTotal}' => formatBytes($o['disk']['total']),
                '{diskPercent}' => percent($o['disk']['current'], $o['disk']['total']),
                '{netUp}' => formatBytes($o['netIO']['up']),
                '{netDown}' => formatBytes($o['netIO']['down']),
                '{netSent}' => formatBytes($o['netTraffic']['sent']),
                '{netRecv}' => formatBytes($o['netTraffic']['recv']),
                '{tcp}' => $o['tcpCount'],
                '{udp}' => $o['udpCount'],
                '{xrayState}' => $o['xray']['state'] === 'running' ? $textbotlang['Admin']['stats']['xrayRunning'] : $textbotlang['Admin']['stats']['xrayStopped'],
                '{xrayVersion}' => $o['xray']['version'],
                '{panelVersion}' => $o['panelVersion'],
                '{uptime}' => $o['uptime'],
                '{ipv4}' => $o['publicIP']['ipv4'] ?: $textbotlang['Admin']['stats']['ipNone'],
                '{ipv6}' => $o['publicIP']['ipv6'] ?: $textbotlang['Admin']['stats']['ipNone'],
            ];

            $message = strtr($text_x_ui, $replace);
            sendmessage($from_id, $message, $optionX_ui_single, 'HTML');
        }
    } elseif ($marzban_list_get['type'] == "mirza_agent") {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $option_mirza, 'HTML');
    } elseif ($marzban_list_get['type'] == "alireza_single") {
        $x_ui_check_connect = login($marzban_list_get['code_panel'], false);
        if ($x_ui_check_connect['success']) {
            sendmessage($from_id, $textbotlang['Admin']['managepanel']['connectXUi'], $optionalireza_single, 'HTML');
        } elseif ($x_ui_check_connect['msg'] == "The username or password is incorrect") {
            $text_marzban = $textbotlang['Admin']['managepanel']['invalidCredentials'];
            sendmessage($from_id, $text_marzban, $optionalireza_single, 'HTML');
        } else {
            $text_marzban = panelErrorText($x_ui_check_connect['errror']);
            sendmessage($from_id, $text_marzban, $optionalireza_single, 'HTML');
        }
    } elseif ($marzban_list_get['type'] == "hiddify") {
        $System_Stats = serverstatus($marzban_list_get['name_panel']);
        if (!empty($System_Stats['status']) && $System_Stats['status'] != 200) {
            $text_marzban = $textbotlang['Admin']['managepanel']['fetchErrorCode'] . $System_Stats['status'];
            sendmessage($from_id, $text_marzban, $optionhiddfy, 'HTML');
        } elseif (!empty($System_Stats['error'])) {
            $text_marzban = panelErrorText($System_Stats['error']);
            sendmessage($from_id, $text_marzban, $optionhiddfy, 'HTML');
        } else {
            $System_Stats = json_decode($System_Stats['body'], true);
            if (isset($System_Stats['stats'])) {
                $mem_total = round($System_Stats['stats']['system']['ram_total'], 2);
                $mem_used = round($System_Stats['stats']['system']['ram_used'], 2);
                $bandwidth = formatBytes($System_Stats['outgoing_bandwidth'] + $System_Stats['incoming_bandwidth']);
                $text_marzban = sprintf($textbotlang['Admin']['stats']['panelServer'], $mem_total, $mem_used, $marzban_list_get['agent']);
                sendmessage($from_id, $text_marzban, $optionhiddfy, 'HTML');
            } elseif (isset($System_Stats['message']) && $System_Stats['message'] == "Unathorized") {
                $text_marzban = $textbotlang['Admin']['managepanel']['invalidUrl'];
                sendmessage($from_id, $text_marzban, $optionhiddfy, 'HTML');
            } else {
                sendmessage($from_id, $textbotlang['Admin']['managepanel']['notConnected'], $optionhiddfy, 'HTML');
            }
        }
    } elseif ($marzban_list_get['type'] == "Manualsale") {
        sendmessage($from_id, $textbotlang['Admin']['selectOption2'], $optionManualsale, 'HTML');
    } elseif ($marzban_list_get['type'] == "marzneshin") {
        $Check_token = token_panelm($marzban_list_get['code_panel']);
        if (isset($Check_token['access_token'])) {
            $System_Stats = Get_System_Statsm($text);
            if (!empty($System_Stats['status']) && $System_Stats['status'] != 200) {
                $text_marzban = $textbotlang['Admin']['managepanel']['fetchErrorCode'] . $System_Stats['status'];
                sendmessage($from_id, $text_marzban, $optionMarzban, 'HTML');
                return;
            } elseif (!empty($System_Stats['error'])) {
                $text_marzban = panelErrorText($System_Stats['error']);
                sendmessage($from_id, $text_marzban, $optionMarzban, 'HTML');
                return;
            }
            $System_Stats = json_decode($System_Stats['body'], true);
            $active_users = $System_Stats['active'];
            $total_user = $System_Stats['total'];
            $__q43 = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND Service_location = ? AND name_product != ?");
            $__q43->bindValue(1, $marzban_list_get['name_panel'], PDO::PARAM_STR);
            $__q43->bindValue(2, $textbotlang['common']['labels']['testServiceName'], PDO::PARAM_STR);
            $__q43->execute();
            $ListSell = number_format($__q43->fetch(PDO::FETCH_ASSOC)['COUNT(*)'] ?? 0);
            $__q44 = $pdo->prepare("SELECT SUM(price_product) FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND Service_location = ? AND name_product != ?");
            $__q44->bindValue(1, $marzban_list_get['name_panel'], PDO::PARAM_STR);
            $__q44->bindValue(2, $textbotlang['common']['labels']['testServiceName'], PDO::PARAM_STR);
            $__q44->execute();
            $ListSellSUM = number_format($__q44->fetch(PDO::FETCH_ASSOC)['SUM(price_product)'] ?? 0);
            $Condition_marzban = "";
            $text_marzban = sprintf($textbotlang['Admin']['stats']['panelMarzban2'], $total_user, $active_users, $ListSell, $ListSellSUM, $marzban_list_get['agent']);
            sendmessage($from_id, $text_marzban, $optionmarzneshin, 'HTML');
        } elseif (isset($Check_token['detail']) && $Check_token['detail'] == "Incorrect username or password") {
            $text_marzban = $textbotlang['Admin']['managepanel']['invalidCredentials'];
            sendmessage($from_id, $text_marzban, $optionMarzban, 'HTML');
        } else {
            $text_marzban = (!empty($Check_token['error']) || !empty($Check_token['errror'])) ? panelErrorText($Check_token['error'] ?? $Check_token['errror']) : $textbotlang['Admin']['managepanel']['errorStatusPanel'] . json_encode($Check_token);
            sendmessage($from_id, $text_marzban, $optionMarzban, 'HTML');
        }
    } elseif ($marzban_list_get['type'] == "WGDashboard") {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $optionwg, 'HTML');
    } elseif ($marzban_list_get['type'] == "s_ui") {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $options_ui, 'HTML');
    } elseif ($marzban_list_get['type'] == "ibsng") {
        $result = loginIBsng($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
        if ($result) {
            sendmessage($from_id, $result['msg'], $optionibsng, 'HTML');
        } else {
            sendmessage($from_id, $result['msg'], $optionibsng, 'HTML');
        }
    } elseif ($marzban_list_get['type'] == "mikrotik") {
        $result = login_mikrotik($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
        if (isset($result['error'])) {
            sendmessage($from_id, panelErrorText($result), $option_mikrotik, 'HTML');
        } else {
            $free_hdd_space = round($result['free-hdd-space'] / pow(1024, 3), 2);
            $free_memory = round($result['free-memory'] / pow(1024, 3), 2);
            $total_hdd_space = round($result['total-hdd-space'] / pow(1024, 3), 2);
            $total_memory = round($result['total-memory'] / pow(1024, 3), 2);
            sendmessage($from_id, sprintf($textbotlang['Admin']['stats']['mikrotik'], $result['platform'], $result['version'], $result['uptime'], $result['architecture-name'], $result['board-name'], $result['build-time'], $result['cpu'], $result['cpu-count'], $result['cpu-frequency'], $result['cpu-load'], $total_hdd_space, $free_hdd_space, $total_memory, $free_memory, $result['write-sect-since-reboot'], $result['write-sect-total']), $option_mikrotik, 'HTML');
        }
    } elseif ($marzban_list_get['type'] == "rebecca") {
        $Check_connection = Get_System_Stats_rebecca($marzban_list_get['name_panel']);
        if (empty($Check_connection['error']) && (empty($Check_connection['status']) || $Check_connection['status'] < 400)) {
            $ListSell = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND Service_location = ? AND name_product != ?");
            $ListSell->bindValue(1, $marzban_list_get['name_panel'], PDO::PARAM_STR);
            $ListSell->bindValue(2, $textbotlang['common']['labels']['testServiceName'], PDO::PARAM_STR);
            $ListSell->execute();
            $ListSell = number_format($ListSell->fetch(PDO::FETCH_ASSOC)['COUNT(*)'] ?? 0);
            $ListSellSum = $pdo->prepare("SELECT SUM(price_product) FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND Service_location = ? AND name_product != ?");
            $ListSellSum->bindValue(1, $marzban_list_get['name_panel'], PDO::PARAM_STR);
            $ListSellSum->bindValue(2, $textbotlang['common']['labels']['testServiceName'], PDO::PARAM_STR);
            $ListSellSum->execute();
            $ListSellSUM = number_format($ListSellSum->fetch(PDO::FETCH_ASSOC)['SUM(price_product)'] ?? 0);
            $text_marzban = sprintf($textbotlang['Admin']['stats']['panelSales'], $ListSell, $ListSellSUM, $marzban_list_get['agent']);
            sendmessage($from_id, $text_marzban, $optionrebecca, 'HTML');
        } elseif (!empty($Check_connection['status']) && $Check_connection['status'] == 401) {
            $text_marzban = $textbotlang['Admin']['managepanel']['invalidToken'];
            sendmessage($from_id, $text_marzban, $optionrebecca, 'HTML');
        } else {
            $text_marzban = !empty($Check_connection['error']) ? panelErrorText($Check_connection['error']) : $textbotlang['Admin']['managepanel']['errorStatusPanel'] . json_encode($Check_connection);
            sendmessage($from_id, $text_marzban, $optionrebecca, 'HTML');
        }
    } else {
        sendmessage($from_id, $textbotlang['Admin']['selectOption2'], $optionMarzban, 'HTML');
    }
    update("user", "Processing_value", $text, "id", $from_id);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['panelName'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['getNameNew'], $backadmin, 'HTML');
    step('GetNameNew', $from_id);
} elseif ($user['step'] == "GetNameNew") {
    if (rowExists("marzban_panel", "name_panel", $text)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['repeatPanel'], $backadmin, 'HTML');
        return;
    }
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['changedNamePanel']);
    update("marzban_panel", "name_panel", $text, "name_panel", $user['Processing_value']);
    update("invoice", "Service_location", $text, "Service_location", $user['Processing_value']);
    update("product", "Location", $text, "Location", $user['Processing_value']);
    update("user", "Processing_value", $text, "id", $from_id);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['editPanelUrl'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['getUrlNew'], $backadmin, 'HTML');
    step('GeturlNew', $from_id);
} elseif ($user['step'] == "GeturlNew") {
    $text = normalizePanelUrl($text);
    if (!filter_var($text, FILTER_VALIDATE_URL)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['invalidDomain'], $backadmin, 'HTML');
        return;
    }
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['changedUrlPanel']);
    update("marzban_panel", "url_panel", $text, "name_panel", $user['Processing_value']);
    update("marzban_panel", "datelogin", null, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['changeUserGroup'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['askUserGroup'], $backadmin, 'HTML');
    step('getagentpanel', $from_id);
} elseif ($user['step'] == "getagentpanel") {
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['userGroupChanged']);
    update("marzban_panel", "agent", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} elseif ($text == $textbotlang['Admin']['managepanel']['btnSubLinkDomain'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['askSubLinkSample'], $backadmin, 'HTML');
    step('GeturlNewx', $from_id);
} elseif ($user['step'] == "GeturlNewx") {
    if (!filter_var($text, FILTER_VALIDATE_URL)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['invalidDomain'], $backadmin, 'HTML');
        return;
    }
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($typepanel['type'] == "x-ui_single") {
        $req = new CurlRequest($text);
        $response = $req->get();
        if ($response['status'] != 200) {
            sendmessage($from_id, $textbotlang['Admin']['managepanel']['subLinkInactive'], null, 'HTML');
            return;
        } elseif (!empty($response['error'])) {
            sendmessage($from_id, $textbotlang['Admin']['managepanel']['subLinkInactive'], null, 'HTML');
            return;
        }
        $response = $response['body'];
        if (isBase64($response)) {
            $response = base64_decode($response);
        }
        $protocol = ['vmess', 'vless', 'trojan', 'ss'];
        $sub_check = explode('://', $response)[0];
        if (!in_array($sub_check, $protocol)) {
            sendmessage($from_id, $textbotlang['Admin']['managepanel']['subLinkInvalid'], null, 'HTML');
            return;
        }
        $text = dirname($text);
    }
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['changedUrlPanel']);
    update("marzban_panel", "linksubx", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} elseif ($text == "🔗 uuid admin" && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['askAdminUuid'], $backadmin, 'HTML');
    step('getuuidadmin', $from_id);
} elseif ($user['step'] == "getuuidadmin") {
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['adminUuidSaved']);
    update("marzban_panel", "secret_code", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['accountCreateLimit'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['setLimit'], $backadmin, 'HTML');
    step('getlimitnew', $from_id);
} elseif ($user['step'] == "getlimitnew") {
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['changedLimit']);
    update("marzban_panel", "limit_panel", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['testServiceTime'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['getlimitusertest']['askTime'], $backadmin, 'HTML');
    step('updatetime', $from_id);
} elseif ($user['step'] == "updatetime") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidTime'], $backadmin, 'HTML');
        return;
    }
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['savedData']);
    update("marzban_panel", "time_usertest", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['testAccountVolume'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['getlimitusertest']['askVolume'], $backadmin, 'HTML');
    step('val_usertest', $from_id);
} elseif ($user['step'] == "val_usertest") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidVolume'], $backadmin, 'HTML');
        return;
    }
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['savedData']);
    update("marzban_panel", "val_usertest", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['setInboundId'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['askInboundId'], $backadmin, 'HTML');
    step('getinboundiid', $from_id);
} elseif ($user['step'] == "getinboundiid") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['inboundIdSaved'], $optionX_ui_single, 'HTML');
    update("marzban_panel", "inboundid", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['editUsername'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['getUsernameNew'], $backadmin, 'HTML');
    step('GetusernameNew', $from_id);
} elseif ($user['step'] == "GetusernameNew") {
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['changedUsernamePanel']);
    update("marzban_panel", "username_panel", $text, "name_panel", $user['Processing_value']);
    update("marzban_panel", "datelogin", null, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} elseif ($text == $textbotlang['Admin']['Protocol']['btnSettings'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['Inbound']['getProtocol'], $keyboardprotocol, 'HTML');
    step('getprotocolx_ui', $from_id);
} elseif ($user['step'] == "getprotocolx_ui") {
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['setProtocol']);
    $marzbanprotocol = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    update("x_ui", "protocol", $text, "codepanel", $marzbanprotocol['code_panel']);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['editPassword'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['getPasswordNew'], $backadmin, 'HTML');
    step('GetpaawordNew', $from_id);
} elseif ($user['step'] == "GetpaawordNew") {
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['changedPasswordPanel']);
    update("marzban_panel", "password_panel", $text, "name_panel", $user['Processing_value']);
    update("marzban_panel", "datelogin", null, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['deletePanel'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['confirmByWord'], $backadmin, 'HTML');
    step('confirmremovepanel', $from_id);
} elseif ($user['step'] == "confirmremovepanel") {
    if ($text == $textbotlang['keyboard']['confirm']) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['removedPanel'], $keyboardadmin, 'HTML');
        $marzban = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
        $stmt = $pdo->prepare("DELETE FROM marzban_panel WHERE name_panel = :name_panel");
        $stmt->bindParam(':name_panel', $user['Processing_value'], PDO::PARAM_STR);
        $stmt->execute();
    }
    step('home', $from_id);
} elseif ($text == $textbotlang['Admin']['btnKeyboard']['manageUser'] || $datain == "backlistuser") {
    $keyboardtypelistuser = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['usersWithBalance'], 'callback_data' => "balanceuserlist"],
            ],
            [
                ['text' => $textbotlang['keyboard']['usersWithAffiliates'], 'callback_data' => "listrefral"],
            ],
            [
                ['text' => $textbotlang['keyboard']['activeCardUserList'], 'callback_data' => "cartuserlist"],
            ],
            [
                ['text' => $textbotlang['keyboard']['usersWithNegativeBalance'], 'callback_data' => "zerobalance"],
            ],
            [
                ['text' => $textbotlang['keyboard']['agentList'], 'callback_data' => "agentlistusers"],
                ['text' => $textbotlang['keyboard']['allUserList'], 'callback_data' => "alllistusers"],
            ],
            [
                ['text' => $textbotlang['keyboard']['searchOrder'], 'callback_data' => "searchorder"],
                ['text' => $textbotlang['keyboard']['groupCharge'], 'callback_data' => "balanceaddall"],
            ],
            [
                ['text' => $textbotlang['keyboard']['searchUserBtn'], 'callback_data' => "searchuser"],
                ['text' => $textbotlang['keyboard']['messagingSection'], 'callback_data' => "systemsms"],
            ],
            [
                ['text' => $textbotlang['keyboard']['groupVolumeOrTime'], 'callback_data' => "voloume_or_day_all"],
            ]
        ]
    ]);
    $text_list_users = $textbotlang['Admin']['selectOption3'];
    if ($datain == "backlistuser") {
        Editmessagetext($from_id, $message_id, $text_list_users, $keyboardtypelistuser);
    } else {
        sendmessage($from_id, $text_list_users, $keyboardtypelistuser, 'html');
    }
} elseif ($datain == "alllistusers") {
    update("user", "pagenumber", "1", "id", $from_id);
    $page = 1;
    $items_per_page = 10;
    $start_index = ($page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM user  LIMIT ?, ?");
    $result->bindValue(1, $start_index, PDO::PARAM_INT);
    $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageuser'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_pageuser'
        ]
    ];
    $backbtn = [
        [
            'text' => $textbotlang['keyboard']['backToPrev'],
            'callback_data' => 'backlistuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $backbtn;
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif ($datain == 'next_pageuser') {
    $numpage = select("user", "*", null, null, "count");
    $page = $user['pagenumber'];
    $items_per_page = 10;
    $sum = $user['pagenumber'] * $items_per_page;
    if ($sum > $numpage) {
        $next_page = 1;
    } else {
        $next_page = $page + 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM user LIMIT ?, ?");
    $result->bindValue(1, $start_index, PDO::PARAM_INT);
    $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageuser'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_pageuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif ($datain == 'previous_pageuser') {
    $page = $user['pagenumber'];
    $items_per_page = 10;
    if ($user['pagenumber'] <= 1) {
        $next_page = 1;
    } else {
        $next_page = $page - 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM user LIMIT ?, ?");
    $result->bindValue(1, $start_index, PDO::PARAM_INT);
    $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageuser'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_pageuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif ($datain == "agentlistusers") {
    $keyboardtypelistuser = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "n", 'callback_data' => "agenttypshowlist_n"],
                ['text' => "n2", 'callback_data' => "agenttypshowlist_n2"],
            ],
            [
                ['text' => $textbotlang['keyboard']['allAgents'], 'callback_data' => "agenttypshowlist_all"],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['agent']['selectGroup'], $keyboardtypelistuser);
} elseif (preg_match('/agenttypshowlist_(\w+)/', $datain, $datagetr)) {
    $typeagent = $datagetr[1];
    update("user", "pagenumber", "1", "id", $from_id);
    $page = 1;
    $items_per_page = 10;
    $start_index = ($page - 1) * $items_per_page;
    if ($typeagent == "all") {
        $result = $pdo->prepare("SELECT * FROM user WHERE agent != 'f'  LIMIT ?, ?");
        $result->bindValue(1, $start_index, PDO::PARAM_INT);
        $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
        $result->execute();
    } else {
        $result = $pdo->prepare("SELECT * FROM user WHERE agent = ?  LIMIT ?, ?");
        $result->bindValue(1, $typeagent, PDO::PARAM_STR);
        $result->bindValue(2, $start_index, PDO::PARAM_INT);
        $result->bindValue(3, $items_per_page, PDO::PARAM_INT);
        $result->execute();
    }
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => "next_pageuseragent_$typeagent"
        ]
    ];
    $backbtn = [
        [
            'text' => $textbotlang['keyboard']['backToPrev'],
            'callback_data' => 'backlistuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $backbtn;
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif (preg_match('/next_pageuseragent_(\w+)/', $datain, $datagetr)) {
    $typeagent = $datagetr[1];
    $numpage = select("user", "*", null, null, "count");
    $page = $user['pagenumber'];
    $items_per_page = 10;
    $sum = $user['pagenumber'] * $items_per_page;
    if ($sum > $numpage) {
        $next_page = 1;
    } else {
        $next_page = $page + 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    if ($typeagent == "all") {
        $result = $pdo->prepare("SELECT * FROM user WHERE agent != 'f'  LIMIT ?, ?");
        $result->bindValue(1, $start_index, PDO::PARAM_INT);
        $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
        $result->execute();
    } else {
        $result = $pdo->prepare("SELECT * FROM user WHERE agent = ?  LIMIT ?, ?");
        $result->bindValue(1, $typeagent, PDO::PARAM_STR);
        $result->bindValue(2, $start_index, PDO::PARAM_INT);
        $result->bindValue(3, $items_per_page, PDO::PARAM_INT);
        $result->execute();
    }
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => "next_pageuseragent_$typeagent"
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => "previous_pageuseragent_$typeagent"
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif (preg_match('/previous_pageuseragent_(\w+)/', $datain, $datagetr)) {
    $typeagent = $datagetr[1];
    $page = $user['pagenumber'];
    $items_per_page = 10;
    if ($user['pagenumber'] <= 1) {
        $next_page = 1;
    } else {
        $next_page = $page - 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    if ($typeagent == "all") {
        $result = $pdo->prepare("SELECT * FROM user WHERE agent != 'f'  LIMIT ?, ?");
        $result->bindValue(1, $start_index, PDO::PARAM_INT);
        $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
        $result->execute();
    } else {
        $result = $pdo->prepare("SELECT * FROM user WHERE agent = ?  LIMIT ?, ?");
        $result->bindValue(1, $typeagent, PDO::PARAM_STR);
        $result->bindValue(2, $start_index, PDO::PARAM_INT);
        $result->bindValue(3, $items_per_page, PDO::PARAM_INT);
        $result->execute();
    }
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => "next_pageuseragent_$typeagent"
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => "previous_pageuseragent_$typeagent"
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif ($datain == "balanceuserlist") {
    update("user", "pagenumber", "1", "id", $from_id);
    $page = 1;
    $items_per_page = 10;
    $start_index = ($page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM user WHERE Balance != '0'  LIMIT ?, ?");
    $result->bindValue(1, $start_index, PDO::PARAM_INT);
    $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageuserbalance'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_pageuserbalance'
        ]
    ];
    $backbtn = [
        [
            'text' => $textbotlang['keyboard']['backToPrev'],
            'callback_data' => 'backlistuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $backbtn;
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif ($datain == 'next_pageuserbalance') {
    $numpage = select("user", "*", null, null, "count");
    $page = $user['pagenumber'];
    $items_per_page = 10;
    $sum = $user['pagenumber'] * $items_per_page;
    if ($sum > $numpage) {
        $next_page = 1;
    } else {
        $next_page = $page + 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM user WHERE Balance != '0'  LIMIT ?, ?");
    $result->bindValue(1, $start_index, PDO::PARAM_INT);
    $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageuserbalance'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_pageuserbalance'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif ($datain == 'previous_pageuserbalance') {
    $page = $user['pagenumber'];
    $items_per_page = 10;
    if ($user['pagenumber'] <= 1) {
        $next_page = 1;
    } else {
        $next_page = $page - 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM user WHERE Balance != '0'  LIMIT ?, ?");
    $result->bindValue(1, $start_index, PDO::PARAM_INT);
    $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageuserbalance'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_pageuserbalance'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif ($datain == "listrefral") {
    update("user", "pagenumber", "1", "id", $from_id);
    $page = 1;
    $items_per_page = 10;
    $start_index = ($page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM user WHERE affiliatescount != '0'  LIMIT ?, ?");
    $result->bindValue(1, $start_index, PDO::PARAM_INT);
    $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageuserrefral'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_pageuserrefral'
        ]
    ];
    $backbtn = [
        [
            'text' => $textbotlang['keyboard']['backToPrev'],
            'callback_data' => 'backlistuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $backbtn;
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif ($datain == 'next_pageuserrefral') {
    $numpage = select("user", "*", null, null, "count");
    $page = $user['pagenumber'];
    $items_per_page = 10;
    $sum = $user['pagenumber'] * $items_per_page;
    if ($sum > $numpage) {
        $next_page = 1;
    } else {
        $next_page = $page + 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM user WHERE affiliatescount != '0'  LIMIT ?, ?");
    $result->bindValue(1, $start_index, PDO::PARAM_INT);
    $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageuserrefral'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_pageuserrefral'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif ($datain == 'previous_pageuserrefral') {
    $page = $user['pagenumber'];
    $items_per_page = 10;
    if ($user['pagenumber'] <= 1) {
        $next_page = 1;
    } else {
        $next_page = $page - 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM user WHERE affiliatescount != '0'  LIMIT ?, ?");
    $result->bindValue(1, $start_index, PDO::PARAM_INT);
    $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageuserrefral'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_pageuserrefral'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif (preg_match('/addbalanceuser_(\w+)/', $datain, $dataget) && $adminrulecheck['rule'] == "administrator") {
    $iduser = $dataget[1];
    update("user", "Processing_value", $iduser, "id", $from_id);
    telegram('sendmessage', [
        'chat_id' => $from_id,
        'text' => $textbotlang['Admin']['manageUser']['addBalanceUserDesc'],
        'reply_markup' => $backadmin,
        'parse_mode' => "HTML",
        'reply_to_message_id' => $message_id,
    ]);
    step('addbalanceusercurrent', $from_id);
} elseif ($user['step'] == "addbalanceusercurrent") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['invalidPrice'], $backadmin, 'HTML');
        return;
    }
    if ($text > 100000000) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['maxAmountToman'], $backadmin, 'HTML');
        return;
    }
    $dateacc = date('Y/m/d H:i:s');
    $randomString = bin2hex(random_bytes(5));
    $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
    $payment_Status = "paid";
    $Payment_Method = "add balance by admin";
    $invoice = null;
    $stmt->execute([$user['Processing_value'], $randomString, $dateacc, $text, $payment_Status, $Payment_Method, $invoice]);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['addBalanced'], $keyboardadmin, 'html');
    $Balance_user = select("user", "*", "id", $user['Processing_value'], "select");
    $Balance_add_user = $Balance_user['Balance'] + $text;
    update("user", "Balance", $Balance_add_user, "id", $user['Processing_value']);
    $heibalanceuser = number_format($text, 0);
    $textadd = sprintf($textbotlang['users']['Balance']['addedNotice'], $heibalanceuser);
    sendmessage($user['Processing_value'], $textadd, null, 'HTML');
    step('home', $from_id);
    $Balance_user_after = number_format(select("user", "*", "id", $user['Processing_value'], "select")['Balance']);
    $pricadd = number_format($text);
    if (strlen($setting['Channel_Report']) > 0) {
        $textaddbalance = sprintf($textbotlang['Admin']['reportgroup']['balanceIncreased'], $username, $from_id, $user['Processing_value'], $pricadd, $Balance_user_after);
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $paymentreports,
            'text' => $textaddbalance,
            'parse_mode' => "HTML"
        ]);
    }
} elseif (preg_match('/lowbalanceuser_(\w+)/', $datain, $dataget) && $adminrulecheck['rule'] == "administrator") {
    $iduser = $dataget[1];
    update("user", "Processing_value", $iduser, "id", $from_id);
    telegram('sendmessage', [
        'chat_id' => $from_id,
        'text' => $textbotlang['Admin']['manageUser']['lowBalanceUserDesc'],
        'reply_markup' => $backadmin,
        'parse_mode' => "HTML",
        'reply_to_message_id' => $message_id,
    ]);
    step('addbalanceuser', $from_id);
} elseif ($user['step'] == "addbalanceuser") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['invalidPrice'], $backadmin, 'HTML');
        return;
    }
    if ($text > 100000000) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['maxAmountToman'], $backadmin, 'HTML');
        return;
    }
    $dateacc = date('Y/m/d H:i:s');
    $randomString = bin2hex(random_bytes(5));
    $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user,id_order,time,price,payment_Status,Payment_Method,id_invoice) VALUES (?,?,?,?,?,?,?)");
    $payment_Status = "paid";
    $Payment_Method = "low balance by admin";
    $invoice = null;
    $stmt->execute([$user['Processing_value'], $randomString, $dateacc, $text, $payment_Status, $Payment_Method, $invoice]);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['lowBalanced'], $keyboardadmin, 'html');
    $Balance_user = select("user", "*", "id", $user['Processing_value'], "select");
    $Balance_add_user = $Balance_user['Balance'] - $text;
    update("user", "Balance", $Balance_add_user, "id", $user['Processing_value']);
    $lowbalanceuser = number_format($text, 0);
    $textkam = sprintf($textbotlang['users']['Balance']['deductedNotice2'], $lowbalanceuser);
    sendmessage($user['Processing_value'], $textkam, null, 'HTML');
    step('home', $from_id);
    $Balance_user_afters = number_format(select("user", "*", "id", $user['Processing_value'], "select")['Balance']);
    if (strlen($setting['Channel_Report']) > 0) {
        $textaddbalance = sprintf($textbotlang['Admin']['reportgroup']['balanceDecreased2'], $username, $from_id, $user['Processing_value'], $text, $Balance_user_afters);
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $paymentreports,
            'text' => $textaddbalance,
            'parse_mode' => "HTML"
        ]);
    }
} elseif ((preg_match('/banuserlist_(\w+)/', $datain, $dataget) || preg_match('/blockuserfake_(\w+)/', $datain, $dataget)) && $adminrulecheck['rule'] == "administrator") {
    $iduser = $dataget[1];
    $userdata = select("user", "*", "id", $iduser, "select");
    if ($userdata['User_Status'] == "block") {
        sendmessage($from_id, $textbotlang['Admin']['manageUser']['blockedUser'], null, 'HTML');
        return;
    }
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['confirm'], 'callback_data' => 'acceptblock_' . $iduser],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['confirmByButton'], $Response, 'HTML');
} elseif ($user['step'] == "adddecriptionblock") {
    update("user", "description_blocking", $text, "id", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['descriptionBlock'], $keyboardadmin, 'HTML');
    step('home', $from_id);

} elseif ((preg_match('/acceptblock_(\w+)/', $datain, $dataget) || preg_match('/blockuserfake_(\w+)/', $datain, $dataget))) {

    $iduser = $dataget[1];
    update("user", "Processing_value", $iduser, "id", $from_id);
    update("user", "User_Status", "block", "id", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['blockUser'], $backadmin, 'HTML');
    step('adddecriptionblock', $from_id);
    $textblok = sprintf($textbotlang['Admin']['reportgroup']['userBlocked'], $iduser, $from_id);
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['manageUser']['manageUserBtn'], 'callback_data' => 'manageuser_' . $iduser],
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
} elseif (preg_match('/verify_(\w+)/', $datain, $dataget) && $adminrulecheck['rule'] == "administrator") {
    $iduser = $dataget[1];
    update("user", "verify", "1", "id", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['verifiedSuccess'], null, 'HTML');
    sendmessage($iduser, $textbotlang['users']['account']['verifiedByAdmin'], $keyboard, 'HTML');
} elseif (preg_match('/unverify-(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    update("user", "verify", "0", "id", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['unverifiedSuccess'], null, 'HTML');


} elseif (preg_match('/unbanuserr_(\w+)/', $datain, $dataget) && $adminrulecheck['rule'] == "administrator") {
    $iduser = $dataget[1];
    $userdata = select("user", "*", "id", $iduser, "select");
    if ($userdata['User_Status'] == "Active") {
        sendmessage($from_id, $textbotlang['Admin']['manageUser']['userNotBlock'], null, 'HTML');
        return;
    }
    $textblok = sprintf($textbotlang['Admin']['reportgroup']['userUnblocked'], $iduser, $from_id);
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['manageUser']['manageUserBtn'], 'callback_data' => 'manageuser_' . $iduser],
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
    update("user", "User_Status", "Active", "id", $iduser);
    update("user", "description_blocking", " ", "id", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['userUnblocked'], $keyboardadmin, 'HTML');
    sendmessage($iduser, $textbotlang['users']['block']['unblockedNotice'], $keyboard, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/confirmnumber_(\w+)/', $datain, $dataget) && $adminrulecheck['rule'] == "administrator") {
    $iduser = $dataget[1];
    update("user", "number", "confrim number by admin", "id", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['phone']['active'], $keyboardadmin, 'HTML');
} elseif (preg_match('/viewpaymentuser_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    $PaymentUsers = select("Payment_report", "*", "id_user", $iduser, "fetchAll");
    foreach ($PaymentUsers as $paymentUser) {
        $text_order = sprintf($textbotlang['Admin']['Payment']['detailRow'], $paymentUser['id_order'], $paymentUser['id_user'], $paymentUser['price'], $paymentUser['payment_Status'], $paymentUser['Payment_Method'], $paymentUser['time']);
        sendmessage($from_id, $text_order, null, 'HTML');
    }
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['sendPaymentList'], $keyboardadmin, 'HTML');
} elseif (preg_match('/affiliates-(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    $affiliatesUsers = select("user", "*", "affiliates", $iduser, "fetchAll");
    if (!$affiliatesUsers) {
        sendmessage($from_id, $textbotlang['Admin']['affiliates']['noReferrals'], null, 'HTML');
        return;
    }
    $count = 0;
    $text_affiliates = "";
    foreach ($affiliatesUsers as $affiliatesUser) {
        $text_affiliates .= "<code>{$affiliatesUser['id']}</code>\n\r";
        $count++;
        if ($count == 10) {
            sendmessage($from_id, $text_affiliates, null, 'HTML');
            $count = 0;
            $text_affiliates = "";
        }
    }
    sendmessage($from_id, $text_affiliates, null, 'HTML');
    sendmessage($from_id, $textbotlang['Admin']['affiliates']['idsSent'], $keyboardadmin, 'HTML');
} elseif (preg_match('/removeaffiliate-(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    $user2 = select("user", "*", "id", $iduser, "select");
    $user2 = select("user", "*", "id", $user2['affiliates'], "select");
    $affiliatescount = intval($user2['affiliatescount']) - 1;
    update("user", "affiliatescount", $affiliatescount, "id", $user2['id']);
    update("user", "affiliates", "0", "id", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['affiliates']['userRemoved'], $keyboardadmin, 'HTML');
} elseif (preg_match('/removeaffiliateuser-(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    update("user", "affiliatescount", "0", "id", $iduser);
    update("user", "affiliates", "0", "affiliates", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['affiliates']['referralsDeleted'], $keyboardadmin, 'HTML');
} elseif (preg_match('/removeservice-(.*)/', $datain, $dataget)) {
    $username = $dataget[1];
    $info_product = select("invoice", "*", "id_invoice", $username, "select");
    $DataUserOut = $ManagePanel->DataUser($info_product['Service_location'], $info_product['username']);
    $ManagePanel->RemoveUser($info_product['Service_location'], $info_product['username']);
    update('invoice', 'status', 'removebyadmin', 'id_invoice', $username);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['removedService'], $keyboardadmin, 'HTML');
    Editmessagetext($from_id, $message_id, $text_inline, json_encode(['inline_keyboard' => []]));
    step('home', $from_id);
} elseif (preg_match('/removeserviceandback-(\w+)/', $datain, $dataget)) {
    $username = $dataget[1];
    $info_product = select("invoice", "*", "id_invoice", $username, "select");
    if ($info_product['Status'] == "removebyadmin") {
        sendmessage($from_id, $textbotlang['Admin']['order']['alreadyDeleted'], $keyboardadmin, 'HTML');
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($info_product['Service_location'], $info_product['username']);
    if (isset($DataUserOut['msg']) && $DataUserOut['msg'] == "User not found") {
        sendmessage($from_id, $textbotlang['users']['status']['userNotFound'], null, 'html');
    } else {
        if ($DataUserOut['status'] == "Unsuccessful") {
            sendmessage($from_id, $textbotlang['Admin']['errorOccurred'], $keyboardadmin, 'HTML');
        }
    }
    $ManagePanel->RemoveUser($info_product['Service_location'], $info_product['username']);
    update('invoice', 'status', 'removebyadmin', 'id_invoice', $username);
    $Balance_user = select("user", "*", "id", $info_product['id_user'], "select");
    $Balance_add_user = $Balance_user['Balance'] + $info_product['price_product'];
    update("user", "Balance", $Balance_add_user, "id", $info_product['id_user']);
    $textadd = sprintf($textbotlang['users']['Balance']['addedNotice2'], $info_product['price_product']);
    sendmessage($info_product['id_user'], $textadd, null, 'HTML');
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['removedService'], $keyboardadmin, 'HTML');
    Editmessagetext($from_id, $message_id, $text_inline, json_encode(['inline_keyboard' => []]));
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['createDiscountCode'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Discountsell']['getCode'], $backadmin, 'HTML');
    step('get_codesell', $from_id);
} elseif ($user['step'] == "get_codesell") {
    if (!preg_match('/^[A-Za-z\d]+$/', $text)) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['errorCode'], null, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Discount']['priceCodeSell'], null, 'HTML');
    step('get_price_codesell', $from_id);
    savedata("clear", "code", strtolower($text));
} elseif ($user['step'] == "get_price_codesell") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['invalidPrice'], $backadmin, 'HTML');
        return;
    }
    savedata("save", "price", $text);
    sendmessage($from_id, $textbotlang['Admin']['Discountsell']['getLimit'], $backadmin, 'HTML');
    step('getlimitcode', $from_id);
} elseif ($user['step'] == "getlimitcode") {
    savedata("save", "limitDiscount", $text);
    sendmessage($from_id, $textbotlang['Admin']['Discount']['agentCode'], $backadmin, 'HTML');
    step('gettypecodeagent', $from_id);
} elseif ($user['step'] == "gettypecodeagent") {
    $agentst = ["n", "n2", "f", "allusers"];
    if (!in_array($text, $agentst)) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['invalidAgentCode'], $backadmin, 'HTML');
        return;
    }
    savedata("save", "agent", $text);
    sendmessage($from_id, $textbotlang['Admin']['Discount']['askActiveHours'], $backadmin, 'HTML');
    step('gettimediscount', $from_id);
} elseif ($user['step'] == "gettimediscount") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    if (intval($text) == 0) {
        $text = "0";
    } else {
        $text = time() + (intval($text) * 3600);
    }
    savedata("save", "time", $text);
    $keyboarddiscount = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['allPurchases'], 'callback_data' => "discountlimitbuy_0"],
                ['text' => $textbotlang['keyboard']['firstPurchaseBtn'], 'callback_data' => "discountlimitbuy_1"],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Discount']['firstDiscount'], $keyboarddiscount, 'HTML');
    step('getfirstdiscount', $from_id);
} elseif (preg_match('/discountlimitbuy_(\w+)/', $datain, $dataget)) {
    $discountbuylimit = $dataget[1];
    savedata("save", "usefirst", $discountbuylimit);
    if (intval($discountbuylimit) == 1) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['askUserLimit'], $backadmin, 'HTML');
        step('getuseuser', $from_id);
        savedata("save", "typediscount", "all");
    } else {
        $keyboarddiscount = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['keyboard']['purchase'], 'callback_data' => "discounttype_buy"],
                    ['text' => $textbotlang['keyboard']['renew'], 'callback_data' => "discounttype_extend"],
                ],
                [
                    ['text' => $textbotlang['keyboard']['both'], 'callback_data' => "discounttype_all"]
                ]
            ]
        ]);
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Discount']['askSection'], $keyboarddiscount);
    }
} elseif (preg_match('/discounttype_(\w+)/', $datain, $dataget)) {
    $discountbuytype = $dataget[1];
    Editmessagetext($from_id, $message_id, $text_inline, json_encode(['inline_keyboard' => []]));
    savedata("save", "typediscount", $discountbuytype);
    sendmessage($from_id, $textbotlang['Admin']['Discount']['askUserLimit'], $backadmin, 'HTML');
    step('getuseuser', $from_id);
} elseif ($user['step'] == "getuseuser") {
    $userdata = json_decode($user['Processing_value'], true);
    $numberlimit = $userdata['limitDiscount'];
    if (intval($text) > intval($userdata['limitDiscount'])) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['userLimitTooHigh'], $backadmin, 'HTML');
        return;
    }
    savedata("save", "useuser", $text);
    sendmessage($from_id, $textbotlang['Admin']['Discount']['askProductLocation'], $json_list_marzban_panel, 'HTML');
    step('getlocdiscount', $from_id);
} elseif ($user['step'] == "getlocdiscount") {
    if ($text == "/all") {
        $panel['code_panel'] = "/all";
    } else {
        $panel = select("marzban_panel", "*", "name_panel", $text, "select");
    }
    if ($panel == false)
        return;
    savedata("save", "code_panel", $panel['code_panel']);
    savedata("save", "name_panel", $text);
    sendmessage($from_id, $textbotlang['Admin']['Discount']['askProduct'], $json_list_product_list_admin, 'HTML');
    step('getproductdiscount', $from_id);
} elseif ($user['step'] == "getproductdiscount") {
    if ($text != "all") {
        $product = select("product", "*", "name_product", $text, "select");
    } else {
        $product['code_product'] = "all";
    }
    if ($product == false) {
        sendmessage($from_id, $textbotlang['users']['sell']['errorProduct'], $keyboardadmin, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    $stmt = $pdo->prepare("INSERT INTO DiscountSell (codeDiscount, usedDiscount, price, limitDiscount, agent, usefirst, useuser, code_panel, code_product, time,type) VALUES (:codeDiscount, :usedDiscount, :price, :limitDiscount, :agent, :usefirst, :useuser, :code_panel, :code_product, :time,:type)");
    $values = "0";
    $values1 = "1";
    $code_product = "0";
    $stmt->bindParam(':codeDiscount', $userdata['code'], PDO::PARAM_STR);
    $stmt->bindParam(':usedDiscount', $values, PDO::PARAM_STR);
    $stmt->bindParam(':price', $userdata['price'], PDO::PARAM_STR);
    $stmt->bindParam(':limitDiscount', $userdata['limitDiscount'], PDO::PARAM_STR);
    $stmt->bindParam(':agent', $userdata['agent'], PDO::PARAM_STR);
    $stmt->bindParam(':usefirst', $userdata['usefirst'], PDO::PARAM_STR);
    $stmt->bindParam(':useuser', $userdata['useuser'], PDO::PARAM_STR);
    $stmt->bindParam(':code_panel', $userdata['code_panel'], PDO::PARAM_STR);
    $stmt->bindParam(':code_product', $product['code_product'], PDO::PARAM_STR);
    $stmt->bindParam(':time', $userdata['time'], PDO::PARAM_STR);
    $stmt->bindParam(':type', $userdata['typediscount'], PDO::PARAM_STR);
    $stmt->execute();
    $textdiscount = sprintf($textbotlang['Admin']['Discount']['created'], $userdata['code'], $userdata['price'], $userdata['name_panel'], $text, $userdata['agent'], $userdata['limitDiscount']);
    sendmessage($from_id, $textdiscount, $keyboardadmin, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['deleteDiscountCode'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Discount']['removeCode'], $json_list_Discount_list_admin_sell, 'HTML');
    step('remove-Discountsell', $from_id);
} elseif ($user['step'] == "remove-Discountsell") {
    if (!rowExists("DiscountSell", "codeDiscount", $text)) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['notCode'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM Giftcodeconsumed WHERE code = :code");
    $stmt->bindParam(':code', $text, PDO::PARAM_STR);
    $stmt->execute();
    $stmt = $pdo->prepare("DELETE FROM DiscountSell WHERE codeDiscount = :codeDiscount");
    $stmt->bindParam(':codeDiscount', $text, PDO::PARAM_STR);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Discount']['removedCode'], $shopkeyboard, 'HTML');
    step('home', $from_id);
} elseif ($text == "/end") {
    $userdata = json_decode($user['Processing_value'], true);
    $panel = select("marzban_panel", "*", "name_panel", $userdata['name_panel'], "select");
    if ($panel['type'] == "marzneshin") {
        update("user", "Processing_value", $userdata['name_panel'], "id", $from_id);
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['Inbound']['endInbound'], $optionmarzneshin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['Inbound']['endInbound'], $optionMarzban, 'HTML');
    step('home', $from_id);
    return;
} elseif ($text == $textbotlang['keyboard']['setAffiliatePercent'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['affiliates']['askPercent'], $backadmin, 'HTML');
    step('setpercentage', $from_id);
} elseif ($user['step'] == "setpercentage") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['invalidPercent'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['affiliates']['percentSaved'], $affiliates, 'HTML');
    update("setting", "affiliatespercentage", $text);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['setAffiliateBanner']) {
    sendmessage($from_id, $textbotlang['Admin']['affiliates']['askBanner'], $backadmin, 'HTML');
    step('setbanner', $from_id);
} elseif ($user['step'] == "setbanner") {
    if (!$photo) {
        sendmessage($from_id, $textbotlang['Admin']['affiliates']['invalidBanner'], $backadmin, 'HTML');
        return;
    }
    update("affiliates", "id_media", $photoid);
    update("affiliates", "description", $caption);
    sendmessage($from_id, $textbotlang['Admin']['affiliates']['bannerSaved'], $affiliates, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['supportId'] && $adminrulecheck['rule'] == "administrator") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "CartDirect");
    $textcart = sprintf($textbotlang['Admin']['card']['askUsername'], $PaySetting['ValuePay']);
    sendmessage($from_id, $textcart, $backadmin, 'HTML');
    step('CartDirect', $from_id);
} elseif ($user['step'] == "CartDirect") {
    sendmessage($from_id, $textbotlang['Admin']['SettingPayment']['cartDirect'], $CartManage, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "CartDirect");
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['offlineGatewayPv'] && $adminrulecheck['rule'] == "administrator") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "Cartstatuspv")['ValuePay'];
    $card_Statuspv = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $PaySetting, 'callback_data' => $PaySetting],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['cardTitlePv'], $card_Statuspv, 'HTML');
} elseif ($datain == "oncardpv" && $adminrulecheck['rule'] == "administrator") {
    update("PaySetting", "ValuePay", "offcardpv", "NamePay", "Cartstatuspv");
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['cardStatusOffPv'], null);
} elseif ($datain == "offcardpv" && $adminrulecheck['rule'] == "administrator") {
    update("PaySetting", "ValuePay", "oncardpv", "NamePay", "Cartstatuspv");
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['cardStatusOnPv'], null);
} elseif (preg_match('/addbalamceuser_(\w+)/', $datain, $datagetr) && ($adminrulecheck['rule'] == "administrator" || $adminrulecheck['rule'] == "Seller")) {
    $id_order = $datagetr[1];
    $Payment_report = select("Payment_report", "*", "id_order", $id_order, "select");
    update("user", "Processing_value", $id_order, "id", $from_id);
    if ($Payment_report['payment_Status'] == "paid" || $Payment_report['payment_Status'] == "reject") {
        $ff = telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['Admin']['Payment']['reviewedPayment'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
        return;
    }
    update("Payment_report", "payment_Status", "paid", "id_order", $id_order);

    sendmessage($from_id, $textbotlang['Admin']['manageUser']['addBalanceUserDesc'], $backadmin, 'html');
    step('addbalancemanual', $from_id);
    Editmessagetext($from_id, $message_id, $text_inline, null);
} elseif ($user['step'] == "addbalancemanual") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['invalidPrice'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['addBalanceUser'], $keyboardadmin, 'HTML');
    $Payment_report = select("Payment_report", "*", "id_order", $user['Processing_value'], "select");
    $Balance_user = select("user", "*", "id", $Payment_report['id_user'], "select");
    $Balance_add_user = $Balance_user['Balance'] + $text;
    $balanceusers = number_format($text, 0);
    update("user", "Balance", $Balance_add_user, "id", $Payment_report['id_user']);
    $textadd = sprintf($textbotlang['users']['Balance']['addedNotice3'], $balanceusers);
    sendmessage($Payment_report['id_user'], $textadd, null, 'HTML');
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['balanceManualAdd'], $Payment_report['id_user'], $Balance_user['username'], $Payment_report['price'], $text);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $paymentreports,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['purchaseCommission'] && $adminrulecheck['rule'] == "administrator") {
    $marzbancommission = select("affiliates", "*", null, null, "select");
    $keyboardcommission = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbancommission['status_commission'], 'callback_data' => $marzbancommission['status_commission']],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['commission'], $keyboardcommission, 'HTML');
} elseif ($datain == "oncommission") {
    update("affiliates", "status_commission", "offcommission");
    $marzbancommission = select("affiliates", "*", null, null, "select");
    $keyboardcommission = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbancommission['status_commission'], 'callback_data' => $marzbancommission['status_commission']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['commissionOff'], $keyboardcommission);
} elseif ($datain == "offcommission") {
    update("affiliates", "status_commission", "oncommission");
    $marzbancommission = select("affiliates", "*", null, null, "select");
    $keyboardcommission = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbancommission['status_commission'], 'callback_data' => $marzbancommission['status_commission']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['commissionOn'], $keyboardcommission);
} elseif ($text == $textbotlang['keyboard']['startGift'] && $adminrulecheck['rule'] == "administrator") {
    $marzbanDiscountaffiliates = select("affiliates", "*", null, null, "select");
    $keyboardDiscountaffiliates = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbanDiscountaffiliates['Discount'], 'callback_data' => $marzbanDiscountaffiliates['Discount']],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['discountAffiliates'], $keyboardDiscountaffiliates, 'HTML');
} elseif ($datain == "onDiscountaffiliates") {
    update("affiliates", "Discount", "offDiscountaffiliates");
    $marzbanDiscountaffiliates = select("affiliates", "*", null, null, "select");
    $keyboardDiscountaffiliates = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbanDiscountaffiliates['Discount'], 'callback_data' => $marzbanDiscountaffiliates['Discount']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['discountAffiliatesOff'], $keyboardDiscountaffiliates);
} elseif ($datain == "offDiscountaffiliates") {
    update("affiliates", "Discount", "onDiscountaffiliates");
    $marzbanDiscountaffiliates = select("affiliates", "*", null, null, "select");
    $keyboardDiscountaffiliates = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbanDiscountaffiliates['Discount'], 'callback_data' => $marzbanDiscountaffiliates['Discount']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['discountAffiliatesOn'], $keyboardDiscountaffiliates);
} elseif ($text == $textbotlang['keyboard']['startGiftAmount'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['affiliates']['askJoinGift'], $backadmin, 'HTML');
    step('getdiscont', $from_id);
} elseif ($user['step'] == "getdiscont") {
    sendmessage($from_id, $textbotlang['Admin']['affiliates']['joinGiftSaved'], $affiliates, 'HTML');
    update("affiliates", "price_Discount", $text);
    step('home', $from_id);
} elseif ($datain == "mainbalanceaccount" && $adminrulecheck['rule'] == "administrator") {
    $PaySetting = json_decode(select("PaySetting", "ValuePay", "NamePay", "minbalance", "select")[$user['agent']], true);
    $textmin = $textbotlang['Admin']['Balance']['askMinCharge'];
    sendmessage($from_id, $textmin, $backadmin, 'HTML');
    step('minbalance', $from_id);
} elseif ($user['step'] == "minbalance") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    update("user", "Processing_value", $text, "id", $from_id);
    step('getagentbalancemin', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMinChargeGroup'], $backadmin, 'HTML');
} elseif ($user['step'] == "getagentbalancemin") {
    $agentst = ["n", "n2", "f", "allusers"];
    if (!in_array($text, $agentst)) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['invalidAgentCode'], $backadmin, 'HTML');
        return;
    }
    step('home', $from_id);
    $balancemaax = json_decode(select("PaySetting", "ValuePay", "NamePay", "minbalance", "select")['ValuePay'], true);
    $balancemaax[$text] = $user['Processing_value'];
    $balancemaax = json_encode($balancemaax);
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $keyboardadmin, 'HTML');
    update("PaySetting", "ValuePay", $balancemaax, "NamePay", "minbalance");
} elseif ($datain == "maxbalanceaccount" && $adminrulecheck['rule'] == "administrator") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "maxbalance", "select");
    $textmax = $textbotlang['Admin']['Balance']['askMaxCharge'];
    sendmessage($from_id, $textmax, $backadmin, 'HTML');
    step('maxbalance', $from_id);
} elseif ($user['step'] == "maxbalance") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    update("user", "Processing_value", $text, "id", $from_id);
    step('getagentbalancemax', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMinChargeGroup'], $backadmin, 'HTML');
} elseif ($user['step'] == "getagentbalancemax") {
    $agentst = ["n", "n2", "f", "allusers"];
    if (!in_array($text, $agentst)) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['invalidAgentCode'], $backadmin, 'HTML');
        return;
    }
    step('home', $from_id);
    $balancemaax = json_decode(select("PaySetting", "ValuePay", "NamePay", "maxbalance", "select")['ValuePay'], true);
    $balancemaax[$text] = $user['Processing_value'];
    $balancemaax = json_encode($balancemaax);
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $keyboardadmin, 'HTML');
    update("PaySetting", "ValuePay", $balancemaax, "NamePay", "maxbalance");
} elseif (preg_match('/removeagent_(\w+)/', $datain, $dataget) && $adminrulecheck['rule'] == "administrator") {
    $id_user = $dataget[1];
    telegram('sendmessage', [
        'chat_id' => $from_id,
        'text' => $textbotlang['Admin']['agent']['userAgentRemoved'],
        'parse_mode' => "HTML",
        'reply_to_message_id' => $message_id,
    ]);
    update("user", "agent", "f", "id", $id_user);
    update("user", "pricediscount", "0", "id", $id_user);
    update("user", "expire", null, "id", $id_user);
    $stmt = $pdo->prepare("DELETE FROM Requestagent WHERE id = :mp7");
    $stmt->execute([':mp7' => $id_user]);
    step('home', $from_id);
} elseif (preg_match('/addagent_(\w+)/', $datain, $dataget) && $adminrulecheck['rule'] == "administrator") {
    $id_user = $dataget[1];
    update("user", "Processing_value", $id_user, "id", $from_id);
    telegram('sendmessage', [
        'chat_id' => $from_id,
        'text' => $textbotlang['Admin']['agent']['getTypeAgent'],
        'parse_mode' => "HTML",
        'reply_markup' => $backadmin,
        'reply_to_message_id' => $message_id,
    ]);
    step('gettypeagentoflist', $from_id);
} elseif ($user['step'] == "gettypeagentoflist") {
    $agentst = ["n", "n2"];
    if (!in_array($text, $agentst)) {
        sendmessage($from_id, $textbotlang['Admin']['agent']['invalidTypeAgent'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['agent']['userAgented'], $keyboardadmin, 'HTML');
    update("user", "expire", null, "id", $user['Processing_value']);
    update("user", "agent", $text, "id", $user['Processing_value']);
    step('home', $from_id);
} elseif (preg_match('/Percentlow_(\w+)/', $datain, $dataget) && $adminrulecheck['rule'] == "administrator") {
    $id_user = $dataget[1];
    update("user", "Processing_value", $id_user, "id", $from_id);
    telegram('sendmessage', [
        'chat_id' => $from_id,
        'text' => $textbotlang['keyboard']['discountPercentDesc'],
        'reply_markup' => $backadmin,
        'parse_mode' => "HTML",
        'reply_to_message_id' => $message_id,
    ]);
    step('getpercentuser', $from_id);
} elseif ($user['step'] == "getpercentuser") {
    if (intval($text) > 100 || intval($text) < 0 || !ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $keyboardadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['changesSaved'], $keyboardadmin, 'HTML');
    update("user", "pricediscount", $text, "id", $user['Processing_value']);
    step('home', $from_id);
} elseif (preg_match('/maxbuyagent_(\w+)/', $datain, $dataget) && $adminrulecheck['rule'] == "administrator") {
    $id_user = $dataget[1];
    update("user", "Processing_value", $id_user, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMaxNegative'], $backadmin, 'HTML');
    step('getmaxbuyagent', $from_id);
} elseif ($user['step'] == "getmaxbuyagent") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['changesSaved'], $keyboardadmin, 'HTML');
    update("user", "maxbuyagent", $text, "id", $user['Processing_value']);
    step('home', $from_id);
} elseif ($datain == "searchorder") {
    sendmessage($from_id, $textbotlang['Admin']['order']['viewOrderUsername'], $backadmin, 'HTML');
    step('GetusernameconfigAndOrdedrs', $from_id);
} elseif ($user['step'] == "GetusernameconfigAndOrdedrs" || strpos($text, "/config ") !== false || preg_match('/manageinvoice_(\w+)/', $datain, $datagetr)) {
    if ($user['step'] == "GetusernameconfigAndOrdedrs") {
        $usernameconfig = $text;
        $sql = "SELECT * FROM invoice WHERE username LIKE CONCAT('%', :username, '%') OR note  LIKE CONCAT('%', :notes, '%')";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $usernameconfig, PDO::PARAM_STR);
        $stmt->bindParam(':notes', $usernameconfig, PDO::PARAM_STR);
    } elseif (isset($text[0]) && $text[0] == "/") {
        $usernameconfig = explode(" ", $text)[1] ?? '';
        if ($usernameconfig === '') {
            return;
        }
        $sql = "SELECT * FROM invoice WHERE username LIKE CONCAT('%', :username, '%') OR note  LIKE CONCAT('%', :notes, '%')";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $usernameconfig, PDO::PARAM_STR);
        $stmt->bindParam(':notes', $usernameconfig, PDO::PARAM_STR);
    } else {
        $usernameconfig = select("invoice", "*", "id_invoice", $datagetr[1], "select")['username'];
        $sql = "SELECT * FROM invoice WHERE username = :username OR note  = :notes";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $usernameconfig, PDO::PARAM_STR);
        $stmt->bindParam(':notes', $usernameconfig, PDO::PARAM_STR);
    }
    $stmt->execute();
    step("home", $from_id);
    if ($stmt->rowCount() > 1) {
        $keyboardlists = [
            'inline_keyboard' => [],
        ];
        $keyboardlists['inline_keyboard'][] = [
            ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
            ['text' => $textbotlang['keyboard']['serviceStatus'], 'callback_data' => "Status"],
            ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $keyboardlists['inline_keyboard'][] = [
                [
                    'text' => $textbotlang['keyboard']['viewInfo'],
                    'callback_data' => "manageinvoice_" . $row['id_invoice']
                ],
                [
                    'text' => $row['Status'],
                    'callback_data' => "username"
                ],
                [
                    'text' => $row['username'],
                    'callback_data' => $row['username']
                ],
            ];
        }
        $keyboardlists = json_encode($keyboardlists);
        sendmessage($from_id, $textbotlang['Admin']['order']['selectService'], $keyboardlists, 'HTML');
        return;
    }
    $OrderUser = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$OrderUser) {
        sendmessage($from_id, $textbotlang['Admin']['order']['notFound'], null, 'HTML');
        return;
    }
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['refresh'], 'callback_data' => "manageinvoice_" . $OrderUser['id_invoice']],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['Admin']['manageUser']['removeService'], 'callback_data' => "removeservice-" . $OrderUser['id_invoice']],
        ['text' => $textbotlang['Admin']['manageUser']['removeServiceAndBack'], 'callback_data' => "removeserviceandback-" . $OrderUser['id_invoice']],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['deleteServiceFull'], 'callback_data' => "removefull-" . $OrderUser['id_invoice']],
    ];
    if (isset($OrderUser['time_sell'])) {
        $datatime = jdate('Y/m/d H:i:s', $OrderUser['time_sell']);
    } else {
        $datatime = $textbotlang['Admin']['manageUser']['dataorder'];
    }
    if ($OrderUser['name_product'] == $textbotlang['common']['labels']['testServiceName']) {
        $OrderUser['Service_time'] = $OrderUser['Service_time'] . $textbotlang['Admin']['unit']['hours'];
        $OrderUser['Volume'] = $OrderUser['Volume'] . $textbotlang['Admin']['unit']['megabytes'];
    } else {
        $OrderUser['Service_time'] = $OrderUser['Service_time'] . $textbotlang['Admin']['unit']['days'];
        $OrderUser['Volume'] = $OrderUser['Volume'] . $textbotlang['Admin']['unit']['gigabytes'];
    }
    $stmt = $pdo->prepare("SELECT value FROM service_other WHERE username = :username AND type = 'extend_user' AND status = 'paid' ORDER BY time DESC LIMIT 20");
    $stmt->execute([
        ':username' => $OrderUser['username'],
    ]);
    if ($stmt->rowCount() != 0) {
        $service_other = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!($service_other == false || !(is_string($service_other['value']) && is_array(json_decode($service_other['value'], true))))) {
            $service_other = json_decode($service_other['value'], true);
            $codeproduct = select("product", "*", "code_product", $service_other['code_product'], "select");
            if ($codeproduct != false) {
                $OrderUser['name_product'] = $codeproduct['name_product'] ?? $OrderUser['name_product'];
                $OrderUser['Volume'] = $codeproduct['Volume_constraint'] ?? $OrderUser['Volume'];
                $OrderUser['Service_time'] = $codeproduct['Service_time'] ?? $OrderUser['Service_time'];
            }
        }
    }
    $text_order = sprintf($textbotlang['Admin']['order']['detailRow'], $OrderUser['id_invoice'], $OrderUser['Status'], $OrderUser['id_user'], $OrderUser['username'], $OrderUser['Service_location'], $OrderUser['name_product'], $OrderUser['price_product'], $OrderUser['Volume'], $OrderUser['Service_time'], $datatime);
    $DataUserOut = $ManagePanel->DataUser($OrderUser['Service_location'], $OrderUser['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        $keyboard_json = json_encode($keyboardlists);
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['userNotInPanel'], $keyboardadmin, 'html');
        sendmessage($from_id, $text_order, $keyboard_json, 'HTML');
        step('home', $from_id);
        return;
    }
    if ($DataUserOut['online_at'] == "online") {
        $lastonline = $textbotlang['Admin']['connection']['online'];
    } elseif ($DataUserOut['online_at'] == "offline") {
        $lastonline = $textbotlang['Admin']['connection']['offline'];
    } else {
        if (isset($DataUserOut['online_at']) && $DataUserOut['online_at'] !== null) {
            $dateString = $DataUserOut['online_at'];
            $lastonline = jdate('Y/m/d H:i:s', strtotime($dateString));
        } else {
            $lastonline = $textbotlang['Admin']['connection']['notConnected'];
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
    $RemainingVolume = $DataUserOut['data_limit'] ? formatBytes($output) : $textbotlang['users']['status']['unlimited'];
    #---------------[ used_traffic ]--------------#
    $usedTrafficGb = $DataUserOut['used_traffic'] ? formatBytes($DataUserOut['used_traffic']) : $textbotlang['users']['status']['notConsumed'];
    #--------------[ day ]---------------#
    $timeDiff = $DataUserOut['expire'] - time();
    $day = $DataUserOut['expire'] ? floor($timeDiff / 86400) . $textbotlang['users']['status']['day'] : $textbotlang['users']['status']['unlimited'];
    #--------------[ subsupdate ]---------------#
    $lastupdate = "";
    if ($DataUserOut['sub_updated_at'] !== null) {
        $sub_updated = $DataUserOut['sub_updated_at'];
        $dateTime = new DateTime($sub_updated, new DateTimeZone('UTC'));
        $dateTime->setTimezone(new DateTimeZone('Asia/Tehran'));
        $lastupdate = jdate('Y/m/d H:i:s', $dateTime->getTimestamp());
    }
    $limitValue = isset($DataUserOut['data_limit']) ? (float) $DataUserOut['data_limit'] : 0;
    $usedTrafficValue = isset($DataUserOut['used_traffic']) ? (float) $DataUserOut['used_traffic'] : 0;
    if ($limitValue > 0) {
        $Percent = (($limitValue - $usedTrafficValue) * 100) / $limitValue;
    } else {
        $Percent = 100;
    }
    if ($Percent < 0) {
        $Percent = -$Percent;
    }
    $Percent = round($Percent, 2);
    $text_order .= sprintf($textbotlang['Admin']['order']['serviceSummary'], $status_var, $LastTraffic, $usedTrafficGb, $RemainingVolume, $Percent, $expirationDate, $day, $DataUserOut['subscription_url'], $lastonline, $lastupdate, $DataUserOut['sub_last_user_agent']);
    if ($DataUserOut['status'] == "active") {
        $namestatus = $textbotlang['Admin']['manageUser']['btnDisableAccount'];
    } else {
        $namestatus = $textbotlang['keyboard']['activateAccount'];
    }
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['users']['extend']['title'], 'callback_data' => 'extendadmin_' . $OrderUser['id_invoice']],
        ['text' => $textbotlang['users']['status']['config'], 'callback_data' => 'config_' . $OrderUser['id_invoice']],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $namestatus, 'callback_data' => 'changestatusadmin_' . $OrderUser['id_invoice']],
    ];
    $keyboard_json = json_encode($keyboardlists);
    sendmessage($from_id, $text_order, $keyboard_json, 'HTML');
    $stmt = $pdo->prepare("SELECT * FROM service_other s WHERE username = :username AND (status = 'paid' OR status IS NULL)");
    $stmt->bindParam(':username', $usernameconfig, PDO::PARAM_STR);
    $stmt->execute();
    $list_service = $stmt->fetchAll();
    if ($list_service) {
        foreach ($list_service as $extend) {
            $extend_type = [
                'extend_user' => $textbotlang['keyboard']['renew'],
                'extend_user_by_admin' => $textbotlang['Admin']['order']['typeAdminRenew'],
                'extra_user' => $textbotlang['Admin']['order']['typeExtraVolume'],
                "extra_time_user" => $textbotlang['Admin']['order']['typeExtraTime'],
                "transfertouser" => $textbotlang['Admin']['order']['typeTransfer'],
                "extends_not_user" => $textbotlang['Admin']['order']['typeRenewNotInList'],
                "change_location" => $textbotlang['Admin']['order']['typeChangeLocation'],
                'gift_time' => $textbotlang['Admin']['order']['typeGiftTime'],
                'gift_volume' => $textbotlang['Admin']['order']['typeGiftVolume']
            ][$extend['type']];
            $time_jalali = jdate('Y/m/d H:i:s', strtotime($extend['time']));

            $extendtext = sprintf($textbotlang['Admin']['order']['serviceReport'], $extend_type, $extend['time'], $time_jalali, $extend['price'], $extend['id_user'], $extend['username']);
            sendmessage($from_id, $extendtext, null, 'HTML');
        }
    }
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['shopFeatureStatus'] && $adminrulecheck['rule'] == "administrator") {
    $marzbanstatusextra = select("shopSetting", "*", "Namevalue", "statusextra", "select")['value'];
    $marzbandirectpay = select("shopSetting", "*", "Namevalue", "statusdirectpabuy", "select")['value'];
    $statustimeextra = select("shopSetting", "*", "Namevalue", "statustimeextra", "select")['value'];
    $statusdisorder = select("shopSetting", "*", "Namevalue", "statusdisorder", "select")['value'];
    $statuschangeservice = select("shopSetting", "*", "Namevalue", "statuschangeservice", "select")['value'];
    $statusshowprice = select("shopSetting", "*", "Namevalue", "statusshowprice", "select")['value'];
    $statusshowconfig = select("shopSetting", "*", "Namevalue", "configshow", "select")['value'];
    $statusremoveserveice = select("shopSetting", "*", "Namevalue", "backserviecstatus", "select")['value'];
    $name_status_extra_Vloume = [
        'onextra' => $textbotlang['Admin']['Status']['statuson'],
        'offextra' => $textbotlang['Admin']['Status']['statusoff']
    ][$marzbanstatusextra];
    $name_status_paydirect = [
        'ondirectbuy' => $textbotlang['Admin']['Status']['statuson'],
        'offdirectbuy' => $textbotlang['Admin']['Status']['statusoff']
    ][$marzbandirectpay];
    $name_status_timeextra = [
        'ontimeextraa' => $textbotlang['Admin']['Status']['statuson'],
        'offtimeextraa' => $textbotlang['Admin']['Status']['statusoff']
    ][$statustimeextra];
    $name_status_disorder = [
        'ondisorder' => $textbotlang['Admin']['Status']['statuson'],
        'offdisorder' => $textbotlang['Admin']['Status']['statusoff']
    ][$statusdisorder];
    $categorygenral = [
        'oncategorys' => $textbotlang['Admin']['Status']['statuson'],
        'offcategorys' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statuscategorygenral']];
    $statustextchange = [
        'onstatus' => $textbotlang['Admin']['Status']['statuson'],
        'offstatus' => $textbotlang['Admin']['Status']['statusoff']
    ][$statuschangeservice];
    $statusshowpricestext = [
        'onshowprice' => $textbotlang['Admin']['Status']['statuson'],
        'offshowprice' => $textbotlang['Admin']['Status']['statusoff']
    ][$statusshowprice];
    $statusshowconfigtext = [
        'onconfig' => $textbotlang['Admin']['Status']['statuson'],
        'offconfig' => $textbotlang['Admin']['Status']['statusoff']
    ][$statusshowconfig];
    $statusbackremovetext = [
        'on' => $textbotlang['Admin']['Status']['statuson'],
        'off' => $textbotlang['Admin']['Status']['statusoff']
    ][$statusremoveserveice];
    $name_status_categorytime = [
        'oncategory' => $textbotlang['Admin']['Status']['statuson'],
        'offcategory' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statuscategory']];
    $Bot_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['Status']['statusSubject'], 'callback_data' => "subjectde"],
                ['text' => $textbotlang['Admin']['Status']['subject'], 'callback_data' => "subject"],
            ],
            [
                ['text' => $name_status_extra_Vloume, 'callback_data' => "editshops-extravolunme-$marzbanstatusextra"],
                ['text' => $textbotlang['Admin']['Status']['statusVolumeExtra'], 'callback_data' => "extravolunme"],
            ],
            [
                ['text' => $name_status_paydirect, 'callback_data' => "editshops-paydirect-$marzbandirectpay"],
                ['text' => $textbotlang['Admin']['Status']['paydirect'], 'callback_data' => "paydirect"],
            ],
            [
                ['text' => $name_status_timeextra, 'callback_data' => "editshops-statustimeextra-$statustimeextra"],
                ['text' => $textbotlang['Admin']['Status']['statusTimeExtra'], 'callback_data' => "statustimeextra"],
            ],
            [
                ['text' => $name_status_disorder, 'callback_data' => "editshops-disorderss-$statusdisorder"],
                ['text' => $textbotlang['keyboard']['sendDisruptionReport'], 'callback_data' => "disorderss"],
            ],
            [
                ['text' => $categorygenral, 'callback_data' => "editshops-categroygenral-" . $setting['statuscategorygenral']],
                ['text' => $textbotlang['keyboard']['categoryBug'], 'callback_data' => "categroygenral"],
            ],
            [
                ['text' => $name_status_categorytime, 'callback_data' => "editshops-categorytime-{$setting['statuscategory']}"],
                ['text' => $textbotlang['Admin']['Status']['statusCategoryTime'], 'callback_data' => "statuscategorytime"],
            ],
            [
                ['text' => $statustextchange, 'callback_data' => "editshops-changgestatus-" . $statuschangeservice],
                ['text' => $textbotlang['keyboard']['deactivateAccountStatus'], 'callback_data' => "changgestatus"],
            ],
            [
                ['text' => $statusshowpricestext, 'callback_data' => "editshops-showprice-" . $statusshowprice],
                ['text' => $textbotlang['keyboard']['showProductPrice'], 'callback_data' => "showprice"],
            ],
            [
                ['text' => $statusshowconfigtext, 'callback_data' => "editshops-showconfig-" . $statusshowconfig],
                ['text' => $textbotlang['keyboard']['getConfigBtn'], 'callback_data' => "config"],
            ],
            [
                ['text' => $statusbackremovetext, 'callback_data' => "editshops-removeservicebackbtn-" . $statusremoveserveice],
                ['text' => $textbotlang['keyboard']['refundBtn'], 'callback_data' => "removeservicebackbtn"],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['botTitle'], $Bot_Status, 'HTML');
} elseif (preg_match('/^editshops-(.*)-(.*)/', $datain, $dataget)) {
    $type = $dataget[1];
    $value = $dataget[2];
    if ($type == "extravolunme") {
        if ($value == "onextra") {
            $valuenew = "offextra";
        } else {
            $valuenew = "onextra";
        }
        update("shopSetting", "value", $valuenew, "Namevalue", "statusextra");
    } elseif ($type == "paydirect") {
        if ($value == "ondirectbuy") {
            $valuenew = "offdirectbuy";
        } else {
            $valuenew = "ondirectbuy";
        }
        update("shopSetting", "value", $valuenew, "Namevalue", "statusdirectpabuy");
    } elseif ($type == "statustimeextra") {
        if ($value == "ontimeextraa") {
            $valuenew = "offtimeextraa";
        } else {
            $valuenew = "ontimeextraa";
        }
        update("shopSetting", "value", $valuenew, "Namevalue", "statustimeextra");
    } elseif ($type == "disorderss") {
        if ($value == "ondisorder") {
            $valuenew = "offdisorder";
        } else {
            $valuenew = "ondisorder";
        }
        update("shopSetting", "value", $valuenew, "Namevalue", "statusdisorder");
    } elseif ($type == "categroygenral") {
        if ($value == "oncategorys") {
            $valuenew = "offcategorys";
        } else {
            $valuenew = "oncategorys";
        }
        update("setting", "statuscategorygenral", $valuenew, null, null);
    } elseif ($type == "changgestatus") {
        if ($value == "onstatus") {
            $valuenew = "offstatus";
        } else {
            $valuenew = "onstatus";
        }
        update("shopSetting", "value", $valuenew, "Namevalue", "statuschangeservice");
    } elseif ($type == "showprice") {
        if ($value == "onshowprice") {
            $valuenew = "offshowprice";
        } else {
            $valuenew = "onshowprice";
        }
        update("shopSetting", "value", $valuenew, "Namevalue", "statusshowprice");
    } elseif ($type == "showconfig") {
        if ($value == "onconfig") {
            $valuenew = "offconfig";
        } else {
            $valuenew = "onconfig";
        }
        update("shopSetting", "value", $valuenew, "Namevalue", "configshow");
    } elseif ($type == "removeservicebackbtn") {
        if ($value == "on") {
            $valuenew = "off";
        } else {
            $valuenew = "on";
        }
        update("shopSetting", "value", $valuenew, "Namevalue", "backserviecstatus");
    } elseif ($type == "categorytime") {
        if ($value == "oncategory") {
            $valuenew = "offcategory";
        } else {
            $valuenew = "oncategory";
        }
        update("setting", "statuscategory", $valuenew);
    }
    $setting = select("setting", "*", null, null, "select");
    $marzbanstatusextra = select("shopSetting", "*", "Namevalue", "statusextra", "select")['value'];
    $marzbandirectpay = select("shopSetting", "*", "Namevalue", "statusdirectpabuy", "select")['value'];
    $statustimeextra = select("shopSetting", "*", "Namevalue", "statustimeextra", "select")['value'];
    $statusdisorder = select("shopSetting", "*", "Namevalue", "statusdisorder", "select")['value'];
    $statuschangeservice = select("shopSetting", "*", "Namevalue", "statuschangeservice", "select")['value'];
    $statusshowprice = select("shopSetting", "*", "Namevalue", "statusshowprice", "select")['value'];
    $statusshowconfig = select("shopSetting", "*", "Namevalue", "configshow", "select")['value'];
    $statusremoveserveice = select("shopSetting", "*", "Namevalue", "backserviecstatus", "select")['value'];
    $name_status_extra_Vloume = [
        'onextra' => $textbotlang['Admin']['Status']['statuson'],
        'offextra' => $textbotlang['Admin']['Status']['statusoff']
    ][$marzbanstatusextra];
    $name_status_paydirect = [
        'ondirectbuy' => $textbotlang['Admin']['Status']['statuson'],
        'offdirectbuy' => $textbotlang['Admin']['Status']['statusoff']
    ][$marzbandirectpay];
    $name_status_timeextra = [
        'ontimeextraa' => $textbotlang['Admin']['Status']['statuson'],
        'offtimeextraa' => $textbotlang['Admin']['Status']['statusoff']
    ][$statustimeextra];
    $name_status_disorder = [
        'ondisorder' => $textbotlang['Admin']['Status']['statuson'],
        'offdisorder' => $textbotlang['Admin']['Status']['statusoff']
    ][$statusdisorder];
    $categorygenral = [
        'oncategorys' => $textbotlang['Admin']['Status']['statuson'],
        'offcategorys' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statuscategorygenral']];
    $statustextchange = [
        'onstatus' => $textbotlang['Admin']['Status']['statuson'],
        'offstatus' => $textbotlang['Admin']['Status']['statusoff']
    ][$statuschangeservice];
    $statusshowpricestext = [
        'onshowprice' => $textbotlang['Admin']['Status']['statuson'],
        'offshowprice' => $textbotlang['Admin']['Status']['statusoff']
    ][$statusshowprice];
    $statusshowconfigtext = [
        'onconfig' => $textbotlang['Admin']['Status']['statuson'],
        'offconfig' => $textbotlang['Admin']['Status']['statusoff']
    ][$statusshowconfig];
    $statusbackremovetext = [
        'on' => $textbotlang['Admin']['Status']['statuson'],
        'off' => $textbotlang['Admin']['Status']['statusoff']
    ][$statusremoveserveice];
    $name_status_categorytime = [
        'oncategory' => $textbotlang['Admin']['Status']['statuson'],
        'offcategory' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statuscategory']];
    $Bot_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['Status']['statusSubject'], 'callback_data' => "subjectde"],
                ['text' => $textbotlang['Admin']['Status']['subject'], 'callback_data' => "subject"],
            ],
            [
                ['text' => $name_status_extra_Vloume, 'callback_data' => "editshops-extravolunme-$marzbanstatusextra"],
                ['text' => $textbotlang['Admin']['Status']['statusVolumeExtra'], 'callback_data' => "extravolunme"],
            ],
            [
                ['text' => $name_status_paydirect, 'callback_data' => "editshops-paydirect-$marzbandirectpay"],
                ['text' => $textbotlang['Admin']['Status']['paydirect'], 'callback_data' => "paydirect"],
            ],
            [
                ['text' => $name_status_timeextra, 'callback_data' => "editshops-statustimeextra-$statustimeextra"],
                ['text' => $textbotlang['Admin']['Status']['statusTimeExtra'], 'callback_data' => "statustimeextra"],
            ],
            [
                ['text' => $name_status_disorder, 'callback_data' => "editshops-disorderss-$statusdisorder"],
                ['text' => $textbotlang['keyboard']['sendDisruptionReport'], 'callback_data' => "disorderss"],
            ],
            [
                ['text' => $categorygenral, 'callback_data' => "editshops-categroygenral-" . $setting['statuscategorygenral']],
                ['text' => $textbotlang['keyboard']['categoryBug'], 'callback_data' => "categroygenral"],
            ],
            [
                ['text' => $name_status_categorytime, 'callback_data' => "editshops-categorytime-{$setting['statuscategory']}"],
                ['text' => $textbotlang['Admin']['Status']['statusCategoryTime'], 'callback_data' => "statuscategorytime"],
            ],
            [
                ['text' => $statustextchange, 'callback_data' => "editshops-changgestatus-" . $statuschangeservice],
                ['text' => $textbotlang['keyboard']['deactivateAccountStatus'], 'callback_data' => "changgestatus"],
            ],
            [
                ['text' => $statusshowpricestext, 'callback_data' => "editshops-showprice-" . $statusshowprice],
                ['text' => $textbotlang['keyboard']['showProductPrice'], 'callback_data' => "showprice"],
            ],
            [
                ['text' => $statusshowconfigtext, 'callback_data' => "editshops-showconfig-" . $statusshowconfig],
                ['text' => $textbotlang['keyboard']['getConfigBtn'], 'callback_data' => "config"],
            ],
            [
                ['text' => $statusbackremovetext, 'callback_data' => "editshops-removeservicebackbtn-" . $statusremoveserveice],
                ['text' => $textbotlang['keyboard']['refundBtn'], 'callback_data' => "removeservicebackbtn"],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['botTitle'], $Bot_Status);
} elseif ($text == $textbotlang['Admin']['report']['btnExport'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboardexportdata, 'HTML');
} elseif ($text == $textbotlang['Admin']['cronjob']['btnSettings'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $setting_panel, 'HTML');
} elseif ($text == $textbotlang['keyboard']['exportUsers'] && $adminrulecheck['rule'] == "administrator") {
    $counttable = select("user", "*", null, null, "count");
    if ($counttable == 0) {
        sendmessage($from_id, $textbotlang['Admin']['report']['noDataToExport'], null, 'HTML');
        return;
    }
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sql = "SELECT * FROM user";
    $result = $pdo->query($sql);

    $col = 1;
    $headers = array_keys($result->fetch(PDO::FETCH_ASSOC));
    foreach ($headers as $header) {
        $sheet->setCellValue([$col, 1], $header);
        $col++;
    }

    $row = 2;
    while ($row_data = $result->fetch(PDO::FETCH_ASSOC)) {
        $col = 1;
        foreach ($row_data as $value) {
            $sheet->setCellValue([$col, $row], $value);
            $col++;
        }
        $row++;
    }
    $date = date("Y-m-d");
    $filename = "users_{$date}.xlsx";
    $writer = new Xlsx($spreadsheet);
    $writer->save($filename);
    sendDocument($from_id, $filename, $textbotlang['Admin']['report']['btnExportUsers']);
    unlink($filename);
} elseif ($text == $textbotlang['keyboard']['exportOrders'] && $adminrulecheck['rule'] == "administrator") {
    $counttable = select("invoice", "*", null, null, "count");
    if ($counttable == 0) {
        sendmessage($from_id, $textbotlang['Admin']['report']['noDataToExport'], null, 'HTML');
        return;
    }
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sql = "SELECT * FROM invoice";
    $result = $pdo->query($sql);

    $col = 1;
    $headers = array_keys($result->fetch(PDO::FETCH_ASSOC));
    foreach ($headers as $header) {
        $sheet->setCellValue([$col, 1], $header);
        $col++;
    }

    $row = 2;
    while ($row_data = $result->fetch(PDO::FETCH_ASSOC)) {
        $col = 1;
        foreach ($row_data as $value) {
            $sheet->setCellValue([$col, $row], $value);
            $col++;
        }
        $row++;
    }
    $date = date("Y-m-d");
    $filename = "invoice_{$date}.xlsx";
    $writer = new Xlsx($spreadsheet);
    $writer->save($filename);
    sendDocument($from_id, $filename, $textbotlang['Admin']['report']['btnExportOrders']);
    unlink($filename);
} elseif ($text == $textbotlang['keyboard']['exportPayments'] && $adminrulecheck['rule'] == "administrator") {
    $counttable = select("Payment_report", "*", null, null, "count");
    if ($counttable == 0) {
        sendmessage($from_id, $textbotlang['Admin']['report']['noDataToExport'], null, 'HTML');
        return;
    }
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sql = "SELECT * FROM Payment_report";
    $result = $pdo->query($sql);

    $col = 1;
    $headers = array_keys($result->fetch(PDO::FETCH_ASSOC));
    foreach ($headers as $header) {
        $sheet->setCellValue([$col, 1], $header);
        $col++;
    }

    $row = 2;
    while ($row_data = $result->fetch(PDO::FETCH_ASSOC)) {
        $col = 1;
        foreach ($row_data as $value) {
            $sheet->setCellValue([$col, $row], $value);
            $col++;
        }
        $row++;
    }
    $date = date("Y-m-d");
    $filename = "Payment_report_{$date}.xlsx";
    $writer = new Xlsx($spreadsheet);
    $writer->save($filename);
    sendDocument($from_id, $filename, $textbotlang['Admin']['report']['btnExportPayments']);
    unlink($filename);
} elseif (preg_match('/rejectremoceserviceadmin-(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $invoice = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $requestcheck = select("cancel_service", "*", "username", $invoice['username'], "select");
    if ($requestcheck['status'] == "accept" || $requestcheck['status'] == "reject") {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['keyboard']['alreadyReviewed'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
        return;
    }
    step("descriptionsrequsts", $from_id);
    update("user", "Processing_value", $requestcheck['username'], "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['order']['askRejectReason'], $backuser, 'HTML');
} elseif ($user['step'] == "descriptionsrequsts") {
    sendmessage($from_id, $textbotlang['Admin']['order']['requestRegistered'], $keyboardadmin, 'HTML');
    $nameloc = select("invoice", "*", "username", $user['Processing_value'], "select");
    update("cancel_service", "status", "reject", "username", $user['Processing_value']);
    update("cancel_service", "description", $text, "username", $user['Processing_value']);
    step("home", $from_id);
    sendmessage($nameloc['id_user'], sprintf($textbotlang['users']['status']['deleteRequestRejected'], $user['Processing_value'], $text), null, 'HTML');
} elseif (preg_match('/remoceserviceadmin-(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $invoice = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $requestcheck = select("cancel_service", "*", "username", $invoice['username'], "select");
    if ($requestcheck['status'] == "accept" || $requestcheck['status'] == "reject") {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['keyboard']['alreadyReviewed'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
        return;
    }
    $nameloc = select("invoice", "*", "username", $requestcheck['username'], "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $requestcheck['username']);
    $stmt = $pdo->prepare("SELECT  SUM(price) FROM service_other WHERE username = :username AND type != 'change_location' AND type != 'extend_user' LIMIT 1");
    $stmt->bindParam(':username', $nameloc['username']);
    $stmt->execute();
    $sumproduct = $stmt->fetch(PDO::FETCH_ASSOC);
    if (isset($DataUserOut['msg']) && $DataUserOut['msg'] == "User not found") {
        sendmessage($from_id, $textbotlang['users']['status']['userNotFound'], null, 'html');
        step('home', $from_id);
        return;
    }
    if ($DataUserOut['data_limit'] == null && $DataUserOut['expire'] == null) {
        sendmessage($from_id, $textbotlang['Admin']['cronjob']['cannotDeleteUnlimited'], null, 'html');
        step('home', $from_id);
        return;
    }
    if ($DataUserOut['status'] == "on_hold") {
        $pricelast = $invoice['price_product'];
    } elseif ($DataUserOut['data_limit'] == null) {
        $serviceTime = (float) ($nameloc['Service_time'] ?? 0);
        if ($serviceTime > 0) {
            $pricetime = ($nameloc['price_product'] / $serviceTime) + intval($sumproduct['SUM(price)']);
            $pricelast = (($DataUserOut['expire'] - time()) / 86400) * $pricetime;
        } else {
            $pricelast = 0;
        }
    } elseif ($DataUserOut['expire'] == null) {
        $dataLimit = isset($DataUserOut['data_limit']) ? (float) $DataUserOut['data_limit'] : 0;
        if ($dataLimit > 0) {
            $volumelefts = ($dataLimit - (float) ($DataUserOut['used_traffic'] ?? 0)) / pow(1024, 3);
            $volumeDivisor = $dataLimit / pow(1024, 3);
            $volumeleft = $volumeDivisor > 0 ? $volumelefts / $volumeDivisor : 0;
            $pricelast = round($volumeleft * ($nameloc['price_product'] + intval($sumproduct['SUM(price)'])), 2);
        } else {
            $pricelast = 0;
        }
    } else {
        $serviceTime = (float) ($nameloc['Service_time'] ?? 0);
        $dataLimit = isset($DataUserOut['data_limit']) ? (float) $DataUserOut['data_limit'] : 0;
        $volumeDivisor = $dataLimit / pow(1024, 3);
        if ($serviceTime > 0 && $volumeDivisor > 0) {
            $timeleft = (round(($DataUserOut['expire'] - time()) / 86400, 0)) / $serviceTime;
            $volumelefts = ($dataLimit - (float) ($DataUserOut['used_traffic'] ?? 0)) / pow(1024, 3);
            $volumeleft = $volumelefts / $volumeDivisor;
            $pricelast = round($timeleft * $volumeleft * ($nameloc['price_product'] + intval($sumproduct['SUM(price)'])), 2);
        } else {
            $pricelast = 0;
        }
    }
    $pricelast = intval($pricelast);
    if (intval($pricelast) != 0) {
        $Balance_id_cancel = select("user", "*", "id", $nameloc['id_user'], "select");
        $Balance_id_cancel_fee = intval($Balance_id_cancel['Balance']) + intval($pricelast);
        update("user", "Balance", $Balance_id_cancel_fee, "id", $nameloc['id_user']);
        sendmessage($nameloc['id_user'], sprintf($textbotlang['users']['Balance']['addedNotice4'], $pricelast), null, 'HTML');
    }
    $ManagePanel->RemoveUser($nameloc['Service_location'], $requestcheck['username']);
    update("cancel_service", "status", "accept", "username", $requestcheck['username']);
    update("invoice", "status", "removedbyadmin", "username", $requestcheck['username']);
    sendmessage($from_id, sprintf($textbotlang['Admin']['Balance']['addedToUserNotice'], $pricelast), null, 'HTML');
    sendmessage($nameloc['id_user'], sprintf($textbotlang['users']['status']['deleteRequestApproved'], $nameloc['username']), null, 'HTML');
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['deleteRequestApproved'], $from_id, $pricelast, $requestcheck['username'], $nameloc['id_user']);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherreport,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
} elseif (preg_match('/remoceserviceadminmanual-(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    update("user", "Processing_value", $id_invoice, "id", $from_id);
    $invoice = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $requestcheck = select("cancel_service", "*", "username", $invoice['username'], "select");
    if ($requestcheck['status'] == "accept" || $requestcheck['status'] == "reject") {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['keyboard']['alreadyReviewed'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $invoice['Service_location'], "select");
    $ManagePanel->RemoveUser($invoice['Service_location'], $requestcheck['username']);
    update("cancel_service", "status", "accept", "username", $requestcheck['username']);
    update("invoice", "status", "removedbyadmin", "username", $requestcheck['username']);
    sendmessage($invoice['id_user'], sprintf($textbotlang['users']['status']['deleteRequestApproved2'], $invoice['username']), null, 'HTML');
    sendmessage($from_id, $textbotlang['Admin']['order']['askRefundAmount'], $backadmin, 'HTML');
    step("getpricebackremove", $from_id);
} elseif ($user['step'] == "getpricebackremove") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    $invoice = select("invoice", "*", "id_invoice", $user['Processing_value'], "select");
    $Balance_id_cancel = select("user", "*", "id", $invoice['id_user'], "select");
    $Balance_id_cancel_fee = intval($Balance_id_cancel['Balance']) + intval($text);
    update("user", "Balance", $Balance_id_cancel_fee, "id", $invoice['id_user']);
    sendmessage($invoice['id_user'], sprintf($textbotlang['users']['Balance']['addedNotice5'], $text), null, 'HTML');
    sendmessage($from_id, $textbotlang['Admin']['Balance']['addedToUser'], $keyboardadmin, 'HTML');
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['deleteRequestApproved2'], $from_id, $text, $invoice['username'], $invoice['id_user']);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherreport,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
} elseif ($datain == "settimecornremovevolume" && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['cronjob']['setVolumeRemove'] . $setting['cronvolumere'] . $textbotlang['Admin']['unit']['day'], $backadmin, 'HTML');
    step("getcronvolumere", $from_id);
} elseif ($user['step'] == "getcronvolumere") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['cronjob']['changedData'], $setting_panel, 'HTML');
    step("home", $from_id);
    update("setting", "cronvolumere", $text);
} elseif ($datain == "setting_on_holdcron" && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['cronjob']['askOnHoldDays'] . $setting['on_hold_day'] . $textbotlang['Admin']['unit']['day'], $backadmin, 'HTML');
    step("on_hold_day", $from_id);
} elseif ($user['step'] == "on_hold_day") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['cronjob']['changedData'], $setting_panel, 'HTML');
    step("home", $from_id);
    update("setting", "on_hold_day", $text);
}
if ($datain == "settimecornremove" && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['cronjob']['setDayRemove'] . $setting['removedayc'] . $textbotlang['Admin']['unit']['day'], $backadmin, 'HTML');
    step("getdaycron", $from_id);
} elseif ($user['step'] == "getdaycron") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['cronjob']['changedData'], $setting_panel, 'HTML');
    step("home", $from_id);
    update("setting", "removedayc", $text);
} elseif ($text == $textbotlang['keyboard']['editEducation'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['selectName'], $json_list_helpkey, 'HTML');
    step("getnameforedite", $from_id);
} elseif ($user['step'] == "getnameforedite") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $helpedit, 'HTML');
    update("user", "Processing_value", $text, "id", $from_id);
    step("home", $from_id);
} elseif ($text == $textbotlang['keyboard']['editName'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Product']['askNewName'], $backadmin, 'HTML');
    step('changenamehelp', $from_id);
} elseif ($user['step'] == "changenamehelp") {
    if (strlen($text) >= 150) {
        sendmessage($from_id, $textbotlang['Admin']['Help']['nameTooLong'], null, 'HTML');
        return;
    }
    update("help", "name_os", $text, "name_os", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Help']['nameUpdated'], $helpedit, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['editCategory'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['askNewCategory'], $backadmin, 'HTML');
    step('changecategoryhelp', $from_id);
} elseif ($user['step'] == "changecategoryhelp") {
    if (strlen($text) >= 150) {
        sendmessage($from_id, $textbotlang['Admin']['Help']['nameTooLong'], null, 'HTML');
        return;
    }
    update("help", "category", $text, "name_os", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Help']['categoryUpdated'], $helpedit, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['editDescription'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['askNewDesc'], $backadmin, 'HTML');
    step('changedeshelp', $from_id);
} elseif ($user['step'] == "changedeshelp") {
    update("help", "Description_os", $text, "name_os", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Help']['descUpdated'], $helpedit, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['editMedia'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['askNewMedia'], $backadmin, 'HTML');
    step('changemedia', $from_id);
} elseif ($user['step'] == "changemedia") {
    if ($photo) {
        if (isset($photoid))
            update("help", "Media_os", $photoid, "name_os", $user['Processing_value']);
        update("help", "type_Media_os", "photo", "name_os", $user['Processing_value']);
    } elseif ($video) {
        if (isset($videoid))
            update("help", "Media_os", $videoid, "name_os", $user['Processing_value']);
        update("help", "type_Media_os", "video", "name_os", $user['Processing_value']);
    }
    sendmessage($from_id, $textbotlang['Admin']['Help']['descUpdated'], $helpedit, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['disableShowCard']) {
    sendmessage($from_id, $textbotlang['Admin']['Status']['applyScope'], null, 'HTML');
    step('showcardallusers', $from_id);
} elseif ($user['step'] == "showcardallusers") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['disableShowCardStatus'], null, 'HTML');
    if (intval($text) == "1") {
        update("user", "cardpayment", "0");
        update("setting", "showcard", "0");
    } elseif (intval($text) == 2) {
        update("user", "cardpayment", "0", "agent", "f");
        update("setting", "showcard", "0");
    } else {
        update("setting", "showcard", "0");
    }
} elseif ($text == $textbotlang['keyboard']['enableShowCard']) {
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['activeShowCardStatus'], null, 'HTML');
    update("user", "cardpayment", "1");
    update("setting", "showcard", "1");
} elseif ($text == $textbotlang['keyboard']['renewalMethod'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $Methodextend, 'HTML');
    step('updateextendmethod', $from_id);
} elseif ($user['step'] == "updateextendmethod") {
    $aarayvalid = array(
        $textbotlang['keyboard']['resetVolumeTime'],
        $textbotlang['keyboard']['addTimeVolumeNextMonth'],
        $textbotlang['keyboard']['resetTimeAddVolume'],
        $textbotlang['keyboard']['resetVolumeAddTime'],
        $textbotlang['keyboard']['addTimeConvertVolume']
    );
    if (!in_array($text, $aarayvalid)) {
        sendmessage($from_id, $textbotlang['Admin']['algorithmExtend']['invalidMethod'], null, 'HTML');
        return;
    }
    update("marzban_panel", "Methodextend", extendMethodKey($text), "name_panel", $user['Processing_value']);
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['algorithmExtend']['saveData']);
    step('home', $from_id);
} elseif ($text == "/token") {
    $secret_key = select("admin", "*", "id_admin", $from_id, "select");
    $secret_key = base64_encode($secret_key['password']);
    sendmessage($from_id, "<code>$secret_key</code>", null, 'HTML');
} elseif ($text == "/token2") {
    $token = bin2hex(random_bytes(16));
    file_put_contents('api/hash.txt', $token);
    sendmessage($from_id, sprintf($textbotlang['Admin']['api']['token'], $token), null, 'HTML');
    $apiDocsUrl = "https://$domainhostsEscaped/api/index.html";
    sendmessage($from_id, sprintf($textbotlang['Admin']['api']['docsLink'], $apiDocsUrl), null, 'HTML');
} elseif ($text == $textbotlang['keyboard']['activateWebPanel'] && $adminrulecheck['rule'] == "administrator") {
    $admin_select = select("admin", "*", "id_admin", $from_id, "select");
    $randomString = bin2hex(random_bytes(6));
    update("admin", "username", $from_id, "id_admin", $from_id);
    update("admin", "password", password_hash($randomString, PASSWORD_BCRYPT, ['cost' => 12]), "id_admin", $from_id);
    sendmessage($from_id, sprintf($textbotlang['Admin']['webpanel']['activated'], $domainhosts, $from_id, $randomString), null, 'HTML');
} elseif (preg_match('/addordermanualـ(\w+)/', $datain, $dataget) && $adminrulecheck['rule'] == "administrator") {
    $iduser = $dataget[1];
    update("user", "Processing_value", $iduser, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['addorder']['stepTwo'], $backadmin, 'HTML');
    step('getusernameconfig', $from_id);
} elseif ($user['step'] == "getusernameconfig") {
    $text = strtolower($text);
    if (!preg_match('/^\w{3,32}$/', $text)) {
        sendmessage($from_id, $textbotlang['common']['invalidUsername'], $backuser, 'html');
        return;
    }
    $stmtDup = $pdo->prepare("SELECT 1 FROM invoice WHERE BINARY username = ? LIMIT 1");
    $stmtDup->execute([$text]);
    if ($stmtDup->fetchColumn() !== false) {
        sendmessage($from_id, $textbotlang['Admin']['addorder']['usernameExists'], null, 'HTML');
        return;
    }
    update("user", "Processing_value_one", $text, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['addorder']['stepThree'], $json_list_marzban_panel, 'HTML');
    step('getnamepanelconfig', $from_id);
} elseif ($user['step'] == "getnamepanelconfig") {
    update("user", "Processing_value_tow", $text, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['addorder']['stepFour'], $json_list_product_list_admin, 'HTML');
    step('stependforaddorder', $from_id);
} elseif ($user['step'] == "stependforaddorder") {
    $sql = "SELECT * FROM product  WHERE name_product = :name_product AND (Location = :location OR Location = '/all') LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':name_product', $text, PDO::PARAM_STR);
    $stmt->bindParam(':location', $user['Processing_value_tow'], PDO::PARAM_STR);
    $stmt->execute();
    $info_product = $stmt->fetch(PDO::FETCH_ASSOC);
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value_tow'], "select");
    $DataUserOut = $ManagePanel->DataUser($user['Processing_value_tow'], $user['Processing_value_one']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        $datetimestep = strtotime("+" . $info_product['Service_time'] . "days");
        if ($info_product['Service_time'] == 0) {
            $datetimestep = 0;
        } else {
            $datetimestep = strtotime(date("Y-m-d H:i:s", $datetimestep));
        }
        $datac = array(
            'expire' => $datetimestep,
            'data_limit' => $info_product['Volume_constraint'] * pow(1024, 3),
            'from_id' => $user['Processing_value'],
            'username' => "",
            'type' => 'buy'
        );
        $DataUserOut = $ManagePanel->createUser($user['Processing_value_tow'], $info_product['code_product'], $user['Processing_value_one'], $datac);
        if ($DataUserOut['username'] == null) {
            sendmessage($from_id, $textbotlang['Admin']['reportgroup']['checkReportGroup'], null, 'HTML');
            $DataUserOut['msg'] = json_encode($DataUserOut['msg']);
            $texterros = sprintf($textbotlang['Admin']['reportgroup']['errorConfigCreateAdmin'], $DataUserOut['msg'], $from_id, $marzban_list_get['name_panel']);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $texterros,
                    'parse_mode' => "HTML"
                ]);
                step("home", $from_id);
            }
            return;
        }
    } else {
        $DataUserOut['configs'] = $DataUserOut['links'];
    }
    $date = time();
    $randomString = bin2hex(random_bytes(4));
    $notifctions = json_encode(array(
        'volume' => false,
        'time' => false,
    ));
    $stmt = $pdo->prepare("INSERT IGNORE INTO invoice (id_user, id_invoice, username, time_sell, Service_location, name_product, price_product, Volume, Service_time, Status,notifctions) VALUES (:id_user, :id_invoice, :username, :time_sell, :Service_location, :name_product, :price_product, :Volume, :Service_time, :Status,:notifctions)");
    $Status = "active";
    $stmt->bindParam(':id_user', $user['Processing_value'], PDO::PARAM_STR);
    $stmt->bindParam(':id_invoice', $randomString, PDO::PARAM_STR);
    $stmt->bindParam(':username', $user['Processing_value_one'], PDO::PARAM_STR);
    $stmt->bindParam(':time_sell', $date, PDO::PARAM_STR);
    $stmt->bindParam(':Service_location', $user['Processing_value_tow'], PDO::PARAM_STR);
    $stmt->bindParam(':name_product', $info_product['name_product'], PDO::PARAM_STR);
    $stmt->bindParam(':price_product', $info_product['price_product'], PDO::PARAM_STR);
    $stmt->bindParam(':Volume', $info_product['Volume_constraint'], PDO::PARAM_STR);
    $stmt->bindParam(':Service_time', $info_product['Service_time'], PDO::PARAM_STR);
    $stmt->bindParam(':Status', $Status, PDO::PARAM_STR);
    $stmt->bindParam(':notifctions', $notifctions, PDO::PARAM_STR);
    $stmt->execute();
    $output_config_link = $marzban_list_get['sublink'] == "onsublink" ? $DataUserOut['subscription_url'] : "";
    $config = "";
    if ($marzban_list_get['config'] == "onconfig" && is_array($DataUserOut['configs'])) {
        foreach ($DataUserOut['configs'] as $link) {
            $config .= "\n" . $link;
        }
    }
    $Shoppinginfo = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['help']['btninlinebuy'], 'callback_data' => "helpbtn"],
            ]
        ]
    ]);
    $textbotlang['textbot']['afterPay'] = $marzban_list_get['type'] == "Manualsale" ? $textbotlang['textbot']['manual'] : $textbotlang['textbot']['afterPay'];
    $textbotlang['textbot']['afterPay'] = $marzban_list_get['type'] == "WGDashboard" ? $textbotlang['textbot']['wgDashboard'] : $textbotlang['textbot']['afterPay'];
    $textbotlang['textbot']['afterPay'] = $marzban_list_get['type'] == "ibsng" || $marzban_list_get['type'] == "mikrotik" ? $textbotlang['textbot']['afterPayIbsng'] : $textbotlang['textbot']['afterPay'];
    if (intval($info_product['Service_time']) == 0)
        $info_product['Service_time'] = $textbotlang['users']['status']['unlimited'];
    if (intval($info_product['Volume_constraint']) == 0)
        $info_product['Volume_constraint'] = $textbotlang['users']['status']['unlimited'];
    $textcreatuser = str_replace('{username}', "<code>{$DataUserOut['username']}</code>", $textbotlang['textbot']['afterPay']);
    $textcreatuser = str_replace('{name_service}', $info_product['name_product'], $textcreatuser);
    $textcreatuser = str_replace('{location}', $marzban_list_get['name_panel'], $textcreatuser);
    $textcreatuser = str_replace('{day}', $info_product['Service_time'], $textcreatuser);
    $textcreatuser = str_replace('{volume}', $info_product['Volume_constraint'], $textcreatuser);
    $textcreatuser = str_replace('{config}', "<code>{$output_config_link}</code>", $textcreatuser);
    $textcreatuser = str_replace('{links}', $config, $textcreatuser);
    $textcreatuser = str_replace('{links2}', $output_config_link, $textcreatuser);
    if (intval($info_product['Volume_constraint']) == 0) {
        $textcreatuser = str_replace($textbotlang['Admin']['unit']['gigabytes'], "", $textcreatuser);
    }
    if ($marzban_list_get['type'] == "Manualsale" || $marzban_list_get['type'] == "ibsng" || $marzban_list_get['type'] == "mikrotik") {
        $textcreatuser = str_replace('{password}', $DataUserOut['subscription_url'], $textcreatuser);
        update("invoice", "user_info", $DataUserOut['subscription_url'], "id_invoice", $randomString);
    }
    sendMessageService($marzban_list_get, $DataUserOut['configs'], $output_config_link, $DataUserOut['username'], $Shoppinginfo, $textcreatuser, $randomString, $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['addorder']['stepFive'], $keyboardadmin, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['minBulkBalance'] && $adminrulecheck['rule'] == "administrator") {
    $PaySetting = select("shopSetting", "value", "Namevalue", "minbalancebuybulk", "select")['value'];
    $textmin = sprintf($textbotlang['Admin']['price']['askBulkMin'], $PaySetting);
    sendmessage($from_id, $textmin, $backadmin, 'HTML');
    step('minbalancebulk', $from_id);
} elseif ($user['step'] == "minbalancebulk") {
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $shopkeyboard, 'HTML');
    update("shopSetting", "value", $text, "Namevalue", "minbalancebuybulk");
    step('home', $from_id);
} elseif (preg_match('/showcarduser-(.*)/', $datain, $dataget)) {
    $id_user = $dataget[1];
    sendmessage($id_user, $textbotlang['users']['Balance']['cardEnabledNotice'], null, 'HTML');
    sendmessage($from_id, $textbotlang['Admin']['card']['enabled'], null, 'HTML');
    update("user", "cardpayment", "1", "id", $id_user);
} elseif (preg_match('/carduserhide-(.*)/', $datain, $dataget)) {
    $id_user = $dataget[1];
    sendmessage($from_id, $textbotlang['Admin']['card']['disabled'], null, 'HTML');
    update("user", "cardpayment", "0", "id", $id_user);
} elseif ($text == $textbotlang['keyboard']['deleteCardNumber'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['card']['askDelete'], $list_card_remove, 'HTML');
    step('getcardremove', $from_id);
} elseif ($user['step'] == "getcardremove") {
    $stmt = $pdo->prepare("DELETE FROM card_number WHERE cardnumber = :cardnumber");
    $stmt->bindParam(':cardnumber', $text, PDO::PARAM_STR);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['card']['deleted'], $CartManage, 'HTML');
    step("home", $from_id);
} elseif (preg_match('/rejectrequesta_(\w+)/', $datain, $datagetr)) {
    $id_user = $datagetr[1];
    $request_agent = select("Requestagent", "*", "id", $id_user, "select");
    update("Requestagent", "status", "reject", "id", $id_user);
    $userinfo = select("user", "*", "id", $id_user, "select");
    $Balancenew = $userinfo['Balance'] + intval($setting['agentreqprice']);
    update("user", "Balance", $Balancenew, "id", $id_user);
    if ($request_agent['status'] == "reject" || $request_agent['status'] == "accept") {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['keyboard']['alreadyReviewed'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
        return;
    }
    $keyboardreject = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['keyboard']['requestRejected'], 'callback_data' => "reject"]],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['agent']['requestRejected'], null, 'HTML');
    sendmessage($id_user, $textbotlang['users']['agent']['requestRejected'], null, 'HTML');
    $textrequestagent = sprintf($textbotlang['Admin']['agent']['requestNotice'], $id_user, $request_agent['username'], $request_agent['Description']);
    Editmessagetext($from_id, $message_id, $textrequestagent, $keyboardreject);
} elseif (preg_match('/addagentrequest_(\w+)/', $datain, $datagetr)) {
    $id_user = $datagetr[1];
    $request_agent = select("Requestagent", "*", "id", $id_user, "select");
    if (!$request_agent) {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['keyboard']['requestNotFound'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
        return;
    }
    if ($request_agent['status'] == "reject" || $request_agent['status'] == "accept") {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['keyboard']['alreadyReviewed'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
        return;
    }
    $defaultAgentType = 'n';
    $agentTypeLabels = [
        'n' => $textbotlang['keyboard']['normalAgent'],
        'n2' => $textbotlang['keyboard']['advancedAgent'],
    ];
    update("Requestagent", "status", "accept", "id", $id_user);
    update("Requestagent", "type", $defaultAgentType, "id", $id_user);
    update("user", "agent", $defaultAgentType, "id", $id_user);
    update("user", "expire", null, "id", $id_user);
    sendmessage($id_user, $textbotlang['users']['agent']['requestApproved'], null, 'HTML');
    sendmessage($from_id, $textbotlang['Admin']['agent']['userAgented'], $keyboardadmin, 'HTML');
    $agentTypeButtons = [];
    foreach ($agentTypeLabels as $typeCode => $label) {
        $buttonText = ($typeCode === $defaultAgentType ? "✅ " : "") . $label;
        $agentTypeButtons[] = [
            'text' => $buttonText,
            'callback_data' => "setagenttype_{$typeCode}_{$id_user}"
        ];
    }
    $keyboardreject = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['keyboard']['requestApproved'], 'callback_data' => "accept"]],
            $agentTypeButtons,
            [['text' => $textbotlang['keyboard']['agentExpireTime'], 'callback_data' => 'expireset_' . $id_user]],
            [['text' => $textbotlang['keyboard']['userManagement'], 'callback_data' => 'manageuser_' . $id_user]]
        ]
    ], JSON_UNESCAPED_UNICODE);
    $textrequestagent = sprintf($textbotlang['Admin']['agent']['requestNotice2'], $id_user, $request_agent['username'], $request_agent['Description']);
    $textrequestagent .= sprintf($textbotlang['Admin']['agent']['statusApproved'], $agentTypeLabels[$defaultAgentType]);
    $textrequestagent .= $textbotlang['Admin']['agent']['changeTypeHint'];
    Editmessagetext($from_id, $message_id, $textrequestagent, $keyboardreject);
    telegram('answerCallbackQuery', array(
        'callback_query_id' => $callback_query_id,
        'text' => $textbotlang['keyboard']['agentActivated'],
        'show_alert' => false,
        'cache_time' => 5,
    ));
} elseif (preg_match('/^setagenttype_(n|n2)_(\w+)/', $datain, $datagetr)) {
    $selectedType = $datagetr[1];
    $id_user = $datagetr[2];
    $agentTypeLabels = [
        'n' => $textbotlang['keyboard']['normalAgent'],
        'n2' => $textbotlang['keyboard']['advancedAgent'],
    ];
    if (!array_key_exists($selectedType, $agentTypeLabels)) {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['Admin']['agent']['invalidTypeAgent'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
        return;
    }
    update("user", "agent", $selectedType, "id", $id_user);
    update("Requestagent", "type", $selectedType, "id", $id_user);
    $request_agent = select("Requestagent", "*", "id", $id_user, "select");
    if ($request_agent) {
        $agentTypeButtons = [];
        foreach ($agentTypeLabels as $typeCode => $label) {
            $buttonText = ($typeCode === $selectedType ? "✅ " : "") . $label;
            $agentTypeButtons[] = [
                'text' => $buttonText,
                'callback_data' => "setagenttype_{$typeCode}_{$id_user}"
            ];
        }
        $keyboardreject = json_encode([
            'inline_keyboard' => [
                [['text' => $textbotlang['keyboard']['requestApproved'], 'callback_data' => "accept"]],
                $agentTypeButtons,
                [['text' => $textbotlang['keyboard']['agentExpireTime'], 'callback_data' => 'expireset_' . $id_user]],
                [['text' => $textbotlang['keyboard']['userManagement'], 'callback_data' => 'manageuser_' . $id_user]]
            ]
        ], JSON_UNESCAPED_UNICODE);
        $textrequestagent = sprintf($textbotlang['Admin']['agent']['requestNotice3'], $id_user, $request_agent['username'], $request_agent['Description']);
        $textrequestagent .= sprintf($textbotlang['Admin']['agent']['statusApproved2'], $agentTypeLabels[$selectedType]);
        $textrequestagent .= $textbotlang['Admin']['agent']['changeTypeHint'];
        Editmessagetext($from_id, $message_id, $textrequestagent, $keyboardreject);
    }
    telegram('answerCallbackQuery', array(
        'callback_query_id' => $callback_query_id,
        'text' => strtr($textbotlang['keyboard']['agentTypeChanged'], ['{agent_type}' => $agentTypeLabels[$selectedType]]),
        'show_alert' => false,
        'cache_time' => 5,
    ));
} elseif ($datain == "iranpay2setting" && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $trnado, 'HTML');
} elseif ($text == $textbotlang['keyboard']['apiIranPay4'] && $adminrulecheck['rule'] == "administrator") {
    $current = getPaySettingValue('apiiranpay4', '0');
    sendmessage($from_id, sprintf($textbotlang['Admin']['gateway']['askMerchant'], $current), $backadmin, 'HTML');
    step('apiiranpay4', $from_id);
} elseif ($user['step'] == "apiiranpay4") {
    update("PaySetting", "ValuePay", trim($text), "NamePay", "apiiranpay4");
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $abangatewaykeyboard, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['endpointIranPay4'] && $adminrulecheck['rule'] == "administrator") {
    $current = getPaySettingValue('endpointiranpay4', '0');
    sendmessage($from_id, sprintf($textbotlang['keyboard']['askEndpointIranPay4'], $current), $backadmin, 'HTML');
    step('endpointiranpay4', $from_id);
} elseif ($user['step'] == "endpointiranpay4") {
    // Validated before it is stored, not before it is used. The key travels to
    // this address as a bearer token, so `http://` would put it on the wire in
    // clear — and an address that is not a public https host is not one this
    // bot should be posting a shop's takings to.
    $candidate = rtrim(trim($text), '/');
    $parts = parse_url($candidate);
    $host = $parts['host'] ?? '';
    $validHost = $host !== '' && filter_var('https://' . $host, FILTER_VALIDATE_URL) !== false && str_contains($host, '.');
    if (($parts['scheme'] ?? '') !== 'https' || !$validHost) {
        sendmessage($from_id, $textbotlang['keyboard']['endpointIranPay4Invalid'], $abangatewaykeyboard, 'HTML');
        step('home', $from_id);
    } else {
        update("PaySetting", "ValuePay", $candidate, "NamePay", "endpointiranpay4");
        sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $abangatewaykeyboard, 'HTML');
        step('home', $from_id);
    }
} elseif ($text == $textbotlang['keyboard']['minAmountIranPay4'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['gateway']['askMinAmount'] ?? $textbotlang['users']['selectoption'], $backadmin, 'HTML');
    step("getmainiranpay4", $from_id);
} elseif ($user['step'] == "getmainiranpay4") {
    update("PaySetting", "ValuePay", intval($text), "NamePay", "minbalanceiranpay4");
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $abangatewaykeyboard, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['maxAmountIranPay4'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['gateway']['askMaxAmount'] ?? $textbotlang['users']['selectoption'], $backadmin, 'HTML');
    step("getmaaxiranpay4", $from_id);
} elseif ($user['step'] == "getmaaxiranpay4") {
    update("PaySetting", "ValuePay", intval($text), "NamePay", "maxbalanceiranpay4");
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $abangatewaykeyboard, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['dailyLimitIranPay4'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['gateway']['askDailyLimit'] ?? $textbotlang['users']['selectoption'], $backadmin, 'HTML');
    step("getdailyiranpay4", $from_id);
} elseif ($user['step'] == "getdailyiranpay4") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    update("PaySetting", "ValuePay", intval($text), "NamePay", "dailylimitiranpay4");
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $abangatewaykeyboard, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['cashbackIranPay4'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['gateway']['askCashback'] ?? $textbotlang['users']['selectoption'], $backadmin, 'HTML');
    step("getcashiranpay4", $from_id);
} elseif ($user['step'] == "getcashiranpay4") {
    update("PaySetting", "ValuePay", intval($text), "NamePay", "chashbackiranpay4");
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $abangatewaykeyboard, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['setEducationIranPay4'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['gateway']['askEducation'] ?? $textbotlang['users']['selectoption'], $backadmin, 'HTML');
    step("helpiranpay4", $from_id);
} elseif ($user['step'] == "helpiranpay4") {
    update("PaySetting", "ValuePay", $text, "NamePay", "helpiranpay4");
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $abangatewaykeyboard, 'HTML');
    step('home', $from_id);
} elseif ($datain == "iranpay4setting" && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $abangatewaykeyboard, 'HTML');
} elseif ($datain == "iranpay3setting" && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $iranpaykeyboard, 'HTML');
}elseif ($text == "API T" && $adminrulecheck['rule'] == "administrator") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "apiternado", "select");
    $texttronseller = sprintf($textbotlang['Admin']['gateway']['askMerchant'], $PaySetting['ValuePay']);
    sendmessage($from_id, $texttronseller, $backadmin, 'HTML');
    step('apiternado', $from_id);
} elseif ($user['step'] == "apiternado") {
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $trnado, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "apiternado");
    step('home', $from_id);
} elseif ($datain == "affilnecurrencysetting") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $tronnowpayments, 'HTML');
} elseif ($text == $textbotlang['keyboard']['inboundDeactivate'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['Inbound']['getProtocol'], $keyboardprotocol, 'HTML');
    step('getprotocoldisable', $from_id);
} elseif ($user['step'] == "getprotocoldisable") {
    global $json_list_marzban_panel_inbounds;
    $protocol = ["vless", "vmess", "trojan", "shadowsocks"];
    if (!in_array($text, $protocol)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['Inbound']['invalidProtocol'], null, 'HTML');
        return;
    }
    $getinbounds = getinbounds($user['Processing_value'])[$text];
    $list_marzban_panel_inbounds = [
        'keyboard' => [],
        'resize_keyboard' => true,
    ];
    foreach ($getinbounds as $button) {
        $list_marzban_panel_inbounds['keyboard'][] = [
            ['text' => $button['tag']]
        ];
    }
    $list_marzban_panel_inbounds['keyboard'][] = [
        ['text' => $textbotlang['keyboard']['backToAdminMenu']],
    ];
    $json_list_marzban_panel_inbounds = json_encode($list_marzban_panel_inbounds);
    update("user", "Processing_value_one", $text, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['Inbound']['getInbound'], $json_list_marzban_panel_inbounds, 'HTML');
    step('getInbounddisable', $from_id);
} elseif ($user['step'] == "getInbounddisable") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['inboundNameSaved'], $optionMarzban, 'HTML');
    $textpro = "{$user['Processing_value_one']}*$text";
    update("marzban_panel", "inbound_deactive", $textpro, "name_panel", $user['Processing_value']);
    step("home", $from_id);
} elseif ($text == $textbotlang['Admin']['report']['btnOptimize'] && $adminrulecheck['rule'] == "administrator") {
    $textoptimize = $textbotlang['Admin']['report']['optimizeWarning'];
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['confirmOptimize'], 'callback_data' => 'optimizebot'],
            ],
        ]
    ]);
    sendmessage($from_id, $textoptimize, $Response, 'HTML');
} elseif ($datain == "optimizebot") {
    #remove data
    $stmt = $pdo->prepare("DELETE FROM invoice WHERE Status = 'unpaid' AND name_product != :mp11");
    $stmt->execute([':mp11' => $textbotlang['common']['labels']['testServiceName']]);
    $countunpiadorder = $stmt->rowCount();
    $stmt = $pdo->prepare("DELETE FROM invoice WHERE Status = 'disabled' AND name_product != :mp12");
    $stmt->execute([':mp12' => $textbotlang['common']['labels']['testServiceName']]);
    $countdisableorder = $stmt->rowCount();
    $stmt = $pdo->prepare("DELETE FROM invoice WHERE Status = 'removebyadmin'");
    $stmt->execute();
    $countremoveadminorder = $stmt->rowCount();
    $stmt = $pdo->prepare("DELETE FROM invoice WHERE Status = 'removedbyadmin'");
    $stmt->execute();
    $countremoveadminorder += $stmt->rowCount();
    $stmt = $pdo->prepare("DELETE FROM invoice WHERE Status = 'disabled' AND name_product = :mp13");
    $stmt->execute([':mp13' => $textbotlang['common']['labels']['testServiceName']]);
    $countdisableordtester = $stmt->rowCount();
    $stmt = $pdo->prepare("DELETE FROM invoice WHERE Status = 'removeTime'");
    $stmt->execute();
    $stmt = $pdo->prepare("DELETE FROM invoice WHERE Status = 'removevolume'");
    $stmt->execute();
    $stmt = $pdo->prepare("DELETE FROM invoice WHERE Status = 'removebyuser' ");
    $stmt->execute();
    $optimizebot = sprintf($textbotlang['Admin']['report']['optimizeResult'], $countunpiadorder, $countdisableorder, $countremoveadminorder, $countdisableordtester);
    Editmessagetext($from_id, $message_id, $optimizebot, null);
    $time = time();
    $logss = "optimize_{$countunpiadorder}_{$countdisableorder}_{$countremoveadminorder}_{$countdisableordtester}_$time";
    @file_put_contents(__DIR__ . '/storage/log.txt', "\n" . $logss, FILE_APPEND);
} elseif ($datain == "settimecornvolume") {
    sendmessage($from_id, $textbotlang['Admin']['cronjob']['askVolumeAlert'], $backadmin, 'HTML');
    step("getvolumewarn", $from_id);
} elseif ($user['step'] == "getvolumewarn") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['invalidValue'], null, 'html');
        return;
    }
    update("setting", "volumewarn", $text);
    sendmessage($from_id, $textbotlang['Admin']['changesSaved2'], $setting_panel, 'HTML');
    step("home", $from_id);
} elseif ($text == $textbotlang['keyboard']['manualCreateConfig']) {
    savedata("clear", "idpanel", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['addorder']['manualIntro'], $backadmin, 'HTML');
    step('getusernameconfigcr', $from_id);
} elseif ($user['step'] == "getusernameconfigcr") {
    if (!preg_match('~(?!_)^[a-z][a-z\d_]{2,32}(?<!_)$~i', $text)) {
        sendmessage($from_id, $textbotlang['users']['invalidusername'], $backadmin, 'HTML');
        return;
    }
    update("user", "Processing_value_one", $text, "id", $from_id);
    step('getcountcreate', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['addorder']['askConfigCount'], $backadmin, 'HTML');
} elseif ($user['step'] == "getcountcreate") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    if (intval($text) > 10 or intval($text) < 0) {
        sendmessage($from_id, $textbotlang['Admin']['addorder']['invalidCount'], $backadmin, 'HTML');
        return;
    }
    savedata("save", "count", $text);
    step('getvolumesconfig', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['addorder']['askVolume'], $backadmin, 'HTML');
} elseif ($user['step'] == "getvolumesconfig") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['invalidValue'], null, 'html');
        return;
    }
    update("user", "Processing_value_tow", $text, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['addorder']['askTime'], $backadmin, 'HTML');
    step("gettimeaccount", $from_id);
} elseif ($user['step'] == "gettimeaccount") {
    $userdata = json_decode($user['Processing_value'], true);
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['invalidValue'], null, 'html');
        return;
    }
    if (intval($text) == 0) {
        $expire = 0;
    } else {
        $datetimestep = strtotime("+" . $text . "days");
        $expire = strtotime(date("Y-m-d H:i:s", $datetimestep));
    }
    $datac = array(
        'expire' => $expire,
        'data_limit' => $user['Processing_value_tow'] * pow(1024, 3),
        'from_id' => $from_id,
        'username' => "$username",
        'type' => "new by admin $from_id"
    );
    $panel = select("marzban_panel", "*", "name_panel", $userdata['idpanel'], "select");
    for ($i = 0; $i < $userdata['count']; $i++) {
        $usernameconfig = $user['Processing_value_one'] . "_" . $i;
        $dataoutput = $ManagePanel->createUser($userdata['idpanel'], "usertest", $usernameconfig, $datac);
        if ($dataoutput['username'] == null) {
            $dataoutput['msg'] = json_encode($dataoutput['msg']);
            sendmessage($from_id, $textbotlang['users']['sell']['errorConfig'], null, 'HTML');
            $texterros = sprintf($textbotlang['Admin']['reportgroup']['errorAccountCreate'], $dataoutput['msg'], $from_id, $username, $panel['name_panel']);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'message_thread_id' => $errorreport,
                    'text' => $texterros,
                    'parse_mode' => "HTML"
                ]);
                step("home", $from_id);
            }
            return;
        }
        $randomString = bin2hex(random_bytes(5));
        $output_config_link = $panel['sublink'] == "onsublink" ? $dataoutput['subscription_url'] : "";
        $config = "";
        if ($marzban_list_get['config'] == "onconfig" && is_array($dataoutput['configs'])) {
            foreach ($dataoutput['configs'] as $link) {
                $config .= "\n" . $link;
            }
        }
        $textbotlang['textbot']['afterPay'] = $panel['type'] == "Manualsale" ? $textbotlang['textbot']['manual'] : $textbotlang['textbot']['afterPay'];
        $textbotlang['textbot']['afterPay'] = $panel['type'] == "WGDashboard" ? $textbotlang['textbot']['wgDashboard'] : $textbotlang['textbot']['afterPay'];
        $textbotlang['textbot']['afterPay'] = $panel['type'] == "ibsng" || $panel['type'] == "mikrotik" ? $textbotlang['textbot']['afterPayIbsng'] : $textbotlang['textbot']['afterPay'];
        if (intval($text) == 0)
            $text = $textbotlang['users']['status']['unlimited'];
        $textcreatuser = str_replace('{username}', "<code>{$dataoutput['username']}</code>", $textbotlang['textbot']['afterPay']);
        $textcreatuser = str_replace('{name_service}', $textbotlang['Admin']['addorder']['customPlan'], $textcreatuser);
        $textcreatuser = str_replace('{location}', $panel['name_panel'], $textcreatuser);
        $textcreatuser = str_replace('{day}', $text, $textcreatuser);
        $textcreatuser = str_replace('{volume}', $user['Processing_value_tow'], $textcreatuser);
        $textcreatuser = str_replace('{config}', $output_config_link, $textcreatuser);
        $textcreatuser = str_replace('{links}', $config, $textcreatuser);
        $textcreatuser = str_replace('{links2}', $output_config_link, $textcreatuser);
        if ($panel['type'] == "Manualsale" || $panel['type'] == "ibsng" || $panel['type'] == "mikrotik") {
            $textcreatuser = str_replace('{password}', $dataoutput['subscription_url'], $textcreatuser);
            update("invoice", "user_info", $dataoutput['subscription_url'], "id_invoice", $randomString);
        }
        sendMessageService($panel, $dataoutput['configs'], $output_config_link, $dataoutput['username'], null, $textcreatuser, $randomString);
    }
    sendmessage($from_id, $textbotlang['users']['selectoption'], $optionathmarzban, 'HTML');
    $text_report = "";
    if (strlen($setting['Channel_Report']) > 0) {
        $text_report = sprintf($textbotlang['Admin']['reportgroup']['configCreatedByAdmin'], $user['Processing_value_one'], $user['Processing_value_tow'], $text, $from_id, $username, $userdata['count']);
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $buyreport,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
    update("user", "Processing_value", $userdata['idpanel'], "id", $from_id);
    step("home", $from_id);
} elseif ($text == $textbotlang['keyboard']['botReport'] && $adminrulecheck['rule'] == "administrator") {
    $textupdate = $textbotlang['Admin']['report']['botReportIntro'];
    sendmessage($from_id, $textupdate, null, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['panelFeatures']) {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['selectPanel'], $json_list_marzban_panel, 'HTML');
    step('getlocoption', $from_id);
} elseif ($user['step'] == "getlocoption") {
    update("user", "Processing_value", $text, "id", $from_id);
    $typepanel = select("marzban_panel", "*", "name_panel", $text, "select")['type'];
    if ($typepanel == "marzban") {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $optionathmarzban, 'HTML');
    } elseif ($typepanel == "x-ui_single") {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $optionathx_ui, 'HTML');
    } elseif ($typepanel == "hiddify") {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $optionathx_ui, 'HTML');
    } elseif ($typepanel == "alireza") {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $optionathx_ui, 'HTML');
    } elseif ($typepanel == "alireza_single") {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $optionathx_ui, 'HTML');
    } elseif ($typepanel == "marzneshin") {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $optionathx_ui, 'HTML');
    } elseif ($typepanel == "WGDashboard") {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $optionathx_ui, 'HTML');
    }
    step("home", $from_id);
} elseif ($text == $textbotlang['keyboard']['manageNodes'] || $datain == "bakcnode") {
    if ($adminnumber != $from_id) {
        sendmessage($from_id, $textbotlang['Admin']['mainAdminOnly'], null, 'HTML');
        return;
    }
    $nodes = Get_Nodes($user['Processing_value']);
    if (!empty($nodes['error'])) {
        sendmessage($from_id, panelErrorText($nodes['error']), null, 'HTML');
        return;
    }
    if (!empty($nodes['status']) && $nodes['status'] != 200) {
        sendmessage($from_id, sprintf($textbotlang['Admin']['errorCode'], $nodes['status']), null, 'HTML');
        return;
    }
    $nodes = json_decode($nodes['body'], true);
    if (count($nodes) == 0) {
        sendmessage($from_id, $textbotlang['Admin']['node']['settingsUnavailable'], null, 'HTML');
        return;
    }
    $keyboardlistsnode['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "actionnode"],
        ['text' => $textbotlang['keyboard']['name'], 'callback_data' => "namenode"]
    ];
    foreach ($nodes as $result) {
        if (!isset($result['id']))
            continue;
        $keyboardlistsnode['inline_keyboard'][] = [
            ['text' => $textbotlang['keyboard']['management'], 'callback_data' => "node_{$result['id']}"],
            ['text' => $result['name'], 'callback_data' => "node_{$result['id']}"],
        ];
    }
    $keyboardlistsnode = json_encode($keyboardlistsnode);
    if ($datain == "bakcnode") {
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['node']['intro'], $keyboardlistsnode);
    } else {
        sendmessage($from_id, $textbotlang['Admin']['node']['intro'], $keyboardlistsnode, 'HTML');
    }
} elseif (preg_match('/^node_(.*)/', $datain, $dataget)) {
    $nodeid = $dataget[1];
    update("user", "Processing_value_one", $nodeid, "id", $from_id);
    $node = Get_Node($user['Processing_value'], $nodeid);
    if (!empty($node['error'])) {
        sendmessage($from_id, panelErrorText($node['error']), null, 'HTML');
        return;
    }
    if (!empty($node['status']) && $node['status'] != 200) {
        sendmessage($from_id, sprintf($textbotlang['Admin']['errorCode2'], $node['status']), null, 'HTML');
        return;
    }
    $nodeusage = Get_usage_Nodes($user['Processing_value']);
    if (!empty($nodeusage['error'])) {
        sendmessage($from_id, panelErrorText($nodeusage['error']), null, 'HTML');
        return;
    }
    if (!empty($nodeusage['status']) && $nodeusage['status'] != 200) {
        sendmessage($from_id, sprintf($textbotlang['Admin']['errorCode3'], $nodeusage['status']), null, 'HTML');
        return;
    }
    $node = json_decode($node['body'], true);
    $nodeusage = json_decode($nodeusage['body'], true);
    foreach ($nodeusage['usages'] as $nodeusages) {
        if ($nodeusages['node_id'] == $nodeid) {
            $nodeusage = $nodeusages;
            break;
        }
    }
    $sumvolume = formatBytes($nodeusage['downlink'] + $nodeusage['uplink']);
    $textnode = sprintf($textbotlang['Admin']['node']['info'], $node['name'], $node['address'], $node['port'], $node['api_port'], $sumvolume, $node['usage_coefficient'], $node['xray_version'], $node['status']);
    $backinfoss = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['renameNode'], 'callback_data' => "changenamenode"],
                ['text' => $textbotlang['keyboard']['changeNodeMultiplier'], 'callback_data' => "changecoefficient"],
            ],
            [
                ['text' => $textbotlang['keyboard']['changeNodeIp'], 'callback_data' => "changeipnode"],
                ['text' => $textbotlang['keyboard']['reconnectNode'], 'callback_data' => "reconnectnode"],
            ],
            [
                ['text' => $textbotlang['keyboard']['deleteNode'], 'callback_data' => "removenode"],
            ],
            [
                ['text' => $textbotlang['keyboard']['backToNodeList'], 'callback_data' => "bakcnode"],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textnode, $backinfoss);
} elseif ($datain == "changecoefficient") {
    $backinfoss = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['backToNode'], 'callback_data' => "node_" . $user['Processing_value_one']],
            ]
        ]
    ]);
    $textnode = $textbotlang['Admin']['node']['askMultiplier'];
    Editmessagetext($from_id, $message_id, $textnode, $backinfoss);
    step("getusage_coefficient", $from_id);
} elseif ($user['step'] == "getusage_coefficient") {
    $config = array(
        'usage_coefficient' => $text
    );
    Modifyuser_node($user['Processing_value'], $user['Processing_value_one'], $config);
    $backinfoss = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['backToNode'], 'callback_data' => "node_" . $user['Processing_value_one']],
            ]
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['node']['multiplierSaved'], $backinfoss, 'HTML');
    step('home', $from_id);
} elseif ($datain == "changenamenode") {
    $backinfoss = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['backToNode'], 'callback_data' => "node_" . $user['Processing_value_one']],
            ]
        ]
    ]);
    $textnode = $textbotlang['Admin']['node']['askName'];
    Editmessagetext($from_id, $message_id, $textnode, $backinfoss);
    step("getnamenode", $from_id);
} elseif ($user['step'] == "getnamenode") {
    $config = array(
        'name' => $text
    );
    Modifyuser_node($user['Processing_value'], $user['Processing_value_one'], $config);
    $backinfoss = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['backToNode'], 'callback_data' => "node_" . $user['Processing_value_one']],
            ]
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['node']['nameSaved'], $backinfoss, 'HTML');
    step('home', $from_id);
} elseif ($datain == "changeipnode") {
    $backinfoss = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['backToNode'], 'callback_data' => "node_" . $user['Processing_value_one']],
            ]
        ]
    ]);
    $textnode = $textbotlang['Admin']['node']['askIp'];
    Editmessagetext($from_id, $message_id, $textnode, $backinfoss);
    step("getipnodeset", $from_id);
} elseif ($user['step'] == "getipnodeset") {
    $config = array(
        'address' => $text
    );
    Modifyuser_node($user['Processing_value'], $user['Processing_value_one'], $config);
    $backinfoss = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['backToNode'], 'callback_data' => "node_" . $user['Processing_value_one']],
            ]
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['node']['ipSaved'], $backinfoss, 'HTML');
    step('home', $from_id);
} elseif ($datain == "reconnectnode") {
    reconnect_node($user['Processing_value'], $user['Processing_value_one']);
    $backinfoss = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['backToNode'], 'callback_data' => "node_" . $user['Processing_value_one']],
            ]
        ]
    ]);
    $textnode = $textbotlang['Admin']['node']['reconnected'];
    Editmessagetext($from_id, $message_id, $textnode, $backinfoss);
} elseif ($datain == "removenode") {
    removenode($user['Processing_value'], $user['Processing_value_one']);
    $backinfoss = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['backToNode'], 'callback_data' => "bakcnode"],
            ]
        ]
    ]);
    $textnode = $textbotlang['Admin']['node']['deleted'];
    Editmessagetext($from_id, $message_id, $textnode, $backinfoss);
} elseif ($text == $textbotlang['keyboard']['financial'] && $adminrulecheck['rule'] == "administrator") {
    $cartotcart = getPaySettingValue('Cartstatus', 'offcard');
    $plisio = getPaySettingValue('nowpaymentstatus', 'offnowpayment');
    $arzireyali1 = getPaySettingValue('statusSwapWallet', 'offSwapinoBot');
    if ($arzireyali1 != "onSwapinoBot" && $arzireyali1 != "offSwapinoBot") {
        update("PaySetting", "ValuePay", "onSwapinoBot", "NamePay", "statusSwapWallet");
        $arzireyali1 = getPaySettingValue('statusSwapWallet', 'offSwapinoBot');
    }
    $arzireyali2 = getPaySettingValue('statustarnado', 'offternado');
    $arzireyali3 = getPaySettingValue('statusiranpay3', 'offiranpay3');
    $abangateway4 = getPaySettingValue('statusiranpay4', 'offiranpay4');
    $abangateway4text = $abangateway4 == 'oniranpay4'
        ? $textbotlang['Admin']['Status']['statuson']
        : $textbotlang['Admin']['Status']['statusoff'];
    $aqayepardakht = getPaySettingValue('statusaqayepardakht', 'offaqayepardakht');
    $zarinpal = getPaySettingValue('zarinpalstatus', 'offzarinpal');
    $affilnecurrency = getPaySettingValue('digistatus', 'offdigi');
    $paymentstatussnotverify = getPaySettingValue('paymentstatussnotverify', 'offpaymentstatus');
    $paymentsstartelegram = getPaySettingValue('statusstar', '0');
    $payment_status_nowpayment = getPaySettingValue('statusnowpayment', '0');
    $cartotcartstatus = [
        'oncard' => $textbotlang['Admin']['Status']['statuson'],
        'offcard' => $textbotlang['Admin']['Status']['statusoff']
    ][$cartotcart];
    $plisiostatus = [
        'onnowpayment' => $textbotlang['Admin']['Status']['statuson'],
        'offnowpayment' => $textbotlang['Admin']['Status']['statusoff']
    ][$plisio];
    $arzireyali1status = [
        'onSwapinoBot' => $textbotlang['Admin']['Status']['statuson'],
        'offSwapinoBot' => $textbotlang['Admin']['Status']['statusoff']
    ][$arzireyali1];
    $arzireyali2status = [
        'onternado' => $textbotlang['Admin']['Status']['statuson'],
        'offternado' => $textbotlang['Admin']['Status']['statusoff']
    ][$arzireyali2];
    $aqayepardakhtstatus = [
        'onaqayepardakht' => $textbotlang['Admin']['Status']['statuson'],
        'offaqayepardakht' => $textbotlang['Admin']['Status']['statusoff']
    ][$aqayepardakht];
    $zarinpalstatus = [
        'onzarinpal' => $textbotlang['Admin']['Status']['statuson'],
        'offzarinpal' => $textbotlang['Admin']['Status']['statusoff']
    ][$zarinpal];
    $affilnecurrencystatus = [
        'ondigi' => $textbotlang['Admin']['Status']['statuson'],
        'offdigi' => $textbotlang['Admin']['Status']['statusoff']
    ][$affilnecurrency];
    $arzireyali3text = [
        'oniranpay3' => $textbotlang['Admin']['Status']['statuson'],
        'offiranpay3' => $textbotlang['Admin']['Status']['statusoff']
    ][$arzireyali3];
    $paymentstar = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$paymentsstartelegram];
    $now_payment_status = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$payment_status_nowpayment];
    $Bot_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "actions"],
                ['text' => $textbotlang['Admin']['Status']['statusSubject'], 'callback_data' => "subjectde"],
                ['text' => $textbotlang['Admin']['Status']['subject'], 'callback_data' => "subject"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "cartsetting"],
                ['text' => $cartotcartstatus, 'callback_data' => "editpayment-Cartstatus-$cartotcart"],
                ['text' => $textbotlang['keyboard']['cartToCartGateway'], 'callback_data' => "carttocart"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "plisiosetting"],
                ['text' => $plisiostatus, 'callback_data' => "editpayment-plisio-$plisio"],
                ['text' => "📌 plisio", 'callback_data' => "plisio"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "nowpaymentsetting"],
                ['text' => $now_payment_status, 'callback_data' => "editpayment-nowpayment-$payment_status_nowpayment"],
                ['text' => "📌 nowpayment", 'callback_data' => "nowpayment"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "iranpay1setting"],
                ['text' => $arzireyali1status, 'callback_data' => "editpayment-arzireyali1-$arzireyali1"],
                ['text' => $textbotlang['keyboard']['iranPay1Label'], 'callback_data' => "arzireyali1"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "iranpay2setting"],
                ['text' => $arzireyali2status, 'callback_data' => "editpayment-arzireyali2-$arzireyali2"],
                ['text' => $textbotlang['keyboard']['iranPay2Label'], 'callback_data' => "arzireyali2"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "iranpay4setting"],
                ['text' => $abangateway4text, 'callback_data' => "editpayment-oniranpay4-$abangateway4"],
                ['text' => $textbotlang['keyboard']['iranPay4Label'], 'callback_data' => "oniranpay4"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "iranpay3setting"],
                ['text' => $arzireyali3text, 'callback_data' => "editpayment-oniranpay3-$arzireyali3"],
                ['text' => $textbotlang['keyboard']['iranPay3Label'], 'callback_data' => "oniranpay3"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "aqayepardakhtsetting"],
                ['text' => $aqayepardakhtstatus, 'callback_data' => "editpayment-aqayepardakht-$aqayepardakht"],
                ['text' => $textbotlang['keyboard']['aqayePardakhtGateway'], 'callback_data' => "aqayepardakht"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "zarinpalsetting"],
                ['text' => $zarinpalstatus, 'callback_data' => "editpayment-zarinpal-$zarinpal"],
                ['text' => $textbotlang['keyboard']['zarinPalGateway'], 'callback_data' => "zarinpal"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "affilnecurrencysetting"],
                ['text' => $affilnecurrencystatus, 'callback_data' => "editpayment-affilnecurrency-$affilnecurrency"],
                ['text' => $textbotlang['keyboard']['cryptoOfflinePayment'], 'callback_data' => "affilnecurrency"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "startelegram"],
                ['text' => $paymentstar, 'callback_data' => "editpayment-startelegram-$paymentsstartelegram"],
                ['text' => "💫Star Telegram", 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['keyboard']['maxChargeBalance'], 'callback_data' => "maxbalanceaccount"],
                ['text' => $textbotlang['keyboard']['minChargeBalance'], 'callback_data' => "mainbalanceaccount"],
            ],
            [
                ['text' => $textbotlang['keyboard']['walletAddress'], 'callback_data' => "walletaddress"],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['gateway']['intro'], $Bot_Status, 'HTML');
} elseif ($text == $textbotlang['keyboard']['renewalCashback'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['price']['askRenewCashback'], $backadmin, 'HTML');
    step('getpricecashback', $from_id);
} elseif ($user['step'] == "getpricecashback") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidTime'], $backadmin, 'HTML');
        return;
    }
    savedata("clear", "price_cashback", $text);
    sendmessage($from_id, $textbotlang['Admin']['price']['askUserGroup'], $backadmin, 'HTML');
    step('getagent', $from_id);
} elseif ($user['step'] == "getagent") {
    if (!in_array($text, ['f', 'n', 'n2'])) {
        sendmessage($from_id, $textbotlang['Admin']['price']['invalidUserGroup'], $backadmin, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    if ($text == "f") {
        update("shopSetting", "value", $userdata['price_cashback'], "Namevalue", "chashbackextend");
    } else {
        $shop_cashbackagent = json_decode(select("shopSetting", "*", "Namevalue", "chashbackextend_agent")['value'], true);
        $shop_cashbackagent[$text] = $userdata['price_cashback'];
        update("shopSetting", "value", json_encode($shop_cashbackagent), "Namevalue", "chashbackextend_agent");
    }
    sendmessage($from_id, $textbotlang['Admin']['price']['amountSaved'], $shopkeyboard, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/^editpayment-(.*)-(.*)/', $datain, $dataget)) {
    $type = $dataget[1];
    $value = $dataget[2];
    if ($type == "Cartstatus") {
        if ($value == "oncard") {
            $valuenew = "offcard";
        } else {
            $valuenew = "oncard";
        }
        update("PaySetting", "ValuePay", $valuenew, "NamePay", "Cartstatus");
    } elseif ($type == "plisio") {
        if ($value == "onnowpayment") {
            $valuenew = "offnowpayment";
        } else {
            $valuenew = "onnowpayment";
        }
        update("PaySetting", "ValuePay", $valuenew, "NamePay", "nowpaymentstatus");
    } elseif ($type == "arzireyali1") {
        if ($value == "onSwapinoBot") {
            $valuenew = "offSwapinoBot";
        } else {
            $valuenew = "onSwapinoBot";
        }
        update("PaySetting", "ValuePay", $valuenew, "NamePay", "statusSwapWallet");
    } elseif ($type == "arzireyali2") {
        if ($value == "onternado") {
            $valuenew = "offternado";
        } else {
            $valuenew = "onternado";
        }
        update("PaySetting", "ValuePay", $valuenew, "NamePay", "statustarnado");
    } elseif ($type == "aqayepardakht") {
        if ($value == "onaqayepardakht") {
            $valuenew = "offaqayepardakht";
        } else {
            $valuenew = "onaqayepardakht";
        }
        update("PaySetting", "ValuePay", $valuenew, "NamePay", "statusaqayepardakht");
    } elseif ($type == "zarinpal") {
        if ($value == "onzarinpal") {
            $valuenew = "offzarinpal";
        } else {
            $valuenew = "onzarinpal";
        }
        update("PaySetting", "ValuePay", $valuenew, "NamePay", "zarinpalstatus");
    } elseif ($type == "affilnecurrency") {
        if ($value == "ondigi") {
            $valuenew = "offdigi";
        } else {
            $valuenew = "ondigi";
        }
        update("PaySetting", "ValuePay", $valuenew, "NamePay", "digistatus");
    } elseif ($type == "oniranpay4") {
        $valuenew = $value == "oniranpay4" ? "offiranpay4" : "oniranpay4";
        update("PaySetting", "ValuePay", $valuenew, "NamePay", "statusiranpay4");
    } elseif ($type == "oniranpay3") {
        if ($value == "oniranpay3") {
            $valuenew = "offiranpay3";
        } else {
            $valuenew = "oniranpay3";
        }
        update("PaySetting", "ValuePay", $valuenew, "NamePay", "statusiranpay3");
    } elseif ($type == "startelegram") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("PaySetting", "ValuePay", $valuenew, "NamePay", "statusstar");
    } elseif ($type == "nowpayment") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("PaySetting", "ValuePay", $valuenew, "NamePay", "statusnowpayment");
    }
    $zarinpal = getPaySettingValue('zarinpalstatus', 'offzarinpal');
    $cartotcart = getPaySettingValue('Cartstatus', 'offcard');
    $plisio = getPaySettingValue('nowpaymentstatus', 'offnowpayment');
    $arzireyali1 = getPaySettingValue('statusSwapWallet', 'offSwapinoBot');
    $arzireyali2 = getPaySettingValue('statustarnado', 'offternado');
    $aqayepardakht = getPaySettingValue('statusaqayepardakht', 'offaqayepardakht');
    $affilnecurrency = getPaySettingValue('digistatus', 'offdigi');
    $arzireyali3 = getPaySettingValue('statusiranpay3', 'offiranpay3');
    $abangateway4 = getPaySettingValue('statusiranpay4', 'offiranpay4');
    $abangateway4text = $abangateway4 == 'oniranpay4'
        ? $textbotlang['Admin']['Status']['statuson']
        : $textbotlang['Admin']['Status']['statusoff'];
    $paymentstatussnotverify = getPaySettingValue('paymentstatussnotverify', 'offpaymentstatus');
    $paymentsstartelegram = getPaySettingValue('statusstar', '0');
    $payment_status_nowpayment = getPaySettingValue('statusnowpayment', '0');
    $cartotcartstatus = [
        'oncard' => $textbotlang['Admin']['Status']['statuson'],
        'offcard' => $textbotlang['Admin']['Status']['statusoff']
    ][$cartotcart];
    $plisiostatus = [
        'onnowpayment' => $textbotlang['Admin']['Status']['statuson'],
        'offnowpayment' => $textbotlang['Admin']['Status']['statusoff']
    ][$plisio];
    $arzireyali1status = [
        'onSwapinoBot' => $textbotlang['Admin']['Status']['statuson'],
        'offSwapinoBot' => $textbotlang['Admin']['Status']['statusoff']
    ][$arzireyali1];
    $arzireyali2status = [
        'onternado' => $textbotlang['Admin']['Status']['statuson'],
        'offternado' => $textbotlang['Admin']['Status']['statusoff']
    ][$arzireyali2];
    $aqayepardakhtstatus = [
        'onaqayepardakht' => $textbotlang['Admin']['Status']['statuson'],
        'offaqayepardakht' => $textbotlang['Admin']['Status']['statusoff']
    ][$aqayepardakht];
    $zarinpalstatus = [
        'onzarinpal' => $textbotlang['Admin']['Status']['statuson'],
        'offzarinpal' => $textbotlang['Admin']['Status']['statusoff']
    ][$zarinpal];
    $affilnecurrencystatus = [
        'ondigi' => $textbotlang['Admin']['Status']['statuson'],
        'offdigi' => $textbotlang['Admin']['Status']['statusoff']
    ][$affilnecurrency];
    $arzireyali3text = [
        'oniranpay3' => $textbotlang['Admin']['Status']['statuson'],
        'offiranpay3' => $textbotlang['Admin']['Status']['statusoff']
    ][$arzireyali3];
    $paymentstar = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$paymentsstartelegram];
    $now_payment_status = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$payment_status_nowpayment];
    $Bot_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "actions"],
                ['text' => $textbotlang['Admin']['Status']['statusSubject'], 'callback_data' => "subjectde"],
                ['text' => $textbotlang['Admin']['Status']['subject'], 'callback_data' => "subject"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "cartsetting"],
                ['text' => $cartotcartstatus, 'callback_data' => "editpayment-Cartstatus-$cartotcart"],
                ['text' => $textbotlang['keyboard']['cartToCartGateway'], 'callback_data' => "carttocart"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "plisiosetting"],
                ['text' => $plisiostatus, 'callback_data' => "editpayment-plisio-$plisio"],
                ['text' => "📌 plisio", 'callback_data' => "plisio"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "nowpaymentsetting"],
                ['text' => $now_payment_status, 'callback_data' => "editpayment-nowpayment-$payment_status_nowpayment"],
                ['text' => "📌 nowpayment", 'callback_data' => "nowpayment"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "iranpay1setting"],
                ['text' => $arzireyali1status, 'callback_data' => "editpayment-arzireyali1-$arzireyali1"],
                ['text' => $textbotlang['keyboard']['iranPay1Label'], 'callback_data' => "arzireyali1"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "iranpay2setting"],
                ['text' => $arzireyali2status, 'callback_data' => "editpayment-arzireyali2-$arzireyali2"],
                ['text' => $textbotlang['keyboard']['iranPay2Label'], 'callback_data' => "arzireyali2"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "iranpay4setting"],
                ['text' => $abangateway4text, 'callback_data' => "editpayment-oniranpay4-$abangateway4"],
                ['text' => $textbotlang['keyboard']['iranPay4Label'], 'callback_data' => "oniranpay4"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "iranpay3setting"],
                ['text' => $arzireyali3text, 'callback_data' => "editpayment-oniranpay3-$arzireyali3"],
                ['text' => $textbotlang['keyboard']['iranPay3Label'], 'callback_data' => "oniranpay3"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "aqayepardakhtsetting"],
                ['text' => $aqayepardakhtstatus, 'callback_data' => "editpayment-aqayepardakht-$aqayepardakht"],
                ['text' => $textbotlang['keyboard']['aqayePardakhtGateway'], 'callback_data' => "aqayepardakht"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "zarinpalsetting"],
                ['text' => $zarinpalstatus, 'callback_data' => "editpayment-zarinpal-$zarinpal"],
                ['text' => $textbotlang['keyboard']['zarinPalGateway'], 'callback_data' => "zarinpal"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "affilnecurrencysetting"],
                ['text' => $affilnecurrencystatus, 'callback_data' => "editpayment-affilnecurrency-$affilnecurrency"],
                ['text' => $textbotlang['keyboard']['cryptoOfflinePayment'], 'callback_data' => "affilnecurrency"],
            ],
            [
                ['text' => $textbotlang['keyboard']['settings'], 'callback_data' => "startelegram"],
                ['text' => $paymentstar, 'callback_data' => "editpayment-startelegram-$paymentsstartelegram"],
                ['text' => "💫Star Telegram", 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['keyboard']['maxChargeBalance'], 'callback_data' => "maxbalanceaccount"],
                ['text' => $textbotlang['keyboard']['minChargeBalance'], 'callback_data' => "mainbalanceaccount"],
            ],
            [
                ['text' => $textbotlang['keyboard']['walletAddress'], 'callback_data' => "walletaddress"],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['gateway']['intro'], $Bot_Status);
} elseif ($text == $textbotlang['keyboard']['cashbackCartToCart']) {
    sendmessage($from_id, $textbotlang['Admin']['price']['askPaymentCashback'], $backadmin, 'HTML');
    step("getcashcart", $from_id);
} elseif ($user['step'] == "getcashcart") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['price']['priceSaved'], $CartManage, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "chashbackcart");
} elseif ($text == $textbotlang['keyboard']['cashbackAqayePardakht']) {
    sendmessage($from_id, $textbotlang['Admin']['price']['askPaymentCashback'], $backadmin, 'HTML');
    step("getcashahaypar", $from_id);
} elseif ($user['step'] == "getcashahaypar") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['price']['priceSaved'], $CartManage, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "chashbackaqaypardokht");
} elseif ($text == $textbotlang['keyboard']['feeStatusIranPay2'] && $adminrulecheck['rule'] == "administrator") {
    $feeStatusNow = select("PaySetting", "ValuePay", "NamePay", "feestatusternado", "select")['ValuePay'] ?? 'offfeeternado';
    $feeStatusNew = ($feeStatusNow === 'onfeeternado') ? 'offfeeternado' : 'onfeeternado';
    update("PaySetting", "ValuePay", $feeStatusNew, "NamePay", "feestatusternado");
    if ($feeStatusNew === 'onfeeternado') {
        $feeValueNow = cubepayFeeValue();
        $feeDescNow = $feeValueNow <= 100
            ? $feeValueNow . '%'
            : number_format($feeValueNow) . ' T';
        sendmessage($from_id, sprintf($textbotlang['Admin']['gateway']['cubepayFeeOn'], $feeDescNow), $trnado, 'HTML');
    } else {
        sendmessage($from_id, $textbotlang['Admin']['gateway']['cubepayFeeOff'], $trnado, 'HTML');
    }
} elseif ($text == $textbotlang['keyboard']['feeAmountIranPay2'] && $adminrulecheck['rule'] == "administrator") {
    $feeValueNow = cubepayFeeValue();
    sendmessage($from_id, sprintf($textbotlang['Admin']['gateway']['cubepayFeeAsk'], $feeValueNow), $backadmin, 'HTML');
    step("getfeeiranpay2", $from_id);
} elseif ($user['step'] == "getfeeiranpay2") {

    update("PaySetting", "ValuePay", (string) $text, "NamePay", "feeternado");
    $feeExample = number_format(cubepayApplyFee(100000, $text));
    $feeSavedText = $text <= 100
        ? sprintf($textbotlang['Admin']['gateway']['cubepayFeeSavedPercent'], $text, $feeExample)
        : sprintf($textbotlang['Admin']['gateway']['cubepayFeeSavedFixed'], number_format($text), $feeExample);
    sendmessage($from_id, $feeSavedText, $trnado, 'HTML');
    step("home", $from_id);
} elseif ($text == $textbotlang['keyboard']['cashbackIranPay2']) {
    sendmessage($from_id, $textbotlang['Admin']['price']['askPaymentCashback'], $backadmin, 'HTML');
    step("getcashiranpay2", $from_id);
} elseif ($user['step'] == "getcashiranpay2") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['price']['priceSaved'], $trnado, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "chashbackiranpay2");
} elseif ($text == $textbotlang['keyboard']['cashbackIranPay3']) {
    sendmessage($from_id, $textbotlang['Admin']['price']['askPaymentCashback'], $backadmin, 'HTML');
    step("getcashiranpay4", $from_id);
} elseif ($user['step'] == "getcashiranpay4") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['price']['priceSaved'], $CartManage, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "chashbackiranpay3");
} elseif ($text == $textbotlang['keyboard']['cashbackIranPay1']) {
    sendmessage($from_id, $textbotlang['Admin']['price']['askPaymentCashback'], $backadmin, 'HTML');
    step("getcashiranpay1", $from_id);
} elseif ($user['step'] == "getcashiranpay1") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['price']['priceSaved'], $Swapinokey, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "chashbackiranpay1");
} elseif ($text == $textbotlang['keyboard']['cashbackPlisio']) {
    sendmessage($from_id, $textbotlang['Admin']['price']['askPaymentCashback'], $backadmin, 'HTML');
    step("getcashplisio", $from_id);
} elseif ($user['step'] == "getcashplisio") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['price']['priceSaved'], $CartManage, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "chashbackplisio");
} elseif ($text == $textbotlang['keyboard']['cashbackNowPayment']) {
    sendmessage($from_id, $textbotlang['Admin']['price']['askPaymentCashback'], $backadmin, 'HTML');
    step("getcashnowpayment", $from_id);
} elseif ($user['step'] == "getcashnowpayment") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['price']['priceSaved'], $nowpayment_setting_keyboard, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "cashbacknowpayment");
} elseif ($text == $textbotlang['keyboard']['cashbackZarinPal']) {
    sendmessage($from_id, $textbotlang['Admin']['price']['askPaymentCashback'], $backadmin, 'HTML');
    step("getcashzarinpal", $from_id);
} elseif ($user['step'] == "getcashzarinpal") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['price']['priceSaved'], $keyboardzarinpal, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "chashbackzarinpal");
} elseif ($text == $textbotlang['keyboard']['addConfig']) {
    $product = [];
    $stmt = $pdo->prepare("SELECT * FROM product WHERE Location = :text or Location = '/all' ");
    $stmt->bindParam(':text', $user['Processing_value'], PDO::PARAM_STR);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
        ['text' => $textbotlang['keyboard']['backToAdminMenu']],
    ];
    $json_list_product_list_admin = json_encode($list_product);
    sendmessage($from_id, $textbotlang['Admin']['config']['askProductName'], $json_list_product_list_admin, 'HTML');
    step('getnameproduct', $from_id);
    savedata("clear", "namepanel", $user['Processing_value']);
} elseif ($user['step'] == "getnameproduct") {
    $product_check = select("product", "*", "name_product", $text, "select");
    if ($product_check == false && $text != $textbotlang['Admin']['config']['testKeyword']) {
        sendmessage($from_id, $textbotlang['Admin']['config']['productNotFound'], $backadmin, 'HTML');
        return;
    }
    if ($text == $textbotlang['Admin']['config']['testKeyword']) {
        savedata("save", "name_product", "usertest");
    } else {
        savedata("save", "name_product", $product_check['code_product']);
    }
    sendmessage($from_id, $textbotlang['Admin']['config']['askConfigs'], $backadmin, 'HTML');
    step("getconfigtext", $from_id);
} elseif ($user['step'] == "getconfigtext") {
    $userdata = json_decode($user['Processing_value'], true);
    step('home', $from_id);
    $config = parseConfigs($text);
    sendmessage($from_id, $textbotlang['Admin']['config']['saved'] . count($config), $optionManualsale, 'HTML');
    $panel = select("marzban_panel", "*", "name_panel", $userdata['namepanel'], "select");
    if ($panel == false) {
        sendmessage($from_id, $textbotlang['Admin']['config']['saveError'], $backadmin, 'HTML');
        return;
    }
    $status = "active";
    foreach ($config as $content_config) {

        $stmt = $pdo->prepare("INSERT IGNORE INTO manualsell (codepanel,namerecord,contentrecord,status,codeproduct) VALUES (:codepanel,:namerecord,:contentrecord,:status,:codeproduct)");
        $stmt->bindParam(':codepanel', $panel['code_panel']);
        $stmt->bindParam(':namerecord', $content_config['name']);
        $stmt->bindParam(':contentrecord', $content_config['config']);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':codeproduct', $userdata['name_product']);
        $stmt->execute();
    }
    update("user", "Processing_value", $panel['name_panel'], "id", $from_id);
} elseif ($text == $textbotlang['Admin']['config']['btnDelete']) {
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $listconfig = [];
    $stmt = $pdo->prepare("SELECT * FROM manualsell WHERE codepanel = :mp14");
    $stmt->execute([':mp14' => $panel['code_panel']]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $listconfig[] = [$row['namerecord']];
    }
    $list_configmanual = [
        'keyboard' => [],
        'resize_keyboard' => true,
    ];
    $list_configmanual['keyboard'][] = [
        ['text' => $textbotlang['keyboard']['backToAdminMenu']],
    ];
    foreach ($listconfig as $button) {
        $list_configmanual['keyboard'][] = [
            ['text' => $button[0]]
        ];
    }
    $json_list_manualconfig_list = json_encode($list_configmanual);
    sendmessage($from_id, $textbotlang['Admin']['config']['askDeleteName'], $json_list_manualconfig_list, 'HTML');
    step("getnameremove", $from_id);
} elseif ($user['step'] == "getnameremove") {
    sendmessage($from_id, $textbotlang['Admin']['config']['deleted'], $optionManualsale, 'HTML');
    $stmt = $pdo->prepare("DELETE FROM manualsell WHERE namerecord = ?");
    $stmt->bindParam(1, $text);
    $stmt->execute();
    step("home", $from_id);
} elseif ($text == $textbotlang['keyboard']['changeLocationPrice'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['price']['askChangeLocation'], $backadmin, 'HTML');
    step('setpricechangelocation', $from_id);
} elseif ($user['step'] == "setpricechangelocation") {
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['price']['changeLocationSaved']);
    update("marzban_panel", "priceChangeloc", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['extraVolumePrice'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['price']['askExtraVolume'], $backadmin, 'HTML');
    step('GetPriceExtra', $from_id);
} elseif ($user['step'] == "GetPriceExtra") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['invalidPrice'], $backadmin, 'HTML');
        return;
    }
    savedata("clear", "namepanel", $user['Processing_value']);
    savedata("save", "price", $text);
    sendmessage($from_id, $textbotlang['Admin']['price']['selectUserGroup'] . "\n" . $textbotlang['Admin']['price']['allGroupsHint'], $backuser, 'HTML');
    step('gettypeextra', $from_id);
} elseif ($user['step'] == "gettypeextra") {
    $agentst = ["n", "n2", "f", "all"];
    if (!in_array($text, $agentst)) {
        sendmessage($from_id, $textbotlang['Admin']['agent']['invalidTypeAgent'], $backadmin, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    $typepanel = select("marzban_panel", "*", "name_panel", $userdata['namepanel'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['price']['priceSaved']);
    $eextraprice = json_decode($typepanel['priceextravolume'], true);
    if ($text == 'all') {
        $eextraprice["f"] = $userdata['price'];
        $eextraprice["n"] = $userdata['price'];
        $eextraprice["n2"] = $userdata['price'];
    } else {
        $eextraprice[$text] = $userdata['price'];
    }
    $eextraprice = json_encode($eextraprice);
    update("marzban_panel", "priceextravolume", $eextraprice, "name_panel", $userdata['namepanel']);
    update("user", "Processing_value", $userdata['namepanel'], "id", $from_id);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['customVolumePrice'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['price']['askCustomVolume'], $backadmin, 'HTML');
    step('GetPricecustomvo', $from_id);
} elseif ($user['step'] == "GetPricecustomvo") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['invalidPrice'], $backadmin, 'HTML');
        return;
    }
    savedata("clear", "namepanel", $user['Processing_value']);
    savedata("save", "price", $text);
    sendmessage($from_id, $textbotlang['Admin']['price']['selectUserGroup'] . "\n" . $textbotlang['Admin']['price']['allGroupsHint'], $backuser, 'HTML');
    step('gettypeextracustom', $from_id);
} elseif ($user['step'] == "gettypeextracustom") {
    $agentst = ["n", "n2", "f", "all"];
    if (!in_array($text, $agentst)) {
        sendmessage($from_id, $textbotlang['Admin']['agent']['invalidTypeAgent'], $backadmin, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    $typepanel = select("marzban_panel", "*", "name_panel", $userdata['namepanel'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['price']['priceSaved']);
    $eextraprice = json_decode($typepanel['pricecustomvolume'], true);
    if ($text == 'all') {
        $eextraprice["f"] = $userdata['price'];
        $eextraprice["n"] = $userdata['price'];
        $eextraprice["n2"] = $userdata['price'];
    } else {
        $eextraprice[$text] = $userdata['price'];
    }
    $eextraprice = json_encode($eextraprice);
    update("marzban_panel", "pricecustomvolume", $eextraprice, "name_panel", $userdata['namepanel']);
    update("user", "Processing_value", $userdata['namepanel'], "id", $from_id);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['extraTimePrice'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['price']['askExtraTime'], $backadmin, 'HTML');
    step('GetPricetimeextra', $from_id);
} elseif ($user['step'] == "GetPricetimeextra") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['invalidPrice'], $backadmin, 'HTML');
        return;
    }
    savedata("clear", "namepanel", $user['Processing_value']);
    savedata("save", "price", $text);
    sendmessage($from_id, $textbotlang['Admin']['price']['selectUserGroup'] . "\n" . $textbotlang['Admin']['price']['allGroupsHint'], $backuser, 'HTML');
    step('gettypeextratime', $from_id);
} elseif ($user['step'] == "gettypeextratime") {
    $agentst = ["n", "n2", "f", "all"];
    if (!in_array($text, $agentst)) {
        sendmessage($from_id, $textbotlang['Admin']['agent']['invalidTypeAgent'], $backadmin, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    $typepanel = select("marzban_panel", "*", "name_panel", $userdata['namepanel'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['price']['priceSaved']);
    $eextraprice = json_decode($typepanel['priceextratime'], true);
    if ($text == 'all') {
        $eextraprice["f"] = $userdata['price'];
        $eextraprice["n"] = $userdata['price'];
        $eextraprice["n2"] = $userdata['price'];
    } else {
        $eextraprice[$text] = $userdata['price'];
    }
    $eextraprice = json_encode($eextraprice);
    update("marzban_panel", "priceextratime", $eextraprice, "name_panel", $userdata['namepanel']);
    update("user", "Processing_value", $userdata['namepanel'], "id", $from_id);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['customTimePrice'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['price']['askCustomTime'], $backadmin, 'HTML');
    step('GetPriceExtratime', $from_id);
} elseif ($user['step'] == "GetPriceExtratime") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['invalidPrice'], $backadmin, 'HTML');
        return;
    }
    savedata("clear", "namepanel", $user['Processing_value']);
    savedata("save", "price", $text);
    sendmessage($from_id, $textbotlang['Admin']['price']['selectUserGroup'] . "\n" . $textbotlang['Admin']['price']['allGroupsHint'], $backuser, 'HTML');
    step('gettypeextratimecustom', $from_id);
} elseif ($user['step'] == "gettypeextratimecustom") {
    $agentst = ["n", "n2", "f", "all"];
    if (!in_array($text, $agentst)) {
        sendmessage($from_id, $textbotlang['Admin']['agent']['invalidTypeAgent'], $backadmin, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    $typepanel = select("marzban_panel", "*", "name_panel", $userdata['namepanel'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['price']['priceSaved']);
    $eextraprice = json_decode($typepanel['pricecustomtime'], true);
    if ($text == 'all') {
        $eextraprice["f"] = $userdata['price'];
        $eextraprice["n"] = $userdata['price'];
        $eextraprice["n2"] = $userdata['price'];
    } else {
        $eextraprice[$text] = $userdata['price'];
    }
    $eextraprice = json_encode($eextraprice);
    update("marzban_panel", "pricecustomtime", $eextraprice, "name_panel", $userdata['namepanel']);
    update("user", "Processing_value", $userdata['namepanel'], "id", $from_id);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['showCartAfterFirstPay'] && $adminrulecheck['rule'] == "administrator") {
    $paymentverify = select("PaySetting", "ValuePay", "NamePay", "checkpaycartfirst", "select")['ValuePay'];
    $keyboardverify = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $paymentverify, 'callback_data' => $paymentverify],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['card']['afterFirstPayDesc'], $keyboardverify, 'HTML');
} elseif ($datain == "onpayverify") {
    update("PaySetting", "ValuePay", "offpayverify", "NamePay", "checkpaycartfirst");
    $paymentverify = select("PaySetting", "ValuePay", "NamePay", "checkpaycartfirst", "select")['ValuePay'];
    $keyboardverify = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $paymentverify, 'callback_data' => $paymentverify],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['card']['afterFirstPayOff'], $keyboardverify);
} elseif ($datain == "offpayverify") {
    update("PaySetting", "ValuePay", "onpayverify", "NamePay", "checkpaycartfirst");
    $paymentverify = select("PaySetting", "ValuePay", "NamePay", "checkpaycartfirst", "select")['ValuePay'];
    $keyboardverify = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $paymentverify, 'callback_data' => $paymentverify],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['card']['afterFirstPayOn'], $keyboardverify);
} elseif ($text == $textbotlang['keyboard']['editConfig']) {
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $listconfig = [];
    $stmt = $pdo->prepare("SELECT * FROM manualsell WHERE codepanel = :mp15");
    $stmt->execute([':mp15' => $panel['code_panel']]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $listconfig[] = [$row['namerecord']];
    }
    $list_configmanual = [
        'keyboard' => [],
        'resize_keyboard' => true,
    ];
    $list_configmanual['keyboard'][] = [
        ['text' => $textbotlang['keyboard']['backToAdminMenu']],
    ];
    foreach ($listconfig as $button) {
        $list_configmanual['keyboard'][] = [
            ['text' => $button[0]]
        ];
    }
    $json_list_manualconfig_list = json_encode($list_configmanual);
    sendmessage($from_id, $textbotlang['Admin']['config']['askEditName'], $json_list_manualconfig_list, 'HTML');
    step("getnameedit", $from_id);
} elseif ($user['step'] == "getnameedit") {
    sendmessage($from_id, $textbotlang['Admin']['selectOption4'], $configedit, 'HTML');
    step("home", $from_id);
    update("user", "Processing_value_one", $text, "id", $from_id);
} elseif ($text == $textbotlang['keyboard']['configDetails']) {
    sendmessage($from_id, $textbotlang['Admin']['config']['askNewContent'], $backadmin, 'HTML');
    step("getcontentedit", $from_id);
} elseif ($user['step'] == "getcontentedit") {
    sendmessage($from_id, $textbotlang['Admin']['saved'], $optionManualsale, 'HTML');
    update("manualsell", "contentrecord", $text, "namerecord", $user['Processing_value_one']);
} elseif ($text == $textbotlang['keyboard']['increaseGroupPrice']) {
    sendmessage($from_id, $textbotlang['Admin']['price']['askIncreasePanel'], $json_list_marzban_panel, 'HTML');
    step("getaddpricepeoductloc", $from_id);
} elseif ($user['step'] == "getaddpricepeoductloc") {
    sendmessage($from_id, $textbotlang['Admin']['price']['askPriceGroup'], $backadmin, 'HTML');
    savedata("clear", "namepanel", $text);
    step("getagentaddpriceproduct", $from_id);
} elseif ($user['step'] == "getagentaddpriceproduct") {
    $keyboard_type_price = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['percentage'], 'callback_data' => 'typeaddprice_percent'],
                ['text' => $textbotlang['keyboard']['fixed'], 'callback_data' => 'typeaddprice_static'],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['price']['askPercentOrFixed'], $keyboard_type_price, 'HTML');
    savedata("save", "agent", $text);
    step("home", $from_id);
} elseif (preg_match('/^typeaddprice_(\w+)/', $datain, $dataget)) {
    $type = $dataget[1];
    deletemessage($from_id, $message_id);
    if ($type == "static") {
        sendmessage($from_id, $textbotlang['Admin']['price']['askAmount'], $backadmin, 'HTML');
    } else {
        sendmessage($from_id, $textbotlang['Admin']['price']['askPercent'], $backadmin, 'HTML');
    }
    savedata("save", "type_price", $type);
    step("getaddpricepeoduct", $from_id);
} elseif ($user['step'] == "getaddpricepeoduct") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    $stmt = $pdo->prepare("SELECT * FROM product WHERE Location = :mp16 AND agent = :mp17");
    $stmt->execute([':mp16' => $userdata['namepanel'], ':mp17' => $userdata['agent']]);
    $product = $stmt->fetchAll();
    if ($product == false) {
        sendmessage($from_id, $textbotlang['Admin']['price']['noProductFound'], $shopkeyboard, 'HTML');
        step("home", $from_id);
        return;
    }
    if ($userdata['type_price'] == "static") {
        $stmt = $pdo->prepare("UPDATE  product set price_product = price_product + :price WHERE Location = :location AND agent = :agent");
        $stmt->bindParam(':price', $text, PDO::PARAM_STR);
    } else {
        $stmt = $pdo->prepare("UPDATE  product set price_product = price_product + (price_product * :price / 100)  WHERE Location = :location AND agent = :agent");
        $stmt->bindParam(':price', $text, PDO::PARAM_STR);
    }
    $stmt->bindParam(':location', $userdata['namepanel'], PDO::PARAM_STR);
    $stmt->bindParam(':agent', $userdata['agent'], PDO::PARAM_STR);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['price']['appliedToAll'], $shopkeyboard, 'HTML');
    step("home", $from_id);
} elseif ($text == $textbotlang['keyboard']['decreaseGroupPrice']) {
    sendmessage($from_id, $textbotlang['Admin']['price']['askDecreasePanel'], $json_list_marzban_panel, 'HTML');
    step("getlowpricepeoductloc", $from_id);
} elseif ($user['step'] == "getlowpricepeoductloc") {
    sendmessage($from_id, $textbotlang['Admin']['price']['askPriceGroup'], $backadmin, 'HTML');
    savedata("clear", "namepanel", $text);
    step("getkampricepeoductloc", $from_id);
} elseif ($user['step'] == "getkampricepeoductloc") {
    sendmessage($from_id, $textbotlang['Admin']['price']['askAmount'], $backadmin, 'HTML');
    savedata("save", "agent", $text);
    step("getkampricepeoduct", $from_id);
} elseif ($user['step'] == "getkampricepeoduct") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    $stmt = $pdo->prepare("SELECT * FROM product WHERE Location = :mp22 AND agent = :mp23");
    $stmt->execute([':mp22' => $userdata['namepanel'], ':mp23' => $userdata['agent']]);
    $product = $stmt->fetchAll();
    if ($product == false) {
        sendmessage($from_id, $textbotlang['Admin']['price']['noProductFound'], $shopkeyboard, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("UPDATE product SET price_product = ROUND(price_product - :price) WHERE Location = :location AND agent = :agent");
    $stmt->bindValue(':price', intval($text), PDO::PARAM_INT);
    $stmt->bindParam(':location', $userdata['namepanel'], PDO::PARAM_STR);
    $stmt->bindParam(':agent', $userdata['agent'], PDO::PARAM_STR);
    $stmt->execute();
    clearSelectCache('product');
    sendmessage($from_id, $textbotlang['Admin']['price']['appliedToAll'], $shopkeyboard, 'HTML');
    step("home", $from_id);
} elseif ($text == $textbotlang['keyboard']['minAmountCartToCart']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMinDeposit'], $backadmin, 'HTML');
    step("getmaincart", $from_id);
} elseif ($user['step'] == "getmaincart") {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['minDepositSaved'], $CartManage, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "minbalancecart");
} elseif ($text == $textbotlang['keyboard']['maxAmountCartToCart']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMaxDeposit'], $backadmin, 'HTML');
    step("getmaxcart", $from_id);
} elseif ($user['step'] == "getmaxcart") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['maxDepositSaved'], $CartManage, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "maxbalancecart");
} elseif ($text == $textbotlang['keyboard']['minAmountPlisio']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMinDeposit'], $backadmin, 'HTML');
    step("getmainplisio", $from_id);
} elseif ($user['step'] == "getmainplisio") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['minDepositSaved'], $NowPaymentsManage, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "minbalanceplisio");
} elseif ($text == $textbotlang['keyboard']['maxAmountPlisio']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMaxDeposit'], $backadmin, 'HTML');
    step("getmaxplisio", $from_id);
} elseif ($user['step'] == "getmaxplisio") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['maxDepositSaved'], $NowPaymentsManage, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "maxbalanceplisio");
} elseif ($text == $textbotlang['keyboard']['minAmountCryptoOffline']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMinDeposit'], $backadmin, 'HTML');
    step("getmaindigitaltron", $from_id);
} elseif ($user['step'] == "getmaindigitaltron") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['minDepositSaved'], $tronnowpayments, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "minbalancedigitaltron");
} elseif ($text == $textbotlang['keyboard']['maxAmountCryptoOffline']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMaxDeposit'], $backadmin, 'HTML');
    step("getmaxdigitaltron", $from_id);
} elseif ($user['step'] == "getmaxdigitaltron") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['maxDepositSaved'], $tronnowpayments, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "maxbalancedigitaltron");
} elseif ($text == $textbotlang['keyboard']['minAmountIranPay1']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMinDeposit'], $backadmin, 'HTML');
    step("getmainiranpay1", $from_id);
} elseif ($user['step'] == "getmainiranpay1") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['minDepositSaved'], $Swapinokey, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "minbalanceiranpay1");
} elseif ($text == $textbotlang['keyboard']['maxAmountIranPay1']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMaxDeposit'], $backadmin, 'HTML');
    step("getmaaxiranpay1", $from_id);
} elseif ($user['step'] == "getmaaxiranpay1") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['maxDepositSaved'], $Swapinokey, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "maxbalanceiranpay1");
} elseif ($text == $textbotlang['keyboard']['minAmountIranPay2']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMinDeposit'], $backadmin, 'HTML');
    step("getmainiranpay2", $from_id);
} elseif ($user['step'] == "getmainiranpay2") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['minDepositSaved'], $trnado, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "minbalanceiranpay2");
} elseif ($text == $textbotlang['keyboard']['maxAmountIranPay2']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMaxDeposit'], $backadmin, 'HTML');
    step("getmaaxiranpay2", $from_id);
} elseif ($user['step'] == "getmaaxiranpay2") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['maxDepositSaved'], $Swapinokey, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "maxbalanceiranpay2");
} elseif ($text == $textbotlang['keyboard']['minAmountAqayePardakht']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMinDeposit'], $backadmin, 'HTML');
    step("getmainaqayepardakht", $from_id);
} elseif ($user['step'] == "getmainaqayepardakht") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['minDepositSaved'], $aqayepardakht, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "minbalanceaqayepardakht");
} elseif ($text == $textbotlang['keyboard']['maxAmountAqayePardakht']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMaxDeposit'], $backadmin, 'HTML');
    step("getmaaxaqayepardakht", $from_id);
} elseif ($user['step'] == "getmaaxaqayepardakht") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['maxDepositSaved'], $aqayepardakht, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "maxbalanceaqayepardakht");
} elseif ($text == $textbotlang['keyboard']['minAmountZarinPal']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMinDeposit'], $backadmin, 'HTML');
    step("getmainaqzarinpal", $from_id);
} elseif ($user['step'] == "getmainaqzarinpal") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['minDepositSaved'], $aqayepardakht, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "minbalancezarinpal");
} elseif ($text == $textbotlang['keyboard']['maxAmountZarinPal']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMaxDeposit'], $backadmin, 'HTML');
    step("getmaaxzarinpal", $from_id);
} elseif ($user['step'] == "getmaaxzarinpal") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['maxDepositSaved'], $aqayepardakht, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "maxbalancezarinpal");
} elseif ($datain == "walletaddress") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "walletaddress", "select");
    $texttronseller = sprintf($textbotlang['Admin']['gateway']['askTronWallet'], $PaySetting['ValuePay']);
    sendmessage($from_id, $texttronseller, $backadmin, 'HTML');
    step('walletaddresssiranpay', $from_id);
} elseif ($user['step'] == "walletaddresssiranpay") {
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $keyboardadmin, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "walletaddress");
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['apiIranPay'] && $adminrulecheck['rule'] == "administrator") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "apiiranpay", "select")['ValuePay'];
    $texttronseller = sprintf($textbotlang['Admin']['gateway']['askApiCode'], $PaySetting);
    sendmessage($from_id, $texttronseller, $backadmin, 'HTML');
    step('apiiranpay', $from_id);
} elseif ($user['step'] == "apiiranpay") {
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $iranpaykeyboard, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "apiiranpay");
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['minAmountIranPay3']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMinDeposit'], $backadmin, 'HTML');
    step("minbalanceiranpay", $from_id);
} elseif ($user['step'] == "minbalanceiranpay") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['minDepositSaved'], $iranpaykeyboard, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "minbalanceiranpay");
} elseif ($text == $textbotlang['keyboard']['maxAmountIranPay3']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMaxDeposit'], $backadmin, 'HTML');
    step("maxbalanceiranpay", $from_id);
} elseif ($user['step'] == "maxbalanceiranpay") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['maxDepositSaved'], $iranpaykeyboard, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "maxbalanceiranpay");
} elseif ($text == $textbotlang['keyboard']['minCustomVolume'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['price']['askMinVolume'], $backadmin, 'HTML');
    step('GetmaineExtra', $from_id);
} elseif ($user['step'] == "GetmaineExtra") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidVolume'], $backuser, 'HTML');
        return;
    }
    savedata("clear", "namepanel", $user['Processing_value']);
    savedata("save", "mainvalume", $text);
    sendmessage($from_id, $textbotlang['Admin']['price']['selectUserGroup'], $backuser, 'HTML');
    step('gettypeextramain', $from_id);
} elseif ($user['step'] == "gettypeextramain") {
    $agentst = ["n", "n2", "f"];
    if (!in_array($text, $agentst)) {
        sendmessage($from_id, $textbotlang['Admin']['agent']['invalidTypeAgent'], $backadmin, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    $typepanel = select("marzban_panel", "*", "name_panel", $userdata['namepanel'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['savedData']);
    $eextraprice = json_decode($typepanel['mainvolume'], true);
    $eextraprice[$text] = $userdata['mainvalume'];
    $eextraprice = json_encode($eextraprice);
    update("marzban_panel", "mainvolume", $eextraprice, "name_panel", $userdata['namepanel']);
    update("user", "Processing_value", $userdata['namepanel'], "id", $from_id);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['maxCustomVolume'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['price']['askMaxVolume'], $backadmin, 'HTML');
    step('GetmaxeExtra', $from_id);
} elseif ($user['step'] == "GetmaxeExtra") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidVolume'], $backuser, 'HTML');
        return;
    }
    savedata("clear", "namepanel", $user['Processing_value']);
    savedata("save", "maxvolume", $text);
    sendmessage($from_id, $textbotlang['Admin']['price']['selectUserGroup'], $backuser, 'HTML');
    step('gettypeextramax', $from_id);
} elseif ($user['step'] == "gettypeextramax") {
    $agentst = ["n", "n2", "f"];
    if (!in_array($text, $agentst)) {
        sendmessage($from_id, $textbotlang['Admin']['agent']['invalidTypeAgent'], $backadmin, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    $typepanel = select("marzban_panel", "*", "name_panel", $userdata['namepanel'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['savedData']);
    $eextraprice = json_decode($typepanel['maxvolume'], true);
    $eextraprice[$text] = $userdata['maxvolume'];
    $eextraprice = json_encode($eextraprice);
    update("marzban_panel", "maxvolume", $eextraprice, "name_panel", $userdata['namepanel']);
    update("user", "Processing_value", $userdata['namepanel'], "id", $from_id);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['minCustomTime'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['price']['askMinTime'], $backadmin, 'HTML');
    step('Getmaintime', $from_id);
} elseif ($user['step'] == "Getmaintime") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidVolume'], $backuser, 'HTML');
        return;
    }
    savedata("clear", "namepanel", $user['Processing_value']);
    savedata("save", "maintime", $text);
    sendmessage($from_id, $textbotlang['Admin']['price']['selectUserGroup'], $backuser, 'HTML');
    step('gettypeextramaintime', $from_id);
} elseif ($user['step'] == "gettypeextramaintime") {
    $agentst = ["n", "n2", "f"];
    if (!in_array($text, $agentst)) {
        sendmessage($from_id, $textbotlang['Admin']['agent']['invalidTypeAgent'], $backadmin, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    $typepanel = select("marzban_panel", "*", "name_panel", $userdata['namepanel'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['savedData']);
    $eextraprice = json_decode($typepanel['maintime'], true);
    $eextraprice[$text] = $userdata['maintime'];
    $eextraprice = json_encode($eextraprice);
    update("marzban_panel", "maintime", $eextraprice, "name_panel", $userdata['namepanel']);
    update("user", "Processing_value", $userdata['namepanel'], "id", $from_id);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['maxCustomTime'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['price']['askMaxTime'], $backadmin, 'HTML');
    step('Getmaxtime', $from_id);
} elseif ($user['step'] == "Getmaxtime") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidVolume'], $backuser, 'HTML');
        return;
    }
    savedata("clear", "namepanel", $user['Processing_value']);
    savedata("save", "maxtime", $text);
    sendmessage($from_id, $textbotlang['Admin']['price']['selectUserGroup'], $backuser, 'HTML');
    step('gettypeextramaxtime', $from_id);
} elseif ($user['step'] == "gettypeextramaxtime") {
    $agentst = ["n", "n2", "f"];
    if (!in_array($text, $agentst)) {
        sendmessage($from_id, $textbotlang['Admin']['agent']['invalidTypeAgent'], $backadmin, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    $typepanel = select("marzban_panel", "*", "name_panel", $userdata['namepanel'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['savedData']);
    $eextraprice = json_decode($typepanel['maxtime'], true);
    $eextraprice[$text] = $userdata['maxtime'];
    $eextraprice = json_encode($eextraprice);
    update("marzban_panel", "maxtime", $eextraprice, "name_panel", $userdata['namepanel']);
    update("user", "Processing_value", $userdata['namepanel'], "id", $from_id);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['addDepartment']) {
    sendmessage($from_id, $textbotlang['Admin']['manageadmin']['askMessageAdminId'], $backadmin, 'HTML');
    step("getidadmindep", $from_id);
} elseif ($user['step'] == "getidadmindep") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    savedata('clear', 'idadmin', $text);
    sendmessage($from_id, $textbotlang['Admin']['department']['askName'], $backadmin, 'HTML');
    step("getdeparteman", $from_id);
} elseif ($user['step'] == "getdeparteman") {
    $userdata = json_decode($user['Processing_value'], true);
    $stmt = $pdo->prepare("INSERT IGNORE INTO departman (idsupport,name_departman) VALUES (:idsupport,:name_departman)");
    $stmt->bindParam(':idsupport', $userdata['idadmin']);
    $stmt->bindParam(':name_departman', $text);
    $stmt->execute();
    step("home", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['department']['added'], $supportcenter, 'HTML');
} elseif ($text == $textbotlang['keyboard']['deleteDepartment']) {
    $countdeparteman = select("departman", "*", null, null, "count");
    if ($countdeparteman == 0) {
        sendmessage($from_id, $textbotlang['Admin']['department']['noneToDelete'], $departemanslist, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['department']['askDelete'], $departemanslist, 'HTML');
    step("getremovedep", $from_id);
} elseif ($user['step'] == "getremovedep") {
    $stmt = $pdo->prepare("DELETE FROM departman WHERE name_departman = ?");
    $stmt->bindParam(1, $text);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['department']['deleted'], $supportcenter, 'HTML');
    step("home", $from_id);
} elseif ($text == $textbotlang['keyboard']['serviceSettings'] && $adminrulecheck['rule'] == "administrator") {
    $textsetservice = $textbotlang['Admin']['managepanel']['askServiceSetup'];
    sendmessage($from_id, $textsetservice, $backadmin, 'HTML');
    step('getservceid', $from_id);
} elseif ($user['step'] == "getservceid") {
    $userdata = json_decode(getuserm($text, $user['Processing_value'])['body'], true);
    if (isset($userdata['detail']) and $userdata['detail'] == "User not found") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['userNotInPanel'], null, 'HTML');
        return;
    }
    update("marzban_panel", "proxies", json_encode($userdata['service_ids']), "name_panel", $user['Processing_value']);
    step("home", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['setupSaved'], $optionmarzneshin, 'HTML');
} elseif ($text == $textbotlang['keyboard']['setSupportId'] && $adminrulecheck['rule'] == "administrator") {
    $textcart = sprintf($textbotlang['Admin']['card']['askSupportUsername'], $setting['id_support']);
    sendmessage($from_id, $textcart, $backadmin, 'HTML');
    step('idsupportset', $from_id);
} elseif ($user['step'] == "idsupportset") {
    sendmessage($from_id, $textbotlang['Admin']['SettingPayment']['cartDirect'], $supportcenter, 'HTML');
    update("setting", "id_support", $text, null, null);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['setEducationCartToCart'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['askTutorialMedia'], $backadmin, 'HTML');
    step("gethelpcart", $from_id);
} elseif ($user['step'] == "gethelpcart") {
    if ($text) {
        if (intval($text) == 2) {
            update("PaySetting", "ValuePay", "2", "NamePay", "helpcart");
        } else {
            $data = json_encode(array(
                'type' => "text",
                'text' => $text
            ));
            update("PaySetting", "ValuePay", $data, "NamePay", "helpcart");
        }
    } elseif ($photo) {
        $data = json_encode(array(
            'type' => "photo",
            'text' => $caption,
            'photoid' => $photoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpcart");
    } elseif ($video) {
        $data = json_encode(array(
            'type' => "video",
            'text' => $caption,
            'videoid' => $videoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpcart");
    } else {
        sendmessage($from_id, $textbotlang['Admin']['Help']['invalidContent'], $backadmin, 'HTML');
        return;
    }
    step('home', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Help']['tutorialSaved'], $CartManage, 'HTML');
} elseif ($text == $textbotlang['keyboard']['setEducationNowPayment'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['askTutorialMedia'], $backadmin, 'HTML');
    step("gethelpnowpayment", $from_id);
} elseif ($user['step'] == "gethelpnowpayment") {
    if ($text) {
        if (intval($text) == 2) {
            update("PaySetting", "ValuePay", "2", "NamePay", "helpnowpayment");
        } else {
            $data = json_encode(array(
                'type' => "text",
                'text' => $text
            ));
            update("PaySetting", "ValuePay", $data, "NamePay", "helpnowpayment");
        }
    } elseif ($photo) {
        $data = json_encode(array(
            'type' => "photo",
            'text' => $caption,
            'photoid' => $photoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpnowpayment");
    } elseif ($video) {
        $data = json_encode(array(
            'type' => "video",
            'text' => $caption,
            'videoid' => $videoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpnowpayment");
    } else {
        sendmessage($from_id, $textbotlang['Admin']['Help']['invalidContent'], $backadmin, 'HTML');
        return;
    }
    step('home', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Help']['tutorialSaved'], $nowpayment_setting_keyboard, 'HTML');
} elseif ($text == $textbotlang['Admin']['gateway']['btnPerfectMoneyHelp'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['askTutorialMedia'], $backadmin, 'HTML');
    step("gethelpperfect", $from_id);
} elseif ($user['step'] == "gethelpperfect") {
    if ($text) {
        if (intval($text) == 2) {
            update("PaySetting", "ValuePay", "0", "NamePay", "helpperfectmony");
        } else {
            $data = json_encode(array(
                'type' => "text",
                'text' => $text
            ));
            update("PaySetting", "ValuePay", $data, "NamePay", "helpperfectmony");
        }
    } elseif ($photo) {
        $data = json_encode(array(
            'type' => "photo",
            'text' => $caption,
            'photoid' => $photoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpperfectmony");
    } elseif ($video) {
        $data = json_encode(array(
            'type' => "video",
            'text' => $caption,
            'videoid' => $videoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpperfectmony");
    } else {
        sendmessage($from_id, $textbotlang['Admin']['Help']['invalidContent'], $backadmin, 'HTML');
        return;
    }
    step('home', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Help']['tutorialSaved'], $CartManage, 'HTML');
} elseif ($text == $textbotlang['keyboard']['setEducationPlisio'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['askTutorialMedia'], $backadmin, 'HTML');
    step("gethelpplisio", $from_id);
} elseif ($user['step'] == "gethelpplisio") {
    if ($text) {
        if (intval($text) == 2) {
            update("PaySetting", "ValuePay", "0", "NamePay", "helpplisio");
        } else {
            $data = json_encode(array(
                'type' => "text",
                'text' => $text
            ));
            update("PaySetting", "ValuePay", $data, "NamePay", "helpplisio");
        }
    } elseif ($photo) {
        $data = json_encode(array(
            'type' => "photo",
            'text' => $caption,
            'photoid' => $photoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpplisio");
    } elseif ($video) {
        $data = json_encode(array(
            'type' => "video",
            'text' => $caption,
            'videoid' => $videoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpplisio");
    } else {
        sendmessage($from_id, $textbotlang['Admin']['Help']['invalidContent'], $backadmin, 'HTML');
        return;
    }
    step('home', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Help']['tutorialSaved'], $CartManage, 'HTML');
} elseif ($text == $textbotlang['keyboard']['setEducationIranPay1'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['askTutorialMedia'], $backadmin, 'HTML');
    step("gethelpiranpay1", $from_id);
} elseif ($user['step'] == "gethelpiranpay1") {
    if ($text) {
        if (intval($text) == 2) {
            update("PaySetting", "ValuePay", "0", "NamePay", "helpiranpay1");
        } else {
            $data = json_encode(array(
                'type' => "text",
                'text' => $text
            ));
            update("PaySetting", "ValuePay", $data, "NamePay", "helpiranpay1");
        }
    } elseif ($photo) {
        $data = json_encode(array(
            'type' => "photo",
            'text' => $caption,
            'photoid' => $photoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpiranpay1");
    } elseif ($video) {
        $data = json_encode(array(
            'type' => "video",
            'text' => $caption,
            'videoid' => $videoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpiranpay1");
    } else {
        sendmessage($from_id, $textbotlang['Admin']['Help']['invalidContent'], $backadmin, 'HTML');
        return;
    }
    step('home', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Help']['tutorialSaved'], $CartManage, 'HTML');
} elseif ($text == $textbotlang['keyboard']['setEducationIranPay2'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['askTutorialMedia'], $backadmin, 'HTML');
    step("helpiranpay2", $from_id);
} elseif ($user['step'] == "helpiranpay2") {
    if ($text) {
        if (intval($text) == 2) {
            update("PaySetting", "ValuePay", "0", "NamePay", "helpiranpay2");
        } else {
            $data = json_encode(array(
                'type' => "text",
                'text' => $text
            ));
            update("PaySetting", "ValuePay", $data, "NamePay", "helpiranpay2");
        }
    } elseif ($photo) {
        $data = json_encode(array(
            'type' => "photo",
            'text' => $caption,
            'photoid' => $photoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpiranpay2");
    } elseif ($video) {
        $data = json_encode(array(
            'type' => "video",
            'text' => $caption,
            'videoid' => $videoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpiranpay2");
    } else {
        sendmessage($from_id, $textbotlang['Admin']['Help']['invalidContent'], $backadmin, 'HTML');
        return;
    }
    step('home', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Help']['tutorialSaved'], $CartManage, 'HTML');
} elseif ($text == $textbotlang['keyboard']['setEducationIranPay3'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['askTutorialMedia'], $backadmin, 'HTML');
    step("helpiranpay3", $from_id);
} elseif ($user['step'] == "helpiranpay3") {
    if ($text) {
        if (intval($text) == 2) {
            update("PaySetting", "ValuePay", "0", "NamePay", "helpiranpay3");
        } else {
            $data = json_encode(array(
                'type' => "text",
                'text' => $text
            ));
            update("PaySetting", "ValuePay", $data, "NamePay", "helpiranpay3");
        }
    } elseif ($photo) {
        $data = json_encode(array(
            'type' => "photo",
            'text' => $caption,
            'photoid' => $photoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpiranpay3");
    } elseif ($video) {
        $data = json_encode(array(
            'type' => "video",
            'text' => $caption,
            'videoid' => $videoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpiranpay3");
    } else {
        sendmessage($from_id, $textbotlang['Admin']['Help']['invalidContent'], $backadmin, 'HTML');
        return;
    }
    step('home', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Help']['tutorialSaved'], $CartManage, 'HTML');
} elseif ($text == $textbotlang['keyboard']['setEducationAqayePardakht'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['askTutorialMedia'], $backadmin, 'HTML');
    step("helpaqayepardakht", $from_id);
} elseif ($user['step'] == "helpaqayepardakht") {
    if ($text) {
        if (intval($text) == 2) {
            update("PaySetting", "ValuePay", "0", "NamePay", "helpaqayepardakht");
        } else {
            $data = json_encode(array(
                'type' => "text",
                'text' => $text
            ));
            update("PaySetting", "ValuePay", $data, "NamePay", "helpaqayepardakht");
        }
    } elseif ($photo) {
        $data = json_encode(array(
            'type' => "photo",
            'text' => $caption,
            'photoid' => $photoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpaqayepardakht");
    } elseif ($video) {
        $data = json_encode(array(
            'type' => "video",
            'text' => $caption,
            'videoid' => $videoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpaqayepardakht");
    } else {
        sendmessage($from_id, $textbotlang['Admin']['Help']['invalidContent'], $backadmin, 'HTML');
        return;
    }
    step('home', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Help']['tutorialSaved'], $CartManage, 'HTML');
} elseif ($text == $textbotlang['keyboard']['setEducationZarinPal'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['askTutorialMedia'], $backadmin, 'HTML');
    step("helpzarinpal", $from_id);
} elseif ($user['step'] == "helpzarinpal") {
    if ($text) {
        if (intval($text) == 2) {
            update("PaySetting", "ValuePay", "0", "NamePay", "helpzarinpal");
        } else {
            $data = json_encode(array(
                'type' => "text",
                'text' => $text
            ));
            update("PaySetting", "ValuePay", $data, "NamePay", "helpzarinpal");
        }
    } elseif ($photo) {
        $data = json_encode(array(
            'type' => "photo",
            'text' => $caption,
            'photoid' => $photoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpzarinpal");
    } elseif ($video) {
        $data = json_encode(array(
            'type' => "video",
            'text' => $caption,
            'videoid' => $videoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpzarinpal");
    } else {
        sendmessage($from_id, $textbotlang['Admin']['Help']['invalidContent'], $backadmin, 'HTML');
        return;
    }
    step('home', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Help']['tutorialSaved'], $CartManage, 'HTML');
} elseif ($text == $textbotlang['keyboard']['setEducationCryptoOffline'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['askTutorialMedia'], $backadmin, 'HTML');
    step("helpofflinearze", $from_id);
} elseif ($user['step'] == "helpofflinearze") {
    if ($text) {
        if (intval($text) == 2) {
            update("PaySetting", "ValuePay", "0", "NamePay", "helpofflinearze");
        } else {
            $data = json_encode(array(
                'type' => "text",
                'text' => $text
            ));
            update("PaySetting", "ValuePay", $data, "NamePay", "helpofflinearze");
        }
    } elseif ($photo) {
        $data = json_encode(array(
            'type' => "photo",
            'text' => $caption,
            'photoid' => $photoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpofflinearze");
    } elseif ($video) {
        $data = json_encode(array(
            'type' => "video",
            'text' => $caption,
            'videoid' => $videoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpofflinearze");
    } else {
        sendmessage($from_id, $textbotlang['Admin']['Help']['invalidContent'], $backadmin, 'HTML');
        return;
    }
    step('home', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Help']['tutorialSaved'], $CartManage, 'HTML');
} elseif ($text == $textbotlang['keyboard']['agentMembershipFee']) {
    sendmessage($from_id, $textbotlang['Admin']['agent']['askMembershipFee'], $backadmin, 'HTML');
    step("getpricereqagent", $from_id);
} elseif ($user['step'] == "getpricereqagent") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['cronjob']['changedData'], $setting_panel, 'HTML');
    step("home", $from_id);
    update("setting", "agentreqprice", $text, null, null);
} elseif ($text == $textbotlang['keyboard']['autoConfirmNoCheck'] && $adminrulecheck['rule'] == "administrator") {
    $paymentverify = select("PaySetting", "ValuePay", "NamePay", "autoconfirmcart", "select")['ValuePay'];
    $keyboardverify = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $paymentverify, 'callback_data' => $paymentverify],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Payment']['autoConfirmDesc'], $keyboardverify, 'HTML');
} elseif ($datain == "onauto") {
    update("PaySetting", "ValuePay", "offauto", "NamePay", "autoconfirmcart");
    $paymentverify = select("PaySetting", "ValuePay", "NamePay", "autoconfirmcart", "select")['ValuePay'];
    $keyboardverify = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $paymentverify, 'callback_data' => $paymentverify],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['card']['afterFirstPayOff'], $keyboardverify);
} elseif ($datain == "offauto") {
    update("PaySetting", "ValuePay", "onauto", "NamePay", "autoconfirmcart");
    $paymentverify = select("PaySetting", "ValuePay", "NamePay", "autoconfirmcart", "select")['ValuePay'];
    $keyboardverify = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $paymentverify, 'callback_data' => $paymentverify],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['card']['afterFirstPayOn'], $keyboardverify);
} elseif (preg_match('/transferaccount_(\w+)/', $datain, $dataget) && $adminrulecheck['rule'] == "administrator") {
    $iduser = $dataget[1];
    update("user", "Processing_value", $iduser, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['askTransferTargetId'], $backadmin, 'HTML');
    step("getidfortransfers", $from_id);
} elseif ($user['step'] == "getidfortransfers") {
    if (!rowExists("user", "id", $text)) {
        sendmessage($from_id, $textbotlang['Admin']['notUser'], $backadmin, 'HTML');
        return;
    }
    if ($text == $user['Processing_value']) {
        sendmessage($from_id, $textbotlang['Admin']['manageUser']['transferSameUser'], $keyboardadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['transferDone'], $keyboardadmin, 'HTML');
    $stmt = $pdo->prepare("DELETE FROM user WHERE id = :id_user");
    $stmt->bindParam(':id_user', $text, PDO::PARAM_STR);
    $stmt->execute();
    update("user", "id", $text, "id", $user['Processing_value']);
    update("Payment_report", "id_user", $text, "id_user", $user['Processing_value']);
    update("invoice", "id_user", $text, "id_user", $user['Processing_value']);
    update("support_message", "iduser", $text, "iduser", $user['Processing_value']);
    update("service_other", "id_user", $text, "id_user", $user['Processing_value']);
    update("Giftcodeconsumed", "id_user", $text, "id_user", $user['Processing_value']);
    step("home", $from_id);
} elseif ($text == $textbotlang['keyboard']['qrBackground']) {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['askQrBackground'], $backadmin, 'HTML');
    step("getimagebackgroundqr", $from_id);
} elseif ($user['step'] == "getimagebackgroundqr") {
    if (!$photo) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['invalidImage'], $backadmin, 'HTML');
        return;
    }
    $response = getFileddire($photoid);
    if ($response['ok']) {
        $filePath = $response['result']['file_path'];
        $fileUrl = "https://api.telegram.org/file/bot$APIKEY/$filePath";
        $fileContent = file_get_contents($fileUrl);
        file_put_contents("custom.jpg", $fileContent);
        file_put_contents("images.jpg", $fileContent);
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['qrBackgroundSaved'], $setting_panel, 'HTML');
        step("home", $from_id);
    }
} elseif ($text == $textbotlang['keyboard']['setProtocolInbound'] || $text == $textbotlang['Admin']['managepanel']['btnSetGroupName'] || $text == $textbotlang['Admin']['node']['btnSettings']) {
    if ($text == $textbotlang['Admin']['managepanel']['btnSetGroupName']) {
        $textsetprotocol = $textbotlang['Admin']['managepanel']['askGroupName'];
    } elseif ($text == $textbotlang['Admin']['node']['btnSettings']) {
        $textsetprotocol = $textbotlang['Admin']['node']['askSetup'];
    } else {
        $textsetprotocol = $textbotlang['Admin']['managepanel']['askProtocolSetup'];
    }
    sendmessage($from_id, $textsetprotocol, $backadmin, 'HTML');
    step("setinboundandprotocol", $from_id);
} elseif ($user['step'] == "setinboundandprotocol") {
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($panel['type'] == "marzban") {
        if ($panel['version_panel'] == "1") {
            $DataUserOut = getuser($text, $user['Processing_value']);
            if (!empty($DataUserOut['error'])) {
                sendmessage($from_id, panelErrorText($DataUserOut['error']), null, 'HTML');
                return;
            }
            if (!empty($DataUserOut['status']) && $DataUserOut['status'] != 200) {
                sendmessage($from_id, sprintf($textbotlang['Admin']['errorCode4'], $DataUserOut['status']), null, 'HTML');
                return;
            }
            $DataUserOut = json_decode($DataUserOut['body'], true);
            if ((isset($DataUserOut['msg']) && $DataUserOut['msg'] == "User not found") or !isset($DataUserOut['proxy_settings'])) {
                sendmessage($from_id, $textbotlang['users']['status']['userNotFound'], null, 'html');
                return;
            }
            foreach ($DataUserOut['proxy_settings'] as $key => &$value) {
                if ($key == "shadowsocks") {
                    unset($DataUserOut['proxy_settings'][$key]['password']);
                } elseif ($key == "trojan") {
                    unset($DataUserOut['proxy_settings'][$key]['password']);
                }elseif ($key == "wireguard") {
                    unset($DataUserOut['proxy_settings'][$key]['private_key']);
                    unset($DataUserOut['proxy_settings'][$key]['public_key']);
                    unset($DataUserOut['proxy_settings'][$key]['peer_ips']);
                }  else {
                    unset($DataUserOut['proxy_settings'][$key]['id']);
                }
                if (count($DataUserOut['proxy_settings'][$key]) == 0) {
                    $DataUserOut['proxy_settings'][$key] = new stdClass();
                }
            }
            update("marzban_panel", "inbounds", json_encode($DataUserOut['group_ids']), "name_panel", $user['Processing_value']);
            update("marzban_panel", "proxies", json_encode($DataUserOut['proxy_settings'], true), "name_panel", $user['Processing_value']);
        } else {
            $DataUserOut = getuser($text, $user['Processing_value']);
            if (!empty($DataUserOut['error'])) {
                sendmessage($from_id, panelErrorText($DataUserOut['error']), null, 'HTML');
                return;
            }
            if (!empty($DataUserOut['status']) && $DataUserOut['status'] != 200) {
                sendmessage($from_id, sprintf($textbotlang['Admin']['errorCode5'], $DataUserOut['status']), null, 'HTML');
                return;
            }
            $DataUserOut = json_decode($DataUserOut['body'], true);
            if ((isset($DataUserOut['msg']) && $DataUserOut['msg'] == "User not found") or !isset($DataUserOut['proxies'])) {
                sendmessage($from_id, $textbotlang['users']['status']['userNotFound'], null, 'html');
                return;
            }
            foreach ($DataUserOut['proxies'] as $key => &$value) {
                if ($key == "shadowsocks") {
                    unset($DataUserOut['proxies'][$key]['password']);
                } elseif ($key == "trojan") {
                    unset($DataUserOut['proxies'][$key]['password']);
                } else {
                    unset($DataUserOut['proxies'][$key]['id']);
                }
                if (count($DataUserOut['proxies'][$key]) == 0) {
                    $DataUserOut['proxies'][$key] = new stdClass();
                }
            }
            update("marzban_panel", "inbounds", json_encode($DataUserOut['inbounds']), "name_panel", $user['Processing_value']);
            update("marzban_panel", "proxies", json_encode($DataUserOut['proxies'], true), "name_panel", $user['Processing_value']);
        }
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['protocolSaved'], $optionMarzban, 'HTML');
    } elseif ($panel['type'] == "s_ui") {
        $data = GetClientsS_UI($text, $panel['name_panel']); {
            if (count($data) == 0) {
                sendmessage($from_id, $textbotlang['Admin']['managepanel']['userNotInPanel2'], $options_ui, 'HTML');
                return;
            }
            $servies = [];
            foreach ($data['inbounds'] as $service) {
                $servies[] = $service;
            }
            update("marzban_panel", "proxies", json_encode($servies, true), "name_panel", $user['Processing_value']);
        }
    } elseif ($panel['type'] == "ibsng" || $panel['type'] == "mikrotik") {
        update("marzban_panel", "proxies", $text, "name_panel", $user['Processing_value']);
        $groupSavedKeyboard = $panel['type'] == "ibsng" ? $optionibsng : $option_mikrotik;
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['groupNameSaved'], $groupSavedKeyboard, 'HTML');
    } elseif ($panel['type'] == "x-ui_single") {
        $data = get_clinets($text, $panel);
        if (!empty($data['error'])) {
            sendmessage($from_id, panelErrorText($data['error']), null, 'HTML');
            return;
        }
        if (!empty($data['status']) && $data['status'] != 200) {
            sendmessage($from_id, sprintf($textbotlang['Admin']['managepanel']['eylanErrorCode'], $data['status']), null, 'HTML');
            return;
        }
        $data = json_decode($data['body'], true);
        if (!$data['success']) {
            sendmessage($from_id, $textbotlang['Admin']['managepanel']['eylanUserNotExist'], $optionX_ui_single, 'HTML');
            sendmessage($from_id, $textbotlang['Admin']['managepanel']['eylanPanelOutput'] . json_encode($data), null, 'HTML');
            return;
        }
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['protocolSaved'], $optionX_ui_single, 'HTML');
        update("marzban_panel", "inbounds", json_encode($data['obj']['inboundIds']), "name_panel", $user['Processing_value']);
    } elseif ($panel['type'] == "rebecca") {
        $userdata = json_decode(getuser_rebecca($text, $user['Processing_value'])['body'], true);
        if (!is_array($userdata) || !isset($userdata['service_id'])) {
            sendmessage($from_id, $textbotlang['users']['status']['userNotFound'], null, 'html');
            return;
        }
        update("marzban_panel", "proxies", json_encode([$userdata['service_id']]), "name_panel", $user['Processing_value']);
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['protocolSaved'], $optionrebecca, 'HTML');
    } else {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['protocolSaved'], $optionMarzban, 'HTML');
    }
    step("home", $from_id);
} elseif ($text == $textbotlang['keyboard']['renewalStatus'] && $adminrulecheck['rule'] == "administrator") {
    $marzbanstatus = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $keyboardstatus = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbanstatus['status_extend'], 'callback_data' => $marzbanstatus['status_extend']],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['activePanel'], $keyboardstatus, 'HTML');
} elseif ($datain == "on_extend") {
    update("marzban_panel", "status_extend", "off_extend", "name_panel", $user['Processing_value']);
    $marzbanstatus = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $keyboardstatus = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbanstatus['status_extend'], 'callback_data' => $marzbanstatus['status_extend']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['activePanelOff'], $keyboardstatus);
} elseif ($datain == "off_extend") {
    update("marzban_panel", "status_extend", "on_extend", "name_panel", $user['Processing_value']);
    $marzbanstatus = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $keyboardstatus = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbanstatus['status_extend'], 'callback_data' => $marzbanstatus['status_extend']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['activePanelOn'], $keyboardstatus);
} elseif ((preg_match('/confirmchannel-(\w+)/', $datain, $dataget))) {
    $iduser = $dataget[1];
    $userdata = select("user", "*", "id", $iduser, "select");
    if ($userdata['joinchannel'] == "active") {
        sendmessage($from_id, $textbotlang['Admin']['manageUser']['alreadyVerified'], null, 'HTML');
        return;
    }
    update("user", "joinchannel", "active", "id", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['channel']['joinExempt'], $keyboardadmin, 'HTML');
} elseif ((preg_match('/zerobalance-(\w+)/', $datain, $dataget)) && $adminrulecheck['rule'] == "administrator") {
    $iduser = $dataget[1];
    $userdata = select("user", "*", "id", $iduser, "select");
    update("user", "Balance", "0", "id", $iduser);
    sendmessage($from_id, sprintf($textbotlang['Admin']['Balance']['resetToZero'], $userdata['Balance']), $keyboardadmin, 'HTML');
} elseif (preg_match('/removeadmin_(\w+)/', $datain, $dataget) && $adminrulecheck['rule'] == "administrator") {
    $idadmin = trim($dataget[1]);
    $mainAdminId = trim((string) $adminnumber);
    if ($idadmin === $mainAdminId) {
        sendmessage($from_id, $textbotlang['Admin']['manageadmin']['cannotDeleteMain'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM admin WHERE TRIM(id_admin) = :id_admin");
    $stmt->bindParam(':id_admin', $idadmin, PDO::PARAM_STR);
    $stmt->execute();
    if ($stmt->rowCount() === 0) {
        sendmessage($from_id, $textbotlang['Admin']['manageadmin']['notFound'], null, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['manageadmin']['deleted'], null, 'HTML');
}
elseif (preg_match('/activeconfig-(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    $checkexits = select("user", "*", "id", $iduser, "select");
    if (intval($checkexits['checkstatus']) != 0) {
        sendmessage($from_id, $textbotlang['Admin']['manageUser']['accountToggleBusy'], null, 'HTML');
        return;
    }
    update("user", "checkstatus", "1", "id", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['configsQueuedEnable'], null, 'HTML');
} elseif (preg_match('/disableconfig-(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    $checkexits = select("user", "*", "id", $iduser, "select");
    if (intval($checkexits['checkstatus']) != 0) {
        sendmessage($from_id, $textbotlang['Admin']['manageUser']['accountToggleBusy'], null, 'HTML');
        return;
    }
    update("user", "checkstatus", "2", "id", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['configsQueuedDisable'], null, 'HTML');
}
elseif ($text == $textbotlang['keyboard']['hidePanelForUser'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['askHideUserId'], $backadmin, 'HTML');
    step('getuserhide', $from_id);
} elseif ($user['step'] == "getuserhide") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['hiddenForUser']);
    if ($typepanel['hide_user'] == null) {
        $hideuserid = [];
    } else {
        $hideuserid = json_decode($typepanel['hide_user'], true);
    }
    $hideuserid[] = $text;
    $hideuserid = json_encode($hideuserid);
    update("marzban_panel", "hide_user", $hideuserid, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['removeFromHiddenList'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['askHideUserId'], $backadmin, 'HTML');
    step('getuserhideforremove', $from_id);
} elseif ($user['step'] == "getuserhideforremove") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    step("home", $from_id);
    if ($typepanel['hide_user'] == null) {
        outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['hiddenListEmpty']);
        return;
    }
    $hideuserid = json_decode($typepanel['hide_user'], true);
    if (count($hideuserid) == 0) {
        outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['userNotInList']);
        return;
    }
    if (!in_array($text, $hideuserid)) {
        outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['userNotInList2']);
        return;
    }
    $key = array_search($text, $hideuserid);
    if ($key !== false) {
        unset($hideuserid[$key]);
        $hideuserid = array_values($hideuserid);
    }
    $hideuserid = json_encode($hideuserid);
    update("marzban_panel", "hide_user", $hideuserid, "name_panel", $user['Processing_value']);
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['userRemovedFromList']);
} elseif ($datain == "scoresetting") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $lottery, 'HTML');
} elseif ($text == $textbotlang['keyboard']['setFirstPrize']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askChargeAmount'], $lottery, 'HTML');
    step("getonelotary", $from_id);
} elseif ($user['step'] == "getonelotary") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['price']['rewardSaved'], $lottery, 'HTML');
    step("home", $from_id);
    $data = json_decode($setting['Lottery_prize'], true);
    $data['one'] = $text;
    $data = json_encode($data, true);
    update("setting", "Lottery_prize", $data, null, null);
} elseif ($text == $textbotlang['keyboard']['setSecondPrize']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askChargeAmount'], $lottery, 'HTML');
    step("getonelotary2", $from_id);
} elseif ($user['step'] == "getonelotary2") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['price']['rewardSaved'], $lottery, 'HTML');
    step("home", $from_id);
    $data = json_decode($setting['Lottery_prize'], true);
    $data['tow'] = $text;
    $data = json_encode($data, true);
    update("setting", "Lottery_prize", $data, null, null);
} elseif ($text == $textbotlang['keyboard']['setThirdPrize']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askChargeAmount'], $lottery, 'HTML');
    step("getonelotary3", $from_id);
} elseif ($user['step'] == "getonelotary3") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['price']['rewardSaved'], $lottery, 'HTML');
    step("home", $from_id);
    $data = json_decode($setting['Lottery_prize'], true);
    $data['theree'] = $text;
    $data = json_encode($data, true);
    update("setting", "Lottery_prize", $data, null, null);
} elseif ($datain == "gradonhshans") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $wheelkeyboard, 'HTML');
} elseif ($text == $textbotlang['keyboard']['lotteryWinAmount']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askChargeAmount'], $backadmin, 'HTML');
    step("getpricewheel", $from_id);
} elseif ($user['step'] == "getpricewheel") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['price']['rewardSaved'], $wheelkeyboard, 'HTML');
    step("home", $from_id);
    update("setting", "wheelـluck_price", $text, null, null);
} elseif ($text == $textbotlang['keyboard']['pendingReceipts']) {
    $sql = "SELECT * FROM Payment_report WHERE Payment_Method = 'cart to cart' AND payment_Status = 'waiting'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $list_payment = $stmt->fetchAll();
    $list_payment_count = count($list_payment);
    if ($list_payment_count == 0) {
        sendmessage($from_id, $textbotlang['Admin']['Payment']['noPending'], $list_payment, 'HTML');
        return;
    }
    $list_pay = ['inline_keyboard' => []];
    foreach ($list_payment as $payment) {
        $list_payment['inline_keyboard'][] = [
            ['text' => $payment['id_user'], 'callback_data' => "checkpay"]
        ];
        $list_payment['inline_keyboard'][] = [
            ['text' => "✅", 'callback_data' => "Confirm_pay_{$payment['id_order']}"],
            ['text' => "❌", 'callback_data' => "reject_pay_{$payment['id_order']}"],
            ['text' => "📝", 'callback_data' => "showinfopay_{$payment['id_order']}"],
            ['text' => "🗑", 'callback_data' => "removeresid_{$payment['id_order']}"],
        ];
        $list_payment['inline_keyboard'][] = [
            ['text' => "💸💸💸💸💸💸💸💸💸", 'callback_data' => "checkpay"]
        ];
    }
    $list_payment['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['deleteAllReceipts'], 'callback_data' => "removeresid"]
    ];
    $list_payment = json_encode($list_payment);
    sendmessage($from_id, $textbotlang['Admin']['Payment']['pendingIntro'], $list_payment, 'HTML');
} elseif ($datain == "removeresid") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['Payment']['allReceiptsDeleted'], $list_payment, 'HTML');
    $sql = "UPDATE Payment_report SET payment_Status = 'reject',dec_not_confirmed = 'remove_all' WHERE Payment_Method = 'cart to cart' AND payment_Status = 'waiting'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
} elseif (preg_match('/showinfopay_(\w+)/', $datain, $dataget)) {
    $idorder = $dataget[1];
    $paymentUser = select("Payment_report", "*", "id_order", $idorder, "select");
    if ($paymentUser == false) {
        telegram('answerCallbackQuery', array(
            'callback_query_id' => $callback_query_id,
            'text' => $textbotlang['keyboard']['transactionDeleted'],
            'show_alert' => true,
            'cache_time' => 5,
        ));
        return;
    }
    $text_order = sprintf($textbotlang['Admin']['Payment']['detailRow2'], $paymentUser['id_order'], $paymentUser['id_user'], $paymentUser['price'], $paymentUser['payment_Status'], $paymentUser['Payment_Method'], $paymentUser['time']);
    sendmessage($from_id, $text_order, null, 'HTML');
} elseif ($text == $textbotlang['keyboard']['setInbound']) {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['askConfigUsername'], $backadmin, 'HTML');
    step("getdatainboundproduct", $from_id);
} elseif ($user['step'] == "getdatainboundproduct") {
    $marzban_list_get = select("marzban_panel", "*", "code_panel", $user['Processing_value_one']);
    $datainbound = "";
    if ($marzban_list_get['type'] == "marzban") {
        $DataUserOut = getuser($text, $marzban_list_get['name_panel']);
        if (!empty($DataUserOut['error'])) {
            sendmessage($from_id, panelErrorText($DataUserOut['error']), null, 'HTML');
            return;
        }
        if (!empty($DataUserOut['status']) && $DataUserOut['status'] != 200) {
            sendmessage($from_id, sprintf($textbotlang['Admin']['errorCode6'], $DataUserOut['status']), null, 'HTML');
            return;
        }
        $DataUserOut = json_decode($DataUserOut['body'], true);
        if ((isset($DataUserOut['msg']) && $DataUserOut['msg'] == "User not found") or !isset($DataUserOut['proxies'])) {
            sendmessage($from_id, $textbotlang['users']['status']['userNotFound'], null, 'html');
            return;
        }
        foreach ($DataUserOut['proxies'] as $key => &$value) {
            if ($key == "shadowsocks") {
                unset($DataUserOut['proxies'][$key]['password']);
            } elseif ($key == "trojan") {
                unset($DataUserOut['proxies'][$key]['password']);
            } else {
                unset($DataUserOut['proxies'][$key]['id']);
            }
            if (count($DataUserOut['proxies'][$key]) == 0) {
                $DataUserOut['proxies'][$key] = new stdClass();
            }
        }
        $stmt = $pdo->prepare("UPDATE product SET proxies = :proxies WHERE id = :name_product AND (Location = :Location OR Location = '/all') AND agent = :agent");
        $proxies_json = json_encode($DataUserOut['proxies']);
        $stmt->bindParam(':proxies', $proxies_json);
        $stmt->bindParam(':name_product', $user['Processing_value']);
        $stmt->bindParam(':Location', $marzban_list_get['name_panel']);
        $stmt->bindParam(':agent', $user['Processing_value_tow']);
        $stmt->execute();
        $datainbound = json_encode($DataUserOut['inbounds']);
    } elseif ($marzban_list_get['type'] == "marzneshin") {
        $userdata = json_decode(getuserm($text, $marzban_list_get['name_panel'])['body'], true);
        if (isset($userdata['detail']) and $userdata['detail'] == "User not found") {
            sendmessage($from_id, $textbotlang['Admin']['managepanel']['userNotInPanel'], null, 'HTML');
            return;
        }
        $datainbound = json_encode($userdata['service_ids'], true);
    } elseif ($marzban_list_get['type'] == "x-ui_single" || $marzban_list_get['type'] == "alireza_single") {
        $datainbound = $text;
    } elseif ($marzban_list_get['type'] == "s_ui") {
        $data = GetClientsS_UI($text, $marzban_list_get['name_panel']);
        if (count($data) == 0) {
            sendmessage($from_id, $textbotlang['Admin']['managepanel']['userNotInPanel2'], $options_ui, 'HTML');
            return;
        }
        $servies = [];
        foreach ($data['inbounds'] as $service) {
            $servies[] = $service;
        }
        $datainbound = json_encode($servies);
    } elseif ($marzban_list_get['type'] == "ibsng" || $marzban_list_get['type'] == "mikrotik") {
        $datainbound = $text;
    } else {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['inboundUnsupported'], $shopkeyboard, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("UPDATE product SET inbounds = :inbounds WHERE id = :name_product AND (Location = :Location OR Location = '/all') AND agent = :agent");
    $stmt->bindParam(':inbounds', $datainbound);
    $stmt->bindParam(':name_product', $user['Processing_value']);
    $stmt->bindParam(':Location', $marzban_list_get['name_panel']);
    $stmt->bindParam(':agent', $user['Processing_value_tow']);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Product']['updated'], $shopkeyboard, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/extendadmin_(\w+)/', $datain, $dataget) || strpos($text, "/extend ") !== false) {
    if ($text[0] == "/") {
        $usernameconfig = explode(" ", $text)[1];
        $id_invoice = select("invoice", "id_invoice", "username", $usernameconfig, 'select');
        if ($id_invoice == false) {
            sendmessage($from_id, $textbotlang['Admin']['manageUser']['notFound'], null, 'HTML');
            return;
        }
        $id_invoice = $id_invoice['id_invoice'];
    } else {
        $id_invoice = $dataget[1];
    }
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    if ($nameloc == false) {
        sendmessage($from_id, $textbotlang['Admin']['order']['renewError'], null, 'HTML');
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "Unsuccessful") {
        sendmessage($from_id, $textbotlang['users']['status']['error'], null, 'html');
        return;
    }
    update("user", "Processing_value_one", $nameloc['id_invoice'], "id", $from_id);
    savedata("clear", "id_invoice", $nameloc['id_invoice']);
    $textcustom = $textbotlang['Admin']['order']['askVolume'];
    sendmessage($from_id, $textcustom, $backuser, 'html');
    step('gettimecustomvolomforextendadmin', $from_id);
} elseif ($user['step'] == "gettimecustomvolomforextendadmin") {
    $userdate = json_decode($user['Processing_value'], true);
    $nameloc = select("invoice", "*", "id_invoice", $userdate['id_invoice'], "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidVolume'], $backuser, 'HTML');
        return;
    }
    savedata("save", "volume", $text);
    $textcustom = $textbotlang['Admin']['order']['askTime'];
    sendmessage($from_id, $textcustom, $backuser, 'html');
    step('getvolumecustomuserforextendadmin', $from_id);
} elseif ($user['step'] == "getvolumecustomuserforextendadmin") {
    $userdate = json_decode($user['Processing_value'], true);
    $nameloc = select("invoice", "*", "id_invoice", $userdate['id_invoice'], "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidTime'], $backuser, 'HTML');
        return;
    }
    $prodcut['name_product'] = $nameloc['name_product'];
    $prodcut['note'] = "";
    $prodcut['price_product'] = 0;
    $prodcut['Service_time'] = $text;
    $prodcut['Volume_constraint'] = $userdate['volume'];
    update("invoice", "name_product", $prodcut['name_product'], "id_invoice", $userdate['id_invoice']);
    update("invoice", "price_product", $prodcut['price_product'], "id_invoice", $userdate['id_invoice']);
    update("invoice", "Volume", $prodcut['Volume_constraint'], "id_invoice", $userdate['id_invoice']);
    update("invoice", "Service_time", $prodcut['Service_time'], "id_invoice", $userdate['id_invoice']);
    step("home", $from_id);
    $keyboardextend = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['extend']['confirm'], 'callback_data' => "confirmserivceadmin-" . $nameloc['id_invoice']],
            ],
            [
                ['text' => $textbotlang['keyboard']['backToMainMenu2'], 'callback_data' => "backuser"]
            ]
        ]
    ]);
    $textextend = sprintf($textbotlang['users']['extend']['invoiceCreatedByAdmin'], $nameloc['username'], $prodcut['name_product'], $prodcut['Service_time'], $prodcut['Volume_constraint'], $prodcut['note']);
    if ($user['step'] == "getvolumecustomuserforextendadmin") {
        sendmessage($from_id, $textextend, $keyboardextend, 'HTML');
    } else {
        Editmessagetext($from_id, $message_id, $textextend, $keyboardextend);
    }
} elseif (preg_match('/^confirmserivceadmin-(.*)/', $datain, $dataget)) {
    Editmessagetext($from_id, $message_id, $text_inline, json_encode(['inline_keyboard' => []]));
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $prodcut['code_product'] = "custom_volume";
    $prodcut['name_product'] = $nameloc['name_product'];
    $prodcut['price_product'] = 0;
    $prodcut['Service_time'] = $nameloc['Service_time'];
    $prodcut['Volume_constraint'] = $nameloc['Volume'];
    if ($prodcut == false || !in_array($nameloc['Status'], ['active', 'end_of_time', 'end_of_volume', 'sendedwarn', 'send_on_hold'])) {
        sendmessage($from_id, $textbotlang['Admin']['order']['renewError'], null, 'HTML');
        return;
    }
    deletemessage($from_id, $message_id);
    $extend = $ManagePanel->extend($marzban_list_get['Methodextend'], $prodcut['Volume_constraint'], $prodcut['Service_time'], $nameloc['username'], $prodcut['code_product'], $marzban_list_get['code_panel']);
    if ($extend['status'] == false) {
        $extend['msg'] = json_encode($extend['msg']);
        $textreports = sprintf($textbotlang['Admin']['reportgroup']['errorRenewServiceAdmin'], $marzban_list_get['name_panel'], $nameloc['username'], $extend['msg']);
        sendmessage($from_id, $textbotlang['Admin']['order']['renewErrorSupport'], null, 'HTML');
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
    $dateacc = date('Y/m/d H:i:s');
    $value = $prodcut['Volume_constraint'] . "_" . $prodcut['Service_time'];
    $type = "extend_user_by_admin";
    $stmt->bindParam(':id_user', $from_id, PDO::PARAM_STR);
    $stmt->bindParam(':username', $nameloc['username'], PDO::PARAM_STR);
    $stmt->bindParam(':value', $value, PDO::PARAM_STR);
    $stmt->bindParam(':type', $type, PDO::PARAM_STR);
    $stmt->bindParam(':time', $dateacc, PDO::PARAM_STR);
    $stmt->bindParam(':price', $prodcut['price_product'], PDO::PARAM_STR);
    $output_json = json_encode($extend);
    $stmt->bindParam(':output', $output_json, PDO::PARAM_STR);
    $stmt->execute();
    update("invoice", "Status", "active", "id_invoice", $id_invoice);
    sendmessage($from_id, $textbotlang['users']['extend']['thanks'], null, 'HTML');
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['renewedByAdmin'], $from_id, $nameloc['id_user'], $prodcut['name_product'], $nameloc['username'], $nameloc['Service_location']);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherservice,
            'text' => $text_report,
            'parse_mode' => "HTML"
        ]);
    }
} elseif (preg_match('/removeresid_(\w+)/', $datain, $dataget)) {
    $idorder = $dataget[1];
    $stmt = $pdo->prepare("DELETE FROM Payment_report WHERE id_order = :id_order");
    $stmt->bindParam(':id_order', $idorder, PDO::PARAM_STR);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Payment']['receiptDeleted'], null, 'HTML');
}
if (isset($update["inline_query"])) {
    $sql = "SELECT * FROM invoice WHERE (username LIKE CONCAT('%', :username, '%') OR note  LIKE CONCAT('%', :notes, '%') OR Volume LIKE CONCAT('%',:Volume, '%') OR Service_time LIKE CONCAT('%',:Service_time, '%')) AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $query, PDO::PARAM_STR);
    $stmt->bindParam(':Service_time', $query, PDO::PARAM_STR);
    $stmt->bindParam(':Volume', $query, PDO::PARAM_STR);
    $stmt->bindParam(':notes', $query, PDO::PARAM_STR);
    $stmt->execute();
    $invoices = $stmt->fetchAll();
    $results = [];
    foreach ($invoices as $OrderUser) {
        if (isset($OrderUser['time_sell'])) {
            $datatime = jdate('Y/m/d H:i:s', $OrderUser['time_sell']);
        } else {
            $datatime = $textbotlang['Admin']['manageUser']['dataorder'];
        }
        if ($OrderUser['name_product'] == $textbotlang['common']['labels']['testServiceName']) {
            $OrderUser['Service_time'] = $OrderUser['Service_time'] . $textbotlang['Admin']['unit']['hours'];
            $OrderUser['Volume'] = $OrderUser['Volume'] . $textbotlang['Admin']['unit']['megabytes'];
        } else {
            $OrderUser['Service_time'] = $OrderUser['Service_time'] . $textbotlang['Admin']['unit']['days'];
            $OrderUser['Volume'] = $OrderUser['Volume'] . $textbotlang['Admin']['unit']['gigabytes'];
        }
        $results[] = [
            "type" => "article",
            "id" => uniqid(),
            'cache_time' => 0,
            'is_personal' => true,
            "title" => $OrderUser['username'],
            "input_message_content" => [
                "message_text" => sprintf($textbotlang['Admin']['order']['detailRow3'], $OrderUser['id_invoice'], $OrderUser['Status'], $OrderUser['id_user'], $OrderUser['username'], $OrderUser['Service_location'], $OrderUser['name_product'], $OrderUser['price_product'], $OrderUser['Volume'], $OrderUser['Service_time'], $datatime)
            ]
        ];
    }
    answerInlineQuery($inline_query_id, $results);
} elseif (preg_match('/vieworderuser_(\w+)/', $datain, $datagetr)) {
    $id_user = $datagetr[1];
    update("user", "pagenumber", "1", "id", $from_id);
    $page = 1;
    $items_per_page = 10;
    $start_index = ($page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM invoice WHERE id_user = ?  LIMIT ?, ?");
    $result->bindValue(1, $id_user, PDO::PARAM_STR);
    $result->bindValue(2, $start_index, PDO::PARAM_INT);
    $result->bindValue(3, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['serviceStatus'], 'callback_data' => "Status"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['keyboard']['viewInfo'],
                'callback_data' => "manageinvoice_" . $row['id_invoice']
            ],
            [
                'text' => $row['Status'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['username'],
                'callback_data' => $row['username']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageinvoice_' . $id_user
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_pageinvoice_' . $id_user
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    sendmessage($from_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json, 'html');
} elseif (preg_match('/next_pageinvoice_(\w+)/', $datain, $datagetr)) {
    $id_user = $datagetr[1];
    $numpage = select("invoice", "*", "id_user", $id_user, "count");
    $page = $user['pagenumber'];
    $items_per_page = 10;
    $sum = $user['pagenumber'] * $items_per_page;
    if ($sum > $numpage) {
        $next_page = 1;
    } else {
        $next_page = $page + 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM invoice WHERE id_user = ?  LIMIT ?, ?");
    $result->bindValue(1, $id_user, PDO::PARAM_STR);
    $result->bindValue(2, $start_index, PDO::PARAM_INT);
    $result->bindValue(3, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['serviceStatus'], 'callback_data' => "Status"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['keyboard']['viewInfo'],
                'callback_data' => "manageinvoice_" . $row['id_invoice']
            ],
            [
                'text' => $row['Status'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['username'],
                'callback_data' => $row['username']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageinvoice_' . $id_user
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_pageinvoice_' . $id_user
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif (preg_match('/previous_pageinvoice_(\w+)/', $datain, $datagetr)) {
    $id_user = $datagetr[1];
    $numpage = select("invoice", "*", "id_user", $id_user, "count");
    $page = $user['pagenumber'];
    $items_per_page = 10;
    if ($user['pagenumber'] <= 1) {
        $next_page = 1;
    } else {
        $next_page = $page - 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM invoice WHERE id_user = ?  LIMIT ?, ?");
    $result->bindValue(1, $id_user, PDO::PARAM_STR);
    $result->bindValue(2, $start_index, PDO::PARAM_INT);
    $result->bindValue(3, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['serviceStatus'], 'callback_data' => "Status"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['keyboard']['viewInfo'],
                'callback_data' => "manageinvoice_" . $row['id_invoice']
            ],
            [
                'text' => $row['Status'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['username'],
                'callback_data' => $row['username']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageinvoice_' . $id_user
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_pageinvoice_' . $id_user
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif ($datain == "cartuserlist") {
    update("user", "pagenumber", "1", "id", $from_id);
    $page = 1;
    $items_per_page = 10;
    $start_index = ($page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM user WHERE cardpayment = '1'  LIMIT ?, ?");
    $result->bindValue(1, $start_index, PDO::PARAM_INT);
    $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageusercart'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_pageusercart'
        ]
    ];
    $backbtn = [
        [
            'text' => $textbotlang['keyboard']['backToPrev'],
            'callback_data' => 'backlistuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $backbtn;
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif ($datain == 'next_pageusercart') {
    $numpage = select("user", "*", null, null, "count");
    $page = $user['pagenumber'];
    $items_per_page = 10;
    $sum = $user['pagenumber'] * $items_per_page;
    if ($sum > $numpage) {
        $next_page = 1;
    } else {
        $next_page = $page + 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM user WHERE cardpayment = '1'  LIMIT ?, ?");
    $result->bindValue(1, $start_index, PDO::PARAM_INT);
    $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageusercart'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_pageusercart'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif ($datain == 'previous_pageusercart') {
    $page = $user['pagenumber'];
    $items_per_page = 10;
    if ($user['pagenumber'] <= 1) {
        $next_page = 1;
    } else {
        $next_page = $page - 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM user WHERE cardpayment = '1'  LIMIT ?, ?");
    $result->bindValue(1, $start_index, PDO::PARAM_INT);
    $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageusercart'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_pageusercart'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif (preg_match('/createbot_(\w+)/', $datain, $datagetr) && $adminrulecheck['rule'] == "administrator") {
    $id_user = $datagetr[1];
    $checkbot = select("botsaz", "*", "id_user", $id_user, "count");
    if ($checkbot != 0) {
        $textexitsbot = $textbotlang['Admin']['agentbot']['alreadyInstalled'];
        sendmessage($from_id, $textexitsbot, $keyboardadmin, 'HTML');
        return;
    }
    savedata("clear", "id_user", $id_user);
    $texbot = $textbotlang['Admin']['agentbot']['intro'];
    sendmessage($from_id, $texbot, $backadmin, 'HTML');
    step("gettokenbot", $from_id);
} elseif ($user['step'] == "gettokenbot") {
    $getInfoToken = json_decode(file_get_contents("https://api.telegram.org/bot$text/getme"), true);
    if ($getInfoToken == false or !$getInfoToken['ok']) {
        sendmessage($from_id, $textbotlang['Admin']['agentbot']['invalidToken'], $backadmin, 'HTML');
        return;
    }
    $checkbot = select("botsaz", "*", "bot_token", $text, "count");
    if ($checkbot != 0) {
        sendmessage($from_id, $textbotlang['Admin']['agentbot']['tokenExists'], null, 'HTML');
        return;
    }
    savedata("save", "token", $text);
    savedata("save", "username", $getInfoToken['result']['username']);
    $texbot = $textbotlang['Admin']['manageadmin']['askId'];
    sendmessage($from_id, $texbot, $backadmin, 'HTML');
    step("getadminidbot", $from_id);
} elseif ($user['step'] == "getadminidbot") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    $userdate = json_decode($user['Processing_value'], true);
    step("home", $from_id);
    $admin_ids = json_encode(array(
        $userdate['id_user']
    ));
    $destination = getcwd();
    $dirsource = "$destination/vpnbot/{$userdate['id_user']}{$userdate['username']}";
    if (is_dir($dirsource) && !deleteDirectory($dirsource)) {
        error_log('Failed to remove existing bot directory: ' . $dirsource);
    }
    if (!copyDirectoryContents($destination . '/vpnbot/Default', $dirsource)) {
        error_log('Failed to copy default bot files into: ' . $dirsource);
    }
    $contentconfig = file_get_contents($dirsource . "/config.php");
    $new_code = str_replace('BotTokenNew', $userdate['token'], $contentconfig);
    file_put_contents($dirsource . "/config.php", $new_code);
    file_get_contents("https://api.telegram.org/bot{$userdate['token']}/setwebhook?url=https://$domainhosts/vpnbot/{$userdate['id_user']}{$userdate['username']}/index.php");
    file_get_contents(sprintf($textbotlang['Admin']['agentbot']['activatedUrlAlt'], $userdate['token'], $userdate['id_user']));
    $datasetting = json_encode(array(
        "minpricetime" => 4000,
        "pricetime" => 4000,
        "minpricevolume" => 4000,
        "pricevolume" => 4000,
        "support_username" => "@support",
        "Channel_Report" => 0,
        "cart_info" => $textbotlang['users']['Balance']['cardInstructionAlt'],
        'show_product' => true,
    ));
    $value = "{}";
    $stmt = $pdo->prepare("INSERT INTO botsaz (id_user,bot_token,admin_ids,username,time,setting,hide_panel) VALUES (:id_user,:bot_token,:admin_ids,:username,:time,:setting,:hide_panel)");
    $stmt->bindParam(':id_user', $userdate['id_user'], PDO::PARAM_STR);
    $stmt->bindParam(':bot_token', $userdate['token'], PDO::PARAM_STR);
    $stmt->bindParam(':admin_ids', $admin_ids);
    $stmt->bindParam(':username', $userdate['username'], PDO::PARAM_STR);
    $time = date('Y/m/d H:i:s');
    $stmt->bindParam(':time', $time, PDO::PARAM_STR);
    $stmt->bindParam(':setting', $datasetting, PDO::PARAM_STR);
    $stmt->bindParam(':hide_panel', $value, PDO::PARAM_STR);
    $stmt->execute();
    $texbot = sprintf($textbotlang['Admin']['agentbot']['created'], $userdate['username'], $userdate['token']);
    sendmessage($from_id, $texbot, $keyboardadmin, 'HTML');
} elseif (preg_match('/removebotsell_(\w+)/', $datain, $datagetr)) {
    $id_user = $datagetr[1];
    $contentbto = select("botsaz", "*", "id_user", $id_user, "select");
    $destination = getcwd();
    $dirsource = "$destination/vpnbot/$id_user{$contentbto['username']}";
    if (is_dir($dirsource) && !deleteDirectory($dirsource)) {
        error_log('Failed to remove bot directory: ' . $dirsource);
    }
    if (!empty($contentbto['bot_token'])) {
        file_get_contents("https://api.telegram.org/bot{$contentbto['bot_token']}/deletewebhook");
    }
    $stmt = $pdo->prepare("DELETE FROM botsaz WHERE id_user = :id_user");
    $stmt->bindParam(':id_user', $id_user, PDO::PARAM_STR);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['agentbot']['deleted'], $keyboardadmin, 'HTML');
} elseif (preg_match('/setvolumesrc_(\w+)/', $datain, $datagetr)) {
    $id_user = $datagetr[1];
    savedata("clear", "id_user", $id_user);
    sendmessage($from_id, $textbotlang['Admin']['price']['askAgentVolume'], $backadmin, 'HTML');
    step("getpricevolumesrc", $from_id);
} elseif ($user['step'] == "getpricevolumesrc") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    step("home", $from_id);
    $userdate = json_decode($user['Processing_value'], true);
    $botinfo = json_decode(select("botsaz", "setting", "id_user", $userdate['id_user'], "select")['setting'], true);
    $botinfo['minpricevolume'] = $text;
    update("botsaz", "setting", json_encode($botinfo), "id_user", $userdate['id_user']);
    sendmessage($from_id, $textbotlang['Admin']['price']['saved'], $keyboardadmin, 'HTML');
} elseif (preg_match('/settimepricesrc_(\w+)/', $datain, $datagetr)) {
    $id_user = $datagetr[1];
    savedata("clear", "id_user", $id_user);
    sendmessage($from_id, $textbotlang['Admin']['price']['askAgentTime'], $backadmin, 'HTML');
    step("getpricetimesrc", $from_id);
} elseif ($user['step'] == "getpricetimesrc") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    step("home", $from_id);
    $userdate = json_decode($user['Processing_value'], true);
    $botinfo = json_decode(select("botsaz", "setting", "id_user", $userdate['id_user'], "select")['setting'], true);
    $botinfo['minpricetime'] = $text;
    update("botsaz", "setting", json_encode($botinfo), "id_user", $userdate['id_user']);
    sendmessage($from_id, $textbotlang['Admin']['price']['saved'], $keyboardadmin, 'HTML');
}
if ($datain == "settimecornday" && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['cronjob']['askNotifyDays'] . $setting['daywarn'] . $textbotlang['Admin']['unit']['day'], $backadmin, 'HTML');
    step("getdaywarn", $from_id);
} elseif ($user['step'] == "getdaywarn") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['cronjob']['changedData'], $keyboardadmin, 'HTML');
    step("home", $from_id);
    update("setting", "daywarn", $text);
} elseif ($datain == "linkappsetting") {
    sendmessage($from_id, $textbotlang['Admin']['selectOption5'], $keyboardlinkapp, 'HTML');
} elseif ($text == $textbotlang['keyboard']['addApp']) {
    sendmessage($from_id, $textbotlang['Admin']['apps']['askName'], $backadmin, 'HTML');
    step("getnamebtnapp", $from_id);
} elseif ($user['step'] == "getnamebtnapp") {
    if (strlen($text) > 200) {
        sendmessage($from_id, $textbotlang['Admin']['apps']['nameTooLong'], $backadmin, 'HTML');
        return;
    }
    savedata("clear", "name", $text);
    sendmessage($from_id, $textbotlang['Admin']['apps']['askLink'], $backadmin, 'HTML');
    step("geturlbtnapp", $from_id);
} elseif ($user['step'] == "geturlbtnapp") {
    if (!filter_var($text, FILTER_VALIDATE_URL)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['invalidDomain'], $backadmin, 'HTML');
        return;
    }
    $userdate = json_decode($user['Processing_value'], true);
    $stmt = $pdo->prepare("INSERT INTO app (name, link) VALUES (:name, :link)");
    $stmt->bindParam(':name', $userdate['name'], PDO::PARAM_STR);
    $stmt->bindParam(':link', $text, PDO::PARAM_STR);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['apps']['added'], $keyboardlinkapp, 'HTML');
    step("home", $from_id);
} elseif ($text == $textbotlang['keyboard']['deleteApp']) {
    sendmessage($from_id, $textbotlang['Admin']['apps']['selectDelete'], $json_list_remove_helpـlink, 'HTML');
    step("getnameappforremove", $from_id);
} elseif ($user['step'] == "getnameappforremove") {
    sendmessage($from_id, $textbotlang['Admin']['apps']['deleted'], $keyboardlinkapp, 'HTML');
    step('home', $from_id);
    $stmt = $pdo->prepare("DELETE FROM app WHERE name = :name");
    $stmt->bindParam(':name', $text, PDO::PARAM_STR);
    $stmt->execute();
} elseif ($text == $textbotlang['keyboard']['panelFeatureStatus'] && $adminrulecheck['rule'] == "administrator") {
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if (!in_array($panel['subvip'], ['offsubvip', 'onsubvip'])) {
        update("marzban_panel", "subvip", "offsubvip", "code_panel", $panel['code_panel']);
        $panel = select("marzban_panel", "*", "code_panel", $panel['code_panel'], "select");
    }
    if (!in_array($panel['version_panel'], ['0', '1'])) {
        $panel['version_panel'] = '0';
    }
    $customvlume = json_decode($panel['customvolume'], true);
    $statusconfig = [
        'onconfig' => $textbotlang['Admin']['Status']['statuson'],
        'offconfig' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['config']];
    $statussublink = [
        'onsublink' => $textbotlang['Admin']['Status']['statuson'],
        'offsublink' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['sublink']];
    $statusshowbuy = [
        'active' => $textbotlang['Admin']['Status']['statuson'],
        'disable' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['status']];
    $statusshowtest = [
        'ONTestAccount' => $textbotlang['Admin']['Status']['statuson'],
        'OFFTestAccount' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['TestAccount']];
    $statusconnecton = [
        'onconecton' => $textbotlang['Admin']['Status']['statuson'],
        'offconecton' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['conecton']];
    $status_extend = [
        'on_extend' => $textbotlang['Admin']['Status']['statuson'],
        'off_extend' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['status_extend']];
    $changeloc = [
        'onchangeloc' => $textbotlang['Admin']['Status']['statuson'],
        'offchangeloc' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['changeloc']];
    $inbocunddisable = [
        'oninbounddisable' => $textbotlang['Admin']['Status']['statuson'],
        'offinbounddisable' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['inboundstatus']];
    $subvip = [
        'onsubvip' => $textbotlang['Admin']['Status']['statuson'],
        'offsubvip' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['subvip']];
    $customstatusf = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$customvlume['f']];
    $customstatusn = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$customvlume['n']];
    $customstatusn2 = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$customvlume['n2']];
    $on_hold_test = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['on_hold_test']];
    $Bot_Status = [
        'inline_keyboard' => [
            [
                ['text' => $statusshowbuy, 'callback_data' => "editpanel-statusbuy-{$panel['status']}-{$panel['code_panel']}"],
                ['text' => $textbotlang['keyboard']['showPanel'], 'callback_data' => "none"],
            ],
            [
                ['text' => $statusshowtest, 'callback_data' => "editpanel-statustest-{$panel['TestAccount']}-{$panel['code_panel']}"],
                ['text' => $textbotlang['keyboard']['showTestAccount'], 'callback_data' => "none"],
            ],
            [
                ['text' => $status_extend, 'callback_data' => "editpanel-stautsextend-{$panel['status_extend']}-{$panel['code_panel']}"],
                ['text' => $textbotlang['keyboard']['renewalStatus'], 'callback_data' => "none"],
            ],
            [
                ['text' => $customstatusf, 'callback_data' => "editpanel-customstatusf-{$customvlume['f']}-{$panel['code_panel']}"],
                ['text' => $textbotlang['keyboard']['customServiceGroupF'], 'callback_data' => "none"],
            ],
            [
                ['text' => $customstatusn, 'callback_data' => "editpanel-customstatusn-{$customvlume['n']}-{$panel['code_panel']}"],
                ['text' => $textbotlang['keyboard']['customServiceGroupN'], 'callback_data' => "none"],
            ],
            [
                ['text' => $customstatusn2, 'callback_data' => "editpanel-customstatusn2-{$customvlume['n2']}-{$panel['code_panel']}"],
                ['text' => $textbotlang['keyboard']['customServiceGroupN2'], 'callback_data' => "none"],
            ]
        ]
    ];
    if (!in_array($panel['type'], ['Manualsale', "WGDashboard", 'hiddify'])) {
        $Bot_Status['inline_keyboard'][] = [
            ['text' => $statusconfig, 'callback_data' => "editpanel-stautsconfig-{$panel['config']}-{$panel['code_panel']}"],
            ['text' => $textbotlang['keyboard']['sendConfig'], 'callback_data' => "none"],
        ];
    }
    if (!in_array($panel['type'], ['Manualsale', "WGDashboard", 'hiddify'])) {
        $Bot_Status['inline_keyboard'][] = [
            ['text' => $statussublink, 'callback_data' => "editpanel-sublink-{$panel['sublink']}-{$panel['code_panel']}"],
            ['text' => $textbotlang['keyboard']['sendSubLink'], 'callback_data' => "none"],
        ];
    }
    if (in_array($panel['type'], ['marzban', "x-ui_single", "marzneshin"])) {
        $Bot_Status['inline_keyboard'][] = [
            ['text' => $statusconnecton, 'callback_data' => "editpanel-connecton-{$panel['conecton']}-{$panel['code_panel']}"],
            ['text' => $textbotlang['keyboard']['firstConnection'], 'callback_data' => "none"],
        ];
        $Bot_Status['inline_keyboard'][] = [
            ['text' => $on_hold_test, 'callback_data' => "editpanel-on_hold_Test-{$panel['on_hold_test']}-{$panel['code_panel']}"],
            ['text' => $textbotlang['keyboard']['firstConnectionTest'], 'callback_data' => "none"],
        ];
    }
    if (!in_array($panel['type'], ["Manualsale", "WGDashboard"])) {
        $Bot_Status['inline_keyboard'][] = [
            ['text' => $changeloc, 'callback_data' => "editpanel-changeloc-{$panel['changeloc']}-{$panel['code_panel']}"],
            ['text' => $textbotlang['keyboard']['changeLocation'], 'callback_data' => "none"],
        ];
        $Bot_Status['inline_keyboard'][] = [
            ['text' => $subvip, 'callback_data' => "editpanel-subvip-{$panel['subvip']}-{$panel['code_panel']}"],
            ['text' => $textbotlang['keyboard']['exclusiveSubLink'], 'callback_data' => "none"],
        ];
    }
    if (in_array($panel['type'], ["marzban"])) {
        $Bot_Status['inline_keyboard'][] = [
            ['text' => $inbocunddisable, 'callback_data' => "editpanel-inbocunddisable-{$panel['inboundstatus']}-{$panel['code_panel']}"],
            ['text' => $textbotlang['keyboard']['inactiveAccount'], 'callback_data' => "none"],
        ];
    }
    if ($panel['type'] == "ibsng" || $panel['type'] == "mikrotik") {
        unset($Bot_Status['inline_keyboard'][2]);
        unset($Bot_Status['inline_keyboard'][3]);
        unset($Bot_Status['inline_keyboard'][4]);
        unset($Bot_Status['inline_keyboard'][5]);
        unset($Bot_Status['inline_keyboard'][6]);
        unset($Bot_Status['inline_keyboard'][7]);
        unset($Bot_Status['inline_keyboard'][8]);
        unset($Bot_Status['inline_keyboard'][9]);
    }
    $Bot_Status['inline_keyboard'] = array_values($Bot_Status['inline_keyboard']);
    $Bot_Status = json_encode($Bot_Status);
    sendmessage($from_id, $textbotlang['Admin']['Status']['botTitle'], $Bot_Status, 'HTML');
} elseif (preg_match('/^editpanel-(.*)-(.*)-(.*)/', $datain, $dataget)) {
    $type = $dataget[1];
    $value = $dataget[2];
    $code_panel = $dataget[3];
    if ($type == "stautsconfig") {
        if ($value == "onconfig") {
            $valuenew = "offconfig";
        } else {
            $valuenew = "onconfig";
        }
        update("marzban_panel", "config", $valuenew, "code_panel", $code_panel);
    } elseif ($type == "sublink") {
        if ($value == "onsublink") {
            $valuenew = "offsublink";
        } else {
            $valuenew = "onsublink";
        }
        update("marzban_panel", "sublink", $valuenew, "code_panel", $code_panel);
    } elseif ($type == "statusbuy") {
        if ($value == "active") {
            $valuenew = "disable";
        } else {
            $valuenew = "active";
        }
        update("marzban_panel", "status", $valuenew, "code_panel", $code_panel);
    } elseif ($type == "statustest") {
        if ($value == "ONTestAccount") {
            $valuenew = "OFFTestAccount";
        } else {
            $valuenew = "ONTestAccount";
        }
        update("marzban_panel", "TestAccount", $valuenew, "code_panel", $code_panel);
    } elseif ($type == "connecton") {
        if ($value == "onconecton") {
            $valuenew = "offconecton";
        } else {
            $valuenew = "onconecton";
        }
        update("marzban_panel", "conecton", $valuenew, "code_panel", $code_panel);
    } elseif ($type == "stautsextend") {
        if ($value == "on_extend") {
            $valuenew = "off_extend";
        } else {
            $valuenew = "on_extend";
        }
        update("marzban_panel", "status_extend", $valuenew, "code_panel", $code_panel);
    } elseif ($type == "changeloc") {
        if ($value == "onchangeloc") {
            $valuenew = "offchangeloc";
        } else {
            $valuenew = "onchangeloc";
        }
        update("marzban_panel", "changeloc", $valuenew, "code_panel", $code_panel);
    } elseif ($type == "inbocunddisable") {
        if ($value == "oninbounddisable") {
            $valuenew = "offinbounddisable";
        } else {
            $valuenew = "oninbounddisable";
        }
        update("marzban_panel", "inboundstatus", $valuenew, "code_panel", $code_panel);
    } elseif ($type == "subvip") {
        if ($value == "onsubvip") {
            $valuenew = "offsubvip";
        } else {
            $valuenew = "onsubvip";
        }
        update("marzban_panel", "subvip", $valuenew, "code_panel", $code_panel);
    } elseif ($type == "customstatusf") {
        $panel = select("marzban_panel", "*", "code_panel", $code_panel, "select");
        $customvlume = json_decode($panel['customvolume'], true);
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        $customvlume['f'] = $valuenew;
        update("marzban_panel", "customvolume", json_encode($customvlume), "code_panel", $code_panel);
    } elseif ($type == "customstatusn") {
        $panel = select("marzban_panel", "*", "code_panel", $code_panel, "select");
        $customvlume = json_decode($panel['customvolume'], true);
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        $customvlume['n'] = $valuenew;
        update("marzban_panel", "customvolume", json_encode($customvlume), "code_panel", $code_panel);
    } elseif ($type == "customstatusn2") {
        $panel = select("marzban_panel", "*", "code_panel", $code_panel, "select");
        $customvlume = json_decode($panel['customvolume'], true);
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        $customvlume['n2'] = $valuenew;
        update("marzban_panel", "customvolume", json_encode($customvlume), "code_panel", $code_panel);
    } elseif ($type == "on_hold_Test") {
        if ($value == "0") {
            $valuenew = "1";
        } else {
            $valuenew = "0";
        }
        update("marzban_panel", "on_hold_test", $valuenew, "code_panel", $code_panel);
    }
    $panel = select("marzban_panel", "*", "code_panel", $code_panel, "select");
    $customvlume = json_decode($panel['customvolume'], true);
    $statusconfig = [
        'onconfig' => $textbotlang['Admin']['Status']['statuson'],
        'offconfig' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['config']];
    $statussublink = [
        'onsublink' => $textbotlang['Admin']['Status']['statuson'],
        'offsublink' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['sublink']];
    $statusshowbuy = [
        'active' => $textbotlang['Admin']['Status']['statuson'],
        'disable' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['status']];
    $statusshowtest = [
        'ONTestAccount' => $textbotlang['Admin']['Status']['statuson'],
        'OFFTestAccount' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['TestAccount']];
    $statusconnecton = [
        'onconecton' => $textbotlang['Admin']['Status']['statuson'],
        'offconecton' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['conecton']];
    $status_extend = [
        'on_extend' => $textbotlang['Admin']['Status']['statuson'],
        'off_extend' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['status_extend']];
    $changeloc = [
        'onchangeloc' => $textbotlang['Admin']['Status']['statuson'],
        'offchangeloc' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['changeloc']];
    $inbocunddisable = [
        'oninbounddisable' => $textbotlang['Admin']['Status']['statuson'],
        'offinbounddisable' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['inboundstatus']];
    $subvip = [
        'onsubvip' => $textbotlang['Admin']['Status']['statuson'],
        'offsubvip' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['subvip']];
    $customstatusf = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$customvlume['f']];
    $customstatusn = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$customvlume['n']];
    $customstatusn2 = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$customvlume['n2']];
    $on_hold_test = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$panel['on_hold_test']];
    $Bot_Status = [
        'inline_keyboard' => [
            [
                ['text' => $statusshowbuy, 'callback_data' => "editpanel-statusbuy-{$panel['status']}-{$panel['code_panel']}"],
                ['text' => $textbotlang['keyboard']['showPanel'], 'callback_data' => "none"],
            ],
            [
                ['text' => $statusshowtest, 'callback_data' => "editpanel-statustest-{$panel['TestAccount']}-{$panel['code_panel']}"],
                ['text' => $textbotlang['keyboard']['showTestAccount'], 'callback_data' => "none"],
            ],
            [
                ['text' => $status_extend, 'callback_data' => "editpanel-stautsextend-{$panel['status_extend']}-{$panel['code_panel']}"],
                ['text' => $textbotlang['keyboard']['renewalStatus'], 'callback_data' => "none"],
            ],
            [
                ['text' => $customstatusf, 'callback_data' => "editpanel-customstatusf-{$customvlume['f']}-{$panel['code_panel']}"],
                ['text' => $textbotlang['keyboard']['customServiceGroupF'], 'callback_data' => "none"],
            ],
            [
                ['text' => $customstatusn, 'callback_data' => "editpanel-customstatusn-{$customvlume['n']}-{$panel['code_panel']}"],
                ['text' => $textbotlang['keyboard']['customServiceGroupN'], 'callback_data' => "none"],
            ],
            [
                ['text' => $customstatusn2, 'callback_data' => "editpanel-customstatusn2-{$customvlume['n2']}-{$panel['code_panel']}"],
                ['text' => $textbotlang['keyboard']['customServiceGroupN2'], 'callback_data' => "none"],
            ]
        ]
    ];
    if (!in_array($panel['type'], ['Manualsale', "WGDashboard", 'hiddify'])) {
        $Bot_Status['inline_keyboard'][] = [
            ['text' => $statusconfig, 'callback_data' => "editpanel-stautsconfig-{$panel['config']}-{$panel['code_panel']}"],
            ['text' => $textbotlang['keyboard']['sendConfig'], 'callback_data' => "none"],
        ];
    }
    if (!in_array($panel['type'], ['Manualsale', "WGDashboard", 'hiddify'])) {
        $Bot_Status['inline_keyboard'][] = [
            ['text' => $statussublink, 'callback_data' => "editpanel-sublink-{$panel['sublink']}-{$panel['code_panel']}"],
            ['text' => $textbotlang['keyboard']['sendSubLink'], 'callback_data' => "none"],
        ];
    }
    if (in_array($panel['type'], ['marzban', "x-ui_single", "marzneshin"])) {
        $Bot_Status['inline_keyboard'][] = [
            ['text' => $statusconnecton, 'callback_data' => "editpanel-connecton-{$panel['conecton']}-{$panel['code_panel']}"],
            ['text' => $textbotlang['keyboard']['firstConnection'], 'callback_data' => "none"],
        ];
        $Bot_Status['inline_keyboard'][] = [
            ['text' => $on_hold_test, 'callback_data' => "editpanel-on_hold_Test-{$panel['on_hold_test']}-{$panel['code_panel']}"],
            ['text' => $textbotlang['keyboard']['firstConnectionTest'], 'callback_data' => "none"],
        ];
    }
    if (!in_array($panel['type'], ["Manualsale", "WGDashboard"])) {
        $Bot_Status['inline_keyboard'][] = [
            ['text' => $changeloc, 'callback_data' => "editpanel-changeloc-{$panel['changeloc']}-{$panel['code_panel']}"],
            ['text' => $textbotlang['keyboard']['changeLocation'], 'callback_data' => "none"],
        ];
        $Bot_Status['inline_keyboard'][] = [
            ['text' => $subvip, 'callback_data' => "editpanel-subvip-{$panel['subvip']}-{$panel['code_panel']}"],
            ['text' => $textbotlang['keyboard']['exclusiveSubLink'], 'callback_data' => "none"],
        ];
    }
    if (in_array($panel['type'], ["marzban"])) {
        $Bot_Status['inline_keyboard'][] = [
            ['text' => $inbocunddisable, 'callback_data' => "editpanel-inbocunddisable-{$panel['inboundstatus']}-{$panel['code_panel']}"],
            ['text' => $textbotlang['keyboard']['inactiveAccount'], 'callback_data' => "none"],
        ];
    }
    $Bot_Status = json_encode($Bot_Status);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['botTitle'], $Bot_Status);
} elseif ($datain == "startelegram") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $Startelegram, 'HTML');
} elseif ($text == $textbotlang['keyboard']['minAmountStar']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMinDeposit'], $backadmin, 'HTML');
    step("getmainaqstar", $from_id);
} elseif ($user['step'] == "getmainaqstar") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['minDepositSaved'], $Startelegram, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "minbalancestar");
} elseif ($text == $textbotlang['keyboard']['maxAmountStar']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMaxDeposit'], $backadmin, 'HTML');
    step("maxbalancestar", $from_id);
} elseif ($user['step'] == "maxbalancestar") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['maxDepositSaved'], $Startelegram, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "maxbalancestar");
} elseif ($text == $textbotlang['keyboard']['minAmountNowPayment']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMinDeposit'], $backadmin, 'HTML');
    step("getmainaqnowpayment", $from_id);
} elseif ($user['step'] == "getmainaqnowpayment") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['minDepositSaved'], $nowpayment_setting_keyboard, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "minbalancenowpayment");
} elseif ($text == $textbotlang['keyboard']['maxAmountNowPayment']) {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['askMaxDeposit'], $backadmin, 'HTML');
    step("maxbalancenowpayment", $from_id);
} elseif ($user['step'] == "maxbalancenowpayment") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['maxDepositSaved'], $nowpayment_setting_keyboard, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "maxbalancenowpayment");
} elseif ($text == $textbotlang['keyboard']['setEducationStar'] && $adminrulecheck['rule'] == "administrator") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['askTutorialMedia'], $backadmin, 'HTML');
    step("gethelpstar", $from_id);
} elseif ($user['step'] == "gethelpstar") {
    if ($text) {
        if (intval($text) == 2) {
            update("PaySetting", "ValuePay", "0", "NamePay", "helpstar");
        } else {
            $data = json_encode(array(
                'type' => "text",
                'text' => $text
            ));
            update("PaySetting", "ValuePay", $data, "NamePay", "helpstar");
        }
    } elseif ($photo) {
        $data = json_encode(array(
            'type' => "photo",
            'text' => $caption,
            'photoid' => $photoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpstar");
    } elseif ($video) {
        $data = json_encode(array(
            'type' => "video",
            'text' => $caption,
            'videoid' => $videoid
        ));
        update("PaySetting", "ValuePay", $data, "NamePay", "helpstar");
    } else {
        sendmessage($from_id, $textbotlang['Admin']['Help']['invalidContent'], $backadmin, 'HTML');
        return;
    }
    step('home', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Help']['tutorialSaved'], $Startelegram, 'HTML');
} elseif ($text == $textbotlang['keyboard']['cashbackStar']) {
    sendmessage($from_id, $textbotlang['Admin']['price']['askPaymentCashback2'], $backadmin, 'HTML');
    step("chashbackstar", $from_id);
} elseif ($user['step'] == "chashbackstar") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['price']['priceSaved'], $Startelegram, 'HTML');
    step("home", $from_id);
    update("PaySetting", "ValuePay", $text, "NamePay", "chashbackstar");
} elseif ($text == $textbotlang['keyboard']['quickSetVolumePrice']) {
    sendmessage($from_id, $textbotlang['Admin']['price']['customServiceIntro'], $backadmin, 'HTML');
    step("getpricef", $from_id);
} elseif ($user['step'] == "getpricef") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    savedata("clear", "pricef", $text);
    sendmessage($from_id, $textbotlang['Admin']['price']['askGroupN'], $backadmin, 'HTML');
    step("getpricnn", $from_id);
} elseif ($user['step'] == "getpricnn") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    savedata("save", "pricen", $text);
    sendmessage($from_id, $textbotlang['Admin']['price']['askGroupN2'], $backadmin, 'HTML');
    step("getpricnn2", $from_id);
} elseif ($user['step'] == "getpricnn2") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    $pricelist = json_encode(array(
        'f' => $userdata['pricef'],
        'n' => $userdata['pricen'],
        'n2' => $text
    ));
    update("marzban_panel", "pricecustomvolume", $pricelist, null, null);
    sendmessage($from_id, $textbotlang['Admin']['price']['savedGroup'], $keyboardadmin, 'HTML');
    step("home", $from_id);
} elseif ($text == $textbotlang['keyboard']['quickSetTimePrice']) {
    sendmessage($from_id, $textbotlang['Admin']['price']['customServiceIntro'], $backadmin, 'HTML');
    step("getpriceftime", $from_id);
} elseif ($user['step'] == "getpriceftime") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    savedata("clear", "pricef", $text);
    sendmessage($from_id, $textbotlang['Admin']['price']['askGroupN'], $backadmin, 'HTML');
    step("getpricnntime", $from_id);
} elseif ($user['step'] == "getpricnntime") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    savedata("save", "pricen", $text);
    sendmessage($from_id, $textbotlang['Admin']['price']['askGroupN2'], $backadmin, 'HTML');
    step("getpricnn2time", $from_id);
} elseif ($user['step'] == "getpricnn2time") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    $userdata = json_decode($user['Processing_value'], true);
    $pricelist = json_encode(array(
        'f' => $userdata['pricef'],
        'n' => $userdata['pricen'],
        'n2' => $text
    ));
    update("marzban_panel", "pricecustomtime", $pricelist, null, null);
    sendmessage($from_id, $textbotlang['Admin']['price']['savedGroup'], $keyboardadmin, 'HTML');
    step("home", $from_id);
} elseif ($datain == "changeloclimit") {
    sendmessage($from_id, $textbotlang['Admin']['changeLocation']['askLimitType'], $keyboardchangelimit, 'HTML');
} elseif ($text == $textbotlang['keyboard']['generalLimit']) {
    $limitnumber = json_decode($setting['limitnumber'], true);
    sendmessage($from_id, sprintf($textbotlang['Admin']['changeLocation']['askTotalLimit'], $limitnumber['all']), $backadmin, 'HTML');
    step("limitchangeall", $from_id);
} elseif ($user['step'] == "limitchangeall") {
    sendmessage($from_id, $textbotlang['Admin']['changeLocation']['limitSaved'], $keyboardchangelimit, 'HTML');
    step("home", $from_id);
    $value = json_decode($setting['limitnumber'], true);
    $value['all'] = intval($text);
    update("setting", "limitnumber", json_encode($value), null, null);
} elseif ($text == $textbotlang['keyboard']['freeLimit']) {
    $limitnumber = json_decode($setting['limitnumber'], true);
    sendmessage($from_id, sprintf($textbotlang['Admin']['changeLocation']['askFreeLimit'], $limitnumber['free']), $backadmin, 'HTML');
    step("limitfreechangefree", $from_id);
} elseif ($user['step'] == "limitfreechangefree") {
    sendmessage($from_id, $textbotlang['Admin']['changeLocation']['limitSaved'], $keyboardchangelimit, 'HTML');
    step("home", $from_id);
    $value = json_decode($setting['limitnumber'], true);
    $value['free'] = intval($text);
    update("setting", "limitnumber", json_encode($value), null, null);
} elseif ($text == $textbotlang['keyboard']['resetAllUsersLimit']) {
    $keyboarddata = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['confirmAndZero'], 'callback_data' => 'reasetchangeloc'],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['changeLocation']['resetConfirm'], $keyboarddata, 'HTML');
} elseif ($datain == "reasetchangeloc") {
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['changeLocation']['limitsReset'], null);
    update("user", "limitchangeloc", "0", null, null);
} elseif (preg_match('/changeloclimitbyuser_(\w+)/', $datain, $datagetr)) {
    $id_user = $datagetr[1];
    savedata("clear", "id_user", $id_user);
    sendmessage($from_id, $textbotlang['Admin']['changeLocation']['askUserLimit'], $backadmin, 'HTML');
    step("getlimitchangenewbyuser", $from_id);
} elseif ($user['step'] == "getlimitchangenewbyuser") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    step("home", $from_id);
    $userdate = json_decode($user['Processing_value'], true);
    if (!is_array($userdate) || empty($userdate['id_user'])) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $keyboardadmin, 'HTML');
        return;
    }
    update("user", "limitchangeloc", $text, "id", $userdate['id_user']);
    sendmessage($from_id, $textbotlang['Admin']['changeLocation']['userLimitSaved'], $keyboardadmin, 'HTML');
} elseif (preg_match('/hidepanel_(\w+)/', $datain, $datagetr)) {
    $id_user = $datagetr[1];
    savedata("clear", "id_user", $id_user);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['askHidePanelsAgent'], $json_list_marzban_panel, 'HTML');
    step("getpanelhidebotsaz", $from_id);
} elseif ($text == "/finish") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['panelsHidden'], $keyboardadmin, 'HTML');
    step("home", $from_id);
} elseif ($user['step'] == "getpanelhidebotsaz") {
    $userdata = json_decode($user['Processing_value'], true);
    $list_panel = json_decode(select("botsaz", "hide_panel", "id_user", $userdata['id_user'], "select")['hide_panel'] ?? '[]', true);
    if (!is_array($list_panel)) {
        $list_panel = [];
    }
    if (in_array($text, $list_panel)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['alreadyAdded'], null, 'HTML');
        return;
    }
    $list_panel[] = $text;
    update("botsaz", "hide_panel", json_encode($list_panel), "id_user", $userdata['id_user']);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['panelSelectedFinish'], null, 'HTML');
} elseif (preg_match('/removehide_(\w+)/', $datain, $datagetr)) {
    global $list_hide_panel;
    $id_user = $datagetr[1];
    savedata("clear", "id_user", $id_user);
    $list_panel = json_decode(select("botsaz", "hide_panel", "id_user", $id_user, "select")['hide_panel'] ?? '[]', true);
    if (!is_array($list_panel)) {
        $list_panel = [];
    }
    $list_hide_panel = [
        'keyboard' => [],
        'resize_keyboard' => true,
    ];
    foreach ($list_panel as $panelname) {
        $list_hide_panel['keyboard'][] = [
            ['text' => $panelname]
        ];
    }
    $list_hide_panel['keyboard'][] = [
        ['text' => $textbotlang['Admin']['backAdminBtn']],
    ];
    $list_hide_panel = json_encode($list_hide_panel);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['askShowPanelsAgent'], $list_hide_panel, 'HTML');
    step("getremovehidepanel", $from_id);
} elseif ($text == "/remove") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['panelsShown'], $keyboardadmin, 'HTML');
    step("home", $from_id);
} elseif ($user['step'] == "getremovehidepanel") {
    $userdata = json_decode($user['Processing_value'], true);
    $list_panel = json_decode(select("botsaz", "hide_panel", "id_user", $userdata['id_user'], "select")['hide_panel'] ?? '[]', true);
    if (!is_array($list_panel)) {
        $list_panel = [];
    }
    if (!in_array($text, $list_panel)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['panelNotInList'], null, 'HTML');
        return;
    }
    $count = 0;
    foreach ($list_panel as $panel) {
        if ($panel == $text) {
            unset($list_panel[$count]);
            break;
        }
        $count += 1;
    }
    $list_panel = array_values($list_panel);
    update("botsaz", "hide_panel", json_encode($list_panel), "id_user", $userdata['id_user']);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['panelSelectedRemove'], null, 'HTML');
} elseif ($datain == "voloume_or_day_all") {
    if (is_file('cronbot/username.json')) {
        $userslist = json_decode(file_get_contents('cronbot/username.json'), true);
        if (is_array($userslist) and count($userslist) != 0) {
            sendmessage($from_id, $textbotlang['Admin']['gift']['busy'], $keyboardadmin, 'HTML');
            return;
        }
    }
    sendmessage($from_id, $textbotlang['Admin']['gift']['askPanel'], $json_list_marzban_panel, "html");
    step("getpanelgift", $from_id);
} elseif ($user['step'] == "getpanelgift") {
    $panel = select("marzban_panel", "*", "name_panel", $text, "count");
    if ($panel == 0) {
        sendmessage($from_id, $textbotlang['Admin']['gift']['panelNotFound'], null, "html");
        return;
    }
    savedata("clear", "name_panel", $text);
    $keyboardstatistics = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['volume2'], 'callback_data' => 'typegift_volume'],
                ['text' => $textbotlang['keyboard']['timeDuration'], 'callback_data' => 'typegift_day'],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['gift']['selectType'], $keyboardstatistics, "html");
    step('home', $from_id);
} elseif (preg_match('/typegift_(\w+)/', $datain, $datagetr)) {
    $typegift = $datagetr[1];
    savedata("save", "typegift", $typegift);
    deletemessage($from_id, $message_id);
    if ($typegift == "volume") {
        sendmessage($from_id, $textbotlang['Admin']['gift']['askVolume'], $backadmin, "html");
    } else {
        sendmessage($from_id, $textbotlang['Admin']['gift']['askDays'], $backadmin, "html");
    }
    step("getvaluegift", $from_id);
} elseif ($user['step'] == "getvaluegift") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    savedata("save", "value", $text);
    sendmessage($from_id, $textbotlang['Admin']['gift']['askMessage'], $backadmin, "html");
    step("gettextgift", $from_id);
} elseif ($user['step'] == "gettextgift") {
    savedata("save", "text", $text);
    savedata("save", "id_admin", $from_id);
    $keyboardstatistics = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['confirmStartProcess'], 'callback_data' => 'startgift'],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['gift']['confirmStart'], $keyboardstatistics, "html");
    step("home", $from_id);
} elseif ($datain == "startgift") {
    $keyboardstatistics = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['cancelGiftSend'], 'callback_data' => 'cancel_gift'],
            ],
        ]
    ]);
    $userdata = json_decode($user['Processing_value'], true);
    if (!isset($userdata['typegift'])) {
        sendmessage($from_id, $textbotlang['Admin']['errorRestart'], $keyboardstatistics, "html");
        return;
    }
    $message_id = Editmessagetext($from_id, $message_id, $textbotlang['Admin']['gift']['started'], $keyboardstatistics);
    $userdata['id_message'] = $message_id['result']['message_id'];
    $stmt = $pdo->prepare("SELECT username FROM invoice WHERE  (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND Service_location = :mp24 AND name_product != :mp25");
    $stmt->execute([':mp24' => $userdata['name_panel'], ':mp25' => $textbotlang['common']['labels']['testServiceName']]);
    $userslist = json_encode($stmt->fetchAll());
    file_put_contents('cronbot/gift', json_encode($userdata));
    file_put_contents('cronbot/username.json', $userslist);
} elseif ($datain == "cancel_gift") {
    if (is_file('cronbot/username.json')) {
        unlink('cronbot/username.json');
    }
    if (is_file('cronbot/gift')) {
        unlink('cronbot/gift');
    }
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['gift']['canceled'], null, 'HTML');
} elseif (preg_match('/expireset_(\w+)/', $datain, $datagetr) && $adminrulecheck['rule'] == "administrator") {
    $id_user = $datagetr[1];
    savedata("clear", "id_user", $id_user);
    sendmessage($from_id, $textbotlang['Admin']['agent']['askExpiry'], $backadmin, 'HTML');
    step("gettime_expire_agent", $from_id);
} elseif ($user['step'] == "gettime_expire_agent") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    step("home", $from_id);
    $userdate = json_decode($user['Processing_value'], true);
    $timestamp = time() + (intval($text) * 86400);
    update("user", "expire", $timestamp, "id", $userdate['id_user']);
    sendmessage($from_id, $textbotlang['Admin']['agent']['expirySaved'], $keyboardadmin, 'HTML');
} elseif ($text == $textbotlang['keyboard']['groupShowCard']) {
    sendmessage($from_id, $textbotlang['Admin']['card']['askUserIds'], $backadmin, 'HTML');
    step("getlistidcart", $from_id);
} elseif ($user['step'] == "getlistidcart") {
    $list = explode("\n", $text);
    foreach ($list as $id_user) {
        $id_user = trim($id_user);
        if ($id_user === '')
            continue;
        if (!rowExists("user", "id", $id_user)) {
            sendmessage($from_id, sprintf($textbotlang['Admin']['manageUser']['notFoundById'], $id_user), $backadmin, 'HTML');
            continue;
        }
        update("user", "cardpayment", "1", "id", $id_user);
    }
    sendmessage($from_id, $textbotlang['Admin']['card']['enabledForUsers'], $CartManage, 'HTML');
    step("home", $from_id);
} elseif ($text == $textbotlang['keyboard']['exportActiveCardUsers']) {
    $listusers = select("user", "id", "cardpayment", "1", "fetchAll");
    if (!$listusers) {
        sendmessage($from_id, $textbotlang['Admin']['card']['noUsersEnabled'], $CartManage, 'HTML');
        return;
    }
    $filename = 'cartlist.txt';
    file_put_contents($filename, implode("\n", array_column($listusers, 'id')) . "\n");
    sendDocument($from_id, $filename, $textbotlang['Admin']['card']['enabledUserList']);
    unlink($filename);
} elseif ($text == $textbotlang['keyboard']['firstPurchaseCommission'] && $adminrulecheck['rule'] == "administrator") {
    $marzbanporsant_one_buy = select("affiliates", "*", null, null, "select");
    $keyboardDiscountaffiliates = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbanporsant_one_buy['porsant_one_buy'], 'callback_data' => $marzbanporsant_one_buy['porsant_one_buy']],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['affiliates']['commissionScope'], $keyboardDiscountaffiliates, 'HTML');
} elseif ($datain == "on_buy_porsant") {
    update("affiliates", "porsant_one_buy", "off_buy_porsant");
    $marzbanporsant_one_buy = select("affiliates", "*", null, null, "select");
    $keyboardDiscountaffiliates = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbanporsant_one_buy['porsant_one_buy'], 'callback_data' => $marzbanporsant_one_buy['porsant_one_buy']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['affiliates']['commissionScope'], $keyboardDiscountaffiliates);
} elseif ($datain == "off_buy_porsant") {
    update("affiliates", "porsant_one_buy", "on_buy_porsant");
    $marzbanporsant_one_buy = select("affiliates", "*", null, null, "select");
    $keyboardDiscountaffiliates = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbanporsant_one_buy['porsant_one_buy'], 'callback_data' => $marzbanporsant_one_buy['porsant_one_buy']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['affiliates']['commissionScope'], $keyboardDiscountaffiliates);
} elseif (preg_match('/changestatusadmin_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $nameloc['username']);
    if ($DataUserOut['status'] == "on_hold") {
        sendmessage($from_id, $textbotlang['users']['status']['notConnectedCannotChange'], null, 'html');
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
                    ['text' => $textbotlang['users']['status']['btnConfirmDisable'], 'callback_data' => "confirmaccountdisableadmin_" . $id_invoice],
                ],
                [
                    ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "manageinvoice_" . $nameloc['id_invoice']],
                ]
            ]
        ]);
        Editmessagetext($from_id, $message_id, $textbotlang['users']['status']['confirmDisableDesc'], $confirmdisableaccount);
    } else {
        $confirmdisableaccount = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['status']['btnConfirmEnable'], 'callback_data' => "confirmaccountdisableadmin_" . $id_invoice],
                ],
                [
                    ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "manageinvoice_" . $nameloc['id_invoice']],
                ]
            ]
        ]);
        Editmessagetext($from_id, $message_id, $textbotlang['users']['status']['confirmEnableDesc'], $confirmdisableaccount);
    }
} elseif (preg_match('/confirmaccountdisableadmin_(\w+)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $nameloc = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "manageinvoice_" . $nameloc['id_invoice']],
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
        update("invoice", "Status", "active", "id_invoice", $nameloc['id_invoice']);
        Editmessagetext($from_id, $message_id, $textbotlang['users']['status']['activedconfig'], $bakinfos);
    } else {
        update("invoice", "Status", "disablebyadmin", "id_invoice", $nameloc['id_invoice']);
        Editmessagetext($from_id, $message_id, $textbotlang['users']['status']['disabledconfig'], $bakinfos);
    }
} elseif (preg_match('/removefull-(.*)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $bakinfos = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['keyboard']['confirmAndDelete'], 'callback_data' => "confirmremovefulls-" . $id_invoice],
            ],
            [
                ['text' => $textbotlang['users']['status']['backinfo'], 'callback_data' => "manageinvoice_" . $id_invoice],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['order']['confirmDeleteFromDb'], $bakinfos);
} elseif (preg_match('/confirmremovefulls-(.*)/', $datain, $dataget)) {
    $id_invoice = $dataget[1];
    $invocie = select("invoice", "*", "id_invoice", $id_invoice, "select");
    $stmt = $pdo->prepare("DELETE FROM invoice WHERE id_invoice = :id_invoice");
    $stmt->bindParam(':id_invoice', $id_invoice, PDO::PARAM_STR);
    $stmt->execute();
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['order']['deleted'], json_encode(['inline_keyboard' => []]));
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $otherreport,
            'text' => strtr($textbotlang['keyboard']['adminDeletedService'], ['{from_id}' => $from_id, '{first_name}' => $first_name, '{service_username}' => $invocie['username']]),
            'parse_mode' => "HTML"
        ]);
    }
} elseif ($text == $textbotlang['keyboard']['addCategory']) {
    sendmessage($from_id, $textbotlang['Admin']['category']['askAddName'], $backadmin, 'HTML');
    step("getremarkcategory", $from_id);
} elseif ($user['step'] == "getremarkcategory") {
    sendmessage($from_id, $textbotlang['Admin']['category']['added'], $shopkeyboard, 'HTML');
    step("home", $from_id);
    $stmt = $pdo->prepare("INSERT INTO category (remark) VALUES (?)");
    $stmt->bindParam(1, $text);
    $stmt->execute();
} elseif ($text == $textbotlang['keyboard']['deleteCategory']) {
    sendmessage($from_id, $textbotlang['Admin']['category']['selectDelete'], KeyboardCategoryadmin(), 'HTML');
    step("removecategory", $from_id);
} elseif ($user['step'] == "removecategory") {
    sendmessage($from_id, $textbotlang['Admin']['category']['deleted'], $shopkeyboard, 'HTML');
    step("home", $from_id);
    $stmt = $pdo->prepare("DELETE FROM category WHERE remark = :remark ");
    $stmt->bindParam(':remark', $text);
    $stmt->execute();
} elseif ($text == $textbotlang['keyboard']['hidePanel'] && $adminrulecheck['rule'] == "administrator") {
    if ($user['Processing_value_one'] != "/all") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['hidePanelAllOnly'], null, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['askHidePanelProduct'], $json_list_marzban_panel, 'HTML');
    step('getlistpanel', $from_id);
} elseif ($text == "/end_hide") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['panelsHiddenProduct'], $shopkeyboard, 'HTML');
    step("home", $from_id);
} elseif ($user['step'] == "getlistpanel") {
    $list_panel = json_decode(select("product", "hide_panel", "id", $user['Processing_value'], "select")['hide_panel'] ?? '', true);
    if (!is_array($list_panel)) {
        $list_panel = [];
    }
    if (in_array($text, $list_panel)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['alreadyAdded'], null, 'HTML');
        return;
    }
    $list_panel[] = $text;
    update("product", "hide_panel", json_encode($list_panel), "id", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['panelSelectedEndHide'], null, 'HTML');
} elseif ($text == $textbotlang['keyboard']['deleteAllHiddenPanels'] && $adminrulecheck['rule'] == "administrator") {
    update("product", "hide_panel", "{}", "name_product", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['hiddenPanelsCleared'], null, 'HTML');
} elseif ($text == $textbotlang['keyboard']['reWebhookAgentBots']) {
    $bots_agent = select("botsaz", "*", null, null, "fetchAll");
    if (count($bots_agent) == 0) {
        sendmessage($from_id, $textbotlang['Admin']['agentbot']['noneFound'], null, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['agentbot']['webhookRunning'], null, 'HTML');
    foreach ($bots_agent as $bot) {
        file_get_contents("https://api.telegram.org/bot{$bot['bot_token']}/setwebhook?url=https://$domainhosts/vpnbot/{$bot['id_user']}{$bot['username']}/index.php");
    }
    sendmessage($from_id, $textbotlang['Admin']['agentbot']['webhookDone'], null, 'HTML');
} elseif (preg_match('/statuscronuser-(.*)/', $datain, $dataget)) {
    $id_user = $dataget[1];
    $user_status = select("user", "*", "id", $id_user);
    if (intval($user_status['status_cron']) == 0) {
        update("user", "status_cron", "1", "id", $id_user);
        sendmessage($from_id, $textbotlang['Admin']['cronjob']['userNotifyEnabled'], null, 'HTML');
    } else {
        update("user", "status_cron", "0", "id", $id_user);
        sendmessage($from_id, $textbotlang['Admin']['cronjob']['userNotifyDisabled'], null, 'HTML');
    }
} elseif ($text == $textbotlang['keyboard']['manageCategory']) {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboard_Category_manage, 'HTML');
} elseif ($text == $textbotlang['keyboard']['backToShopMenu']) {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $shopkeyboard, 'HTML');
} elseif ($text == $textbotlang['keyboard']['manageProducts'] || $datain == "backproductadmin") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboard_shop_manage, 'HTML');
} elseif ($text == $textbotlang['keyboard']['editCategoryMenu']) {
    sendmessage($from_id, $textbotlang['Admin']['category']['selectEdit'], KeyboardCategoryadmin(), 'HTML');
    step("editcategory_name", $from_id);
} elseif ($user['step'] == "editcategory_name") {
    savedata("clear", "category", $text);
    sendmessage($from_id, $textbotlang['Admin']['category']['askNewName'], $backadmin, 'HTML');
    step("get_name_new_category", $from_id);
} elseif ($user['step'] == "get_name_new_category") {
    $userdata = json_decode($user['Processing_value'], true);
    sendmessage($from_id, $textbotlang['Admin']['category']['renamed'], $keyboard_Category_manage, 'HTML');
    step("home", $from_id);
    update("category", "remark", $text, "remark", $userdata['category']);
    update("product", "category", $text, "category", $userdata['category']);
} elseif ($datain == "zerobalance") {
    update("user", "pagenumber", "1", "id", $from_id);
    $page = 1;
    $items_per_page = 10;
    $start_index = ($page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM user WHERE Balance < 0  LIMIT ?, ?");
    $result->bindValue(1, $start_index, PDO::PARAM_INT);
    $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageuserzero'
        ]
    ];
    $backbtn = [
        [
            'text' => $textbotlang['keyboard']['backToPrev'],
            'callback_data' => 'backlistuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backbtn;
    $keyboard_json = json_encode($keyboardlists);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif ($datain == 'next_pageuserzero') {
    $numpage = select("user", "*", null, null, "count");
    $page = $user['pagenumber'];
    $items_per_page = 10;
    $sum = $user['pagenumber'] * $items_per_page;
    if ($sum > $numpage) {
        $next_page = 1;
    } else {
        $next_page = $page + 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM user WHERE Balance < 0  LIMIT ?, ?");
    $result->bindValue(1, $start_index, PDO::PARAM_INT);
    $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageuserzero'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_pageuserzero'
        ]
    ];
    $backbtn = [
        [
            'text' => $textbotlang['keyboard']['backToPrev'],
            'callback_data' => 'backlistuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backbtn;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif ($datain == 'previous_pageuserzero') {
    $page = $user['pagenumber'];
    $items_per_page = 10;
    if ($user['pagenumber'] <= 1) {
        $next_page = 1;
    } else {
        $next_page = $page - 1;
    }
    $start_index = ($next_page - 1) * $items_per_page;
    $result = $pdo->prepare("SELECT * FROM user WHERE Balance < 0  LIMIT ?, ?");
    $result->bindValue(1, $start_index, PDO::PARAM_INT);
    $result->bindValue(2, $items_per_page, PDO::PARAM_INT);
    $result->execute();
    $keyboardlists = [
        'inline_keyboard' => [],
    ];
    $keyboardlists['inline_keyboard'][] = [
        ['text' => $textbotlang['keyboard']['operation'], 'callback_data' => "action"],
        ['text' => $textbotlang['keyboard']['username'], 'callback_data' => "username"],
        ['text' => $textbotlang['keyboard']['userId'], 'callback_data' => "iduser"]
    ];
    while ($row = ($result)->fetch(PDO::FETCH_ASSOC)) {
        $keyboardlists['inline_keyboard'][] = [
            [
                'text' => $textbotlang['Admin']['manageUser']['manageUserBtn'],
                'callback_data' => "manageuser_" . $row['id']
            ],
            [
                'text' => $row['username'],
                'callback_data' => "username"
            ],
            [
                'text' => $row['id'],
                'callback_data' => $row['id']
            ],
        ];
    }
    $pagination_buttons = [
        [
            'text' => $textbotlang['users']['page']['next'],
            'callback_data' => 'next_pageuserzero'
        ],
        [
            'text' => $textbotlang['users']['page']['previous'],
            'callback_data' => 'previous_pageuserzero'
        ]
    ];
    $backbtn = [
        [
            'text' => $textbotlang['keyboard']['backToPrev'],
            'callback_data' => 'backlistuser'
        ]
    ];
    $keyboardlists['inline_keyboard'][] = $pagination_buttons;
    $keyboardlists['inline_keyboard'][] = $backbtn;
    $keyboard_json = json_encode($keyboardlists);
    update("user", "pagenumber", $next_page, "id", $from_id);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageUser']['manageUserBtnDesc'], $keyboard_json);
} elseif ($text == $textbotlang['keyboard']['editApp']) {
    sendmessage($from_id, $textbotlang['Admin']['apps']['selectEdit'], $json_list_remove_helpـlink, 'HTML');
    step("edit_app", $from_id);
} elseif ($user['step'] == "edit_app") {
    savedata("clear", "nameapp", $text);
    step("get_new_lin_app", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['apps']['askNewLink'], $backadmin, 'HTML');
} elseif ($user['step'] == "get_new_lin_app") {
    step("home", $from_id);
    $userdata = json_decode($user['Processing_value'], true);
    sendmessage($from_id, $textbotlang['Admin']['apps']['updated'], $keyboardlinkapp, 'HTML');
    update("app", "link", $text, "name", $userdata['nameapp']);
} elseif ($datain == "nowpaymentsetting") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $nowpayment_setting_keyboard, 'HTML');
} elseif ($text == $textbotlang['keyboard']['autoConfirmNoCheckTime']) {
    sendmessage($from_id, sprintf($textbotlang['Admin']['Payment']['askAutoConfirmMinutes'], $setting['timeauto_not_verify']), $backadmin, 'HTML');
    step("gettimeauto", $from_id);
} elseif ($user['step'] == "gettimeauto") {
    if (!is_numeric($text)) {
        sendmessage($from_id, $textbotlang['common']['invalidInput'], $backadmin, 'HTML');
        return;
    }
    update("setting", "timeauto_not_verify", $text);
    sendmessage($from_id, $textbotlang['Admin']['cronjob']['timeSaved'], $CartManage, 'HTML');
    step("home", $from_id);
} elseif ($text == $textbotlang['keyboard']['showFirstPurchase']) {
    $panel = select("marzban_panel", "*", "code_panel", $user['Processing_value_one'], "select");
    $stmt = $pdo->prepare("SELECT * FROM product WHERE id = :name_product  AND agent = :agent AND (Location = :Location OR Location = '/all') LIMIT 1");
    $stmt->bindParam(':name_product', $user['Processing_value']);
    $stmt->bindParam(':Location', $panel['name_panel']);
    $stmt->bindParam(':agent', $user['Processing_value_tow']);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    $status_name = [
        '0' => $textbotlang['Admin']['Status']['off'],
        '1' => $textbotlang['Admin']['Status']['on']
    ][$product['one_buy_status']];
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $status_name, 'callback_data' => 'status_on_buy-' . $product['code_product'] . "-" . $product['one_buy_status']],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Product']['firstPurchaseDesc'], $Response, 'HTML');
} elseif (preg_match('/status_on_buy-(.*)-(.*)/', $datain, $dataget)) {
    $code_product = $dataget[1];
    $status_now = $dataget[2];
    if ($status_now == '0') {
        $status_now = '1';
    } else {
        $status_now = '0';
    }
    $panel = select("marzban_panel", "*", "code_panel", $user['Processing_value_one'], "select");
    $stmt = $pdo->prepare("UPDATE product SET one_buy_status = :one_buy_status WHERE code_product = :code_product AND (Location = :Location OR Location = '/all') AND agent = :agent");
    $stmt->bindParam(':one_buy_status', $status_now);
    $stmt->bindParam(':code_product', $code_product);
    $stmt->bindParam(':Location', $panel['name_panel']);
    $stmt->bindParam(':agent', $user['Processing_value_tow']);
    $stmt->execute();
    $stmt = $pdo->prepare("SELECT * FROM product WHERE code_product = :code_product  AND agent = :agent AND (Location = :Location OR Location = '/all') LIMIT 1");
    $stmt->bindParam(':code_product', $code_product);
    $stmt->bindParam(':Location', $panel['name_panel']);
    $stmt->bindParam(':agent', $user['Processing_value_tow']);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    $status_name = [
        '0' => $textbotlang['Admin']['Status']['off'],
        '1' => $textbotlang['Admin']['Status']['on']
    ][$product['one_buy_status']];
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $status_name, 'callback_data' => 'status_on_buy-' . $product['code_product'] . "-" . $product['one_buy_status']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Product']['firstPurchaseDesc'], $Response);
} elseif ($text == $textbotlang['keyboard']['excludeUserAutoConfirm']) {
    sendmessage($from_id, $textbotlang['Admin']['Payment']['autoConfirmSelect'], $Exception_auto_cart_keyboard, 'HTML');
} elseif ($text == $textbotlang['keyboard']['excludeUser']) {
    sendmessage($from_id, $textbotlang['Admin']['Payment']['askExcludeUserId'], $backadmin, 'HTML');
    step("getidExceptio", $from_id);
} elseif ($user['step'] == "getidExceptio") {
    if (!rowExists("user", "id", $text)) {
        sendmessage($from_id, $textbotlang['Admin']['Payment']['userNotFound'], $backadmin, 'HTML');
        return;
    }
    $list_Exceptions = select("PaySetting", "ValuePay", "NamePay", "Exception_auto_cart", "select")['ValuePay'];
    $list_Exceptions = is_string($list_Exceptions) ? json_decode($list_Exceptions, true) : [];
    if (in_array($text, $list_Exceptions)) {
        sendmessage($from_id, $textbotlang['Admin']['Payment']['userAlreadyExcluded'], $backadmin, 'HTML');
        return;
    }
    $list_Exceptions[] = $text;
    $list_Exceptions = array_values($list_Exceptions);
    sendmessage($from_id, $textbotlang['Admin']['Payment']['userExcluded'], $Exception_auto_cart_keyboard, 'HTML');
    update("PaySetting", "ValuePay", json_encode($list_Exceptions), "NamePay", "Exception_auto_cart");
    step("home", $from_id);
} elseif ($text == $textbotlang['keyboard']['removeUserFromList']) {
    sendmessage($from_id, $textbotlang['Admin']['Payment']['askRemoveExcludeId'], $backadmin, 'HTML');
    step("getidExceptioremove", $from_id);
} elseif ($user['step'] == "getidExceptioremove") {
    if (!rowExists("user", "id", $text)) {
        sendmessage($from_id, $textbotlang['Admin']['Payment']['userNotFound'], $backadmin, 'HTML');
        return;
    }
    $list_Exceptions = select("PaySetting", "ValuePay", "NamePay", "Exception_auto_cart", "select")['ValuePay'];
    $list_Exceptions = is_string($list_Exceptions) ? json_decode($list_Exceptions, true) : [];
    if (!in_array($text, $list_Exceptions)) {
        sendmessage($from_id, $textbotlang['Admin']['Payment']['userNotExcluded'], $backadmin, 'HTML');
        return;
    }
    $count = 0;
    foreach ($list_Exceptions as $list) {
        if ($list == $text) {
            unset($list_Exceptions[$count]);
            break;
        }
        $count += 1;
    }
    $list_Exceptions = array_values($list_Exceptions);
    sendmessage($from_id, $textbotlang['Admin']['Payment']['userExcludeRemoved'], $Exception_auto_cart_keyboard, 'HTML');
    update("PaySetting", "ValuePay", json_encode($list_Exceptions), "NamePay", "Exception_auto_cart");
    step("home", $from_id);
} elseif ($text == $textbotlang['keyboard']['showUserList']) {
    $list_Exceptions = select("PaySetting", "ValuePay", "NamePay", "Exception_auto_cart", "select")['ValuePay'];
    $list_Exceptions = is_string($list_Exceptions) ? json_decode($list_Exceptions, true) : [];
    if (count($list_Exceptions) == 0) {
        sendmessage($from_id, $textbotlang['Admin']['Payment']['excludeListEmpty'], null, 'HTML');
        return;
    }
    $list = "";
    foreach ($list_Exceptions as $list_ex) {
        $list .= $list_ex . "\n";
    }
    sendmessage($from_id, $textbotlang['Admin']['Payment']['excludeListTitle'], null, 'HTML');
    sendmessage($from_id, $list, null, 'HTML');
} elseif ($text == $textbotlang['keyboard']['setApi'] && $adminrulecheck['rule'] == "administrator") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "marchent_floypay")['ValuePay'];
    $textaqayepardakht = sprintf($textbotlang['Admin']['gateway']['askApiCode2'], $PaySetting);
    sendmessage($from_id, $textaqayepardakht, $backadmin, 'HTML');
    step('marchent_floypay', $from_id);
} elseif ($user['step'] == "marchent_floypay") {
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['saveApi'], $Swapinokey, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "marchent_floypay");
    step('home', $from_id);
} elseif ($text == $textbotlang['keyboard']['panelSetting']) {
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if (!$panel) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['notFound'], null, 'HTML');
        return;
    }
    $list_panel = get_panel_list($panel);
    if (!empty($list_panel['error'])) {
        sendmessage($from_id, panelErrorText($list_panel['error']), null, 'HTML');
        return;
    }
    if (!empty($list_panel['status']) && $list_panel['status'] != 200) {
        sendmessage($from_id, sprintf($textbotlang['Admin']['managepanel']['errorCode'], $list_panel['status']), null, 'HTML');
        return;
    }
    $list_panel = json_decode($list_panel['body'], true)['obj'] ?? [];
    $list_panel_keyboard = ['inline_keyboard' => []];
    foreach ($list_panel as $result) {
        $list_panel_keyboard['inline_keyboard'][] = [
            ['text' => $result['name_panel'], 'callback_data' => "set_panel_id_mirza:{$result['id']}:{$panel['id']}"],
        ];
    }
    sendmessage($from_id, $textbotlang['Admin']['addorder']['selectPanel'], json_encode($list_panel_keyboard), 'HTML');
} elseif (preg_match('/set_panel_id_mirza:(.*):(.*)/', $datain, $dataget)) {
    $panel_id_mirza = $dataget[1];
    $panel_id = $dataget[2];
    deletemessage($from_id, $message_id);
    update("marzban_panel", "inbounds", $panel_id_mirza, "id", $panel_id);
    sendmessage($from_id, $textbotlang['Admin']['addorder']['panelSelected'], $option_mirza, 'HTML');
} elseif ($text == $textbotlang['bottext']['open_button']) {
    $bt_home = strtr($textbotlang['bottext']['home_text'], ['{lang}' => $textbotlang['bottext']['langs'][$user['lang']] ?? $bt_lang]);
    sendmessage($from_id, $bt_home, keyboard_list_text($user['lang']), 'HTML');
} elseif ($datain == "bt_close") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['bottext']['msg_closed'], null, 'HTML');
} elseif (preg_match('/bt_lang:(.*)/', $datain, $dataget)) {
    $lang = $dataget[1];
    $bt_home = strtr($textbotlang['bottext']['home_text'], ['{lang}' => $textbotlang['bottext']['langs'][$lang] ?? $bt_lang]);
    Editmessagetext($from_id, $message_id, $bt_home, keyboard_list_text($lang));
} elseif (preg_match('/^bt_edit\|([^|]+)\|(.+)$/', $datain, $dataget)) {
    $bt_lang = $dataget[1];
    $bt_key = $dataget[2];
    if (!in_array($bt_lang, ['fa', 'en', 'ru', 'zh'], true)) {
        $bt_lang = 'fa';
    }
    sendmessage($from_id, $textbotlang['Admin']['askNewText'] . "\n\n" . $textbotlang['bottext']['reset_hint'], $backadmin, 'HTML');
    savedata("clear", "bt_lang", $bt_lang);
    savedata("save", "bt_key", $bt_key);
    step("get_new_text", $from_id);

} elseif ($user['step'] == "get_new_text") {
    $userdata = json_decode((string) ($user['Processing_value'] ?? ''), true);
    if (!is_array($userdata)) {
        $userdata = [];
    }
    $bt_lang = $userdata['bt_lang'] ?? 'fa';
    $bt_key = $userdata['bt_key'] ?? '';
    if (!in_array($bt_lang, ['fa', 'en', 'ru', 'zh'], true)) {
        $bt_lang = 'fa';
    }
    $bt_valid = false;
    foreach (($textbotlang['bottext']['items'] ?? []) as $bt_it) {
        if (($bt_it['key'] ?? '') === $bt_key) {
            $bt_valid = true;
            break;
        }
    }
    if (!$bt_valid || $bt_key === '') {
        step('home', $from_id);
        sendmessage($from_id, $textbotlang['bottext']['msg_session'], $keyboardadmin, 'HTML');
        return;
    }
    $bt_new = trim((string) $text);
    if ($bt_new === '') {
        sendmessage($from_id, $textbotlang['bottext']['msg_empty'], $backadmin, 'HTML');
        return;
    }
    $bt_map = (isset($setting['text_edit']) && $setting['text_edit']) ? json_decode($setting['text_edit'], true) : [];
    if (!is_array($bt_map)) {
        $bt_map = [];
    }
    $bt_parts = explode('.', $bt_key);
    $bt_p0 = $bt_parts[0] ?? '';
    $bt_p1 = $bt_parts[1] ?? '';
    $bt_reset = $bt_new === '0';
    if ($bt_reset) {
        unset($bt_map[$bt_lang][$bt_p0][$bt_p1]);
        if (empty($bt_map[$bt_lang][$bt_p0])) {
            unset($bt_map[$bt_lang][$bt_p0]);
        }
        if (empty($bt_map[$bt_lang])) {
            unset($bt_map[$bt_lang]);
        }
    } else {
        $bt_map[$bt_lang][$bt_p0][$bt_p1] = $bt_new;
    }
    update("setting", "text_edit", empty($bt_map) ? null : json_encode($bt_map, JSON_UNESCAPED_UNICODE), null, null);
    step('home', $from_id);
    $textbotlang = languagechange();
    $bt_home = strtr($textbotlang['bottext']['home_text'], ['{lang}' => $textbotlang['bottext']['langs'][$bt_lang] ?? $bt_lang]);
    sendmessage($from_id, $bt_reset ? $textbotlang['bottext']['msg_reset_done'] : $textbotlang['bottext']['msg_saved'], $keyboardadmin, 'HTML');
    sendmessage($from_id, $bt_home, keyboard_list_text($bt_lang), 'HTML');
}
