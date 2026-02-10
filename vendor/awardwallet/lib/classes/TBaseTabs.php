<?

// -----------------------------------------------------------------------
// class that deals with drawing and selecting tabs
// Author: Alexi Vereschaga, ITlogy LLC, alexi@itlogy.com, www.ITlogy.com
// -----------------------------------------------------------------------

class TBaseTabs{

	var $Fields;
	var $defaultSelect = "All";
	var $width="100%";
	var $selectedTab;
	var $margin = 30;
	var $AddLink;
	var $AutoHideLine = true;

	function __construct( $arFields, $defaultSelect ){
		$this->Fields = $arFields;
		$this->defaultSelect = $defaultSelect;
	}

	function makeDefaultSelection(){
		$_SESSION["showTabS"] = $this->defaultSelect;
		$this->selectedTab = $this->defaultSelect;
	}

	function selectATab(){
		if(isset($_GET["showTabG"]) && isset($this->Fields[$_GET["showTabG"]])){
			$_SESSION["showTabS"] = $_GET["showTabG"];
			$this->selectedTab = $_GET["showTabG"];
		}
		# select tab if selection was passed through session
		elseif(isset($_SESSION["showTabS"]) && isset($this->Fields[$_SESSION["showTabS"]])){
			$this->selectedTab = $_SESSION["showTabS"];
		}
		# otherwise do a default selection
		else{
			$this->makeDefaultSelection();
		}
		# check situations when a tab was selected somehow but this tab is not in the list of tabs
		$tabSelected = false;
		foreach($this->Fields as $key => $value)
			if($key == $this->selectedTab)
				$tabSelected = true;
		# if a selected tab does not exist, select the tab with the largest amount of programs added
		if(!$tabSelected){
			$this->makeDefaultSelection();
		}
		# finally actually mark the tab to be selected....
		$this->Fields[$this->selectedTab]["selected"] = true;
		#end selecting the right tab
	}

	function drawTabs1(){
		$this->selectATab();
		if(is_array($this->Fields)){
			$img2 = "<img src=\"/lib/images/tabBorderNotSelectedL.gif\" width=\"4\" height=\"36\" alt=\"\">";
			if($this->Fields[$this->defaultSelect]["selected"])
				$img2 = "<img src=\"/lib/images/tabBorderL.gif\" width=\"4\" height=\"36\" alt=\"\">";
		?>
		<table cellspacing="0" cellpadding="0" border="0" style="margin-top: 2px;" width="<?=$this->width?>">
		<tr>
			<td nowrap valign="bottom"><img src="/lib/images/tabCornerLeft.gif" width="5" height="5" alt=""></td>
			<td nowrap width="25" style="border-bottom: solid 5px #E1E1E1;">&nbsp;</td>
			<td nowrap valign="bottom"><?=$img2?></td>
		<?
			$count = 0;
			foreach( $this->Fields as $key => $value ) {
				$count++;
		  		$sel = "";
		  		if($value["selected"]){
					if($count == 1)
						$img1 = "<img src=\"/lib/images/tabBorderSelectedL.gif\" width=\"4\" height=\"33\" alt=\"\">";
					else
						$img1 = "<img src=\"/lib/images/tabBorderSelectedInsideL.gif\" width=\"7\" height=\"33\" alt=\"\">";
		?>
			<td nowrap align="center" style="border-top: solid 1px #BCBCBC">
		<table cellspacing="0" cellpadding="0" border="0" style="margin-top: 3px">
		<tr>
			<td nowrap valign="bottom"><?=$img1?></td>
			<td nowrap bgcolor="#E1E1E1"><div style="margin-left: <?=$this->margin?>px; margin-right: <?=$this->margin?>px; margin-bottom: 8px; color: #AD0000; font-size: 16px; font-weight: bold;">
			<?=$value["caption"]?></div>
			</td>
			<td nowrap valign="bottom"><img src="/lib/images/tabBorderSelectedR.gif" width="4" height="33" alt=""></td>
		</tr>
		</table>
		</td>
			<td nowrap valign="bottom"><img src="/lib/images/tabBorderOuterSelectedR.gif" width="4" height="36" alt=""></td>

		<?
				}
				else{
		?>

			<td nowrap align="center" style="border-top: solid 1px #BCBCBC; background-position: bottom; background-repeat: repeat-x;" background="/lib/images/tabBottomBg.gif"><a href="<?=$value["path"]?>" class="a13pxBlue" style="font-size: 13px; font-weight: bold;"><div style="margin-left: <?=$this->margin?>px; margin-right: <?=$this->margin?>px; margin-bottom: 8px;"><?=$value["caption"]?></div></a></td>
			<td nowrap valign="bottom"><img src="/lib/images/tabBorderR.gif" width="4" height="36" alt=""></td>

		<?
				}
			}
		?>

		<td nowrap width="100%" style="border-bottom: solid 5px #E1E1E1;">&nbsp;</td>
		<td nowrap valign="bottom"><img src="/lib/images/tabCornerRight.gif" width="5" height="5" alt=""></td>
		</tr>
		</table>
		<?
		}
	}

