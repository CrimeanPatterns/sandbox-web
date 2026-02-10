<?php

// -----------------------------------------------------------------------
// Interface class.
//		Contains base interface class, to show site messages etc.
//		You should override class to build custom interface.
//		TInterface = class( TBaseInterface ) ..
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

class TBaseInterface
{
	public $requireHTTPS = False;
	
	public $FooterScripts = array();
	public $ScriptFiles	  = array();
	public $CssFiles	  = array();
	public $HeaderStyles  = array();
	public $onLoadScripts = array();

	/**
	 * Small screen resolution (width <= 1024) detected
	 * @var bool
	 */
	public $SmallScreen = false;
	public $ScreenWidth;
	public $ScreenHeight;

	public function __construct(){
		if(isset($_COOKIE['Browser']) && is_string($_COOKIE['Browser'])) {
			$screenSize = array();
			parse_str( urldecode($_COOKIE['Browser']), $screenSize);
			if(isset($screenSize['sw']))
				$this->ScreenWidth = intval($screenSize['sw']);
			if(isset($screenSize['sh']))
				$this->ScreenHeight = intval($screenSize['sh']);
			if($this->ScreenWidth <= 1024 && $this->ScreenWidth > 300)
				$this->SmallScreen = true;
		}
	}

	// called after database connection is up and user is authenticated if any
	public function Init(){
		
	}

	// draw a simple notification message.
	// $sKind = "error", "info", "warning"
	function DrawMessage( $sText, $sKind ){
		$topMargin = 2;
		if(strcasecmp($sKind, "success") == 0)
			$topMargin = 3;
?>
		<table cellspacing="0" cellpadding="5" border="0" align="center" style="background-color: White;">
		<tr>
			<td valign="top"><img src="/lib/images/<?=strtolower( $sKind )?>.gif" alt="" style="margin-top: <?=$topMargin?>px;"></td>
			<td class="<?=$sKind?>Frm" align="left"><?=$sText?></td>
		</tr>
		</table>
<?
	}

	function GetMessage( $sText, $sKind ){
		$sKind = strtolower( $sKind );
		return "<table cellspacing=\"0\" cellpadding=\"5\" border=\"0\" align=\"center\" id=\"noBorder\">
		<tr>
			<td><img src=\"/lib/images/{$sKind}.gif\" alt=\"\" style=\"margin-bottom: -1px;\" alt=\"\"></td>
			<td class=\"{$sKind}Frm\">{$sText}</td>
		</tr>
		</table>";
	}

	// draws button
	// sType: submit, button; sName: input name; sTitle: button title;
	// sAttrs: additional tag attributes
	function DrawButton( $sType, $sName, $sTitle, $sAttrs = NULL )
	{
		echo "<input type=\"$sType\" name=\"".htmlspecialchars($sName)."\" value=\"" . htmlspecialchars( $sTitle ) ."\"";
		if( isset( $sAttrs ) )
			echo " $sAttrs";
		echo ">\r\n";
	}

	function DrawButton2($caption, $attr, $size = 26){
		return "<table cellspacing='0' cellpadding='0' border='0'>
<tr>
	<td><input type='submit' ".$attr." class='button{$size}' value='".$caption."'></td>
	<td><img src='/lib/images/arrow".$size.".gif' alt=''></td>
</tr>
</table>";
	}

