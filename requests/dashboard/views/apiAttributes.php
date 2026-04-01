<?php
// API for Attribute Management
// Action-based routing

if (!isset($storeId)) {
    // Fallback if index.php hasn't defined $storeId
    outputError("Authentication required.");
}

$action = $_REQUEST["action"] ?? "";

switch ($action) {
    case "list":
        // List all non-deleted attributes for the store
        $attributes = selectDBNew("attributes", "status = ? AND storeId = ?", ["0", $storeId], "");
        if ($attributes) {
            outputData($attributes);
        } else {
            outputData([]);
        }
        break;

    case "add":
        // Add a new attribute
        if (!isset($_POST["enTitle"]) || !isset($_POST["arTitle"])) {
            outputError("Missing required fields.");
        }

        $insertData = [
            "enTitle" => $_POST["enTitle"],
            "arTitle" => $_POST["arTitle"],
            "storeId" => $storeId,
            "status"  => "0"
        ];

        if (insertDB("attributes", $insertData)) {
            logStoreActivity($storeId, "Attribute Added: " . $_POST["enTitle"]);
            outputData(["message" => "Attribute added successfully."]);
        } else {
            outputError("Failed to add attribute.");
        }
        break;

    case "update":
        // Update an existing attribute
        if (!isset($_POST["id"]) || !isset($_POST["enTitle"]) || !isset($_POST["arTitle"])) {
            outputError("Missing required fields.");
        }

        $attributeId = $_POST["id"];
        $updateData = [
            "enTitle" => $_POST["enTitle"],
            "arTitle" => $_POST["arTitle"]
        ];

        if (updateDBNew("attributes", $updateData, "id = ? AND storeId = ?", [$attributeId, $storeId])) {
            logStoreActivity($storeId, "Attribute Updated: " . $_POST["enTitle"]);
            outputData(["message" => "Attribute updated successfully."]);
        } else {
            outputError("Failed to update attribute or no changes made.");
        }
        break;

    case "delete":
        // Soft delete an attribute (status = 1)
        if (!isset($_REQUEST["id"])) {
            outputError("Attribute ID required.");
        }

        $attributeId = $_REQUEST["id"];
        $updateData = ["status" => "1"];

        if (updateDBNew("attributes", $updateData, "id = ? AND storeId = ?", [$attributeId, $storeId])) {
            logStoreActivity($storeId, "Attribute Deleted ID: " . $attributeId);
            outputData(["message" => "Attribute deleted successfully."]);
        } else {
            outputError("Failed to delete attribute.");
        }
        break;

    default:
        outputError("Invalid action.");
        break;
}
