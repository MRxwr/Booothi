<?php
echo "die";die();
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
        echo outputData(["msg" => "OTP sent successfully"]);die();
    }elseif( $action == "verify" ){
        if( !isset($data["phone"]) || empty($data["phone"]) ){
            echo outputError(["msg" => "Phone is required"]);die();  
        }
        if( !isset($data["code"]) || empty($data["code"]) ){
            echo outputError(["msg" => "OTP code is required"]);die();  
        }
        if( $otp = selectDB("otp_codes", "`phone` = '{$data["phone"]}' AND `code` = '{$data["code"]}' AND `type` = 'login'") ){
            if( $employee = selectDB("employees", "phone = '{$data["phone"]}' AND `keepMeAlive` != ''") ){
                if ( $employee["hidden"] == "1" ){
                    updateDB("employees", ["keepMeAlive" => ""], "id = '{$employee["id"]}'");
                    echo outputError(["msg" => "Your account is locked, Please contact support"]);die();
                }
                if( $employee["status"] == "1" ){
                    updateDB("employees", ["keepMeAlive" => ""], "id = '{$employee["id"]}'");
                    echo outputError(["msg" => "No store assigned to this account, Please contact support"]);die();
                }
                if( $employee["is_deleted"] == "1" ){
                    updateDB("employees", ["phone" => "Deleted " . $employee["phone"], "email" => "Deleted " . $employee["email"], "keepMeAlive" => ""], "id = '{$employee["id"]}'");
                }
                $employeeToken = generateToken();
                updateDB("employees", ["keepMeAlive" => $employeeToken], "id = '{$employee["id"]}'");
                logStoreActivity("Login", "Employee logged in: " . $employee["enName"]);
                echo outputData(["msg" => "OTP verified successfully", "token" => $employeeToken]);die();
            }else{
                echo outputError(["msg" => "Phone number not found, Please register now"]);die();
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
                logStoreActivity("Registration", "New employee registered: " . $data["fullName"]);
                echo outputData(["msg" => "Registration successful", "token" => $employeeToken]);die();
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
        if( selectDB("stores", "url = '{$data["url"]}'") ){
            echo outputError(["msg" => "Store URL already exists, Please choose another one"]);die();
        }else{
            $insertData = [
                "title" => $data["title"],
                "url"   => $data["url"],
                "phone" => $data["phone"],
                "email" => $data["email"]
            ];
            if( insertDB("stores", $insertData) ){
                //get store id
                $store = selectDB("stores", "url = '{$data["url"]}'");
                //create shop called Online Store
                insertDB("shops", [
                    "storeId" => $store["id"],
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
                    "storeId" => $store["id"],
                    "enTitle" => "Store Owner",
                    "arTitle" => "مالك المتجر",
                    "permissions"   => json_encode($permissions),
                    "hidden"  => "1",
                ]);
                $role = selectDB("roles", "storeId = '{$store["id"]}' AND enTitle = 'Store Owner'");
                $roleId = $role["id"];
                $shop = selectDB("shops", "storeId = '{$store["id"]}' AND enTitle = 'Online Store'");
                $shopId = $shop["id"];
                $token = getToken();
                updateDB("employees", ["storeId" => $store["id"], "empType" => $roleId, "shopId" => $shopId], "keepMeAlive = '{$token}'");
                logStoreActivity("Store Creation", "New store created: " . $data["title"]);
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
