<?php
// API for Banner Management
// Action-based routing

if (!isset($storeId)) {
    outputError("Authentication required.");
}

$action = $_REQUEST["action"] ?? "";

switch ($action) {
    case "list":
        // List all non-deleted banners for the store, ordered by rank
        $banners = selectDBNew("banner",["0", $storeId], "status = ? AND storeId = ?", "`rank` ASC");
        if ($banners) {
            outputData($banners);
        } else {
            outputData([]);
        }
        break;

    case "add":
        // Add a new banner
        if (!isset($_POST["title"]) || !isset($_POST["link"])) {
            outputError("Missing required fields.");
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
            outputData(["message" => "Banner added successfully."]);
        } else {
            outputError("Failed to add banner.");
        }
        break;

    case "update":
        // Update an existing banner
        if (!isset($_POST["id"]) || !isset($_POST["title"]) || !isset($_POST["link"])) {
            outputError("Missing required fields.");
        }

        $bannerId = $_POST["id"];
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
            outputData(["message" => "Banner updated successfully."]);
        } else {
            outputError("Failed to update banner or no changes made.");
        }
        break;

    case "toggleStatus":
        // Show/Hide banner (hidden = 1 is visible, 2 is hidden)
        if (!isset($_REQUEST["id"]) || !isset($_REQUEST["hidden"])) {
            outputError("Banner ID and visibility status required.");
        }

        $bannerId = $_REQUEST["id"];
        $hidden = $_REQUEST["hidden"]; // 1 or 2

        if (updateDBNew("banner", ["hidden" => $hidden], "id = ? AND storeId = ?", [$bannerId, $storeId])) {
            $statusText = ($hidden == "1") ? "Shown" : "Hidden";
            logStoreActivity($storeId, "Banner $statusText ID: " . $bannerId);
            outputData(["message" => "Banner status updated to $statusText."]);
        } else {
            outputError("Failed to update banner visibility.");
        }
        break;

    case "delete":
        // Soft delete a banner (status = 1)
        if (!isset($_REQUEST["id"])) {
            outputError("Banner ID required.");
        }

        $bannerId = $_REQUEST["id"];
        if (updateDBNew("banner", ["status" => "1"], "id = ? AND storeId = ?", [$bannerId, $storeId])) {
            logStoreActivity($storeId, "Banner Deleted ID: " . $bannerId);
            outputData(["message" => "Banner deleted successfully."]);
        } else {
            outputError("Failed to delete banner.");
        }
        break;

    case "updateRank":
        // Update rank for multiple banners
        if (!isset($_POST["id"]) || !isset($_POST["rank"]) || !is_array($_POST["id"])) {
            outputError("Invalid rank data.");
        }

        reset($dbconnect); // Ensure we are ready for multiple updates
        $successCount = 0;
        for ($i = 0; $i < count($_POST["id"]); $i++) {
            $id = $_POST["id"][$i];
            $rank = $_POST["rank"][$i];
            if (updateDBNew("banner", ["rank" => $rank], "id = ? AND storeId = ?", [$id, $storeId])) {
                $successCount++;
            }
        }

        logStoreActivity($storeId, "Banner Ranks Updated ($successCount items)");
        outputData(["message" => "Ranks updated successfully for $successCount items."]);
        break;

    default:
        outputError("Invalid action.");
        break;
}
