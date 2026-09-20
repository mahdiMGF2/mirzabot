<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/utils.php';
$textbotlang = languagechange();
require_once __DIR__ . '/../botapi.php';

header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('Asia/Tehran');
$otherservice = topicId('otherservice');
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');

list($headers, $data, $action) = apiRequestContext();
$method = $_SERVER['REQUEST_METHOD'];
$setting = select("setting", "*");

function usr_users(array $data, string $method): void
{
    global $pdo;

    validateMethod('GET', $method);

    $paging = paginationParams($data);
    $limit = $paging['limit'];
    $page = $paging['page'];
    $offset = $paging['offset'];
    $q = $paging['q'];

    $agent_type = '';
    $agentParams = [];
    if (isset($data['agent']) && is_scalar($data['agent']) && $data['agent'] !== '') {
        $agent_type = " AND agent = :agent";
        $agentParams[':agent'] = (string) $data['agent'];
    }

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user WHERE (id LIKE :id_user OR username LIKE :username) $agent_type");
        $search = "%$q%";
        $stmt->execute([':id_user' => $search, ':username' => $search] + $agentParams);
        $totalUsers = (int) $stmt->fetchColumn();
        $totalPages = (int) ceil($totalUsers / $limit);
        $query = "SELECT id as user_id,username,limit_usertest,roll_Status,number,Balance,User_Status,agent,affiliatescount,affiliates,cardpayment,register as time_join,verify,pricediscount,last_message_time,limit_usertest,score,joinchannel,status_cron,expire,maxbuyagent FROM user WHERE (id  LIKE CONCAT('%', :user_id, '%') OR username  LIKE CONCAT('%', :username, '%')) $agent_type ORDER BY register DESC,Balance DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':username', $q, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $q, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($agentParams as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendJsonResponse(true, "Successful", [
            'users' => $users,
            'pagination' => [
                'total_users' => $totalUsers,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'per_page' => $limit
            ]
        ]);

    } catch (Exception $e) {
        error_log("Database error in users: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 500);
    }
}

function usr_user(array $data, string $method): void
{
    global $pdo, $textbotlang;

    validateMethod('GET', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "chat_id empty", []);
    }

    try {
        $stmt = $pdo->prepare("SELECT id as user_id,username,limit_usertest,roll_Status,number,Balance,User_Status,agent,affiliatescount,affiliates,cardpayment,register as time_join,verify,pricediscount,last_message_time,limit_usertest,score,joinchannel,status_cron,expire,maxbuyagent,limitchangeloc,description_blocking FROM user WHERE id = :user_id");
        $stmt->bindValue(':user_id', intval($data['chat_id']), PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($users)) {
            sendJsonResponse(true, "Successful", [

                'users' => [],
                'pagination' => [
                    'total_users' => 1,
                    'total_pages' => 1,
                    'current_page' => 1,
                    'per_page' => 10
                ]
            ]);
        }
        $stmt = $pdo->prepare("SELECT SUM(price_product) as sum_price,COUNT(username) as count_invoice FROM invoice WHERE name_product != :name_product AND  id_user = :user_id AND Status != 'Unpaid'");
        $stmt->bindValue(':user_id', intval($users[0]['user_id']), PDO::PARAM_INT);
        $stmt->bindValue(':name_product', $textbotlang['common']['labels']['testServiceName'], PDO::PARAM_STR);
        $stmt->execute();
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        $users[0]['count_invoice'] = $invoice['count_invoice'];
        $users[0]['sum_invoice'] = $invoice['sum_price'];
        $stmt = $pdo->prepare("SELECT SUM(price) as sum_price,COUNT(*) as count_payment FROM Payment_report WHERE id_user = :user_id AND Payment_Method not in ('Unpaid','reject','expire')");
        $stmt->bindValue(':user_id', intval($users[0]['user_id']), PDO::PARAM_INT);
        $stmt->execute();
        $payment_report = $stmt->fetch(PDO::FETCH_ASSOC);
        $users[0]['count_payment'] = $payment_report['count_payment'];
        $users[0]['sum_payment'] = $payment_report['sum_price'];
        $stmt = $pdo->prepare("SELECT SUM(price) as sum_price,COUNT(*) as count_service FROM service_other WHERE id_user = :user_id AND (status = 'paid' OR status IS NULL)");
        $stmt->bindValue(':user_id', intval($users[0]['user_id']), PDO::PARAM_INT);
        $stmt->execute();
        $service_report = $stmt->fetch(PDO::FETCH_ASSOC);
        $users[0]['count_service'] = $service_report['count_service'];
        $users[0]['sum_service'] = $service_report['sum_price'];
        $bot_agent = select("botsaz", "*", "id_user", $data['chat_id'], "select");
        $list_panel = [];
        if ($bot_agent) {
            $stmt = $pdo->prepare("SELECT * FROM marzban_panel WHERE status = 'active'");
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $list_panel[] = $row['name_panel'];
            }
        }
        $users[0]['agent_bot'] = $bot_agent;
        $users[0]['panels'] = $list_panel;
        $panel = select("marzban_panel", "code_panel,name_panel", null, null, "fetchAll");
        $product = select("product", "code_product,name_product", null, null, "fetchAll");
        sendJsonResponse(true, "Successful", [

            'users' => $users,
            'panel' => $panel,
            'product' => $product,
            'pagination' => [
                'total_users' => 1,
                'total_pages' => 1,
                'current_page' => 1,
                'per_page' => 10
            ]
        ]);
    } catch (Exception $e) {
        error_log("Database error in user: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 500);
    }
}

