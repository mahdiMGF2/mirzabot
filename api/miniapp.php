<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/utils.php';
$textbotlang = languagechange();
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../jdf.php';
require_once __DIR__ . '/../keyboard.php';

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Tehran');
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');
$ManagePanel = new ManagePanel();
$headers = getallheaders();
$setting = select("setting", "*");
$method = $_SERVER['REQUEST_METHOD'];
$data = null;
if ($method == "GET") {
    $data = array(
        'actions' => isset($_GET['actions']) && is_string($_GET['actions']) ? $_GET['actions'] : '',
        'user_id' => isset($_GET['user_id']) && is_numeric($_GET['user_id']) ? (int) $_GET['user_id'] : 0,
        'page' => isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int) $_GET['page'] : 1,
        'limit' => isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0 ? (int) $_GET['limit'] : 10,
        'q' => isset($_GET['q']) && is_string($_GET['q']) ? $_GET['q'] : null,
        'username' => isset($_GET['username']) && is_string($_GET['username']) ? $_GET['username'] : null,
        'id_panel' => isset($_GET['country_id']) && is_string($_GET['country_id']) ? $_GET['country_id'] : "",
        'category_id' => isset($_GET['category_id']) && is_string($_GET['category_id']) ? $_GET['category_id'] : 0,
        'time_range_day' => isset($_GET['time_range_day']) && is_string($_GET['time_range_day']) ? $_GET['time_range_day'] : 0,
        'traffic_gb' => isset($_GET['traffic_gb']) && is_string($_GET['traffic_gb']) ? $_GET['traffic_gb'] : 0,
        'time_days' => isset($_GET['time_days']) && is_string($_GET['time_days']) ? $_GET['time_days'] : 0
    );
} elseif ($method == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
}
if (!is_array($data)) {
    echo json_encode([
        'status' => false,
        'msg' => "Data invalid",
        'obj' => []
    ]);
    return;
}

$data = sanitize_recursive($data);

$authorization = (string) (headerValue($headers, 'Authorization') ?? '');
$tokencheck = preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $authorization, $bearerMatch) ? $bearerMatch[1] : '';
if ($tokencheck === '') {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'msg' => "Token invalid",
    ]);
    return;
}

// Authenticate before touching anything else: the session token, not the
// client-supplied user_id, decides whose account this request acts on.
$usercheck = select('user', "*", "token", $tokencheck, "select");
if (!$usercheck || !is_string($usercheck['token']) || !hash_equals($usercheck['token'], $tokencheck)) {
    http_response_code(403);
    echo json_encode([
        'status' => false,
        'msg' => "Token invalid",
    ]);
    return;
}
$data['user_id'] = $usercheck['id'];

if ($usercheck['User_Status'] == "block") {
    http_response_code(402);
    echo json_encode([
        'status' => false,
        'msg' => "user blocked",
    ]);
    return;
}

$errorreport = topicId('errorreport');
$porsantreport = topicId('porsantreport');
$buyreport = topicId('buyreport');
$action = $data['actions'] ?? '';

