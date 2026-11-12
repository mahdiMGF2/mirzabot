<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../function.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/../botapi.php';

header('Content-Type: application/json; charset=UTF-8');
date_default_timezone_set('Asia/Tehran');
ini_set('default_charset', 'UTF-8');
ini_set('error_log', 'error_log');

list($headers, $data, $action) = apiRequestContext();
$method = $_SERVER['REQUEST_METHOD'];
$setting = config_all('setting');

function cat_categorys(array $data, string $method): void
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
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM category WHERE (remark LIKE :remark )");
        $stmt->execute([':remark' => $search]);
        $totalcategory = (int) $stmt->fetchColumn();
        $totalPages = (int) ceil($totalcategory / $limit);
        $query = "SELECT * FROM category WHERE remark  LIKE CONCAT('%', :remark, '%') ORDER BY display_order, id LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':remark', $q, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $categorys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendJsonResponse(true, "Successful", [
            'categorys' => $categorys,
            'pagination' => [
                'total_categorys' => $totalcategory,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'per_page' => $limit
            ]
        ]);

    } catch (Exception $e) {
        error_log("Database error in category: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 500);
    }
}

function cat_category(array $data, string $method): void
{
    validateMethod('GET', $method);

    if (!isset($data['id']) || empty($data['id'])) {
        sendJsonResponse(false, "id empty", []);
    }

    try {
        $category = select("category", "*", "id", $data['id'], "select");
        if (!$category) {
            sendJsonResponse(true, "Successful", [
                'category' => [],
            ]);
        }
        sendJsonResponse(true, "Successful", [
            'category' => $category,
        ]);
    } catch (Exception $e) {
        error_log("Database error in category: " . $e->getMessage());
        sendJsonResponse(false, "Database error occurred", [], 500);
    }
}

function cat_category_add(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);
    requireFields($data, ['remark']);
    try {
        $categoryData = [
            'remark' => $data['remark'],
        ];

        $columns = implode(',', array_keys($categoryData));
        $placeholders = ':' . implode(', :', array_keys($categoryData));

        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO category ({$columns}) VALUES ({$placeholders})"
        );

        foreach ($categoryData as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $stmt->execute();
        $pdo->prepare("UPDATE category SET display_order = id WHERE id = ?")->execute([$pdo->lastInsertId()]);
        sendJsonResponse(true, "Successful");

    } catch (Exception $e) {
        error_log("Error in category_add: " . $e->getMessage());
        sendJsonResponse(false, "An error occurred while editing category");
    }
}

function cat_category_edit(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);
    requireFields($data, ['id']);
    $category = select("category", "*", "id", $data['id'], "select");
    if (!$category) {
        sendJsonResponse(false, "category not found", [], 200);
    }

    try {
        $categoryData = [
            'remark' => isset($data['remark']) ? $data['remark'] : $category['remark'],
        ];

        $setParts = [];
        foreach ($categoryData as $key => $value) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setClause = implode(", ", $setParts);

        $stmt = $pdo->prepare("UPDATE category SET {$setClause} WHERE id = :id");

        foreach ($categoryData as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(":id", $data['id'], PDO::PARAM_INT);

        $stmt->execute();

        sendJsonResponse(true, "category updated successfully", [], 200);

    } catch (Exception $e) {
        error_log("Error in category_edit: " . $e->getMessage());
        sendJsonResponse(false, "An error occurred while adding category");
    }
}

function cat_category_delete(array $data, string $method): void
{
    global $pdo;

    validateMethod('POST', $method);
    requireFields($data, ['id']);
    $category = select("category", "*", "id", $data['id'], "select");
    if (!$category) {
        sendJsonResponse(false, "category not found", [], 200);
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM category  WHERE id = :id");
        $stmt->bindValue(":id", $data['id'], PDO::PARAM_INT);
        $stmt->execute();

        sendJsonResponse(true, "category delete successfully", [], 200);

    } catch (Exception $e) {
        error_log("Error in category delete : " . $e->getMessage());
        sendJsonResponse(false, "An error occurred while delete prodcut");
    }
}

match ($action) {
    'categorys' => cat_categorys($data, $method),
    'category' => cat_category($data, $method),
    'category_add' => cat_category_add($data, $method),
    'category_edit' => cat_category_edit($data, $method),
    'category_delete' => cat_category_delete($data, $method),
    default => sendJsonResponse(false, "Action Invalid"),
};


?>