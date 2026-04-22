<?php 
if( isset($_GET["hide"]) && !empty($_GET["hide"]) ){
	$hideId = (int)$_GET["hide"];
	if( updateDB("packages",array("hidden"=> "2"),"`id` = '{$hideId}'") ){
		header("LOCATION: ?v=Packages");
	}
}

if( isset($_GET["show"]) && !empty($_GET["show"]) ){
	$showId = (int)$_GET["show"];
	if( updateDB("packages",array("hidden"=> "1"),"`id` = '{$showId}'") ){
		header("LOCATION: ?v=Packages");
	}
}

if( isset($_GET["delId"]) && !empty($_GET["delId"]) ){
	$delId = (int)$_GET["delId"];
	if( updateDB("packages",array("status"=> "1"),"`id` = '{$delId}'") ){
		header("LOCATION: ?v=Packages");
	}
}

if( isset($_POST["updateRank"]) ){
	for( $i = 0; $i < sizeof($_POST["rank"]); $i++){
		$rankVal = (int)$_POST["rank"][$i];
		$rankId = (int)$_POST["id"][$i];
		updateDB("packages",array("rank"=>$rankVal),"`id` = '{$rankId}'");
	}
	header("LOCATION: ?v=Packages");
}

if( isset($_POST["title"]) ){
	$_POST["storeId"] = ( $_POST["storeId"] == 0 ) ? $storeId : $_POST["storeId"];
	$id = (int)$_POST["update"];
	unset($_POST["update"]);
    $_POST["title"] = json_encode($_POST["title"], JSON_UNESCAPED_UNICODE);
    $_POST["subtitle"] = json_encode($_POST["subtitle"], JSON_UNESCAPED_UNICODE);
    $_POST["details"] = json_encode($_POST["details"], JSON_UNESCAPED_UNICODE);
    $_POST["priceSubtitle"] = json_encode($_POST["priceSubtitle"], JSON_UNESCAPED_UNICODE);
	if ( $id == 0 ){
		if (is_uploaded_file($_FILES['image']['tmp_name'])) {
			$_POST["image"] = uploadImageSystemFolder($_FILES['image']['tmp_name'], "packages");
		} else {
			$_POST["image"] = "";
		}
		
		
		if( insertDB("packages", $_POST) ){
			header("LOCATION: ?v=Packages");
		}else{
		?>
		<script>
			alert("Could not process your request, Please try again.");
		</script>
		<?php
		}
	}else{
		if (is_uploaded_file($_FILES['image']['tmp_name'])) {
			$_POST["image"] = uploadImageSystemFolder($_FILES['image']['tmp_name'], "packages");
		} else {
			$image = selectDB("packages", "`id` = '{$id}'");
			$_POST["image"] = $image[0]["image"];
		}
		
		if( updateDB("packages", $_POST, "`id` = '{$id}'") ){
			header("LOCATION: ?v=Packages");
		}else{
		?>
		<script>
			alert("Could not process your request, Please try again.");
		</script>
		<?php
		}
	}
}
?>
<div class="row">
<div class="col-sm-12">
<div class="panel panel-default card-view">
<div class="panel-heading">
<div class="pull-left">
	<h6 class="panel-title txt-dark"><?php echo direction("Package Details","تفاصيل الحزمة") ?></h6>
</div>
	<div class="clearfix"></div>
