<?php
// API for Subscriptions Management
// This file is included via requests/dashboard/index.php?endpoint=Subscriptions

if (!isset($storeId)) {
    echo outputError(["msg" => "Authentication required."]);die();
}

require_once("../../admin/includes/functions/payment.php");

$action = $_GET["action"] ?? "list";

if ($action == "list") {
    // List subscriptions for the authenticated store
    $page = isset($_REQUEST["page"]) ? (int)$_REQUEST["page"] : 1;
    $limit = isset($_REQUEST["limit"]) ? (int)$_REQUEST["limit"] : 20;
    $offset = ($page - 1) * $limit;
    
    $where = "`storeId` = '{$storeId}'";
    
    // Sort logic
    $orderBy = $_REQUEST["orderBy"] ?? "id";
    $orderDir = $_REQUEST["orderDir"] ?? "DESC";
    
    // Total count for pagination
    $totalCount = queryDB("SELECT COUNT(*) as total FROM subscriptions WHERE {$where}");
    $totalEntries = (int)($totalCount[0]["total"] ?? 0);
    $totalPages = ceil($totalEntries / $limit);

    // Fetch records
    $sql = "SELECT s.*, p.title as packageTitle 
            FROM subscriptions as s 
            LEFT JOIN packages as p ON s.packageId = p.id 
            WHERE s.{$where} 
            ORDER BY s.{$orderBy} {$orderDir} 
            LIMIT {$offset}, {$limit}";

    if ($subscriptions = queryDB($sql)) {
        $statusMap = [
            0 => ["en" => "Pending", "ar" => "انتظار"],
            1 => ["en" => "Success", "ar" => "ناجح"],
            2 => ["en" => "Failed", "ar" => "فاشلة"]
        ];

        foreach ($subscriptions as &$sub) {
            $pTitle = json_decode($sub["packageTitle"], true);
            $sub["packageNameEn"] = $pTitle["en"] ?? "";
            $sub["packageNameAr"] = $pTitle["ar"] ?? "";
            
            $s = $statusMap[$sub["status"]] ?? $statusMap[0];
            $sub["statusTitleEn"] = $s["en"];
            $sub["statusTitleAr"] = $s["ar"];
            
            $sub["formattedDate"] = timeZoneConverter($sub["date"]);
            unset($sub["packageTitle"]);
        }
        
        echo json_encode([
            "status" => "success", 
            "data" => $subscriptions,
            "pagination" => [
                "currentPage" => $page,
                "totalPages" => $totalPages,
                "totalEntries" => $totalEntries
            ]
        ]);
    } else {
        echo json_encode(["status" => "success", "data" => [], "msg" => "No subscriptions found"]);
    }
}

elseif ($action == "packages") {
    // List all available packages for purchase
    if ($packages = selectDBNew("packages", ["0"], "`status` = ?", "`rank` ASC")) {
        $response = [];
        foreach ($packages as $package) {
            $title = json_decode($package['title'], true);
            $subtitle = json_decode($package['subtitle'], true);
            $details = json_decode($package['details'], true);
            $priceSubtitle = json_decode($package['priceSubtitle'], true);
            
            $response[] = [
                "id" => $package['id'],
                "title" => array(
                    "en" => $title['en'] ?? '',
                    "ar" => $title['ar'] ?? ''
                ),
                "subtitle" => array(
                    "en" => $subtitle['en'] ?? '',
                    "ar" => $subtitle['ar'] ?? ''
                ),
                "details" => array(
                    "en" => $details['en'] ?? '',
                    "ar" => $details['ar'] ?? ''
                ),
                "priceSubtitle" => array(
                    "en" => $priceSubtitle['en'] ?? '',
                    "ar" => $priceSubtitle['ar'] ?? ''
                ),
                "price" => $package['price'],
                "days" => $package['days']
            ];
        }
        echo json_encode(["status" => "success", "data" => $response]);
    } else {
        echo outputError(["msg" => "No packages available."]);
    }
}

elseif ($action == "purchase") {
    // Initiate a new subscription purchase
    $input = json_decode(file_get_contents('php://input'), true);
    $packageId = $input['packageId'] ?? 0;
    $employeeId = $input['employeeId'] ?? 0; // Coming from app context

    if (!$packageId) {
        echo outputError(["msg" => "Package ID is required."]);
        die();
    }

    if ($package = selectDBNew("packages", [$packageId], "`id` = ? AND `status` = '0'", "")) {
        $orderId = "SUB-" . time() . mt_rand(100, 999);
        $price = $package[0]['price'];

        // Request payment link
        $paymentData = [
            "endpoint" => "PaymentRequest",
            "apikey" => $PaymentAPIKey,
            "PaymentMethodId" => 2,
            "CustomerReference" => $orderId,
            "DisplayCurrencyIso" => "KWD",
            "InvoiceValue" => $price,
            "CallBackUrl" => $protocol . $_SERVER['HTTP_HOST'] . "/api/subCallback.php",
            "ErrorUrl" => $protocol . $_SERVER['HTTP_HOST'] . "/api/subCallback.php",
            "CustomerName" => $storeDetails[0]["title"] ?? "Store Subscription",
            "CustomerEmail" => "subscription@artline.com",
            "Language" => "en",
            "Items" => [
                [
                    "ItemName" => "Package: " . json_decode($package[0]['title'], true)['en'],
                    "Quantity" => 1,
                    "UnitPrice" => $price
                ]
            ]
        ];

        $paymentResponse = payment($paymentData);

        if (isset($paymentResponse['url']) && isset($paymentResponse['id'])) {
            $insertData = [
                "packageId" => $packageId,
                "storeId" => $storeId,
                "employeeId" => $employeeId,
                "orderId" => $orderId,
                "gatewayId" => $paymentResponse['id'],
                "price" => $price,
                "status" => 0,
                "date" => date("Y-m-d H:i:s")
            ];
            insertDB("subscriptions", $insertData);

            echo json_encode([
                "status" => "success", 
                "paymentUrl" => $paymentResponse['url'],
                "orderId" => $orderId
            ]);
        } else {
            echo outputError(["msg" => "Payment gateway error."]);
        }
    } else {
        echo outputError(["msg" => "Package not found."]);
    }
}
?>