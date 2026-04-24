<?php
// API for Receipt / Thermal Printing Support

if (!isset($storeId)) {
    echo outputError(["msg" => "Authentication required."]);die();
}

if (!isset($_REQUEST["action"]) || empty($_REQUEST["action"])) {
    echo outputError(["msg" => "Action is required"]);die();
}

$action = $_REQUEST["action"];

if ($action == "print") {
    $orderId = $_REQUEST["orderId"] ?? "";
    if (empty($orderId)) {
        echo outputError(["msg" => "Order ID is required"]);die();
    }

    $order = selectDB("pos_orders", "id = '{$orderId}' AND storeId = '{$storeId}'");
    if (!$order) {
        echo outputError(["msg" => "Order not found"]);die();
    }

    $order = $order[0];
    $info = json_decode($order["info"], true);
    
    // Fetch store details for the header
    $store = selectDB("stores", "id = '{$storeId}'");
    
    $receipt = [
        "header" => [
            "storeName" => $store[0]["title"] ?? "ArtLine Store",
            "logo" => $store[0]["logo"] ?? "",
            "phone" => $store[0]["phone"] ?? "",
            "address" => $store[0]["address"] ?? ""
        ],
        "order" => [
            "id" => $order["id"],
            "ref" => $order["orderId"],
            "date" => $order["date"],
            "customer" => $info["name"] ?? "Walking Customer",
            "phone" => $info["phone"] ?? ""
        ],
        "items" => [],
        "totals" => [
            "subtotal" => (float)($info["subTotal"] ?? $order["price"]),
            "discount" => (float)($info["discount"] ?? 0),
            "total" => (float)$order["price"]
        ],
        "payment" => [
            "method" => $order["paymentMethod"] == 10 ? "Cash" : ($order["paymentMethod"] == 1 ? "Online" : "Link")
        ]
    ];

    // Build item list with titles for the printer
    $itemsRaw = json_decode($order["items"], true) ?: ($info["items"] ?? []);
    foreach ($itemsRaw as $item) {
        $p = selectDB2("enTitle, arTitle", "products", "id = '{$item["productId"]}'");
        $a = selectDB2("enTitle, arTitle", "attributes_products", "id = '{$item["subId"]}'");
        
        $receipt["items"][] = [
            "title" => direction($p[0]["enTitle"], $p[0]["arTitle"]),
            "variant" => direction($a[0]["enTitle"], $a[0]["arTitle"]),
            "quantity" => $item["quantity"],
            "price" => (float)($item["price"] ?? 0)
        ];
    }

    // This endpoint returns formatted JSON for the App/Web to handle thermal printing commands
    echo outputData(["receipt" => $receipt]);
    die();
}
