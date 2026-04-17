<script type="text/javascript">
$(function(){
	<?php
		if( isset($_GET["e"]) ){
			?>
			var qorder =  "<?php echo $_GET['c'] ?>";
			var msgError = "<?php echo direction('Please choose a number below ','الرجاء إختيار رقم أقل من ') ?>"
			alert(msgError + qorder);
			<?php
		}  
	?>
	$("body").on('click','#wishlistBtn',function(e){
		e.preventDefault();
		if ( confirm("<?php echo direction("Are you sure you want to add this item to your wishlist?","هل انت متأكد من إنك تريد اضافة هذا المنتج لقائمة المفضلة؟") ?>") ){
			var id = <?php echo $product[0]["id"] ?>;
			var wishlistArray = JSON.parse($.cookie("<?php echo $cookieSession . "activity" ?>"));
			wishlistArray["wishlist"]["id"].push(id)
			$.cookie("<?php echo $cookieSession . "activity" ?>", JSON.stringify(wishlistArray));
			$("#wishlistTotal").html(wishlistArray["wishlist"]["id"].length);
			$("#wishlistTotal1").html(wishlistArray["wishlist"]["id"].length);
			alert("<?php echo direction("Item has been added to your wishlist successfully.","تمت إضافة المنتج لقائمتك المفضلة بنجاح") ?>");
		}
	});
	
	$("body").on('change','.selectedOptions',function(e){
		e.preventDefault();
		var id = $(this).val();
		$("#sku").html($("#sku"+id).html());
		$("#price").html($("#price"+id).html()+"<?php echo selectedCurr() ?>");
		$("#sale").html($("#priceBefore"+id).html()+"<?php echo selectedCurr() ?>");
		$("input[name=qorder]").attr("max",$("#quantity"+id).html());
		var quan = parseInt($("#quantity"+id).html());
		if( quan <= 0 ){
			$("#subminBtn").attr("disabled","disabled");
			$("#subminBtn").html('<span class="fa fa-shopping-cart"></span> ' + "<?php echo direction("Sold Out","انتهت الكمية") ?>");
		}else{
			$("#subminBtn").removeAttr("disabled");
			$("#subminBtn").html('<span class="fa fa-shopping-cart"></span> ' + "<?php echo direction("Add to cart","أضف للسلة") ?>");
		}
	});

	$(document).ready(function(){
		var maxQuantity = $("input[name=qorder]").attr("max");
		if( maxQuantity <= 0 ){
			$("#subminBtn").attr("disabled","disabled");
			$("#subminBtn").html('<span class="fa fa-shopping-cart"></span> ' + "<?php echo direction("Sold Out","انتهت الكمية") ?>");
		}
	});


	$('.input-number').focusin(function(){
		$(this).data('oldValue', $(this).val());
	});

	$('.input-number').change(function(){
		minValue =  parseInt($(this).attr('min'));
		maxValue =  parseInt($(this).attr('max'));
		valueCurrent = parseInt($(this).val());
		name = $(this).attr('name');
		if(valueCurrent >= minValue){
			$(".btn-number[data-type='minus'][data-field='"+name+"']").removeAttr('disabled')
		}else{
			alert('Sorry, the minimum value was reached');
			$(this).val($(this).data('oldValue'));
		}
		if(valueCurrent <= maxValue){
			$(".btn-number[data-type='plus'][data-field='"+name+"']").removeAttr('disabled')
		}else{
			alert('Sorry, the maximum value was reached');
			$(this).val($(this).data('oldValue'));
		}


	});
	$(".input-number").keydown(function (e) {
		// Allow: backspace, delete, tab, escape, enter and .
		if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 190]) !== -1 ||
			 // Allow: Ctrl+A
			(e.keyCode == 65 && e.ctrlKey === true) || 
			 // Allow: home, end, left, right
			(e.keyCode >= 35 && e.keyCode <= 39)) {
				 // let it happen, don't do anything
				 return;
		}
		// Ensure that it is a number and stop the keypress
		if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
			e.preventDefault();
		}
	});

	if ( window.history.replaceState ) {
		window.history.replaceState( null, null, window.location.href );
	}
});

