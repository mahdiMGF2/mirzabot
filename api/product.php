<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';

header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('Asia/Tehran');
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');

list($headers, $data, $action) = apiRequestContext();
$method = $_SERVER['REQUEST_METHOD'];
$setting = select("setting", "*");

function prod_products(array $data, string $method): void
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
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM product WHERE (id LIKE :id_product OR name_product LIKE :name_product)");
        $stmt->execute([':id_product' => $search, ':name_product' => $search]);
        $totalproduct = (int) $stmt->fetchColumn();
        $totalPages = (int) ceil($totalproduct / $limit);
        $query = "SELECT * FROM product WHERE (id  LIKE CONCAT('%', :id_product, '%') OR name_product  LIKE CONCAT('%', :name_product, '%')) ORDER BY id LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':name_product', $q, PDO::PARAM_STR);
        $stmt->bindValue(':id_product', $q, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $panel = select("marzban_panel", "name_panel,code_panel", "status", "active", "fetchAll");
        $category = select("category", "*", null, null, "fetchAll");
        sendJsonResponse(true, "Successful", [
            'products' => $products,
            'panels' => $panel,
            'category' => $category,
            'pagination' => [
                'total_product' => $totalproduct,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'per_page' => $limit
            ]
        ]);

    } catch (Exception $e) {
        error_log("Database error in product: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 500);
    }
}

function prod_product(array $data, string $method): void
{
    validateMethod('GET', $method);

    if (!isset($data['id']) || empty($data['id'])) {
        sendJsonResponse(false, "id empty", []);
    }

    try {
        $prodcut = select("product", "*", "id", $data['id'], "select");
        if (!$prodcut) {
            sendJsonResponse(true, "Successful", [
                'product' => [],
            ]);
        }
        $count_invoice = select("invoice", "*", "name_product", $prodcut['name_product'], "count");
        $sum_invoice = select("invoice", "SUM(price_product) as sum_price", "name_product", $prodcut['name_product'], "select");
        $panel = select("marzban_panel", "name_panel,code_panel", "status", "active", "fetchAll");
        $category = select("category", "*", null, null, "fetchAll");
        $prodcut['hide_panel'] = json_decode($prodcut['hide_panel'] ?? '', true) ?: [];
        sendJsonResponse(true, "Successful", [
            'product' => $prodcut,
            'count_invoice' => $count_invoice,
            'sum_invoice' => $sum_invoice['sum_price'] ?? 0,
            'panels' => $panel,
            'category' => $category,
        ]);
    } catch (Exception $e) {
        error_log("Database error in product: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 500);
    }
}

function prod_product_add(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);
    requireFields($data, ['name', 'location']);
    $price = requireInt($data, 'price', 0);
    $dataLimit = requireInt($data, 'data_limit', 0);
    $serviceTime = requireInt($data, 'time', 0);

    $prodcut = select("product", "*", "name_product", $data['name'], "count");
    if ($prodcut != 0) {
        sendJsonResponse(false, "product name exits", [], 200);
    }
    if ($data['location'] === "/all") {
        $locationName = "/all";
    } else {
        $panel = select("marzban_panel", "*", "code_panel", $data['location'], "select");
        if (!$panel)
            sendJsonResponse(false, "location not found", [], 200);
        $locationName = $panel['name_panel'];
    }
    try {
        $randomString = bin2hex(random_bytes(3));

        $productData = [
            'code_product' => $randomString,
            'name_product' => $data['name'],
            'price_product' => $price,
            'Volume_constraint' => $dataLimit,
            'Service_time' => $serviceTime,
            'Location' => $locationName,
            'agent' => empty($data['agent']) ? "f" : $data['agent'],
            'note' => empty($data['note']) ? "" : $data['note'],
            'data_limit_reset' => empty($data['data_limit_reset']) ? "no_reset" : $data['data_limit_reset'],
            'inbounds' => empty($data['inbounds']) ? null : (is_array($data['inbounds']) ? json_encode($data['inbounds']) : $data['inbounds']),
            'proxies' => empty($data['proxies']) ? null : (is_array($data['proxies']) ? json_encode($data['proxies']) : $data['proxies']),
            'category' => empty($data['category']) ? null : $data['category'],
            'one_buy_status' => empty($data['one_buy_status']) ? 0 : $data['one_buy_status'],
            'hide_panel' => empty($data['hide_panel']) ? "{}" : (is_array($data['hide_panel']) ? json_encode($data['hide_panel']) : $data['hide_panel']),
        ];

        $columns = implode(',', array_keys($productData));
        $placeholders = ':' . implode(', :', array_keys($productData));

        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO product ({$columns}) VALUES ({$placeholders})"
        );

        foreach ($productData as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $stmt->execute();
        sendJsonResponse(true, "Successful");

    } catch (Exception $e) {
        error_log("Error in product_add: " . $e->getMessage());
        sendJsonResponse(false, "An error occurred while editing product");
    }
}

