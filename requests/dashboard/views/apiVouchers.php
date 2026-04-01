<?php
// API for Voucher Management
// Action-based routing

if (!isset($storeId)) {
    outputError("Authentication required.");
}

$action = $_REQUEST["action"] ?? "";

switch ($action) {
    case "list":
        // List all non-deleted vouchers for the store
        $vouchers = selectDBNew("vouchers", "status = ? AND storeId = ?", ["0", $storeId], "ORDER BY id DESC");
        if ($vouchers) {
            outputData($vouchers);
        } else {
            outputData([]);
        }
        break;

    case "add":
        // Add a new voucher
        if (!isset($_POST["code"]) || !isset($_POST["type"]) || !isset($_POST["discount"])) {
            outputError("Missing required fields.");
        }

        $insertData = [
            "code"         => $_POST["code"],
            "type"         => $_POST["type"],
            "discountType" => $_POST["discountType"] ?? "1",
            "discount"     => $_POST["discount"],
            "startDate"    => $_POST["startDate"],
            "endDate"      => $_POST["endDate"],
            "storeId"      => $storeId,
            "status"       => "0"
        ];

        if (insertDB("vouchers", $insertData)) {
            logStoreActivity($storeId, "Voucher Added: " . $_POST["code"]);
            outputData(["message" => "Voucher added successfully."]);
        } else {
            outputError("Failed to add voucher.");
        }
        break;

    case "update":
        // Update an existing voucher
        if (!isset($_POST["id"]) || !isset($_POST["code"])) {
            outputError("Missing required fields.");
        }

        $voucherId = $_POST["id"];
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
            logStoreActivity($storeId, "Voucher Updated: " . $_POST["code"]);
            outputData(["message" => "Voucher updated successfully."]);
        } else {
            outputError("Failed to update voucher or no changes made.");
        }
        break;

    case "delete":
        // Soft delete a voucher (status = 1)
        if (!isset($_REQUEST["id"])) {
            outputError("Voucher ID required.");
        }

        $voucherId = $_REQUEST["id"];
        if (updateDBNew("vouchers", ["status" => "1"], "id = ? AND storeId = ?", [$voucherId, $storeId])) {
            logStoreActivity($storeId, "Voucher Deleted ID: " . $voucherId);
            outputData(["message" => "Voucher deleted successfully."]);
        } else {
            outputError("Failed to delete voucher.");
        }
        break;

    case "getItems":
        // Get specific items associated with a voucher (if type != 1)
        if (!isset($_REQUEST["id"])) {
            outputError("Voucher ID required.");
        }

        $voucher = selectDBNew("vouchers", "id = ? AND storeId = ?", [$_REQUEST["id"], $storeId], "");
        if ($voucher) {
            $items = json_decode($voucher[0]["items"], true) ?: [];
            outputData($items);
        } else {
            outputError("Voucher not found.");
        }
        break;

    case "saveItems":
        // Save items (JSON array) for a voucher
        if (!isset($_POST["id"]) || !isset($_POST["items"])) {
            outputError("Voucher ID and items (array) required.");
        }

        $voucherId = $_POST["id"];
        $itemsJson = json_encode($_POST["items"]);

        if (updateDBNew("vouchers", ["items" => $itemsJson], "id = ? AND storeId = ?", [$voucherId, $storeId])) {
            logStoreActivity($storeId, "Voucher Items Updated ID: " . $voucherId);
            outputData(["message" => "Voucher items updated successfully."]);
        } else {
            outputError("Failed to update voucher items.");
        }
        break;

    default:
        outputError("Invalid action.");
        break;
}
