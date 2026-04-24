<?php
// API for POS Orders
// Web & App Compatible

if (!isset($storeId)) {
    echo outputError(["msg" => "Authentication required."]);die();
}

if (!isset($_REQUEST["action"]) || empty($_REQUEST["action"])) {
    echo outputError(["msg" => "Action is required"]);die();
}

$action = $_REQUEST["action"];
$data = $_POST;

if ($action == "save") {
    /*
     Data expected:
     - items: JSON array of objects [{productId, subId, quantity, extras: {id:[], variant:[]}}]
     - customer: {name, phone, email}
     - payment: {methodId, amount}
     - discount: {type, value}
    */
    
    if (!isset($data["items"]) || empty($data["items"])) {
        echo outputError(["msg" => "No items in order"]);die();
    }

    $items = json_decode($data["items"], true);
    $customer = json_decode($data["customer"] ?? "[]", true);
    $payment = json_decode($data["payment"] ?? "[]", true);
    
    $totalPrice = 0;
    
    foreach ($items as $item) {
        $subId = $item["subId"];
        $qty = $item["quantity"];
        
        $attr = selectDB("attributes_products", "id = '{$subId}' AND status = '0'");
        if (!$attr) continue;
        
        $itemPrice = $attr[0]["price"];
        $extraPrice = 0;
        
        // Handle extras
        $itemExtras = $item["extras"] ?? ["id" => [], "variant" => []];
        if (!empty($itemExtras["id"])) {
            foreach ($itemExtras["id"] as $idx => $exId) {
                $exInfo = selectDB("extras", "id = '{$exId}'");
                if ($exInfo) {
                    $price = ($exInfo[0]['priceBy'] == 0 ? $exInfo[0]['price'] : ($itemExtras["variant"][$idx] ?? 0));
                    $extraPrice += $price;
                }
            }
        }
        
        $totalPrice += ($itemPrice + $extraPrice) * $qty;
        
        // Update Inventory if not preorder
        $productInfo = selectDB("products", "id = '{$item["productId"]}'");
        if ($productInfo && $productInfo[0]["preorder"] == 0) {
            $newStock = max(0, $attr[0]["quantity"] - $qty);
            updateDBNew("attributes_products", ["quantity" => $newStock], "id = ? AND storeId = ?", [$subId, $storeId]);
        }
    }
    
    $discountValue = (float)($data["discountAmount"] ?? 0);
    $finalPrice = max(0, $totalPrice - $discountValue);

    $orderInfo = [
        "name" => $customer["name"] ?? "Walking Customer",
        "phone" => $customer["phone"] ?? "",
        "email" => $customer["email"] ?? "",
        "items" => $items,
        "discount" => $discountValue,
        "total" => $finalPrice
    ];

    // Align with pos_orders schema from screenshot
    $insertData = [
        "date" => date("Y-m-d H:i:s"),
        "storeId" => $storeId,
        "orderId" => "POS-" . rand(1000, 9999) . "-" . time(),
        "gatewayId" => "POS",
        "info" => json_encode($orderInfo, JSON_UNESCAPED_UNICODE),
        "address" => json_encode(["shipping" => 0], JSON_UNESCAPED_UNICODE),
        "paymentMethod" => $payment["methodId"] ?? 10, // Default to Cash
        "price" => $finalPrice,
        "items" => $data["items"], // raw items json if needed
        "status" => 1 // Paid
    ];

    if (insertDB("pos_orders", $insertData)) {
        $orderId = $dbconnect->insert_id;
        logStoreActivity("POS", "New POS order created ID: " . $orderId, $storeId);
        echo outputData(["msg" => "Order saved successfully", "orderId" => $orderId]);
    } else {
        echo outputError(["msg" => "Failed to save order"]);
    }
    die();

} elseif ($action == "details") {
    $orderId = $_REQUEST["orderId"] ?? "";
    $order = selectDB("pos_orders", "id = '{$orderId}' AND storeId = '{$storeId}'");
    if (!$order) {
        echo outputError(["msg" => "Order not found"]);die();
    }
    echo outputData(["order" => $order[0]]);
    die();
}