function usr_user_add(array $data, string $method): void
{
    global $pdo, $setting;

    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 500);
    }

    try {
        $userInfo = telegram('getChat', ['chat_id' => $data['chat_id']]);

        if (!is_array($userInfo) || empty($userInfo['ok'])) {
            sendJsonResponse(false, $userInfo['description'] ?? 'Telegram API error');
        }

        $randomString = bin2hex(random_bytes(6));
        $currentTime = time();

        $verifyValue = ($setting['verifystart'] === "onverify") ? 0 : 1;

        $userData = [
            'id' => $data['chat_id'],
            'step' => 'none',
            'limit_usertest' => $setting['limit_usertest_all'],
            'User_Status' => 'Active',
            'number' => 'none',
            'Balance' => '0',
            'pagenumber' => '1',
            'username' => $userInfo['result']['username'] ?? 'none',
            'agent' => 'f',
            'message_count' => '0',
            'last_message_time' => '0',
            'affiliates' => '0',
            'affiliatescount' => '0',
            'cardpayment' => $setting['showcard'],
            'number_username' => '100',
            'namecustom' => 'none',
            'register' => $currentTime,
            'verify' => $verifyValue,
            'codeInvitation' => $randomString,
            'pricediscount' => '0',
            'maxbuyagent' => '0',
            'joinchannel' => '0',
            'score' => '0'
        ];

        $columns = implode(',', array_keys($userData));
        $placeholders = ':' . implode(', :', array_keys($userData));

        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO user ({$columns}) VALUES ({$placeholders})"
        );

        foreach ($userData as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $stmt->execute();
        sendJsonResponse(true, "Successful");

    } catch (Exception $e) {
        error_log("Error in user_add: " . $e->getMessage());
        sendJsonResponse(false, "An error occurred while adding user");
    }
}

function usr_block_user(array $data, string $method): void
{
    global $setting, $textbotlang, $otherservice;

    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 200);
    }
    if (empty($data['description'])) {
        sendJsonResponse(false, "description empty", [], 200);
    }
    if (($data['type_block'] ?? '') == "block") {
        $typeblock = "block";
        $text_report = sprintf($textbotlang['Admin']['reportgroup']['userBlockedByApi'], $data['chat_id']);
    } else {
        $text_report = sprintf($textbotlang['Admin']['reportgroup']['userUnblockedByApi'], $data['chat_id']);
        sendmessage($data['chat_id'], $textbotlang['users']['block']['unblocked'], null, 'HTML');
        $typeblock = "Active";
    }
    update("user", "description_blocking", $data['description'], "id", $data['chat_id']);
    update("user", "User_Status", $typeblock, "id", $data['chat_id']);
    sendReport($text_report, $setting['Channel_Report'], $otherservice, null);
    sendJsonResponse(true, "Successful");
}

