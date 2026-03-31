<?php
$totals2 = (float)substr(getCartPrice(),0,6);
if (isset($_POST["paymentMethod"]) AND $_POST["paymentMethod"] == "2"){
    $VisaCard =  round(($totals2*2.75/100),2);
    //$totals2 = $totals2 + $VisaCard ;
    $totals2 = $totals2 ;
}else{
    $VisaCard = 0 ;
}

$getCartId = json_decode($_COOKIE[$cookieSession."activity"],true);
if ( $cart = selectDBNew("cart",[$getCartId["cart"]],"`cartId` = ?","") ){
	$items = $cart;
	for( $i = 0; $i < sizeof($items); $i++ ){
		unset($items[$i]["collections"]);
		unset($items[$i]["extras"]);
		unset($items[$i]["giftCard"]);
		$items[$i]["collections"] = json_decode($cart[$i]["collections"],true);
		$items[$i]["extras"] = json_decode($cart[$i]["extras"],true);
		$items[$i]["giftCard"] = json_decode($cart[$i]["giftCard"],true);
		if( $subQuan = selectDBNew("attributes_products",[$items[$i]["subId"],$items[$i]["quantity"]],"`id` = ? AND `quantity` >= ?","") ){
			$items[$i]["price"] = $subQuan[0]["price"];
			$items[$i]["discountPrice"] = checkProductDiscountDefault($items[$i]["subId"]);
			if(isset($_POST["voucher"])){
			  $items[$i]["priceAfterVoucher"] = numTo3Float(checkItemVoucherDefault($_POST["voucher"],$items[$i]["subId"]));  
			}else{
			   $items[$i]["priceAfterVoucher"] = 0  ;
			}
			
			if( $items[$i]["priceAfterVoucher"] != 0 ){
				$paymentAPIPrice[] = $items[$i]["priceAfterVoucher"];
			}elseif( $items[$i]["discountPrice"] != $items[$i]["price"]){
				$paymentAPIPrice[] = $items[$i]["discountPrice"];
			}else{
				$paymentAPIPrice[] = $items[$i]["price"];
			}
		}else{
			deleteDBNew("cart",[$cart[$i]["id"]],"`id` = ?");
			header("LOCATION: ?v=Checkout&error=5");die();
		}
	}
}

if ( isset($_POST["address"]["place"]) && !empty($_POST["address"]["place"]) && $_POST["address"]["place"] != 3 && $_POST["address"]["place"] != 4 ){
	if ( $_POST["address"]["country"] == "KW" && $delivery = selectDBNew("areas",[$_POST["address"]["area"]],"`id` = ?","") ){
		$shoppingCharges = $delivery[0]["charges"];
	}elseif( $delivery = selectDBNew("stores",[$storeCode],"`storeCode` = ?","") ){
		if( $delivery[0]["shippingMethod"] != 0 ){
			$PaymentAPIKey = $delivery[0]["PaymentAPIKey"];
			$settingsShippingMethod = $delivery[0]["shippingMethod"];
			$shoppingCharges = getInternationalShipping(getItemsForPayment($getCartId["cart"],$paymentAPIPrice),$_POST["address"]);
		}else{
			$shippingPerPiece = json_decode($delivery[0]["internationalDelivery"],true);
			if ( getCartQuantity() == 1 ){
				$shoppingCharges = $shippingPerPiece[0];
			}else{
				$shoppingCharges = ($shippingPerPiece[0] * (getCartQuantity() - 1 ) ) + $shippingPerPiece[1];
			}
		}
	}
	$userDelivery = $shoppingCharges;
}elseif( $_POST["address"]["place"] == 4 ){
	$userDelivery = $noAddressDelivery;
}else{
	$userDelivery = 0;
}
$_POST["address"]["shipping"] = $userDelivery;

//////////// for payment page //////////////////
$info = array(
	"name" => $_POST["name"],
	"phone" =>  convertMobileNumber($_POST["phone"]),
	"email" => validateEmail($_POST["email"]),
	"civilId" => $_POST["civilId"]
);
?>
<style>
	body{
		background-color:#fafafa
	}
