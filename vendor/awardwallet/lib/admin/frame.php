<?php
// -----------------------------------------------------------------------
// link management
// Author: Alexi Vereschaga, ITlogy LLC, alexi@itlogy.com, www.ITlogy.com 
// -----------------------------------------------------------------------
require( "../../kernel/public.php" );
$bSecuredPage = False;
require( "$sPath/lib/admin/design/header.php" );
?>
<iframe id="myFrame" src="<?=$QS["url"]?>" HSPACE="0" VSPACE="0" frameborder="0" style="height:100%; width:100%;">
  <p>Your browser does not support iframes.</p>
</iframe>
<script language="JavaScript">
function resize_iframe()
{

	var height=window.innerWidth;//Firefox
	if (document.body.clientHeight)
	{
		height=document.body.clientHeight;//IE
	}
	document.getElementById("myFrame").style.height=parseInt(height-
	document.getElementById("myFrame").offsetTop-60)+"px";
}
window.onresize=resize_iframe;
resize_iframe();
</script>
<?
require("$sPath/lib/admin/design/footer.php");
?>
