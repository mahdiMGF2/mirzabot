<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/utils.php';
$textbotlang = languagechange();
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
header('Content-Type: application/json');
date_default_timezone_set('Asia/Tehran');
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');
$ManagePanel = new ManagePanel();

$errorreport = topicId('errorreport');
$setting = config_all('setting');

list($headers, $data, $action) = apiRequestContext();
$method = $_SERVER['REQUEST_METHOD'];

function inv_invoices(array $data, string $method): void
{
    global $pdo;

    validateMethod('GET', $method);

    $paging = paginationParams($data);
    $limit = $paging['limit'];
    $page = $paging['page'];
    $offset = $paging['offset'];
    $q = $paging['q'];

    try {
        $search = "%$q%";
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM invoice WHERE (id_user LIKE :id_user OR username LIKE :username OR note LIKE :note)");
        $stmt->execute([':id_user' => $search, ':username' => $search, ':note' => $search]);
        $total_record = (int) $stmt->fetchColumn();
        $totalPages = (int) ceil($total_record / $limit);

        $stmt = $pdo->prepare("SELECT id_invoice as id, id_user, username, Service_location,name_product,Status FROM invoice WHERE ( id_user  LIKE CONCAT('%', :id_user, '%') OR username  LIKE CONCAT('%', :username, '%') OR note  LIKE CONCAT('%', :note, '%')) ORDER BY time_sell DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':id_user', $q, PDO::PARAM_STR);
        $stmt->bindValue(':username', $q, PDO::PARAM_STR);
        $stmt->bindValue(':note', $q, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendJsonResponse(true, "Successful", [
            'invoices' => $invoices,
            'pagination' => [
                'total_record' => $total_record,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'per_page' => $limit
            ]
        ]);
    } catch (Exception $e) {
        error_log("Database error in invoices: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 500);
    }
}

function inv_services(array $data, string $method): void
{
    global $pdo;

    validateMethod('GET', $method);

    $paging = paginationParams($data);
    $limit = $paging['limit'];
    $page = $paging['page'];
    $offset = $paging['offset'];
    $q = $paging['q'];

    try {
        $search = "%$q%";
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM service_other WHERE (id_user LIKE :id_user OR username LIKE :username) AND (status = 'paid' OR status IS NULL)");
        $stmt->execute([':id_user' => $search, ':username' => $search]);
        $total_record = (int) $stmt->fetchColumn();
        $totalPages = (int) ceil($total_record / $limit);

        $stmt = $pdo->prepare("SELECT * FROM service_other WHERE ( id_user  LIKE CONCAT('%', :id_user, '%') OR username  LIKE CONCAT('%', :username, '%')) AND (status = 'paid' OR status IS NULL) ORDER BY time DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':id_user', $q, PDO::PARAM_STR);
        $stmt->bindValue(':username', $q, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendJsonResponse(true, "Successful", [
            'services' => $invoices,
            'pagination' => [
                'total_record' => $total_record,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'per_page' => $limit
            ]
        ]);
    } catch (Exception $e) {
        error_log("Database error in services: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 500);
    }
}

function inv_invoice(array $data, string $method): void
{
    global $pdo, $textbotlang, $ManagePanel;

    validateMethod('GET', $method);
    if (!isset($data['id_invoice'])) {
        sendJsonResponse(false, "id_invoice empty", []);
    }

    $stmt = $pdo->prepare("SELECT id_invoice, id_user, username, Service_location, time_sell, name_product, price_product,Service_time,Service_time,Volume, Status, note, refral FROM invoice WHERE id_invoice = :id_invoice");
    $stmt->execute([':id_invoice' => $data['id_invoice']]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($invoice === false) {
        sendJsonResponse(false, "user not found", []);
    } else {
        $user_data = select("user", "*", "id", $invoice['id_user'], "select");
        if (!is_array($user_data)) {
            $user_data = ['number' => null, 'agent' => null];
        }
        $stmt = $pdo->prepare("SELECT SUM(price_product) as total_order,COUNT(username) as count_order FROM invoice WHERE name_product != :name_product AND  id_user = :user_id AND Status != 'Unpaid'");
        $stmt->bindValue(':user_id', intval($invoice['id_user']), PDO::PARAM_INT);
        $stmt->bindValue(':name_product', $textbotlang['common']['labels']['testServiceName'], PDO::PARAM_STR);
        $stmt->execute();
        $order_fince = $stmt->fetch(PDO::FETCH_ASSOC);
        $data_user = $ManagePanel->DataUser($invoice['Service_location'], $invoice['username']);
        $invoice['number'] = $user_data['number'];
        $invoice['agent'] = $user_data['agent'];
        $invoice['count_order'] = $order_fince['count_order'];
        $invoice['total_order'] = $order_fince['total_order'];
        $invoice['user_data'] = $data_user;
        sendJsonResponse(true, "Successful", $invoice);
    }
}

function inv_remove_service(array $data, string $method): void
{
    global $pdo, $ManagePanel;

    validateMethod('POST', $method);
    if (!isset($data['id_invoice'])) {
        sendJsonResponse(false, "id_invoice empty", []);
    }
    $invoice = select("invoice", "*", "id_invoice", $data['id_invoice'], "select");
    if ($invoice == false) {
        sendJsonResponse(false, "Invalid ID_INVOICE", []);
    }
    if (!isset($data['type']) || !in_array($data['type'], ['one', 'tow', 'three'], true)) {
        sendJsonResponse(false, "type empty", []);
    }
    if ($data['type'] == "one" || $data['type'] == "tow") {
        $refund = $data['type'] == "tow" ? requireInt($data, 'amount', 0) : 0;
        $claim = $pdo->prepare("UPDATE invoice SET Status = 'removebyadmin' WHERE id_invoice = ? AND (Status IS NULL OR Status != 'removebyadmin')");
        $claim->execute([$data["id_invoice"]]);
        clearSelectCache('invoice');
        $claimed = $claim->rowCount() === 1;
        if ($claimed && $refund > 0) {
            addBalance($invoice['id_user'], $refund);
        }
        $ManagePanel->RemoveUser($invoice['Service_location'], $invoice['username']);
        if (!$claimed && $refund > 0) {
            sendJsonResponse(false, "Service was already removed; refund skipped", $invoice);
        }
    } elseif ($data['type'] == "three") {
        $stmt = $pdo->prepare("DELETE  FROM invoice WHERE id_invoice = :id_invoice");
        $stmt->execute(['id_invoice' => $data['id_invoice']]);
    }
    sendJsonResponse(true, "Successful", $invoice);
}

function inv_invoice_add(array $data, string $method): void
{
    global $pdo, $setting, $textbotlang, $ManagePanel, $errorreport;

    validateMethod('POST', $method);

    requireFields($data, ['chat_id', 'username', 'code_product', 'location_code']);

    $data['username'] = strtolower($data['username']);
    if ($data['code_product'] == "customvolume") {
        if (!isset($data['time_service'], $data['volume_service']) || !is_numeric($data['time_service']) || !is_numeric($data['volume_service']))
            sendJsonResponse(false, "invalid value service time or volume", []);
        $product = [
            'name_product' => $textbotlang['common']['labels']['customService'],
            'code_product' => "customvolume",
            'Service_time' => (int) $data['time_service'],
            'price_product' => 1,
            'Volume_constraint' => (int) $data['volume_service'],
        ];
    } else {
        $product = select("product", "*", "code_product", $data['code_product'], "select");
    }
    if (!$product) {
        sendJsonResponse(false, "product not found", []);
    }
    $invoiceCount = select("invoice", "*", "username", $data['username'], "count");
    if ($invoiceCount > 0) {
        sendJsonResponse(false, "User exists in the database.", []);
    }
    $panel = select("marzban_panel", "*", "code_panel", $data['location_code'], "select");
    if (!$panel) {
        sendJsonResponse(false, "panel code not found", []);
    }
    $DataUserOut = $ManagePanel->DataUser($panel['name_panel'], $data['username']);
    if (!is_array($DataUserOut) || ($DataUserOut['status'] ?? null) == "Unsuccessful") {
        $datetimestep = strtotime("+" . $product['Service_time'] . "days");
        $datetimestep = $product['Service_time'] == 0 ? 0 : strtotime(date("Y-m-d H:i:s", $datetimestep));
        $datac = array(
            'expire' => $datetimestep,
            'data_limit' => $product['Volume_constraint'] * pow(1024, 3),
            'from_id' => $data['chat_id'],
            'username' => "",
            'type' => 'add order by admin'
        );
        $DataUserOut = $ManagePanel->createUser($panel['name_panel'], $product['code_product'], $data['username'], $datac);
        if (!is_array($DataUserOut) || ($DataUserOut['username'] ?? null) == null) {
            sendmessage($data['chat_id'], $textbotlang['Admin']['reportgroup']['errorSubscriptionCreateApi'], null, 'HTML');
            $errorDetail = json_encode(is_array($DataUserOut) ? ($DataUserOut['msg'] ?? null) : null);
            $texterros = sprintf($textbotlang['Admin']['reportgroup']['errorConfigCreatePanel'], $errorDetail, $data['chat_id'], $panel['name_panel']);
            sendReport($texterros, $setting['Channel_Report'], $errorreport);
            sendJsonResponse(false, "error in create Account", [], 200);

        }
    } else {
        $DataUserOut['configs'] = $DataUserOut['links'] ?? null;
    }
    $notifctions = json_encode(array(
        'volume' => false,
        'time' => false,
    ));

    $stmt = $pdo->prepare("INSERT IGNORE INTO invoice (id_user, id_invoice, username, time_sell, Service_location, name_product, price_product, Volume, Service_time, Status,notifctions) VALUES (:id_user, :id_invoice, :username, :time_sell, :service_location, :name_product, :price_product, :volume, :service_time, :status, :notifctions)");

    $randomString = bin2hex(random_bytes(4));

    $stmt->execute([
        ':id_user' => $data['chat_id'],
        ':id_invoice' => $randomString,
        ':username' => $data['username'],
        ':time_sell' => time(),
        ':service_location' => $panel['name_panel'],
        ':name_product' => $product['name_product'],
        ':price_product' => $product['price_product'],
        ':volume' => $product['Volume_constraint'],
        ':service_time' => $product['Service_time'],
        ':status' => 'active',
        ':notifctions' => $notifctions
    ]);

    sendJsonResponse(true, "Successful");
}

function inv_change_status_config(array $data, string $method): void
{
    global $ManagePanel;

    validateMethod('POST', $method);

    requireFields($data, ['id_invoice']);

    $invoice = select("invoice", "*", "id_invoice", $data['id_invoice'], "select");
    if (!$invoice)
        sendJsonResponse(false, "invoice  not found", [], 200);
    $DataUserOut = $ManagePanel->DataUser($invoice['Service_location'], $invoice['username']);
    $currentStatus = is_array($DataUserOut) ? ($DataUserOut['status'] ?? null) : null;
    if ($currentStatus === null || $currentStatus == "on_hold" || $currentStatus == "Unsuccessful") {
        sendJsonResponse(false, "Status config not allowed for change status config", [], 200);
    }
    $dataoutput = $ManagePanel->Change_status($invoice['username'], $invoice['Service_location']);
    if (!is_array($dataoutput) || ($dataoutput['status'] ?? null) == "Unsuccessful") {
        sendJsonResponse(false, "unsuccessful change status", [], 200);
    }
    if ($invoice['Status'] == "disablebyadmin") {
        update("invoice", "Status", "active", "id_invoice", $invoice['id_invoice']);
    } else {
        update("invoice", "Status", "disablebyadmin", "id_invoice", $invoice['id_invoice']);
    }
    sendJsonResponse(true, "Successful");
}

function inv_extend_service_admin(array $data, string $method): void
{
    global $pdo, $setting, $textbotlang, $ManagePanel, $errorreport;

    validateMethod('POST', $method);

    requireFields($data, ['id_invoice']);
    $volume_service = requireInt($data, 'volume_service', 0);
    $time_service = requireInt($data, 'time_service', 0);

    $invoice = select("invoice", "*", "id_invoice", $data['id_invoice'], "select");
    if (!$invoice)
        sendJsonResponse(false, "invoice  not found", [], 200);
    $panel = select("marzban_panel", "*", "name_panel", $invoice['Service_location'], "select");
    if (!$panel)
        sendJsonResponse(false, "Panel Not Found", [], 200);
    $extend = $ManagePanel->extend($panel['Methodextend'], $volume_service, $time_service, $invoice['username'], "custom_volume", $panel['code_panel']);
    if (!is_array($extend) || ($extend['status'] ?? false) == false) {
        $extendError = json_encode(is_array($extend) ? ($extend['msg'] ?? null) : null);
        $textreports = sprintf($textbotlang['Admin']['reportgroup']['errorRenewServiceApi'], $panel['name_panel'], $invoice['username'], $extendError);
        sendmessage($invoice['id_user'], $textbotlang['users']['extend']['genericErrorApi'], null, 'HTML');
        sendReport($textreports, $setting['Channel_Report'], $errorreport);
        sendJsonResponse(false, "Error in extend service", [[json_decode($extendError, true)]], 200);
    }
    $stmt = $pdo->prepare("INSERT IGNORE INTO service_other (id_user, username, value, type, time, price, output) VALUES (:id_user, :username, :value, :type, :time, :price, :output)");
    $stmt->execute([
        ':id_user' => $invoice['id_user'],
        ':username' => $invoice['username'],
        ':value' => $volume_service . "_" . $time_service,
        ':type' => "extend_user_by_admin",
        ':time' => date('Y/m/d H:i:s'),
        ':price' => 0,
        ':output' => json_encode($extend),
    ]);
    update("invoice", "Status", "active", "id_invoice", $data['id_invoice']);
    sendJsonResponse(true, "Successful");
}

match ($action) {
    'invoices' => inv_invoices($data, $method),
    'services' => inv_services($data, $method),
    'invoice' => inv_invoice($data, $method),
    'remove_service' => inv_remove_service($data, $method),
    'invoice_add' => inv_invoice_add($data, $method),
    'change_status_config' => inv_change_status_config($data, $method),
    'extend_service_admin' => inv_extend_service_admin($data, $method),
    default => sendJsonResponse(false, "Action Invalid"),
};


?>