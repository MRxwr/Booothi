<?php
require("../admin/includes/config.php");
require("../admin/includes/functions.php");

## Read value
$draw = $_POST['draw'] ?? 0;
$row = $_POST['start'] ?? 0;
$rowperpage = $_POST['length'] ?? 10; // Rows display per page
$columnIndex = $_POST['order'][0]['column'] ?? 0; // Column index
$columnName = $_POST['columns'][$columnIndex]['data'] ?? 'date'; // Column name
$columnSortOrder = $_POST['order'][0]['dir'] ?? 'desc'; // asc or desc
$searchValue = $_POST['search']['value'] ?? ''; // Search value

## Search 
$searchQuery = " "; 
if($searchValue != ''){
  $searchQuery = " AND (`orderId` LIKE '%".$searchValue."%' OR `gatewayId` LIKE '%".$searchValue."%')";
}

## Total number of records without filtering
$totalRecordsQuery = selectDBNew("subscriptions", [0], "`id` != ?", "");
$totalRecords = is_array($totalRecordsQuery) ? count($totalRecordsQuery) : 0;

## Total number of record with filtering
$totalFilterQuery = selectDBNew("subscriptions", [0], "`id` != ? {$searchQuery}", "");
$totalRecordwithFilter = is_array($totalFilterQuery) ? count($totalFilterQuery) : 0;

## Fetch records
$sql = "SELECT s.*, st.title as storeName, p.title as packageName 
        FROM subscriptions as s 
        LEFT JOIN stores as st ON s.storeId = st.id 
        LEFT JOIN packages as p ON s.packageId = p.id 
        WHERE s.`id` != '0' {$searchQuery} 
        ORDER BY ".$columnName." ".$columnSortOrder." 
        LIMIT ".$row.",".$rowperpage;

$data = array();
if( $subscriptions = queryDB($sql) ){
    $statusText = [
        0 => direction("Pending", "انتظار"),
        1 => direction("Success", "ناجح"),
        2 => direction("Failed", "فاشلة"),
        3 => direction("Expired", "منتهية")
    ];
    $statusBgColor = ["default", "success", "danger", "warning"];

    for( $i = 0; $i < sizeof($subscriptions); $i++ ){
        $price = $subscriptions[$i]["price"];
        $date = $subscriptions[$i]["date"];
        $orderId = $subscriptions[$i]["orderId"];
        $storeName = $subscriptions[$i]["storeName"];
        $packageName = json_decode($subscriptions[$i]["packageName"], true);
        $packageNameDisplay = direction($packageName["en"] ?? '', $packageName["ar"] ?? '');
        $gatewayId = $subscriptions[$i]["gatewayId"];
        
        $statusVal = $subscriptions[$i]["status"];
        $status = "<span class='label label-{$statusBgColor[$statusVal]}'>{$statusText[$statusVal]}</span>";
        
        $action = "<div>
                    <a href='?v=SubscriptionDetails&id={$subscriptions[$i]["id"]}' class='btn btn-default btn-icon-anim btn-circle' title='".direction("View","عرض")."' data-toggle='tooltip'><i class='fa fa-eye'></i></a>
                  </div>";

        $data[] = array( 
              "date" => $date,
              "orderId" => $orderId,
              "store" => $storeName,
              "package" => $packageNameDisplay,
              "price" => $price . ' KD',
              "gatewayId" => $gatewayId,
              "status" => $status,
              "action" => $action
           );	  
    }
}

$response = array(
    "draw" => intval($draw),
    "iTotalRecords" => $totalRecords,
    "iTotalDisplayRecords" => $totalRecordwithFilter,
    "aaData" => $data
);

echo json_encode($response);
?>