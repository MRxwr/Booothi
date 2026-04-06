<?php
// API for Extras (Add-ons) and Variants Management
// Action-based routing

if (!isset($storeId)) {
    echo outputError(["msg" => "Authentication required."]);die();
}

$action = $_REQUEST["action"] ?? "";

switch ($action) {
    case "list":
        // List all non-deleted extras for the store
        $extras = selectDB2New("id, enTitle, arTitle, price, is_required, type, priceBy, variants, hidden", "extras", ["0", $storeId], "status = ? AND storeId = ?", "id DESC");
        if ($extras) {
            foreach ($extras as &$extra) {
                // Decode variants if they exist
                $extra["variants"] = json_decode($extra["variants"], true);
            }
            echo outputData($extras);die();
        } else {
            echo outputData([]);die();
        }
        break;

    case "add":
        // Add a new extra
        if (!isset($_POST["enTitle"]) || !isset($_POST["arTitle"])) {
            echo outputError(["msg" => "Missing required fields."]);die();
        }

        $insertData = [
            "enTitle"     => $_POST["enTitle"],
            "arTitle"     => $_POST["arTitle"],
            "price"       => $_POST["price"] ?? 0,
            "is_required" => $_POST["is_required"] ?? "0",
            "type"        => $_POST["type"] ?? "0",
            "priceBy"     => $_POST["priceBy"] ?? "0",
            "storeId"     => $storeId,
            "hidden"      => "1", // Default to active/visible
            "status"      => "0"
        ];

        if (insertDB("extras", $insertData)) {
            logStoreActivity("Extras", "Extra Added: " . $_POST["enTitle"]);
            echo outputData(["msg" => "Extra added successfully."]);die();
        } else {
            echo outputError(["msg" => "Failed to add extra."]);die();
        }
        break;

    case "update":
        // Update an existing extra
        if (!isset($_POST["extraId"]) || !isset($_POST["enTitle"])) {
            echo outputError(["msg" => "Missing required fields."]);die();
        }

        $extraId = $_POST["extraId"];
        $updateData = [
            "enTitle"     => $_POST["enTitle"],
            "arTitle"     => $_POST["arTitle"],
            "price"       => $_POST["price"],
            "is_required" => $_POST["is_required"],
            "type"        => $_POST["type"],
            "priceBy"     => $_POST["priceBy"]
        ];

        if (updateDBNew("extras", $updateData, "id = ? AND storeId = ?", [$extraId, $storeId])) {
            logStoreActivity("Extras", "Extra Updated: " . $_POST["enTitle"]);
            echo outputData(["msg" => "Extra updated successfully."]);die();
        } else {
            echo outputError(["msg" => "Failed to update extra or no changes made."]);die();
        }
        break;

    case "delete":
        // Soft delete an extra
        if (!isset($_REQUEST["extraId"])) {
            echo outputError(["msg" => "Extra ID required."]);die();
        }

        $extraId = $_REQUEST["extraId"];
        if (updateDBNew("extras", ["status" => "1"], "id = ? AND storeId = ?", [$extraId, $storeId])) {
            logStoreActivity("Extras", "Extra Deleted ID: " . $extraId);
            echo outputData(["msg" => "Extra deleted successfully."]);die();
        } else {
            echo outputError(["msg" => "Failed to delete extra."]);die();
        }
        break;

    // --- Variants Management (Inside the extras table 'variants' JSON column) ---

    case "hide":
        if( !isset($_REQUEST["extraId"]) || empty($_REQUEST["extraId"]) ){
            echo outputError(["msg" => "Extra ID Is Required"]);die();  
        }
        $extra = selectDB("extras", "id = '{$_REQUEST["extraId"]}' AND storeId = '{$storeId}'");
        if( !$extra ){
            echo outputError(["msg" => "Extra not found"]);die();
        }
        $newHidden = ($extra[0]["hidden"] == 1) ? 2 : 1;
        if( updateDBNew("extras", ["hidden" => $newHidden], "id = ? AND storeId = ?", [$_REQUEST["extraId"], $storeId] ) ){
            logStoreActivity("Extras", "Toggled visibility for extra: " . $_REQUEST["extraId"]);
            echo outputData(["msg" => "Extra visibility updated"]);
        }else{
            echo outputError(["msg" => "Failed to update visibility"]);
        }
        break;
    case "listVariants":
        if (!isset($_REQUEST["extraId"])) {
            echo outputError(["msg" => "Extra ID required."]);die();
        }
        $extraId = $_REQUEST["extraId"];
        $extra = selectDBNew("extras", [$extraId, $storeId], "id = ? AND storeId = ?", "");
        if ($extra) {
            $variants = json_decode($extra[0]["variants"], true) ?: ["enTitle" => [], "arTitle" => []];
            echo outputData($variants);die();
        } else {
            echo outputError(["msg" => "Extra not found."]);die();
        }
        break;

    case "saveVariants":
        // Expects 'extraId' and 'enTitle' (array), 'arTitle' (array)
        if (!isset($_POST["extraId"]) || !isset($_POST["enTitle"]) || !is_array($_POST["enTitle"])) {
            echo outputError(["msg" => "Invalid variant data."]);die();
        }

        $extraId = $_POST["extraId"];
        $variants = [
            "enTitle" => $_POST["enTitle"],
            "arTitle" => $_POST["arTitle"]
        ];

        $variantsJson = json_encode($variants, JSON_UNESCAPED_UNICODE);

        if (updateDBNew("extras", ["variants" => $variantsJson], "id = ? AND storeId = ?", [$extraId, $storeId])) {
            logStoreActivity("Extras", "Extra Variants Updated ID: " . $extraId);
            echo outputData(["msg" => "Variants updated successfully."]);die();
        } else {
            echo outputError(["msg" => "Failed to update variants."]);die();
        }
        break;

    default:
        echo outputError(["msg" => "Invalid action."]);die();
        break;
}
