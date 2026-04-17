<?php
if( isset($_GET["error"]) && $_GET["error"] == "3" ){
	$gatewayPayload = json_decode( base64_decode( urldecode( $_GET["keys"] ) ), true );
	if( $orderDetails = selectDBNew("orders2",[$gatewayPayload["gatewayId"]],"`gatewayId` = ?","") ){
		if( $orderDetails[0]["status"] == "0" ){
			updateDB("orders2",["status"=>"5","gatewayPayload" => json_encode($gatewayPayload,JSON_UNESCAPED_UNICODE)],"`gatewayId` = '{$gatewayPayload["gatewayId"]}'");
		}
	}
}
?>
<div class="sec-pad grey-bg">
	<div class="container" style="margin-top: 30px;">
		<div class="row d-flex justify-content-center">
			<div class="col-lg-10 col-12">
				<div class="checkout-page">
					<div class="sidebar-item">
						<div class="make-me-sticky check-make-me-sticky">
							<h3 class="bold text-center mb-4 pb-3"><?php echo $cartText ?></h3>
							<div class="checkoutsidebar">
								<?php
								if ( getCartItemsTotal() < 1 ){
									header("LOCATION: index.php");
								}else{
									echo loadCartItems();
								}
								?>
							</div>
							<div class="checkoutsidebar-calculation">
								<div class="calc-text-box d-flex justify-content-between">
									<span class="calc-text bold"></span>
									<span class="calc-text bold ShoppingSpan">
									<?php echo ""?>
									</span>
								</div>
								<div class="calc-text-box d-flex justify-content-between">
									<span class="calc-text bold"><?php echo $totalPriceText ?></span>
									<span class="calc-text bold totalSpan">
									<?php echo getCartPriceTotal() ?>
									</span>
								</div>
								<div class="calc-text-box d-flex justify-content-between">
									<span class="bold voucherMsgS" style="color:red;font-size:18px"><b class="voucherMsg"></b></span>
								</div>
							</div>

						</div>
					</div>

					<form method="post" action="?v=Bill">
						<div class="content-section">
							<?php
							if ( isset($_GET["error"]) ){
							?>
							<div class="checkout-informationbox">
								<div style="color:red; font-size:18px; text-align:center">
									<img src="https://i.imgur.com/h8aeHER.png" style="width:50px;height:50px">
									<br>
									<br>
									<?php echo $paymentFailureMsgText ?>
								</div>
							</div>
							<br>

							<?php
							}

							if ( $giftCard == 1 ){
							?>
							<div class="checkout-informationbox">
								<div class="media checkout-heading-box">
									<span class="count-number">1</span>
									<div class="media-body">
										<h3 class="checkout-heading"><?php echo $pleaseFillForGiftsText ?></h3>
										<p class="checkout-heading-text"></p>
									</div>
								</div>
								<div class="form-group">
								<input type="text" class="form-control" name="giftCard[from]" value="" placeholder="<?php echo $fromText ?>" >
								</div>
								<div class="form-group">
								<input type="text" class="form-control" name="giftCard[to]" value=""  placeholder="<?php echo $toText ?>" >
								</div>
								<div class="form-group">
								<input type="text" class="form-control" name="giftCard[message]" value="" placeholder="<?php echo $msgText ?>" >
								</div>
							</div>
							<?php
							}else{
								?>
								<input type="hidden" class="form-control" name="giftCard[from]" value="" placeholder="From" >
								<input type="hidden" class="form-control" name="giftCard[to]" value=""  placeholder="To" >
								<input type="hidden" class="form-control" name="giftCard[message]" value="" placeholder="Message" >
								<?php
							}
							?>

							<div class="checkout-informationbox">
								<div class="media checkout-heading-box">
									<span class="count-number">2</span>
									<div class="media-body">
										<h3 class="checkout-heading"><?php echo $personalInfoText ?></h3>
										<p class="checkout-heading-text"></p>
									</div>
								</div>
								<?php 
								if ( $emailOpt == 0 ){
									$emailHidden = "hidden";
								}else{
									$emailHidden = "text";
								}

								if ( isset($userID) AND !empty($userID) ){
									$sql = "SELECT * FROM `users` WHERE `id` = '".$userID."'";
									$result = $dbconnect->query($sql);
									$row = $result->fetch_assoc();
									?>
									<div class="form-group">
									<input type="text" class="form-control checkLetters" name="name" value="<?php echo $row["fullName"] ?>" >
									</div>
									<div class="form-group">
									<input type="number" class="form-control" name="phone" value="<?php echo $row["phone"] ?>" minlength="8" required>
									</div>
									<div class="form-group">
									<input type="<?php echo $emailHidden ?>" class="form-control" name="email" value="<?php echo $row["email"] ?>" >
									</div>
									<div class="form-group " id="civilIdDiv">
									<input type="hidden" class="form-control" name="civilId" placeholder="<?php echo $civilIdText ?>" >
									</div>
									<?php
								}else{
								?>
									<div class="form-group">
									<input type="text" class="form-control checkLetters" name="name" placeholder="<?php echo $fullNameText ?>" >
									</div>
									<div class="form-group">
									<input type="number" class="form-control" name="phone" placeholder="<?php echo $Mobile ?>" minlength="8" required >
									</div>
									<div class="form-group">
									<input type="<?php echo $emailHidden ?>" class="form-control" name="email" placeholder="<?php echo $emailText ?>" >
									</div>
									<div class="form-group " id="civilIdDiv">
									<input type="hidden" class="form-control" name="civilId" placeholder="<?php echo $civilIdText ?>" >
									</div>
								<?php
								}
								?>
							</div>

							<div class="checkout-informationbox">
								<div class="media checkout-heading-box">
									<span class="count-number">3</span>
									<div class="media-body">
										<h3 class="checkout-heading"><?php echo $addressText ?></h3>
										<p class="checkout-heading-text"></p>
									</div>
								</div>

								<ul class="nav nav-tabs" style="padding-right:0px">
								<li class="nav-item">
									<a class="nav-link active homeForm" id="homeFormId">
										<img src="<?php echo encryptImage("img/home.png") ?>" class="main-img">
										<img src="<?php echo encryptImage("img/home-active.png") ?>" class="active-img">
										<p><?php echo $houseText ?></p>
									</a>
								</li>
								<li class="nav-item">
									<a class="nav-link apartmentForm" id="apartmentFormId">
										<img src="<?php echo encryptImage("img/apartment.png") ?>" class="main-img">
										<img src="<?php echo encryptImage("img/apartment-active.png") ?>" class="active-img">
										<p><?php echo $apartmentText ?></p>
									</a>
								</li>

								<?php
								if ( $inStore == "1" ){
								?>
								<li class="nav-item">
									<a class="nav-link pickUpFROM" id="pickUpFROMid">
										<img src="https://i.imgur.com/8k3poG6.png" class="main-img" style="width:31px; height:31px">
										<img src="https://i.imgur.com/8k3poG6.png"  style="color: #f00;-webkit-filter: invert(100%);filter: invert(100%);width:31px; height:31px" class="active-img">
										<p><?php echo $pickUpText ?></p>
									</a>
								</li>
								<?php
								}
								if ( $noAddressOpt == "1" ){
								?>
								<li class="nav-item">
									<a class="nav-link noAddressFROM" id="noAddressFROMid">
										<img src="https://i.imgur.com/8k3poG6.png" class="main-img" style="width:31px; height:31px">
										<img src="https://i.imgur.com/8k3poG6.png"  style="color: #f00;-webkit-filter: invert(100%);filter: invert(100%);width:31px; height:31px" class="active-img">
										<p><?php echo direction("No Address","لا يوجد عنوان") ?></p>
									</a>
								</li>
								<?php
								}
								?>
								</ul>

								<div class="form-group areaSelection">
									<p class="mb-2"><?php echo $countryText ?></p>
									<select name="address[country]" class="form-control CountryClick select2Country" required>
										<option value="KW" selected >Kuwait</option>
										<?php
										if( $countries = selectDB("cities","`status` = '1' AND `CountryCode` NOT LIKE 'KW' GROUP BY `CountryCode` ORDER BY `CountryName` ASC") ){
											for( $i =0; $i < sizeof($countries); $i++ ){
										?>
										<option value="<?php echo $countries[$i]["CountryCode"] ?>"><?php echo $countries[$i]["CountryName"] ?></option>
										<?php
											}
										}
										?>
									</select>
									<i class="fa fa-angle-down d-none"></i>
								</div>

								<div class="form-group areaSelection">
									<p class="mb-2"><?php echo $selectAreaText ?></p>
									<select name="address[area]" class="form-control getAreas select2Area" required>
										<option selected disabled value=""><?php echo $selectAreaText ?></option>
										<?php 
										$orderAreas = direction("enTitle","arTitle");
										if( $areas = selectDB("areas","`id` != '0' AND `status` = '0' ORDER BY `{$orderAreas}` ASC") ){
											for( $i =0; $i < sizeof($areas); $i++ ){
											?>
											<option value="<?php echo $areas[$i]['id'] ?>">
											<?php
											echo direction($areas[$i]["enTitle"],$areas[$i]["arTitle"]);
											?>
											</option>
											<?php
											}
										}
										?>
									</select>
								</div>

								<div class="tab-content">
									<input type="hidden" class="form-control" id="pMethod" name="paymentMethod" value="" required>
									<input type="hidden" class="form-control" id="place" name="address[place]" value="1">

									<div id="" class="tab-pane active homeFormDiv addressDiv">
										<div class="form-group">
											<input type="text" class="form-control checkLetters" id="block" name="address[block]" placeholder="<?php echo $blockText ?>" required>
										</div>
										<div class="form-group">
											<input type="text" class="form-control checkLetters" id="street" name="address[street]" placeholder="<?php echo $streetText ?>" required>
										</div>
										<div class="form-group">
											<input type="text" class="form-control checkLetters" id="avenue" name="address[avenue]" placeholder="<?php echo $avenueText ?>" >
										</div>
										<div class="form-group">
											<input type="text" class="form-control checkLetters" id="building" name="address[building]" placeholder="<?php echo direction("Building","المبنى") ?>" required>
										</div>
										<div class="form-group">
											<input type="hidden" class="form-control checkLetters" id="floor" name="address[floor]" placeholder="<?php echo $floorText ?>" value="">
										</div>
										<div class="form-group">
											<input type="hidden" class="form-control checkLetters" id="apartment" name="address[apartment]" placeholder="<?php echo $apartmentText ?>" value="">
										</div>
										<div class="form-group">
											<input type="text" class="form-control checkLetters" id="postalCode" name="address[postalCode]" placeholder="<?php echo direction("Postal Code","رمز صندوق البريد") ?>" pattern="^[a-zA-Z0-9\s]+$">
										</div>
										<div class="form-group">
											<input type="text" class="form-control" id="notes" name="address[notes]" placeholder="<?php echo $specialInstructionText ?>">
										</div>
									</div>

									<div id="" class="tab-pane active noAddressDiv addressDiv" style="display:none">
										<div class="form-group">
											<input type="text" class="form-control checkLetters" id="noAddressName" name="address[noAddressName]" placeholder="<?php echo direction("Recipient name","اسم المستلم") ?>">
										</div>
										<div class="form-group">
											<input type="text" class="form-control checkLetters" id="noAddressPhone" name="address[noAddressPhone]" placeholder="<?php echo direction("Recipient phone","هاتف المستلم") ?>">
										</div>
									</div>
									
								</div>
							</div>

							<div class="checkout-informationbox">
								<div class="media checkout-heading-box">
									<span class="count-number">4</span>
									<div class="media-body">
										<h3 class="checkout-heading"><?php echo $paymentMethodText ?></h3>
										<p class="checkout-heading-text"></p>
									</div>
								</div>
								<div class="row form-row d-flex payment-box">
									<?php
									if( is_array($paymentOptions)){
										for( $i  = 0; $i < sizeof($paymentOptions); $i++){
											$paymentClassLabelId = str_replace("-","",str_replace("/","",str_replace(" ","",direction($paymentOptions[$i]["enTitle"],$paymentOptions[$i]["arTitle"]))));
											?>
											<div class="col-sm-4 col-4 col-md-4" id="<?php echo $paymentOptions[$i]["paymentId"] ?>p_m">
												<a class="<?php echo $paymentClassLabelId ?>" id="<?php echo $paymentOptions[$i]["paymentId"] ?>"><label id="pMethods<?php echo $paymentOptions[$i]["paymentId"] ?>" class="pMethods radiocardwrapper">
													<i class="<?php echo $paymentOptions[$i]["icon"] ?>"></i>
													<span class="cardcontent d-block"><?php echo direction($paymentOptions[$i]["enTitle"],$paymentOptions[$i]["arTitle"]) ?></span> 
												</label></a>
											</div>
											<?php
										}
									}
									?>
								</div>
								<div class="mt-5">
									<p class="pl-1 mt-4"><?php //echo $termsAndConditionsText ?></p><button class="btn theme-btn w-100 payBtnNow"><?php echo $payNowText ?></button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
