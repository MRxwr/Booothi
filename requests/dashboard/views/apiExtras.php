<?php
// API for Extras (Add-ons) and Variants Management
// Action-based routing

if (!isset($storeId)) {
    outputError("Authentication required.");
}

$action = $_REQUEST["action"] ?? "";

switch ($action) {
    case "list":
        // List all non-deleted extras for the store
        $extras = selectDBNew("extras", "status = ? AND storeId = ?", ["0", $storeId], "ORDER BY id DESC");
        if ($extras) {
            foreach ($extras as &$extra) {
                // Decode variants if they exist
                $extra["variants"] = json_decode($extra["variants"], true);
            }
            outputData($extras);
        } else {
            outputData([]);
        }
        break;

    case "add":
        // Add a new extra
        if (!isset($_POST["enTitle"]) || !isset($_POST["arTitle"])) {
            outputError("Missing required fields.");
        }

        $insertData = [
            "enTitle"     => $_POST["enTitle"],
            "arTitle"     => $_POST["arTitle"],
            "price"       => $_POST["price"] ?? 0,
            "is_required" => $_POST["is_required"] ?? "0",
            "type"        => $_POST["type"] ?? "0",
            "priceBy"     => $_POST["priceBy"] ?? "0",
            "storeId"     => $storeId,
            "status"      => "0"
        ];

        if (insertDB("extras", $insertData)) {
            logStoreActivity($storeId, "Extra Added: " . $_POST["enTitle"]);
            outputData(["message" => "Extra added successfully."]);
        } else {
            outputError("Failed to add extra.");
        }
        break;

    case "update":
        // Update an existing extra
        if (!isset($_POST["id"]) || !isset($_POST["enTitle"])) {
            outputError("Missing required fields.");
        }

        $extraId = $_POST["id"];
        $updateData = [
            "enTitle"     => $_POST["enTitle"],
            "arTitle"     => $_POST["arTitle"],
            "price"       => $_POST["price"],
            "is_required" => $_POST["is_required"],
            "type"        => $_POST["type"],
            "priceBy"     => $_POST["priceBy"]
        ];

        if (updateDBNew("extras", $updateData, "id = ? AND storeId = ?", [$extraId, $storeId])) {
            logStoreActivity($storeId, "Extra Updated: " . $_POST["enTitle"]);
            outputData(["message" => "Extra updated successfully."]);
        } else {
            outputError("Failed to update extra or no changes made.");
        }
        break;

    case "delete":
        // Soft delete an extra
        if (!isset($_REQUEST["id"])) {
            outputError("Extra ID required.");
        }

        $extraId = $_REQUEST["id"];
        if (updateDBNew("extras", ["status" => "1"], "id = ? AND storeId = ?", [$extraId, $storeId])) {
            logStoreActivity($storeId, "Extra Deleted ID: " . $extraId);
            outputData(["message" => "Extra deleted successfully."]);
        } else {
            outputError("Failed to delete extra.");
        }
        break;

    // --- Variants Management (Inside the extras table 'variants' JSON column) ---

    case "listVariants":
        if (!isset($_REQUEST["id"])) {
            outputError("Extra ID required.");
        }
        $extra = selectDBNew("extras", "id = ? AND storeId = ?", [$_REQUEST["id"], $storeId], "");
        if ($extra) {
            $variants = json_decode($extra[0]["variants"], true) ?: ["enTitle" => [], "arTitle" => []];
            outputData($variants);
        } else {
            outputError("Extra not found.");
        }
        break;

    case "saveVariants":
        // Expects 'id' and 'enTitle' (array), 'arTitle' (array)
        if (!isset($_POST["id"]) || !isset($_POST["enTitle"]) || !is_array($_POST["enTitle"])) {
            outputError("Invalid variant data.");
        }

        $extraId = $_POST["id"];
        $variants = [
            "enTitle" => $_POST["enTitle"],
            "arTitle" => $_POST["arTitle"]
        ];

        $variantsJson = json_encode($variants, JSON_UNESCAPED_UNICODE);

        if (updateDBNew("extras", ["variants" => $variantsJson], "id = ? AND storeId = ?", [$extraId, $storeId])) {
            logStoreActivity($storeId, "Extra Variants Updated ID: " . $extraId);
            outputData(["message" => "Variants updated successfully."]);
        } else {
            outputError("Failed to update variants.");
        }
        break;

    default:
        outputError("Invalid action.");
        break;
}
