<?php
// API for Banner Management
// Action-based routing

if (!isset($storeId)) {
    echo outputError(["msg" => "Authentication required."]);die();
}

$action = $_REQUEST["action"] ?? "";

switch ($action) {
    case "list":
        // List all non-deleted banners for the store, ordered by rank
        $banners = selectDB2("`id`,`title`,`link`,`image`, `hidden`", "banner", "status = '0' AND storeId = '{$storeId}' ORDER BY rank ASC");
        if ($banners) {
            echo outputData($banners); die();
        } else {
            echo outputData([]); die();
        }
        break;

    case "add":
        // Add a new banner
        if (!isset($_POST["title"]) || !isset($_POST["link"])) {
            echo outputError(["msg" => "Missing required fields."]); die();
        }

        $insertData = [
            "title"   => $_POST["title"],
            "link"    => $_POST["link"],
            "hidden"  => $_POST["hidden"] ?? "1",
            "storeId" => $storeId,
            "status"  => "0"
        ];

        // Handle Image Upload
        if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            $insertData["image"] = uploadImageToStoreFolder($_FILES['image']['tmp_name'], $storeId, "banners");
        } else {
            $insertData["image"] = "";
        }

        if (insertDB("banner", $insertData)) {
            logStoreActivity($storeId, "Banner Added: " . $_POST["title"]);
            echo outputData(["message" => "Banner added successfully."]); die();
        } else {
            echo outputError(["msg" => "Failed to add banner."]); die();
        }
        break;

    case "update":
        // Update an existing banner
        if (!isset($_POST["bannerId"]) || !isset($_POST["title"]) || !isset($_POST["link"])) {
            echo outputError(["msg" => "Missing required fields."]); die();
        } 

        $bannerId = $_POST["bannerId"];
        $updateData = [
            "title"  => $_POST["title"],
            "link"   => $_POST["link"],
            "hidden" => $_POST["hidden"] ?? "1"
        ];

        // Handle Image Upload (Optional on update)
        if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            $updateData["image"] = uploadImageToStoreFolder($_FILES['image']['tmp_name'], $storeId, "banners");
        }

        if (updateDBNew("banner", $updateData, "id = ? AND storeId = ?", [$bannerId, $storeId])) {
            logStoreActivity($storeId, "Banner Updated: " . $_POST["title"]);
            echo outputData(["message" => "Banner updated successfully."]); die();
        } else {
            echo outputError(["msg" => "Failed to update banner or no changes made."]); die();
        }
        break;

    case "hide":
        if( !isset($_REQUEST["bannerId"]) || empty($_REQUEST["bannerId"]) ){
            echo outputError(["msg" => "Banner ID Is Required"]);die();  
        }
        $banner = selectDB("banner", "id = '{$_REQUEST["bannerId"]}' AND storeId = '{$storeId}'");
        if( !$banner ){
            echo outputError(["msg" => "Banner not found"]);die();
        }
        $newHidden = ($banner[0]["hidden"] == 1) ? 2 : 1;
        if( updateDBNew("banner", array("hidden" => $newHidden), "id = ? AND storeId = ?", [$_REQUEST["bannerId"], $storeId] ) ){
            logStoreActivity("Banners", "Toggled visibility for banner: " . $_REQUEST["bannerId"]);
            echo outputData(array("msg" => "Banner visibility updated"));
        }else{
            echo outputError(["msg" => "Failed to update visibility"]);
        }
        break;

    case "delete":
        // Soft delete a banner (status = 1)
        if (!isset($_REQUEST["bannerId"])) {
            echo outputError(["msg" => "Banner ID required."]); die();
        }

        $bannerId = $_REQUEST["bannerId"];
        if (updateDBNew("banner", ["status" => "1"], "id = ? AND storeId = ?", [$bannerId, $storeId])) {
            logStoreActivity($storeId, "Banner Deleted ID: " . $bannerId);
            echo outputData(["message" => "Banner deleted successfully."]); die();
        } else {
            echo outputError(["msg" => "Failed to delete banner."]); die();
        }
        break;

    case "updateRank":
        // Update rank for multiple banners
        if (!isset($_POST["bannerId"]) || !isset($_POST["rank"]) || !is_array($_POST["bannerId"])) {
            echo outputError(["msg" => "Invalid rank data."]); die();
        }

        reset($dbconnect); // Ensure we are ready for multiple updates
        $successCount = 0;
        for ($i = 0; $i < count($_POST["bannerId"]); $i++) {
            $id = $_POST["bannerId"][$i];
            $rank = $_POST["rank"][$i];
            if (updateDBNew("banner", ["rank" => $rank], "id = ? AND storeId = ?", [$id, $storeId])) {
                $successCount++;
            }
        }

        logStoreActivity($storeId, "Banner Ranks Updated ($successCount items)");
        echo outputData(["message" => "Ranks updated successfully for $successCount items."]); die();
        break;

    default:
        echo outputError(["msg" => "Invalid action."]);die();
        break;
}