$(".img_producto_container").on("mouseover", function() {
$(this).children(".img_producto").css({ transform: "scale(" + $(this).attr("data-scale") + ")" });
}).on("mouseout", function() {
$(this).children(".img_producto").css({ transform: "scale(1)" });
}).on("mousemove", function(e) {
$(this).children(".img_producto").css({
	"transform-origin":
		((e.pageX - $(this).offset().left) / $(this).width()) * 100 +
		"% " +
		((e.pageY - $(this).offset().top) / $(this).height()) * 100 +
		"%"
	});
});

$().fancybox({
	selector : '.imglist .expand--img-popup',
	hash   : false,
	thumbs : {
		autoStart : false
	},
	buttons : [
		'fullScreen',
		'zoom',
		'download',
		'share',
		'close'

	]
});
$(function(){
	$('.LoginAj').click(function(e){
		e.preventDefault();
		var LoginEV = $('.LoginE').val();
		var loginPV = $('.LoginPass').val();
		$.ajax({
			type:"POST",
			url: "api/functions.php",
			data: {
				loginEmailAj: LoginEV,
				loginPassAj: loginPV,
			},
			success:function(result){
				console.log(result);
				var data = result.split(',');
				if ( data[0] == 1 ){
					if ( !alert(data[1]) ){
						window.location.reload();
					}
				}
				else{
					alert(data[1])
				}
			}
		});
	});
	
	$('.newUser').click(function(e){
		e.preventDefault();
		var newEmail = $('.newEmail').val()
		var newPhone = $('.newPhone').val()
		var newName = $('.newName').val()
		var newPassword = $('.newPass').val()
		$.ajax({
			type:"POST",
			url: "api/functions.php",
			data: {
				nameReg: newName,
				phoneReg: newPhone,
				emailReg: newEmail,
				passwordReg: newPassword,
			},
			success:function(result){
			alert(result);
			$("#reg_popup").removeClass('show');
			$('.modal-backdrop').removeClass('show');
			}
		});
	});
	
	$('.resetP').click(function(e){
		e.preventDefault();
		var emailFP = $('.userEmail').val()
		$.ajax({
			type:"POST",
			url: "api/functions.php",
			data: {
				emailAj: emailFP,
			},
			success:function(result){
				alert(result);
			}
		});
	});
	
	$('.editPassword').click(function(e){
		e.preventDefault();
		var editEmailV = $('.editEmail').val()
		var editPassV = $('.editPass').val()
		$.ajax({
			type:"POST",
			url: "api/functions.php",
			data: {
				editEmailAj: editEmailV,
				editPassAj: editPassV,
			},
			success:function(result){
				alert(result);
			}
		});
	});
	
	$(body).on('click','#wishlistHeart,#wishlistHeartMobile,#wishlistHeartMenu',function(){
		$.ajax({
			type:"POST",
			url: "api/wishlist.php",
			data: {
				id:1,
			},
			success:function(result){
				$("#wishlistBody").html(result);
			}
		});
	});
	
	$(body).on('load',function(){
		if(approiateAPICALL.orientation == "landscape")
		{
			$('#myModal').modal('show');
		}else{
			$('#myModal').modal('hide');
		}
	});
	
	$(body).on("click",'.removeWishlist', function(){
		if ( confirm("<?php echo direction("Are you sure you want to remove product from wishlist?","هل أنت متأكد من إزالة المنتج من قائمة المفضلة؟") ?>") ){
			var pos = $(this).attr("id");
			var wishlistArray = JSON.parse($.cookie("<?php echo $cookieSession . "activity" ?>"));
			wishlistArray["wishlist"]["id"].splice(pos,1)
			$.cookie("<?php echo $cookieSession . "activity" ?>", JSON.stringify(wishlistArray));
			$("#wishlistTotal").html(wishlistArray["wishlist"]["id"].length);
			$("#wishlistTotal1").html(wishlistArray["wishlist"]["id"].length);
			$.ajax({
				type:"POST",
				url: "api/wishlist.php",
				data: {
					id:1,
				},
				success:function(result){
					$("#wishlistBody").html(result);
					alert("<?php echo direction("Product removed successfully","تم إزالة المنتج بنجاح") ?>");
				}
			});
		}
	});

    $(document).ready(function() {
        new WOW().init();
    });

    $(function(){
        $('.selectpicker').selectpicker();
		$('.RemoveButton').click(function(e){
            e.preventDefault();
            var codeid = $(this).attr("id");
			codeIdItem = codeid.split('-'); 
			var itemId = codeIdItem[0];
			var itemSize = codeIdItem[1];
			var itemLoop = codeIdItem[2];
            $("#item_"+itemLoop).remove();

            // remove from session
            $.ajax({
                type:"POST",
                url: "api/functions.php",
                data: {
                    removeItemBoxId: itemId,
					removeItemBoxSubId: itemSize,
                },
                success:function(result){
					var resultArray = result.split(',')
                    //console.log(resultArray);
                    //$('.PriceBox').text(parseInt(result) + "KD");
                    var jqueryTotal = 0;
                    $('.Total').each(function(i, e){
                        jqueryTotal += parseInt($(e).text().trim().slice(0,-2));
                        //console.log(parseInt($(e).text().trim().slice(0,-2)));
                    });
                    $('.PriceBox').text(resultArray[1]);
                    $('.cart_price').text(resultArray[1]);
                    $('.cartItemNo').text(resultArray[0]);
                }
            })
        });
    });
    // for cart
    $(document).ready(function(){
        $("#voucher_text").click(function(){
            $("#voucher_text").attr('style','display:none');
            $("#voucher_code").attr('style','display:flex');
        });
    });
    //change directory
    $(document).ready(function(){
      $('.en').click(function() {
         $("html[lang=he]").attr("dir", "ltr");
          $("#body").addClass("left-to-right");
          $("#cart_popup").removeClass("left");
        $("#cart_popup").addClass("right");
      });
      $('.arab').click(function() {
         $("html[lang=he]").attr("dir", "rtl");
        $("#body").removeClass("left-to-right");
        $("#cart_popup").removeClass("right");
        $("#cart_popup").addClass("left");
      });
        
    });
    // cart popup
    $(window).scroll(function(){
      var sticky = $('.fixme'),
          scroll = $(window).scrollTop();

      if (scroll >= 500) sticky.addClass('fixed');
      else sticky.removeClass('fixed');
    });
    // owl carousel
    jQuery("#carousel").owlCarousel({
        autoplay: true,
        lazyLoad: true,
        loop: true,
        margin: 20,
        responsiveClass: true,
        autoHeight: true,
        autoplayTimeout: 3000,
        smartSpeed: 800,
        nav: true,
        dots: false,
        rtl: true,
      responsive: {
        0: {
          items: 1
        },

        600: {
          items: 2
        },

        1024: {
          items: 3
        },

        1366: {
          items: 3
        }
      }
    });
    // cat_slider
    jQuery("#cat_carousel").owlCarousel({
        autoplay: false,
        lazyLoad: true,
        loop: false,
        margin: 6,
        responsiveClass: true,
        nav: false,
        dots: false,
        rtl: true,
      responsive: {
        0: {
          items: 4
        },
        340: {
          items: 5
        },
        520: {
          items: 7
        },
        768: {
          items: 9
        }
      }
    });
    //TOGGLING NESTED ul
    $(".drop-down .selected a").click(function() {
        // $(".drop-down .options").toggle();
        $(".drop-down .options").toggleClass("show");
    });

//HIDE OPTIONS IF CLICKED on categories and scroll page on category product
      
$("#mobile-drop-cust-close .product-category-mobile").click(function() {
        // $(".drop-down .options").toggle();
         $(".drop-down .options").removeClass("show");

         $('html, body').animate({
        scrollTop: $(".main-product-sec").offset().top - 300
    }, 1000);
    });

    //HIDE OPTIONS IF CLICKED ANYWHERE ELSE ON PAGE
    $(document).bind('click', function(e) {
        var $clicked = $(e.target);
        if (! $clicked.parents().hasClass("drop-down"))
            $(".drop-down .options").removeClass("show");
    });
    /* Add to cart fly effect with jQuery. */   
    $('.add-to-cart').on('click', function () {
        var cart = $('.shopping-cart');
        var imgtodrag = $(this).parent('.product-meta').parent('.product-text').parent('.product-box').find(".product-box-img").eq(0);
        if (imgtodrag) {
            var imgclone = imgtodrag.clone()
                .offset({
                top: imgtodrag.offset().top,
                left: imgtodrag.offset().left
            })
                .css({
                    'opacity': '0.8',
                    'position': 'absolute',
                    'height': '120px',
                    'width': '120px',
                    'z-index': '100',
                    'border-radius': '50%'
            })
                .appendTo($('body'))
                .animate({
                'top': cart.offset().top + 10,
                    'left': cart.offset().left + 10,
                    'width': 75,
                    'height': 75
            }, 1000, 'easeInOutExpo');    
            // setTimeout(function () {
            //     cart.effect("shake", {
            //         times: 2
            //     }, 200);
            // }, 1500);
            imgclone.animate({
                'width': 0,
                    'height': 0
            }, function () {
                $(this).detach()
            });
        }
    });
    // for cart
    $(document).ready(function(){
        $(".add-to-cart-btn").click(function(){
            $(this).attr('style','display:none');
            $(this).next().attr('style','display:flex');
        });
        $(".counter_add").click(function() {
            var new_count = parseInt($(this).prev().html()) + 1;
            $(this).prev().html(new_count)
        });

        $(".counter_minuse").click(function() {
            var exist_count = parseInt($(this).next().html());
            if(exist_count > 1){
                $(this).next().html(exist_count - 1)
            }else{
                $(this).parent().prev().attr('style','display:flex');
                $(this).parent().attr('style','display:none');
            }
        });
        // for cart popup
        $(".cart_counter_add").click(function() {
            var new_count = parseInt($(this).prev().html()) + 1;
            $(this).prev().html(new_count)
        });

        $(".cart_counter_minuse").click(function() {
            var exist_count = parseInt($(this).next().html());
            if(exist_count > 1){
                $(this).next().html(exist_count - 1)
            }else{
                $(this).parent().prev().attr('style','display:flex');
                $(this).parent().parent().attr('style','display:none');
            }
        });
    });
    // product view slider
    $(document).ready(function() {
        var sync1 = $("#sync1");
        var sync2 = $("#sync2");
        var slidesPerPage = 4; //globaly define number of elements per page
        var syncedSecondary = true;

        sync1.owlCarousel({
            items: 1,
            slideSpeed: 2000,
            nav: false,
            autoplay: false, 
            dots: true,
            loop: true,
            rtl: true,
            responsiveRefreshRate: 200,
            navText: ['<svg width="100%" height="100%" viewBox="0 0 11 20"><path style="fill:none;stroke-width: 1px;stroke: #000;" d="M9.554,1.001l-8.607,8.607l8.607,8.606"/></svg>', '<svg width="100%" height="100%" viewBox="0 0 11 20" version="1.1"><path style="fill:none;stroke-width: 1px;stroke: #000;" d="M1.054,18.214l8.606,-8.606l-8.606,-8.607"/></svg>'],
        }).on('changed.owl.carousel', syncPosition);

        sync2
            .on('initialized.owl.carousel', function() {
                sync2.find(".owl-item").eq(0).addClass("current");
            })
            .owlCarousel({
                items: slidesPerPage,
                dots: false,
                nav: false,
                rtl: true,
                smartSpeed: 200,
                slideSpeed: 500,
                slideBy: slidesPerPage, //alternatively you can slide by 1, this way the active slide will stick to the first item in the second carousel
                responsiveRefreshRate: 100
            }).on('changed.owl.carousel', syncPosition2);
    function syncPosition(el) {
        //if you set loop to false, you have to restore this next line
        //var current = el.item.index;

        //if you disable loop you have to comment this block
        var count = el.item.count - 1;
        var current = Math.round(el.item.index - (el.item.count / 2) - .5);

        if (current < 0) {
            current = count;
        }
        if (current > count) {
            current = 0;
        }
        //end block
        sync2
            .find(".owl-item")
            .removeClass("current")
            .eq(current)
            .addClass("current");
            var onscreen = sync2.find('.owl-item.active').length - 1;
            var start = sync2.find('.owl-item.active').first().index();
            var end = sync2.find('.owl-item.active').last().index();

            if (current > end) {
                sync2.data('owl.carousel').to(current, 100, true);
            }
            if (current < start) {
                sync2.data('owl.carousel').to(current - onscreen, 100, true);
            }
        }
        function syncPosition2(el) {
            if (syncedSecondary) {
                var number = el.item.index;
                sync1.data('owl.carousel').to(number, 100, true);
            }
        }

        sync2.on("click", ".owl-item", function(e) {
            e.preventDefault();
            var number = $(this).index();
            sync1.data('owl.carousel').to(number, 300, true);
        });
    });
	/////////////////////////////////////////
	$('.btn-number').click(function(e){
		e.preventDefault();
		
		fieldName = $(this).attr('data-field');
		type      = $(this).attr('data-type');
		qorder = $(this).attr('data-qorder');
		qitemId = $(this).attr('data-itemId');
		itemIndex = $(this).attr('data-itemIndex');
		itemPrice = $(this).attr('data-price');
		

		var input = $("input[name='"+fieldName+"']");
		var currentVal = parseInt(input.val());
		if (!isNaN(currentVal)) {
			if(type == 'minus') {
				$.ajax({
					type:"GET",
					url: "api/functions.php",
					data: {
						itemIndexM: itemIndex
						
					},
					success:function(result){
						var itemTotalPrice = parseInt(itemPrice) * parseInt(result);
						$('.itemTotal_' + qitemId).text(itemTotalPrice +"KD");
						
						var jqueryTotal = 0;
						$('.Total').each(function(i, e){
							jqueryTotal += parseInt($(e).text().trim().slice(0,-2));
						});
						$('.PriceBox').text(jqueryTotal + "KD");
					}
				});
				
				if(currentVal > input.attr('min')) {
					input.val(currentVal - 1).change();
				} 
				if(parseInt(input.val()) == input.attr('min')) {
					$(this).attr('disabled', true);
				}

			} else if(type == 'plus') {

				$.ajax({
					type:"GET",
					url: "api/functions.php",
					data: {
						itemIndexP: itemIndex
						
					},
					success:function(result){
						var itemTotalPrice = parseInt(itemPrice) * parseInt(result);
						$('.itemTotal_' + qitemId).text(itemTotalPrice +"KD");
						
						var jqueryTotal = 0;
						$('.Total').each(function(i, e){
							jqueryTotal += parseInt($(e).text().trim().slice(0,-2));
						});
						$('.PriceBox').text(jqueryTotal + "KD");

					}
				});
				
				if(currentVal < input.attr('max')) {
					input.val(currentVal + 1).change();
				}
				if(parseInt(input.val()) == input.attr('max')) {
					$(this).attr('disabled', true);
				}

			}
		} else {
			input.val(0);
		}
	});
	$('.input-number').focusin(function(){
	   $(this).data('oldValue', $(this).val());
	});
	$('.input-number').change(function() {
		
		minValue =  parseInt($(this).attr('min'));
		maxValue =  parseInt($(this).attr('max'));
		valueCurrent = parseInt($(this).val());
		
		name = $(this).attr('name');
		if(valueCurrent >= minValue) {
			$(".btn-number[data-type='minus'][data-field='"+name+"']").removeAttr('disabled')
		} else {
			alert('Sorry, the minimum value was reached');
			$(this).val($(this).data('oldValue'));
		}
		if(valueCurrent <= maxValue) {
			$(".btn-number[data-type='plus'][data-field='"+name+"']").removeAttr('disabled')
		} else {
			alert('Sorry, the maximum value was reached');
			$(this).val($(this).data('oldValue'));
		}
		
		
	});
	$(".input-number").keydown(function (e) {
		// Allow: backspace, delete, tab, escape, enter and .
		if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 190]) !== -1 ||
			 // Allow: Ctrl+A
			(e.keyCode == 65 && e.ctrlKey === true) || 
			 // Allow: home, end, left, right
			(e.keyCode >= 35 && e.keyCode <= 39)) {
				 // let it happen, don't do anything
				 return;
		}
		// Ensure that it is a number and stop the keypress
		if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
			e.preventDefault();
		}
	});

    if ( window.history.replaceState ) {
    window.history.replaceState( null, null, window.location.href );
    }
})

