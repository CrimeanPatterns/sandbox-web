<?
//namespace EmailAdmin;

global $hInstance;
global $dateRegExps;
global $emlIntlMonths;
global $airlineCodes;

// Dummy class created to use functions from traits in this file and to prevent code duplication (e.g. same functions
// cost() here and PriceTools::cost(), changes in which should be tracked manually and could cause to troubles).
// Yes, it is dirty hack, but big and messy global tools file is worse.
class ToolsTraitWrapper {

	use DateTimeTools;
	use PriceTools;
	use RegExpTools;
	use StringTools;
	use ItineraryTools;

}

$hInstance = (object) array();

// If used not in email parsers web IDE ("adminka") - it is better to use StringTools::glue instead of this function
function glue($str, $with = ", ")
{
	$w = new ToolsTraitWrapper();

	return $w->glue($str, $with);
}

// If used not in email parsers web IDE ("adminka") - it is better to use RegExpTools::re instead of this function
function re($re, $text=false, $index = 1)
{
	global $hInstance;

	if (is_array($re)){
		foreach($re as $reItem){
			$res = re($reItem, $text, $index);
			if ($hInstance->lastRe) return $res;
		}
		return null;
	}

	if (is_numeric($re) && $text==false){
		return ($hInstance->lastRe && isset($hInstance->lastRe[$re])) ? $hInstance->lastRe[$re] : null;
	}

	if ($text === false){
		$text = $hInstance->targetSource;
	}

	if (!is_string($text) && is_callable($text)){ // we have function

		// go through the text using replace function
		return preg_replace_callback($re, function($m) use($text){
			return $text($m);
		}, $index); // index as text in this case
	}

	// convert node to string
	if ($text && !is_string($text) && in_array(get_class($text), ['DOMElement', 'DOMNodeList'])){
		// cache result
		if (isset($text->cachedText)){
			$text = $text->cachedText;
		} else {
			$value = text($text);
			$text->cachedText = $value;
			$text = $value;
		}
		//$text = text($text);
	}

	// check if nospace flag exist
	if (preg_match("#^(.*?[^a-z])([IMGCUXimgcu]*?)x([IMGCUXimgcu]*?)$#is", substr($re,-10,10), $m)){ // quick check
		if (preg_match("#^(.*?[^A-Za-z\d])([IMGCUXimgcux]*?)$#is", $re, $m)){ // now replace spaces
			$re = preg_replace("#[ \t\f\v]+#", '\s+', $m[1]).str_ireplace("x",'',$m[2]);
		}
	}

	if (preg_match($re, $text, $m)){
		$hInstance->lastRe = $m;
		return isset($m[$index]) ? $m[$index] : $m[0];
	} else {
		$hInstance->lastRe = null;
		return null;
	}
}

// If used not in email parsers web IDE ("adminka") - it is better to use PriceTools::cost instead of this function
function cost($value)
{
	$w = new ToolsTraitWrapper();
	return $w->cost($value);
}

// If used not in email parsers web IDE ("adminka") - it is better to use PriceTools::currency instead of this function
function currency($text)
{
	$w = new ToolsTraitWrapper();
	return $w->currency($text);
}

// If used not in email parsers web IDE ("adminka") - it is better to use StringTools::htmlToPlainText instead of this function
function text($html)
{
	global $hInstance;
	$w = new ToolsTraitWrapper();

	if ($html && !is_string($html)){
		return $w->htmlToPlainText(html($html, is_object($hInstance->targetSource)?$hInstance->targetSource:null));
	} else {
		return $w->htmlToPlainText($html);
	}
}

function orval()
{
	$array = func_get_args();
	$n = sizeof($array);

	for ($i=0; $i<$n; $i++){
        if (((gettype($array[$i]) == 'array' || (gettype($array[$i]) == 'object' && $array[$i] instanceof Countable)) && sizeof($array[$i])>0) || $i == $n-1) return $array[$i];
        if ($array[$i]) return $array[$i];
	}
	return '';
}

function targetInstance($newInstance)
{
	global $hInstance;
	$hInstance = $newInstance;
}

function node($xpath, $from = null, $allowEmpty = true, $regexp = null, $nodeIndex = null)
{
	global $hInstance;
	return $hInstance->http->FindSingleNode($xpath, orval($from, is_object($hInstance->targetSource)?$hInstance->targetSource:null), $allowEmpty, $regexp, $nodeIndex);
}

function nodes($xpath, $from = null, $regexp = null)
{
	global $hInstance;
	return $hInstance->http->FindNodes($xpath, orval($from, is_object($hInstance->targetSource)?$hInstance->targetSource:null), $regexp);
}

function xPath($xpath, $from = null)
{
	global $hInstance;
	return $hInstance->http->XPath->query($xpath, orval($from, is_object($hInstance->targetSource)?$hInstance->targetSource:null));
}

function disableEncoding($bool = true)
{
	global $hInstance;
	$html = $hInstance->parser->getHtmlBody();
	$hInstance->http->ParseEncoding = $bool;
	$hInstance->http->SetBody($html);
}

// If used not in email parsers web IDE ("adminka") - it is better to use StringTools::nicify instead of this function
function nice($text, $glue = false)
{
	$w = new ToolsTraitWrapper();
	return $w->nicify($text, $glue);
}

function clear($re, $text, $by='')
{
	return preg_replace($re, $by, $text);
}

function detach($re, &$text, $by='')
{
	$value = preg_match($re, $text, $m)?(isset($m[1])?$m[1]:$m[0]):null;
	$text = preg_replace($re, $by, $text);
	return $value;
}

