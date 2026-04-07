<?php 
header("Content-Type: application/json; charset=UTF-8");
require_once("../../admin/includes/config.php");
require_once("../../admin/includes/functions.php");

// check user token \\
$skipTokenEndpoints = ["user"];
if ( isset($_GET["endpoint"]) && in_array(strtolower($_GET["endpoint"]), $skipTokenEndpoints) ){
}else{
	if( !checkToken() ){
		echo outputError(array("msg" => "Unauthorized token"));die();
	}else{
		$storeId = checkToken();
		if ( !getStoreDetails($storeId) ){
			echo outputError(array("msg" => "Store not found, Please try again later"));die();
		}else{
			$storeDetails = getStoreDetails($storeId);
		}
	}
}

// get viewed page from pages folder \\
if( isset($_GET["endpoint"]) && searchFile("views","api{$_GET["endpoint"]}.php") ){
	require_once("views/".searchFile("views","api{$_GET["endpoint"]}.php"));
}else{
	echo outputError(array("msg" => "404 api Not Found"));die();
}
?>