<?php
if( !isset($_REQUEST["action"]) || empty($_REQUEST["action"]) ){
    echo outputError(["msg" => "Action is required"]);die();  
}else{
    $action = $_REQUEST["action"];
    $data = $_POST;
    if( $action == "list" ){
        $tabs = selectDB2("id, enTitle, arTitle", "pages", "id != '0' ORDER BY `enTitle` ASC");
        $response["tabs"] = array();
        if( $tabs ){
                $response["tabs"] = $tabs;
        }
        echo outputData($response);
    }else{
        echo outputError(["msg" => "Invalid action specified"]);die();
    }
}
?>
