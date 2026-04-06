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
        $employees = selectDB2New("id, fullName, email, phone, empType, shopId, hidden", "employees", ["0", $storeId], "is_deleted = ? AND storeId = ?", "id DESC");
        
        // Enrich with Shop and Role details
        if ($employees) {
            foreach ($employees as &$emp) {
                // Get Shop name
                $shop = selectDB2New("id, enTitle, arTitle", "shops", [$emp["shopId"]], "id = ?", "") ?: [];
                $emp["shopTitleEn"] = $shop ? $shop[0]["enTitle"] : "";
                $emp["shopTitleAr"] = $shop ? $shop[0]["arTitle"] : "";

                // Get Role name
                $role = selectDB2New("id, enTitle, arTitle", "roles", [$emp["empType"]], "id = ?", "") ?: [];
                $emp["roleTitleEn"] = $role ? $role[0]["enTitle"] : "";
                $emp["roleTitleAr"] = $role ? $role[0]["arTitle"] : "";
                
                // Remove sensitive info
                unset($emp["password"]);
            }
        }
        
        // Get all available shops and roles for dropdowns
        $shops = selectDB2New("id, enTitle, arTitle", "shops", ["0", $storeId], "status = ? AND storeId = ?", "") ?: [];
        $roles = selectDB2New("id, enTitle, arTitle", "roles", ["0", "1", $storeId], "status = ? AND hidden = ? AND storeId = ?", "") ?: [];
        
        echo outputData([
            "employees" => $employees ?: [],
            "shops" => $shops ?: [],
            "roles" => $roles ?: []
        ]);die();
        break;

    case "add":
        // Add a new employee
        if (!isset($_POST["fullName"]) || !isset($_POST["email"]) || !isset($_POST["password"])) {
            echo outputError(["msg" => "Missing required fields."]);die();
        }

        // Check if email already exists (only among non-deleted employees)
        $existingEmail = selectDBNew("employees", [$_POST["email"], $storeId, "0"], "email = ? AND storeId = ? AND is_deleted = ?", "");
        if ($existingEmail) {
            echo outputError(["msg" => "Email already exists."]);
            die();
        }

        // Check if phone already exists (if provided, only among non-deleted employees)
        if (!empty($_POST["phone"])) {
            $existingPhone = selectDBNew("employees", [$_POST["phone"], $storeId, "0"], "phone = ? AND storeId = ? AND is_deleted = ?", "");
            if ($existingPhone) {
                echo outputError(["msg" => "Phone number already exists."]);
                die();
            }
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
            "hidden"   => "1",
            "is_deleted" => "0"
        ];

        if (insertDB("employees", $insertData)) {
            logStoreActivity($storeId, "Employee Added: " . $_POST["fullName"]);
            echo outputData(["msg" => "Employee added successfully."]);die();
        } else {
            echo outputError(["msg" => "Failed to add employee."]);die();
        }
        break;

    case "update":
        // Update an existing employee
        if (!isset($_POST["employeeId"]) || !isset($_POST["fullName"]) || !isset($_POST["email"])) {
            echo outputError(["msg" => "Missing required fields."]);die();
        }

        $empId = $_POST["employeeId"];

        // Check if email already exists for another non-deleted employee
        $existingEmail = selectDBNew("employees", [$_POST["email"], $storeId, "0", $empId], "email = ? AND storeId = ? AND is_deleted = ? AND id != ?", "");
        if ($existingEmail) {
            echo outputError(["msg" => "Email already exists."]);
            die();
        }

        // Check if phone already exists for another non-deleted employee (if provided)
        if (!empty($_POST["phone"])) {
            $existingPhone = selectDBNew("employees", [$_POST["phone"], $storeId, "0", $empId], "phone = ? AND storeId = ? AND is_deleted = ? AND id != ?", "");
            if ($existingPhone) {
                echo outputError(["msg" => "Phone number already exists."]);
                die();
            }
        }

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
            echo outputData(["msg" => "Employee updated successfully."]);die();
        } else {
            echo outputError(["msg" => "Failed to update employee or no changes made."]);die();
        }
        break;

    case "hide":
        // Lock/Unlock employee account (hidden=2 is locked, 0 is active)
        if (!isset($_REQUEST["employeeId"]) ) {
            echo outputError(["msg" => "Employee ID required."]);die();
        }

        $empId = $_REQUEST["employeeId"];
        $employee = selectDBNew("employees", [$empId, $storeId], "id = ? AND storeId = ?", "");
        if (!$employee) {
            echo outputError(["msg" => "Employee not found."]);die();
        }
        $lockedValue = ($employee[0]["locked"] == "1") ? "2" : "0";

        if (updateDBNew("employees", ["hidden" => $lockedValue], "id = ? AND storeId = ?", [$empId, $storeId])) {
            $statusText = ($lockedValue == "2") ? "Locked" : "Unlocked";
            logStoreActivity($storeId, "Employee account $statusText ID: " . $empId);
            echo outputData(["msg" => "Employee account $statusText."]);die();
        } else {
            echo outputError(["msg" => "Failed to update employee status."]);die();
        }
        break;

    case "delete":
        // Soft delete an employee using is_deleted flag (also set status for future use)
        if (!isset($_REQUEST["employeeId"])) {
            echo outputError(["msg" => "Employee ID required."]);die();
        }

        $empId = $_REQUEST["employeeId"];
        if (updateDBNew("employees", ["is_deleted" => "1", "status" => "1"], "id = ? AND storeId = ?", [$empId, $storeId])) {
            logStoreActivity($storeId, "Employee Deleted ID: " . $empId);
            echo outputData(["msg" => "Employee deleted successfully."]);die();
        } else {
            echo outputError(["msg" => "Failed to delete employee."]);die();
        }
        break;

    case "getRoles":
        // Get list of available roles for the dropdown
        $roles = selectDBNew("roles", ["0", "1", $storeId], "status = ? AND hidden = ? AND storeId = ?", "");
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
