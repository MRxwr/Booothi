<?php
// API for POS Home (Categories and Products)
// Web & App Compatible

if (!isset($storeId)) {
    echo outputError(["msg" => "Authentication required."]);die();
}

if (!isset($_REQUEST["action"]) || empty($_REQUEST["action"])) {
    echo outputError(["msg" => "Action is required"]);die();
}

$action = $_REQUEST["action"];

if ($action == "index") {
    // 1. Get Categories
    $categories = selectDB2("id, enTitle, arTitle, imageurl, rank", "categories", "storeId = '{$storeId}' AND status = '0' AND hidden = '1' ORDER BY rank ASC");
    
    // 2. Get Products with Pagination
    $categoryId = $_REQUEST["categoryId"] ?? "";
    $search = $_REQUEST["search"] ?? "";
    $page = isset($_REQUEST["page"]) ? (int)$_REQUEST["page"] : 1;
    $limit = isset($_REQUEST["limit"]) ? (int)$_REQUEST["limit"] : 20;
    $offset = ($page - 1) * $limit;

    $where = "p.storeId = '{$storeId}' AND p.status = '0' AND p.hidden = '0'";
    
    if (!empty($categoryId)) {
        $categoryJoin = " JOIN category_products cp ON p.id = cp.productId";
        $where .= " AND cp.categoryId = '{$categoryId}'";
    } else {
        $categoryJoin = "";
    }

    if (!empty($search)) {
        $searchEscaped = $dbconnect->real_escape_string($search);
        $where .= " AND (p.enTitle LIKE '%{$searchEscaped}%' OR p.arTitle LIKE '%{$searchEscaped}%' OR p.id LIKE '%{$searchEscaped}%')";
    }

    // Get Total Count for Pagination
    $countSql = "SELECT COUNT(DISTINCT p.id) as total FROM products p {$categoryJoin} WHERE {$where}";
    $totalResult = queryDB($countSql);
    $totalEntries = (int)($totalResult[0]["total"] ?? 0);
    $totalPages = ceil($totalEntries / $limit);

    // Get Products
    $sql = "SELECT p.id, p.enTitle, p.arTitle, p.type, p.extras,
            (SELECT i.imageurl FROM images i WHERE i.productId = p.id ORDER BY i.id ASC LIMIT 1) as image
            FROM products p 
            {$categoryJoin}
            WHERE {$where} 
            GROUP BY p.id
            ORDER BY p.id DESC
            LIMIT {$limit} OFFSET {$offset}";
            
    $products = queryDB($sql);
    
    if ($products) {
        foreach ($products as &$product) {
            // Fetch Variants/Attributes
            $attributes = selectDB2("id, enTitle, arTitle, price, stock, sku", "attributes_products", "productId = '{$product["id"]}' AND status = '0' AND hidden = '0'");
            $product["variants"] = $attributes ?: [];
            
            // Fetch Extras
            $extraIds = json_decode($product["extras"] ?? "[]", true) ?: [];
            $product["extras_data"] = [];
            if (!empty($extraIds) && is_array($extraIds)) {
                $extraIdsStr = implode(",", array_map('intval', $extraIds));
                $product["extras_data"] = selectDB2("id, enTitle, arTitle, price, priceBy", "extras", "id IN ({$extraIdsStr}) AND status = '0'");
            }
            unset($product["extras"]);
        }
    }

    echo outputData([
        "categories" => $categories ?: [],
        "products" => $products ?: [],
        "pagination" => [
            "currentPage" => $page,
            "totalPages" => $totalPages,
            "totalEntries" => $totalEntries,
            "limit" => $limit
        ]
    ]);
    die();
}
