<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
header('Content-Type: application/json');
date_default_timezone_set('Asia/Tehran');
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');
$ManagePanel = new ManagePanel();

$setting = config_all('setting');

list($headers, $data, $action) = apiRequestContext();
$method = $_SERVER['REQUEST_METHOD'];

function pay_payments(array $data, string $method): void
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
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM payment_report WHERE id_user LIKE :id_user OR id_order LIKE :id_order");
        $stmt->execute([':id_user' => $search, ':id_order' => $search]);
        $total_record = (int) $stmt->fetchColumn();
        $totalPages = (int) ceil($total_record / $limit);

        $stmt = $pdo->prepare("SELECT id_order as id,id_user,time,price,payment_status,Payment_Method FROM payment_report WHERE id_user LIKE CONCAT('%', :id_user, '%') OR id_order LIKE CONCAT('%', :id_order, '%') ORDER BY time DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':id_user', $q, PDO::PARAM_STR);
        $stmt->bindValue(':id_order', $q, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $payment = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendJsonResponse(true, "Successful", [
            'payments' => $payment,
            'pagination' => [
                'total_record' => $total_record,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'per_page' => $limit
            ]
        ]);
    } catch (Exception $e) {
        error_log("Database error in payments: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 500);
    }
}

function pay_payment(array $data, string $method): void
{
    validateMethod('GET', $method);

    if (!isset($data['id_order']) || empty($data['id_order'])) {
        sendJsonResponse(false, "id_order empty", []);
    }

    $payment = select("payment_report", "*", "id_order", $data['id_order'], "select");
    if (!$payment) {
        sendJsonResponse(false, "payment not found", [], 200);
    }
    sendJsonResponse(true, "Successful", $payment, 200);
}

match ($action) {
    'payments' => pay_payments($data, $method),
    'payment' => pay_payment($data, $method),
    default => sendJsonResponse(false, "Action Invalid"),
};


?>