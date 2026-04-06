<?php
// API for Store Management (Superadmin)
// Actions: list, add, update, delete

$action = $_REQUEST["action"] ?? "";

switch ($action) {

    case "list":
        $stores = selectDB2New(
            "id, title, storeCode, email, phone, country, currency, language, maintenanceMode, status,
             logo, bgImage, sizeChartImage, theme, categoryView, productView, showCategoryTitle, showLogo,
             websiteColor, headerButton, shippingMethod, package, startDate, amount,
             giftCard, emailOpt, enableInvoiceImage, userDiscount, inStore, noAddress, noAddressDelivery,
             whatsappNoti, socialMedia, internationalDelivery, expressDelivery,
             enAbout, arAbout, enPrivacy, arPrivacy, enTerms, arTerms,
             paymentAPIKey, enDeliveryTime, arDeliveryTime",
            "stores",
            ["0"],
            "status = ?",
            "id DESC"
        );

        if ($stores) {
            foreach ($stores as &$store) {
                $store["enAbout"]    = urldecode($store["enAbout"]);
                $store["arAbout"]    = urldecode($store["arAbout"]);
                $store["enPrivacy"]  = urldecode($store["enPrivacy"]);
                $store["arPrivacy"]  = urldecode($store["arPrivacy"]);
                $store["enTerms"]    = urldecode($store["enTerms"]);
                $store["arTerms"]    = urldecode($store["arTerms"]);
                $store["socialMedia"]            = json_decode($store["socialMedia"], true) ?: [];
                $store["whatsappNoti"]           = json_decode($store["whatsappNoti"], true) ?: [];
                $store["internationalDelivery"]  = json_decode($store["internationalDelivery"], true) ?: [];
                $store["expressDelivery"]        = json_decode($store["expressDelivery"], true) ?: [];
            }
            unset($store);
        }

        echo outputData(["stores" => $stores ?: []]);die();
        break;

    case "add":
        if (empty($_POST["title"]) || empty($_POST["storeCode"])) {
            echo outputError(["msg" => "Title and store code are required."]);die();
        }

        // Check storeCode uniqueness
        $existingCode = selectDBNew("stores", [$_POST["storeCode"], "0"], "storeCode = ? AND status = ?", "");
        if ($existingCode) {
            echo outputError(["msg" => "Store code already exists."]);die();
        }

        $data = [
            "title"               => $_POST["title"] ?? "",
            "storeCode"           => $_POST["storeCode"] ?? "",
            "email"               => $_POST["email"] ?? "",
            "phone"               => $_POST["phone"] ?? "",
            "country"             => $_POST["country"] ?? "",
            "currency"            => $_POST["currency"] ?? "",
            "language"            => $_POST["language"] ?? "0",
            "maintenanceMode"     => $_POST["maintenanceMode"] ?? "3",
            "shippingMethod"      => $_POST["shippingMethod"] ?? "0",
            "paymentAPIKey"       => $_POST["paymentAPIKey"] ?? "",
            "package"             => $_POST["package"] ?? "0",
            "startDate"           => $_POST["startDate"] ?? "",
            "amount"              => $_POST["amount"] ?? "0",
            "giftCard"            => $_POST["giftCard"] ?? "0",
            "emailOpt"            => $_POST["emailOpt"] ?? "0",
            "enableInvoiceImage"  => $_POST["enableInvoiceImage"] ?? "0",
            "userDiscount"        => $_POST["userDiscount"] ?? "0",
            "inStore"             => $_POST["inStore"] ?? "0",
            "noAddress"           => $_POST["noAddress"] ?? "0",
            "noAddressDelivery"   => $_POST["noAddressDelivery"] ?? "0",
            "theme"               => $_POST["theme"] ?? "0",
            "categoryView"        => $_POST["categoryView"] ?? "0",
            "productView"         => $_POST["productView"] ?? "0",
            "showCategoryTitle"   => $_POST["showCategoryTitle"] ?? "0",
            "showLogo"            => $_POST["showLogo"] ?? "0",
            "websiteColor"        => $_POST["websiteColor"] ?? "#000000",
            "headerButton"        => $_POST["headerButton"] ?? "#000000",
            "enDeliveryTime"      => $_POST["enDeliveryTime"] ?? "",
            "arDeliveryTime"      => $_POST["arDeliveryTime"] ?? "",
            "enAbout"             => urlencode($_POST["enAbout"] ?? ""),
            "arAbout"             => urlencode($_POST["arAbout"] ?? ""),
            "enPrivacy"           => urlencode($_POST["enPrivacy"] ?? ""),
            "arPrivacy"           => urlencode($_POST["arPrivacy"] ?? ""),
            "enTerms"             => urlencode($_POST["enTerms"] ?? ""),
            "arTerms"             => urlencode($_POST["arTerms"] ?? ""),
            "status"              => "0",
        ];

        $socialMedia = $_POST["socialMedia"] ?? [];
        $data["socialMedia"] = is_array($socialMedia) ? json_encode($socialMedia) : $socialMedia;

        $internationalDelivery = $_POST["internationalDelivery"] ?? [];
        $data["internationalDelivery"] = is_array($internationalDelivery) ? json_encode($internationalDelivery) : $internationalDelivery;

        $expressDelivery = $_POST["expressDelivery"] ?? [];
        $data["expressDelivery"] = is_array($expressDelivery) ? json_encode($expressDelivery) : $expressDelivery;

        if (!insertDB("stores", $data)) {
            echo outputError(["msg" => "Could not add store, please try again."]);die();
        }

        // Fetch the newly created store ID for image uploads
        $newStore = selectDBNew("stores", [$_POST["storeCode"], "0"], "storeCode = ? AND status = ?", "id DESC");
        if ($newStore) {
            $newStoreId = $newStore[0]["id"];
            $imageUpdates = [];

            if (isset($_FILES["logo"]["tmp_name"]) && is_uploaded_file($_FILES["logo"]["tmp_name"])) {
                $uploaded = uploadImageToStoreFolder($_FILES["logo"]["tmp_name"], $newStoreId, "main");
                if ($uploaded) { $imageUpdates["logo"] = $uploaded; }
            }
            if (isset($_FILES["bgImage"]["tmp_name"]) && is_uploaded_file($_FILES["bgImage"]["tmp_name"])) {
                $uploaded = uploadImageToStoreFolder($_FILES["bgImage"]["tmp_name"], $newStoreId, "main");
                if ($uploaded) { $imageUpdates["bgImage"] = $uploaded; }
            }
            if (isset($_FILES["sizeChartImage"]["tmp_name"]) && is_uploaded_file($_FILES["sizeChartImage"]["tmp_name"])) {
                $uploaded = uploadImageToStoreFolder($_FILES["sizeChartImage"]["tmp_name"], $newStoreId, "main");
                if ($uploaded) { $imageUpdates["sizeChartImage"] = $uploaded; }
            }

            if (!empty($imageUpdates)) {
                updateDBNew("stores", $imageUpdates, "id = ?", [$newStoreId]);
            }
        }

        echo outputData(["msg" => "Store added successfully."]);die();
        break;

    case "update":
        if (empty($_POST["storeId"])) {
            echo outputError(["msg" => "Store ID is required."]);die();
        }

        $targetStoreId = intval($_POST["storeId"]);

        // Verify store exists and is active
        $existingStore = selectDBNew("stores", [$targetStoreId, "0"], "id = ? AND status = ?", "");
        if (!$existingStore) {
            echo outputError(["msg" => "Store not found."]);die();
        }

        // Check storeCode uniqueness if being changed
        if (!empty($_POST["storeCode"])) {
            $codeConflict = selectDBNew("stores", [$_POST["storeCode"], "0", $targetStoreId], "storeCode = ? AND status = ? AND id != ?", "");
            if ($codeConflict) {
                echo outputError(["msg" => "Store code already taken by another store."]);die();
            }
        }

        $data = [];

        $textFields = [
            "title", "storeCode", "email", "phone", "country", "currency",
            "language", "maintenanceMode", "shippingMethod", "paymentAPIKey",
            "package", "startDate", "amount", "giftCard", "emailOpt",
            "enableInvoiceImage", "userDiscount", "inStore", "noAddress",
            "noAddressDelivery", "theme", "categoryView", "productView",
            "showCategoryTitle", "showLogo", "websiteColor", "headerButton",
            "enDeliveryTime", "arDeliveryTime"
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

        // Handle file uploads
        if (isset($_FILES["logo"]["tmp_name"]) && is_uploaded_file($_FILES["logo"]["tmp_name"])) {
            $uploaded = uploadImageToStoreFolder($_FILES["logo"]["tmp_name"], $targetStoreId, "main");
            if ($uploaded) { $data["logo"] = $uploaded; }
        }
        if (isset($_FILES["bgImage"]["tmp_name"]) && is_uploaded_file($_FILES["bgImage"]["tmp_name"])) {
            $uploaded = uploadImageToStoreFolder($_FILES["bgImage"]["tmp_name"], $targetStoreId, "main");
            if ($uploaded) { $data["bgImage"] = $uploaded; }
        }
        if (isset($_FILES["sizeChartImage"]["tmp_name"]) && is_uploaded_file($_FILES["sizeChartImage"]["tmp_name"])) {
            $uploaded = uploadImageToStoreFolder($_FILES["sizeChartImage"]["tmp_name"], $targetStoreId, "main");
            if ($uploaded) { $data["sizeChartImage"] = $uploaded; }
        }

        if (empty($data)) {
            echo outputError(["msg" => "No fields to update."]);die();
        }

        if (updateDBNew("stores", $data, "id = ?", [$targetStoreId])) {
            echo outputData(["msg" => "Store updated successfully."]);die();
        } else {
            echo outputError(["msg" => "Could not update store, please try again."]);die();
        }
        break;

    case "delete":
        if (empty($_POST["storeId"])) {
            echo outputError(["msg" => "Store ID is required."]);die();
        }

        $targetStoreId = intval($_POST["storeId"]);

        $existingStore = selectDBNew("stores", [$targetStoreId, "0"], "id = ? AND status = ?", "");
        if (!$existingStore) {
            echo outputError(["msg" => "Store not found."]);die();
        }

        if (updateDBNew("stores", ["status" => "1"], "id = ?", [$targetStoreId])) {
            echo outputData(["msg" => "Store deleted successfully."]);die();
        } else {
            echo outputError(["msg" => "Could not delete store, please try again."]);die();
        }
        break;

    default:
        echo outputError(["msg" => "Invalid action."]);die();
        break;
}
?>