function mini_invoices(array $data, string $method): void
{
    global $pdo, $textbotlang, $ManagePanel;

    if ($method !== "GET") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be GET",
        ]);
        return;
    }
    $limit = $data['limit'];
    if ($limit > 10)
        $limit = 10;
    $page = $data['page'];
    $user_id = $data['user_id'];
    $username = $data['q'];
    $offset = ($page - 1) * $limit;
    if ($username != null) {
        $querywhere = " AND username LIKE :username";
    } else {
        $querywhere = "";
    }
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM invoice WHERE id_user = :user_id AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') $querywhere");
    $countStmt->bindValue(':user_id', $user_id);
    if ($username != null) {
        $username = "%$username%";
        $countStmt->bindValue(':username', $username, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalItems = $countStmt->fetchColumn();
    $totalPages = ceil($totalItems / $limit);
    $stmt = $pdo->prepare("SELECT username,note,Service_location FROM invoice WHERE id_user = :user_id AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') $querywhere  ORDER BY time_sell DESC LIMIT :limit OFFSET :offset ");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    if ($username != null) {
        $username = "%$username%";
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    }
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $datauser = [];
    if (is_array($invoices)) {
        foreach ($invoices as $invoice) {
            $DataUserOut = $ManagePanel->DataUser($invoice['Service_location'], $invoice['username']);
            if ($DataUserOut['status'] == "Unsuccessful") {
                $expire = $textbotlang['common']['labels']['unknown'];
            } else {
                $expire = $DataUserOut['expire'] ? jdate('Y/m/d', $DataUserOut['expire']) : $textbotlang['common']['labels']['unlimited'];
            }
            $datauser[] = [
                'username' => $invoice['username'],
                'status' => $DataUserOut['status'],
                'expire' => $expire,
                'note' => $invoice['note']
            ];
        }
    }
    echo json_encode([
        'status' => true,
        'msg' => "Successful",
        'obj' => $datauser,
        'meta' => [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit
        ]
    ]);
}

function mini_service(array $data, string $method): void
{
    global $pdo, $textbotlang, $ManagePanel;

    if ($method !== "GET") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be GET",
        ]);
        return;
    }
    $user_id = $data['user_id'];
    $username = $data['username'];
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :user_id AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold') AND username = :username");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($invoice) {
        $panel = select("marzban_panel", "*", "name_panel", $invoice['Service_location'], "select");
        if (!$panel) {
            http_response_code(404);
            echo json_encode([
                'status' => false,
                'msg' => "Panel Not Found",
                'obj' => []
            ]);
            return;
        }
        $DataUserOut = $ManagePanel->DataUser($invoice['Service_location'], $invoice['username']);
        if (!is_array($DataUserOut) || !array_key_exists('data_limit', $DataUserOut) || !array_key_exists('used_traffic', $DataUserOut)) {
            http_response_code(502);
            echo json_encode([
                'status' => false,
                'msg' => isset($DataUserOut['msg']) ? $DataUserOut['msg'] : "Service data unavailable",
                'obj' => []
            ]);
            return;
        }
        $data_limit_bytes = is_numeric($DataUserOut['data_limit']) ? (float) $DataUserOut['data_limit'] : 0;
        $used_traffic_bytes = is_numeric($DataUserOut['used_traffic']) ? (float) $DataUserOut['used_traffic'] : 0;
        $remaining_traffic_bytes = max($data_limit_bytes - $used_traffic_bytes, 0);
        $data_limit = $data_limit_bytes / pow(1024, 3);
        $used_Traffic = $used_traffic_bytes / pow(1024, 3);
        $remaining_traffic = $remaining_traffic_bytes / pow(1024, 3);
        $config = [];
        if (in_array($panel['type'], ['marzban', 'marzneshin', 'alireza_single', 'x-ui_single', 'hiddify'])) {
            if ($panel['sublink'] == "onsublink" && !empty($DataUserOut['subscription_url'])) {
                $config[] = [
                    'type' => "link",
                    'value' => $DataUserOut['subscription_url']
                ];
            }
            if ($panel['config'] == "onconfig" && !empty($DataUserOut['links'])) {
                $config[] = [
                    'type' => "config",
                    'value' => $DataUserOut['links']
                ];
            }
        } elseif ($panel['type'] == "WGDashboard") {
            $config[] = [
                'type' => "file",
                'value' => $DataUserOut['subscription_url'] ?? '',
                'filename' => $panel['inboundid'] . "_" . $invoice['id_user'] . "_" . $invoice['id_invoice'] . ".config"
            ];
        } elseif (in_array($panel['type'], ['mikrotik', 'ibsng'])) {
            $config[] = [
                'type' => "password",
                'value' => $DataUserOut['password'] ?? ''
            ];
        }
        if (isset($DataUserOut['sub_updated_at']) && $DataUserOut['sub_updated_at'] !== null) {
            $sub_updated = $DataUserOut['sub_updated_at'];
            $dateTime = new DateTime($sub_updated, new DateTimeZone('UTC'));
            $dateTime->setTimezone(new DateTimeZone('Asia/Tehran'));
            $lastupdate = jdate('Y/m/d H:i:s', $dateTime->getTimestamp());
        } else {
            $lastupdate = null;
        }
        if (($DataUserOut['online_at'] ?? null) == "online") {
            $lastonline = $textbotlang['common']['connection']['online'];
        } elseif (($DataUserOut['online_at'] ?? null) == "offline") {
            $lastonline = $textbotlang['common']['connection']['offline'];
        } else {
            if (isset($DataUserOut['online_at']) && $DataUserOut['online_at'] !== null) {
                $dateString = $DataUserOut['online_at'];
                $date = new DateTime($dateString, new DateTimeZone('UTC'));
                $date->setTimezone(new DateTimeZone('Asia/Tehran'));
                $lastonline = jdate('Y/m/d H:i:s', $date->getTimestamp());
            } else {
                $lastonline = $textbotlang['common']['connection']['notConnected'];
            }
        }
        $expireTimestamp = isset($DataUserOut['expire']) && is_numeric($DataUserOut['expire']) ? (int) $DataUserOut['expire'] : 0;
        $expirationDate = $expireTimestamp ? jdate('Y/m/d', $expireTimestamp) : $textbotlang['common']['labels']['unlimited'];
        $usernameOutput = $DataUserOut['username'] ?? $invoice['username'];
        echo json_encode([
            'status' => true,
            'msg' => "Successful",
            'obj' => array(
                'status' => $DataUserOut['status'],
                'username' => $usernameOutput,
                'product_name' => $invoice['name_product'],
                'total_traffic_gb' => round($data_limit, 2),
                'used_traffic_gb' => round($used_Traffic, 2),
                'remaining_traffic_gb' => round($remaining_traffic, 2),
                'expiration_time' => $expirationDate,
                'last_subscription_update' => $lastupdate,
                'online_at' => $lastonline,
                'service_output' => $config
            ),
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'msg' => "Service Not  Found",
            'obj' => []
        ]);
    }
}

