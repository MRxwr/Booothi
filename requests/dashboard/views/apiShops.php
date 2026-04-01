<?php
if( !isset($_REQUEST["action"]) || empty($_REQUEST["action"]) ){
    echo outputError(array("msg" => "Action is required"));die();  
}else{
    $action = $_REQUEST["action"];
    $data = $_POST;
    if( $action == "add" ){
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
    } else {
        echo outputError(array("msg" => "Invalid action specified"));
    }
}
?>