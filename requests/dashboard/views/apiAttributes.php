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
        $attributes = selectDB2("`id`,`enTitle`,`arTitle`,`hidden`", "attributes", "status = '0' AND storeId = '{$storeId}' ORDER BY id DESC");
        if ($attributes) {
            echo outputData($attributes); die();
        } else {
            echo outputData([]); die();
        }
        break;

    case "add":
        // Add a new attribute
        if (!isset($_POST["enTitle"]) || !isset($_POST["arTitle"])) {
            echo outputError("Missing required fields."); die();
        }

        $insertData = [
            "enTitle" => $_POST["enTitle"],
            "arTitle" => $_POST["arTitle"],
            "storeId" => $storeId,
            "hidden" => "1", 
            "status"  => "0"
        ];

        if (insertDB("attributes", $insertData)) {
            logStoreActivity($storeId, "Attribute Added: " . $_POST["enTitle"]);
            echo outputData(["message" => "Attribute added successfully."]); die();
        } else {
            echo outputError("Failed to add attribute."); die();
        }
        break;

    case "update":
        // Update an existing attribute
        if (!isset($_POST["id"]) || !isset($_POST["enTitle"]) || !isset($_POST["arTitle"])) {
            echo outputError("Missing required fields."); die();
        }

        $attributeId = $_POST["attributeId"];
        $updateData = [
            "enTitle" => $_POST["enTitle"],
            "arTitle" => $_POST["arTitle"]
        ];

        if (updateDBNew("attributes", $updateData, "id = ? AND storeId = ?", [$attributeId, $storeId])) {
            logStoreActivity($storeId, "Attribute Updated: " . $_POST["enTitle"]);
            echo outputData(["message" => "Attribute updated successfully."]); die();
        } else {
            echo outputError("Failed to update attribute or no changes made."); die();
        }
        break;
    case "hide":
        if( !isset($_REQUEST["attributeId"]) || empty($_REQUEST["attributeId"]) ){
            echo outputError(array("msg" => "Attribute ID Is Required"));die();  
        }
        $attribute = selectDB("attributes", "id = '{$_REQUEST["attributeId"]}' AND storeId = '{$storeId}'");
        if( !$attribute ){
            echo outputError(array("msg" => "Attribute not found"));die();
        }
        $newHidden = ($attribute[0]["hidden"] == 1) ? 2 : 1;
        if( updateDBNew("attributes", array("hidden" => $newHidden), "id = ? AND storeId = ?", [$_REQUEST["attributeId"], $storeId] ) ){
            logStoreActivity("Attributes", "Toggled visibility for attribute: " . $_REQUEST["attributeId"]);
            echo outputData(array("msg" => "Attribute visibility updated"));
        }else{
            echo outputError(array("msg" => "Failed to update visibility"));
        }
        break;
    case "delete":
        // Soft delete an attribute (status = 1)
        if (!isset($_REQUEST["attributeId"])) {
            echo outputError("Attribute ID required."); die();
        }

        $attributeId = $_REQUEST["attributeId"];
        $updateData = ["status" => "1"];

        if (updateDBNew("attributes", $updateData, "id = ? AND storeId = ?", [$attributeId, $storeId])) {
            logStoreActivity($storeId, "Attribute Deleted ID: " . $attributeId);
            echo outputData(["message" => "Attribute deleted successfully."]); die();
        } else {
            echo outputError("Failed to delete attribute."); die();
        }
        break;

    default:
        echo outputError("Invalid action."); die();
        break;
}
