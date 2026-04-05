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
         // Free memory
        $product["variants"] = [];
        $variants = selectDB2("id, enTitle, arTitle, price, cost, sku, quantity, hidden", "attributes_products", "productId = '{$product["id"]}' AND  `status` = '0'");
        $product["variants"] = $variants ?: [];

        unset($product["extras"], $product["categoryId"], $product["storeId"], $product["status"], $product["subId"], $product["date"], $product["storeQuantity"], $product["onlineQuantity"], $product["price"], $product["cost"], $product["sku"], $product["quantity"] );
        echo outputData($product);die();
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
            "weight" => isset($data["weight"]) ? $data["weight"] : "0"
        );
        
        if( insertDB("products", $insertData) ){
            $productId = $dbconnect->insert_id;

            // Handle Image Upload (Support multiple images from 'image' input)
            if (isset($_FILES["image"])) {
                $files = $_FILES["image"];
                $tmpNames = is_array($files["tmp_name"]) ? $files["tmp_name"] : [$files["tmp_name"]];
                
                foreach ($tmpNames as $tmpName) {
                    if (is_uploaded_file($tmpName)) {
                        $imageUrl = uploadImageToStoreFolder($tmpName, $storeId, "products");
                        insertDB("images", array("productId" => $productId, "imageurl" => $imageUrl));
                    }
                }
            }
            
            // Handle Categories
            if( isset($data["categoryIds"]) && is_array($data["categoryIds"]) ){
                foreach($data["categoryIds"] as $catId){
                    insertDB("category_products", array("productId" => $productId, "categoryId" => $catId));
                }
            }

            // Handle Extras
            if( isset($data["extraIds"]) && is_array($data["extraIds"]) ){
                $extrasJson = json_encode($data["extraIds"]);
                updateDBNew("products", array("extras" => $extrasJson), "id = ?", [$productId]);
            }
            
            // Handle Attributes (Variants) - Always at least one
            if( isset($data["variants"]) && is_array($data["variants"]) ){
                foreach($data["variants"] as $variant){
                    $attrData = array(
                        "productId" => $productId,
                        "storeId" => $storeId,
                        "attribute" => $variant["attribute"] ?? "",
                        "enTitle" => $variant["enTitle"] ?? "",
                        "arTitle" => $variant["arTitle"] ?? "",
                        "price" => $variant["price"] ?? "0",
                        "cost" => $variant["cost"] ?? "0",
                        "sku" => $variant["sku"] ?? "",
                        "quantity" => $variant["quantity"] ?? "0"
                    );
                    insertDB("attributes_products", $attrData);
                }
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
        
        $fields = ["enTitle", "arTitle", "type", "enDetails", "arDetails", "discount", "discountType", "video", "preorder", "preorderText", "preorderTextAr", "sizeChart", "oneTime", "isImage", "collection", "giftCard", "width", "height", "depth", "weight", "hidden"];
        $updateData = array();
        foreach($fields as $field){
            if(isset($data[$field])) $updateData[$field] = $data[$field];
        }

        if( updateDBNew("products", $updateData, "id = ? AND storeId = ?", [$data["productId"], $storeId]) ){
            // Handle Image Upload (Add to images table)
            if (isset($_FILES["image"])) {
                $files = $_FILES["image"];
                $tmpNames = is_array($files["tmp_name"]) ? $files["tmp_name"] : [$files["tmp_name"]];

                foreach ($tmpNames as $tmpName) {
                    if (is_uploaded_file($tmpName)) {
                        $imageUrl = uploadImageToStoreFolder($tmpName, $storeId, "products");
                        insertDB("images", array("productId" => $data["productId"], "imageurl" => $imageUrl));
                    }
                }
            }

            // Update categories if provided
            if( isset($data["categoryIds"]) && is_array($data["categoryIds"]) ){
                deleteDB("category_products", "productId = '{$data["productId"]}'");
                foreach($data["categoryIds"] as $catId){
                    insertDB("category_products", array("productId" => $data["productId"], "categoryId" => $catId));
                }
            }

            // Update extras if provided
            if( isset($data["extraIds"]) && is_array($data["extraIds"]) ){
                $extrasJson = json_encode($data["extraIds"]);
                updateDBNew("products", array("extras" => $extrasJson), "id = ?", [$data["productId"]]);
            }
            
            // Update / Sync Attributes (Variants)
            if( isset($data["variants"]) && is_array($data["variants"]) ){
                $currentVariantIds = [];
                foreach($data["variants"] as $variant){
                    $attrData = array(
                        "productId" => $data["productId"],
                        "storeId" => $storeId,
                        "attribute" => $variant["attribute"] ?? "",
                        "enTitle" => $variant["enTitle"] ?? "",
                        "arTitle" => $variant["arTitle"] ?? "",
                        "price" => $variant["price"] ?? "0",
                        "cost" => $variant["cost"] ?? "0",
                        "sku" => $variant["sku"] ?? "",
                        "quantity" => $variant["quantity"] ?? "0",
                        "status" => "0"
                    );

                    if( isset($variant["id"]) && !empty($variant["id"]) ){
                        // Update existing variant (Safe because historical orders are json-cached)
                        updateDBNew("attributes_products", $attrData, "id = ? AND productId = ?", [$variant["id"], $data["productId"]]);
                        $currentVariantIds[] = (int)$variant["id"];
                    } else {
                        // Insert new variant
                        if( insertDB("attributes_products", $attrData) ){
                            $currentVariantIds[] = $dbconnect->insert_id;
                        }
                    }
                }
                
                // Soft-delete variants that were NOT sent in the request (Syncing)
                if (!empty($currentVariantIds)) {
                    $idsList = implode(",", $currentVariantIds);
                    updateDBNew("attributes_products", ["status" => "1"], "productId = ? AND id NOT IN ($idsList)", [$data["productId"]]);
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
    }elseif( $action == "hide" ){
        if( !isset($_REQUEST["productId"]) || empty($_REQUEST["productId"]) ){
            echo outputError(array("msg" => "Product ID Is Required"));die();  
        }
        $product = selectDB("products", "id = '{$_REQUEST["productId"]}' AND storeId = '{$storeId}'");
        if( !$product ){
            echo outputError(array("msg" => "Product not found"));die();
        }
        $newHidden = ($product[0]["hidden"] == 1) ? 2 : 1;
        if( updateDBNew("products", array("hidden" => $newHidden), "id = ? AND storeId = ?", [$_REQUEST["productId"], $storeId] ) ){
            logStoreActivity("Products", "Toggled visibility for product: " . $_REQUEST["productId"]);
            echo outputData(array("msg" => "Product visibility updated"));
        }else{
            echo outputError(array("msg" => "Failed to update visibility"));
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
    }elseif( $action == "getAttributes" ){
        $attributes = selectDB2("id, enTitle, arTitle", "attributes", "status = '0'");
        echo outputData(array("attributes" => $attributes ?: []));die();

    }elseif( $action == "buildVariants" ){
        if( !isset($data["attributes"]) || !is_array($data["attributes"]) ){
            echo outputError(array("msg" => "Attributes array is required"));die();
        }

        // Build array of title arrays per attribute
        $titleGroups = [];
        foreach($data["attributes"] as $attr){
            if( !isset($attr["title"]) || !is_array($attr["title"]) || empty($attr["title"]) ) continue;
            $attrInfo = selectDB2("enTitle, arTitle", "attributes", "id = '{$attr["attributeId"]}' AND status = '0'");
            if( !$attrInfo ) continue;
            $titleGroups[] = $attr["title"];
        }

        if( empty($titleGroups) ){
            echo outputError(array("msg" => "No valid attributes provided"));die();
        }

        // Cartesian product of all title groups
        $combinations = [[]];
        foreach($titleGroups as $group){
            $newCombinations = [];
            foreach($combinations as $existing){
                foreach($group as $title){
                    $newCombinations[] = array_merge($existing, [trim($title)]);
                }
            }
            $combinations = $newCombinations;
        }

        // Build variant skeleton objects ready for client to fill
        $variants = [];
        foreach($combinations as $combo){
            $variants[] = array(
                "attribute" => implode("_", $combo),
                "enTitle" => "",
                "arTitle" => "",
                "price" => "0",
                "cost" => "0",
                "sku" => "",
                "quantity" => "0"
            );
        }

        echo outputData(array("variants" => $variants));die();

    } else {
        echo outputError(array("msg" => "Invalid action specified"));
    }
}
?>
