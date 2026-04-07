<?php
function checkToken(){
	GLOBAL $_SERVER;
	if( isset($_SERVER['HTTP_AUTHORIZATION']) && !empty($_SERVER['HTTP_AUTHORIZATION']) ){
    	$token = str_replace("Bearer ","",$_SERVER["HTTP_AUTHORIZATION"]);
	}else{
		return false;
	}
	if( $checkToken = selectDBNew("employees",[$token],"`keepMeAlive` = ?", "") ){
		return $checkToken[0]["storeId"];
	}
	return false;
};

function generateToken(){
	return bin2hex(random_bytes(32));
};

function getToken(){
	GLOBAL $_SERVER;
	if( isset($_SERVER['HTTP_AUTHORIZATION']) && !empty($_SERVER['HTTP_AUTHORIZATION']) ){
		$token = str_replace("Bearer ","",$_SERVER["HTTP_AUTHORIZATION"]);
	}else{
		return false;
	}
	return $token;
};

function getEmployeeDetails(){
	GLOBAL $_SERVER;
	if( isset($_SERVER['HTTP_AUTHORIZATION']) && !empty($_SERVER['HTTP_AUTHORIZATION']) ){
    	$token = str_replace("Bearer ","",$_SERVER["HTTP_AUTHORIZATION"]);
	}else{
		return false;
	}
	if( $getEmployee = selectDBNew("employees",[$token],"`keepMeAlive` = ?", "") ){
		return $getEmployee[0];
	}
	return false;
}
?>