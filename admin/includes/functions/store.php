<?php
// get store details \\
function getStoreDetails($storeId){
    if( $getStore = selectDBNew("stores",[$storeId],"`id` = ?", "") ){
        return $getStore[0];
    }
    return false;
}
?>