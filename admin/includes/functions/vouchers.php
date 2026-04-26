<?php
function checkItemVoucher($code,$id){
	$sale = checkProductDiscountDefault($id);
	if( $voucher = selectDBNew("vouchers",[$code,date("Y-m-d"),date("Y-m-d")],"`id` = ? AND `endDate` >= ? AND `startDate` <= ?","") ){
		$voucherId = $voucher[0]["id"];
		if( $voucher[0]["type"] == 1 ){
			if( $voucher[0]["discountType"] == 1 ){
				$price = ($sale * ((100-$voucher[0]["discount"])/100));
			}elseif( $voucher[0]["discountType"] == 2 ){
				$price = ($sale - $voucher[0]["discount"]);
			}
			return numTo3Float(priceCurr($price));;
		}elseif( $voucher[0]["type"] == 2 ){
			$price = $sale;
			if( $voucher = selectDBNew("vouchers",[$id],"JSON_UNQUOTE(JSON_EXTRACT(items,'$[*]')) LIKE ?","") ){
				if( $voucher[0]["discountType"] == 1 ){
					$price = $price * ((100-$voucher[0]["discount"])/100);
				}else{
					$price = $price - $voucher[0]["discount"];
				}
			}
			return numTo3Float(priceCurr($price));;
		}elseif( $voucher[0]["type"] == 3 ){
			$price = $sale;
			if( $voucher = selectDBNew("vouchers",[$id],"JSON_UNQUOTE(JSON_EXTRACT(items,'$[*]')) LIKE ?","") ){
				if( $voucher[0]["discountType"] == 1 ){
					$price = $price * ((100-$voucher[0]["discount"])/100);
				}else{
					$price = $price - $voucher[0]["discount"];
				}
			}
			return numTo3Float(priceCurr($price));
		}
	}else{
		return numTo3Float(0);
	}
}

function voucherApplyToAll($code){
	$code = selectDBNew("vouchers",[$code],"`id` = ?","");
	if( $code[0]["discountType"] == 1 ){
		return ((float)substr(getCartPrice(),0,6) * ((100-$code[0]["discount"])/100));
	}elseif( $code[0]["discountType"] == 2 ){
		return ((float)substr(getCartPrice(),0,6) - priceCurr($code[0]["discount"]));
	}
}

function voucherApplyToAllVoucher($code,$total){
	$code = selectDBNew("vouchers",[$code],"`id` = ?","");
	if( $code[0]["discountType"] == 1 ){
		return ((float)$total * ((100-$code[0]["discount"])/100));
	}elseif( $code[0]["discountType"] == 2 ){
		return ((float)$total - priceCurr($code[0]["discount"]));
	}
}

function voucherSelectedItems($code){
	GLOBAL $_COOKIE,$cookieSession;
	$getCartId = json_decode($_COOKIE[$cookieSession."activity"],true);
	$code = selectDBNew("vouchers",[$code],"`id` = ?","");
	$cart = selectDBNew("cart",[$getCartId["cart"]],"`cartId` = ?","");
	$items = json_decode($code[0]["items"],true);
	for ( $i = 0; $i < sizeof($cart); $i++ ){
		$subProduct = selectDBNew("attributes_products",[$cart[$i]["subId"]],"`id` = ?","");
		$product = selectDBNew("products",[$cart[$i]["productId"]],"`id` = ?","");
		$price = $subProduct[0]["price"];
		if ( $code = selectDBNew("vouchers",[$cart[$i]["productId"]],"JSON_UNQUOTE(JSON_EXTRACT(items,'$[*]')) LIKE ?","") ){
			if( $code[0]["discountType"] == 1 ){
				$price = $price * ((100-$code[0]["discount"])/100);
			}else{
				$price = $price - $code[0]["discount"];
			}
		}else{
			if( $product[0]["discountType"] == 0 ){
				$price = $subProduct[0]["price"] * ((100-$product[0]["discount"])/100);
			}else{
				$price = $subProduct[0]["price"] - $product[0]["discount"];
			}
		}
		$price = $price * $cart[$i]["quantity"];
		$finalPrice[] = $price;
	}
	return priceCurr(array_sum($finalPrice));
}

function voucherDoubleDiscount($code){
	GLOBAL $_COOKIE,$cookieSession;
	$getCartId = json_decode($_COOKIE[$cookieSession."activity"],true);
	$code = selectDBNew("vouchers",[$code],"`id` = ?","");
	$cart = selectDBNew("cart",[$getCartId["cart"]],"`cartId` = ?","");
	$items = json_decode($code[0]["items"],true);
	for ( $i = 0; $i < sizeof($cart); $i++ ){
		$subProduct = selectDBNew("attributes_products",[$cart[$i]["subId"]],"`id` = ?","");
		$product = selectDBNew("products",[$cart[$i]["productId"]],"`id` = ?","");
		if( $product[0]["discountType"] == 0 ){
			$price = $subProduct[0]["price"] * ((100-$product[0]["discount"])/100);
		}else{
			$price = $subProduct[0]["price"] - $product[0]["discount"];
		}
		if ( $code = selectDBNew("vouchers",[$cart[$i]["productId"]],"JSON_UNQUOTE(JSON_EXTRACT(items,'$[*]')) LIKE ?","") ){
			if( $code[0]["discountType"] == 1 ){
				$price = $price * ((100-$code[0]["discount"])/100);
			}else{
				$price = $price - $code[0]["discount"];
			}
		}
		$price = $price * $cart[$i]["quantity"];
		$finalPrice[] = $price;
	}
	return priceCurr(array_sum($finalPrice));
}
?>