function usr_verify_user(array $data, string $method): void
{
    global $textbotlang;

    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 500);
    }
    if (($data['type_verify'] ?? '') == "1") {
        $type_verify = "0";
    } else {
        $type_verify = "1";
        sendmessage($data['chat_id'], $textbotlang['users']['account']['verifiedNotice'], null, 'HTML');
    }
    update("user", "verify", $type_verify, "id", $data['chat_id']);
    sendJsonResponse(true, "Successful");
}

function usr_change_status_user(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 200);
    }
    $checkexits = select("user", "*", "id", $data['chat_id'], "select");
    if (!$checkexits) {
        sendJsonResponse(false, "user not found", [], 200);
    }
    if (intval($checkexits['checkstatus'] ?? 0) != 0) {
        sendJsonResponse(false, "actions exits", [], 200);
    }
    if (($data['type'] ?? '') == "active") {
        update("user", "checkstatus", "1", "id", $data['chat_id']);
    } else {
        update("user", "checkstatus", "2", "id", $data['chat_id']);
    }
    sendJsonResponse(true, "Successful");
}

function usr_add_balance(array $data, string $method): void
{
    global $pdo, $textbotlang;

    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 200);
    }
    $amount = requireInt($data, 'amount', 1);
    $stmt = $pdo->prepare("UPDATE user SET Balance = Balance + :amount WHERE id = :user_id");
    $stmt->bindValue(':user_id', intval($data['chat_id']), PDO::PARAM_INT);
    $stmt->bindValue(':amount', $amount, PDO::PARAM_INT);
    $stmt->execute();
    $text_balance = sprintf($textbotlang['users']['Balance']['added'], $amount);
    sendmessage($data['chat_id'], $text_balance, null, 'html');
    sendJsonResponse(true, "Successful");
}

function usr_withdrawal(array $data, string $method): void
{
    global $pdo, $textbotlang;

    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 200);
    }
    $amount = requireInt($data, 'amount', 1);
    $stmt = $pdo->prepare("UPDATE user SET Balance = Balance - :amount WHERE id = :user_id AND Balance >= :amount2");
    $stmt->bindValue(':user_id', intval($data['chat_id']), PDO::PARAM_INT);
    $stmt->bindValue(':amount', $amount, PDO::PARAM_INT);
    $stmt->bindValue(':amount2', $amount, PDO::PARAM_INT);
    $stmt->execute();
    clearSelectCache('user');
    if ($stmt->rowCount() === 0) {
        sendJsonResponse(false, "insufficient balance", [], 200);
    }
    $text_balance = sprintf($textbotlang['users']['Balance']['deducted'], $amount);
    sendmessage($data['chat_id'], $text_balance, null, 'html');
    sendJsonResponse(true, "Successful");
}

function usr_accept_number(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 200);
    }
    update("user", "number", "confrim number by admin", "id", $data['chat_id']);
    sendJsonResponse(true, "Successful");
}

