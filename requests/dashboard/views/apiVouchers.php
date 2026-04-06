<?php
// API for Voucher Management
// Action-based routing

if (!isset($storeId)) {
    echo outputError(["msg" => "Authentication required."]);die();
}

$action = $_REQUEST["action"] ?? "";

switch ($action) {
    case "list":
        // List all non-deleted vouchers for the store
        $vouchers = selectDB2New("id, code, type, discountType, discount, startDate, endDate, items, hidden", "vouchers", ["0", $storeId], "status = ? AND storeId = ?", "id DESC");
        if ($vouchers) {
            echo outputData($vouchers);die();
        } else {
            echo outputData([]);die();
        }
        break;

    case "add":
        // Add a new voucher
        if (!isset($_POST["code"]) || !isset($_POST["type"]) || !isset($_POST["discount"])) {
            echo outputError(["msg" => "Missing required fields."]);die();
        }

        $insertData = [
            "code"         => $_POST["code"],
            "type"         => $_POST["type"],
            "discountType" => $_POST["discountType"] ?? "1",
            "discount"     => $_POST["discount"],
            "startDate"    => $_POST["startDate"],
            "endDate"      => $_POST["endDate"],
            "storeId"      => $storeId,
            "hidden"       => "1", // Default to active/visible
            "status"       => "0"
        ];

        if (insertDB("vouchers", $insertData)) {
            logStoreActivity("Vouchers", "Voucher Added: " . $_POST["code"]);
            echo outputData(["msg" => "Voucher added successfully."]);die();
        } else {
            echo outputError(["msg" => "Failed to add voucher."]);die();
        }
        break;

    case "update":
        // Update an existing voucher
        if (!isset($_POST["voucherId"]) || !isset($_POST["code"])) {
            echo outputError(["msg" => "Missing required fields."]);die();
        }

        $voucherId = $_POST["voucherId"];
        $updateData = [
            "code"         => $_POST["code"],
            "type"         => $_POST["type"],
            "discountType" => $_POST["discountType"],
            "discount"     => $_POST["discount"],
            "startDate"    => $_POST["startDate"],
            "endDate"      => $_POST["endDate"]
        ];

        // If type is 1 (Total), items should be cleared as per blade logic
        if ($updateData["type"] == "1") {
            $updateData["items"] = "";
        }

        if (updateDBNew("vouchers", $updateData, "id = ? AND storeId = ?", [$voucherId, $storeId])) {
            logStoreActivity("Vouchers", "Voucher Updated: " . $_POST["code"]);
            echo outputData(["msg" => "Voucher updated successfully."]);die();
        } else {
            echo outputError(["msg" => "Failed to update voucher or no changes made."]);die();
        }
        break;

    case "delete":
        // Soft delete a voucher (status = 1)
        if (!isset($_REQUEST["voucherId"])) {
            echo outputError(["msg" => "Voucher ID required."]);die();
        }

        $voucherId = $_REQUEST["voucherId"];
        if (updateDBNew("vouchers", ["status" => "1"], "id = ? AND storeId = ?", [$voucherId, $storeId])) {
            logStoreActivity($storeId, "Voucher Deleted ID: " . $voucherId);
            echo outputData(["msg" => "Voucher deleted successfully."]);die();
        } else {
            echo outputError(["msg" => "Failed to delete voucher."]);die();
        }
        break;

    case "hide":
        // Toggle voucher visibility (hidden=1 is visible, 2 is hidden)
        if (!isset($_REQUEST["voucherId"]) ) {
            echo outputError(["msg" => "Voucher ID required."]);die();
        }

        $voucherId = $_REQUEST["voucherId"];
        $voucher = selectDBNew("vouchers", [$voucherId, $storeId], "id = ? AND storeId = ?", "");
        if (!$voucher) {
            echo outputError(["msg" => "Voucher not found."]);die();
        }
        $hiddenValue = ($voucher[0]["hidden"] == "1") ? "2" : "1";
        if (updateDBNew("vouchers", ["hidden" => $hiddenValue], "id = ? AND storeId = ?", [$voucherId, $storeId])) {
            $statusText = ($hiddenValue == "2") ? "Hidden" : "Visible";
            logStoreActivity("Vouchers", "Voucher visibility toggled to $statusText ID: " . $voucherId);
            echo outputData(["msg" => "Voucher visibility updated."]);die();
        } else {
            echo outputError(["msg" => "Failed to update voucher visibility."]);die();
        }
        break;
    case "getItems":
        // Get specific items associated with a voucher (if type != 1) with full product details
        if (!isset($_REQUEST["voucherId"])) {
            echo outputError(["msg" => "Voucher ID required."]);die();
        }

        $voucher = selectDBNew("vouchers", [$_REQUEST["voucherId"], $storeId], "id = ? AND storeId = ?", "");
        if (!$voucher) {
            echo outputError(["msg" => "Voucher not found."]);die();
        }

        $itemIds = json_decode($voucher[0]["items"], true) ?: [];
        
        if (empty($itemIds)) {
            echo outputData([]);die();
        }

        // Sanitize IDs and build IN clause
        $sanitizedIds = array_map('intval', $itemIds);
        $idsList = implode(',', $sanitizedIds);
        
        // Fetch full product details
        $sql = "SELECT p.id, p.enTitle, p.arTitle,
                (SELECT i.imageurl FROM images i WHERE i.productId = p.id ORDER BY i.id ASC LIMIT 1) as image
                FROM products p 
                WHERE p.id IN ({$idsList}) AND p.storeId = '{$storeId}' AND p.status = '0'
                ORDER BY p.id DESC";
        
        $products = queryDB($sql);
        echo outputData($products ?: []);die();
        break;

    case "saveItems":
        // Save items (JSON array) for a voucher
        if (!isset($_POST["voucherId"]) || !isset($_POST["items"])) {
            echo outputError(["msg" => "Voucher ID and items (array) required."]);die();
        }

        $voucherId = $_POST["voucherId"];
        $itemsJson = json_encode($_POST["items"]);

        if (updateDBNew("vouchers", ["items" => $itemsJson], "id = ? AND storeId = ?", [$voucherId, $storeId])) {
            logStoreActivity("Vouchers", "Voucher Items Updated ID: " . $voucherId);
            echo outputData(["msg" => "Voucher items updated successfully."]);die();
        } else {
            echo outputError(["msg" => "Failed to update voucher items."]);die();
        }
        break;

    default:
        echo outputError(["msg" => "Invalid action."]);die();
        break;
}