function mini_user_info(array $data, string $method): void
{
    global $pdo, $textbotlang, $tokencheck;

    if ($method !== "GET") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be GET",
        ]);
        return;
    }
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if ($user_info) {
        if ($user_info['codeInvitation'] == null) {
            $randomString = bin2hex(random_bytes(4));
            update("user", "codeInvitation", $randomString, "id", $user_info['id']);
            $user_info['codeInvitation'] = $randomString;
        }
        if ($user_info['number'] == "none") {
            $numberphone = $textbotlang['common']['labels']['receiptNotSent'];
        } else {
            $numberphone = $user_info['number'];
        }
        if ($user_info['number'] == "confrim number by admin") {
            $numberphone = $textbotlang['common']['labels']['confirmedByAdmin'];
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE id_user = :id_user AND name_product != :name_product AND (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn' OR Status = 'send_on_hold')");
        $stmt->bindValue(':name_product', $textbotlang['common']['labels']['testServiceName'], PDO::PARAM_STR);
        $stmt->bindValue(':id_user', $user_info['id'], PDO::PARAM_INT);
        $stmt->execute();
        $countorder = (int) $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Payment_report WHERE id_user = :from_id AND payment_Status = 'paid'");
        $stmt->execute([
            ':from_id' => $user_info['id']
        ]);
        $countpayment = (int) $stmt->fetchColumn();
        $groupuser = [
            'f' => $textbotlang['common']['roles']['normal'],
            'n' => $textbotlang['common']['roles']['agent'],
            'n2' => $textbotlang['common']['roles']['advancedAgent'],
        ][$user_info['agent']];
        $userjoin = jdate('Y/m/d', $user_info['register']);
        echo json_encode([
            'status' => true,
            'msg' => "Successful",
            'obj' => [
                'codeInvitation' => $user_info['codeInvitation'],
                'balance' => $user_info['Balance'],
                'phone' => $numberphone,
                'count_order' => $countorder,
                'count_payment' => $countpayment,
                'group_type' => $groupuser,
                'time_join' => $userjoin,
                'affiliatescount' => $user_info['affiliatescount']

            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'msg' => "User Not  Found",
        ]);
    }
}

function mini_countries(array $data, string $method): void
{
    global $pdo, $setting, $textbotlang, $tokencheck;

    if ($method !== "GET") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be GET",
        ]);
        return;
    }
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if ($user_info) {
        $stmt = $pdo->prepare("SELECT * FROM marzban_panel WHERE status = 'active' AND (agent = :agent OR agent = 'all') AND type != 'Manualsale'");
        $stmt->bindParam(':agent', $user_info['agent']);
        $stmt->execute();
        $panel_list = [];
        $setting = select("setting", "*", null, null, "select");
        ;
        $is_note = false;
        if ($setting['statusnamecustom'] == 'onnamecustom')
            $is_note = true;
        if ($setting['statusnoteforf'] == "0" && $user_info['agent'] == "f")
            $is_note = false;
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (in_array(usernameMethodKey($result['MethodUsername']), ['customUsername', 'customUsernameRandom'], true)) {
                $is_username = true;
            } else {
                $is_username = false;
            }
            $statuscustomvolume = panelAgentValue($result['customvolume'], $user_info['agent']);
            if (intval($statuscustomvolume) == 1 && $result['type'] != "Manualsale") {
                $is_custom = true;
            } else {
                $is_custom = false;
            }
            $hidden_users = json_decode($result['hide_user'] ?? '', true);
            if (is_array($hidden_users) && in_array($user_info['id'], $hidden_users))
                continue;
            $panel_list[] = [
                'id' => $result['code_panel'],
                'name' => $result['name_panel'],
                'is_custom' => $is_custom,
                'is_username' => $is_username,
                'is_note' => $is_note
            ];
        }
        echo json_encode([
            'status' => true,
            'msg' => "Successful",
            'obj' => $panel_list
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'msg' => "User Not  Found",
        ]);
    }
}

function mini_categories(array $data, string $method): void
{
    global $pdo, $setting, $tokencheck;

    if ($method !== "GET") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be GET",
        ]);
        return;
    }
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if ($user_info) {
        $setting = select("setting", "*", null, null, "select");
        if ($setting['statuscategorygenral'] == "offcategorys") {
            echo json_encode(array(
                'status' => true,
                'msg' => "Successful",
                'obj' => []
            ));
            return;
        }
        $stmt = $pdo->prepare("SELECT * FROM category");
        $stmt->execute();
        $category_list = [];
        $panel = select("marzban_panel", "*", "code_panel", $data['id_panel'], "select");
        if (empty($panel)) {
            echo json_encode(array(
                'status' => false,
                'msg' => "panel not fonud!(invalid id_panel)"
            ));
            return;
        }
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stmts = $pdo->prepare("SELECT * FROM product WHERE (Location = :location OR Location = '/all') AND category = :category AND agent = :agent");
            $stmts->bindParam(':location', $panel['name_panel'], PDO::PARAM_STR);
            $stmts->bindParam(':category', $result['remark'], PDO::PARAM_STR);
            $stmts->bindParam(':agent', $user_info['agent']);
            $stmts->execute();
            if ($stmts->rowCount() == 0)
                continue;
            $category_list[] = [
                'id' => $result['id'],
                'name' => $result['remark'],
            ];
        }
        echo json_encode([
            'status' => true,
            'msg' => "Successful",
            'obj' => $category_list
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'msg' => "User Not  Found",
        ]);
    }
}

