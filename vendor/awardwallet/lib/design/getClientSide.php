<?php

if(!isset($_COOKIE["vWidth"]) || !isset($_COOKIE["vHeight"])){
	?>
	<script type="text/javascript">
	var newWidth, newHeight;
	newWidth = screen.width;
	newHeight = screen.height;
	if(newWidth == 0 || newWidth == "")
		newWidth = 5;
	if(newHeight == 0 || newHeight == "")
		newHeight = 5;
	var expdate = new Date();
	expdate.setTime(expdate.getTime()+(12*30*24*60*60*1000)); // ~1 year
	setCookie("vWidth", newWidth, expdate, "/", "", 0)
	setCookie("vHeight", newHeight, expdate, "/", "", 0)
	</script>
	<?
}

$scrWidth = -2;
$scrHeight = -2;
$halfHeight = floor((768-HEADER_FOOTER_HEIGHT)/2);
if(isset($_COOKIE["vWidth"]) && isset($_COOKIE["vHeight"])){
	$scrWidth = intval($_COOKIE["vWidth"]);
	$scrHeight = intval($_COOKIE["vHeight"]);
	$_SESSION["sWidth"] = $scrWidth;
	$_SESSION["sHeight"] = $scrHeight;
	$halfHeight = floor(($scrHeight-HEADER_FOOTER_HEIGHT)/2);
}

?>