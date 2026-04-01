<?php
// API for Orders Management
// Action-based routing

if (!isset($storeId)) {
    outputError("Authentication required.");
}

$action = $_REQUEST["action"] ?? "";

switch ($action) {
    case "list":
        // List recent orders for the store
        $orders = selectDBNew("orders2", "storeId = ?", [$storeId], "ORDER BY id DESC LIMIT 100");
        if ($orders) {
            foreach ($orders as &$order) {
                $info = json_decode($order["info"], true);
                $address = json_decode($order["address"], true);
                $order["customerName"] = $info["name"] ?? "";
                $order["customerPhone"] = $info["phone"] ?? "";
                $order["totalPrice"] = numTo3Float($order["price"] + ($address["shipping"] ?? 0));
                $order["orderDate"] = timeZoneConverter($order["date"]);
                
                // Get payment method title
                if ($paymentMethod = selectDB("p_methods", "`paymentId` = '{$order["paymentMethod"]}'")) {
                    $order["paymentTitle"] = direction($paymentMethod[0]["enTitle"], $paymentMethod[0]["arTitle"]);
                } else {
                    $order["paymentTitle"] = "";
                }
                
                // Keep only summarized info for listing to minimize payload
                unset($order["items"], $order["info"], $order["address"], $order["voucher"], $order["giftCard"]);
            }
            outputData($orders);
        } else {
            outputData([]);
        }
        break;

    case "details":
        // Get full details of a specific order
        if (!isset($_REQUEST["id"])) {
            outputError("Order ID required.");
        }

        $orderId = $_REQUEST["id"];
        $order = selectDBNew("orders2", "id = ? AND storeId = ?", [$orderId, $storeId], "");
        
        if ($order) {
            $data = $order[0];
            $data["items"] = json_decode($data["items"], true);
            $data["voucher"] = json_decode($data["voucher"], true);
            $data["giftCard"] = json_decode($data["giftCard"], true);
            $data["address"] = json_decode($data["address"], true);
            $data["info"] = json_decode($data["info"], true);
            
            // Enrich items with Product and Attribute titles
            foreach ($data["items"] as &$item) {
                $product = selectDB("products", "`id` = '{$item["productId"]}'");
                $item["productTitleEn"] = $product ? $product[0]["enTitle"] : "";
                $item["productTitleAr"] = $product ? $product[0]["arTitle"] : "";
                
                if (!empty($item["subId"])) {
                    $attr = selectDB("attributes_products", "`id` = '{$item["subId"]}'");
                    $item["variantTitleEn"] = $attr ? $attr[0]["enTitle"] : "";
                    $item["variantTitleAr"] = $attr ? $attr[0]["arTitle"] : "";
                }
            }
            
            outputData($data);
        } else {
            outputError("Order not found.");
        }
        break;

    case "updateStatus":
        // Update order status (0: Pending, 1: Paid/Confirmed, etc.)
        if (!isset($_POST["id"]) || !isset($_POST["status"])) {
            outputError("Order ID and status required.");
        }

        $orderId = $_POST["id"];
        $newStatus = $_POST["status"];

        if (updateDBNew("orders2", ["status" => $newStatus], "id = ? AND storeId = ?", [$orderId, $storeId])) {
            logStoreActivity($storeId, "Order Status Updated ID: $orderId to $newStatus");
            outputData(["message" => "Order status updated successfully."]);
        } else {
            outputError("Failed to update order status.");
        }
        break;

    default:
        outputError("Invalid action.");
        break;
}
