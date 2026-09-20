<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';

if (!function_exists('getallheaders')) {
    function getallheaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) !== 'HTTP_') {
                continue;
            }
            $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$headerName] = $value;
        }
        return $headers;
    }
}

function sendJsonResponse($status, $message, $data = [], $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'msg' => $message,
        'obj' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function sendReport($text, $groupid, $topic_id, $reply_markup = null)
{
    if (strlen((string) $groupid) > 0) {
        telegram('sendmessage', [
            'chat_id' => $groupid,
            'message_thread_id' => $topic_id,
            'text' => $text,
            'parse_mode' => "HTML",
            'reply_markup' => $reply_markup
        ]);
    }
}

function headerValue($headers, $name)
{
    if (!is_array($headers)) {
        return null;
    }

    foreach ($headers as $key => $value) {
        if (strcasecmp($key, $name) === 0) {
            return is_array($value) ? reset($value) : $value;
        }
    }

    return null;
}

function apiTokens()
{
    global $APIKEY;

    $tokens = [];

    $hashFile = __DIR__ . '/hash.txt';
    if (is_file($hashFile)) {
        $fileToken = trim((string) file_get_contents($hashFile));
        if ($fileToken !== '') {
            $tokens[] = $fileToken;
        }
    }

    if (empty($tokens) && isset($APIKEY) && $APIKEY !== '') {
        $tokens[] = (string) $APIKEY;
    }

    return $tokens;
}

function validateToken($headers)
{
    $provided = headerValue($headers, 'Token');
    if ($provided === null) {
        return false;
    }

    $provided = trim((string) $provided);
    foreach (apiTokens() as $validToken) {
        if (hash_equals($validToken, $provided)) {
            return true;
        }
    }

    return false;
}

function requireApiToken($headers)
{
    if (!validateToken($headers)) {
        sendJsonResponse(false, "token invalid", [], 403);
    }
}

function hasAdminSession()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['admin_user'])) {
        return false;
    }

    try {
        $admin = select("admin", "id_admin", "username", $_SESSION['admin_user'], "select");
    } catch (Exception $e) {
        error_log("Admin session check failed: " . $e->getMessage());
        return false;
    }

    return is_array($admin) && isset($admin['id_admin']);
}

function requireApiTokenOrAdminSession($headers)
{
    if (validateToken($headers) || hasAdminSession()) {
        return;
    }

    sendJsonResponse(false, "token invalid", [], 403);
}

function sanitizeRecursive($data)
{
    if (is_array($data)) {
        return array_map('sanitizeRecursive', $data);
    }

    return is_string($data) ? htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8') : $data;
}

function validateMethod($expected, $actual)
{
    if (strtoupper($expected) !== strtoupper((string) $actual)) {
        sendJsonResponse(false, "method invalid; method must be {$expected}", [], 405);
    }
}

function readJsonBody()
{
    $raw = file_get_contents("php://input");
    $data = $raw === false ? null : json_decode($raw, true);

    if (!is_array($data)) {
        sendJsonResponse(false, "data invalid", []);
    }

    return sanitizeRecursive($data);
}

function logApiRequest($headers, $data, $action)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO logs_api (header, data, time, ip, actions) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            json_encode($headers),
            json_encode($data),
            date('Y/m/d H:i:s'),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $action
        ]);
    } catch (Exception $e) {
        error_log("API logging error: " . $e->getMessage());
    }
}

function apiRequestContext()
{
    $headers = getallheaders();
    requireApiToken($headers);

    $data = readJsonBody();
    $action = isset($data['actions']) && is_scalar($data['actions']) ? (string) $data['actions'] : '';

    logApiRequest($headers, $data, $action === '' ? 'unknown' : $action);

    return [$headers, $data, $action];
}

function panelAgentValue($json, $agent, $default = 0)
{
    $decoded = json_decode((string) $json, true);
    if (!is_array($decoded) || !array_key_exists($agent, $decoded)) {
        return $default;
    }

    return $decoded[$agent];
}

function topicId($name)
{
    $row = select("topicid", "idreport", "report", $name, "select");

    return is_array($row) && isset($row['idreport']) ? $row['idreport'] : null;
}

function paginationParams(array $data, $defaultLimit = 50, $maxLimit = 1000)
{
    $limit = $defaultLimit;
    if (isset($data['limit']) && is_numeric($data['limit'])) {
        $limit = min(max((int) $data['limit'], 1), $maxLimit);
    }

    $page = isset($data['page']) && is_numeric($data['page']) ? max((int) $data['page'], 1) : 1;

    return [
        'limit' => $limit,
        'page' => $page,
        'offset' => ($page - 1) * $limit,
        'q' => isset($data['q']) && is_scalar($data['q']) ? (string) $data['q'] : '',
    ];
}

function requireFields(array $data, array $fields)
{
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === []) {
            $missing[] = $field;
        }
    }

    if ($missing !== []) {
        sendJsonResponse(false, "Missing required fields: " . implode(', ', $missing), []);
    }
}

function requireInt(array $data, $field, $min = null, $max = null)
{
    if (!isset($data[$field]) || !is_numeric($data[$field])) {
        sendJsonResponse(false, "{$field} invalid", []);
    }

    $value = (int) $data[$field];
    if (($min !== null && $value < $min) || ($max !== null && $value > $max)) {
        sendJsonResponse(false, "{$field} out of range", []);
    }

    return $value;
}