	// draw notification message in a box in the middle of the page
function DrawMessageBox( $sMessage, $sKind = "info", $boxWidth=400 ){
?>
<table cellspacing="0" cellpadding="0" border="0" align="center" style="width: 100%;">
<tr>
	<td valign="bottom" align="center"><br>
<?
if( $this->IsAdminInteface() )
	TBaseInterface::DrawBeginBox( $boxWidth );
else
	$this->DrawBeginBox( $boxWidth )
?>
<table cellspacing="0" cellpadding="0" border="0">
<tr>
	<td width="50" align="left"><img src="/lib/images/<?=strtolower( $sKind )?>_big.gif" border="0" alt=""></td>
	<td class="<?=$sKind?>Frm"><?=$sMessage?></td>
</tr>
</table>
<?
if( $this->IsAdminInteface() )
	TBaseInterface::DrawEndBox();
else
	$this->DrawEndBox()
?>
	</td>
</tr>
</table>
<?
	}
	function DrawBeginBox($boxWidth = 400, $header = null, $closable = true, $classes = null, $closeButton = true){
?>
<table cellspacing="0" cellpadding="0" border="0" width="<?=$boxWidth?>">
<tr>
	<td valign="top" width="31" height="27" style="background-image: url(/lib/images/cornerTL1.gif); background-position: top; background-repeat: no-repeat;"><?=spacer(31, 27)?></td>
	<td width="100%" bgcolor="#F5F2EB" rowspan="3" style="background-image: url(/lib/images/bottomBg2.gif); background-position: bottom; background-repeat: repeat-x; padding-top: 12px; padding-bottom: 10px;">
<?
	}
	function DrawEndBox(){
?>
	</td>
	<td valign="top" width="31" height="27" style="background-image: url(/lib/images/cornerTR1.gif); background-position: top; background-repeat: no-repeat;"><?=spacer(31, 27)?></td>
</tr>
<tr bgcolor="#F5F2EB">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
</tr>
<tr>
<td valign="bottom" height="36" style="background-image: url(/lib/images/cornerBL1.gif); background-position: bottom; background-repeat: no-repeat;"><?=spacer(31, 36)?></td>
<td align="right" valign="bottom" height="36" style="background-image: url(/lib/images/cornerBR1.gif); background-position: bottom; background-repeat: no-repeat;"><?=spacer(31, 36)?></td>
</tr>
</table>
<?
	}

	function IsAdminInteface()
	{
		return ( strpos( strtolower( $_SERVER['SCRIPT_NAME'] ), "/lib/admin/" ) === 0 );
	}

	// show information message or error in header/footer and end execution
	// kind = error, info, warning
	function DiePage( $sMessage, $sKind = "error", $width=400 )
	{
		global $sTitle, $bSecuredPage;
		// console ?
		if(!isset($_SERVER['REQUEST_METHOD']))
			die($sMessage."\n");
		if(ob_get_level() > 0)
			ob_clean();
		extract( $GLOBALS );
		if( !isset( $sTitle ) || ( $sTitle == "No title" ) )
			$sTitle = $sKind;
		if( $this->IsAdminInteface() )
		{
			if(ob_get_level() > 0 && strtolower(ArrayVal($_SERVER, 'HTTP_X_REQUESTED_WITH')) != 'xmlhttprequest')
				require( __DIR__ . "/../admin/design/header.php" );
			$this->DrawMessageBox( $sMessage, $sKind, $width );
			if(ob_get_level() > 0 && strtolower(ArrayVal($_SERVER, 'HTTP_X_REQUESTED_WITH')) != 'xmlhttprequest')
				require( __DIR__ . "/../admin/design/footer.php" );
		}
		else
		{
			$bSecuredPage = false;
			if(ob_get_level() > 0)
                require( __DIR__ . "/../../design/header.php" );
			if(isset($sMessage))
				$this->DrawMessageBox( $sMessage, $sKind, $width );
			if(ob_get_level() > 0)
                require( __DIR__ . "/../../design/footer.php" );
		}
		exit();
	}

	function DrawArrayTable($content, $header, $selected = -1000){
?>
		<table cellspacing="0" cellpadding="5" class="detailsTable">
		<tr bgcolor="AF0001">
<?
		foreach($header as $key => $value ) {
?>
			<td class="white"><?=$value?></td>
<?
}
?>
		</tr>
		<?
		foreach($content as $key => $value ) {
			$vTr = "";
			$vTd = "";
			if( ( $key % 2 ) == 1 )
				$vTr = " bgcolor=\"#FCF6EA\"";
			if($key == $selected){
				$vTr = " bgcolor=\"#FDD28C\"";
				$vTd = " style='font-weight: bold;'";
			}
		?>
		<tr<?=$vTr?>>
<?
			if(count($value) != count($header)){
?>
				<td<?=$vTd?>><?=$key?></td>
<?
			}
			foreach($value as $tdKey => $tdValue ) {
				if(is_array($tdValue)){
?>
					<td<?=$vTd?>><a href="<?=$tdValue["link"]?>"<?=$tdValue["params"]?>><?=$tdValue["caption"]?></a></td>
<?
				}
				else{
?>
					<td<?=$vTd?>><?=$tdValue?></td>
<?
				}
			}
?>
		</tr>
		<?
		}
		?>
		</table>
<?
	}
	function comparePaths($path){
#using this regular expression to get the path without any parameters (i.e. edit.php instead of edit.php?ID=0)
	if(!stripos($path, ".php") && stripos($_SERVER["SCRIPT_NAME"], ".php"))
		$path .= "index.php";
	preg_match( "/^(.*php)/i", $path, $match);
	if(isset($match[0])){
		if($match[0] == $_SERVER["SCRIPT_NAME"])
			return true;
		else
			return false;
	}
	else
		return false;
	}

