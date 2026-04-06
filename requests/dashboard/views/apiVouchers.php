<?php
// API for Voucher Management
// Action-based routing

if (!isset($storeId)) {
    echo outputError("Authentication required.");die();
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
            echo outputError("Missing required fields.");die();
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
            logStoreActivity($storeId, "Voucher Added: " . $_POST["code"]);
            echo outputData(["message" => "Voucher added successfully."]);die();
        } else {
            echo outputError("Failed to add voucher.");die();
        }
        break;

    case "update":
        // Update an existing voucher
        if (!isset($_POST["id"]) || !isset($_POST["code"])) {
            echo outputError("Missing required fields.");die();
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
            echo outputData(["message" => "Voucher updated successfully."]);die();
        } else {
            echo outputError("Failed to update voucher or no changes made.");die();
        }
        break;

    case "delete":
        // Soft delete a voucher (status = 1)
        if (!isset($_REQUEST["id"])) {
            echo outputError("Voucher ID required.");die();
        }

        $voucherId = $_REQUEST["id"];
        if (updateDBNew("vouchers", ["status" => "1"], "id = ? AND storeId = ?", [$voucherId, $storeId])) {
            logStoreActivity($storeId, "Voucher Deleted ID: " . $voucherId);
            echo outputData(["message" => "Voucher deleted successfully."]);die();
        } else {
            echo outputError("Failed to delete voucher.");die();
        }
        break;

    case "hide":
        // Toggle voucher visibility (hidden=1 is visible, 2 is hidden)
        if (!isset($_REQUEST["id"]) || !isset($_REQUEST["hidden"])) {
            echo outputError("Voucher ID and hidden status required.");die();
        }

        $voucherId = $_REQUEST["id"];
        $hiddenValue = ($_REQUEST["hidden"] == "1") ? "2" : "1";

        if (updateDBNew("vouchers", ["hidden" => $hiddenValue], "id = ? AND storeId = ?", [$voucherId, $storeId])) {
            $statusText = ($_REQUEST["hidden"] == "1") ? "Hidden" : "Visible";
            logStoreActivity($storeId, "Voucher visibility toggled to $statusText ID: " . $voucherId);
            echo outputData(["message" => "Voucher visibility updated."]);die();
        } else {
            echo outputError("Failed to update voucher visibility.");die();
        }
        break;
    case "getItems":
        // Get specific items associated with a voucher (if type != 1)
        if (!isset($_REQUEST["id"])) {
            echo outputError("Voucher ID required.");die();
        }

        $voucher = selectDBNew("vouchers", [$_REQUEST["id"], $storeId], "id = ? AND storeId = ?", "");
        if ($voucher) {
            $items = json_decode($voucher[0]["items"], true) ?: [];
            echo outputData($items);die();
        } else {
            echo outputError("Voucher not found.");die();
        }
        break;

    case "saveItems":
        // Save items (JSON array) for a voucher
        if (!isset($_POST["id"]) || !isset($_POST["items"])) {
            echo outputError("Voucher ID and items (array) required.");die();
        }

        $voucherId = $_POST["id"];
        $itemsJson = json_encode($_POST["items"]);

        if (updateDBNew("vouchers", ["items" => $itemsJson], "id = ? AND storeId = ?", [$voucherId, $storeId])) {
            logStoreActivity($storeId, "Voucher Items Updated ID: " . $voucherId);
            echo outputData(["message" => "Voucher items updated successfully."]);die();
        } else {
            echo outputError("Failed to update voucher items.");die();
        }
        break;

    default:
        echo outputError("Invalid action.");die();
        break;
}
