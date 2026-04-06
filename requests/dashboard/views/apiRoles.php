<?php
// API for Role and Permission Management
// Action-based routing

if (!isset($storeId)) {
    echo outputError(["msg" => "Authentication required."]);die();
}

$action = $_REQUEST["action"] ?? "";

switch ($action) {
    case "list":
        // List all non-deleted roles for the store
        $roles = selectDB2("id, enTitle, arTitle, pages, hidden", "roles", "status = '0' AND storeId = '{$storeId}'"); 
        if ($roles) {
            foreach ($roles as &$role) {
                // Decode permissions (pages)
                $role["permissions"] = json_decode($role["pages"], true) ?: [];
                unset($role["pages"]); // Remove raw pages data
            }
            echo outputData($roles); die();
        } else {
            echo outputData([]); die();
        }
        break;

    case "add":
        // Add a new role
        if (!isset($_POST["enTitle"]) || !isset($_POST["arTitle"])) {
            echo outputError(["msg" => "Missing required fields."]); die();
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
            echo outputData(["message" => "Role added successfully."]); die();
        } else {
            echo outputError(["msg" => "Failed to add role."]); die();
        }
        break;

    case "update":
        // Update basic role info
        if (!isset($_POST["roleId"]) || !isset($_POST["enTitle"])) {
            echo outputError(["msg" => "Missing required fields."]); die();
        }

        $roleId = $_POST["roleId"];
        $updateData = [
            "enTitle" => $_POST["enTitle"],
            "arTitle" => $_POST["arTitle"]
        ];

        if (updateDBNew("roles", $updateData, "id = ? AND storeId = ?", [$roleId, $storeId])) {
            logStoreActivity($storeId, "Role Updated: " . $_POST["enTitle"]);
            echo outputData(["message" => "Role updated successfully."]); die();
        } else {
            echo outputError(["msg" => "Failed to update role or no changes made."]); die();
        }
        break;

    case "hide":
       if( !isset($_REQUEST["roleId"]) || empty($_REQUEST["roleId"]) ){
            echo outputError(["msg" => "Role ID Is Required"]);die();  
        }
        $role = selectDB("roles", "id = '{$_REQUEST["roleId"]}' AND storeId = '{$storeId}'");
        if( !$role ){
            echo outputError(["msg" => "Role not found"]);die();
        }
        $newHidden = ($role[0]["hidden"] == 1) ? 2 : 1;
        if( updateDBNew("roles", array("hidden" => $newHidden), "id = ? AND storeId = ?", [$_REQUEST["roleId"], $storeId] ) ){
            logStoreActivity("Roles", "Toggled visibility for role: " . $_REQUEST["roleId"]);
            echo outputData(array("msg" => "Role visibility updated")); die();
        }else{
            echo outputError(array("msg" => "Failed to update visibility")); die();
        }
        break;

    case "delete":
        // Soft delete a role
        if (!isset($_REQUEST["roleId"])) {
            echo outputError("Role ID required."); die();
        }

        $roleId = $_REQUEST["roleId"];
        if (updateDBNew("roles", ["status" => "1"], "id = ? AND storeId = ?", [$roleId, $storeId])) {
            logStoreActivity($storeId, "Role Deleted ID: " . $roleId);
            echo outputData(["message" => "Role deleted successfully."]); die();
        } else {
            echo outputError(["msg" => "Failed to delete role."]); die();
        }
        break;

    case "getPermissions":
        // Get list of all possible pages/permissions
        $pages = selectDB2("id, enTitle, arTitle", "pages", "status = '0' AND hidden = '1' ORDER BY rank ASC");
        echo outputData($pages ?: []); die();
        break;

    case "savePermissions":
        // Save roles permissions (array of page IDs)
        if (!isset($_POST["roleId"]) || !isset($_POST["pages"])) {
            echo outputError(["msg" => "Role ID and pages (array) required."]); die();
        }

        $roleId = $_POST["roleId"];
        $pagesJson = json_encode($_POST["pages"]);

        if (updateDBNew("roles", ["pages" => $pagesJson], "id = ? AND storeId = ?", [$roleId, $storeId])) {
            logStoreActivity($storeId, "Role Permissions Updated ID: " . $roleId);
            echo outputData(["message" => "Permissions updated successfully."]); die();
        } else {
            echo outputError(["msg" => "Failed to update permissions."]); die();
        }
        break;

    default:
        echo outputError(["msg" => "Invalid action."]); die();
        break;
}
