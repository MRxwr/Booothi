<?php
if( !isset($_REQUEST["action"]) || empty($_REQUEST["action"]) ){
    echo outputError(["msg" => "Action is required"]);die();  
}else{
    $action = $_REQUEST["action"];
    $data = $_POST;
    if( $action == "login" ){
        if( !isset($data["phone"]) || empty($data["phone"]) ){
            echo outputError(["msg" => "Phone is required"]);die();  
        }
        $otpCode = generateRandomNumber();
        insertDB("otp_codes", [
            "phone" => $data["phone"],
            "code" => $otpCode,
            "type" => "login"
        ]);
        whatsappUltraMsgVerify($data["phone"], $otpCode);
        logStoreActivity("OTP Sent", "OTP sent to phone: " . $data["phone"], 0);
        echo outputData(["msg" => "OTP sent successfully"]);die();
    }elseif( $action == "verify" ){
        if( !isset($data["phone"]) || empty($data["phone"]) ){
            echo outputError(["msg" => "Phone is required"]);die();  
        }
        if( !isset($data["code"]) || empty($data["code"]) ){
            echo outputError(["msg" => "OTP code is required"]);die();  
        }
        if( $otp = selectDB("otp_codes", "`phone` = '{$data["phone"]}' AND `code` = '{$data["code"]}' AND `type` = 'login'") ){
            deleteDB("otp_codes", "phone = '{$data["phone"]}'");
            if( $employee = selectDB("employees", "phone = '{$data["phone"]}'") ){
                if( $employee[0]["storeId"] == "0" ){
                    $employeeToken = generateToken();
                    updateDB("employees", ["keepMeAlive" => $employeeToken], "id = '{$employee[0]["id"]}'");
                    echo outputError(["msg" => "No store assigned to this account, Please contact support", "token" => $employeeToken, "isRegister" => false, "isStore" => true]);die();
                }
                if ( $employee[0]["hidden"] == "2" ){
                    updateDB("employees", ["keepMeAlive" => ""], "id = '{$employee[0]["id"]}'");
                    echo outputError(["msg" => "Your account is locked, Please contact support", "isRegister" => false, "isStore" => false]);die();
                }
                if( $employee[0]["status"] == "1" ){
                    updateDB("employees", ["keepMeAlive" => ""], "id = '{$employee[0]["id"]}'");
                    echo outputError(["msg" => "Your account is inactive, Please contact support", "isRegister" => false, "isStore" => false]);die();
                }
                if( $employee[0]["is_deleted"] == "1" ){
                    updateDB("employees", ["keepMeAlive" => ""], "id = '{$employee[0]["id"]}'");
                    echo outputError(["msg" => "Could not find employee, Please register now", "isRegister" => true, "isStore" => false]);die();
                }
                $employeeToken = generateToken();
                updateDB("employees", ["keepMeAlive" => $employeeToken], "id = '{$employee[0]["id"]}'");
                logStoreActivity("OTP Verified", "OTP verified for phone: " . $data["phone"], $employee[0]["storeId"]);
                echo outputData(["msg" => "OTP verified successfully", "token" => $employeeToken, "isRegister" => false, "isStore" => false]);die();
            }else{
                echo outputError(["msg" => "Could not find employee, Please register now", "isRegister" => true, "isStore" => false]);die();
            }
        }else{
            echo outputError(["msg" => "Invalid OTP code"]);die();
        }
    }elseif( $action == "register" ){
        if( !isset($data["fullName"]) || empty($data["fullName"]) ){
            echo outputError(["msg" => "Full name is required"]);die();  
        }
        if( !isset($data["email"]) || empty($data["email"]) ){
            echo outputError(["msg" => "Email is required"]);die();  
        }
        if( !isset($data["phone"]) || empty($data["phone"]) ){
            echo outputError(["msg" => "Phone is required"]);die();  
        }
        if( selectDB("employees", "phone = '{$data["phone"]}'") ){
            echo outputError(["msg" => "Phone number already exists, Please login instead"]);die();  
        }else{
            $employeeToken = generateToken();
            $insertData = [
                "fullName" => $data["fullName"],
                "email"    => $data["email"],
                "phone"    => $data["phone"],
                "password" => sha1($data["email"]."2026"),
                "keepMeAlive"    => $employeeToken
            ];
            if( insertDB("employees", $insertData) ){
                logStoreActivity("Registration", "New employee registered: " . $data["fullName"], 0);
                echo outputData(["msg" => "Registration successful", "token" => $employeeToken, "isRegister" => false, "isStore" => true]);die();
            }else{
                echo outputError(["msg" => "Failed to register employee"]);die();  
            }
        }
    }elseif( $action == "createStore" ){
        if( !isset($data["title"]) || empty($data["title"]) ){
            echo outputError(["msg" => "Title is required"]);die();  
        }
        if( !isset($data["url"]) || empty($data["url"]) ){
            echo outputError(["msg" => "URL is required"]);die();  
        }
        if( !isset($data["phone"]) || empty($data["phone"]) ){
            echo outputError(["msg" => "Phone is required"]);die();  
        }
        if( !isset($data["email"]) || empty($data["email"]) ){
            echo outputError(["msg" => "Email is required"]);die();  
        }
        if( selectDB("stores", "storeCode = '{$data["url"]}'") ){
            echo outputError(["msg" => "Store URL already exists, Please choose another one"]);die();
        }else{
            $insertData = [
                "title" => $data["title"],
                "storeCode"   => $data["url"],
                "phone" => $data["phone"],
                "email" => $data["email"]
            ];
            if( insertDB("stores", $insertData) ){
                //get store id
                $store = selectDB("stores", "storeCode = '{$data["url"]}'");
                //create shop called Online Store
                insertDB("shops", [
                    "storeId" => $store[0]["id"],
                    "enTitle" => "Online Store",
                    "arTitle" => "المتجر الإلكتروني",
                    "hidden" => "1",
                ]);
                // create role called Store Owner with all permissions
                $permissions = [
                    "dashboard" => ["view"],
                    "orders" => ["view", "add", "update", "delete"],
                    "products" => ["view", "add", "update", "delete"],
                    "categories" => ["view", "add", "update", "delete"],
                    "attributes" => ["view", "add", "update", "delete"],
                    "employees" => ["view", "add", "update", "delete"],
                    "customers" => ["view", "add", "update", "delete"],
                    "vouchers"  => ["view", "add", "update", "delete"],
                    "banners"   => ["view", "add", "update", "delete"],
                    "exstras"    => ["view", "add", "update", "delete"],
                    "roles"    => ["view", "add", "update", "delete"],
                    "employees"    => ["view", "add", "update", "delete"],
                    "shops"    => ["view", "add", "update", "delete"],
                    "stores"    => ["view", "add", "update", "delete"],
                    // Add more modules and permissions as needed
                ];
                insertDB("roles", [
                    "storeId" => $store[0]["id"],
                    "enTitle" => "Store Owner",
                    "arTitle" => "مالك المتجر",
                    "permissions"   => json_encode($permissions),
                    "hidden"  => "1",
                ]);
                $role = selectDB("roles", "storeId = '{$store[0]["id"]}' AND enTitle = 'Store Owner'");
                $roleId = $role[0]["id"];
                $shop = selectDB("shops", "storeId = '{$store[0]["id"]}' AND enTitle = 'Online Store'");
                $shopId = $shop[0]["id"];
                $token = getToken();
                updateDB("employees", ["storeId" => $store[0]["id"], "empType" => $roleId, "shopId" => $shopId], "keepMeAlive = '{$token}'");
                logStoreActivity("Store Creation", "New store created: " . $data["title"], $store[0]["id"]);
                echo outputData(["msg" => "Store created successfully"]);die();
            }else{
                echo outputError(["msg" => "Failed to create store"]);die();
            }
        }
    }else{
        echo outputError(["msg" => "Invalid action specified"]);die();
    }
}
?>
