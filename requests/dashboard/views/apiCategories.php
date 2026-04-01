<?php
if( !isset($_REQUEST["action"]) || empty($_REQUEST["action"]) ){
    echo outputError(array("msg" => "Action is required"));die();  
}else{
    $action = $_REQUEST["action"];
    $data = $_POST;
    if( $action == "list" ){
        $categories = selectDB2("id, enTitle, arTitle, imageurl, header, rank, hidden", "categories", "storeId = '{$storeId}' AND status = '0' ORDER BY rank ASC");
        $response["categories"] = array();
        if( $categories ){
                $response["categories"] = $categories;
        }
        echo outputData($response);
    }elseif( $action == "add" ){
        if( !isset($data["enTitle"]) || empty($data["enTitle"]) ){
            echo outputError(array("msg" => "English Title Is Required"));die();  
        }
        if( !isset($data["arTitle"]) || empty($data["arTitle"]) ){
            echo outputError(array("msg" => "Arabic Title Is Required"));die();  
        }
        
        $insertData = array(
            "enTitle" => $data["enTitle"],
            "arTitle" => $data["arTitle"],
            "storeId" => $storeId,
            "hidden"  => isset($data["hidden"]) ? $data["hidden"] : "1",
            "rank"    => isset($data["rank"]) ? $data["rank"] : "0"
        );

        if (isset($data["imageurl"])) {
            $insertData["imageurl"] = $data["imageurl"];
        }
        
        // Handle Image Upload from binary if provided
        if (isset($_FILES["image"]) && is_uploaded_file($_FILES["image"]["tmp_name"])) {
            $insertData["imageurl"] = uploadImageToStoreFolder($_FILES["image"]["tmp_name"], $storeId, "category");
        }

        if (isset($data["header"])) {
            $insertData["header"] = $data["header"];
        }
        
        // Handle Header Upload from binary if provided
        if (isset($_FILES["header_img"]) && is_uploaded_file($_FILES["header_img"]["tmp_name"])) {
            $insertData["header"] = uploadImageToStoreFolder($_FILES["header_img"]["tmp_name"], $storeId, "category");
        }
        
        if( insertDB("categories", $insertData) ){
            logStoreActivity("Categories", "Added new category: " . $data["enTitle"]);
            echo outputData(array("msg" => "Category added successfully"));
        }else{
            echo outputError(array("msg" => "Failed to add category"));
        }
    }elseif( $action == "update" ){
        if( !isset($data["categoryId"]) || empty($data["categoryId"]) ){
            echo outputError(array("msg" => "Category ID Is Required"));die();  
        }
        
        $updateData = array();
        if(isset($data["enTitle"])) $updateData["enTitle"] = $data["enTitle"];
        if(isset($data["arTitle"])) $updateData["arTitle"] = $data["arTitle"];
        if(isset($data["hidden"]))  $updateData["hidden"]  = $data["hidden"];
        if(isset($data["rank"]))    $updateData["rank"]    = $data["rank"];
        if(isset($data["imageurl"])) $updateData["imageurl"] = $data["imageurl"];
        if(isset($data["header"]))   $updateData["header"]   = $data["header"];
        
        // Handle binary uploads on update
        if (isset($_FILES["image"]) && is_uploaded_file($_FILES["image"]["tmp_name"])) {
            $updateData["imageurl"] = uploadImageToStoreFolder($_FILES["image"]["tmp_name"], $storeId, "category");
        }
        if (isset($_FILES["header_img"]) && is_uploaded_file($_FILES["header_img"]["tmp_name"])) {
            $updateData["header"] = uploadImageToStoreFolder($_FILES["header_img"]["tmp_name"], $storeId, "category");
        }
        
        if( empty($updateData) ){
            echo outputError(array("msg" => "No data to update"));die();
        }

        if( updateDBNew("categories", $updateData, "id = ? AND storeId = ?", [$data["categoryId"], $storeId] ) ){
            logStoreActivity("Categories", "Updated category ID: " . $data["categoryId"]);
            echo outputData(array("msg" => "Category updated successfully"));
        }else{
            echo outputError(array("msg" => "Failed to update category"));
        }
    }elseif( $action == "hide" ){
        if( !isset($data["categoryId"]) || empty($data["categoryId"]) ){
            echo outputError(array("msg" => "Category ID Is Required"));die();  
        }
        $category = selectDB("categories", "id = '{$data["categoryId"]}' AND storeId = '{$storeId}'");
        if( !$category ){
            echo outputError(array("msg" => "Category not found"));die();
        }
        $newHidden = ($category[0]["hidden"] == 1) ? 2 : 1;
        if( updateDBNew("categories", array("hidden" => $newHidden), "id = ? AND storeId = ?", [$data["categoryId"], $storeId] ) ){
            logStoreActivity("Categories", "Toggled visibility for category: " . $data["categoryId"]);
            echo outputData(array("msg" => "Category visibility updated"));
        }else{
            echo outputError(array("msg" => "Failed to update visibility"));
        }
    }elseif( $action == "delete" ){
        if( !isset($data["categoryId"]) || empty($data["categoryId"]) ){
            echo outputError(array("msg" => "Category ID Is Required"));die();  
        }
        if( updateDBNew("categories", array("status" => "1"), "id = ? AND storeId = ?", [$data["categoryId"], $storeId] ) ){
            logStoreActivity("Categories", "Deleted category ID: " . $data["categoryId"]);
            echo outputData(array("msg" => "Category deleted successfully"));
        }else{
            echo outputError(array("msg" => "Failed to delete category"));
        }
    }elseif( $action == "updateRank" ){
        if( !isset($data["ranks"]) || !is_array($data["ranks"]) ){
            echo outputError(array("msg" => "Ranks array is required"));die();
        }
        foreach($data["ranks"] as $item){
            if(isset($item["id"]) && isset($item["rank"])){
                updateDBNew("categories", array("rank" => $item["rank"]), "id = ? AND storeId = ?", [$item["id"], $storeId]);
            }
        }
        echo outputData(array("msg" => "Ranks updated successfully"));
    } else {
        echo outputError(array("msg" => "Invalid action specified"));
    }
}
?>
