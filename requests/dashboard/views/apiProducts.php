<?php
if( !isset($_REQUEST["action"]) || empty($_REQUEST["action"]) ){
    echo outputError(array("msg" => "Action is required"));die();  
}else{
    $action = $_REQUEST["action"];
    $data = $_POST;
    
    if( $action == "list" ){
        $page = isset($_REQUEST["page"]) ? (int)$_REQUEST["page"] : 1;
        $limit = isset($_REQUEST["limit"]) ? (int)$_REQUEST["limit"] : 20;
        $offset = ($page - 1) * $limit;
        $search = $_REQUEST["search"] ?? "";
        $orderBy = $_REQUEST["orderBy"] ?? "id";
        $orderDir = $_REQUEST["orderDir"] ?? "DESC";

        $where = "p.storeId = '{$storeId}' AND p.status = '0'";

        // Search logic
        if (!empty($search)) {
            $searchEscaped = $dbconnect->real_escape_string($search);
            $where .= " AND (p.enTitle LIKE '%{$searchEscaped}%' OR p.arTitle LIKE '%{$searchEscaped}%' OR p.id LIKE '%{$searchEscaped}%')";
        }

        // Sort mapping
        $allowedSort = ["id", "enTitle", "arTitle", "date"];
        $sortColumn = in_array($orderBy, $allowedSort) ? "p." . $orderBy : "p.id";
        $sortDir = (strtoupper($orderDir) === "ASC") ? "ASC" : "DESC";

        // Get total count for pagination
        $totalCountResult = queryDB("SELECT COUNT(*) as total FROM products p WHERE {$where}");
        $totalEntries = (int)($totalCountResult[0]["total"] ?? 0);
        $totalPages = ceil($totalEntries / $limit);

        $sql = "SELECT p.id, p.enTitle, p.arTitle, p.type, p.recent, p.bestSeller, p.hidden, 
                CASE WHEN p.type = 1 THEN 'Simple' ELSE 'Variant' END as typeEn,
                CASE WHEN p.type = 1 THEN 'بسيط' ELSE 'متغير' END as typeAr,
                (SELECT i.imageurl FROM images i WHERE i.productId = p.id ORDER BY i.id ASC LIMIT 1) as image
                FROM products p 
                WHERE {$where} 
                ORDER BY {$sortColumn} {$sortDir} 
                LIMIT {$limit} OFFSET {$offset}";
        $products = queryDB($sql);
        
        echo outputData([
            "products" => $products ?: [],
            "pagination" => [
                "currentPage" => $page,
                "limit" => $limit,
                "totalEntries" => $totalEntries,
                "totalPages" => $totalPages
            ]
        ]);die();
    }elseif( $action == "details" ){
        if( !isset($data["productId"]) || empty($data["productId"]) ){
            echo outputError(array("msg" => "Product ID Is Required"));die();
        }
        $product = selectDB("products", "id = '{$data["productId"]}' AND storeId = '{$storeId}'");
        if( !$product ){
            echo outputError(array("msg" => "Product not found"));die();
        }
        $product = $product[0];
        
        // Get all images from images table for management/deletion
        $images = selectDB2("id, imageurl", "images", "productId = '{$product["id"]}' ORDER BY id ASC");
        $product["image"] = $images[0]["imageurl"] ?? "";
        $product["gallery"] = $images ?: [];

        // Get categories
        $categories = selectDB("category_products", "productId = '{$product["id"]}'");
        $product["selectedCategories"] = array();
        if ($categories) {
            foreach ($categories as $cat) {
                $categoryInfo = selectDB2("id, enTitle, arTitle", "categories", "id = '{$cat["categoryId"]}'");
                if ($categoryInfo) {
                    $product["selectedCategories"][] = [
                        "id" => $categoryInfo[0]["id"],
                        "enTitle" => $categoryInfo[0]["enTitle"],
                        "arTitle" => $categoryInfo[0]["arTitle"],
                        "title" => direction($categoryInfo[0]["enTitle"], $categoryInfo[0]["arTitle"])
                    ];
                }
            }
        }
        unset($categories); // Free memory

        // Get Extras/Add-ons with titles
        $extraIds = json_decode($product["extras"], true) ?: [];
        $product["selectedExtras"] = array();
        if (!empty($extraIds)) {
            foreach ($extraIds as $exId) {
                $extraInfo = selectDB2("id, enTitle, arTitle", "extras", "id = '{$exId}'");
                if ($extraInfo) {
                    $product["selectedExtras"][] = [
                        "id" => $extraInfo[0]["id"],
                        "enTitle" => $extraInfo[0]["enTitle"],
                        "arTitle" => $extraInfo[0]["arTitle"],
                        "title" => direction($extraInfo[0]["enTitle"], $extraInfo[0]["arTitle"])
                    ];
                }
            }
        }
        unset($extraIds); // Free memory
        
        // If simple product, get price/sku/quantity from attributes_products
        if( $product["type"] == 1 ){
            $attr = selectDB("attributes_products", "productId = '{$product["id"]}' AND hidden = '0'");
            if($attr){
                $product["price"] = $attr[0]["price"];
                $product["cost"] = $attr[0]["cost"];
                $product["sku"] = $attr[0]["sku"];
                $product["quantity"] = $attr[0]["quantity"];
            }
        }
        
        echo outputData($product);
    }elseif( $action == "add" ){
        // Basic required fields
        if( !isset($data["enTitle"]) || empty($data["enTitle"]) ) { echo outputError(array("msg" => "English Title Required")); die(); }
        
        $insertData = array(
            "storeId" => $storeId,
            "enTitle" => $data["enTitle"],
            "arTitle" => isset($data["arTitle"]) ? $data["arTitle"] : "",
            "type" => isset($data["type"]) ? $data["type"] : "1",
            "enDetails" => isset($data["enDetails"]) ? $data["enDetails"] : "",
            "arDetails" => isset($data["arDetails"]) ? $data["arDetails"] : "",
            "discount" => isset($data["discount"]) ? $data["discount"] : "0",
            "discountType" => isset($data["discountType"]) ? $data["discountType"] : "0",
            "video" => isset($data["video"]) ? $data["video"] : "",
            "preorder" => isset($data["preorder"]) ? $data["preorder"] : "0",
            "preorderText" => isset($data["preorderText"]) ? $data["preorderText"] : "",
            "preorderTextAr" => isset($data["preorderTextAr"]) ? $data["preorderTextAr"] : "",
            "sizeChart" => isset($data["sizeChart"]) ? $data["sizeChart"] : "0",
            "oneTime" => isset($data["oneTime"]) ? $data["oneTime"] : "0",
            "isImage" => isset($data["isImage"]) ? $data["isImage"] : "0",
            "collection" => isset($data["collection"]) ? $data["collection"] : "0",
            "giftCard" => isset($data["giftCard"]) ? $data["giftCard"] : "0",
            "width" => isset($data["width"]) ? $data["width"] : "0",
            "height" => isset($data["height"]) ? $data["height"] : "0",
            "depth" => isset($data["depth"]) ? $data["depth"] : "0",
            "weight" => isset($data["weight"]) ? $data["weight"] : "0",
            "imageurl" => isset($data["imageurl"]) ? $data["imageurl"] : ""
        );
        
        // Handle Image Upload
        if (isset($_FILES["image"]) && is_uploaded_file($_FILES["image"]["tmp_name"])) {
            $insertData["imageurl"] = uploadImageToStoreFolder($_FILES["image"]["tmp_name"], $storeId, "products");
        }

        if( insertDB("products", $insertData) ){
            $productId = $dbconnect->insert_id;
            
            // Handle Categories
            if( isset($data["categoryIds"]) && is_array($data["categoryIds"]) ){
                foreach($data["categoryIds"] as $catId){
                    insertDB("category_products", array("productId" => $productId, "categoryId" => $catId));
                }
            }
            
            // Handle Simple Product Attributes
            if( $insertData["type"] == 1 ){
                $attrData = array(
                    "productId" => $productId,
                    "price" => isset($data["price"]) ? $data["price"] : "0",
                    "cost" => isset($data["cost"]) ? $data["cost"] : "0",
                    "sku" => isset($data["sku"]) ? $data["sku"] : "",
                    "quantity" => isset($data["quantity"]) ? $data["quantity"] : "0"
                );
                insertDB("attributes_products", $attrData);
            }
            
            logStoreActivity("Products", "Added product: " . $data["enTitle"]);
            echo outputData(array("msg" => "Product added successfully", "productId" => $productId));
        }else{
            echo outputError(array("msg" => "Failed to add product"));
        }
    }elseif( $action == "update" ){
        if( !isset($data["productId"]) || empty($data["productId"]) ){
            echo outputError(array("msg" => "Product ID Is Required"));die();
        }
        
        $fields = ["enTitle", "arTitle", "type", "enDetails", "arDetails", "discount", "discountType", "video", "preorder", "preorderText", "preorderTextAr", "sizeChart", "oneTime", "isImage", "collection", "giftCard", "width", "height", "depth", "weight", "imageurl", "hidden"];
        $updateData = array();
        foreach($fields as $field){
            if(isset($data[$field])) $updateData[$field] = $data[$field];
        }

        // Handle Image Upload
        if (isset($_FILES["image"]) && is_uploaded_file($_FILES["image"]["tmp_name"])) {
            $updateData["imageurl"] = uploadImageToStoreFolder($_FILES["image"]["tmp_name"], $storeId, "products");
        }
        
        if( updateDBNew("products", $updateData, "id = ? AND storeId = ?", [$data["productId"], $storeId]) ){
            // Update categories if provided
            if( isset($data["categoryIds"]) && is_array($data["categoryIds"]) ){
                deleteDB("category_products", "productId = '{$data["productId"]}'");
                foreach($data["categoryIds"] as $catId){
                    insertDB("category_products", array("productId" => $data["productId"], "categoryId" => $catId));
                }
            }
            
            // Update attributes if simple
            $product = selectDB("products", "id = '{$data["productId"]}'");
            if( $product && $product[0]["type"] == 1 ){
                $attrData = array();
                if(isset($data["price"])) $attrData["price"] = $data["price"];
                if(isset($data["cost"])) $attrData["cost"] = $data["cost"];
                if(isset($data["sku"])) $attrData["sku"] = $data["sku"];
                if(isset($data["quantity"])) $attrData["quantity"] = $data["quantity"];
                
                if(!empty($attrData)){
                    updateDBNew("attributes_products", $attrData, "productId = ? AND hidden = '0'", [$data["productId"]]);
                }
            }
            
            logStoreActivity("Products", "Updated product ID: " . $data["productId"]);
            echo outputData(array("msg" => "Product updated successfully"));
        }else{
            echo outputError(array("msg" => "Failed to update product"));
        }
    }elseif( $action == "delete" ){
        if( !isset($data["productId"]) || empty($data["productId"]) ){
            echo outputError(array("msg" => "Product ID Is Required"));die();
        }
        if( updateDBNew("products", array("status" => "1"), "id = ? AND storeId = ?", [$data["productId"], $storeId]) ){
            logStoreActivity("Products", "Deleted product ID: " . $data["productId"]);
            echo outputData(array("msg" => "Product deleted successfully"));
        }else{
            echo outputError(array("msg" => "Failed to delete product"));
        }
    }elseif( $action == "toggleStatus" ){
        // Handle recent/bestSeller toggles
        if( !isset($data["productId"]) || !isset($data["field"]) ){
            echo outputError(array("msg" => "Product ID and Field (recent/bestSeller) required"));die();
        }
        $field = ($data["field"] == "recent") ? "recent" : "bestSeller";
        $product = selectDB("products", "id = '{$data["productId"]}' AND storeId = '{$storeId}'");
        if($product){
            $newVal = ($product[0][$field] == 1) ? 0 : 1;
            updateDBNew("products", array($field => $newVal), "id = ? AND storeId = ?", [$data["productId"], $storeId]);
            echo outputData(array("msg" => "Status updated"));
        }
    }elseif( $action == "deleteImage" ){
        if( !isset($data["imageId"]) || empty($data["imageId"]) ){
            echo outputError(array("msg" => "Image ID Is Required"));die();
        }
        // Verify image owner via product
        $image = selectDB("images", "id = '{$data["imageId"]}'");
        if( $image ){
            $pId = $image[0]["productId"];
            $product = selectDB("products", "id = '{$pId}' AND storeId = '{$storeId}'");
            if( $product ){
                if( deleteDB("images", "id = '{$data["imageId"]}'") ){
                    echo outputData(array("msg" => "Image deleted successfully"));die();
                }
            }
        }
        echo outputError(array("msg" => "Failed to delete image or unauthorized"));
    } else {
        echo outputError(array("msg" => "Invalid action specified"));
    }
}
?>
