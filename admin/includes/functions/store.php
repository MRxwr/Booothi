<?php
// get store details \\
function getStoreDetails($storeId){
    if( $getStore = selectDBNew("stores",[$storeId],"`id` = ?", "") ){
        return $getStore[0];
    }
    return false;
}

function logStoreActivity($module = null, $activity = null, $storeId = null){
    if ($storeId === null) {
        GLOBAL $storeId;
    }
    $employeeId = getEmployeeDetails();
    $insertData = array(
        "employeeId" => $employeeId ? $employeeId["id"] : 0,
        "storeId" => $storeId,
        "module" => $module,
        "activity" => $activity,
        "date" => date("Y-m-d H:i:s"),
    );
    insertDB("store_activity_log", $insertData);
}
?>