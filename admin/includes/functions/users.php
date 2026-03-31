<?php
function checkToken(){
	GLOBAL $_SERVER;
    echo outputData(array("msg" => $_SERVER));
	if( isset($_SERVER['HTTP_AUTHORIZATION']) && !empty($_SERVER['HTTP_AUTHORIZATION']) ){
    	$token = str_replace("Bearer ","",$_SERVER["HTTP_AUTHORIZATION"]);
	}else{
		echo outputError(array("msg" => "Unauthorized token"));
		exit();
	}
	if( $checkToken = selectDBNew("employees",[$token],"`keepMeAlive` = ?", "") ){
		if( $checkToken[0]["keepMeAlive"] == $token ){
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