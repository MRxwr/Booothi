<?php
// API for Orders Management
// Action-based routing

if (!isset($storeId)) {
    echo outputError(["msg" => "Authentication required."]);die();
}

/**
 * Handle listing of orders with search, sorting, and pagination.
 */
function listOrders($storeId) {
    global $dbconnect;

    // Parameters
    $page = isset($_REQUEST["page"]) ? (int)$_REQUEST["page"] : 1;
    $limit = isset($_REQUEST["limit"]) ? (int)$_REQUEST["limit"] : 20;
    $offset = ($page - 1) * $limit;
    $search = $_REQUEST["search"] ?? "";
    $orderBy = $_REQUEST["orderBy"] ?? "id"; // Default sort
    $orderDir = $_REQUEST["orderDir"] ?? "DESC";

    // Base query parts
    $where = "storeId = '{$storeId}'";
    $orderBy = $dbconnect->real_escape_string($orderBy);
    $orderDir = $dbconnect->real_escape_string($orderDir);
    $limit = $dbconnect->real_escape_string($limit);
    $offset = $dbconnect->real_escape_string($offset);
    // Search logic: Search within JSON 'info' field directly in SQL for better performance
    if (!empty($search)) {
        $searchEscaped = $dbconnect->real_escape_string($search);
        $where .= " AND (
            id LIKE '%{$searchEscaped}%' OR 
            gatewayId LIKE '%{$searchEscaped}%' OR 
            info LIKE '%{$searchEscaped}%' 
        )";
    }

    // Sort mapping: Determine SQL sort column
    $orderColumn = "id"; // Default
    if ($orderBy == "date") {
        $orderColumn = "date";
    }

    // Get total count for pagination
    $totalCountResult = selectDB2("COUNT(*) as total", "orders2", $where);
    $totalEntries = (int)($totalCountResult[0]["total"] ?? 0);
    $totalPages = ceil($totalEntries / $limit);

    // List orders with pagination (SQL level sorting for id and date)
    $orders = selectDB2("id, info, address, price, date, paymentMethod, status, gatewayId", "orders2", "{$where} ORDER BY {$orderColumn} {$orderDir} LIMIT {$limit} OFFSET {$offset}");
    
    // Status mapping for titles
    $statusMap = [
        0 => ["en" => "Pending", "ar" => "انتظار"],
        1 => ["en" => "Paid", "ar" => "مدفوعه"],
        2 => ["en" => "Preparing", "ar" => "جاري التجهيز"],
        3 => ["en" => "On Delivery", "ar" => "جاري التوصيل"],
        4 => ["en" => "Delivered", "ar" => "تم التوصيل"],
        5 => ["en" => "Cancel", "ar" => "ملغية"],
        6 => ["en" => "Return", "ar" => "مسترجع"]
    ];

    if ($orders) {
        foreach ($orders as &$order) {
            $info = json_decode($order["info"], true);
            $address = json_decode($order["address"], true);
            $order["customerName"] = $info["name"] ?? "";
            $order["customerPhone"] = $info["phone"] ?? "";
            $order["totalPrice"] = numTo3Float($order["price"] + ($address["shipping"] ?? 0));
            $order["orderDate"] = timeZoneConverter($order["date"]);
            
            // Get status title
            $s = $statusMap[$order["status"]] ?? $statusMap[0];
            $order["statusTitleEn"] = $s["en"];
            $order["statusTitleAr"] = $s["ar"];

            // Get payment method title
            if ($paymentMethod = selectDB2("enTitle, arTitle", "p_methods", "`paymentId` = '{$order["paymentMethod"]}'")) {
                $order["paymentTitle"] = direction($paymentMethod[0]["enTitle"], $paymentMethod[0]["arTitle"]);
            } else {
                $order["paymentTitle"] = "";
            }
            
            // Cleanup response object
            unset($order["info"], $order["address"]);
        }

        /**
         * Advanced PHP-level sorting for JSON-nested fields (name, phone)
         * Since these are NOT top-level SQL columns, we sort the result set in memory.
         * For 'list' calls with name/phone sorting, we increase the fetch depth if needed, 
         * but for the current limit, this is the most accurate approach.
         */
        if ($orderBy == "name") {
            usort($orders, function($a, $b) use ($orderDir) {
                $valA = strtolower($a["customerName"]);
                $valB = strtolower($b["customerName"]);
                return ($orderDir == "ASC") ? strcmp($valA, $valB) : strcmp($valB, $valA);
            });
        } elseif ($orderBy == "phone") {
            usort($orders, function($a, $b) use ($orderDir) {
                return ($orderDir == "ASC") ? strcmp($a["customerPhone"], $b["customerPhone"]) : strcmp($b["customerPhone"], $a["customerPhone"]);
            });
        }
    }

    echo outputData([
        "orders" => $orders ?: [],
        "pagination" => [
            "currentPage" => $page,
            "limit" => $limit,
            "totalEntries" => $totalEntries,
            "totalPages" => $totalPages
        ]
    ]);
    die();
}

$action = $_REQUEST["action"] ?? "";

