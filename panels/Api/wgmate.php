<?php
function request_wgmate($location, $method, $path, $body = null)
{
    $panel = select("marzban_panel", "*", "name_panel", $location, "select");
    $ch = curl_init(rtrim($panel['url_panel'], '/') . '/api' . $path);
    curl_setopt_array($ch, array(
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT_MS => ($GLOBALS['request_exec_timeout'] ?? null) ?: 30000,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-API-Key: ' . $panel['password_panel']
        ),
    ));
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        return array('status' => null, 'body' => null, 'error' => $error);
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return array('status' => $status, 'body' => $response);
}
function getuser_wgmate($username_account, $location)
{
    $response = request_wgmate($location, "GET", '/users/by-username/' . rawurlencode($username_account));
    $response['user'] = json_decode($response['body'] ?? '', true)['data'] ?? null;
    return $response;
}
function status_wgmate(array $user)
{
    if ($user['status'] == "disabled") {
        return ($user['disableReason'] ?? '') == "user_quota" ? "limited" : "disabled";
    }
    if ($user['status'] == "expired" || ($user['expiresAt'] != "" && strtotime($user['expiresAt']) < time())) {
        return "expired";
    }
    if ($user['quotaBytes'] && $user['usedBytes'] >= $user['quotaBytes']) {
        return "limited";
    }
    if (($user['holdDays'] ?? 0) > 0 && ($user['expiresAt'] ?? "") == "") {
        return "on_hold";
    }
    return $user['status'];
}
function links_wgmate($location, $user_id)
{
    $links = json_decode(request_wgmate($location, "GET", '/users/' . $user_id . '/links')['body'] ?? '', true)['data'] ?? [];
    unset($links['subscription']);
    return array_values($links);
}
function subscription_wgmate($location, $user_id)
{
    return json_decode(request_wgmate($location, "GET", '/users/' . $user_id . '/subscription')['body'] ?? '', true)['data']['configs'] ?? [];
}
function adduser_wgmate($location, $data_limit, $username_ac, $timestamp, $name_product, $note = '', $limitip = null)
{
    $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM product WHERE name_product = ? AND (Location = ? OR Location = '/all') ORDER BY Location = '/all' LIMIT 1");
    $stmt->execute([$name_product, $location]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    $panel = select("marzban_panel", "*", "name_panel", $location, "select");
    $template = json_decode($product['inbounds'] ?? $panel['proxies'] ?? '', true);
    $data = array(
        'username' => $username_ac,
        'note' => $note,
        'quotaBytes' => (int) $data_limit,
        'expiresAt' => $timestamp == 0 ? "" : gmdate('Y-m-d\TH:i:s\Z', $timestamp),
    );
    if (is_array($template)) {
        if (!empty($template['bindings'])) {
            $data['bindings'] = $template['bindings'];
        } else {
            $data['protocols'] = $template['protocols'] ?? [];
            $data['serverId'] = $template['serverId'] ?? '';
        }
    }
    if ($limitip != null && $panel['limit_in_panel'] == "1") {
        $data['connLimit'] = intval($limitip);
    }
    $on_hold = $name_product == "usertest" ? $panel['on_hold_test'] != "0" : $panel['conecton'] != "offconecton";
    if ($on_hold && $timestamp != 0 && $timestamp - time() >= 86400) {
        $data['expiresAt'] = "";
        $data['holdDays'] = max(1, (int) round(($timestamp - time()) / 86400));
    }
    return request_wgmate($location, "POST", '/users', $data);
}
function Modifyuser_wgmate($location, $username_account, array $data)
{
    $response = getuser_wgmate($username_account, $location);
    if ($response['user'] === null) {
        return $response;
    }
    $modify = request_wgmate($location, "PATCH", '/users/' . $response['user']['id'], $data);
    if ($modify['status'] < 400 && $response['user']['status'] == "disabled" && ($response['user']['disableReason'] ?? '') == "user_quota") {
        return request_wgmate($location, "POST", '/users/' . $response['user']['id'] . '/enable');
    }
    return $modify;
}
function action_wgmate($location, $username_account, $method, $action = '')
{
    $response = getuser_wgmate($username_account, $location);
    if ($response['user'] === null) {
        return $response;
    }
    return request_wgmate($location, $method, '/users/' . $response['user']['id'] . $action);
}
