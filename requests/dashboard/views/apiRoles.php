<?php
// API for Role and Permission Management
// Action-based routing

if (!isset($storeId)) {
    outputError("Authentication required.");
}

$action = $_REQUEST["action"] ?? "";

switch ($action) {
    case "list":
        // List all non-deleted roles for the store
        $roles = selectDBNew("roles", "status = ? AND storeId = ?", ["0", $storeId], "ORDER BY id ASC");
        if ($roles) {
            foreach ($roles as &$role) {
                // Decode permissions (pages)
                $role["permissions"] = json_decode($role["pages"], true) ?: [];
            }
            outputData($roles);
        } else {
            outputData([]);
        }
        break;

    case "add":
        // Add a new role
        if (!isset($_POST["enTitle"]) || !isset($_POST["arTitle"])) {
            outputError("Missing required fields.");
        }

        $insertData = [
            "enTitle" => $_POST["enTitle"],
            "arTitle" => $_POST["arTitle"],
            "storeId" => $storeId,
            "hidden"  => "1", // Default to active/visible
            "status"  => "0"
        ];

        if (insertDB("roles", $insertData)) {
            logStoreActivity($storeId, "Role Added: " . $_POST["enTitle"]);
            outputData(["message" => "Role added successfully."]);
        } else {
            outputError("Failed to add role.");
        }
        break;

    case "update":
        // Update basic role info
        if (!isset($_POST["id"]) || !isset($_POST["enTitle"])) {
            outputError("Missing required fields.");
        }

        $roleId = $_POST["id"];
        $updateData = [
            "enTitle" => $_POST["enTitle"],
            "arTitle" => $_POST["arTitle"]
        ];

        if (updateDBNew("roles", $updateData, "id = ? AND storeId = ?", [$roleId, $storeId])) {
            logStoreActivity($storeId, "Role Updated: " . $_POST["enTitle"]);
            outputData(["message" => "Role updated successfully."]);
        } else {
            outputError("Failed to update role or no changes made.");
        }
        break;

    case "toggleStatus":
        // Toggle visibility (hidden = 1 is visible, 2 is hidden)
        if (!isset($_REQUEST["id"]) || !isset($_REQUEST["hidden"])) {
            outputError("Role ID and visibility status required.");
        }

        $roleId = $_REQUEST["id"];
        $hidden = $_REQUEST["hidden"]; // 1 or 2

        if (updateDBNew("roles", ["hidden" => $hidden], "id = ? AND storeId = ?", [$roleId, $storeId])) {
            logStoreActivity($storeId, "Role visibility updated ID: " . $roleId);
            outputData(["message" => "Role visibility updated."]);
        } else {
            outputError("Failed to update role visibility.");
        }
        break;

    case "delete":
        // Soft delete a role
        if (!isset($_REQUEST["id"])) {
            outputError("Role ID required.");
        }

        $roleId = $_REQUEST["id"];
        if (updateDBNew("roles", ["status" => "1"], "id = ? AND storeId = ?", [$roleId, $storeId])) {
            logStoreActivity($storeId, "Role Deleted ID: " . $roleId);
            outputData(["message" => "Role deleted successfully."]);
        } else {
            outputError("Failed to delete role.");
        }
        break;

    case "getPermissions":
        // Get list of all possible pages/permissions
        $pages = selectDBNew("pages", "status = ? AND hidden = ?", ["0", "1"], "ORDER BY id ASC");
        outputData($pages ?: []);
        break;

    case "savePermissions":
        // Save roles permissions (array of page IDs)
        if (!isset($_POST["id"]) || !isset($_POST["pages"])) {
            outputError("Role ID and pages (array) required.");
        }

        $roleId = $_POST["id"];
        $pagesJson = json_encode($_POST["pages"]);

        if (updateDBNew("roles", ["pages" => $pagesJson], "id = ? AND storeId = ?", [$roleId, $storeId])) {
            logStoreActivity($storeId, "Role Permissions Updated ID: " . $roleId);
            outputData(["message" => "Permissions updated successfully."]);
        } else {
            outputError("Failed to update permissions.");
        }
        break;

    default:
        outputError("Invalid action.");
        break;
}
