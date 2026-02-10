<?
// -----------------------------------------------------------------------
// THIS IS DEPRICATED CRAP - DO NOT USE! FOLLOW THE SAME CONCEPT AS ON MAIN PAGE OF VERESCH.COM
// forum class for displaying Forum table in many different formats
// Author: Alexi Vereschaga, ITlogy LLC, alexi@itlogy.com, www.ITlogy.com 
// -----------------------------------------------------------------------

class TBaseForumList extends TBaseList{
	# date format
	var $dateFormat = DATE_TIME_FORMAT;
	var $russifyDate = false;
	var $formatHTML = true;
	var $ShowTitle = true;
	var $sItem = "";
	var $addPadding = true;
	var $bShowArchiveLink = true;
	var $showBottomNav = false;
	var $showTopNav = false;
	var $achiveLink = "/newsArchive.php";
	var $iColumns = 2;

	#forum format, with header info at the top (email, date, name), subject in the middle and main message body after that...
	function Draw(){
		global $Connection;
		$this->OpenQuery();
		$objRS = &$this->Query;
		if( !$objRS->EOF )
		{
			if($this->showTopNav)
				$this->drawPageDetails("bottom", True);
			echo "<form method=post name=list_{$this->Table}>";
			echo "<input type=hidden name=action>\n";
			print "<table cellspacing=0 cellpadding=0 border=0 width='100%'>";
			while( ( $this->UsePages && !$objRS->EndOfPage() ) || ( !$this->UsePages && !$objRS->EOF ) )
			{
				$this->FormatFields();
				$this->DrawRow();
				$objRS->Next();
			}
			if($this->bShowArchiveLink)
				print "<tr><td height='20' colspan='".$this->iColumns."' align='right'><a style='font-size: 10px;' href='".$this->achiveLink."'>archive &gt;&gt;</a></td></tr>";
			print "</table>";
			echo "</form>";
			if($this->showBottomNav)
				$this->drawPageDetails("top", False);
		}
		else
			$this->DrawEmptyList();
	}