function prod_product_edit(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);
    requireFields($data, ['id']);
    $product = select("product", "*", "id", $data['id'], "select");
    if (!$product) {
        sendJsonResponse(false, "product not found", [], 200);
    }
    if (isset($data['name']) && $product['name_product'] != $data['name']) {
        $product_check = select("product", "*", "name_product", $data['name'], "count");
        if ($product_check != 0)
            sendJsonResponse(false, "product name exits", [], 200);
        update("invoice", "name_product", $data['name'], "name_product", $product['name_product']);
    }

    // Location is stored as the panel *name*; the client sends a panel code.
    $location = $product['Location'];
    if (isset($data['location'])) {
        if ($data['location'] === "/all") {
            $location = "/all";
        } else {
            $panel = select("marzban_panel", "*", "code_panel", $data['location'], "select");
            if (!$panel)
                sendJsonResponse(false, "location not found", [], 200);
            $location = $panel['name_panel'];
        }
    }

    try {
        $productData = [
            'name_product' => isset($data['name']) ? $data['name'] : $product['name_product'],
            'price_product' => isset($data['price']) ? $data['price'] : $product['price_product'],
            'Volume_constraint' => $data['volume'] ?? $data['data_limit'] ?? $product['Volume_constraint'],
            'Service_time' => isset($data['time']) ? $data['time'] : $product['Service_time'],
            'Location' => $location,
            'agent' => isset($data['agent']) ? $data['agent'] : $product['agent'],
            'note' => isset($data['note']) ? $data['note'] : $product['note'],
            'data_limit_reset' => isset($data['data_limit_reset']) ? $data['data_limit_reset'] : $product['data_limit_reset'],
            'inbounds' => isset($data['inbounds']) ? (is_array($data['inbounds']) ? json_encode($data['inbounds']) : $data['inbounds']) : $product['inbounds'],
            'proxies' => isset($data['proxies']) ? (is_array($data['proxies']) ? json_encode($data['proxies']) : $data['proxies']) : $product['proxies'],
            'category' => isset($data['category']) ? $data['category'] : $product['category'],
            'one_buy_status' => isset($data['one_buy_status']) ? $data['one_buy_status'] : $product['one_buy_status'],
            'hide_panel' => isset($data['hide_panel']) ? json_encode($data['hide_panel']) : $product['hide_panel'],
        ];
        $setParts = [];
        foreach ($productData as $key => $value) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setClause = implode(", ", $setParts);

        $stmt = $pdo->prepare("UPDATE product SET {$setClause} WHERE id = :id");

        foreach ($productData as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(":id", $data['id'], PDO::PARAM_INT);

        $stmt->execute();

        sendJsonResponse(true, "product updated successfully", [], 200);

    } catch (Exception $e) {
        error_log("Error in product_edit: " . $e->getMessage());
        sendJsonResponse(false, "An error occurred while adding product");
    }
}

function prod_product_delete(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);
    requireFields($data, ['id']);
    $product = select("product", "*", "id", $data['id'], "select");
    if (!$product) {
        sendJsonResponse(false, "product not found", [], 200);
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM product  WHERE id = :id");
        $stmt->bindValue(":id", $data['id'], PDO::PARAM_INT);
        $stmt->execute();

        sendJsonResponse(true, "product delete successfully", [], 200);

    } catch (Exception $e) {
        error_log("Error in product delete : " . $e->getMessage());
        sendJsonResponse(false, "An error occurred while delete prodcut");
    }
}