function mini_time_ranges(array $data, string $method): void
{
    global $pdo, $setting, $textbotlang, $tokencheck;

    if ($method !== "GET") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be GET",
        ]);
        return;
    }
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if ($user_info) {
        $setting = select("setting", "*", null, null, "select");
        if ($setting['statuscategory'] == "offcategory") {
            echo json_encode(array(
                'status' => true,
                'msg' => "Successful",
                'obj' => []
            ));
            return;
        }
        $category_time_list = [];
        $panel = select("marzban_panel", "*", "code_panel", $data['id_panel'], "select");
        if (empty($panel)) {
            echo json_encode(array(
                'status' => false,
                'msg' => "panel not fonud!(invalid id_panel)"
            ));
            return;
        }
        $stmt = $pdo->prepare("SELECT (Service_time) FROM product WHERE (Location = :name_panel OR Location = '/all') AND  agent = :agent");
        $stmt->bindValue(':agent', $user_info['agent'], PDO::PARAM_STR);
        $stmt->bindValue(':name_panel', $panel['name_panel'], PDO::PARAM_STR);
        $stmt->execute();
        $montheproduct = array_flip(array_flip($stmt->fetchAll(PDO::FETCH_COLUMN)));
        if (in_array("1", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['1day'],
                'day' => 1
            );
        }
        if (in_array("7", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['7day'],
                'day' => 7
            );
        }
        if (in_array("31", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['1'],
                'day' => 31
            );
        }
        if (in_array("30", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['1'],
                'day' => 30
            );
        }
        if (in_array("61", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['2'],
                'day' => 61
            );
        }
        if (in_array("60", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['2'],
                'day' => 60
            );
        }
        if (in_array("91", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['3'],
                'day' => 91
            );
        }
        if (in_array("90", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['3'],
                'day' => 90
            );
        }
        if (in_array("121", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['4'],
                'day' => 121
            );
        }
        if (in_array("120", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['4'],
                'day' => 120
            );
        }
        if (in_array("181", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['6'],
                'day' => 181
            );
        }
        if (in_array("180", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['6'],
                'day' => 180
            );
        }
        if (in_array("365", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['365'],
                'day' => 365
            );
        }
        if (in_array("0", $montheproduct)) {
            $category_time_list[] = array(
                'id' => 0,
                'name' => $textbotlang['common']['duration']['byVolume'],
                'day' => 0
            );
        }
        echo json_encode([
            'status' => true,
            'msg' => "Successful",
            'obj' => $category_time_list
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'msg' => "User Not  Found",
        ]);
    }
}

function mini_services(array $data, string $method): void
{
    global $pdo, $tokencheck;

    if ($method !== "GET") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be GET",
        ]);
        return;
    }
    $product_list = [];
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if ($user_info) {
        $panel = select("marzban_panel", "*", "code_panel", $data['id_panel'], "select");
        if (empty($panel)) {
            echo json_encode(array(
                'status' => false,
                'msg' => "panel not fonud!(invalid id_panel)"
            ));
            return;
        }
        $category_remark = null;
        $category_remarks = "";
        $queryParams = [':loc' => $panel['name_panel'], ':ag' => $user_info['agent']];
        $selected_category_id = isset($data['category_id']) ? $data['category_id'] : null;
        if (!empty($data['category_id'])) {
            $category_remark = select("category", "*", "id", $data['category_id'], "select");
            if (!is_array($category_remark) || !isset($category_remark['remark'])) {
                echo json_encode([
                    'status' => false,
                    'msg' => "category not found!(invalid category_id)",
                ]);
                return;
            }
            $category_remarks = "AND category = :catrem";
            $queryParams[':catrem'] = $category_remark['remark'];
            $selected_category_id = $category_remark['id'];
        }
        $time_range_day = "";
        if ($data['time_range_day'] != 0) {
            $time_range_day = "AND Service_time = :trd";
            $queryParams[':trd'] = $data['time_range_day'];
        }
        $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :loc OR Location = '/all')AND agent= :ag $category_remarks $time_range_day");
        $stmt->execute($queryParams);
        $product_list = [];
        $countorder = null;
        while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $hide_panel = json_decode($result['hide_panel'], true);
            if (!is_array($hide_panel)) {
                $hide_panel = [];
            }
            if (in_array($panel['name_panel'], $hide_panel))
                continue;
            if ($result['one_buy_status'] == "1") {
                if ($countorder === null) {
                    $stmts2 = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE Status != 'Unpaid' AND id_user = :mp4");
                    $stmts2->execute([':mp4' => $user_info['id']]);
                    $countorder = (int) $stmts2->fetchColumn();
                }
                if ($countorder != 0)
                    continue;
            }
            if (intval($user_info['pricediscount']) != 0) {
                $resultper = ($result['price_product'] * $user_info['pricediscount']) / 100;
                $result['price_product'] = $result['price_product'] - $resultper;
            }
            $product_list[] = [
                'id' => $result['code_product'],
                'name' => $result['name_product'],
                'description' => $result['note'],
                'price' => $result['price_product'],
                'traffic_gb' => $result['Volume_constraint'],
                'time_days' => intval($result['Service_time']),
                'category_id' => $selected_category_id,
                'country_id' => $panel['code_panel'],
                'time_range_id' => $result['Service_time']

            ];
        }
        echo json_encode([
            'status' => true,
            'msg' => "Successful",
            'obj' => $product_list
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'msg' => "User Not  Found",
        ]);
    }
}

