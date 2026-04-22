<?php
if( !isset($_GET["storeCode"]) || empty($_GET["storeCode"]) ){
	header ("LOCATION: default.php");die();
}

if( $storeDetails = selectDBNew("stores",[$_GET["storeCode"]],"`storeCode` = ?","") ){
	$storeID = $storeDetails[0]["id"];
	$storeCode = $storeDetails[0]["storeCode"];
	$headerButton = $storeDetails[0]["headerButton"];
	$websiteColor = $storeDetails[0]["websiteColor"];
	$settingsEmail = $storeDetails[0]["email"];
	$settingsPhone = $storeDetails[0]["phone"];
	$settingsTitle = $storeDetails[0]["title"];
	$settingsImage = $storeDetails[0]["bgImage"];
	$settingslogo = $storeDetails[0]["logo"];
	$showLogo = $storeDetails[0]["showLogo"];
	$settingsShippingMethod = $storeDetails[0]["shippingMethod"];
	$defaultCountry = $storeDetails[0]["country"];
	$settingsLang = (isset($storeDetails[0]["language"]) && $storeDetails[0]["language"] == "0") ? "ENG" : "AR";
	$productView = $storeDetails[0]["productView"];
	$showCategoryTitle = $storeDetails[0]["showCategoryTitle"];
	$categoryView = $storeDetails[0]["categoryView"];
	$theme = $storeDetails[0]["theme"];
	$giftCard = $storeDetails[0]["giftCard"];
	$emailOpt = $storeDetails[0]["emailOpt"];
	$noAddressOpt = $storeDetails[0]["noAddress"];
	$settingsDTime = $storeDetails[0]["enDeveliveryTime"];
	$settingsDTimeAr = $storeDetails[0]["arDeveliveryTime"];
	$PaymentAPIKey = $storeDetails[0]["PaymentAPIKey"];
	$inStore = $storeDetails[0]["inStore"];
	$noAddressDelivery = $storeDetails[0]["noAddressDelivery"];
	$expressDelivery = json_decode($storeDetails[0]["expressDelivery"], true);
	$paymentOptions = json_decode($storeDetails[0]["paymentOptions"], true);
	$storeSocialMediaLinks = json_decode($storeDetails[0]["socialMedia"], true);

	// Check if store has active duration otherwise turn on maintenance mode automatically \\
	$sql = "SELECT SUM(p.days) as totalDays 
			FROM subscriptions s 
			JOIN packages p ON s.packageId = p.id 
			WHERE s.storeId = ? AND s.status = 1";
	$totalDaysSub = queryDB($sql, [$storeID]);
	$totalDays = $totalDaysSub[0]["totalDays"] ?? 0;
	$dateStart = $storeDetails[0]["date"];
	$expiryDate = date('Y-m-d H:i:s', strtotime($dateStart . " + {$totalDays} days"));

	if ( date("Y-m-d H:i:s") > $expiryDate && $storeDetails[0]["maintenanceMode"] != 1 ){
		updateDB("stores", ["maintenanceMode" => 1], "`id` = '{$storeID}'");
		$maintenanceMode = 1;
	} else {
		$maintenanceMode = $storeDetails[0]["maintenanceMode"];
	}
}else{
	header ("LOCATION: default.php");die();
}
?>