</div>
<div class="panel-wrapper collapse in">
<div class="panel-body">
	<form class="" method="POST" action="" enctype="multipart/form-data">
		<div class="row m-0">

            <div class="col-md-6">
			    <label><?php echo direction("English Title","العنوان بالإنجليزي") ?></label>
			    <input type="text" name="title[en]" class="form-control" required>
			</div>

			<div class="col-md-6">
			    <label><?php echo direction("Arabic Title","العنوان بالعربي") ?></label>
			    <input type="text" name="title[ar]" class="form-control" required>
			</div>

            <div class="col-md-6">
			    <label><?php echo direction("Subtitle Title","العنوان الفرعي") ?></label>
			    <input type="text" name="subtitle[en]" class="form-control" required>
			</div>

			<div class="col-md-6">
			    <label><?php echo direction("Arabic Subtitle","العنوان الفرعي بالعربي") ?></label>
			    <input type="text" name="subtitle[ar]" class="form-control" required>
			</div>

			<div class="col-md-6">
			    <label><?php echo direction("English Details","التفاصيل بالإنجليزي") ?></label>
                <textarea name="details[en]" class="tinymce" id="enDetails"></textarea>
			</div>

			<div class="col-md-6">
			    <label><?php echo direction("Arabic Details","التفاصيل بالعربي") ?></label>
                <textarea name="details[ar]" class="tinymce" id="arDetails"></textarea>
			</div>

			<div class="col-md-4">
			    <label><?php echo direction("Price","السعر") ?></label>
                <input type="number" step="any" name="price" min="0" class="form-control" required>
			</div>

			<div class="col-md-4">
			    <label><?php echo direction("Price English Subtitle","تفاصيل السعر الفرعي بالإنجليزي") ?></label>
                <input type="text" name="priceSubtitle[en]" class="form-control">
			</div>

            <div class="col-md-4">
			    <label><?php echo direction("Price Arabic Subtitle","تفاصيل السعر الفرعي بالعربي") ?></label>
                <input type="text" name="priceSubtitle[ar]" class="form-control">
			</div>

            <div class="col-md-6">
			    <label><?php echo direction("Discount","الخصم") ?></label>
                <input type="number" step="any" name="discount" min="0" class="form-control" required>
			</div>

			<div class="col-md-6">
			    <label><?php echo direction("Discount Type","نوع الخصم") ?></label>
                <select name="discountType" class="form-control" required>
				    <option value="1"><?php echo direction("Percentage","نسبة مئوية") ?></option>
				    <option value="2"><?php echo direction("Fixed Amount","مبلغ ثابت") ?></option>
			    </select>
			</div>
			
			<div class="col-md-12">
			<label><?php echo direction("Logo","الشعار") ?></label>
			<input type="file" name="image" class="form-control" required>
			</div>
			
			<div id="images" style="margin-top: 10px; display:none">
				<div class="col-md-12">
				<img id="logoImg" src="" style="width:250px;height:250px">
				</div>
			</div>
			
			<div class="col-md-6" style="margin-top:10px">
			<input type="submit" class="btn btn-primary" value="<?php echo direction("Submit","أرسل") ?>">
			<input type="hidden" name="update" value="0">
			</div>
		</div>
	</form>
</div>
</div>
</div>
</div>
				
				<!-- Bordered Table -->