function usr_send_message(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 200);
    }
    if (!isset($data['text']) || empty($data['text'])) {
        sendJsonResponse(false, "text empty", [], 200);
    }
    if (empty($data['file'])) {
        sendmessage($data['chat_id'], $data['text'], null, 'html');
        sendJsonResponse(true, "Successful");
    }

    if (!isset($data['content_type']) || empty($data['content_type'])) {
        sendJsonResponse(false, "content_type empty", [], 200);
    }

    $extensions = [
        'image' => 'jpg',
        'video' => 'mp4',
        'application' => 'pdf',
        'audio' => 'mp3',
    ];
    $contentType = explode('/', (string) $data['content_type'])[0];
    if (!isset($extensions[$contentType])) {
        sendJsonResponse(false, "content_type invalid", [], 200);
    }

    $decoded = base64_decode((string) $data['file'], true);
    if ($decoded === false || $decoded === '') {
        sendJsonResponse(false, "file invalid", [], 200);
    }

    $tempBase = tempnam(sys_get_temp_dir(), 'mirza_');
    if ($tempBase === false) {
        sendJsonResponse(false, "unable to store uploaded file", [], 500);
    }
    $tempFile = $tempBase . '.' . $extensions[$contentType];
    unlink($tempBase);
    if (file_put_contents($tempFile, $decoded) === false) {
        sendJsonResponse(false, "unable to store uploaded file", [], 500);
    }

    try {
        if ($contentType == "image") {
            sendphoto($data['chat_id'], new CURLFile($tempFile), $data['text']);
        } elseif ($contentType == "video") {
            sendvideo($data['chat_id'], new CURLFile($tempFile), $data['text']);
        } elseif ($contentType == "application") {
            sendDocument($data['chat_id'], $tempFile, $data['text']);
        } else {
            telegram('sendAudio', [
                'chat_id' => $data['chat_id'],
                'audio' => new CURLFile($tempFile),
                'caption' => $data['text'],
            ]);
        }
    } finally {
        if (is_file($tempFile)) {
            unlink($tempFile);
        }
    }
    sendJsonResponse(true, "Successful");
}

function usr_set_limit_test(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 200);
    }
    if (!isset($data['limit_test']) || empty($data['limit_test'])) {
        sendJsonResponse(false, "limit_test empty", [], 200);
    }
    $stmt = $pdo->prepare("UPDATE user SET limit_usertest =  :limit_test WHERE id = :user_id");
    $stmt->bindValue(':user_id', intval($data['chat_id']), PDO::PARAM_INT);
    $stmt->bindValue(':limit_test', intval($data['limit_test']), PDO::PARAM_INT);
    $stmt->execute();
    sendJsonResponse(true, "Successful");
}

function usr_transfer_account(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id']))
        sendJsonResponse(false, "user-id empty", [], 200);
    if (!isset($data['new_userid']) || empty($data['new_userid']))
        sendJsonResponse(false, "new_userid empty", [], 200);
    if ($data["chat_id"] == $data["new_userid"])
        sendJsonResponse(false, "inavlid user_id", [], 200);
    if (!ctype_digit((string) $data["new_userid"]) || !ctype_digit((string) $data["chat_id"]))
        sendJsonResponse(false, "inavlid user_id", [], 200);
    if (!rowExists("user", "id", $data['chat_id']))
        sendJsonResponse(false, "source user not found", [], 200);
    $stmt = $pdo->prepare("DELETE FROM user WHERE id = :id_user");
    $stmt->execute([':id_user' => $data["new_userid"]]);
    update("user", "id", $data["new_userid"], "id", $data['chat_id']);
    update("Payment_report", "id_user", $data["new_userid"], "id_user", $data['chat_id']);
    update("invoice", "id_user", $data["new_userid"], "id_user", $data['chat_id']);
    update("support_message", "iduser", $data["new_userid"], "iduser", $data['chat_id']);
    update("service_other", "id_user", $data["new_userid"], "id_user", $data['chat_id']);
    update("Giftcodeconsumed", "id_user", $data["new_userid"], "id_user", $data['chat_id']);
    update("botsaz", "id_user", $data["new_userid"], "id_user", $data['chat_id']);
    sendJsonResponse(true, "Successful");
}

function usr_join_channel_exception(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 500);
    }
    update("user", "joinchannel", "active", "id", $data['chat_id']);
    sendJsonResponse(true, "Successful");
}