function prod_set_inbounds(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);
    requireFields($data, ['id', 'input']);
    $product = select("product", "*", "id", $data['id'], "select");
    if (!$product) {
        sendJsonResponse(false, "product not found", [], 200);
    }
    $panel = select("marzban_panel", "*", 'name_panel', $product['Location'], "select");
    if (!$panel) {
        sendJsonResponse(false, "panel not found", [], 200);
    }
    if ($panel['type'] == "marzban") {
        $DataUserOut = getuser($data['input'], $panel['name_panel']);
        if (!empty($DataUserOut['error']))
            sendJsonResponse(false, $DataUserOut['error'], [], 200);
        if (!empty($DataUserOut['status']) && $DataUserOut['status'] != 200)
            sendJsonResponse(false, $DataUserOut['msg'], [], 200);
        $DataUserOut = json_decode($DataUserOut['body'] ?? '', true);
        if (!is_array($DataUserOut))
            sendJsonResponse(false, "User Not Found", [], 200);

        // Marzban >= 1.0 renamed "proxies" to "proxy_settings" and moved the
        // inbound list into "group_ids".
        $isNewMarzban = $panel['version_panel'] == "1";
        $proxyKey = $isNewMarzban ? 'proxy_settings' : 'proxies';
        if ((isset($DataUserOut['msg']) && $DataUserOut['msg'] == "User not found") or !isset($DataUserOut[$proxyKey]))
            sendJsonResponse(false, "User Not Found", [], 200);

        foreach ($DataUserOut[$proxyKey] as $key => $value) {
            if ($key == "shadowsocks" || $key == "trojan") {
                unset($DataUserOut[$proxyKey][$key]['password']);
            } elseif ($key == "wireguard") {
                unset($DataUserOut[$proxyKey][$key]['private_key']);
                unset($DataUserOut[$proxyKey][$key]['public_key']);
                unset($DataUserOut[$proxyKey][$key]['peer_ips']);
            } else {
                unset($DataUserOut[$proxyKey][$key]['id']);
            }
            if (count($DataUserOut[$proxyKey][$key]) == 0) {
                $DataUserOut[$proxyKey][$key] = new stdClass();
            }
        }
        $proxy_output = json_encode($DataUserOut[$proxyKey]);
        $stmt = $pdo->prepare("UPDATE product SET proxies = :proxies WHERE id = :id_product");
        $stmt->execute([':proxies' => $proxy_output, ':id_product' => $data['id']]);
        $datainbound = json_encode($isNewMarzban ? ($DataUserOut['group_ids'] ?? []) : ($DataUserOut['inbounds'] ?? []));
    } elseif ($panel['type'] == "marzneshin") {
        $userdata = json_decode(getuserm($data['input'], $panel['name_panel'])['body'] ?? '', true);
        if (!is_array($userdata) || (isset($userdata['detail']) and $userdata['detail'] == "User not found"))
            sendJsonResponse(false, "User Not Found", [], 200);
        $datainbound = json_encode($userdata['service_ids'] ?? []);
    } elseif ($panel['type'] == "x-ui_single") {
        $user_data = get_clinets($data['input'], $panel['name_panel']);
        if (!empty($user_data['error']))
            sendJsonResponse(false, $user_data['error'], [], 200);
        if (!empty($user_data['status']) && $user_data['status'] != 200)
            sendJsonResponse(false, $user_data['msg'], [], 200);
        $user_data = json_decode($user_data['body'] ?? '', true)['obj'] ?? null;
        if ($user_data == null)
            sendJsonResponse(false, "User Not Found", [], 200);
        $datainbound = $user_data['inboundId'];
    } elseif ($panel['type'] == "s_ui") {
        $user_data = GetClientsS_UI($data['input'], $panel['name_panel']);
        if (!is_array($user_data) || count($user_data) == 0 || !isset($user_data['inbounds'])) {
            sendJsonResponse(false, "User Not Found", [], 200);
        }
        $servies = [];
        foreach ($user_data['inbounds'] as $service) {
            $servies[] = $service;
        }
        $datainbound = json_encode($servies);
    } elseif ($panel['type'] == "ibsng" || $panel['type'] == "mikrotik") {
        $datainbound = $data['input'];
    } else {
        sendJsonResponse(false, "panel_not_support_options", [], 200);
    }
    $stmt = $pdo->prepare("UPDATE product SET inbounds = :inbounds WHERE id = :id_product ");
    $stmt->execute([':inbounds' => $datainbound, ':id_product' => $data['id']]);
    sendJsonResponse(true, "successfully", [], 200);
}

function prod_remove_inbounds(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);
    requireFields($data, ['id']);
    $product = select("product", "*", "id", $data['id'], "select");
    if (!$product) {
        sendJsonResponse(false, "product not found", [], 200);
    }
    $stmt = $pdo->prepare("UPDATE product SET inbounds = NULL,proxies = NULL WHERE id = :id_product ");
    $stmt->execute([':id_product' => $data['id']]);
    sendJsonResponse(true, "successfully", [], 200);
}

match ($action) {
    'products' => prod_products($data, $method),
    'product' => prod_product($data, $method),
    'product_add' => prod_product_add($data, $method),
    'product_edit' => prod_product_edit($data, $method),
    'product_delete' => prod_product_delete($data, $method),
    'set_inbounds' => prod_set_inbounds($data, $method),
    'remove_inbounds' => prod_remove_inbounds($data, $method),
    default => sendJsonResponse(false, "Action Invalid"),
};


?>