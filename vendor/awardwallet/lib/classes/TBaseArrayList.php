<?php

// -----------------------------------------------------------------------
// Array List Class.
//		Contains base array list class, to display arrays in a tabular format
//		You should override class to build custom interface.
//		TArrayList = class( TBaseArrayList ) ..
// Author: Alexi Vereschaga, ITlogy LLC, alexi@itlogy.com, www.ITlogy.com 
// -----------------------------------------------------------------------

class TBaseArrayList{
# selects the whole row with this id
	var $selected = -1000;
#Table content
	var $vContent = array();
#table headers
	var $vHeader = array();
#first column preFix
	var $preFix = NULL;
#first column postFix
	var $postFix = NULL;
#header bg color
	var $headerBg = "#A02831";
#row color 1
	var $rowColor1 = "White";
#row color 2
	var $rowColor2 = "#FCF6EA";
#row selected color
	var $rowSelectedColor = "#FDD28C";
#if it is set to something it will be printed across the whole first row
	var $firstRow = NULL;
#if it is set to something it will added to the first td tag
	var $firstRowParams = NULL;
#if it is set to something it will added to all td tags
	var $allTdTags = NULL;

	function DrawArrayTable(){
?>
		<table cellspacing="0" cellpadding="5" class="detailsTable">
		<tr bgcolor="<?=$this->headerBg?>">
<?
		if($this->firstRow != NULL)
			print "<td>&nbsp;</td>";
		foreach($this->vHeader as $key => $value ) {
?>
			<td class="white"><?=$value?></td>
<?
}
?>
		</tr>
		<?
		foreach($this->vContent as $key => $value ) {
			$vTr = " bgcolor=\"{$this->rowColor1}\"";
			$vTd = "";
			if( ( $key % 2 ) == 1 )
				$vTr = " bgcolor=\"{$this->rowColor2}\"";
			if($key == $this->selected){
				$vTr = " bgcolor=\"{$this->rowSelectedColor}\"";
				$vTd = " style='font-weight: bold;'";
			}
			if($this->allTdTags != NULL)
				$vTd .= " " . $this->allTdTags;
			$vTdTemp = $vTd;
		?>
		<tr<?=$vTr?>>
<?
			if(count($value) != count($this->vHeader)){
?>
				<td<?=$vTd?>><?=$this->preFix?><?=$key?><?=$this->postFix?></td>
<?
			}
			if($this->firstRow != NULL){
?>
				<td<?=$vTd?>><?=$this->firstRow?></td>
<?
			}
			if(is_array($value)){
				$i=0;
				foreach($value as $tdKey => $tdValue ) {
					if($i == 0 && $this->firstRowParams != NULL)
						$vTd .= " " . $this->firstRowParams;
					else
						$vTd = $vTdTemp;
					
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
					$i++;
				}
			}//value is not an array
			else{
?>
				<td<?=$vTd?>><?=$value?></td>
<?
			}
?>
		</tr>
		<?
		}
		?>
		</table>
<?
	}
}
?>
