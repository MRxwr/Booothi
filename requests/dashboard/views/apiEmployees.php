<?php
// API for Employee Management
// Action-based routing

if (!isset($storeId)) {
    echo outputError(["msg" => "Authentication required."]);die();
}

$action = $_REQUEST["action"] ?? "";

switch ($action) {
    case "list":
        // List all non-deleted employees for the store (excluding system admins/hidden accounts)
        $employees = selectDBNew("employees", ["0", $storeId, "1"], "status = ? AND storeId = ? AND hidden != ?", "id DESC");
        
        // Enrich with Shop and Role details
        if ($employees) {
            foreach ($employees as &$emp) {
                // Get Shop name
                $shop = selectDBNew("shops", [$emp["shopId"]], "id = ?", "");
                $emp["shopTitleEn"] = $shop ? $shop[0]["enTitle"] : "";
                $emp["shopTitleAr"] = $shop ? $shop[0]["arTitle"] : "";

                // Get Role name
                $role = selectDBNew("roles", [$emp["empType"]], "id = ?", "");
                $emp["roleTitleEn"] = $role ? $role[0]["enTitle"] : "";
                $emp["roleTitleAr"] = $role ? $role[0]["arTitle"] : "";
                
                // Remove sensitive info
                unset($emp["password"]);
            }
            echo outputData($employees);die();
        } else {
            echo outputData([]);die();
        }
        break;

    case "add":
        // Add a new employee
        if (!isset($_POST["fullName"]) || !isset($_POST["email"]) || !isset($_POST["password"])) {
            echo outputError(["msg" => "Missing required fields."]);die();
        }

        $insertData = [
            "fullName" => $_POST["fullName"],
            "email"    => $_POST["email"],
            "phone"    => $_POST["phone"] ?? "",
            "password" => sha1($_POST["password"]),
            "empType"  => $_POST["empType"] ?? "0",
            "shopId"   => $_POST["shopId"] ?? "0",
            "storeId"  => $storeId,
            "status"   => "0",
            "hidden"   => "0" 
        ];

        if (insertDB("employees", $insertData)) {
            logStoreActivity($storeId, "Employee Added: " . $_POST["fullName"]);
            echo outputData(["message" => "Employee added successfully."]);die();
        } else {
            echo outputError(["msg" => "Failed to add employee."]);die();
        }
        break;

    case "update":
        // Update an existing employee
        if (!isset($_POST["id"]) || !isset($_POST["fullName"]) || !isset($_POST["email"])) {
            echo outputError(["msg" => "Missing required fields."]);die();
        }

        $empId = $_POST["id"];
        $updateData = [
            "fullName" => $_POST["fullName"],
            "email"    => $_POST["email"],
            "phone"    => $_POST["phone"],
            "empType"  => $_POST["empType"],
            "shopId"   => $_POST["shopId"]
        ];

        // Handle password update if provided
        if (!empty($_POST["password"])) {
            $updateData["password"] = sha1($_POST["password"]);
        }

        if (updateDBNew("employees", $updateData, "id = ? AND storeId = ?", [$empId, $storeId])) {
            logStoreActivity($storeId, "Employee Updated: " . $_POST["fullName"]);
            echo outputData(["message" => "Employee updated successfully."]);die();
        } else {
            echo outputError(["msg" => "Failed to update employee or no changes made."]);die();
        }
        break;

    case "toggleLock":
        // Lock/Unlock employee account (hidden=2 is locked, 0 is active)
        if (!isset($_REQUEST["id"]) || !isset($_REQUEST["locked"])) {
            echo outputError(["msg" => "Employee ID and lock status required."]);die();
        }

        $empId = $_REQUEST["id"];
        $lockedValue = ($_REQUEST["locked"] == "1") ? "2" : "0";

        if (updateDBNew("employees", ["hidden" => $lockedValue], "id = ? AND storeId = ?", [$empId, $storeId])) {
            $statusText = ($_REQUEST["locked"] == "1") ? "Locked" : "Unlocked";
            logStoreActivity($storeId, "Employee account $statusText ID: " . $empId);
            echo outputData(["message" => "Employee account $statusText."]);die();
        } else {
            echo outputError(["msg" => "Failed to update employee status."]);die();
        }
        break;

    case "delete":
        // Soft delete an employee
        if (!isset($_REQUEST["id"])) {
            echo outputError(["msg" => "Employee ID required."]);die();
        }

        $empId = $_REQUEST["id"];
        if (updateDBNew("employees", ["status" => "1"], "id = ? AND storeId = ?", [$empId, $storeId])) {
            logStoreActivity($storeId, "Employee Deleted ID: " . $empId);
            echo outputData(["message" => "Employee deleted successfully."]);die();
        } else {
            echo outputError(["msg" => "Failed to delete employee."]);die();
        }
        break;

    case "getRoles":
        // Get list of available roles for the dropdown
        $roles = selectDBNew("roles", ["0", "1"], "status = ? AND hidden = ?", "");
        echo outputData($roles ?: []);die();
        break;

    case "getShops":
        // Get list of available shops for the dropdown
        $shops = selectDBNew("shops", ["0", $storeId], "status = ? AND storeId = ?", "");
        echo outputData($shops ?: []);die();
        break;

    default:
        echo outputError(["msg" => "Invalid action."]);die();
        break;
}
