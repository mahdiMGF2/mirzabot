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
$setting = config_all('setting');

function disc_discounts(array $data, string $method): void
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
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM discount WHERE (code LIKE :code_discount)");
        $stmt->execute([':code_discount' => $search]);
        $totalDiscount = (int) $stmt->fetchColumn();
        $totalPages = (int) ceil($totalDiscount / $limit);
        $query = "SELECT * FROM discount WHERE (code  LIKE CONCAT('%', :code_discount, '%')) ORDER BY id LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':code_discount', $q, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $discount = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendJsonResponse(true, "Successful", [
            'discount' => $discount,
            'pagination' => [
                'total_discount' => $totalDiscount,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'per_page' => $limit
            ]
        ]);

    } catch (Exception $e) {
        error_log("Database error in discount: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 500);
    }
}

function disc_discount(array $data, string $method): void
{
    validateMethod('GET', $method);

    if (!isset($data['id']) || empty($data['id'])) {
        sendJsonResponse(false, "id empty", []);
    }

    try {
        $discount = select("discount", "*", "id", $data['id'], "select");
        if (!$discount) {
            sendJsonResponse(true, "Successful", [
                'discount' => [],
            ]);
        }
        sendJsonResponse(true, "Successful", [
            'discount' => $discount,
        ]);
    } catch (Exception $e) {
        error_log("Database error in discount: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 500);
    }
}

function disc_discount_add(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);
    requireFields($data, ['code']);
    $price = requireInt($data, 'price', 0);
    $limitUse = requireInt($data, 'limit_use', 0);

    if (!preg_match('/^[A-Za-z\d]+$/', $data['code'])) {
        sendJsonResponse(false, "invalid code", [], 200);
    }
    if (select("discount", "*", "code", $data['code'], "count") != 0) {
        sendJsonResponse(false, "Discount code exits", [], 200);
    }
    try {
        $productData = [
            'code' => $data['code'],
            'price' => $price,
            'limituse' => $limitUse,
            'limitused' => 0
        ];

        $columns = implode(',', array_keys($productData));
        $placeholders = ':' . implode(', :', array_keys($productData));
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO discount ({$columns}) VALUES ({$placeholders})"
        );

        foreach ($productData as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $stmt->execute();
        sendJsonResponse(true, "Successful");

    } catch (Exception $e) {
        error_log("Error in Discount add: " . $e->getMessage());
        sendJsonResponse(false, "An error occurred while editing Discount");
    }
}

function disc_discount_delete(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);
    requireFields($data, ['id']);
    $product = select("discount", "*", "id", $data['id'], "select");
    if (!$product) {
        sendJsonResponse(false, "Discount not found", [], 200);
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM discount  WHERE id = :id");
        $stmt->bindValue(":id", $data['id'], PDO::PARAM_INT);
        $stmt->execute();

        sendJsonResponse(true, "Discount delete successfully", [], 200);

    } catch (Exception $e) {
        error_log("Error in Discount delete : " . $e->getMessage());
        sendJsonResponse(false, "An error occurred while delete Discount");
    }
}

function disc_discount_sell_lists(array $data, string $method): void
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
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM discountsell WHERE (codeDiscount LIKE :code_discount)");
        $stmt->execute([':code_discount' => $search]);
        $totalDiscount = (int) $stmt->fetchColumn();
        $totalPages = (int) ceil($totalDiscount / $limit);
        $query = "SELECT * FROM discountsell WHERE (codeDiscount  LIKE CONCAT('%', :code_discount, '%')) ORDER BY id LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':code_discount', $q, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $discount = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $product = select("product", "code_product as id,name_product", null, null, "fetchAll");
        $panel = select("marzban_panel", "code_panel,name_panel", "status", "active", "fetchAll");
        sendJsonResponse(true, "Successful", [
            'discount' => $discount,
            'product' => $product,
            'panel' => $panel,
            'pagination' => [
                'total_discount' => $totalDiscount,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'per_page' => $limit
            ]
        ]);

    } catch (Exception $e) {
        error_log("Database error in discount: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 500);
    }
}

