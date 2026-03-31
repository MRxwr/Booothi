<?php
function checkToken(){
	GLOBAL $_SERVER;
	if( isset($_SERVER['HTTP_AUTHORIZATION']) && !empty($_SERVER['HTTP_AUTHORIZATION']) ){
    	$token = str_replace("Bearer ","",$_SERVER["HTTP_AUTHORIZATION"]);
	}else{
		return false;
	}
	if( $checkToken = selectDBNew("employees",[$token],"`keepMeAlive` = ?", "") ){
		if( $checkToken[0]["keepMeAlive"] == $token ){
			return true;
		}else{
			return false;
		}
	}
	return false;
};
?>