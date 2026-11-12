<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../botapi.php';
header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('Asia/Tehran');
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');

$headrs = getallheaders();
$setting = config_all('setting');
if (!validateToken($headrs)) {
    sendJsonResponse(false, "token invalid", [], 403);
}

$method = $_SERVER['REQUEST_METHOD'];

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    sendJsonResponse(false, "data invalid", []);
}
$data = sanitize_recursive($data);
$action = $data['actions'] ?? '';

logApiRequest($headrs, $data, $action);

function svc_services(array $data, string $method): void
{
    global $pdo;

    validateMethod('GET', $method);

    // "limit" stays optional and unbounded by default, as callers of this
    // endpoint have always relied on getting the full list back.
    $limit = null;
    if (isset($data['limit']) && is_numeric($data['limit'])) {
        $limit = min(max((int) $data['limit'], 1), 1000);
    }

    try {
        $sql = "SELECT id,id_user,username,time,price,type,status FROM service_other";
        $stmt = $pdo->prepare($limit === null ? $sql : $sql . " LIMIT :limit");
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendJsonResponse(true, "Successful", $users);
    } catch (Exception $e) {
        error_log("Database error in services: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 500);
    }
}

match ($action) {
    'services' => svc_services($data, $method),
    default => sendJsonResponse(false, "Action Invalid"),
};