$(function(){
	function stripLetters(str) {
		const numericString = str.replace(/[^0-9.]/g, '');
		return parseFloat(numericString);
	}
	$("input[name=express]").change(function(){
		var delivery = stripLetters("<?php echo $userDelivery ?>");
		var expressDelivery = stripLetters("<?php echo $expressPrice ?>");
		var cartTotal = stripLetters($(".totalSpan").html())-stripLetters($(".ShoppingSpan").html());
		if ($(this).is(":checked")) {
			$(".ShoppingSpan").html(parseFloat(expressDelivery).toFixed(3)+"<?php echo selectedCurr() ?>");
			$(".totalSpan").html(parseFloat(cartTotal+expressDelivery).toFixed(3)+"<?php echo selectedCurr() ?>");
			$("#expressDel").val(expressDelivery);
		} else {
			$(".ShoppingSpan").html(parseFloat(delivery).toFixed(3)+"<?php echo selectedCurr() ?>");
			$(".totalSpan").html(parseFloat(cartTotal+delivery).toFixed(3)+"<?php echo selectedCurr() ?>");
			$("#expressDel").val(0);
		}
	})
	/*
	$('.sendVoucher').click(function(e){
		e.preventDefault();
		var voucher = $('#voucherInput').val()
		$.ajax({
			type:"POST",
			url: "api/functions.php",
			data: {
				checkVoucherVal: voucher,
				visaCardCheck: <?php echo $VisaCard ?>,
				userDiscountCheck: <?php echo $totals2 ?>,
				totals2: <?php echo $totals2 ?>,
				shippingChargesInput : stripLetters($(".ShoppingSpan").html()),
				paymentMethodInput : <?php echo $_POST["paymentMethod"] ?>,
				userDiscountPercentage: <?php echo $userDiscount; ?>,
			},
			success:function(result){
				console.log(result);
				var data = result.split(',');
				$('.totalSpan').text(data[0]+"<?php echo selectedCurr() ?>");
				$('.ShoppingSpan').text(data[3]+"<?php echo selectedCurr() ?>");
				$('.VisaSpan').text(data[5]+"<?php echo selectedCurr() ?>");
				$('.UserDiscount').text(data[6]+"%");
				$('.voucherMsg').html(data[1]);
				$('.orderVoucherInput').val(data[2]);
				$('.DiscountSpan').text(data[4]+"%");
				$('.VisaClass').val(data[5]);
				$('.SubTotal').text(data[7]+"<?php echo selectedCurr() ?>");
				$('.addon').text(data[8]+"<?php echo selectedCurr() ?>");
			}
		});
	});
	*/
})
$(function(){
	$(document).ready(function() {
		$('.select2Country').select2({
			theme: "classic"
		});
		$('.select2Area').select2({
			theme: "classic"
		});
		<?php
		if( isset($_GET["error"]) && !empty($_GET["error"]) ){
			if( $_GET["error"] == 1 ){
				?>
				alert("<?php echo direction("Failed to process your order, Please try again.","لم نستطع تنفيذ طلبك ، الرجاء المحاولة مجددا") ?>");
				<?php
			}elseif($_GET["error"] == 2 ){
				?>
				alert("<?php echo direction("Failed to read your cart, Please try again.","حصل خطأ اثناء قراة سلتك الرجاء المحاولة مجددا") ?>");
				<?php
			}elseif($_GET["error"] == 3 ){
				?>
				alert("<?php echo direction("Failed payment, Please try agian.","عملية دفع فاشلة، الرجاء المحاولة مجددا") ?>");
				<?php
			}elseif($_GET["error"] == 4 ){
				?>
				alert("<?php echo direction("Could not connect to payment gateway, Please try again.","لم نستطع التواصل مع بوابة الدفع، الرجاء المحاولة مجددا") ?>");
				<?php
			}elseif($_GET["error"] == 5 ){
				?>
				alert("<?php echo direction("An item has been deleted from you cart, please change quantity and try again.","تم حذف منتج من سلتك ، حاول تغيير الكمية و المحاولة مجددا") ?>");
				<?php
			}
		}
		?>
	});
	$('.checkLetters').keyup(function() {
		var countryName = $('.CountryClick').val()
		if ( countryName != "KW" ){
			var inputValue = $(this).val();
			var englishLettersAndNumbersRegex = /^[a-zA-Z0-9\s]+$/;
			// Check if the input matches the desired pattern
			if (!englishLettersAndNumbersRegex.test(inputValue)) {
				alert("<?php echo direction("Only english letters and numbers are allowed","مسموح فقط بالأحرف و الأرقام الإنجليزية") ?>");
				$(this).val('');
			}
		}
	});
	$('.payBtnNow').on('click', function(event) {
		var mobileNumber = $('input[name=phone]').val();
		var countryName = $('.CountryClick').val();
		var englishLettersAndNumbersRegex = /^[a-zA-Z0-9\s]+$/;
		var isValid = true; // Flag variable to track validation status
		if ($.isNumeric(mobileNumber)) {
			if (mobileNumber.length <= 7) {
				alert('<?php echo direction("Please enter your phone number correctly","الرجاء ادخال رقم الهاتف بالشكل الصحيح") ?>');
				isValid = false;
			}
			if ($('#pMethod').val() == '') {
				alert('<?php echo direction("Please select a payment method","الرجاء إختيار طريقة دفع") ?>');
				isValid = false;
			}
		}else{
			alert('<?php echo direction("Please enter your phone number correctly","الرجاء ادخال رقم الهاتف بالشكل الصحيح") ?>');
			isValid = false;
		}
		$('.addressDiv').find('input:not(:hidden)').each(function() {
			if (countryName != "KW") {
				var inputValue = $(this).val();
				var inputId = $(this).attr("id");
				if (!englishLettersAndNumbersRegex.test(inputValue)) {
					alert(inputId+". "+"<?php echo direction('Only English letters, numbers.','مسموح فقط بالأحرف والأرقام الإنجليزية.') ?>");
					$(this).val('').focus();
					isValid = false;
				}
			}
		});
		if (!isValid) {
			event.preventDefault(); // Prevent form submission
			return false;
		}
	});

	$('.CountryClick').change(function(e){
		$('#mainView').attr('style','display:none');
		$('.loading-screen').attr('style','display:flex');
		e.preventDefault();
		var countryName = $(this).val()
		if ( countryName != "<?php echo $defaultCountry ?>" ){
			$("#10p_m").attr("style","display:none");
			$('#pMethod').val('');
		}else{
			$("#10p_m").attr("style","display:block");
		}
		if ( countryName != "KW" ){
			$('input[name="name"]').prop('required',true);
			$('input[name="email"]').prop('required',true);
			$('input[name="civilId"]').prop('required',true);
			$('input[name="civilId"]').attr('type','text');
			$('#payCash').hide();
			$('#civilIdDiv').show();
			var inputValue1 = $('input[name="name"]').val();
			var englishLettersAndNumbersRegex1 = /^[a-zA-Z0-9\s]+$/;
			// Check if the input matches the desired pattern
			if (!englishLettersAndNumbersRegex1.test(inputValue1)) {
				alert("<?php echo direction("Only english letters and numbers are allowed","مسموح فقط بالأحرف و الأرقام الإنجليزية") ?>");
				$('input[name="name"]').val('');
			}
		}else{
			$('input[name="name"]').removeAttr('required');
			$('input[name="postalCode"]').removeAttr('required');
			$('input[name="email"]').removeAttr('required');
			$('input[name="civilId"]').removeAttr('required');
			$('input[name="civilId"]').attr('type','hidden');
			$('#payCash').show();
			$('#civilIdDiv').hide();
		}
		$.ajax({
			type:"POST",
			url: "api/functions.php",
			data: {
				getAreasA: countryName,
			},
			success:function(result){
				$('.getAreas').html(result);
				$('.loading-screen').attr('style','display:none');
				$('#mainView').attr('style','display:block');
			}
		});
	});
	$('.homeForm').click(function(e){
		$('.homeFormDiv').attr("style","display:block");
		$('.noAddressDiv').attr("style","display:none");
		$('#block').prop('required',true);
		$('#street').prop('required',true);
		$('#avenue').prop('required',false);
		$('#building').prop('required',true);
		$('#floor').prop('required',false);
		$('#apartment').prop('required',false);
		$('#postalCode').prop('required',false);
		$('#notes').prop('required',false);
		$('#noAddressName').prop('required',false);
		$('#noAddressPhone').prop('required',false);
		$('.getAreas').prop('required',true);
		$('#floor').attr("type","hidden");
		$('#apartment').attr("type","hidden");
		$('#homeFormId').addClass('active');
		$('#apartmentFormId').removeClass('active');
		$('#pickUpFROMid').removeClass('active');
		$('#noAddressFROMid').removeClass('active');
		$('.areaSelection').attr('style',"display:block");
		$('#place').val('1');
	});
	$('.apartmentForm').click(function(e){
		$('.homeFormDiv').attr("style","display:block");
		$('.noAddressDiv').attr("style","display:none");
		$('#block').prop('required',true);
		$('#street').prop('required',true);
		$('#avenue').prop('required',false);
		$('#building').prop('required',true);
		$('#floor').prop('required',true);
		$('#apartment').prop('required',true);
		$('#postalCode').prop('required',false);
		$('#notes').prop('required',false);
		$('#noAddressName').prop('required',false);
		$('#noAddressPhone').prop('required',false);
		$('.getAreas').prop('required',true);
		$('#floor').attr("type","text");
		$('#apartment').attr("type","text");
		$('#apartmentFormId').addClass('active');
		$('#homeFormId').removeClass('active');
		$('#pickUpFROMid').removeClass('active');
		$('#noAddressFROMid').removeClass('active');
		$('.areaSelection').attr('style',"display:block");
		$('#place').val('2');
	});
	$('.pickUpFROM').click(function(e){
		$('.homeFormDiv').attr("style","display:none");
		$('.noAddressDiv').attr("style","display:none");
		$('#block').prop('required',false);
		$('#street').prop('required',false);
		$('#avenue').prop('required',false);
		$('#building').prop('required',false);
		$('#floor').prop('required',false);
		$('#apartment').prop('required',false);
		$('#postalCode').prop('required',false);
		$('#notes').prop('required',false);
		$('#noAddressName').prop('required',false);
		$('#noAddressPhone').prop('required',false);
		$('.getAreas').prop('required',false);
		$('.areaSelection').attr('style',"display:none");
		$('#pickUpFROMid').addClass('active');
		$('#homeFormId').removeClass('active');
		$('#apartmentFormId').removeClass('active');
		$('#noAddressFROMid').removeClass('active');
		$('#place').val('3');
	}); 
	$('.noAddressFROM').click(function(e){
		$('.homeFormDiv').attr("style","display:none");
		$('.noAddressDiv').attr("style","display:block");
		$('#block').prop('required',false);
		$('#street').prop('required',false);
		$('#avenue').prop('required',false);
		$('#building').prop('required',false);
		$('#floor').prop('required',false);
		$('#apartment').prop('required',false);
		$('#postalCode').prop('required',false);
		$('#notes').prop('required',false);
		$('#noAddressName').prop('required',true);
		$('#noAddressPhone').prop('required',true);
		$('.getAreas').prop('required',false);
		$('.areaSelection').attr('style',"display:none");
		$('#noAddressFROMid').addClass('active');
		$('#pickUpFROMid').removeClass('active');
		$('#homeFormId').removeClass('active');
		$('#apartmentFormId').removeClass('active');
		$('#place').val('4');
	}); 
	<?php
	if( is_array($paymentOptions) ){
		for( $i  = 0; $i < sizeof($paymentOptions); $i++){
			$paymentClassLabelId = str_replace("-","",str_replace("/","",str_replace(" ","",direction($paymentOptions[$i]["enTitle"],$paymentOptions[$i]["arTitle"]))));
			?>
			$('.<?php echo $paymentClassLabelId ?>').click(function(){
				var payId = $(this).attr("id");
				$('.pMethods').removeClass('active');
				$('#pMethods'+payId).addClass('active');
				$('#pMethod').val(payId);
			});
			<?php
		}
	}
	?>
})
$(".product-category, .product-category-mobile").click(function() {
	$('.loading-screen').css('display', 'flex');
	$.ajax({
		type:"POST",
		url: "api/listofItems.php",
		data: {
			id:$(this).attr('type'),
			order:"<?php echo $requestOrder ?>",
			storeId:"<?php echo $storeID ?>",
		},
		success:function(result){
			$("#listOfItems").html(result);
			$('.loading-screen').css('display', 'none');
		}
	});
});
</script>
<script src="js/js.js?y=<?php echo md5(time()) ?>"></script>