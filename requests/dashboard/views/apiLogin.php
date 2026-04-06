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
        insertDB("otp_codes", [
            "phone" => $data["phone"],
            "code" => generateRandomNumber(),
            "type" => "login"
        ]);
        whatsappUltraMsgVerify($data["phone"], "login");
        echo outputData(["msg" => "OTP sent successfully"]);die();
    }elseif( $action == "verify" ){
        if( !isset($data["phone"]) || empty($data["phone"]) ){
            echo outputError(["msg" => "Phone is required"]);die();  
        }
        if( !isset($data["code"]) || empty($data["code"]) ){
            echo outputError(["msg" => "OTP code is required"]);die();  
        }
        if( $otp = selectDB("otp_codes", "`phone` = '{$data["phone"]}' AND `code` = '{$data["code"]}' AND `type` = 'login'") ){
            if( $employee = selectDB("employees", "phone = '{$data["phone"]}'") ){
                $employeeToken = generateToken();
                updateDB("employees", ["token" => $employeeToken], "id = '{$employee["id"]}'");
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
                    "Banners"   => ["view", "add", "update", "delete"],
                sa
                    // Add more modules and permissions as needed
                ];
                insertDB("roles", [
                    "storeId" => $store["id"],
                    "enTitle" => "Store Owner",
                    "arTitle" => "مالك المتجر",
                    "permissions"   => json_encode($permissions),
                    "hidden"  => "1",
                ]);
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