switch ($action) {
    case "list":
        listOrders($storeId);
        break;

    case "getStatuses":
        /**
         * Returns available order statuses.
         * Mapping based on system logic (status 0 is default/pending):
         * 0: Pending, 1: Paid, 2: Preparing, 3: On Delivery, 4: Delivered, 5: Cancel, 6: Return
         */
        $statuses = [
            ["id" => 0, "enTitle" => "Pending", "arTitle" => "انتظار"],
            ["id" => 1, "enTitle" => "Paid", "arTitle" => "مدفوعه"],
            ["id" => 2, "enTitle" => "Preparing", "arTitle" => "جاري التجهيز"],
            ["id" => 3, "enTitle" => "On Delivery", "arTitle" => "جاري التوصيل"],
            ["id" => 4, "enTitle" => "Delivered", "arTitle" => "تم التوصيل"],
            ["id" => 5, "enTitle" => "Cancel", "arTitle" => "ملغية"],
            ["id" => 6, "enTitle" => "Return", "arTitle" => "مسترجع"]
        ];
        echo outputData($statuses); 
        die();

    case "details":
        // Get full details of a specific order
        if (!isset($_REQUEST["orderId"]) || empty($_REQUEST["orderId"])) {
            echo outputError(["msg" => "Order ID required."]); die();
        }

        $orderId = $_REQUEST["orderId"];
        $order = selectDB2("id, items, voucher, giftCard, address, info, status, paymentMethod, date, price, gatewayId", "orders2", "id = '{$orderId}' AND storeId = '{$storeId}'");
        
        if ($order) {
            $data = $order[0];

            // Status mapping for titles
            $statusMap = [
                0 => ["en" => "Pending", "ar" => "انتظار"],
                1 => ["en" => "Paid", "ar" => "مدفوعه"],
                2 => ["en" => "Preparing", "ar" => "جاري التجهيز"],
                3 => ["en" => "On Delivery", "ar" => "جاري التوصيل"],
                4 => ["en" => "Delivered", "ar" => "تم التوصيل"],
                5 => ["en" => "Cancel", "ar" => "ملغية"],
                6 => ["en" => "Return", "ar" => "مسترجع"]
            ];

            $s = $statusMap[$data["status"]] ?? $statusMap[0];
            $data["statusTitleEn"] = $s["en"];
            $data["statusTitleAr"] = $s["ar"];
            $data["orderDate"] = timeZoneConverter($data["date"]);

            $data["items"] = json_decode($data["items"], true);
            $data["voucher"] = json_decode($data["voucher"], true);
            $data["giftCard"] = json_decode($data["giftCard"], true);
            $data["address"] = json_decode($data["address"], true);
            $data["info"] = json_decode($data["info"], true);
            
            // Enrich items with Product, Attribute titles, and Image
            foreach ($data["items"] as &$item) {
                $product = selectDB2("id, enTitle, arTitle", "products", "id = '{$item["productId"]}'");
                $item["productTitleEn"] = $product ? $product[0]["enTitle"] : "";
                $item["productTitleAr"] = $product ? $product[0]["arTitle"] : "";
                
                // Get Product Image from images table
                $image = selectDB2("imageurl", "images", "productId = '{$item["productId"]}' ORDER BY id ASC LIMIT 1");
                $item["productImage"] = $image ? $image[0]["imageurl"] : "";

                if (!empty($item["subId"])) {
                    $attr = selectDB2("id, enTitle, arTitle", "attributes_products", "id = '{$item["subId"]}'");
                    $item["variantTitleEn"] = $attr ? $attr[0]["enTitle"] : "";
                    $item["variantTitleAr"] = $attr ? $attr[0]["arTitle"] : "";
                }

                // Enrich extras
                $item["extrasDetails"] = [];
                if (!empty($item["extras"]["id"])) {
                    foreach ($item["extras"]["id"] as $key => $extraId) {
                        if (!empty($extraId)) {
                            $extraInfo = selectDB2("id, enTitle, arTitle, price, priceBy", "extras", "id = '{$extraId}'");
                            if ($extraInfo) {
                                $variantValue = $item["extras"]["variant"][$key] ?? "";
                                $finalPrice = ($extraInfo[0]["priceBy"] == 0) ? $extraInfo[0]["price"] : $variantValue;
                                
                                $item["extrasDetails"][] = [
                                    "id" => $extraId,
                                    "enTitle" => $extraInfo[0]["enTitle"],
                                    "arTitle" => $extraInfo[0]["arTitle"],
                                    "price" => $finalPrice,
                                    "variant" => ($extraInfo[0]["priceBy"] == 0) ? $variantValue : ""
                                ];
                            }
                        }
                    }
                }
                unset($item["extras"]); // Remove raw extras data
                //do the same for collections
                $item["collectionsDetails"] = [];
                if (!empty($item["collections"]["id"])) {
                    foreach ($item["collections"]["id"] as $key => $collectionId) {
                        if (!empty($collectionId)) {
                            $collectionInfo = selectDB2("id, enTitle, arTitle", "collections", "id = '{$collectionId}'");
                            if ($collectionInfo) {
                                $item["collectionsDetails"][] = [
                                    "id" => $collectionId,
                                    "enTitle" => $collectionInfo[0]["enTitle"],
                                    "arTitle" => $collectionInfo[0]["arTitle"]
                                ];
                            }
                        }
                    }
                }
                unset($item["collections"]); // Remove raw collections data

            }
            
            echo outputData($data); die();
        } else {
            echo outputError(["msg" => "Order not found."]); die();
        }
        break;

    case "updateStatus":
        // Update order status (0: Pending, 1: Paid/Confirmed, etc.)
        if (!isset($_POST["orderId"]) || !isset($_POST["status"])) {
            echo outputError(["msg" => "Order ID and status required."]); die();
        }

        $orderId = $_POST["orderId"];
        $newStatus = $_POST["status"];

        if (updateDBNew("orders2", ["status" => $newStatus], "id = ? AND storeId = ?", [$orderId, $storeId])) {
            logStoreActivity("Orders", "Order Status Updated ID: $orderId to $newStatus");
            echo outputData(["msg" => "Order status updated successfully."]); die();
        } else {
            echo outputError(["msg" => "Failed to update order status."]); die();
        }
        break;

    default:
        echo outputError(["msg" => "Invalid action."]); die();
        break;
}
