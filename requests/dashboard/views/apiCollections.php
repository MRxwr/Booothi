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
        $categories = selectDBNew("categories", "status = ? AND storeId = ?", ["0", $storeId], "ORDER BY rank ASC");
        
        // Get current collections for this product
        $currentCollections = selectDBNew("collections", "productId = ?", [$productIdScope], "");
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
        
        outputData($response);
        break;

    case "save":
        // Save (sync) categories for a product
        if (!isset($_POST["productId"]) || !isset($_POST["categoryIds"])) {
            outputError("Product ID and Category IDs (array) required.");
        }

        $productIdScope = $_POST["productId"];
        $newCategoryIds = $_POST["categoryIds"]; // Expected as an array

        // Verify the product belongs to the store
        $productCheck = selectDBNew("products", "id = ? AND storeId = ?", [$productIdScope, $storeId], "");
        if (!$productCheck) {
            outputError("Invalid Product ID.");
        }

        // Delete existing collections for this product
        // Note: Using raw SQL delete here as we don't have a deleteDBNew for raw WHERE yet
        $delSql = "DELETE FROM `collections` WHERE `productId` = ?";
        $stmt = $conn->prepare($delSql);
        $stmt->bind_param("i", $productIdScope);
        $stmt->execute();
        $stmt->close();

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
        outputData(["message" => "Collections updated successfully for $count categories."]);
        break;

    default:
        outputError("Invalid action.");
        break;
}