	function Draw1(){
		global $Connection, $QS, $color1, $Interface;
		$headerColor = "#b0b0b0";
		$this->Query = New TQuery( $this->AddFilters( $this->SQL . $this->GetOrderBy() . " limit {$this->Limit}" ), $Connection );
		$objRS = &$this->Query;
		if( isset( $QS["PageSize"] ) )
			$objRS->PageSize = intval( $QS["PageSize"] );
		if( $this->ShowTotals )
			$this->Totals = TotalPageNavigator( $objRS );
		// create url parameters
		$arQS = $QS;
		unset( $arQS["ID"] );
		unset( $arQS["Page"] );
		$this->URLParamsString = ImplodeAssoc( "=", "&", $arQS, True );
		if( $this->URLParamsString != "" )
			$this->URLParamsString = "&" . $this->URLParamsString;
		// filters
		if( !$objRS->EOF ){
			if( $this->UsePages )
			{
				$objRS->SelectPageByURL( "Page", array( $this->KeyField ) );
				$this->PageNavigator = $objRS->PageNavigator();
			}
?>
<table cellspacing="0" cellpadding="5" border="0" width="100%">
<form method=post name=list_<?=$this->Table?>>
<?
			while( ( $this->UsePages && !$objRS->EndOfPage() ) || ( !$this->UsePages && !$objRS->EOF ) ){
				if( !isset( $sFormat ) )
					$sFormat = $this->dateFormat;
				$d = $Connection->SQLToDateTime( $objRS->Fields["PostTime"] );
				if( $d > 0 )
					$objRS->Fields["PostTime"] = date( $sFormat, $d );
				else
					$objRS->Fields["PostTime"] = "";
				if($this->russifyDate)
					$objRS->Fields["PostTime"] = $Interface->russifyMonths($objRS->Fields["PostTime"]);
?>
<tr>
	<td>
<?if($objRS->Fields["FullName"]!="" || $objRS->Fields["Email"]!="" || $objRS->Fields["PostTime"]!=""){?>
			<table cellspacing="0" cellpadding="0" border="0">
<?	if($objRS->Fields["FullName"]!=""){?>
			<tr>
				<td style="color: <?=$headerColor?>;">Name:</td>
				<td width="15">&nbsp;</td>
				<td style="color: <?=$headerColor?>;"><?=$objRS->Fields["FullName"]?></td>
			</tr>
<?	}if($objRS->Fields["Email"]!=""){?>
			<tr>
				<td style="color: <?=$headerColor?>;">Email:</td>
				<td width="15">&nbsp;</td>
				<td style="color: <?=$headerColor?>;"><?=$objRS->Fields["Email"]?></td>
			</tr>
<?	}if($objRS->Fields["PostTime"]!=""){?>
			<tr>
				<td style="color: <?=$headerColor?>;">Date:</td>
				<td width="15">&nbsp;</td>
				<td style="color: <?=$headerColor?>;"><?=$objRS->Fields["PostTime"]?></td>
			</tr>
<?	}?>
		</table>
<?
}
else
	print "&nbsp;";
?>
	</td>
	<td align="right" valign="top">
<?echo $this->GetEditLinks();?>
	</td>
</tr>
<tr>
	<td align="center" colspan="2">
	<strong><?=$objRS->Fields["Title"]?></strong>
	</td>
</tr>
<tr>
	<td style="border-bottom: 1px solid #E1E1E1;" colspan="2">
<?
if($this->formatHTML)
	print TextToHTML($objRS->Fields["BodyText"]);
else
	print $objRS->Fields["BodyText"];
?>
	</td>
</tr>
<?
				$objRS->Next();
			}
?>
</table>
<?
			$s = $objRS->PageNavigator();
			if( $s != "" ){
?>
<table width=100% border="0" cellspacing="0" cellpadding="5" class="listFooter">
<tr>
	<td align="right"><?=$s?></td>
</tr>
</table>
<?
			}
		}
		else{
			$Interface->DrawMessageBox("There are no records to display at this time");
		}
	}
#A very simple news format. Used on zapfoot.com first page.
	function Draw2(){
		global $Connection, $Interface;
		$objRS = New TQuery( $this->SQL, $Connection );
		while( !$objRS->EOF ){
			$sFormat = "d F, Y";
			$d = $Connection->SQLToDateTime( $objRS->Fields["PostTime"] );
			if( $d > 0 )
				$objRS->Fields["PostTime"] = date( $sFormat, $d );
			else
				$objRS->Fields["PostTime"] = "";
?>
<strong><?=$Interface->russifyMonths($objRS->Fields["PostTime"])?></strong><br>
<?=$objRS->Fields["BodyText"]?><br><br>
<?
			$objRS->Next();
		}
	}

#A very simple format. Used on EverythingEquus fraud page
#Draws title and then message body underneath...
	function Draw3($titleBgColor = "#FDC1C6"){
		global $Connection, $Interface;
		$objRS = New TQuery( $this->SQL, $Connection );
		while( !$objRS->EOF ){
		$Interface->drawSectionDivider($objRS->Fields["Title"])
?>
<br>
<?
if($this->formatHTML)
	print TextToHTML($objRS->Fields["BodyText"]);
else
	print $objRS->Fields["BodyText"];
?>
<br><br>
<?
			$objRS->Next();
		}
	}
#Standard forum format with the ability to post messages...
	function Draw4(){
		$this->Draw1();
?>
<br>
<div align="center"><a href="/lib/admin/edit.php?ID=0&cnf=/lib/admin/log/logFields.php">Add a New Post &gt;&gt;</a></div>
<?
	}
	function DrawNews1(){
		global $Connection, $Interface, $objPictureManager;
		$objRS = New TQuery( $this->SQL, $Connection );
		while( !$objRS->EOF ){
			if( !isset( $sFormat ) )
				$sFormat = $this->dateFormat;
			if($objRS->Fields["NewsTime"] != ""){
				$d = $Connection->SQLToDateTime( $objRS->Fields["NewsTime"] );
				if( $d > 0 )
					$objRS->Fields["NewsTime"] = date( $sFormat, $d );
			}
			else{
				$objRS->Fields["NewsTime"] = "";
			}
			$Pic = PicturePath( $objPictureManager->Dir, "large", $objRS->Fields["NewsID"], $objRS->Fields["NewsPhotoVer"], $objRS->Fields["NewsPhotoExt"], $objPictureManager->Prefix );
			$Thumb = PicturePath( $objPictureManager->Dir, "small", $objRS->Fields["NewsID"], $objRS->Fields["NewsPhotoVer"], "gif", $objPictureManager->Prefix );
			$pageWidth = PICTURE_WIDTH + 20;
			$pageHeight = PICTURE_HEIGHT + 20;
			$lPadding = $rPadding = "";
			if($this->addPadding){
				$lPadding = "padding-left: 15px;";
				$rPadding = "padding-right: 15px;";
			}
?>
<table cellspacing="0" cellpadding="0" border="0" width="100%">
<?if($objRS->Position > 1){?>
<tr><td colspan="2" height="10"><?=PIXEL?></td></tr>
<?}?>
<?
if($objRS->Fields["Title"] != "" && $this->ShowTitle){
?>
<tr>
	<td colspan="2"><?$Interface->drawSectionDivider2($objRS->Fields["Title"])?></td>
</tr>
<tr><td colspan="2" height="10"><?=PIXEL?></td></tr>
<?
}
if($objRS->Fields["NewsTime"] != ""){
?>
<tr>
	<td align="right" colspan="2"><div style="margin-left: 20px; font-style: italic;"><?=$objRS->Fields["NewsTime"]?></div></td>
</tr>
<?
}
?>
<tr>
<?
if($objRS->Fields["NewsPhotoVer"] != ""){
?>
	<td style="<?=$lPadding?>" align="center" valign="top">
<table border="0" cellpadding="0" cellspacing="0">
<tr>
	<td style="font-size: 12px; border: 1px solid Red; padding-top: 4px; padding-bottom: 4px; padding-right: 4px; padding-left: 4px;" colspan="2" valign="middle" align="center" bgcolor="#FCF6EA"><a href="#" onclick="openAWindow( '<?=$Pic?>', 'News', <?=$pageWidth?>, <?=$pageHeight?>, 1, 0 ); return false;"><img src="<?=$Thumb?>" alt="News Picture" border="0"></a></td>
</tr>
</table>
<div style="padding-top: 5px; padding-bottom: 5px;">
<a href="#" onclick="openAWindow( '<?=$Pic?>', 'News', <?=$pageWidth?>, <?=$pageHeight?>, 1, 0 ); return false;"  class="a12pxBlue" style="font-size: 11px;"><img src="/lib/images/zoom1.gif" border="0" style="margin-bottom: -4px; margin-right: 8px;"></a><a href="#" onclick="openAWindow( '<?=$Pic?>', 'News', <?=$pageWidth?>, <?=$pageHeight?>, 1, 0 ); return false;"  class="a12pxBlue" style="font-size: 11px;">Zoom</a></div>
</td>
<?
	$lPadding = "padding-left: 15px;";
}
?>
	<td style="<?=$lPadding?> <?=$rPadding?>" valign="top">
<?
if($this->formatHTML)
	print TextToHTML($objRS->Fields["BodyText"]);
else
	print $objRS->Fields["BodyText"];
?>
</td>
</tr>
</table>
<?
			$objRS->Next();
		}
	}
	function DrawTitles1(){
		global $Connection, $Interface;
		$objRS = New TQuery( $this->SQL, $Connection );
?>
<table cellspacing="0" cellpadding="5" border="0" width="100%">
<?
		while( !$objRS->EOF ){
?>
<tr>
	<td height="20" valign="top"><img style="margin-top: 5px; margin-left: 0px;" src="/lib/images/bulletRed1.gif" alt=""></td>
	<td align="left" width="100%"><a href="<?=$this->sItem?>Details.php?ID=<?=$objRS->Fields["ForumID"]?>"><?=$objRS->Fields["Title"]?></a></td>
</tr>
<?
			$objRS->Next();
		}
?>
</table>
<?
	}
	#A very simple news format. Used on EuroLifeFurniture.com first page.
	function DrawNews2(){
		global $Connection;
		$objRS = New TQuery( $this->SQL, $Connection );
		while( !$objRS->EOF ){
			$sFormat = "d M, Y";
			$d = $Connection->SQLToDateTime( $objRS->Fields["NewsTime"] );
			if( $d > 0 )
				$objRS->Fields["NewsTime"] = date( $sFormat, $d );
			else
				$objRS->Fields["NewsTime"] = "";
?>
<tr>
	<td valign="top"><img src="/lib/images/bulletOrange1.gif" style="margin-top: 5px;"></td>
	<td style="padding-left: 5px; padding-right: 15px; color: <?=COLOR_DARK_GRAY?>;"><span style="font-weight:bold;">(<?=$objRS->Fields["NewsTime"]?>)</span> <?=$objRS->Fields["BodyText"]?></td>
</tr>
<tr><td colspan="2">&nbsp;</td></tr>
<?
			$objRS->Next();
		}
	}
#A very simple news format. Used on veresch.com first page. This time i will try to be smart about it...
	function DrawNews3(){
		global $Connection;
		$this->OpenQuery();
		$objRS = &$this->Query;
		if( !$objRS->EOF )
		{
			print "<table>";
			while( ( $this->UsePages && !$objRS->EndOfPage() ) || ( !$this->UsePages && !$objRS->EOF ) )
			{
				$this->FormatFields();
				$this->DrawNewsRow3();
				$objRS->Next();
			}
			print "</table>";
		}
		else
			$this->DrawEmptyList();
	}
	
// draw one row for News 3
	function DrawNewsRow3()
	{
		$objRS = &$this->Query;
?>
<tr>
<td valign="top" height="25"><img src="/lib/images/bulletOrange1.gif" style="margin-top: 9px;"></td>
<td style="padding-left: 5px; padding-right: 5px; color: <?=COLOR_DARK_GRAY?>;"><span style="font-weight:normal; font-size: 12px;"><?=$objRS->Fields["NewsTime"]?></span> -  <?=$objRS->Fields["BodyText"]?></td>
</tr>
<?
	}
}
?>
