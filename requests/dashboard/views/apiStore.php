<?php
// API for Store Management (Single Store Context)
// Actions: view, update

$action = $_REQUEST["action"] ?? "";

// For this API, we assume the store context is derived from the authenticated user's storeId
// Since this is likely called from a dashboard, $storeId should be available from index.php/checkToken()
if ( !isset($storeId) || empty($storeId) ){
    echo outputError(["msg" => "Store context not found."]);die();
}

switch ($action) {

    case "view":
        $store = selectDB2New(
            "id, title, storeCode, email, phone, country, currency, language, maintenanceMode, hidden,
             logo, bgImage, sizeChartImage, theme, categoryView, productView, showCategoryTitle, showLogo,
             websiteColor, headerButton, shippingMethod, package, startDate, amount,
             giftCard, emailOpt, enableInvoiceImage, userDiscount, inStore, noAddress, noAddressDelivery,
             whatsappNoti, socialMedia, internationalDelivery, expressDelivery,
             enAbout, arAbout, enPrivacy, arPrivacy, enTerms, arTerms,
             paymentAPIKey, enDeveliveryTime, arDeveliveryTime, paymentOptions",
            "stores",
            [$storeId],
            "id = ?",
            ""
        );

        if ($store && isset($store[0])) {
            $store = $store[0];
            $store["enAbout"]    = isset($store["enAbout"]) ? urldecode($store["enAbout"]) : "";
            $store["arAbout"]    = isset($store["arAbout"]) ? urldecode($store["arAbout"]) : "";
            $store["enPrivacy"]  = isset($store["enPrivacy"]) ? urldecode($store["enPrivacy"]) : "";
            $store["arPrivacy"]  = isset($store["arPrivacy"]) ? urldecode($store["arPrivacy"]) : "";
            $store["enTerms"]    = isset($store["enTerms"]) ? urldecode($store["enTerms"]) : "";
            $store["arTerms"]    = isset($store["arTerms"]) ? urldecode($store["arTerms"]) : "";
            $store["socialMedia"]            = (isset($store["socialMedia"]) && !empty($store["socialMedia"])) ? json_decode($store["socialMedia"], true) : [];
            $store["whatsappNoti"]           = (isset($store["whatsappNoti"]) && !empty($store["whatsappNoti"])) ? json_decode($store["whatsappNoti"], true) : [];
            $store["internationalDelivery"]  = (isset($store["internationalDelivery"]) && !empty($store["internationalDelivery"])) ? json_decode($store["internationalDelivery"], true) : [];
            $store["expressDelivery"]        = (isset($store["expressDelivery"]) && !empty($store["expressDelivery"])) ? json_decode($store["expressDelivery"], true) : [];
            $store["paymentOptions"]         = (isset($store["paymentOptions"]) && !empty($store["paymentOptions"])) ? json_decode($store["paymentOptions"], true) : [];
            
            echo outputData(["store" => $store]);die();
        } else {
            echo outputError(["msg" => "Store details not found."]);die();
        }
        break;

    case "update":
        $data = [];

        // Note: storeCode is intentionally omitted from update as per requirement
        $textFields = [
            "title", "email", "phone", "country", "currency",
            "language", "maintenanceMode", "shippingMethod", "paymentAPIKey",
            "package", "startDate", "amount", "giftCard", "emailOpt",
            "enableInvoiceImage", "userDiscount", "inStore", "noAddress",
            "noAddressDelivery", "theme", "categoryView", "productView",
            "showCategoryTitle", "showLogo", "websiteColor", "headerButton",
            "enDeveliveryTime", "arDeveliveryTime"
        ];

        foreach ($textFields as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = $_POST[$field];
            }
        }

        // URL-encode page content fields
        $pageFields = ["enAbout", "arAbout", "enPrivacy", "arPrivacy", "enTerms", "arTerms"];
        foreach ($pageFields as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = urlencode($_POST[$field]);
            }
        }

        if (isset($_POST["socialMedia"])) {
            $sm = $_POST["socialMedia"];
            $data["socialMedia"] = is_array($sm) ? json_encode($sm) : $sm;
        }
        if (isset($_POST["internationalDelivery"])) {
            $id = $_POST["internationalDelivery"];
            $data["internationalDelivery"] = is_array($id) ? json_encode($id) : $id;
        }
        if (isset($_POST["expressDelivery"])) {
            $ed = $_POST["expressDelivery"];
            $data["expressDelivery"] = is_array($ed) ? json_encode($ed) : $ed;
        }

        if (isset($_POST["paymentOptions"])) {
            $po = $_POST["paymentOptions"];
            $data["paymentOptions"] = is_array($po) ? json_encode($po) : $po;
        }

        // Handle file uploads
        if (isset($_FILES["logo"]["tmp_name"]) && is_uploaded_file($_FILES["logo"]["tmp_name"])) {
            $uploaded = uploadImageToStoreFolder($_FILES["logo"]["tmp_name"], $storeId, "main");
            if ($uploaded) { $data["logo"] = $uploaded; }
        }
        if (isset($_FILES["bgImage"]["tmp_name"]) && is_uploaded_file($_FILES["bgImage"]["tmp_name"])) {
            $uploaded = uploadImageToStoreFolder($_FILES["bgImage"]["tmp_name"], $storeId, "main");
            if ($uploaded) { $data["bgImage"] = $uploaded; }
        }
        if (isset($_FILES["sizeChartImage"]["tmp_name"]) && is_uploaded_file($_FILES["sizeChartImage"]["tmp_name"])) {
            $uploaded = uploadImageToStoreFolder($_FILES["sizeChartImage"]["tmp_name"], $storeId, "main");
            if ($uploaded) { $data["sizeChartImage"] = $uploaded; }
        }

        if (empty($data)) {
            echo outputError(["msg" => "No fields to update."]);die();
        }

        if (updateDBNew("stores", $data, "id = ?", [$storeId])) {
            echo outputData(["msg" => "Store updated successfully."]);die();
        } else {
            echo outputError(["msg" => "Could not update store, please try again."]);die();
        }
        break;

    default:
        echo outputError(["msg" => "Invalid action."]);die();
        break;
}
?>