<form method="post" action="">
<input name="updateRank" type="hidden" value="1">
<div class="col-sm-12">
<div class="panel panel-default card-view">
<div class="panel-heading">
<div class="pull-left">
<h6 class="panel-title txt-dark"><?php echo direction("List of Packages","قائمة الحزم") ?></h6>
</div>
<div class="clearfix"></div>
</div>
<div class="panel-wrapper collapse in">
<div class="panel-body">
<button class="btn btn-primary">
<?php echo direction("Submit rank","أرسل الترتيب") ?>
</button>  
<div class="table-wrap mt-40">
<div class="table-responsive">
	<table class="table display responsive product-overview mb-30" id="myTable">
		<thead>
		<tr>
		<th>#</th>
		<th><?php echo direction("English Title","العنوان بالإنجليزي") ?></th>
		<th><?php echo direction("Arabic Title","العنوان بالعربي") ?></th>
		<th class="text-nowrap"><?php echo direction("Actions","الخيارات") ?></th>
		</tr>
		</thead>
		
		<tbody>
		<?php 
		if( $packages = selectDB("packages","`status` = '0' ORDER BY `rank` ASC") ){
			for( $i = 0; $i < sizeof($packages); $i++ ){
				$counter = $i + 1;
			if ( $packages[$i]["hidden"] == 2 ){
				$icon = "fa fa-eye";
				$link = "?v={$_GET["v"]}&show={$packages[$i]["id"]}";
				$hide = direction("Show","إظهار");
			}else{
				$icon = "fa fa-eye-slash";
				$link = "?v={$_GET["v"]}&hide={$packages[$i]["id"]}";
				$hide = direction("Hide","إخفاء");
			}
            $title = json_decode($packages[$i]["title"], true);
            $subtitle = json_decode($packages[$i]["subtitle"], true);
            $details = json_decode($packages[$i]["details"], true);
            $priceSubtitle = json_decode($packages[$i]["priceSubtitle"], true);
			?>
			<tr>
			<td>
			<input name="rank[]" class="form-control" type="number" value="<?php echo $counter ?>">
			<input name="id[]" class="form-control" type="hidden" value="<?php echo $packages[$i]["id"] ?>">
			</td>
			<td id="enTitleTable<?php echo $packages[$i]["id"]?>" ><?php echo $title["en"] ?></td>
			<td id="arTitleTable<?php echo $packages[$i]["id"]?>" ><?php echo $title["ar"] ?></td>
			<td class="text-nowrap">
			
			<a id="<?php echo $packages[$i]["id"] ?>" class="mr-25 edit" data-toggle="tooltip" data-original-title="<?php echo direction("Edit","تعديل") ?>"> <i class="fa fa-pencil text-inverse m-r-10"></i>
			</a>
			<a href="<?php echo $link ?>" class="mr-25" data-toggle="tooltip" data-original-title="<?php echo $hide ?>"> <i class="<?php echo $icon ?> text-inverse m-r-10"></i>
			</a>
			<a href="<?php echo "?v={$_GET["v"]}&delId={$packages[$i]["id"]}" ?>" data-toggle="tooltip" data-original-title="<?php echo direction("Delete","حذف") ?>"><i class="fa fa-close text-danger"></i>
			</a>
			<div style="display:none">
                <label id="hidden<?php echo $packages[$i]["id"]?>"><?php echo $packages[$i]["hidden"] ?></label>
                <label id="logo<?php echo $packages[$i]["id"]?>"><?php echo $packages[$i]["image"] ?></label>
                <label id="enTitleVal<?php echo $packages[$i]["id"]?>"><?php echo $title["en"] ?></label>
                <label id="arTitleVal<?php echo $packages[$i]["id"]?>"><?php echo $title["ar"] ?></label>
                <label id="enSubtitleVal<?php echo $packages[$i]["id"]?>"><?php echo $subtitle["en"] ?></label>
                <label id="arSubtitleVal<?php echo $packages[$i]["id"]?>"><?php echo $subtitle["ar"] ?></label>
                <label id="enDetailsVal<?php echo $packages[$i]["id"]?>"><?php echo $details["en"] ?></label>
                <label id="arDetailsVal<?php echo $packages[$i]["id"]?>"><?php echo $details["ar"] ?></label>
                <label id="priceVal<?php echo $packages[$i]["id"]?>"><?php echo $packages[$i]["price"] ?></label>
                <label id="enPriceSubtitleVal<?php echo $packages[$i]["id"]?>"><?php echo $priceSubtitle["en"] ?></label>
                <label id="arPriceSubtitleVal<?php echo $packages[$i]["id"]?>"><?php echo $priceSubtitle["ar"] ?></label>
                <label id="discountVal<?php echo $packages[$i]["id"]?>"><?php echo $packages[$i]["discount"] ?></label>
                <label id="discountTypeVal<?php echo $packages[$i]["id"]?>"><?php echo $packages[$i]["discountType"] ?></label>
            </div>
			
			</td>
			</tr>
			<?php
			}
		}
		?>
		</tbody>
		
	</table>
</div>
</div>
</div>
</div>
</div>
</div>
</form>
</div>
<script>
	$(document).on("click",".edit", function(){
		var id = $(this).attr("id");
        $("input[name=update]").val(id);

		$("input[type=file]").prop("required",false);
        $("input[name='title[en]']").val($("#enTitleVal"+id).text()).focus();
		$("input[name='title[ar]']").val($("#arTitleVal"+id).text());
		$("input[name='subtitle[en]']").val($("#enSubtitleVal"+id).text());
        $("input[name='subtitle[ar]']").val($("#arSubtitleVal"+id).text());
        $("input[name='price']").val($("#priceVal"+id).text());
        $("input[name='priceSubtitle[en]']").val($("#enPriceSubtitleVal"+id).text());
        $("input[name='priceSubtitle[ar]']").val($("#arPriceSubtitleVal"+id).text());
        $("input[name='discount']").val($("#discountVal"+id).text());
        $("select[name='discountType']").val($("#discountTypeVal"+id).text());

        if (tinymce.get("enDetails")) {
			tinymce.get("enDetails").setContent($("#enDetailsVal"+id).text() || "");
		}
		if (tinymce.get("arDetails")) {
			tinymce.get("arDetails").setContent($("#arDetailsVal"+id).text() || "");
		}
		$("select[name=hidden]").val($("#hidden"+id).text());
		$("#logoImg").attr("src","../logos/"+$("#logo"+id).text());
		$("#images").attr("style","margin-top:10px;display:block");
	})
</script>