function usr_cron_notif(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 500);
    }
    $type = ($data['type'] ?? '') == "1" ? "0" : "1";
    update("user", "status_cron", $type, "id", $data['chat_id']);
    sendJsonResponse(true, "Successful");
}

function usr_manage_show_cart(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 500);
    }
    $type = ($data['type'] ?? '') == "1" ? "0" : "1";
    update("user", "cardpayment", $type, "id", $data['chat_id']);
    sendJsonResponse(true, "Successful");
}

function usr_zero_balance(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 500);
    }
    update("user", "Balance", 0, "id", $data['chat_id']);
    sendJsonResponse(true, "Successful");
}

function usr_affiliates_users(array $data, string $method): void
{
    global $pdo;

    validateMethod('GET', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 500);
    }

    try {
        $stmt = $pdo->prepare("SELECT id as user_id FROM user WHERE affiliates = :affiliates_id");
        $stmt->bindValue(':affiliates_id', $data['chat_id']);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendJsonResponse(true, "Successful", [
            'users' => $users
        ]);

    } catch (Exception $e) {
        error_log("Database error in users: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 500);
    }
}

function usr_remove_affiliates(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 500);
    }
    update("user", "affiliates", "0", "affiliates", $data['chat_id']);
    update("user", "affiliatescount", "0", "id", $data['chat_id']);
    sendJsonResponse(true, "Successful");
}

function usr_remove_affiliate_user(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 500);
    }
    update("user", "affiliates", "0", "id", $data['chat_id']);
    sendJsonResponse(true, "Successful");
}

function usr_set_agent(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 500);
    }
    if (!isset($data['agent_type']) || !in_array($data['agent_type'], ['f', 'n', 'n2'], true)) {
        sendJsonResponse(false, "agent_type invalid", [], 200);
    }
    update("user", "agent", $data['agent_type'], "id", $data['chat_id']);
    sendJsonResponse(true, "Successful");
}

function usr_set_expire_agent(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 200);
    }
    $expireDays = requireInt($data, 'expire_time', 0);
    $timestamp = $expireDays != 0 ? time() + ($expireDays * 86400) : null;
    update("user", "expire", $timestamp, "id", $data['chat_id']);
    sendJsonResponse(true, "Successful");
}

function usr_set_becoming_negative(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 200);
    }
    $maxbuyagent = requireInt($data, 'amount', 0);
    update("user", "maxbuyagent", $maxbuyagent, "id", $data['chat_id']);
    sendJsonResponse(true, "Successful");
}

function usr_set_percentage_discount(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id'])) {
        sendJsonResponse(false, "user-id empty", [], 200);
    }
    $percentage = requireInt($data, 'percentage', 0, 100);
    update("user", "pricediscount", $percentage, "id", $data['chat_id']);
    sendJsonResponse(true, "Successful");
}

