<?php
require("../admin/includes/config.php");
require("../admin/includes/functions.php");

// Set header to HTML because this is a callback page
header("Content-Type: text/html; charset=utf-8");

// Try different keys from callback URL (matching checkInvoice.php logic)
$orderId = $_GET['orderId'] ?? $_GET['requested_order_id'] ?? $_GET['Id'] ?? ''; 
$status = $_GET['status'] ?? '';

if (empty($orderId)) {
    echo "<h1>Error: Missing order ID</h1>";
    exit;
}

// Check current subscription in database by orderId
$subscription = selectDBNew("subscriptions", [$orderId], "`orderId` = ?", "");

if (!$subscription) {
    echo "<h1>Error: Subscription record not found.</h1>";
    exit;
}

// SECURITY: Never allow an order with status other than 0 (Pending) to be updated
if ($subscription[0]['status'] != 0) {
    echo "<h1>Error: This subscription has already been processed.</h1>";
    exit;
}

$subId = $subscription[0]['id'];
$storeId = $subscription[0]['storeId'];
$packageId = $subscription[0]['packageId'];
$gatewayPayload = json_encode($_GET, JSON_UNESCAPED_UNICODE);

if ($status == "Captured" || $status == "Success" || $status == "CAPTURED") {
    // 1. Update subscription status and store gateway payload
    updateDB("subscriptions", [
        "status" => 1,
        "gatewayPayload" => $gatewayPayload
    ], "`id` = '{$subId}'");

    // 2. Set maintenance mode to OFF (3) for the store
    updateDB("stores", ["maintenanceMode" => 3], "`id` = '{$storeId}'");

    // 3. Optional: Add any success message logic here
    $title = "Payment Successful";
    $message = "Your subscription has been activated successfully.";
    $color = "green";
} else {
    // Update status to failed and store gateway payload
    updateDB("subscriptions", [
        "status" => 2,
        "gatewayPayload" => $gatewayPayload
    ], "`id` = '{$subId}'");
    $title = "Payment Failed";
    $message = "Unfortunately, the payment was not completed. Please try again.";
    $color = "red";
}

// Landing page HTML for the App to capture (No ? parameters as requested)
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $title; ?></title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; }
        .card { max-width: 400px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; }
        .title { font-size: 24px; color: <?php echo $color; ?>; margin-bottom: 20px; }
        .message { font-size: 16px; margin-bottom: 30px; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="title"><?php echo $title; ?></div>
        <div class="message"><?php echo $message; ?></div>
        <!-- App can listen for this URL or close webview on click -->
        <a href="artline://subscription" class="btn">Return to App</a>
    </div>
</body>
</html>