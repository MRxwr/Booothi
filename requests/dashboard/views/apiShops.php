<?php
if( !isset($_REQUEST["action"]) || empty($_REQUEST["action"]) ){
    echo outputError(array("msg" => "Action is required"));die();  
}else{
    $action = $_REQUEST["action"];
    $data = $_POST;
    if( $action == "list" ){
        $shops = selectDB("shops", "storeId = '{$storeId}' AND status = '0'");
        $response["shops"] = array();
        if( $shops ){
            foreach($shops as $shop){
                $response["shops"][] = array(
                    "id" => $shop["id"],
                    "enTitle" => $shop["enTitle"],
                    "arTitle" => $shop["arTitle"],
                    "hidden" => $shop["hidden"],
                    "status" => $shop["status"],
                );
            }
        }
        echo outputData($response);
    }elseif( $action == "add" ){
        if( !isset($data["enTitle"]) || empty($data["enTitle"]) ){
            echo outputError(array("msg" => "Shop English Title Is Required"));die();  
        }
        if( !isset($data["arTitle"]) || empty($data["arTitle"]) ){
            echo outputError(array("msg" => "Shop Arabic Title Is Required"));die();  
        }
        
        // Insert into database
        $insertData = array(
            "enTitle" => $data["enTitle"],
            "arTitle" => $data["arTitle"],
            "storeId" => $storeId,
        );
        
        if( insertDB("shops", $insertData) ){
            logStoreActivity("Shops", "Added new shop: " . json_encode($data));
            echo outputData(array("msg" => "Shop added successfully"));
        }else{
            echo outputError(array("msg" => "Failed to add shop, please try again later"));
        }
    }elseif( $action == "update" ){
        if( !isset($data["enTitle"]) || empty($data["enTitle"]) ){
            echo outputError(array("msg" => "Shop English Title Is Required"));die();  
        }
        if( !isset($data["arTitle"]) || empty($data["arTitle"]) ){
            echo outputError(array("msg" => "Shop Arabic Title Is Required"));die();  
        }
        if( !isset($data["shopId"]) || empty($data["shopId"]) ){
            echo outputError(array("msg" => "Shop ID Is Required"));die();  
        }
            
        // Update database
        $updateData = array(
            "enTitle" => $data["enTitle"],
            "arTitle" => $data["arTitle"],
            "storeId" => $storeId,
        );
        
        if( updateDBNew("shops", $updateData, "id = ?", [$data["shopId"]] ) ){
            logStoreActivity("Shops", "Updated shop: " . json_encode($data));
            echo outputData(array("msg" => "Shop updated successfully"));
        }else{
            echo outputError(array("msg" => "Failed to update shop, please try again later"));
        }
    }elseif( $action == "hide" ){
        if( !isset($data["shopId"]) || empty($data["shopId"]) ){
            echo outputError(array("msg" => "Shop ID Is Required"));die();  
        }
        // get shop hidden status then reverse it
        $shop = selectDBNew("shops", [$data["shopId"], $storeId], "id = ? AND storeId = ?", "");
        if( !$shop ){
            echo outputError(array("msg" => "Shop not found"));die();  
        }
        // Update database
        $updateData = array(
            "hidden" => $shop[0]["hidden"] == 1 ? 0 : 1,
        );
        // log activity 0 means show 1 means hide
        $activity = $shop[0]["hidden"] == 1 ? "Unhidden" : "Hidden";
        if( updateDBNew("shops", $updateData, "id = ?", [$data["shopId"]] ) ){
            logStoreActivity("Shops", $activity . " shop: " . json_encode($data));
            echo outputData(array("msg" => "Shop has been " . strtolower($activity) . " successfully"));
        }else{
            echo outputError(array("msg" => "Failed to update shop, please try again later"));
        }
    }elseif( $action == "delete" ){
        if( !isset($data["shopId"]) || empty($data["shopId"]) ){
            echo outputError(array("msg" => "Shop ID Is Required"));die();  
        }
        // Update database
        $updateData = array(
            "status" => 1,
        );
        if( updateDBNew("shops", $updateData, "id = ?", [$data["shopId"]] ) ){
            logStoreActivity("Shops", "Deleted Shop: " . json_encode($data));
            echo outputData(array("msg" => "Shop has been deleted successfully"));
        }else{
            echo outputError(array("msg" => "Failed to delete shop, please try again later"));
        }
    } else {
        echo outputError(array("msg" => "Invalid action specified"));
    }
}
?>