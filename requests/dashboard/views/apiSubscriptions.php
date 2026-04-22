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
    $sql = "SELECT s.orderId, s.gatewayId, s.price, s.status, s.date, e.fullName, p.title as packageTitle 
            FROM subscriptions as s 
            LEFT JOIN packages as p ON s.packageId = p.id 
            LEFT JOIN employees as e ON s.employeeId = e.id
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
            
            $sub["date"] = timeZoneConverter($sub["date"]);
            unset($sub["packageTitle"]);
        }
        echo outputData([
            "subscriptions" => $subscriptions,
            "pagination" => [
                "currentPage" => $page,
                "totalPages" => $totalPages,
                "totalEntries" => $totalEntries
            ]
        ]);die();
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
}elseif ($action == "packages") {
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
                "discount" => $package['discount'],
                "discountType" => $package['discountType'],
                "discountedPrice" => ($package['discountType'] == "1") 
                    ? round($package['price'] * (1 - $package['discount'] / 100), 2) 
                    : max(0, round($package['price'] - $package['discount'], 2)),
                "duration" => $package['duration']
            ];
        }
        echo json_encode(["status" => "success", "data" => $response]);
    } else {
        echo outputError(["msg" => "No packages available."]);
    }
}elseif ($action == "purchase") {
    $packageId = $_REQUEST['packageId'] ?? 0;
    $employeeId = getEmployeeDetails()['id'] ?? 0; // Coming from app context

    if (!$packageId) {
        echo outputError(["msg" => "Package ID is required."]);
        die();
    }

    if ($package = selectDBNew("packages", [$packageId], "`id` = ? AND `status` = '0'", "")) {
        $orderId = "SUB-" . time() . mt_rand(100, 999);
        $price = ( $package[0]['discountType'] == "1" ) 
            ? round($package[0]['price'] * (1 - $package[0]['discount'] / 100), 2) 
            : max(0, round($package[0]['price'] - $package[0]['discount'], 2));
        if(getPaymentAPIKey() == ""){
            echo outputError(["msg" => "Payment gateway not configured. Please contact support."]);
            die();
        }
        // Request payment link
        $paymentData = [
            "endpoint"           => "PaymentRequestExcuteNew2024", 
            "apikey"             => getPaymentAPIKey(),
            "PaymentMethodId"    => 1,
            "CustomerReference"  => $orderId,
            "DisplayCurrencyIso" => "KWD",
            "invoiceValue"       => (float)$price, // Ensured float type
            "CallBackUrl"        => "https://" . $_SERVER['HTTP_HOST'] . "/api/subCallback.php",
            "ErrorUrl"           => "https://" . $_SERVER['HTTP_HOST'] . "/api/subCallback.php",
            "CustomerName"       => $storeDetails[0]["title"] ?? "Store Subscription",
            "CustomerEmail"      => $storeDetails[0]["email"] ?? "noreply@artline.com",
            "CustomerMobile"     => $storeDetails[0]["phone"] ?? "97104334",
            "Language"           => "en",
            "invoiceItems"       => [ // Changed from "Items" to "invoiceItems"
                [
                    "ItemName"  => "Package: " . json_decode($package[0]['title'], true)['en'],
                    "Quantity"  => 1,
                    "UnitPrice" => (float)$price
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
                "payload" => json_encode($paymentData),
                "gatewayResponse" => json_encode($paymentResponse),
                "gatewayURL" => $paymentResponse['url'],
                "status" => 0,
                "date" => date("Y-m-d H:i:s")
            ];
            insertDB("subscriptions", $insertData);
            echo outputData(["paymentUrl" => $paymentResponse['url'], "orderId" => $orderId]);die();
        } else {
            echo outputError(["msg" => "Payment gateway error."]);die();
        }
    } else {
        echo outputError(["msg" => "Package not found."]);die();
    }
}
?>