	function russifyMonths($date){
		$monthsRu = array( "января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря", "янв", "фев", "мар", "апр", "мая", "июня", "июля", "авг", "сент", "окт", "нояб", "дек");
		$monthsEn = array( "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
		return str_replace($monthsEn, $monthsRu, $date);
	}

	function russifyWeekDay($date, $option){
		$weekDayRu1 = array( "понедельник", "вторник", "среду", "четверг", "пятницу", "субботу", "воскресенье");
		$weekDayRu2 = array( "понедельник", "вторник", "среда", "четверг", "пятница", "суббота", "воскресенье");
		$weekDayEn = array( "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
		if($option == 1)
			return str_replace($weekDayEn, $weekDayRu1, $date);
		else
			return str_replace($weekDayEn, $weekDayRu2, $date);
	}

	function showFAQs($bullet="<img src='/lib/images/bulletBlue4.gif' alt='' style='margin-top: 9px;'>", $headerColor = FORM_TITLE_COLOR){
		#begin FAQ review
		$sSQL = "SELECT * FROM FaqCategory WHERE Visible = 1 ORDER BY `Rank`";
		$category = new TQuery( $sSQL );
		while( !$category->EOF ){
		?>
		<a name="top"></a>
		<table cellspacing="0" cellpadding="5" border="0" width="80%" align="center">
		<tr>
			<td colspan="2" height="33" style="color: white; padding-left: 17px; font-size: 16px; font-weight: bold;" bgcolor="<?=$headerColor?>"><?=$category->Fields["CategoryTitle"]?></td>
		</tr>
<?
			$sSQL = "SELECT * FROM Faq WHERE Visible = 1 AND FaqCategoryID = " . $category->Fields["FaqCategoryID"] . " ORDER BY `Rank`";
			$faq = new TQuery( $sSQL );
			while( !$faq->EOF ){
?>
		<tr>
			<td style="line-height: 20px;" valign="top">
			<?=$bullet?>
			</td>
			<td width="100%" style="line-height: 20px; padding-bottom: 10px;" valign="top">
			<a href="<?=$_SERVER["SCRIPT_NAME"]?>#<?=$faq->Fields["FaqID"]?>"><?=$faq->Fields["Question"]?></a>
			</td>
		</tr>
<?
				$faq->Next();
			}
?>
		</table>
		<br>
<?
			$category->Next();
		}
		#end FAQ review

		#begin actual FAQ content
		$sSQL = "SELECT * FROM FaqCategory WHERE Visible = 1 ORDER BY `Rank`";
		$category = new TQuery( $sSQL );
		while( !$category->EOF ){
#			$this->drawSectionDivider($category->Fields["CategoryTitle"]);
			print "<div style=\"width: 100%;\" align=\"center\"><div style=\"width: 80%; background-color: #eaeaea; color: #0b70b7; font-size: 14px; font-weight: bold; padding-left: 17px; padding-top: 6px; padding-bottom: 6px; text-align: left;\">".$category->Fields["CategoryTitle"]."</div></div>";
			$sSQL = "SELECT * FROM Faq WHERE Visible = 1 AND FaqCategoryID = " . $category->Fields["FaqCategoryID"] . " ORDER BY `Rank`";
			$faq = new TQuery( $sSQL );
			while( !$faq->EOF ){
?>
		<a name="<?=$faq->Fields["FaqID"]?>"></a>
		<table cellspacing="0" cellpadding="5" border="0" width="80%" style="padding-bottom: 10px;" align="center">
		<tr>
			<td style="font-size: 14px; font-weight: bold; line-height: 23px; color: black;" valign="top">
			Q:
			</td>
			<td width="100%" valign="top" style="font-size: 12px; font-weight: normal; color: black; line-height: 23px;" colspan="2">
			<?=$faq->Fields["Question"]?>
			</td>
		</tr>
		<tr>
			<td style="font-size: 14px; font-weight: bold; line-height: 23px; color: #e67817;" valign="top">
			A:
			</td>
			<td valign="top" style="font-size: 12px; font-weight: normal; color: #e67817; line-height: 23px;" colspan="2">
			<?=$faq->Fields["Answer"]?>
			</td>
		</tr>
		<tr>
			<td style="border-bottom: 1px solid #bcbcbc; padding-bottom: 20px;">&nbsp;</td>
			<td style="border-bottom: 1px solid #bcbcbc; padding-bottom: 20px;">
			<a style="color: #0b70b7; font-size: 10px;" href="http://<?=$_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"]?>#<?=$faq->Fields["FaqID"]?>">http://<?=$_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"]?>#<?=$faq->Fields["FaqID"]?></a>
			</td>
			<td align="right" style="border-bottom: 1px solid #bcbcbc; padding-bottom: 20px;"><a style="color: #727272; font-size: 10px;" href="#top">Top <img src="/lib/images/arrowUp3.gif" border="0" style="margin-bottom: -1px;" alt=""></a></td>
		</tr>
		</table>
<?
				$faq->Next();
			}
			print "<br>";
			$category->Next();
		}
		#end actual FAQ content
	}

	function DrawProgressBar($title, $mesage, $progressFile = "", $titleBg = FORM_TITLE_COLOR){
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<title>Please wait...</title>
</head>
<link rel="stylesheet" type="text/css" href="/design/mainStyle.css">
<link href="/lib/design/libStyle.css" rel="stylesheet" type="text/css"></link>
<?
if( isset( $_GET["Frame"] ) && isset( $_GET["URL"] ) && preg_match("#^\w+$#ims", $_GET['Frame']) ) {
?>
<script>
function loaded()
{
    parent.<?=$_GET["Frame"]?>.location.href = "<?=htmlspecialchars(urlPathAndQuery($_GET["URL"]))?>";
}
</script>
<body onload="loaded()">
<?
} else {
?>
<body>
<? } ?>
<table cellspacing="0" cellpadding="0" border="0" width="100%" height="100%">
<tr>
	<td width="100%" height="100%" align="center" valign="middle">
<table cellspacing="0" cellpadding="5" border="0" width="350" height="170" class="detailsTableDark">
<tr bgcolor="<?=$titleBg?>">
	<td class="white" align="center"><?=$title?></td>
</tr>
<tr>
	<td align="center"><br>
<?=$mesage?><br>
<br>
		<div style="margin: 30px auto; width: 66px;">
		<img src="/lib/images/progressBig.gif" style="border: none; width: 66px; height: 66px;">
		</div>
	</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
<?
	}

	function DrawInviteBox($invitedCount, $acceptedCount){
		global $w1;
?>
<table cellspacing="0" cellpadding="0" border="0">
<tr>
	<td style="border-bottom: 3px solid #A02831;"><div style="margin-left: <?=$w1?>px;" class="txt12pxBldRed">Invite a Friend</div></td>
</tr>
<tr>
	<td>
<form name="inviteFrm" action="/lib/processInviteForm.php" method="post" onsubmit="return validate(this)" style="margin-top: 10px; margin-bottom: 0px;">
Suggest this site to:<br>
<input class="inputTxt" type="Text" name="inviteEmail" value="&#60; email address &#62;" style="width: 170px;" onclick="clearEmailFeild(this)">
<?
/*
<div class="fieldhint">We are not in business of selling email addresses to anybody.
Please do not hesitate to provide a valid address here.</div><br>
*/
?>
<div style="font-weight: normal;"><?=$invitedCount?> - invited, <?=$acceptedCount?> - accepted</div><br>
<?
echo $this->drawButton2("Invite", "style='width: 90px;'", 19);
?>
</form>
	</td>
</tr>
</table>
<script language="JavaScript" type="text/javascript" defer>
<!--
function clearEmailFeild(feild){
	if(feild.value == "< email address >")
		feild.value = "";
}
function validate(frm){
	if (/^[_a-zA-Z\d\-\.]+@([_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+)$/.test(frm.inviteEmail.value))
		return true
	alert("The email address is not in a valid format \n(make sure there are no extra spaces at the end)")
	return (false)
}
-->
</script>
<?
	}
	function drawSectionDivider($title){
?>
<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td style="border-bottom: solid 3px <?=FORM_TITLE_COLOR?>;"><span class="txt12pxBldRed" style="font-weight: bold;"><?=$title?></span></td>
</tr>
</table>
<?
	}
	function drawSectionDivider2($title){
?>
<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td height="20" style="border-bottom: solid 1px <?=FORM_TITLE_COLOR?>;"><span class="txt12pxBldRed"><b><?=$title?></b></span>
	</td>
</tr>
</table>
<?
	}

	function getHTTPSHost(){
		if( ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_PRODUCTION )
			return "www.".SITE_NAME;
		else
			return $_SERVER['SERVER_NAME'] ?? '';
	}

	function forceHTTPS($requireHTTPS){
		if( ConfigValue(CONFIG_HTTPS_ONLY) && stripos($_SERVER['SCRIPT_NAME'], '/lib/errordocs/') !== 0 && stripos($_SERVER['SCRIPT_NAME'], '/account/redirect') !== 0 ){
			$newHost = null;
			if($requireHTTPS){
				if(!isset($_SERVER["HTTPS"])
				|| ($_SERVER["HTTPS"] == "off"))
					$newHost = $_SERVER['HTTP_HOST'];
			}
			else{
				if(isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on"))
					$newHost = $_SERVER['HTTP_HOST'];
			}
			if(isset($newHost)){
				if($requireHTTPS)
					$redirectUrl = "https://".$newHost;
				else
					$redirectUrl = "http://".$newHost;
				$redirectUrl .= $_SERVER['REQUEST_URI'];
				// save cookies across domains
				header("Location: $redirectUrl", null, 301);
				header("X-Redirect-Reason: forceHTTPS");
				exit();
			}
		}
	}

	function RestoreCookies($cookies){
		foreach(array('PasswordSaved', 'PwdHash', 'SavePwd') as $key)
			if(isset($cookies[$key]))
				setcookie($key, $cookies[$key], time() + SECONDS_PER_DAY * 90, "/");
	}

	function RedirectToLogin(){
		global $bDisableCookieAuthorization;
		$sLoginScript = "login.php";
		if( isset( $_COOKIE["PasswordSaved"] ) && isset( $_COOKIE["Pwd"] ) && ( $_COOKIE["PasswordSaved"] == "1" )
		&& !isset( $_SESSION["UserID"] ) && ( $_SERVER["SCRIPT_NAME"] != "/security/{$sLoginScript}" ) && !( strpos( $_SERVER["SCRIPT_NAME"], "/partner/" ) === 0 ) && !isset( $bDisableCookieAuthorization ) && !isset( $_COOKIE['PwdHash'] ) )
		{
			$sBackTo = "";
			if( $_SERVER['SCRIPT_NAME'] != '/index.php' ){
				$sBackTo = $_SERVER['REQUEST_URI'];
				$sBackTo = "&BackTo=" . urlencode( $sBackTo );
			}
		  	echo "<script>document.location.href = '/security/login.php?LoginByCookies=1{$sBackTo}&r=" . rand( 1, 99999999 ) . "'</script>";
		  	exit();
		}
	}

	function RequireUserAuth(){
		RedirectToAuth();
	}

	function FreeCouponCount($sCouponCode){
		$q = new TQuery("select count(Coupon.Code) as Cnt from Coupon
		left outer join Cart  on Coupon.Code = Cart.CouponCode and Cart.PayDate is not null
		where Coupon.Code like '".addslashes($sCouponCode)."'
		and Cart.CouponCode is null");
		return $q->Fields["Cnt"];
	}

	function UsedCouponCount($sCouponCode){
		$q = new TQuery("select count(*) as Cnt from
		Cart where PayDate is not null
		and CouponCode like '".addslashes($sCouponCode)."'");
		return $q->Fields["Cnt"];
	}

	function GetCoupons($sCouponCode){
		return SQLToArray("select Coupon.* from Coupon
		left outer join Cart  on Coupon.Code = Cart.CouponCode and Cart.PayDate is not null
		where Coupon.Code like '".addslashes($sCouponCode)."'
		and Cart.CouponCode is null
		order by Coupon.CreationDate DESC", "CouponID", "Code", true);
	}

	function AdminNavigationSQL(){
		return "SELECT *, CASE WHEN id IN (SELECT distinct(ParentID) as ParentID FROM adminLeftNav WHERE ParentID IS NOT NULL ORDER BY ParentID) THEN id ELSE '' END AS 'subMenu' FROM adminLeftNav WHERE parentID IS NULL AND visible = true ORDER BY `rank`;";
	}

	function DrawAdminNavigation(){
		global $Connection;
		echo '<table cellspacing="0" cellpadding="5" border="0" width="100%">';
		$objRS = New TQuery($this->AdminNavigationSQL(), $Connection);
		$subMenus = array();
		while(!$objRS->EOF){
			$tdLink = "";
			if($objRS->Fields["path"] != "")
				$tdLink = " onclick=\"document.location.href='{$objRS->Fields["path"]}'; return true;\"";
		?>
		<tr onMouseOver='javascript:this.style.backgroundColor="#DD7F02"' onMouseOut='javascript:this.style.backgroundColor="#F6A436"'>
			<td id="m<?=$objRS->Fields["subMenu"]?>" align="center" style="border-bottom: solid 1px #FCDAAD; padding: 6px 0px;" onmouseover="showSubMenu(this, 'sm<?=$objRS->Fields["subMenu"]?>'); this.style.cursor = 'pointer';"<?=$tdLink?> onMouseOut='javascript:this.style.backgroundColor="#F6A436"'>
		<?if($objRS->Fields["path"] != ""){?>
			<a class="a12pxBldWht" href="<?=$objRS->Fields["path"]?>"><?=$objRS->Fields["caption"]?></a>
		<?
		}
		else
			print "<span style='font-weight: bold; color: White;'>" . $objRS->Fields["caption"] . "</span>";
		if($objRS->Fields["subMenu"] != ""){
			print "&nbsp;<img src='/lib/images/arrowDown1.gif'  style='margin-bottom: 2px;'>";
			$subMenus[] = $objRS->Fields['id'];
		}
		if($objRS->Fields["note"] != ""){
		?>
			<br><span style="font-size: 9px; color: White;">(<?=$objRS->Fields["note"]?>)</span>
		<?
		}
		?>
		</td>
		</tr>
		<?
			$objRS->Next();
		}
		?>
		</table>
		<?
		foreach($subMenus as $parentId){
		?>
			<div id="sm<?=$parentId?>" class="subMenu" style="position: absolute; left: 0px; top: 0px;">
			<table cellspacing="0" cellpadding="0" border="0" width="190" bgcolor="#F63636" style="border-top: solid 1px #FCDAAD; border-left: solid 1px #FCDAAD; border-right: solid 1px #FCDAAD;">
		<?
			$objRS3 = New TQuery("SELECT * FROM adminLeftNav WHERE ParentID = " . $parentId . " AND visible = true ORDER BY `Rank`", $Connection);
			while(!$objRS3->EOF){
				$tdLink = "";
				if($objRS3->Fields["path"] != "")
					$tdLink = " onclick=\"document.location.href='{$objRS3->Fields["path"]}'; return true;\"";
		?>
			<tr onMouseOver='javascript:this.style.backgroundColor="#DD7F02"' onMouseOut='javascript:this.style.backgroundColor="#F63636"'>
				<td align="center" style="border-bottom: solid 1px #FCDAAD; padding: 6px 0px;" onmouseover="this.style.cursor = 'pointer';"<?=$tdLink?>>
		<?if($objRS3->Fields["path"] != ""){?>
			<a class="a12pxBldWht" href="<?=$objRS3->Fields["path"]?>" style="font-size: 10px;"><?=$objRS3->Fields["caption"]?></a>
		<?
		}
		else
			print "<span style='font-weight: bold; color: White; font-size: 10px;'>" . $objRS3->Fields["caption"] . "</span>";
		if($objRS3->Fields["note"] != ""){
		?>
			<br><span style="font-size: 9px; color: White;">(<?=$objRS3->Fields["note"]?>)</span>
		<?
		}
		?>
				</td>
			</tr>
		<?
				$objRS3->Next();
		}
		?>
			</table>
			</div>
		<?
		}
	}

	function DrawAdminLinks(){
		echo '<img src="/lib/images/bulletRed3.gif" style="margin-bottom: 1px;">
		<a href="/lib/admin/table/list.php?PageSize=50&Schema=AdminLinks&Sort1=id&SortOrder=Reverse" class="a12pxBldRed">Manage Links</a>';
	}

    function DrawSmallButton($caption, $bAttr="", $bClasses="", $inpAttr="", $href=""){
        $str = "
        <div class='smallButton $bClasses' $bAttr>
            <b class='leftBg'></b>";
        if(empty($href))
            $str .= "<input type='button' class='caption' value='".htmlspecialchars($caption)."' $inpAttr />";
        else
            $str .= "<a class='caption' href='".htmlspecialchars($href)."' $inpAttr>$caption</a>";
        $str .= "
            <b class='rightBg'></b>
        </div>
        ";
        return $str;
    }

}
?>
