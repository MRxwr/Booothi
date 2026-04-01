<?php
// API for Employee Management
// Action-based routing

if (!isset($storeId)) {
    outputError("Authentication required.");
}

$action = $_REQUEST["action"] ?? "";

switch ($action) {
    case "list":
        // List all non-deleted employees for the store (excluding system admins/hidden accounts)
        $employees = selectDBNew("employees", "status = ? AND storeId = ? AND hidden != ?", ["0", $storeId, "1"], "ORDER BY id DESC");
        
        // Enrich with Shop and Role details
        if ($employees) {
            foreach ($employees as &$emp) {
                // Get Shop name
                $shop = selectDBNew("shops", "id = ?", [$emp["shopId"]], "");
                $emp["shopTitleEn"] = $shop ? $shop[0]["enTitle"] : "";
                $emp["shopTitleAr"] = $shop ? $shop[0]["arTitle"] : "";

                // Get Role name
                $role = selectDBNew("roles", "id = ?", [$emp["empType"]], "");
                $emp["roleTitleEn"] = $role ? $role[0]["enTitle"] : "";
                $emp["roleTitleAr"] = $role ? $role[0]["arTitle"] : "";
                
                // Remove sensitive info
                unset($emp["password"]);
            }
            outputData($employees);
        } else {
            outputData([]);
        }
        break;

    case "add":
        // Add a new employee
        if (!isset($_POST["fullName"]) || !isset($_POST["email"]) || !isset($_POST["password"])) {
            outputError("Missing required fields.");
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
            outputData(["message" => "Employee added successfully."]);
        } else {
            outputError("Failed to add employee.");
        }
        break;

    case "update":
        // Update an existing employee
        if (!isset($_POST["id"]) || !isset($_POST["fullName"]) || !isset($_POST["email"])) {
            outputError("Missing required fields.");
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
            outputData(["message" => "Employee updated successfully."]);
        } else {
            outputError("Failed to update employee or no changes made.");
        }
        break;

    case "toggleLock":
        // Lock/Unlock employee account (hidden=2 is locked, 0 is active)
        if (!isset($_REQUEST["id"]) || !isset($_REQUEST["locked"])) {
            outputError("Employee ID and lock status required.");
        }

        $empId = $_REQUEST["id"];
        $lockedValue = ($_REQUEST["locked"] == "1") ? "2" : "0";

        if (updateDBNew("employees", ["hidden" => $lockedValue], "id = ? AND storeId = ?", [$empId, $storeId])) {
            $statusText = ($_REQUEST["locked"] == "1") ? "Locked" : "Unlocked";
            logStoreActivity($storeId, "Employee account $statusText ID: " . $empId);
            outputData(["message" => "Employee account $statusText."]);
        } else {
            outputError("Failed to update employee status.");
        }
        break;

    case "delete":
        // Soft delete an employee
        if (!isset($_REQUEST["id"])) {
            outputError("Employee ID required.");
        }

        $empId = $_REQUEST["id"];
        if (updateDBNew("employees", ["status" => "1"], "id = ? AND storeId = ?", [$empId, $storeId])) {
            logStoreActivity($storeId, "Employee Deleted ID: " . $empId);
            outputData(["message" => "Employee deleted successfully."]);
        } else {
            outputError("Failed to delete employee.");
        }
        break;

    case "getRoles":
        // Get list of available roles for the dropdown
        $roles = selectDBNew("roles", "status = ? AND hidden = ?", ["0", "1"], "");
        outputData($roles ?: []);
        break;

    case "getShops":
        // Get list of available shops for the dropdown
        $shops = selectDBNew("shops", "status = ? AND storeId = ?", ["0", $storeId], "");
        outputData($shops ?: []);
        break;

    default:
        outputError("Invalid action.");
        break;
}