function mini_custom_price(array $data, string $method): void
{
    global $tokencheck;

    if ($method !== "GET") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be GET",
        ]);
        return;
    }
    $user_info = select("user", "*", "token", $tokencheck, "select");
    if ($user_info) {
        $panel = select("marzban_panel", "*", "code_panel", $data['id_panel'], "select");
        if (empty($panel)) {
            echo json_encode(array(
                'status' => false,
                'msg' => "panel not fonud!(invalid id_panel)"
            ));
            return;
        }
        $agentKey = $user_info['agent'];
        $statuscustomvolume = panelAgentValue($panel['customvolume'], $agentKey);
        $mainvolume = panelAgentValue($panel['mainvolume'], $agentKey);
        $maxvolume = panelAgentValue($panel['maxvolume'], $agentKey);
        $maintime = panelAgentValue($panel['maintime'], $agentKey);
        $maxtime = panelAgentValue($panel['maxtime'], $agentKey);
        $traffic_price = panelAgentValue($panel['pricecustomvolume'], $agentKey);
        $time_price = panelAgentValue($panel['pricecustomtime'], $agentKey);
        if (intval($statuscustomvolume) == 1 && $panel['type'] != "Manualsale") {
            $price = ($traffic_price * intval($data['traffic_gb'])) + ($time_price * intval($data['time_days']));
        } else {
            $price = false;
        }
        echo json_encode([
            'status' => true,
            'msg' => "Successful",
            'obj' => array(
                'price' => $price,
                'traffic_min' => intval($mainvolume),
                'traffic_max' => intval($maxvolume),
                'time_min' => intval($maintime),
                'time_max' => intval($maxtime)
            )
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'status' => false,
            'msg' => "User Not  Found",
        ]);
    }
}