function usr_active_bot_agent(array $data, string $method): void
{
    global $pdo, $textbotlang, $domainhosts;

    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id']))
        sendJsonResponse(false, "user-id empty", [], 200);
    if (!isset($data['token']) || empty($data['token']))
        sendJsonResponse(false, "token empty", [], 200);
    $chec_kbot = select("botsaz", "*", "id_user", $data['chat_id'], "count");
    if ($chec_kbot != 0)
        sendJsonResponse(false, "You are allowed to build a robot.");
    if (!preg_match('/^\d+:[A-Za-z0-9_-]{30,}$/', (string) $data['token']))
        sendJsonResponse(false, "You are allowed! Token inavlid");
    $getInfoTokenRaw = @file_get_contents("https://api.telegram.org/bot{$data['token']}/getme");
    $getInfoToken = $getInfoTokenRaw === false ? null : json_decode($getInfoTokenRaw, true);
    if (!is_array($getInfoToken) or empty($getInfoToken['ok']) or empty($getInfoToken['result']['username']))
        sendJsonResponse(false, "You are allowed! Token inavlid");
    $botUsername = $getInfoToken['result']['username'];
    if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $botUsername))
        sendJsonResponse(false, "You are allowed! Token inavlid");
    $check_exits_token = select("botsaz", "*", "bot_token", $data['token'], "count");
    if ($check_exits_token != 0)
        sendJsonResponse(false, "You are allowed! Token exits");
    $admin_ids = json_encode(array(
        $data['chat_id']
    ));
    $data['chat_id'] = intval($data['chat_id']);
    $destination = dirname(__DIR__);
    $dirsource = "$destination/vpnbot/{$data['chat_id']}{$botUsername}";
    if (is_dir($dirsource) && !deleteDirectory($dirsource)) {
        error_log('Failed to remove existing bot directory: ' . $dirsource);
        sendJsonResponse(false, "unable to prepare bot directory", [], 500);
    }
    if (!copyDirectoryContents($destination . '/vpnbot/Default', $dirsource)) {
        error_log('Failed to copy default bot files into: ' . $dirsource);
        sendJsonResponse(false, "unable to create bot files", [], 500);
    }
    $contentconfig = file_get_contents($dirsource . "/config.php");
    if ($contentconfig === false) {
        error_log('Missing bot config template in: ' . $dirsource);
        sendJsonResponse(false, "unable to create bot files", [], 500);
    }
    $new_code = str_replace('BotTokenNew', $data['token'], $contentconfig);
    file_put_contents($dirsource . "/config.php", $new_code);
    $agent_secret = bin2hex(random_bytes(24));
    setAgentWebhook($data['token'], $data['chat_id'], $botUsername, $agent_secret);
    file_get_contents(sprintf($textbotlang['Admin']['agentbot']['activatedUrl'], $data['token'], $data['chat_id']));
    $datasetting = json_encode(array(
        "minpricetime" => 4000,
        "pricetime" => 4000,
        "minpricevolume" => 4000,
        "pricevolume" => 4000,
        "support_username" => "@support",
        "Channel_Report" => 0,
        "cart_info" => $textbotlang['users']['Balance']['cardInstruction'],
        'show_product' => true,
    ));
    $value = "{}";
    $stmt = $pdo->prepare("INSERT INTO botsaz (id_user,bot_token,admin_ids,username,time,setting,hide_panel,webhook_secret) VALUES (:id_user,:bot_token,:admin_ids,:username,:time,:setting,:hide_panel,:webhook_secret)");
    $stmt->execute([
        ':id_user' => $data['chat_id'],
        ':bot_token' => $data['token'],
        ':admin_ids' => $admin_ids,
        ':username' => $botUsername,
        ':time' => date('Y/m/d H:i:s'),
        ':setting' => $datasetting,
        ':hide_panel' => $value,
        ':webhook_secret' => $agent_secret,
    ]);
    sendJsonResponse(true, "Successful");
}

function usr_remove_agent_bot(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id']))
        sendJsonResponse(false, "user-id empty", [], 200);
    $contentbot = select("botsaz", "*", "id_user", $data['chat_id'], "select");
    if (!$contentbot)
        sendJsonResponse(false, "User does not have an active bot.", [], 200);
    $destination = dirname(__DIR__);
    $dirsource = "$destination/vpnbot/" . intval($data['chat_id']) . $contentbot['username'];
    if (is_dir($dirsource) && !deleteDirectory($dirsource)) {
        error_log('Failed to remove bot directory: ' . $dirsource);
    }
    if (!empty($contentbot['bot_token'])) {
        file_get_contents("https://api.telegram.org/bot{$contentbot['bot_token']}/deletewebhook");
    }
    $stmt = $pdo->prepare("DELETE FROM botsaz WHERE id_user = :id_user");
    $stmt->execute([':id_user' => $data['chat_id']]);
    sendJsonResponse(true, "Successful");
}

