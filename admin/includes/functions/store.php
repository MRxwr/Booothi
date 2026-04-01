<?php
// get store details \\
function getStoreDetails($storeId){
    if( $getStore = selectDBNew("stores",[$storeId],"`id` = ?", "") ){
        return $getStore[0];
    }
    return false;
}

function logStoreAcctivity($storeId, $activity, $module = null){
    $employeeId = getEmployeeDetails();
    $insertData = array(
        "employeeId" => $employeeId ? $employeeId["id"] : null,
        "storeId" => $storeId,
        "module" => $module ?? null,
        "activity" => json_encode($activity),
        "date" => date("Y-m-d H:i:s"),
    );
    insertDB("store_activity_log", $insertData);
}
?>