function mini_purchase(array $data, string $method): void
{
    global $pdo, $setting, $textbotlang, $ManagePanel, $errorreport, $porsantreport, $buyreport, $usercheck;

    if ($method !== "POST") {
        echo json_encode([
            'status' => false,
            'msg' => "Method invalid; must be POST",
        ]);
        return;
    }
    $panel = select("marzban_panel", "*", "code_panel", $data['country_id'] ?? '', "select");
    if (empty($panel)) {
        http_response_code(500);
        echo json_encode(array(
            'status' => false,
            'msg' => $textbotlang['users']['sell']['panelMissing']
        ));
        return;
    }
    if ($panel['status'] == "disable") {
        http_response_code(500);
        echo json_encode(array(
            'status' => false,
            'msg' => $textbotlang['users']['sell']['panelInactive']
        ));
        return;
    }
    $user_info = $usercheck;
    if (empty($data['custom_service'])) {
        $product = select("product", "*", "code_product", $data['service_id'] ?? '', "select");
        if (!empty($product)) {
            $productLocation = $product['Location'] ?? '';
            $allowedLocation = ($productLocation === $panel['name_panel'] || $productLocation === '/all');
            $hide_panel = json_decode($product['hide_panel'] ?? '', true);
            if (!is_array($hide_panel)) {
                $hide_panel = [];
            }
            $blocked = !$allowedLocation
                || ($product['agent'] ?? null) !== $user_info['agent']
                || in_array($panel['name_panel'], $hide_panel);
            if (!$blocked && ($product['one_buy_status'] ?? null) == "1") {
                $stmtOneBuy = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE Status != 'Unpaid' AND id_user = :uid");
                $stmtOneBuy->execute([':uid' => $user_info['id']]);
                if ((int) $stmtOneBuy->fetchColumn() != 0) {
                    $blocked = true;
                }
            }
            if ($blocked) {
                http_response_code(403);
                echo json_encode(array(
                    'status' => false,
                    'msg' => $textbotlang['users']['sell']['productNotFound']
                ));
                return;
            }
        }
    } else {
        $agentKey = $user_info['agent'];
        if (intval(panelAgentValue($panel['customvolume'], $agentKey)) !== 1 || $panel['type'] == "Manualsale") {
            http_response_code(403);
            echo json_encode(array(
                'status' => false,
                'msg' => $textbotlang['users']['sell']['invalidVolumeRestart']
            ));
            return;
        }
        $mainvolume = panelAgentValue($panel['mainvolume'], $agentKey);
        $maxvolume = panelAgentValue($panel['maxvolume'], $agentKey);
        $maintime = panelAgentValue($panel['maintime'], $agentKey);
        $maxtime = panelAgentValue($panel['maxtime'], $agentKey);
        $custompricevalue = panelAgentValue($panel['pricecustomvolume'], $agentKey);
        $customtimevalueprice = panelAgentValue($panel['pricecustomtime'], $agentKey);
        $customsrvice = $data['custom_service'];
        if (!is_array($customsrvice) || !isset($customsrvice['traffic_gb'], $customsrvice['time_days'])
            || !is_numeric($customsrvice['traffic_gb']) || !is_numeric($customsrvice['time_days'])) {
            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => $textbotlang['users']['sell']['invalidVolumeRestart']
            ));
            return;
        }
        $product = array(
            'code_product' => "customvolume",
            'name_product' => $textbotlang['users']['customSellVolume']['title'],
            'Volume_constraint' => (int) $customsrvice['traffic_gb'],
            'Service_time' => (int) $customsrvice['time_days'],
            'Location' => $panel['name_panel'],
            'category' => null,
            'price_product' => ((int) $customsrvice['traffic_gb'] * $custompricevalue) + ((int) $customsrvice['time_days'] * $customtimevalueprice)
        );
        if (intval($product['Volume_constraint']) > $maxvolume or intval($product['Volume_constraint']) < $mainvolume) {
            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => $textbotlang['users']['sell']['invalidVolumeRestart']
            ));
            return;
        }
        if (intval($product['Service_time']) > $maxtime or intval($product['Service_time']) < $maintime) {
            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => $textbotlang['users']['sell']['invalidTimeRestart']
            ));
            return;
        }
        if ($product['price_product'] <= 0) {
            http_response_code(403);
            echo json_encode(array(
                'status' => false,
                'msg' => $textbotlang['users']['sell']['invalidVolumeRestart']
            ));
            return;
        }
    }
    if (empty($product)) {
        http_response_code(500);
        echo json_encode(array(
            'status' => false,
            'msg' => $textbotlang['users']['sell']['productNotFound']
        ));
        return;
    }
    if (intval($user_info['pricediscount']) != 0) {
        $result = ($product['price_product'] * $user_info['pricediscount']) / 100;
        $product['price_product'] = $product['price_product'] - $result;
        sendmessage($user_info['id'], sprintf($textbotlang['users']['Discount']['discountapplied'], $user_info['pricediscount']), null, 'HTML');
    }
    $price_product = $product['price_product'];

    $charged = false;
    if ($price_product > 0) {
        if ($user_info['agent'] == "n2") {
            $min_balance = intval($user_info['maxbuyagent']) != 0 ? -intval($user_info['maxbuyagent']) : PHP_INT_MIN;
            $error_msg = $textbotlang['users']['Balance']['maxpurchasereached'];
        } else {
            $min_balance = 0;
            $error_msg = $textbotlang['users']['Balance']['lessThanPrice'];
        }
        $stmt = $pdo->prepare("UPDATE user SET Balance = Balance - :price WHERE id = :id AND Balance - :price_check >= :min_balance");
        $stmt->execute([':price' => $price_product, ':id' => $user_info['id'], ':price_check' => $price_product, ':min_balance' => $min_balance]);
        if ($stmt->rowCount() === 0) {
            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => $error_msg
            ));
            return;
        }
        $charged = true;
    }

    $randomString = bin2hex(random_bytes(4));

    $rolledBack = false;
    $refundOnFailure = function () use ($pdo, $user_info, $price_product, $randomString, &$charged, &$rolledBack) {
        if ($rolledBack) {
            return;
        }
        $rolledBack = true;

        try {
            $stmt = $pdo->prepare("DELETE FROM invoice WHERE id_invoice = :id_invoice");
            $stmt->execute([':id_invoice' => $randomString]);
        } catch (Exception $e) {
            error_log("Failed to roll back invoice {$randomString}: " . $e->getMessage());
        }

        if (!$charged) {
            return;
        }

        try {
            $stmt = $pdo->prepare("UPDATE user SET Balance = Balance + :price WHERE id = :id");
            $stmt->execute([':price' => $price_product, ':id' => $user_info['id']]);
            $charged = false;
        } catch (Exception $e) {
            error_log("Failed to refund user {$user_info['id']}: " . $e->getMessage());
        }
    };

    try {
        $username_ac = generateUsername($user_info['id'], $panel['MethodUsername'], $user_info['username'], $randomString, $data['custom_username'] ?? null, $panel['namecustom'], $user_info['namecustom']);
        $username_ac = strtolower($username_ac);
        $DataUserOut = $ManagePanel->DataUser($panel['name_panel'], $username_ac);
        if (isset($DataUserOut['username']) || rowExists("invoice", "username", $username_ac)) {
            $refundOnFailure();
            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => $textbotlang['users']['sell']['usernameExists']
            ));
            return;
        }
        $notifctions = json_encode(array(
            'volume' => false,
            'time' => false,
        ));
        $stmt = $pdo->prepare("INSERT IGNORE INTO invoice (id_user, id_invoice, username,time_sell, Service_location, name_product, price_product, Volume, Service_time,Status,note,refral,notifctions) VALUES (?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?)");
        $Status = "active";
        $date = time();
        $custom_note = isset($data['custom_note']) && strlen(strval($data['custom_note'])) > 1 ? $data['custom_note'] : null;
        $stmt->execute([$user_info['id'], $randomString, $username_ac, $date, $panel['name_panel'], $product['name_product'], $price_product, $product['Volume_constraint'], $product['Service_time'], $Status, $custom_note, $user_info['affiliates'], $notifctions]);
        $datetimestep = strtotime("+" . $product['Service_time'] . "days");
        if ($product['Service_time'] == 0) {
            $datetimestep = 0;
        } else {
            $datetimestep = strtotime(date("Y-m-d H:i:s", $datetimestep));
        }
        $datac = array(
            'expire' => $datetimestep,
            'data_limit' => $product['Volume_constraint'] * pow(1024, 3),
            'from_id' => $user_info['id'],
            'username' => $user_info['username'],
            'type' => 'buy'
        );
        $dataoutput = $ManagePanel->createUser($panel['name_panel'], $product['code_product'], $username_ac, $datac);
        if (!is_array($dataoutput) || ($dataoutput['username'] ?? null) == null) {
            $refundOnFailure();

            http_response_code(500);
            echo json_encode(array(
                'status' => false,
                'msg' => $textbotlang['users']['sell']['subscriptionError']
            ));

            $errorDetail = json_encode(is_array($dataoutput) ? ($dataoutput['msg'] ?? null) : null);
            $texterros = sprintf($textbotlang['Admin']['reportgroup']['errorSubscriptionCreateAdmin'], $errorDetail, $user_info['id'], $user_info['username'], $panel['name_panel']);
            sendReport($texterros, $setting['Channel_Report'], $errorreport);
            return;
        }
    } catch (Throwable $e) {
        error_log("Purchase failed for user {$user_info['id']}: " . $e->getMessage());
        $refundOnFailure();

        http_response_code(500);
        echo json_encode(array(
            'status' => false,
            'msg' => $textbotlang['users']['sell']['subscriptionError']
        ));
        return;
    }

    $config = "";
    $output_config_link = $panel['sublink'] == "onsublink" ? ($dataoutput['subscription_url'] ?? "") : "";
    if ($panel['config'] == "onconfig" && is_array($dataoutput['configs'] ?? null)) {
        foreach ($dataoutput['configs'] as $link) {
            $config .= "\n" . $link;
        }
    }
    $textbotlang['textbot']['afterPay'] = $panel['type'] == "Manualsale" ? $textbotlang['textbot']['manual'] : $textbotlang['textbot']['afterPay'];
    $textbotlang['textbot']['afterPay'] = $panel['type'] == "WGDashboard" ? $textbotlang['textbot']['wgDashboard'] : $textbotlang['textbot']['afterPay'];
    $textbotlang['textbot']['afterPay'] = $panel['type'] == "ibsng" || $panel['type'] == "mikrotik" ? $textbotlang['textbot']['afterPayIbsng'] : $textbotlang['textbot']['afterPay'];
    if (intval($product['Service_time']) == 0)
        $product['Service_time'] = $textbotlang['users']['status']['unlimited'];
    if (intval($product['Volume_constraint']) == 0)
        $product['Volume_constraint'] = $textbotlang['users']['status']['unlimited'];
    $textcreatuser = str_replace('{username}', "<code>{$dataoutput['username']}</code>", $textbotlang['textbot']['afterPay']);
    $textcreatuser = str_replace('{name_service}', $product['name_product'], $textcreatuser);
    $textcreatuser = str_replace('{location}', $panel['name_panel'], $textcreatuser);
    $textcreatuser = str_replace('{day}', $product['Service_time'], $textcreatuser);
    $textcreatuser = str_replace('{volume}', $product['Volume_constraint'], $textcreatuser);
    $textcreatuser = str_replace('{config}', "<code>{$output_config_link}</code>", $textcreatuser);
    $textcreatuser = str_replace('{links}', $config, $textcreatuser);
    $textcreatuser = str_replace('{links2}', $output_config_link, $textcreatuser);
    sendMessageService($panel, $dataoutput['configs'] ?? null, $output_config_link, $user_info['username'], null, $textcreatuser, $randomString, $user_info['id'], __DIR__ . '/../images.jpg');
    if (in_array(usernameMethodKey($panel['MethodUsername']), ['customTextSequential', 'usernameSequential', 'numericIdSequential', 'agentCustomTextSequential'], true)) {
        $value = intval($user_info['number_username']) + 1;
        update("user", "number_username", $value, "id", $user_info['id']);
        if (in_array(usernameMethodKey($panel['MethodUsername']), ['customTextSequential', 'agentCustomTextSequential'], true)) {
            $value = intval($setting['numbercount']) + 1;
            update("setting", "numbercount", $value);
        }
    }
    $affiliatescommission = select("affiliates", "*", null, null, "select");
    $marzbanporsant_one_buy = select("affiliates", "*", null, null, "select");
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE name_product != :name_product AND id_user = :id_user");
    $stmt->bindParam(':id_user', $user_info['id']);
    $stmt->bindParam(':name_product', $textbotlang['common']['labels']['testServiceName']);
    $stmt->execute();
    $countinvoice = (int) $stmt->fetchColumn();
    if ($affiliatescommission['status_commission'] == "oncommission" && ($user_info['affiliates'] != null && intval($user_info['affiliates']) != 0)) {
        if ($marzbanporsant_one_buy['porsant_one_buy'] == "on_buy_porsant") {
            if ($countinvoice == 1) {
                $result = ($product['price_product'] * $setting['affiliatespercentage']) / 100;
                $user_Balance = select("user", "*", "id", $user_info['affiliates'], "select");
                $Balance_prim = $user_Balance['Balance'] + $result;
                if (intval($setting['scorestatus']) == 1) {
                    sendmessage($user_info['affiliates'], $textbotlang['users']['affiliates']['pointsEarned2'], null, 'html');
                    $scorenew = $user_Balance['score'] + 2;
                    update("user", "score", $scorenew, "id", $user_info['affiliates']);
                }
                update("user", "Balance", $Balance_prim, "id", $user_info['affiliates']);
                $result = number_format($result);
                $dateacc = date('Y/m/d H:i:s');
                $textadd = sprintf($textbotlang['users']['affiliates']['commissionPaidMiniapp'], $result);
                $textreportport = sprintf($textbotlang['Admin']['reportgroup']['commissionPaidMiniapp'], $result, $user_info['affiliates'], $user_info['id'], $dateacc);
                if (strlen($setting['Channel_Report']) > 0) {
                    telegram('sendmessage', [
                        'chat_id' => $setting['Channel_Report'],
                        'message_thread_id' => $porsantreport,
                        'text' => $textreportport,
                        'parse_mode' => "HTML"
                    ]);
                }
                sendmessage($user_info['affiliates'], $textadd, null, 'HTML');
            } else {

                $result = ($product['price_product'] * $setting['affiliatespercentage']) / 100;
                $user_Balance = select("user", "*", "id", $user_info['affiliates'], "select");
                $Balance_prim = $user_Balance['Balance'] + $result;
                if (intval($setting['scorestatus']) == 1) {
                    sendmessage($user_info['affiliates'], $textbotlang['users']['affiliates']['pointsEarned2b'], null, 'html');
                    $scorenew = $user_Balance['score'] + 2;
                    update("user", "score", $scorenew, "id", $user_info['affiliates']);
                }
                update("user", "Balance", $Balance_prim, "id", $user_info['affiliates']);
                $result = number_format($result);
                $dateacc = date('Y/m/d H:i:s');
                $textadd = sprintf($textbotlang['users']['affiliates']['commissionPaidMiniapp2'], $result);
                $textreportport = sprintf($textbotlang['Admin']['reportgroup']['commissionPaidMiniapp2'], $result, $user_info['affiliates'], $user_info['id'], $dateacc);
                if (strlen($setting['Channel_Report']) > 0) {
                    telegram('sendmessage', [
                        'chat_id' => $setting['Channel_Report'],
                        'message_thread_id' => $porsantreport,
                        'text' => $textreportport,
                        'parse_mode' => "HTML"
                    ]);
                }
                sendmessage($user_info['affiliates'], $textadd, null, 'HTML');
            }
        }
    }
    if (intval($setting['scorestatus']) == 1) {
        sendmessage($user_info['id'], $textbotlang['users']['affiliates']['pointsEarned1'], null, 'html');
        $scorenew = $user_info['score'] + 1;
        update("user", "score", $scorenew, "id", $user_info['id']);
    }
    $balanceformatsell = number_format(select("user", "Balance", "id", $user_info['id'], "select")['Balance'], 0);
    $textonebuy = "";
    if ($countinvoice == 1) {
        $textonebuy = $textbotlang['common']['labels']['firstPurchase'];
    }
    $balanceformatsellbefore = number_format($user_info['Balance'], 0);
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['manageUser']['manageUserBtn'], 'callback_data' => 'manageuser_' . $user_info['id']],
            ],
        ]
    ]);
    $timejalali = jdate('Y/m/d H:i:s');
    $text_report = sprintf($textbotlang['Admin']['reportgroup']['accountCreatedMiniapp'], $textonebuy, $user_info['id'], $user_info['username'], $username_ac, $panel['name_panel'], $product['name_product'], $product['Service_time'], $product['Volume_constraint'], $balanceformatsellbefore, $balanceformatsell, $randomString, $user_info['agent'], $user_info['number'], $product['category'], $product['price_product'], $timejalali);
    if (strlen($setting['Channel_Report']) > 0) {
        telegram('sendmessage', [
            'chat_id' => $setting['Channel_Report'],
            'message_thread_id' => $buyreport,
            'text' => $text_report,
            'parse_mode' => "HTML",
            'reply_markup' => $Response
        ]);
    }
    echo json_encode(array(
        'success' => true,
        "message" => "ok",
        "order_id" => $randomString,
        "service" => array(
            'id' => $randomString,
            "username" => $username_ac,
            "status" => "active",
            "expire" => $datetimestep
        )
    ));
}

match ($action) {
    'invoices' => mini_invoices($data, $method),
    'service' => mini_service($data, $method),
    'user_info' => mini_user_info($data, $method),
    'countries' => mini_countries($data, $method),
    'categories' => mini_categories($data, $method),
    'time_ranges' => mini_time_ranges($data, $method),
    'services' => mini_services($data, $method),
    'custom_price' => mini_custom_price($data, $method),
    'purchase' => mini_purchase($data, $method),
    default => sendJsonResponse(false, "Action Invalid", []),
};