function usr_set_price_volume_agent_bot(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id']))
        sendJsonResponse(false, "user-id empty", [], 200);
    $amount = requireInt($data, 'amount', 0);
    $bot_setting = select("botsaz", "setting", "id_user", $data['chat_id'], "select");
    if (!$bot_setting)
        sendJsonResponse(false, "User does not have an active bot.", [], 200);
    $bot_info = json_decode($bot_setting['setting'] ?? '', true);
    if (!is_array($bot_info))
        $bot_info = [];
    $bot_info['minpricevolume'] = $amount;
    update("botsaz", "setting", json_encode($bot_info), "id_user", $data['chat_id']);
    sendJsonResponse(true, "Successful");
}

function usr_set_price_time_agent_bot(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id']))
        sendJsonResponse(false, "user-id empty", [], 200);
    $amount = requireInt($data, 'amount', 0);
    $bot_setting = select("botsaz", "setting", "id_user", $data['chat_id'], "select");
    if (!$bot_setting)
        sendJsonResponse(false, "User does not have an active bot.", [], 200);
    $bot_info = json_decode($bot_setting['setting'] ?? '', true);
    if (!is_array($bot_info))
        $bot_info = [];
    $bot_info['minpricetime'] = $amount;
    update("botsaz", "setting", json_encode($bot_info), "id_user", $data['chat_id']);
    sendJsonResponse(true, "Successful");
}

function usr_SetPanelAgentShow(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id']))
        sendJsonResponse(false, "user-id empty", [], 200);
    if (!isset($data['panels']) || !is_array($data['panels']))
        sendJsonResponse(false, "json invalid", [], 200);
    update("botsaz", "hide_panel", json_encode($data['panels']), "id_user", $data['chat_id']);
    sendJsonResponse(true, "Successful");
}

function usr_SetLimitChangeLocation(array $data, string $method): void
{
    validateMethod('POST', $method);

    if (!isset($data['chat_id']) || empty($data['chat_id']))
        sendJsonResponse(false, "user-id empty", [], 200);
    $limitChangeLoc = requireInt($data, 'Limit', 0);
    update("user", "limitchangeloc", $limitChangeLoc, "id", $data['chat_id']);
    sendJsonResponse(true, "Successful");
}

match ($action) {
    'users' => usr_users($data, $method),
    'user' => usr_user($data, $method),
    'user_add' => usr_user_add($data, $method),
    'block_user' => usr_block_user($data, $method),
    'verify_user' => usr_verify_user($data, $method),
    'change_status_user' => usr_change_status_user($data, $method),
    'add_balance' => usr_add_balance($data, $method),
    'withdrawal' => usr_withdrawal($data, $method),
    'accept_number' => usr_accept_number($data, $method),
    'send_message' => usr_send_message($data, $method),
    'set_limit_test' => usr_set_limit_test($data, $method),
    'transfer_account' => usr_transfer_account($data, $method),
    'join_channel_exception' => usr_join_channel_exception($data, $method),
    'cron_notif' => usr_cron_notif($data, $method),
    'manage_show_cart' => usr_manage_show_cart($data, $method),
    'zero_balance' => usr_zero_balance($data, $method),
    'affiliates_users' => usr_affiliates_users($data, $method),
    'remove_affiliates' => usr_remove_affiliates($data, $method),
    'remove_affiliate_user' => usr_remove_affiliate_user($data, $method),
    'set_agent' => usr_set_agent($data, $method),
    'set_expire_agent' => usr_set_expire_agent($data, $method),
    'set_becoming_negative' => usr_set_becoming_negative($data, $method),
    'set_percentage_discount' => usr_set_percentage_discount($data, $method),
    'active_bot_agent' => usr_active_bot_agent($data, $method),
    "remove_agent_bot" => usr_remove_agent_bot($data, $method),
    "set_price_volume_agent_bot" => usr_set_price_volume_agent_bot($data, $method),
    "set_price_time_agent_bot" => usr_set_price_time_agent_bot($data, $method),
    "SetPanelAgentShow" => usr_SetPanelAgentShow($data, $method),
    "SetLimitChangeLocation" => usr_SetLimitChangeLocation($data, $method),
    default => sendJsonResponse(false, "Action Invalid"),
};


?>