	function drawTabs2(){
		$this->selectATab();
?>
<table cellspacing="0" cellpadding="0" border="0" width="<?=$this->width?>" id="tabsTable">
<tr>
	<td id="tabsAddCell" width="80%" align="right" valign="bottom" style="background-image: url(/lib/images/tabs/tabVertical.gif); background-repeat: no-repeat; background-position: bottom right;"><div style="padding-bottom: 7px; padding-right: 10px;"><? if(isset($this->AddLink)) echo $this->AddLink?></div></td>
<?
		foreach( $this->Fields as $key => $value ) {
		  	$sel = "background-image: url(/lib/images/tabs/tabTopBorder.jpg); background-repeat: repeat-x;";
		  	$aSel = "class=\"notSelected\"";
		  	if($value["selected"]){
				$sel = "background-color: #0b70b7; background-image: url(/lib/images/tabs/tabBg.jpg); background-repeat: no-repeat; background-position: top right;";
				$aSel = "class=\"selected\"";
				$selectedKey = $key;
		  	}
		  	$alink = "<a {$value["path"]} $aSel>";
		  	if(strpos($value["path"], "href=")==false)
		  		$alink = "<a href=\"{$value["path"]}\" $aSel>";

?>
<td nowrap align="center" style="padding-bottom: 7px; padding-left: 20px; padding-right: 20px; <?=$sel?>" valign="bottom"><?=$alink?><?=$value["caption"]?></a></td>
<?
			if($value != end($this->Fields)){
				if($value["selected"])
					$sel = "background-image: url(/lib/images/tabs/tabTopBorder.jpg); background-repeat: repeat-x;";
?>
<td align="center" style="color: #0b70b7; padding-bottom: 7px; <?=$sel?>" width="1" valign="bottom">|</td>
<?
			}
			elseif(!$value["selected"])
				print "<td width=1 valign=\"bottom\"><img src=\"/lib/images/tabs/tabVertical.gif\" alt=\"|\"></td>";
		}
		$sCaption = PIXEL;
		$iHeight = 10;
		if (isset($this->Fields["All"]) && $this->Fields["All"]["selected"] && $this->AutoHideLine) {
#			$tabKeys = array_keys($this->Fields);
#			$sCaption = $this->Fields[$tabKeys[0]]["caption"];
			$iHeight = false;
		}
?>
</tr>
<?if($iHeight){?>
<tr><td colspan="<?=((count($this->Fields)*2)+1)?>" style="background-color: #0b70b7; color: #86b8db; font-size: 24px; padding-left: 10px; font-weight: bold;" height="<?=$iHeight?>"><?=$sCaption?></td></tr>
<?}?>
</table>
<?
	}

	function drawTabs3($bgColor="#71acd6"){
		$this->selectATab();
?>
<table cellspacing="0" cellpadding="0" border="0" width="<?=$this->width?>">
<tr>
<?
		$detailsCounter = 0;
		foreach( $this->Fields as $key => $value ) {
		  	$sel = "background-image: url(/lib/images/tabs/tabTopBorder.jpg); background-repeat: repeat-x;";
		  	$aSel = "style=\"font-weight: bold; font-size: 12px;\"";
		  	if($value["selected"]){
				$sel = "background-color: $bgColor;";
				$aSel = "style=\"color: White; font-weight: bold; text-decoration: none;\"";
				$selectedKey = $key;
		  	}
		  	$alink = "<a {$value["path"]} $aSel>";
		  	if(strpos($value["path"], "href=")===false)
		  		$alink = "<a href=\"{$value["path"]}\" $aSel>";
		  	if($detailsCounter == 0 && !$value["selected"])
				print "<td width=1 valign=\"bottom\"><img src=\"/lib/images/tabs/tabVertical.gif\" alt=\"|\"></td>";

?>
<td nowrap align="center" style="padding-bottom: 7px; padding-left: 20px; padding-right: 20px; <?=$sel?>" valign="bottom"><?=$alink?><?=$value["caption"]?></a></td>
<?
			if($detailsCounter == 0){
				if($value["selected"])
					$sel = "background-image: url(/lib/images/tabs/tabTopBorder.jpg); background-repeat: repeat-x;";
?>
<td align="center" style="color: <?=$bgColor?>; padding-bottom: 7px; <?=$sel?>" width="1" valign="bottom">|</td>
<?
			}
			$detailsCounter++;
		}
		$sCaption = PIXEL;
		$iHeight = 10;
		if (isset($this->Fields["All"]) && $this->Fields["All"]["selected"]) {
#			$tabKeys = array_keys($this->Fields);
#			$sCaption = $this->Fields[$tabKeys[0]]["caption"];
			$iHeight = false;
		}
?>
<td width="70%" height="37" align="left" valign="bottom"><?if(!$this->Fields[$key]["selected"]){?><img src="/lib/images/tabs/tabVertical.gif" alt="|"><?}else{?>&nbsp;<?}?></td>
</tr>
<?if($iHeight){?>
<tr><td colspan="<?=((count($this->Fields)*2)+1)?>" style="background-color: <?=$bgColor?>; color: #86b8db; font-size: 24px; padding-left: 10px; font-weight: bold;" height="<?=$iHeight?>"><?=$sCaption?></td></tr>
<?}?>
</table>
<?
	}
}
?>