function disc_discount_sell(array $data, string $method): void
{
    validateMethod('GET', $method);

    if (!isset($data['id']) || empty($data['id'])) {
        sendJsonResponse(false, "id empty", []);
    }

    try {
        $discount = select("discountsell", "*", "id", $data['id'], "select");
        if (!$discount) {
            sendJsonResponse(true, "Successful", [
                'discount' => [],
            ]);
        }
        if ($discount['code_product'] != "all") {
            $product = select('product', "*", "code_product", $discount['code_product'], "select");
            $discount['code_product'] = $product['name_product'] ?? $discount['code_product'];
        }
        if ($discount['code_panel'] != "/all") {
            $panel = select('marzban_panel', "*", "code_panel", $discount['code_panel'], "select");
            $discount['code_panel'] = $panel['name_panel'] ?? $discount['code_panel'];
        }
        sendJsonResponse(true, "Successful", [
            'discount' => $discount,
        ]);
    } catch (Exception $e) {
        error_log("Database error in discount: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 500);
    }
}

function disc_discount_sell_delete(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);
    requireFields($data, ['id']);
    $product = select("discountsell", "*", "id", $data['id'], "select");
    if (!$product) {
        sendJsonResponse(false, "DiscountSell not found", [], 200);
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM discountsell  WHERE id = :id");
        $stmt->bindValue(":id", $data['id'], PDO::PARAM_INT);
        $stmt->execute();

        sendJsonResponse(true, "DiscountSell delete successfully", [], 200);

    } catch (Exception $e) {
        error_log("Error in DiscountSell delete : " . $e->getMessage());
        sendJsonResponse(false, "An error occurred while delete DiscountSell");
    }
}

function disc_discount_sell_add(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);
    requireFields($data, ['code']);
    $percent = requireInt($data, 'percent', 0, 100);
    $limitUse = requireInt($data, 'limit_use', 0);

    if (!preg_match('/^[A-Za-z\d]+$/', $data['code'])) {
        sendJsonResponse(false, "invalid code", [], 200);
    }
    if (select("discountsell", "*", "codeDiscount", $data['code'], "count") != 0) {
        sendJsonResponse(false, "Discount code exits", [], 200);
    }
    try {
        $productData = [
            'codeDiscount' => $data['code'],
            'price' => $percent,
            'limitDiscount' => $limitUse,
            'usedDiscount' => 0,
            'agent' => empty($data['agent']) ? "allusers" : $data['agent'],
            'usefirst' => empty($data['usefirst']) ? "0" : $data['usefirst'],
            'useuser' => empty($data['useuser']) ? null : $data['useuser'],
            'code_product' => empty($data['code_product']) ? "all" : $data['code_product'],
            'code_panel' => empty($data['code_panel']) ? "/all" : $data['code_panel'],
            'time' => empty($data['time']) ? null : $data['time'],
            'type' => empty($data['type']) ? "all" : $data['type'],
        ];

        $columns = implode(',', array_keys($productData));
        $placeholders = ':' . implode(', :', array_keys($productData));
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO discountsell ({$columns}) VALUES ({$placeholders})"
        );

        foreach ($productData as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $stmt->execute();
        sendJsonResponse(true, "Successful");

    } catch (Exception $e) {
        error_log("Error in Discount add: " . $e->getMessage());
        sendJsonResponse(false, "An error occurred while editing Discount");
    }
}

match ($action) {
    'discounts' => disc_discounts($data, $method),
    'discount' => disc_discount($data, $method),
    'discount_add' => disc_discount_add($data, $method),
    'discount_delete' => disc_discount_delete($data, $method),
    'discount_sell_lists' => disc_discount_sell_lists($data, $method),
    'discount_sell' => disc_discount_sell($data, $method),
    'discount_sell_delete' => disc_discount_sell_delete($data, $method),
    'discount_sell_add' => disc_discount_sell_add($data, $method),
    default => sendJsonResponse(false, "Action Invalid"),
};


?>