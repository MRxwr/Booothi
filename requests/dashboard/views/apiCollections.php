<?php
// API for Product-Category Collections Management
// Action-based routing

if (!isset($storeId)) {
    outputError("Authentication required.");
}

$action = $_REQUEST["action"] ?? "";

switch ($action) {
    case "list":
        // List all categories with their 'checked' status for a specific product
        if (!isset($_REQUEST["productId"])) {
            outputError("Product ID required.");
        }

        $productIdScope = $_REQUEST["productId"];

        // Get all active categories for the store
        $categories = selectDBNew("categories", ["0", $storeId], "status = ? AND storeId = ?", "rank ASC");
        
        // Get current collections for this product
        $currentCollections = selectDBNew("collections", [$productIdScope], "productId = ?", "");
        $checkedCategoryIds = $currentCollections ? array_column($currentCollections, "categoryId") : [];

        $response = [];
        if ($categories) {
            foreach ($categories as $cat) {
                $response[] = [
                    "id"       => $cat["id"],
                    "enTitle"  => $cat["enTitle"],
                    "arTitle"  => $cat["arTitle"],
                    "checked"  => in_array($cat["id"], $checkedCategoryIds) ? "1" : "0"
                ];
            }
        }
        
        echo outputData($response);die();
        break;

    case "save":
        // Save (sync) categories for a product
        if (!isset($_POST["productId"]) || !isset($_POST["categoryIds"])) {
            echo outputError("Product ID and Category IDs (array) required.");die();
        }

        $productIdScope = $_POST["productId"];
        $newCategoryIds = $_POST["categoryIds"]; // Expected as an array

        // Verify the product belongs to the store
        $productCheck = selectDBNew("products", [$productIdScope, $storeId], "id = ? AND storeId = ?", "");
        if (!$productCheck) {
            echo outputError("Invalid Product ID.");die();
        }

        // Delete existing collections for this product
        deleteDBNew("collections", [$productIdScope], "productId = ?");

        // Insert new associations
        $count = 0;
        if (is_array($newCategoryIds)) {
            foreach ($newCategoryIds as $catId) {
                $insertData = [
                    "categoryId" => $catId,
                    "productId"  => $productIdScope
                ];
                if (insertDB("collections", $insertData)) {
                    $count++;
                }
            }
        }

        logStoreActivity($storeId, "Product Collections Updated (Product ID: $productIdScope, $count categories)");
        echo outputData(["message" => "Collections updated successfully for $count categories."]);die();
        break;

    default:
        echo outputError("Invalid action.");die();
        break;
}
