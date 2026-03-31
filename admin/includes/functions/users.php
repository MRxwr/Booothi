<?php
function checkToken(){
	GLOBAL $_SERVER;
	if( isset($_SERVER['HTTP_AUTHORIZATION']) && !empty($_SERVER['HTTP_AUTHORIZATION']) ){
    	$token = str_replace("Bearer ","",$_SERVER["HTTP_AUTHORIZATION"]);
	}else{
		echo outputError(array("msg" => "Unauthorized token"));
		exit();
	}
	if( $checkToken = selectDBNew("employees",[$token],"`token` = ?", "") ){
		if( $checkToken[0]["token"] == $token ){
			echo outputData(array("msg" => "Authorized token"));
			exit();
		}else{
			echo outputError(array("msg" => "Unauthorized token"));
			exit();
		}
	}
	echo outputError(array("msg" => "Unauthorized token"));
	exit();
};
?>