</style>
<div class="sec-pad grey-bg">
	<div class="container">
		<div class="row d-flex justify-content-center">
			<div class="col-lg-10 col-12">
				<div class="checkout-page">
					<div class="make-me-sticky check-make-me-sticky">
						<h3 class="bold text-center mb-4 mt-3 pb-3"><?php echo $cartText ?></h3>
						<div class="checkoutsidebar">
							<?php
							if ( getCartItemsTotal() < 1 || !isset($_POST["address"]["place"]) ){
								if ( isset($_SERVER['HTTP_REFERER']) ){
									header('Location: ' . $_SERVER['HTTP_REFERER']);
								}
							}else{
								echo loadCartItems();
							}
							?>
						</div>
						<div class="checkoutsidebar-calculation">

							<div class="calc-text-box d-flex justify-content-between">
								<span class="calc-text bold subTotalPrice"><?php echo $subTotalPriceText ?></span>
								<span class="calc-text bold SubTotal">
								<?php echo getCartPrice(); ?>
								</span>
							</div>

							<div class="calc-text-box d-flex justify-content-between">
								<span class="calc-text bold subTotalPrice"><?php echo direction("Add-ons","الإضافات") ?></span>
								<span class="calc-text bold addon">
								<?php echo numTo3Float((float)substr(getExtarsTotal(),0,6)).selectedCurr() ?>
								</span>
							</div>

							<div class="calc-text-box d-flex justify-content-between">
								<span class="calc-text bold"><?php echo $discountText ?></span>
								<span class="calc-text bold DiscountSpan">
								<?php echo 0 ; ?>%
								</span>
							</div>

							<div class="calc-text-box d-flex justify-content-between">
								<span class="calc-text bold"><?php echo $deliveryText ?></span>
								<span class="calc-text bold ShoppingSpan">
								<?php echo numTo3Float(priceCurr($userDelivery)) . selectedCurr();?>
								</span>
							</div>

							<?php
							if( isset($userID) ){
							?>
							<div class="calc-text-box d-flex justify-content-between">
								<span class="calc-text bold"><?php echo $userDiscountText ?></span>
								<span class="calc-text bold UserDiscount">
								<?php echo $userDiscount."%"; ?>
								</span>
							</div>
							<?php
							}else{
								$userDiscount = 0;
							}
							?>

							<div class="calc-text-box d-flex justify-content-between">
								<span class="calc-text bold"><?php echo $totalPriceText ?></span>
								<span class="calc-text bold totalSpan">
								<?php
									if ( isset($userDiscount) && !empty($userDiscount) ){
										$totals2 = (float)substr(getCartPrice(),0,6);
										$totals2 = ((100-$userDiscount)/100)*$totals2;
									}
									$totals21 = $totals2 + priceCurr($userDelivery) + (float)substr(getExtarsTotal(),0,6); 
									echo numTo3Float((float)$totals21) . selectedCurr();
									?>
								</span>
							</div>

							<span style="color:red"><?php echo direction($settingsDTime,$settingsDTimeAr);  ?></span>

							<?php
							if( $_POST["address"]["country"] == "KW" && !empty($expressDelivery) ){
								
								$expressOption = direction("Experss Delivery","توصيل سريع");
								$expressPeriod = direction($expressDelivery["englishNote"],$expressDelivery["arabicNote"]);
								var_dump($expressPrice = numTo3Float(priceCurr($expressDelivery["charge"])) . selectedCurr());die();
								
								
								if( isset($expressDelivery["status"]) && $expressDelivery["status"] == 1 ){
									echo "<div class='mt-3'><input name='express' type='checkbox' class=''> <span>{$expressOption} {$expressPeriod} - {$expressPrice}</span></div>";
								}else{
									$expressPrice = 0;
								}
							}
							
							?>

							<div class="calc-text-box d-flex justify-content-between">
								<span class="bold voucherMsgS" style="color:red;font-size:18px"><b class="voucherMsg"></b></span>
							</div>

							<span class="PromoCode d-block text-right">
								<button id="voucher_text" style="font-size:20px"><?php echo $doYouHaveAVoucherText ?></button>
								<div class="cart_CouponBoxWrapper p-0" id="voucher_code">
									<div class="CouponBoxWrapper" style="border-color: #a8a8a8;">
										<div class="InputWrapper w-100">
											<div class="inner-wrap">
												<input type="text" name="voucher" id="voucherInput" placeholder="" class="icon-left" value="" autocomplete="chrome-off">
											</div>
										</div>
										<input type="submit" class="ButtonStyle btn-text sendVoucher" value="<?php echo $sendText ?>" >
										</button>
									</div>
								</div>
							</span>

						</div>
					</div>

					<div>
						<form method="POST" action="payment" enctype="multipart/form-data">
							<input type="hidden" class="form-control orderVoucherInput" name="voucher" value="">
							<input type="hidden" name="paymentMethod" value="<?php echo $_POST["paymentMethod"] ?>">
							<input type="hidden" name="creditTax" class="VisaClass" value="<?php echo $VisaCard ?>">
							<input type="hidden" name="expressDelivery" id="expressDel" value="0">
							<textarea style="display:none" name="info"><?php echo json_encode($info,JSON_UNESCAPED_UNICODE) ?></textarea>
							<textarea style="display:none" name="giftCard"><?php echo json_encode($_POST["giftCard"],JSON_UNESCAPED_UNICODE) ?></textarea>
							<textarea style="display:none" name="address"><?php echo json_encode($_POST["address"],JSON_UNESCAPED_UNICODE) ?></textarea>
							<input type="submit" name="submit" class="btn btn-large" style="width:100%;background-color:<?php echo $websiteColor ?>; color:<?php echo $headerButton ?>" value="<?php echo $proceedToPaymentText ?>">
						</form>
					</div>

				</div>
			</div>
		</div>
	</div>
</div>