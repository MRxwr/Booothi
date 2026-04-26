<div class="modal fade cart-popup <?php echo $directionCART ?>" id="cart_popup">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header d-flex justify-content-between align-items-center">
            <div class="CartItem">
                <?php echo cartSVG() ?>
                <span class="cartItemNo"><?php echo getCartItemsTotal(); ?></span>
            </div>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
            <div class="items-wrapper">
			<form action="?v=Checkout" method="post">
                <!-- start cart item -->
                <?php
                $getCartId = json_decode($_COOKIE[$cookieSession."activity"],true);
				if ( $cart = selectDBNew("cart",[$getCartId["cart"]],"`cartId` = ?","") ){
					$extraPrice = [0];
					for ($i =0; $i < sizeof($cart); $i++){
						$product = selectDB("products","`id` = '{$cart[$i]["productId"]}'");
						$attribute = selectDB("attributes_products","`id` = '{$cart[$i]["subId"]}'");
						$image = selectDB("images","`productId` = '{$cart[$i]["productId"]}' ORDER BY `id` ASC LIMIT 1");
						if( $product[0]["discountType"] == 0 ){
							$sale = $attribute[0]["price"] * ( 1 - ($product[0]["discount"] / 100) );
						}else{
							$sale = $attribute[0]["price"] - $product[0]["discount"];
						}
						if( $product[0]["discount"] != 0 ){
							$realPrice = "[<span style='text-decoration: line-through;'>".numTo3Float(priceCurr($attribute[0]["price"])).selectedCurr()."]</span>";
						}else{
							$realPrice = "";
						}
						$total = $sale * $cart[$i]["quantity"];
					?>
					<div class="ItemBox itembox_<?php echo $cart[$i]["id"] ."-". $cart[$i]["subId"] ?>" id="item_<?php echo $i ?>">
						<div>
							<input type="text" name="qorder<?php echo $i ?>" class="form-control input-number" value="<?php echo $cart[$i]["quantity"] ?>" min="1" max="10" style="border-radius:0;border: 1px solid #e7eaf3;text-align:center;height:37px;width:52px; background-color: transparent;" readonly >
						</div>
						<!-- img -->
						<img src="<?php echo encryptImage("logos/b{$image[0]["imageurl"]}") ?>" class="CartItem-Image">
						<!-- info -->
						<div class="Information">
							<span class="Name">
							<?php
							echo direction($product[0]["enTitle"],$product[0]["arTitle"]);
							echo " ";
							echo direction($attribute[0]["enTitle"],$attribute[0]["arTitle"]);
							echo " ";
							$items = ( $cart[$i]["collections"] == 'null' ) ? [] : json_decode($cart[$i]["collections"],true);
							for( $y = 0; $y < sizeof($items) ; $y++ ){
								if ( !empty($items[$y]) ){
									$productsInfo = selectDB('products', "`id` = '{$items[$y]}'");
									echo "<br>[";
									echo direction
									($productsInfo[0]["enTitle"],$productsInfo[0]["arTitle"]);
									echo "]";
								}
							}
							$extras = json_decode($cart[$i]["extras"],true);
							for( $y = 0; $y < sizeof($extras["id"]) ; $y++ ){
								if ( !empty($extras["variant"][$y]) ){
									$extraInfo = selectDB('extras', "`id` = '{$extras["id"][$y]}'");
									$extraInfo[0]["price"] = ($extraInfo[0]["priceBy"] == 0 ? $extraInfo[0]["price"] : $extras["variant"][$y]);
									$extras["variant"][$y] = ($extraInfo[0]["priceBy"] == 0 ? $extras["variant"][$y] : "");
									$extraPriceView = ( $extraInfo[0]["price"] == 0 ? "]" : numTo3Float(priceCurr($extraInfo[0]["price"])). selectedCurr()." ]" );
									echo "<br>[";
									echo direction
									($extraInfo[0]["enTitle"],$extraInfo[0]["arTitle"]);
									echo ": {$extras["variant"][$y]} " . $extraPriceView;
									$extraPrice[] = $extraInfo[0]["price"]*$cart[$i]["quantity"];
								}
							}
							$items = json_decode($cart[$i]["giftCard"],true);
							if ( isset($items["body"]) ){
								$items["to"] = ( $items["to"] == "" ? "" : $items["to"]);
								$items["from"] = ( $items["from"] == "" ? "" : $items["from"]);
								$items["body"] = ( $items["body"] == "" ? "" : $items["body"]);
								echo " [To:{$items["to"]}, From:{$items["from"]}, Message:{$items["body"]}]</span>";
							}
							echo " ";
							echo $cart[$i]["note"];
							?>
							</span>
							<span class="Price"><?php echo "{$realPrice} " . numTo3Float(priceCurr($sale)) . selectedCurr() . " " . $perPieceText; ?>
							</span>
						</div>
						<!-- total per item -->
						<span class="Total itemTotal_<?php echo $cart[$i]["productId"] ?>"><?php echo numTo3Float(priceCurr($total+array_sum($extraPrice))) . selectedCurr(); $extraPrice = [0];?></span>
						<!-- remove item -->
						<button class="RemoveButton" id="<?php echo "{$cart[$i]["id"]}-{$cart[$i]["subId"]}-{$i}" ?>"><svg xmlns="http://www.w3.org/2000/svg" width="10.003" height="10" viewBox="0 0 10.003 10"><path data-name="_ionicons_svg_ios-close (5)" d="M166.686,165.55l3.573-3.573a.837.837,0,0,0-1.184-1.184l-3.573,3.573-3.573-3.573a.837.837,0,1,0-1.184,1.184l3.573,3.573-3.573,3.573a.837.837,0,0,0,1.184,1.184l3.573-3.573,3.573,3.573a.837.837,0,0,0,1.184-1.184Z" transform="translate(-160.5 -160.55)" fill="currentColor"></path></svg></button>

					</div>
                <?php
					}
				}
                ?>
                <!-- end cart item -->
            </div>
        </div>
        <div class="CheckoutButtonWrapper">
                <?php
                $sql = "SELECT `minPrice` FROM `s_media` WHERE `id` = 2";
                $result = $dbconnect->query($sql);
                $row = $result->fetch_assoc();
				$totals1 =  substr(getCartPriceTotal(),0,6);
                if ( $row["minPrice"] > (float)$totals1 ){
                ?>
            <div class="CheckoutButton checkout-pad-cust" data-toggle="modal" data-target="#minPrice_popup">
                <div class="CheckoutTitle"><b><?php echo $proceedToPaymentText ?></b></div>
                <span class="PriceBox"><?php echo getCartPriceTotal(); ?></span>
            </div>
            <?php }else{ ?>
            <button class="CheckoutButton checkout-pad-cust" <?php
			if ( getCartItemsTotal() < 1 ){
				echo "disabled";
			} 
			?>
			>
                <div class="CheckoutTitle"><b><?php echo $proceedToPaymentText ?></b></div>
				<span class="PriceBox"><?php echo getCartPriceTotal(); ?></span>
            </button>
            <?php } ?>
			</form>
        </div>
    </div>
  </div>
</div>