<?php
// API for POS Payments
// Web & App Compatible

if (!isset($storeId)) {
    echo outputError(["msg" => "Authentication required."]);die();
}

if (!isset($_REQUEST["action"]) || empty($_REQUEST["action"])) {
    echo outputError(["msg" => "Action is required"]);die();
}

$action = $_REQUEST["action"];

if ($action == "list") {
    // Custom formatted array with icons and specific IDs as requested
    $poArray = [
        [
            "isOn" => "1",
            "enTitle" => "Cash on Delivery",
            "arTitle" => "الدفع عند الاستلام", 
            "paymentId" => "10",
            "icon" => "fa fa-money-bill-wave"
        ],
        [
            "isOn" => "1",
            "enTitle" => "Link Payment",
            "arTitle" => "الدفع عبر الرابط",
            "paymentId" => "4",
            "icon" => "fa fa-link"
        ],
        [
            "isOn" => "1",
            "enTitle" => "Online Payment",
            "arTitle" => "الدفع عبر الإنترنت",
            "paymentId" => "1",
            "icon" => "fa fa-credit-card"
        ]
    ];

    echo outputData(["methods" => $poArray]);
    die();
}
