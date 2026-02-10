<?php
// -----------------------------------------------------------------------
// XML parser to parse RSS news feed.
// Author: Alexi Vereschaga, ITlogy LLC, alexi@itlogy.com, www.ITlogy.com 
// Include on a page and set the following parameters before the include:

// ********* Start User-Defined Vars ************
// rss url goes here
// $xmlFile = "http://www.allheadlinenews.com/rss/horses.xml?cat=Horses";
// descriptions (true or false) goes here
// $bDesc = false;
// how many articles should be shown?
// $maxNewsCount = 5;
// ********* End User-Defined Vars **************

// -----------------------------------------------------------------------

ob_flush();
flush();

#begin writing xml parser
class xItem {
	var $xTitle;
	var $xLink;
	var $xDescription;
}

// general vars
$sTitle = "";
$sLink = "";
$sDescription = "";
$arItems = array();
$itemCount = 0;

function startElement($parser, $name, $attrs) {
	global $curTag;
	$curTag .= "^$name";
#print "start element: <textarea cols='40' rows='4'>curTag='$curTag' \nname='$name'</textarea><br><br>";
}

function endElement($parser, $name) {
	global $curTag;
	$caret_pos = strrpos($curTag,'^');
	$curTag = substr($curTag,0,$caret_pos);
#print "end element: <textarea cols='40' rows='4'>curTag='$curTag' \ncaret_pos='$caret_pos'</textarea><br><br>";
}

function characterData($parser, $data) {
	global $curTag; // get the Channel information first
	global $sTitle, $sLink, $sDescription;
	$titleKey = "^RSS^CHANNEL^TITLE";
	$linkKey = "^RSS^CHANNEL^LINK";
	$descKey = "^RSS^CHANNEL^DESCRIPTION";
	if ($curTag == $titleKey) {
		$sTitle = $data;
	}
	elseif ($curTag == $linkKey) {
		$sLink = $data;
	}
	elseif ($curTag == $descKey) {
		$sDescription = $data;
	}
	// now get the items 
	global $arItems, $itemCount;
	$itemTitleKey = "^RSS^CHANNEL^ITEM^TITLE";
	$itemLinkKey = "^RSS^CHANNEL^ITEM^LINK";
	$itemDescKey = "^RSS^CHANNEL^ITEM^DESCRIPTION";

	if ($curTag == $itemTitleKey) {
		// make new xItem    
		$arItems[$itemCount] = new xItem();     
		// set new item object's properties    
		$arItems[$itemCount]->xTitle = $data;
	}
	elseif ($curTag == $itemLinkKey) {
		$arItems[$itemCount]->xLink = $data;
	}
	elseif ($curTag == $itemDescKey) {
		$arItems[$itemCount]->xDescription = $data;
		// increment item counter
		$itemCount++;
	}
#print "Character Data: <textarea cols='80' rows='20'>data='$data' \ncurTag='$curTag' \nsTitle='$sTitle' \nsLink='$sLink' \nsDescription='$sDescription'</textarea><br><br>";
}
// main loop
$showNews = true;
$xml_parser = xml_parser_create();
xml_set_element_handler($xml_parser, "startElement", "endElement");
xml_set_character_data_handler($xml_parser, "characterData");

// open the remote xml feed
$rCurl = curl_init( $xmlFile );
#$rCurl = curl_init( "http://narod.yandex.ru/" );
curl_setopt( $rCurl, CURLOPT_RETURNTRANSFER, True );
curl_setopt( $rCurl, CURLOPT_CONNECTTIMEOUT, 3 );
curl_setopt( $rCurl, CURLOPT_TIMEOUT, 3 );
$data = curl_exec( $rCurl );
curl_close( $rCurl );
#print "<textarea cols='50' rows='30'>$data</textarea>";

if (!xml_parse($xml_parser, $data, true)){
	$showNews = false;
#	print sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), 	xml_get_current_line_number($xml_parser));
}
xml_parser_free($xml_parser);

// write out the items
if($showNews){
	$ubound = $maxNewsCount;
	if(count($arItems) < $maxNewsCount)
		$ubound = count($arItems);
?>
<table cellspacing="0" cellpadding="5" border="0">
<?
	$counter = 0;
	for ($i=0;$i<count($arItems);$i++){
		$txItem = $arItems[$i];
		if($counter < $maxNewsCount && stripos($txItem->xTitle, $titleKeyWord)){
			$counter++;
?>
<tr>
	<td valign="top"><img src="/lib/images/bulletRed1.gif" style="margin-top:5px;"></td>
	<td><a target="_blank" href="<?=$txItem->xLink?>"><?=$txItem->xTitle?></a>
<?			if ($bDesc) {?>
<br><span style="font-size: 10px; color: #666666;"><?=$txItem->xDescription?></span>
<?
			}
?>
	</td>
</tr>
<?
		}
	}
?>
<tr><td colspan="2" align="right"><a href="http://headlinedepot.com" target="_blank" style="font-size:10px; color:#666666;">News provided by Headline Depot</a></td></tr>
</table>
<?
}
else{
	print "The content is not availble";
}
?>
<?#end writing xml parser?>