function splitter($re, $text = false){
	global $hInstance;

	if (func_num_args() < 2){
		$text = $hInstance->targetSource;
	}

	$r = preg_split($re, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
	$ret = [];

	if (count($r) > 1){
		array_shift($r);
		for($i=0; $i<count($r)-1; $i+=2){
			$ret[] = $r[$i].$r[$i+1];
		}
	} elseif (count($r) == 1){
		$ret[] = reset($r);
	}
	return $ret;
}

$airlineCodes = "1T|Q5|AN|1B|W9|ZI|AQ|AA|OZ|4K|8U|Q9|G4|K5|M3|GB|GB|8V|E4|YT|4G|7A|8T|ZY|Z7|JP|UX|EM|A3|KI|PE|KO|5W|VV|I9|WK|QQ|FG|SU|JA|AF|SB|GN|2O|2Q|V7|SW|G8|5D|1A|7T|PL|8A|GD|LD|HT|J2|U3|AP|5A|ED|AB|AG|AI|ZB|CC|RB|TN|W4|IZ|JM|AP|S2|KM|M6|NQ|4A|EH|HP|ZW|U9|VD|TT|QM|BM|NX|ZV|HM|AM|NH|YW|PX|G9|AC|BT|EL|TL|4N|IW|NZ|J6|2D|6V|XM|OE|GV|JW|W3|2B|4C|AR|QN|AS|4D|PL|EV|OB|TC|2J|HC|FO|PJ|8C|OS|IQ|RU|MO|5N|GR|NO|AU|AO|AV|NF|K8|B9|DR|7E|6G|TX|IX|HJ|XT|AK|6V|3G|AZ|ZE|Z8|UM|R7|FV|RX|MQ|FF|ML|VU|BP|GS|VT|3N|VL|FK|G2|V8|VE|V5|CA|Q6|5F|QC|NV|CV|CW|ZA|AH|KI|ER|HO|EN|D9|NM|EE|4F|EI|E8|KY|PC|OF|FJ|RC|QH|NY|2P|ZX|2U|0A|DA|GL|LL|5Y|GG|H9|GG|8C|W9|IP|QK|KK|JS|KC|LV|D4|CD|XL|A6|TD|L8|LK|MK|MD|9U|M0|A7|QO|MR|3S|8D|F4|AJ|8Y|OT|AD|QD|QS|4Y|MC|RE|UU|6K|RK|A5|QL|MV|U8|BQ|P5|BF|5L|EX|JR|Z3|M3|GM|R3|VW|JY|OR|CG|TY|FL|TS|EC|VO|DW|6U|2K|6R|8Q|BA|BG|BF|B4|BZ|JA|J4|A8|4T|UP|E6|LZ|TH|BS|B4|PG|KF|JV|B3|BD|WW|CH|5Z|BO|BV|7R|8E|B2|K6|BN|GQ|V9|7P|J8|QW|8W|BM|DB|E9|SN|NT|0B|KJ|FB|8N|5C|AW|XG|MO|R9|UY|C6|CP|5T|W2|9K|PT|GG|2G|W8|CV|BW|8B|V3|RV|CX|KX|5J|7N|C0|J7|WE|OP|MG|2Z|S8|RP|C8|CI|CK|MU|CJ|WH|8Y|CZ|HR|XO|3Q|X7|A2|QI|C9|CF|G3|WX|CJ|CT|6P|BX|DQ|9L|OH|MN|C5|KR|GJ|CP|DE|6A|C3|CO|PC|CO|CS|V0|CM|SS|XK|F5|OU|QE|CU|CY|YK|OK|WD|DX|ES|L3|D3|N2|H8|0D|D5|DL|2A|1I|D7|AW|DH|D8|DO|E3|5D|KA|KB|DI|1C|VE|BR|H7|QU|S9|T3|DK|W9|WK|MS|LY|UZ|EK|EM|EU|G8|E0|B8|E7|OV|ET|EY|RZ|MM|UI|GJ|K2|3W|EA|QY|E7|EW|EZ|JN|MB|OW|8D|EO|XE|U2|IH|EF|F6|F3|FX|N8|4S|AY|FC|FY|7F|DP|8F|B5|PA|RF|F2|SH|D7|TE|LF|F7|BE|B4|VY|BN|HK|FP|F9|2F|FH|GT|7O|1G|GC|G7|GA|4G|GR|A9|QB|ST|4U|GP|G0|G8|GK|G7|G3|DC|G1|GS|ZK|IJ|TA|G6|J9|G8|GF|GY|H6|HR|HU|1R|2T|4R|X3|HF|HB|HQ|HA|HP|BH|HN|8H|JB|ZU|T4|HW|2L|DU|EO|UD|5K|HD|H5|HX|UO|HH|QX|BN|H4|II|C3|1F|1U|IB|TY|FW|FI|IK|DH|6E|IC|I9|QZ|IO|IJ|H4|D6|ZA|RS|ID|6I|3L|I4|IR|EP|IA|IS|2S|8L|CN|IF|WC|6H|9X|GI|H9|JC|JO|1M|JL|JL|EG|NU|JU|VJ|J9|7C|9W|QJ|0J|3K|LS|8J|B6|JF|0J|SG|JQ|JX|GX|R5|HO|KD|WA|KL|N2|K4|RQ|E2|V2|KV|M5|KQ|BZ|IT|Y9|KP|7K|8J|KE|7B|K9|GW|VD|KU|GO|N5|QH|R8|KY|JF|LR|KG|LA|4M|LU|LP|LO|XO|L3|LT|N6|IK|QV|L7|NG|LQ|LI|LN|L7|LD|ZE|LM|JT|LM|LB|HE|LH|LH|CL|L1|DV|L5|LG|5V|L2|MJ|M7|IN|OM|MB|CC|DM|W5|M2|MH|TF|R5|MA|RI|AE|JE|6V|M7|MP|Q4|H5|8M|MY|MW|IM|IG|MZ|YV|XJ|MX|GL|ME|JI|YX|MY|2M|ZB|8I|YM|3R|M9|NM|N4|VZ|UB|8M|DV|P9|UE|N4|N7|NA|9O|NC|CE|ON|1N|RA|NO|1I|EJ|2N|HG|KZ|DD|JH|6N|N9|M3|HW|NC|U7|NW|FY|J3|DY|BJ|M4|1I|N6|XY|UQ|CR|O8|VC|O6|O2|OA|WY|OY|N3|8Q|R2|OX|QO|OL|ON|OJ|OZ|O7|PV|9Q|PU|U4|Y5|BL|DJ|8P|Q8|PS|LW|GX|PK|GP|PF|NR|PA|PA|PQ|P8|I7|HP|PC|1I|KS|P9|PR|HP|9R|PI|9E|PO|PH|DJ|1U|PD|NI|BK|PW|TO|FE|8Q|8W|P0|QF|QR|R6|1D|8L|V4|FN|ZL|3C|QQ|RW|RH|C7|E2|SL|R4|GZ|RR|RS|AT|R0|V5|BI|RJ|RK|RA|WR|P7|WB|RD|FR|YS|S4|SA|NL|MM|SK|S7|BB|UL|SY|G3|SG|I6|EH|7G|FA|N5|SP|8S|SQ|5M|SI|XS|SJ|ZS|SQ|FT|SX|SM|DG|VD|5G|FS|SD|PI|EZ|SV|WN|A4|WG|LX|SR|WV|S8|XQ|RB|AL|ZP|E5|SC|9S|3U|FM|ZH|8C|7L|NE|CQ|SO|JK|2G|1Z|1S|1I|1H|1Q|1I|1K|1K|2C|2S|NK|9R|S0|SO|1I|SX|RU|S3|H2|OO|JZ|BC|LJ|MI|6Q|PY|8D|NB|6J|IE|6W|HZ|S5|DV|R1|S6|EQ|JJ|TP|TU|3V|M7|T2|FQ|MT|TQ|L9|UE|ZT|TR|TT|TG|FD|TK|T7|9I|TD|TL|GY|3P|7T|TI|BY|PM|FF|QT|GE|HV|VR|T9|TH|S5|9T|UN|T9|T5|UG|T6|QS|TW|AL|6B|DT|SF|PZ|AX|1E|2H|1L|RO|3T|8R|OF|U5|UA|U2|U7|U6|UF|6Z|5X|US|UT|HY|PS|UH|VA|VF|VC|VN|NN|2R|VA|Y4|VI|VX|TV|VK|VS|ZG|VE|VY|XF|LC|VM|VA|DJ|RG|VP|VG|7W|WJ|2W|PT|8O|WS|WA|CN|WF|IV|IW|K5|W6|8Z|WO|1P|8V|SE|MF|XP|YL|Y8|IY|Q3|3J|C4|Z4|UK|8Q|Y0|VJ|6T|SH|BX|TB|XW|BU|GH|9Y|JD|ZQ|DS|2I|KW|4H|M8|5H|TO|WP|OG|B7|YD|WZ|FU|K1|XX|7J|TM|GY|ML|VH|Z2|HK|IJ|N4|ZE|LJ|SH|3O|B1|YC|CN|FA|3I|DN|L8|BU|XS|IL|SZ|TQ|KT|DH|ZZ|A1|UK|CB|RY|VB|5P|C1|V5|SH|C2|QA|W1|J4|E9|3E|KN|ZQ|4Q|K5|S6|IL|V9|6D|E8|J5|T8|I6|IP|T0|7Q|PF|3S|P7|V6|P9|OC|7Z|4P|E9|K8|UJ|6Y|K7|E4|HT|WD|Q9|DA|8F|7Y|UQ|G6|RL|4L|WG|ZF|VQ|DZ|EH|L7|6P|GP|SM|OQ|PN|IH|C3|1C|Y7|JR|CD|9H|Q2|XN|VC|NA|JH|5E|F8|AO|PA|ES|GB|MR|GM|WQ|YY|KY|BQ|CQ|WU|LQ|BZ|K6|D5|H3|AD|PQ|C4|X1|GK|XZ|FZ|D1|9A|CR|JY|YZ|YP|UR|G5|ZP|M4|N1|4Z|3B|OD|BZ|GM|TB|TH|9C|NJ|TJ|P8|H3|HH|Z6|TI|YT|Y1|NS|S1|WM|YO|CB|SF|F1|1F|3F|T6|HC|G1|WG|ZX|N0|W7|H9|IJ|QC|N6|RS|YI|II|BU|L4|Z7|6U|7M|2D|TW|HI|JX|XB|W4|KH|E8|GN|E1|HN|RR|Z7|1H|PT|QY|4X|Y8|7H|R8|H1|D5|H5|IJ|DN|VB|KT|XV|SO|ZJ|YQ|T1|L6|6F|AW|E5|CT|OI|Y5|XR|NP|DN|6I|S9|0X|8B|YR|C7|PP|U1|MM|U1|DF|ZC|I5|9P|B0|KP|I2|4O|OG|NJ|TZ|SX|5K|WH|ZN|O1|A6|P4|V2|C8|5Q|YE|KG|FH|2D|YH|TJ|SX|J7|W2|WL|E6|HQ|R1|Q3|J7|OE|OE|OE|NO|OD|9F|GC|Z9|I8|3V|M1|7I|5Z|ZM|M2|NR|GD|SL|DW|N8|QG|GU";	//13|00|12|10|01|11|20|04|24|78|76|77|99|47|69|07|00
$airportCodes = "";

$emlIntlMonths = array(
	'en' => ['January','February','March','April','May','June','July','August','September','October','November','December'],
	'ru' => ['январь','февраль','март','апрель','май','июнь','июль','август','сентябрь','октябрь','ноябрь','декабрь'],
 	'ru2' => ['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'],
 	'de' => ['Januar','Februar','März','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'],
	'de3'=> ['jan', 'feb', 'mrz', 'apr', 'mai', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dez'],
	'deA'=> ['jan', 'feb', 'mae', 'apr', 'mai', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dez'], // "mae" different
	'deB'=> ['Januar','Februar','Maerz','April','Mai','Juni','Juli','August','September','Oktober','November','Dezember'], // "maerz" different
	'nl' => ['januari','februari','maart','april','mei','juni','juli','augustus','september','oktober','november','december'],
	'nl2' => ['______','______','mrt','______','______','______','______','______','______','______','______','______'],
	'no' => ['januar','februar','mars','april','Kan','juni','juli','august','september','oktober','november','desember'],
	'fr' => ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'],
	'es' => ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'],
	'pt' => ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'],
	'it' => ['gennaio','febbraio','marzo','aprile','maggio','giugno','luglio','agosto','settembre','ottobre','novembre','dicembre'],
	'it2' => ['gen','feb','mar','apr','mag','giu','lug','ago','set','ott','nov','dic'], // Could not find short month names for Italian, converted them from full names so mistakes may be here
	'fi' => ['tammikuuta','helmikuuta','maaliskuuta','huhtikuuta','toukokuuta','kesäkuuta','heinäkuuta','elokuuta','syyskuuta','lokakuuta','marraskuuta','joulukuuta'],
	'pt' => ['Janeiro','Fevereiro', 'Março', 'Abril', 'Maio', 'junho', 'julho', 'agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
	'da' => ["Januar", "Februar", "Marts", "April", "Maj", "Juni", "juli", "August", "September" , "Oktober", "November", "December"],
	'tr' => ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'],
	'pl' => ['styczeń', 'luty', 'marzec', 'kwiecień', 'maj', 'czerwiec', 'lipiec', 'sierpień', 'wrzesień', 'październik', 'listopad', 'grudzień'],
	'pl2' => ['styczeń', 'luty', 'marzec', 'kwiecień', 'maj', 'czerwiec', 'lipca', 'sierpień', 'wrzesień', 'października', 'listopad', 'grudzień'], 
	'zh' => ['一月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '十一月', '十二月'],
	'hu' => ["január", "február", "március", "április", "május", "június", "július", "augusztus", "szeptember", "október", "november", "december"],
	'sv' => ['januari', 'februari', 'mars', 'april', 'maj', 'juni', 'juli', 'augusti', 'september', 'oktober', 'november', 'december'],
	'cs' => ['ledna', 'únor', 'březen', 'dubna', 'květen', 'června', 'července', 'vznešený', 'září', 'říjen', 'listopadu', 'prosince'],
	'th' => ['______', '______', '______', '______', '______', '______', '______', '______', 'ก.ย.', '______', '______', '______'],
	'ro' => ['ian', 'feb', 'mar', 'apr', 'mai', 'iun', 'iul', 'aug', 'sep', 'oct', 'noi', 'dec'],
);

$dateRegExps = array(

	// full dates with named month
	"\d+[ \t.-]\s*\p{L}{3,}[ \t.-]\s*\d+", //"30-June 2008", "22DEC78", "14 III 1879"
	"\p{L}{3,}[ .\t-]\s*\d+[,.stndrh\t ]+\d+", //"July 1st, 2008", "April 17, 1790", "May.9,78"*/
	"\p{L}{3,}\-\d+\-\d+", //"May-09-78", "Apr-17-1790"
	"\d+\-\p{L}{3,}\-\d+", //"78-Dec-22", "1814-MAY-17"

	// no year
	"\d+[ .\t-]\p{L}{3,}", //"1 July", "17 Apr", "9.May"
	"\p{L}{3,}[ .\t-]\d+[,.stndrh\t ]*", //"July 1st,", "Apr 17", "May.9"

	// full dates with numeric month
	"\d+[.\t-]\d+[.\t-]\d{4}", //"30-6-2008", "22.12\t1978"
	"\d{4}/\d+/\d+", //"2008/6/30", "1978/12/22"
	"\d+\-\d+\-\d+", //"2008-6-30", "78-12-22", "8-6-21"
	"\d+[.\t-]\d+[.\t-]\d{2}", //"30.6.08", "22\t12\t78"
	"\d+/\d+/\d+", //"12/22/78", "1/17/2006", "1/17/6"
	"\d{1,2}/\d{1,2}", //"5/12", "10/27"

	// no day
	"\d{4}[ \t.-]\p{L}{3,}", //"2008 June", "1978-XII", "1879.MArCH"
	"\p{L}{3,}[ \t.-]\d{4}", //"June 2008", "DEC1978", "March 1879"
	"\d{4}\-\d+", //"2008-6", "2008-06", "1978-12"
);

// If used not in email parsers web IDE ("adminka") - it is better to use DateTimeTools::monthNameToEnglish or DateTimeTools::dateStringToEnglish instead of this function
function en($date, $ln = false, &$found = false)
{
	global $emlIntlMonths;
	$list = $ln?(is_array($ln)?$ln:array($ln)):array_keys($emlIntlMonths);
	$date = strtolower($date);

		$date = preg_replace("#(\d+)(\p{L})#", '$1 $2', $date);

	foreach($list as $ln){
		if (isset($emlIntlMonths[$ln])){
			$possible = preg_split("#[\s,\-/]+#", $date);

			foreach($possible as $item){
				if (!trim($item,",\n\t ")) continue;

				$i = 0;
				foreach($emlIntlMonths[$ln] as $mn){
					$mn = strtolower($mn);
					if (stripos($mn, $item) === 0) {
						$date = str_ireplace($item, $emlIntlMonths['en'][$i], $date);
						$found = true;
						return trim($date," \t\n,.");
					}
					$i++;
				}
			}
		}
	}
	return $date;
}

function hasMonth($date)
{
	$found = false;
	$r=en($date, false, $found);
	return $found;
}

function ure($re, $text = false, $index = 1)
{
	global $hInstance;
	if (is_numeric($text)){
		$index = $text;
		$text = $hInstance->targetSource;
	}
	elseif ($text === false){
		$index = 1;
		$text = $hInstance->targetSource;
	}
	if (is_object($text) and get_class($text) == 'DOMElement'){
		$text = text($text);
	}

	$res = [];
  preg_replace_callback($re, function($m) use(&$res){
  	$res[] = $m[1];
  }, $text, $index);

  return isset($res[$index-1])?$res[$index-1]:null;
}

function uberDateTime($text = false, $index = 1)
{
	if (is_object($text) and get_class($text) == 'DOMElement'){
		$text = text($text);
	}

	$date = uberDate($text, $index);
	$time = uberTime($text, $index);

	if (!$date) return false;

	return $date.($time?', '.$time:'');
}

function uberTime($text = false, $index = 1)
{
	if (is_object($text) and get_class($text) == 'DOMElement'){
		$text = text($text);
	}

	return trim(ure("#(\d+\s*:\s*\d+(?:\s*(?:AM|PM)*))#ims", $text, $index));
}

function uberName($name, $format = "pure", $text = false, $index = 1)
{
	global $hInstance;
	if (is_numeric($text)){
		$index = $text;
		$text = $hInstance->targetSource;
	}
	elseif (func_num_args() == 0 || $text === false){
		$index = 1;
		$text = $hInstance->targetSource;
	}

	if ($format == "pure")
		return ure("#\n\s*\b$name\b[\s:\-]*([^\n]+)#ms", $text, $index);

	elseif ($format == "airName")
		return ure("#\n\s*\b$name\b[\s:\-]*([^\n\(]+)#ms", $text, $index);
}

function uberDate($text = false, $index = 1)
{
	global $hInstance;
	global $dateRegExps;

	if (is_numeric($text)){
		$index = $text;
		$text = $hInstance->targetSource;
	}
	elseif (func_num_args() == 0 || $text === false){
		$index = 1;
		$text = $hInstance->targetSource;
	}
	if (is_object($text) and get_class($text) == 'DOMElement'){
		$text = text($text);
	}

	$text = preg_replace("#\d+:\d+#", '', $text);

	$res = [];
	foreach($dateRegExps as $reFormat){
		$re = "#({$reFormat})#uims";
		#print $re."\n";
    preg_replace_callback($re, function($m) use(&$res,$reFormat){
    	if (preg_match("#^[\d.,\-\s/]+$#u", $m[1]) /*&& strtotime($m[1])*/){ // for digit dates
    		$res[] = $m[1];
    		#print $m[1]." $reFormat\n";
    	}
    	elseif (hasMonth($m[1])){
	    	$res[] = $m[1];
	    	#print $m[1]." $reFormat\n";
	    }
    }, $text);
	}

	$value = isset($res[$index-1])?trim($res[$index-1]):null;
	//if ($value) $value = date('Y-m-d', strtotime($value)); // make date standard

	return $value;
}

function totime($dateStr, $anchor = null) // usualy "anchor" is a "reservation date"
{/*
	if (preg_match("#^[,\s]*\d+:\d+#", $dateStr) ||
			preg_match("#^[,:\s]*^#", $dateStr)){ // date is absent
		return false;
	}*/
	$dateStr = trim($dateStr);

	if (!$dateStr || $dateStr == ',') return false;

	$date = strtotime($dateStr);
	if (!$date) return false;

	// correct if year doesn't exist
	$yDate = strtotime($dateStr,1);
	$noYear = ($yDate < 24 * 3600 * 365)?true:false;

 	if ($noYear){
 		if ($anchor){
 			$anchor = isUnixtime($anchor)?$anchor:strtotime($anchor);
 			if (!$anchor) return false;

	    // compare date to be greater than anchor
			if ($yDate < $anchor){
	    	$years = date('Y', $anchor) - date('Y', $yDate);
				$yDate = strtotime("+$years year", $yDate);

				// still lower? ok, add 1 year
				if ($yDate < $anchor)
					$yDate = strtotime("+1 year", $yDate);

				$date = $yDate;
	    }
 		}

 		if (!$anchor){
   		if ($date < strtotime('-6 month')){
   			$date = strtotime('+1 year', $date);
   		}
		}
 	}

	return $date;
}

function filter($array, $callback = null){
	if ($callback === null){
		$callback = function($item){
			return ($item === '' || $item === null)?false:true;
		};
	}
	return array_filter($array, $callback);
}

function cell($contains, $offsetX = 0, $offsetY = 0, $moreXpath = "", $parent = -1)
{
	global $hInstance;

	$xoffset = "";
	$yoffset = "";

	if ($parent === -1){
		$parent = is_object($hInstance->targetSource)?$hInstance->targetSource:null;
	}

	if (is_array($contains)) {
		$conditions = [];
		foreach ($contains as $c) {
			$conditions[] = "contains(normalize-space(text()), \"$c\")";
		}
		$searchCondition = implode(' or ', $conditions);
	} else {
		$searchCondition = "contains(normalize-space(text()), \"$contains\")";
	}
	$base = ($parent === null?'':'.')."//*[$searchCondition]/ancestor-or-self::*[self::td or self::th][1]";

	if ($offsetX){
		if ($offsetX < 0){
			$xoffset = "/preceding-sibling::*[self::td or self::th][".abs($offsetX)."]";
		}
		elseif ($offsetX > 0){
			$xoffset = "/following-sibling::*[self::td or self::th][".abs($offsetX)."]";
		}
	}

	if ($offsetY){

		if ($offsetY < 0){
			$yoffset = "/ancestor-or-self::tr[1]/preceding-sibling::tr[".abs($offsetY)."]";
		} else {
			$yoffset = "/ancestor-or-self::tr[1]/following-sibling::tr[".abs($offsetY)."]";
		}

		$yoffset .= "/td[position() = count({$base}{$xoffset}/preceding-sibling::*[self::td or self::th][1]) + 1]";
		$xoffset = "";
	}

	$r = nodes("{$base}{$yoffset}{$xoffset}{$moreXpath}", $parent);
	if (!$r) return null;

	if (count($r) == 1)
		return reset($r);
	else
		return implode("\n", $r);

	return $r;
}

function nodeTarget($nodeContext) // for nodes only
{
	global $hInstance;

	if ($nodeContext == -1){ // default
		$nodeContext = $hInstance->targetSource;
	}

	if (is_string($nodeContext)){
		return null;
	}

	return is_object($nodeContext)?$nodeContext:null;
}

function html($nodes, $from = -1)
{
	global $hInstance;
	if ($nodes === null)
		return "";

	if (is_string($nodes))
	{
		$nodes = xpath($nodes, nodeTarget($from));
	}

	if (get_class($nodes) == 'DOMElement'){
		$nodes = [$nodes];
	}

	$r = "";
	foreach($nodes as $node){
		$r .= $node->ownerDocument->saveXML($node);
	}

	return $r;
}

// remove Passengers, Currency, TotalCharge, Total, Tax, Taxes, TotalTaxAmount, GuestNames, RenterName
// If used not in email parsers web IDE ("adminka") - it is better to use ItineraryTools::correctItinerary instead of this function
function correctItinerary($it, $uniteSegments = false, $uniteFlights = false)
{
	$w = new ToolsTraitWrapper();
	return $w->correctItinerary($it, $uniteSegments, $uniteFlights);
}

// If used not in email parsers web IDE ("adminka") - it is better to use ItineraryTools::uniteAirSegments instead of this function
function uniteAirSegments($it)
{
	$w = new ToolsTraitWrapper();
	return $w->uniteAirSegments($it);
}

// If used not in email parsers web IDE ("adminka") - it is better to use ItineraryTools::uniteFlights instead of this function
function uniteFlights($it)
{
	$w = new ToolsTraitWrapper();
	return $w->uniteFlights($it);
}

function isUnixtime($timestamp) // helper
{
  return preg_match("#[^\d]#", $timestamp)?false:true;
}

function correctDate($date, $anchorDate)
{
	$uanchor = isUnixtime($anchorDate)?$anchorDate:totime($anchorDate);
	$date = isUnixtime($date)?$date:totime($date, $uanchor);

  // compare dates to be greater than anchor
	if ($date < $uanchor){
  	$years = date('Y', $uanchor) - date('Y', $date);
		$date = strtotime("+$years years", $date);

		// still lower? ok, add 1 year
		if ($date < $uanchor)
			$date = strtotime("+1 year", $date);
  }

	return $date;
}

function correctDates(&$from, &$to, $anchorDate = null)
{
	if ($anchorDate){
		$uanchor = isUnixtime($anchorDate)?$anchorDate:totime($anchorDate);
	} else {
		$uanchor = 0;
	}

	// first, move all dates to unix
	$ufrom = isUnixtime($from)?$from:totime($from, $uanchor);
	$uto = isUnixtime($to)?$to:totime($to, $uanchor);

	// return if dates are invalid
	if (!$ufrom || !$uto) return false;

  // compare dates to be greater than anchor
	if ($ufrom < $uanchor){
  	$years = date('Y', $uanchor) - date('Y', $ufrom);
		$ufrom = strtotime("+$years years", $ufrom);
		$uto = strtotime("+$years years", $uto);

		// still lower? ok, add 1 year
		if ($ufrom < $uanchor){
			$ufrom = strtotime("+1 year", $ufrom);
			$uto = strtotime("+1 year", $uto);
		}
  }

  // finally compare dates
  if ($ufrom > $uto){
		$uto = strtotime("+1 day", $uto);

		// still invalid?
		if ($ufrom > $uto){
			// something gone wrong
			$from = $to = false;
			return false;
		}
  }

  // store values
	$from = $ufrom;
	$to = $uto;

	return true;
}

function pdfHtmlGetStyles($html)
{
	$r = [];

	re("#<STYLE[^\>]+>(.*?)<\/STYLE>#ims", function($m) use(&$r){
		$all = $m[1];

		re("#\.(\w+\d+)\s*\{([^\}]+)\}#ms", function($m) use (&$r){
			$name = $m[1];
			$style = $m[2];
			$hash = [];

			re("#([^:]+):([^;]+);#", function($m) use(&$hash){
				$hash[$m[1]] = $m[2];
			}, $style);

			$r[$name] = $hash;
		}, $all);

	}, $html);

	return $r;
}

function pdfHtmlHtmlTable($html)
{
	$css = pdfHtmlGetStyles($html);

	$r = [];
	$page = 0;
	re("#<BODY[^>]+>(.*?)</BODY>#ims", function($m) use(&$r, &$page){

		re("#<p\s+style=\"position:absolute;top:(\d+)px;left:(\d+)px;white\-space:nowrap\"\s+class=\"([^\"]+)\">(.*?)</p>#ims", function($m) use(&$r,&$page){
			$r[] = ['x' => $m[2], 'y' => $m[1] + $page*1200, 'class' => $m[3], 'html' => $m[4]];
		}, $m[1]);

		$page++;
	}, $html);

	$cw = 15;
	$ch = 15;

	$marker = "_\f\v_";
	$max = 0;


	foreach($r as &$b){
		$text = trim(text($b['html']));

		// find bunch size
		$rows = explode("\n", $text);
		$imax = 0;
		foreach($rows as $row){
			$row = trim($row);
			if (strlen($row) > $imax){
				$imax = strlen($row);
			}
		}
		// set font sizes
		$c = $css[$b['class']];
		if (!isset($c['line-height'])) $c['line-height'] = 0;
		if (!isset($c['font-size'])) $c['font-size'] = 12;

		$fontSize = intval($c['font-size'])*0.5;
		$lineHeight = orval(intval($c['line-height']), $fontSize);

		// calculate cell positions
		$width = $imax * $fontSize;
		$height = count($rows) * $lineHeight;

		$x1 = floor($b['x'] / $cw);
		$y1 = floor($b['y'] / $ch);
		$x2 = ceil($width / $cw);
		$y2 = ceil($height / $ch);
		if ($x2 < 0) $x2 = 0;
		if ($y2 < 0) $y2 = 0;

		$x2+=$x1;
		$y2+=$y1;

		#print $text.": $imax*$fontSize = $width/$cw = ".(ceil($width / $cw))."<BR>";

		$b['cell'] = ['x1' => $x1, 'x2' => $x2, 'y1' => $y1, 'y2' => $y2];

		if (isset($field[$y1][$x1]) && $field[$y1][$x1] !== $marker){
			if ($field[$y1][$x1]['html'] != $b['html'])
				$field[$y1][$x1]['html'] .= "<BR>".$b['html'];
		} else {
			$field[$y1][$x1] = $b;
		}

		for ($y=$y1; $y<=$y1; $y++){ #!!! 1 row only
			if (!isset($field[$y])) $field[$y] = [];
			if ($max < $x2) $max = $x2;
			for ($x=$x1; $x<=$x2; $x++){
				if ($x == $x1 && $y == $y1){
					$field[$y][$x]['colSpan'] = $x2-$x1+1;
					$field[$y][$x]['rowSpan'] = 1;#$y2-$y1+1;
				}
			}
		}
	}
	if (!isset($field))
		return;

	ksort($field);

	$out = "<table border=1 borderColor=#dddddd style=\"border-collapse:collapse;\" cellspacing=0 cellpadding=0>";
	// build html
	foreach($field as $y => &$rows){
		ksort($rows);

		$out .= "<tr>";
		$prevx = 0;
		foreach($rows as $x => $cell)
		{
			if ($cell === $marker) continue;

			#$len = 1;while(isset($rows[$x+$len]) && ($rows[$x+$len]===$marker)) $len++;
			#$normal = $len;

			#$h = 1;while(isset($field[$y+$h][$x]) && ($field[$y+$h][$x]===$marker)) $h++;
			   /*
			// long last cells
			for ($i=$len+$x+1; $i<$max; $i++){
				if (isset($rows[$i]) && $rows[$i]!==$marker){
					$len = $normal;
					break;
				} else {
					#$len=$max;
				}
			}   */

			// fill void left cells
			for ($i=$x-1; $i>=$prevx; $i--){
				if ((isset($rows[$i]) && !isset($rows[$i]['data']))/*|| !isset($rows[$i])*/) break;
				$out .= "<td>&nbsp;</td>";
			}
			$prevx = $x;

			// prepare styles
			$styles = [];
			$c = $css[$cell['class']];
			foreach($c as $key => $value){
				$styles[] = "$key:{$c[$key]}";
			}
			$styles = implode(';', $styles);
			$text = $cell['html'];

			$out .= "<td colspan=\"{$cell['colSpan']}\" rowspan=\"{$cell['rowSpan']}\" style=\"$styles;white-space:nowrap;\">$text</td>";

		}
		$out .= "</tr>";
	}

	$out .= "</table>";
	return $out;
}

function copyArrayValues(&$arrDst, $arrSrc, $keys) {
	$arrDst = array_merge($arrDst, array_intersect_key($arrSrc, array_flip($keys)));
	return $arrDst;
}

function cutByRange($src, $range) {
	$start = 0;
	$length = $range;

	if (strpos($range, '/') !== false) {
		$size = strlen($src);

		list($left, $right) = explode('/', $range);
		if ($left) { // 1/3
			$start = $size * ($left / $right);
			$length = $size / $right;
		} else { // /4 (from end)
			$start = $size * (1 - 1 / $right);
			$length = $size / $right;
		}
	}

	if (strpos($range, '-') !== false) {
		list($start, $end) = explode('-', $range);
		$size = strlen($src);
		if ($start) { // 1000-2000
			$length = $end - $start;
		} else { // -1000
			$start = $size - $end;
			$length = $end;
		}
	}

	return substr($src, $start, $length);
}

function quick_match($pattern, $text, &$match = null)
{
	global $hInstance;

	// if array
	if (is_array($pattern)){
		foreach($pattern as $ptr){
			$range = (isset($ptr[2]) && $ptr[2])?$ptr[2]:1000;
			$re = (isset($ptr[0]) && $ptr[0])?$ptr[0]:null;

			if ($re && quick_match($re, cutByRange($text, $range), $match)){
				return 1;
			}
		}
		return 0;
	}

	$nonCase = false;

	if (@preg_match("#^[^\s\w\\\]#", $pattern, $m)){
		if ($m[0] == '#') $m[0] = '\#';

		if (@preg_match("#^{$m[0]}(.*?){$m[0]}([imsxe]*)$#ims", $pattern, $m)){
			$nonCase = (stripos($m[2], 'i') !== false);

			// ok, it's regexp
			// check if we can make the same without preg:
			$inner = $m[1];

			// seems preg_match only usable
			if (@preg_match("#[^\d\w\s:;,><]#ms", $inner)){
				return @preg_match($pattern, $text, $match);
				#$store = isset($hInstance->lastRe)?$hInstance->lastRe:null;
				#$result = re($pattern, $text);
				#$hInstance->lastRe = $store;
        #return $result;
			}

			$pattern = $inner;
		}
	}

	$opt = strtolower(substr($pattern, -2));
	if (in_array($opt, ['/i',"'i",'#i'])){
		$nonCase = true;
		$pattern = substr($pattern, 0, strlen($pattern)-2);
	}

	// not regexp, or it's invalid regexp, use stripos
	$res = $nonCase?stripos($text, $pattern):strpos($text, $pattern);
	if ($res !== false){
		$match = array($pattern);
		return 1;
	}

	return 0;
}

function isRegex($subj)
{
	if (preg_match("#^[^\s\w\\\]#", $subj, $m)){
		if ($m[0] == '#') $m[0] = '\#';
		return preg_match("#({$m[0]})[imsx]*$#i", $subj);
	}
	return false;
}

function cacheParserEmail(&$parser)
{
	$altCount = $parser->countAlternatives();

	$parser->cacheGeneral = (object) array();

	$parser->cacheGeneral->body = $parser->getHTMLBody();
	$parser->cacheGeneral->text = text($parser->cacheGeneral->body);
	$parser->cacheGeneral->plain = $parser->getPlainBody();
	$parser->cacheGeneral->textFromPlain = text($parser->cacheGeneral->plain);

	$parser->cachedContext = array();

	for($i=0; $i<$parser->countAttachments()+$altCount; $i++)
	{
		$p = (object) array();

		$info = $parser->getAttachmentHeader($i, 'content-type');
		if (preg_match('#name="?(.*)"?#', $info, $m))
			$name = $m[1];
		else
			$name = '';

		$p->info = $info;
		$p->name = $name;

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$p->body = $body = $parser->getAttachmentBody($i);
		$p->curtype = $curtype = finfo_buffer($finfo, $body);
		$p->text = text($body);

		if (preg_match("#^application/pdf#i", $info, $m)) {
			$p->pdf = (object) array();

			$p->pdf->html = \PDF::convertToHtml($body, \PDF::MODE_SIMPLE);
			//$p->pdf->simpletable = pdfHtmlHtmlTable(\PDF::convertToHtml($body, \PDF::MODE_COMPLEX));
			$p->pdf->complex = \PDF::convertToHtml($body, \PDF::MODE_COMPLEX);
			$p->pdf->text = text(\PDF::convertToHtml($body, \PDF::MODE_SIMPLE));
		}
		elseif (preg_match("#^text/rtf#i", $info)){
			$p->rtf->html = $parser->rtf2text($body);
			$p->rtf->text = text($parser->rtf2text($body));
		}
		elseif (preg_match("#^text/#i", $info)){
			$p->html = (object) array();

			$p->html->text = text($body);
			$p->html->body = $body;
		}

		finfo_close($finfo);

		$parser->cachedContext[] = $p;
	}
}

// If used not in email parsers web IDE ("adminka") - it is better to use PriceTools::total instead of this function
function total($costAndCurrency, $totalLabel = 'TotalCharge')
{
	$w = new ToolsTraitWrapper();
	return $w->total($costAndCurrency, $totalLabel);
}

function uberAir($text = false)
{
	global $hInstance;
	if (func_num_args() < 1){
		$text = $hInstance->targetSource;
		if (is_object($text) and get_class($text) == 'DOMElement'){
			$text = text($text);
		}
	}

	$res = preg_match("#([A-Z\d]{2})\s*(\d+)#", $text, $m);
	return $res ? array('AirlineName' => $m[1], 'FlightNumber' => $m[2]) : null;
}

function uberFlight($text = false, $index = 1)
{
	global $airlineCodes;
	return ure("#\b(?:$airlineCodes)\s*(\d+)#ms", $text, $index);
}

function uberAirline($text = false, $index = 1)
{
	global $airlineCodes;
	return ure("#\b($airlineCodes)\s*\d+#ms", $text, $index);
}

function uberAirCode($text = false, $index = 1)
{
	return ure("#\b([A-Z]+)\b#", $text, $index);
}

function finfo_buffer_safe($finfo, $body)
{
	// fix bug for alitalia/it-1837826.eml, finfo_buffer kills application
	if (preg_match("#<(div|br|table|td)\s+#i", substr($body,0,500))){
		$type = "text/html";
	} else {
		$type = finfo_buffer($finfo, $body);
	}

	return $type;
}

function niceName($name)
{
	return nice(beautifulName($name));
}

class SupportedEmailsListManager
{
	public $checkerPath;

	public $checkerFilename;

	public $checkerClassName;

	public $checkerFileContent;

	public static $supportedEmailsList = [];

	public $pathWithClassNameParser;
	
	public $parserObject;

	public function __construct($checkerPath)
	{
		$this->checkerFileContent = file_get_contents($checkerPath);
		$this->checkerPath = $checkerPath;
		$this->checkerFilename = basename($checkerPath);
		$regex = '#class\s+(\w+)\s+extends\s+.+?\s*{#ms';
		$reNmsp = '#namespace\s+(.+)\;#i';
		$textNmsp = $this->cutText('namespace', 'class ', $this->checkerFileContent);
		if (preg_match($regex, $this->checkerFileContent, $m) && preg_match($reNmsp, $textNmsp, $math))
		{
			$this->pathWithClassNameParser = $math[1].'\\'.$m[1];
			$this->checkerClassName = $m[1];
		}
		$this->loadSupportedEmailsList();
	}

	private function cutText($start, $end, $text){
		$input = stristr(stristr($text, $start), $end, true);
		return $input;
	}

	public function isEmailSupported($emailPath)
	{
		return in_array($this->shortNameOfEmail($emailPath), self::$supportedEmailsList[$this->checkerFilename]);
	}

	public function removeEmailFromSupportedList($emailPath)
	{
		if ($this->isEmailSupported($emailPath)) {
			if (($key = array_search($this->shortNameOfEmail($emailPath), self::$supportedEmailsList[$this->checkerFilename] )) !== false){
				unset(self::$supportedEmailsList[$this->checkerFilename][$key]);
				$this->saveSupportedEMailsList();
			}
		}
	}

	public function addEmailToSupportedList($emailPath)
	{
		if (!$this->isEmailSupported($emailPath)) {
			self::$supportedEmailsList[$this->checkerFilename][] = $this->shortNameOfEmail($emailPath);
			sort(self::$supportedEmailsList[$this->checkerFilename]);
			$this->saveSupportedEMailsList();
		}
	}

	public function shortNameOfEmail($emailPath){
		if (preg_match('#/statements/[^/]+#i', $emailPath))
			$sn = basename(dirname(dirname($emailPath))).'/'.basename(dirname($emailPath)).'/'.basename($emailPath);
		else
			$sn = basename(dirname($emailPath)).'/'.basename($emailPath);
		return $sn;
	}

	private function loadSupportedEmailsList(){
		
		if(!isset(self::$supportedEmailsList[$this->checkerFilename]) && !empty($this->pathWithClassNameParser)){
			$checker = new $this->pathWithClassNameParser;
			self::$supportedEmailsList[$this->checkerFilename] = isset($checker->mailFiles) ? array_filter(array_map('trim', explode(",", $checker->mailFiles))) : [];
		}elseif(!isset(self::$supportedEmailsList[$this->checkerFilename]) && empty($this->pathWithClassNameParser)){
			self::$supportedEmailsList[$this->checkerFilename] = [];
		}
	}

	private function saveSupportedEMailsList(){
		if (strpos($this->checkerFileContent, '$mailFiles')===false) {
			$regex = '#(class\s+\w+\s+extends\s+[^{]*?{)#ms';
			$this->checkerFileContent = preg_replace($regex, '${1}'."\n\t".'public $mailFiles = "'.implode(', ', self::$supportedEmailsList[$this->checkerFilename]).'";', $this->checkerFileContent);
			// file_put_contents($this->checkerPath, $this->checkerFileContent);
		}else{
			$this->checkerFileContent = preg_replace('#(var|public|private|protected)\s+\$mailFiles[^;]+;#ms', 'public $mailFiles = "'.implode(', ', self::$supportedEmailsList[$this->checkerFilename]).'";', $this->checkerFileContent);
		}
		file_put_contents($this->checkerPath, $this->checkerFileContent);
	}
}

// any quoting here is on you.
function whiten($s) {
	return preg_replace('/\s+/', '\s*', $s);
}
function white($s) {
	return whiten($s);
}

function re_white($q, $text=false, $index=1, $flags='isu') {
	if (is_numeric($q) && $text == false)
		return re($q);

	$q = whiten($q);
	// var_dump(sprintf('$q = \'%s\', $text = \'%s\'', $q, $text));
	return re("#$q#$flags", $text, $index);
}

function rew($q, $text=false, $index=1, $flags='isu') {
	return re_white($q, $text, $index, $flags);
}

// `re_white` + `nice`
function reni($q, $text=false, $index=1, $flags='isu') {
	return nice(re_white($q, $text, $index, $flags));
}

// literal between, no regexps
// 's+' instead of '\s*' because we want to be more precise here.
function between($pref, $suf, $text=false) {
	$pref = whiten( preg_quote($pref, '#') ); // in that order
	$suf = whiten( preg_quote($suf, '#') );
	if (isset($pref) && strlen($pref) > 0 && isset($suf) && strlen($suf) > 0)
		return nice(re("#$pref\s+(.+?)\s+$suf#isu", $text));
	else
		return null;
}

// nice + uberDateTime
function uber_dt_nice($s=false, $index=1) {
	$s = nice($s);
	return uberDateTime($s, $index);
}

// like `dict.get` in python
function get_or($arr, $k, $d=null) {
	if (isset($arr[$k]))
		return $arr[$k];
	return $d;
}

function timestamp_from_format($s, $fmt=null) {
	if (!$fmt)
		return strtotime($s);
	$dt = DateTime::createFromFormat($fmt, $s);
	return $dt ? $dt->getTimestamp() : null;
}

// get dictionary with only named groups from regex
// each group either has to be non-captured group or named group
// (no groups without names)
function re2dict($pat, $text, $flags='isu') {
	if (!preg_match("/$pat/$flags", $text, $ms)) {
		return [];
	}
	$res = $ms;

	// remove indices-numbers
	$n = sizeof($ms);
	for ($i = 0; $i < $n/2; $i++)
		unset($res[$i]);
	return nice($res);
}

// minimal carry if needed
function date_carry($time, $anchor) {
	$dt = strtotime($time, $anchor);
	if ($dt > $anchor)
		return $dt;

	if (strpos($time, ':')) {
		$dt = strtotime('+1 day', $dt);
	} else {
		$dt = strtotime('+1 year', $dt);
	}
	return $dt > $anchor ? $dt : Null;
}

function uni($exp, $text = null)
{
	if (preg_match("#^\.*?/#", $exp)){ // xpath
		return xpath($exp, $text);
	} else {
		return quick_match($exp, $text);
	}
}

function line($needle, $exp = '[^\n]+')
{
	return re("#\n\s*{$needle}[\s:]+({$exp})#ix");
}
