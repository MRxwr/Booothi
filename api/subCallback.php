<?php
require("../admin/includes/config.php");
require("../admin/includes/functions.php");
require("../admin/includes/functions/payment.php");

// Set header to HTML because this is a callback page
header("Content-Type: text/html; charset=utf-8");

$paymentId = $_GET['paymentId'] ?? '';
$orderId = $_GET['Id'] ?? ''; // Some systems use 'Id' or 'orderId' or 'paymentId' interchangeably in callbacks

if (empty($paymentId)) {
    // If not in GET, check if returned as 'Id'
    $paymentId = $_GET['Id'] ?? '';
}

if (empty($paymentId)) {
    echo "<h1>Error: Missing payment ID</h1>";
    exit;
}

// Prepare data to check status using the provided checkPayment function
$checkData = [
    "endpoint" => "GetPaymentStatus",
    "apikey" => $PaymentAPIKey,
    "Key" => $paymentId,
    "KeyType" => "PaymentId"
];

$paymentStatus = checkPayment($checkData);

// The checkPayment returns an array like ["status" => "Captured"/"Failed", "id" => "paymentId"]
$status = $paymentStatus['status'];
$idFromApi = $paymentStatus['id'];

// Check current subscription in database
$subscription = selectDBNew("subscriptions", [$idFromApi], "`gatewayId` = ?", "");

if (!$subscription) {
    echo "<h1>Error: Subscription record not found.</h1>";
    exit;
}

$subId = $subscription[0]['id'];
$storeId = $subscription[0]['storeId'];
$packageId = $subscription[0]['packageId'];

if ($status == "Captured" || $status == "Success") {
    // 1. Update subscription status
    updateDB("subscriptions", ["status" => 1], "`id` = '{$subId}'");

    // 2. Set maintenance mode to OFF (3) for the store
    updateDB("stores", ["maintenanceMode" => 3], "`id` = '{$storeId}'");

    // 3. Optional: Add any success message logic here
    $title = "Payment Successful";
    $message = "Your subscription has been activated successfully.";
    $color = "green";
} else {
    // Update status to failed
    updateDB("subscriptions", ["status" => 2], "`id` = '{$subId}'");
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