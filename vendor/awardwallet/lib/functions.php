<?
// -----------------------------------------------------------------------
// Functions
//		contains often used functions
//		included to every page
//		please move rare-used functions to other file
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------
// -----------------------------------------------------------------------
// glues assotiative array to string like: name1=value1&name2=value2...

$Terminating = false;

function ImplodeAssoc( $sPairGlue, $sSeparator, $ar, $bUrlEncode = False )
{
	$sResult = "";
	foreach( $ar as $sKey => $sValue )
	{
		if( is_array( $sValue ) )
		{
			if( $bUrlEncode )
				$sKey = urlencode( $sKey . "[]" );
			foreach ( $sValue as $s )
			{
				if( $sResult != "" )
					$sResult .= $sSeparator;
				if( $bUrlEncode )
					$s = urlencode( $s );
				$sResult .= $sKey . $sPairGlue . $s;
			}
		}
		else
		{
			if( $bUrlEncode )
				$sKey = urlencode( $sKey );
			if( $sResult != "" )
	 			$sResult .= $sSeparator;
			if( $bUrlEncode )
				$sValue = urlencode( $sValue );
			$sResult .= $sKey . $sPairGlue . $sValue;
		}
	}
	return $sResult;
}

// -----------------------------------------------------------------------
// load array from text file like this:
//		mid=melody
//		midi=melody
// $bUnpackNames = true means that parameters like "mid,midi=melody" will
//		be unpacked to "mid=melody" and "midi=melody"
function LoadArray( $sFileName, $bUnpackNames )
{
	$nFile = fopen( $sFileName, "r" );
	$ar = array();
	if( $nFile === false )
		DieTrace( "error opening $sFileName" );
	$nLine = 1;
	while( !feof( $nFile ) )
	{
		$s = trim( fgets( $nFile, 1024 ) );
		if( strpos( $s, "//" ) === 0 )
			continue;
		if( $s != "" )
			$arLine = explode( "=", $s );
		if( count( $arLine ) != 2 )
			DieTrace( "error in $sFileName, line: $nLine, line must be in format Name=Value" );
		$ar[ trim( $arLine[ 0 ] ) ] = trim( $arLine[ 1 ] );
	}
	fclose( $nFile );
	if( $bUnpackNames )
	{
		$arUnpacked = array();
		foreach( $ar as $sNameList => $sValue )
			foreach( explode( ",", $sNameList ) as $sName )
				$arUnpacked[ $sName ] = $sValue;
		$ar = $arUnpacked;
	}
	return( $ar );
}

// -----------------------------------------------------------------------
// returns file extension, without ".". if file has no extension -
//		returns empty string
function FileExtension( $sFile )
{
	$arMatches = array();
	if( preg_match( "/.*\.(\w+)/i", $sFile, $arMatches ) )
		return( $arMatches[ 1 ] );
	else
		return "";
}

// -----------------------------------------------------------------------
// return file name, without extension
function FileName( $sFile )
{
	$arMatches = array();
	if( preg_match( "/(.*)\.\w+/i", $sFile, $arMatches ) )
		return( $arMatches[ 1 ] );
	else
		return $sFile;
}

// -----------------------------------------------------------------------
// create directories, recursive
function MkDirs($sPath, $mode = 0777 ){
	if( is_dir( $sPath ) )
		return true;
	$sParentPath = dirname( $sPath );
	if( !MkDirs( $sParentPath, $mode ) )
		return false;
	$result = @mkdir( $sPath, $mode );
	if($result)
		chmod($sPath, $mode);
	return is_dir( $sPath );
}

// ----------------------------------------------------------------------
// convert time to array
// $arTime receives array("h" => .., "m" => .., "s" => "..")
// return true on success
function ParseTime( $sTime, &$arTime )
{
	if( preg_match("/^([0-2]?\d)\s*[:.]?\s*([0-5]?\d)(?:\s*[:.]\s*([0-5]?\d))?\s*([ap](?:m|\.\s*m\.))?$/i", $sTime, $ar ) ){
		$arTime=array("h" => intval($ar[1]),"m"=>intval($ar[2]),"s"=>0);
        if(!empty($ar[4])){
            $ampm=str_replace(array('.',' '),'',$ar[4]);
            if(strcasecmp($ampm,"pm")===0){
                if($arTime["h"]!=12){
                    if($arTime["h"]>12){
                        return false;
                    }else{
                        $arTime["h"] += 12;
                    }
                }
            }elseif($arTime['h']>11){
                if(($arTime['h']==12)){
                    $arTime['h']=0;
                }else{
                    return false;
                }
            }
        }
        if($arTime['h']>23)
            if(($arTime['h']!=24) || ($arTime['m']!=0))
                return false;
		if(!empty($ar[3]))
			$arTime["s"] = intval($ar[3]);
		return true;
	}
	return false;
}
// -----------------------------------------------------------------------
// convert string mm/dd/yyyy to unix date
// return false on error
function StrToDate( $s, $bIncludeTime = False )
{
	global $Config;
	$bValidTime = False;
	try {
        $arTime=array('h'=>0,'m'=>0,'s'=>0);
        if($bIncludeTime){
            $arDateTime=explode(' ',$s,2);
            $sDate=$arDateTime[0];
            if(!empty($arDateTime[1])){
                $bValidTime=ParseTime($arDateTime[1],$arTime);
                if(!$bValidTime){
                    throw new Exception('Invalid time: '.$arDateTime[1]);
                }
            }
        } else{
            $sDate=$s;
        }
		if(isset($Config['RussianSite'])){
            if( !preg_match( "/([0-9]?[0-9])\.([0-9]?[0-9])\.([0-9]{4})/ims", $sDate, $ar ) ){
                if($bIncludeTime && !$bValidTime)
                    throw new Exception('RussianSite.InvalidDateTime');
                else
                    throw new Exception('RussianSite.InvalidDate');
            }
			$tmp = $ar[2];
			$ar[2] = $ar[1];
			$ar[1] = $tmp;
		}
		else{
            if( !preg_match( "/([0-9]?[0-9])\/([0-9]?[0-9])\/([0-9]{4})/ims", $sDate, $ar ) ){
                if($bIncludeTime && !$bValidTime)
                    throw new Exception('EnSite.InvalidDateTime');
                else
					throw new Exception('EnSite.InvalidDate');
			}
			if(DATE_FORMAT == 'd/m/Y'){
				$tmp = $ar[1];
				$ar[1] = $ar[2];
				$ar[2] = $tmp;
			}
		}
        if( ( $ar[1] > 12 ) || ( $ar[1] < 1 ) )
            throw new Exception('Invalid month: '.$ar[1]);
        if( ( $ar[2] > date( "d", mktime( 0, 0, 0, $ar[1] + 1, 0, $ar[3] ) ) ) || ( $ar[2] < 1 ) )
            throw new Exception('Invalid day: '.$ar[2]);
        $ar[5] = $arTime['h'];
        $ar[6] = $arTime['m'];
        $ar[8] = $arTime['s'];
        $result = mktime( $ar[5], $ar[6], $ar[8], $ar[1], $ar[2], $ar[3] );
        if ($result === false)
            throw new Exception('mktime error: '.implode(", ", array($ar[5], $ar[6], $ar[8], $ar[1], $ar[2], $ar[3])));
        return $result;
	} catch (Exception $e) {
		$_SESSION['StrToDateError'] =$e->getMessage().'['.$e->getLine().']. Source: '.$s;
		return false;
	}
}

function convertDateByFormatToUnix($val, $time = false)
{
    $val = trim($val);

    $date = DateTime::createFromFormat('m/d/Y h:ia', $val);
    if (false !== $date)
        return date_format($date, 'U');

    $date = DateTime::createFromFormat(getDateTimeFormat($time)['full'], $val);
    if (false !== $date)
        return date_format($date, 'U');

    return StrToDate($val, $time);
}

function getDateTimeFormat($time = false)
{
    $dateFormat = DATE_FORMAT;
    $timeFormat = TIME_FORMAT;

    if (isset($_SESSION['UserFields']['Region'])) {
        $formatByCode = [
            'd.m.y'    => ['az', 'ua', 'tr', 'rs', 'al', 'si', 'sk', 'ru', 'ro', 'pl', 'no', 'mk', 'lv', 'lu', 'kg', 'kz', 'is', 'am', 'ee', 'de', 'cz', 'ba', 'bg', 'by'],
            'd/m/y'    => ['kw', 'vn', 'th', 'br', 'pt', 'my', 'it', 'id', 'in', 'fr', 'gf', 'pf', 'es', 'nz', 'gb', 'au', 'gr'],
            'd-m-y'    => ['be', 'ge', 'fo', 'dk'],
            'y-m-d'    => ['cn', 'se', 'lt'],
            'y/m/d'    => ['jp'],
            'Y. m. d.' => ['kr'],
            'Y.m.d.'   => ['hu'],
            'd.m.Y.'   => ['hr'],
            'd.m.Y'    => ['fi'],
        ];
        $region = strtolower($_SESSION['UserFields']['Region']);
        foreach ($formatByCode as $format => $countries) {
            if (in_array($region, $countries)) {
                $dateFormat = $format;
                break;
            }
        }
        in_array($_SESSION['UserFields']['Region'], ['us', 'au', 'al', 'af', 'gr', 'in', 'kp', 'cn', 'nz', 'kr']) ?: $timeFormat = 'H:i';
    }

    $full = trim($dateFormat . ($time ? ' ' . $timeFormat : ''));
    return ['date' => $dateFormat, 'time' => ($time ? $timeFormat : ''), 'full' => $full];
}

// -----------------------------------------------------------------------
// redirect using script. because when redirecting using headers, url in browser
// not always changed
function ScriptRedirect($sURL, $beforeScript = null, $noObClean = false, $body = null){
	if(preg_match("#^javascript#ims", $sURL))
		$sURL = "/";
	if(!$noObClean){
		ob_clean();
		header("Content-Type: text/html; charset=UTF-8");
	}
	if(empty($body)){
			$body = "<div style='margin-left: auto; margin-right: auto; width: 66px; margin-top: 80px;'>
						<img style='width: 66px; height: 66px; border: none;' src='/lib/images/progressBig.gif'>
					</div>";
	}

	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
	<html>
	<head>
		<meta http-equiv='refresh' content='3;url=".htmlspecialchars($sURL)."'>
		<title>Please wait..</title>";

	echo "<script type='text/javascript'>
        if (!/switchTo|loginMobile/.test(document.location.href))
            sessionStorage.setItem('backUrl', document.location.href);

		function execRedirect(){
			{$beforeScript};
			if(window.parent != window)
				parent.location.href = " .json_encode($sURL).";
			else
				location.replace(".json_encode($sURL).");
		}
		</script>
	</head>
	<body onload='execRedirect();'>
	$body
	</body></html>";
	exit();
}

// -----------------------------------------------------------------------
// redirect with POST using script
function PostRedirect( $sURL, $arParams )
{
	ob_clean();
	echo "<html><body><form method=post action='".htmlspecialchars($sURL)."'>\n";
	foreach ( $arParams as $sName => $sValue )
		echo "<input type=hidden name=$sName value=\"" . htmlspecialchars( $sValue ) . "\">\n";
	echo "</form><script>document.forms[0].submit()</script></body></html>";
	exit();
}

function RedirectToAuth($noObClean = false){
	if(strtolower(ArrayVal($_SERVER, 'HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest'){
        if(!$noObClean)
            ob_clean();
		header('Ajax-Error: unauthorized', true, 500);
		echo("unauthorized");
		exit();
	}
	else{
		$param = '';
		if (isset($_GET['ref']))
			$param .= 'ref='.intval($_GET['ref']).'&';

		$beforeScript = "
		try{
			if(window.parent != window){
				parent.location.href = '/security/unauthorized.php?BackTo=' + encodeURI(parent.location.href);
				return;
			}
		}
		catch(e){};

		var targetUrl = '/security/unauthorized.php?{$param}BackTo=' + encodeURIComponent(document.location.href);

		if(window.parent != window)
			parent.location.href = targetUrl;
		else
			location.replace(targetUrl);

		return;
		";
		ScriptRedirect("/security/unauthorized.php?{$param}BackTo=" . urlencode( $_SERVER['REQUEST_URI'] ), $beforeScript, $noObClean);
	}
}

// -----------------------------------------------------------------------
// authorize user, using session. check that user exists in database
function AuthorizeUser( $bForce = true ){
	global $sPath, $Interface, $bNoSession, $USER_SESSION_CHECKED;
	if(!isset( $_SESSION["UserID"] ) && empty($bNoSession)){
		if( $bForce ){
			if(isset($Interface))
				$Interface->RequireUserAuth();
			else
				RedirectToAuth();
		}
	}
	if( isset( $_SESSION['UserID'] ) ){
		if(!isset($USER_SESSION_CHECKED)){ // do not check twice within one script
			$q = new TQuery( "select * from Usr where UserID = " . intval( $_SESSION["UserID"] ) );
			if( $q->EOF
			|| (isset($_SESSION['UserFields']['Pass']) && $_SESSION['UserFields']['Pass'] != $q->Fields['Pass'])){
				session_unset();
				RedirectToAuth();
			}
			$USER_SESSION_CHECKED = true;
		}
		return true;
	}
	return false;
}

// -----------------------------------------------------------------------
// redirect to other page
function Redirect( $sURL, $httpCode = 302 )
{
  header("Location: $sURL", true, $httpCode);
  exit();
}

// -----------------------------------------------------------------------
// disable page caching
function NoCache()
{
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("pragma: no-cache");
	header("Cache-Control: private, no-store, max-age=0, no-cache, must-revalidate, post-check=0, pre-check=0");
}

// -----------------------------------------------------------------------
// show sql results as html <option=keyfield>valuefield</option>..
function DrawSQLOptions( $sSQL, $sKeyField, $sValueField, $sSelected )
{
  global $Connection;
  $objRS = New TQuery( $sSQL, $Connection );
  while( !$objRS->EOF )
  {
    echo "<option value=\"{$objRS->Fields[$sKeyField]}\"";
    if( strcasecmp( $objRS->Fields[$sKeyField], $sSelected ) == 0 )
      echo " selected";
    echo ">{$objRS->Fields[$sValueField]}</option>\n";
    $objRS->Next();
  }
}

// -----------------------------------------------------------------------
// show associative array as html <option=keyfield>valuefield</option>..
function DrawArrayOptions( $arOptions, $sSelected )
{
  foreach( $arOptions as $sKey => $sValue )
  {
    echo "<option value=\"{$sKey}\"";
    if( strval( $sKey ) == strval( $sSelected ) )
      echo " selected";
    echo ">{$sValue}</option>\n";
  }

}

// generate insert-sql for specified table and associative array of values
// ------------------------------------------------------
function InsertSQL( $sTable, $arValues, $bDelayed = false, $bIgnore = false )
{
	$arKeys = array_keys( $arValues );
	foreach ( $arKeys as $nKey => $sValue )
		$arKeys[$nKey] = "`{$sValue}`";
	return "insert ".($bDelayed?"delayed ":"")." ".($bIgnore?"ignore ":"")." into $sTable( " . implode( $arKeys, ", " ) . " ) values( " . implode( array_values( $arValues ), ", " ) . " )";
}

// generate update-sql for specified table and associative array of values
// ------------------------------------------------------
function UpdateSQL( $sTable, $arKeyFields, $arValues )
{
	foreach ( $arValues as $sKey => $sValue )
		$arEscapedValues["`{$sKey}`"] = $sValue;
	$sCondition = ImplodeAssoc( " = ", " and ", $arKeyFields );
	$sCondition = str_replace( array(' = null', ' = NULL'), ' is null', $sCondition );
	return "update $sTable set " . ImplodeAssoc( " = ", ", ", $arEscapedValues ) . " where " . $sCondition;
}

// generate delete-sql for specified table and associative array of values
// ------------------------------------------------------
function DeleteSQL( $sTable, $arKeyFields )
{
	$sCondition = ImplodeAssoc( " = ", " and ", $arKeyFields );
	$sCondition = str_replace( array(' = null', ' = NULL'), ' is null', $sCondition );
	return "delete from $sTable where $sCondition";
}

// return max value of specified field from specified table, or 0
// ------------------------------------------------------
function TableMax( $sTable, $sColumn ){
	$q = new TQuery( "select max( $sColumn ) as MaxV from $sTable" );
	$q->Close();
	if( !$q->EOF )
		return $q->Fields['MaxV'];
	else
		return 0;
}

// return next key for specified table
// ------------------------------------------------------
function NextKey( $sTable )
{
	global $Connection;
	$sKey = $Connection->PrimaryKeyField( $sTable );
	$nTableMax = TableMax( $sTable, $sKey ) + 1;
	$q = new TQuery( "show tables like 'NextKey'" );
	if( !$q->EOF )
	{
		$q->Open("select * from NextKey where TableName = '$sTable'");
		if( $q->EOF ){
			$Connection->Execute("insert into NextKey( TableName, NextKey ) values( '$sTable', $nTableMax )");
			$q->Close();
			$q->Open();
		}
		if( !$q->EOF ){
			if( $q->Fields['NextKey'] < $nTableMax ){
				$Connection->Execute("update NextKey set NextKey = $nTableMax where NextKeyID = {$q->Fields['NextKeyID']}");
				$q->Close();
				$q->Open();
			}
			$Connection->Execute("update NextKey set NextKey = NextKey + 1 where NextKeyID = {$q->Fields['NextKeyID']}");
			return $q->Fields['NextKey'];
		}
	}
	return $nTableMax;
}

// return value of first resulting line of query
// or returns default if sql returns no records
// ------------------------------------------------------
function QueryTopDef( $sSQL, $sColumn, $sDefault )
{
	global $Connection;
	$q = new TQuery( $sSQL, $Connection );
	if( !$q->EOF )
		return( $q->Fields[$sColumn] );
	else
		return( $sDefault );
}

// load SQL results into array Key => Value
// ------------------------------------------------------
function SQLToArray( $sSQL, $sKeyCol, $sValueCol, $bAsRows = False, $connection = null )
{
	$q = new TQuery( $sSQL, $connection );
	$arResult = array();
	while( !$q->EOF )
	{
		if( !array_key_exists( $sKeyCol, $q->Fields ) )
			DieTrace( "Column $sKeyCol not found" );
		if( $bAsRows )
			$arResult[] = $q->Fields;
		else
			$arResult[$q->Fields[$sKeyCol]] = $q->Fields[$sValueCol];
		$q->Next();
	}
	return $arResult;
}

// load SQL results into simple array of values( without keys )
// ------------------------------------------------------
function SQLToSimpleArray( $sSQL, $sCol, $bAsRows = False )
{
	$q = new TQuery( $sSQL );
	$arResult = array();
	while( !$q->EOF )
	{
		if( $bAsRows )
		{
			if( isset( $sCol ) )
				$arResult[$q->Fields[$sCol]] = $q->Fields;
			else
				$arResult[] = $q->Fields;
		}
		else
			$arResult[] = $q->Fields[$sCol];
		$q->Next();
	}
	return $arResult;
}

// returns value from table using other field to search
// if $bStrong is true - function will die with error, if key value doesn't exists
// ------------------------------------------------------
function Lookup( $sTable, $sKeyField, $sValueField, $sValue, $bStrong = False )
{
	global $Connection;
	$sValueField = strtolower( $sValueField );
	$q = New TQuery( "select $sValueField from $sTable where $sKeyField = $sValue", $Connection );
	if( !$q->EOF )
		return $q->Fields[$sValueField];
	else
		if( $bStrong )
			DieTrace( "Row with $sKeyField = $sValue not found in table $sTable" );
		else
			return NULL;
}

// returns value from table using other fields to search
// if $bStrong is true - function will die with error, if key value doesn't exists
// ------------------------------------------------------
function LookupBy( $sTable, $sValueField, $sWhere, $bStrong = False )
{
    global $Connection;
    $q = New TQuery( "select $sValueField from $sTable where $sWhere", $Connection );
    if( !$q->EOF )
        return $q->Fields[$sValueField];
    else
        if( $bStrong )
            DieTrace( "Row with $sWhere not found in table $sTable" );
        else
            return NULL;
}

// returns value from array, by key, if value not found, returns empty string
// used to supress "Index not found" error
// ------------------------------------------------------
function ArrayVal($ar, $sIndex, $sDefaultValue = ""){
	if( isset( $ar[$sIndex] ) )
		return $ar[$sIndex];
	else
		return $sDefaultValue;
}

// converts identifier name to text
// example: NameOfField -> Name of field
// ------------------------------------------------------
function NameToText( $sName )
{
    return preg_replace('# U R L$#', ' URL', preg_replace('# I D$#', '',
        ucwords(
            trim(
                strtolower(
                    preg_replace('/([A-Z0-9\/]([a-z])*)/', " $1",
                        preg_replace('#^Is([A-Z0-9])#ms', '$1', $sName)))))));
}

function fixText($text,  $br = false){
	$text = str_replace("\'", "'", $text);
	$text = str_replace('\"', '"', $text);
	$text = str_replace('&quot;', '"', $text);
	if($br)
		$text = str_replace("\r\n", "<br>", $text);
	return $text;
}

function fixQuotes($text, $br = false){
	$text = str_replace("'", "\\'", $text);
	$text = str_replace('"', '\\"', $text);
	$text = str_replace('"', '&quot;', $text);
	if($br)
		$text = str_replace("\r\n", "<br>", $text);
	return $text;
}

// get stack trace
function StackTrace($nSkipTop = 0){
	$trace = debug_backtrace(true);
	if($nSkipTop > 0)
		$trace = array_slice($trace, $nSkipTop);
	$ajax = (strtolower(ArrayVal($_SERVER, 'HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest');
	require_once __DIR__.'/3dParty/kint/Kint.class.php';
	if(isset($_SERVER['REQUEST_METHOD']) && ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG && !$ajax){
		ob_start();
		Kint::trace($trace);
		return ob_get_clean();
	}
	else{
		$s = "";
		$trace = Kint::trace($trace, true);
		foreach($trace as $n => $call){
			$s .= $n.". ";
			if(isset($call['file']) && isset($call['line']))
				$s .= $call["file"].":".$call["line"]."\n";
			$s .= ArrayVal($call, "class").ArrayVal($call, "type").$call["function"];
			if(isset($call['args'])){
				$args = kintLite($call['args'], 0, 4);
				if(php_sapi_name() == 'cli')
					$maxLength = 128;
				else
					$maxLength = 32000;
				if(strlen($args) > $maxLength)
					$args = substr($args, 0, $maxLength)." ...";
				if($args == 'array()')
					$args = '';
				$s .= "(".$args.")";
			}
			$s .= "\n";
		}
		//$s = kintLite($trace)."\n";
        $filtered = ['Pass(word)?', 'GoogleAuthSecret', 'GoogleAuthRecoveryCode'];
        $filteredGroup = '(' . implode('|', $filtered) . ')';
		$s = preg_replace('/\'?(' . $filteredGroup . '\d*\'?\s+[\-=]>\s+string[^"]*")([^"]+)"/ims', '$1xxx_$2_is_hidden_xxx"', $s);
        $s = preg_replace('/(?<!Save)(Pass(word)?)\]\s*=>\s*?[^\s]+/ims', '$1] => xxxxxx', $s);
		$s = preg_replace('/(\[\*depth too great\*\]\s*){2,}/ims', '[*depth too great*]', $s);
		return "<pre>".$s."</pre>";
	}
}

function StateReport(){
	if(LowMemory())
		return "low memory";
	$sText = "";
	if( isset( $_SERVER['REQUEST_METHOD'] ) ) {
		$sRef = 'http://' . ($_SERVER['SERVER_NAME'] ?? '') . $_SERVER['SCRIPT_NAME'];
		if( $_SERVER['QUERY_STRING'] != "" )
			$sRef .= "?" . $_SERVER['QUERY_STRING'];
		$sText = "URL: <a href=$sRef>$sRef</a><hr>" . $sText;
	}
	else
		$sText = "Script: {$_SERVER['SCRIPT_NAME']}<br>\n{$sText}";
	if( isset( $_SERVER['REQUEST_METHOD'] ) ) {
		$sText .= "\$_SERVER:<br><pre style=\"text-align: left;\">" . htmlspecialchars( filterGlobalsSensitiveData(print_r( $_SERVER, True )) ) . "</pre><hr>";
		if(isset($_SESSION))
			$sText .= "\$_SESSION (id[-4:]: ".substr(session_id(), -4)."):<br><div style=\"max-height: 400px; overflow: auto;text-align: left;\"><pre>" . htmlspecialchars( filterGlobalsSensitiveData(print_r( $_SESSION, True )) ) . "</pre></div><hr>";
		$sText .= "\$_POST:<br><div style=\"max-height: 400px;overflow: auto;text-align: left;\"><pre>" . htmlspecialchars( filterGlobalsSensitiveData(print_r( $_POST, True )) ) . "</pre></div><hr>";
		$sText .= "\$_FILES:<br><div style=\"max-height: 400px;overflow: auto;text-align: left;\"><pre>" . htmlspecialchars( print_r( $_FILES, True ) ) . "</pre></div><hr>";
	}
	return $sText;
}

// return true when have free memory less than 8MB
function LowMemory(){
	$limit = ini_get("memory_limit");
	if(preg_match("/^(\d+)M$/ims", $limit, $matches)){
		$limit = $matches[1] * 1024 * 1024;
		$usage = memory_get_usage();
		$free = $limit - $usage;
		if($free < (8*1024*1024))
			return true;
	}
	return false;
}

// die with debug trace
function DieTrace( $s, $bTerminate = True, $nSkipStack = 0, $moreInfo = null ){
	global $Interface, $Config, $sTitle, $Terminating, $DieAsException, $DieTraceOnWarning;
	if(!empty($DieAsException) && ($DieAsException === true || ($DieAsException == 'fatal' && $bTerminate)))
		throw new DieTraceException($s);
    if(!empty($DieTraceOnWarning)){
        call_user_func($DieTraceOnWarning, $s, $moreInfo);
        return;
    }
	if(!class_exists('Kint')) // sometimes require_once fired twice, when called from error handler
		require_once __DIR__.'/3dParty/kint/Kint.class.php';
	// prevent recursion
	if($bTerminate){
		if($Terminating)
			exit();
		$Terminating = true;
	}
	restore_error_handler();
	$sText = "<hr>\n<b>".htmlspecialchars(substr($s, 0, 50000))."</b>";
	if(isset($moreInfo)) {
        if (is_scalar($moreInfo) && !is_bool($moreInfo))
            $m = $moreInfo;
        else
            $m = '<pre>'. kintLite($moreInfo).'</pre>';

        $sText .= '<hr/>'.$m;
    }
	if(!is_numeric($nSkipStack)){
		$s .= " (invalid SkipStack)";
		$nSkipStack = 0;
	}
	$sText .= "<hr/><br>".StackTrace($nSkipStack + 1);
	$plainText = !isset( $_SERVER['REQUEST_METHOD'] )/* || strtolower(ArrayVal($_SERVER, 'HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest'*/;
	if( !isset( $Config[CONFIG_SITE_STATE] ) || ( ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG ) ){
		if( !$plainText )
			$sText .= StateReport();
		if( $plainText ) {
			$sText = str_replace( "<br>", "\n", $sText );
			$sText = str_replace( "<hr/>", "\n", $sText );
			$sText = str_replace( "\n\n", "\n", $sText );
			$sText = html_entity_decode( strip_tags( $sText ) );
			fputs(STDERR, html_entity_decode(strip_tags($sText)));
		}
		else
			echo "<div style='text-align: left;'>$sText</div>";
		if( $bTerminate )
			exit(1000);
	}
	else
	{
		$sHeaders = str_replace( "text/plain", "text/html", EMAIL_HEADERS );
		$siteName = SITE_NAME . " at " . gethostname();
		$sHeaders = preg_replace( "/From:[^\n]+/ims", "From: ".$siteName." <".ConfigValue(CONFIG_ERROR_EMAIL).">", $sHeaders );
		$sText .= StateReport();
		$sText = "<html><body>$sText</body></html>";
		if(defined('PASSWORD'))
			$sText = str_replace( PASSWORD, '***', $sText );
		$s = substr( preg_replace( "/[\r\n\s]+/ims", " ", $s ), 0, 200 );
		if($bTerminate)
			$s = "DieTrace: $s";
		else
			$s = "Warning: $s";
		unset($Config[CONFIG_EMAIL_HANDLER]);
		mailTo( ConfigValue( CONFIG_ERROR_EMAIL ), $s, $sText, $sHeaders );
		if($bTerminate){
			if( isset( $_SERVER['REQUEST_METHOD'] ) ) {
				$sTitle = "Error";
				header("Error: trace", null, 500);
				if(isset($Interface))
					$Interface->DiePage( "There has been an error on this page. This error was recorded and will be fixed as soon as possible." );
				else
					die("There has been an error on this page. This error was recorded and will be fixed as soon as possible.");
			}
			else{
				fputs(STDERR, html_entity_decode(strip_tags($sText)));
				exit(1);
			}
		}
	}
}

// draw hiddens inputs from array
function DrawHiddens( $ar, $bReturn = false )
{
	$s = "";
	foreach( $ar as $sKey => $sValue )
	{
		if( is_array( $sValue ) )
		{
			$sKey = $sKey . "[]";
			foreach ( $sValue as $t )
				$s .= "<input type=\"hidden\" name=\"".htmlspecialchars($sKey)."\" value=\"" . htmlspecialchars( $t ) . "\">\n";
		}
		else
			$s .= "<input type=\"hidden\" name=\"".htmlspecialchars($sKey)."\" value=\"" . htmlspecialchars( $sValue ) . "\">\n";
	}
	if($bReturn)
		return $s;
	else
		echo $s;
}

// strip tags
function StripTags( $s )
{
	$s = preg_replace( '/<[^>]*>/ims', ' ', $s );
	$s = preg_replace("/\s{2,}/", " ", $s);
	$s = preg_replace("/\s+\,/", ",", $s);
	$s = trim($s);
	return $s;
}

// returns string formatted as money ib dollars
function formatMoney($number, $ifZero="\$0"){
	if(is_numeric($number)){
		if($number != 0)
			return "\$" . number_format( $number, 2, ".", "," );
		else
			return $ifZero;
	}
	else
		return "N/A";
}


// return user name from array of fields. add prefix, suffix, if it exists
function UserName( $arFields )
{
	$sUserName = "";
	if(isset($arFields["Prefix"]))
		$sUserName = $arFields["Prefix"];
	if( $sUserName != "" )
		$sUserName .= " ";

	$sFName = $sLName = "";
	if(isset($arFields["FirstName"]))
		$sFName = $arFields["FirstName"];
	if(isset($arFields["LastName"]))
		$sLName = $arFields["LastName"];
	$sUserName .= $sFName . " " . $sLName;

	if(isset($arFields["Suffix"]))
		if( $arFields["Suffix"] != "" )
			$sUserName .= " " . $arFields["Suffix"];
	return $sUserName;
}

/**
 * Load schema object
 * first looks in /schema, then in /lib/schema
 *
 * @param string $schemaName
 * @return TBaseSchema
 */
function LoadSchema( $schemaName )
{
	$className = 'AwardWallet\Manager\Schema\\' . $schemaName;

	if (!class_exists($className)) {
        global $sPath;
        if (file_exists("$sPath/schema/$schemaName.php")) {
            require_once("$sPath/schema/$schemaName.php");
        } else {
            if (file_exists("$sPath/lib/schema/$schemaName.php")) {
                require_once("$sPath/lib/schema/$schemaName.php");
            } else {
                DieTrace("Schema $schemaName not found");
            }
        }
        $className = "T{$schemaName}Schema";
    }

	$obj = new $className();
	$obj->CompleteFields();
	return $obj;
}

function spacer($w, $h=1){
	return "<img src='/lib/images/pixel.gif' width='".htmlspecialchars($w)."' height='".htmlspecialchars($h)."' border='0' alt=''>";
}

# i.e. <img border="0" src="< ?=PicturePath( ALBUM_VIRTUAL_ROOT, "medium", $objRS->Fields["PictureID"], $objRS->Fields["ImageVer"], $objRS->Fields["ImageExt"], "pic" )? >">

function PicturePath( $sRoot, $sSize, $nPictureID, $nVersion, $sExt, $sPrefix = "pic", $sSuffix = "" )
{
	$sSize = strtolower( $sSize );
	if( $sSize == "small" )
		$sExt = "gif";
	if( $sSuffix != "" )
		$sSuffix = "-" . $sSuffix;
	if( intval( $nPictureID ) >= 0 )
		return sprintf( "%s/%s/%06d/%s-%d-%s%s.%s", $sRoot, $sSize, intval( $nPictureID ) / 1000, $sPrefix, $nPictureID, $nVersion, $sSuffix, $sExt );
	else
		return sprintf( "/images/uploaded/temp/%s-%s-%s%s.%s", $sPrefix, $sSize, $nVersion, $sSuffix, $sExt );
}

function FilePath( $sRoot, $nFileID, $nVersion, $sExt, $sPrefix = "file", $sSuffix = "", $bPhysical = False )
{
	global $sPath;
	if( $sSuffix != "" )
		$sSuffix = "-" . $sSuffix;
	if( intval( $nFileID ) >= 0 )
		return sprintf( "%s/%06d/%s-%d-%s%s.%s", $sRoot, intval( $nFileID ) / 1000, $sPrefix, $nFileID, $nVersion, $sSuffix, $sExt );
	else
	{
		$sResult = sprintf( "/images/uploaded/temp/%s-%s%s.%s", $sPrefix, $nVersion, $sSuffix, $sExt );
		if( $bPhysical )
			$sResult = $sPath . $sResult;
		return $sResult;
	}
}

// age
function DeleteFiles( $sMask, $nAge = 0 ) {
	$arFiles = glob( $sMask );
	$dTimeEdge = time() - $nAge;
	if( is_array( $arFiles ) )
		foreach ( $arFiles as $sFile ) {
			$dModTime = @filemtime( $sFile );
			if( ( $nAge == 0 ) || !$dModTime || ( $dModTime < $dTimeEdge ) )
				if( file_exists( $sFile ) && !is_dir( $sFile ) )
					@unlink( $sFile );
		}
}

// register hir to statistics
function RegisterHit( $nItemKind, $nItemID )
{
	global $Connection;
	$Connection->Execute("insert into Hit( ItemKind, ItemID, Hits )
	values( $nItemKind, $nItemID, 1 )
	on duplicate key
	update Hits = Hits + 1");
}

// register hit to statistics
function GetHitCount( $nItemKind, $nItemID )
{
	$q = new TQuery("select Hits from Hit where ItemKind = $nItemKind and ItemID = $nItemID" ) ;
	if( !$q->EOF )
		return $q->Fields["Hits"];
	else
		return 0;
}

function Trace()
{
	if( !isset( $_SESSION["Trace"] ) )
		$_SESSION["Trace"] = array();
	$arTrace = &$_SESSION["Trace"];
	while( count( $arTrace ) >= TRACE_URL_COUNT )
		array_shift( $arTrace );
	if(!isset($_SERVER['SCRIPT_URI']))
		return;
	$sURL = $_SERVER['SCRIPT_URI'];
	if( $_SERVER['SCRIPT_NAME'] == '/lib/chat/connector.php' )
		return;
	if( isset( $_SERVER['REQUEST_METHOD'] ) ){
		$sURL = $_SERVER['REQUEST_METHOD'] . " " . $sURL;
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			// record post params
			$ar = array();
			foreach($_POST as $key => $value){
				$value = print_r($value, True);
				if(strlen($value) > 60)
					$value = substr($value, 0, 60)."..";
				$ar[$key] = $value;
			}
			$sURL .= " -- POST VALUES: ".print_r($ar, True);
			$sURL .= " -- FILES: ".print_r( $_FILES, True );
		}
	}
	if(ArrayVal( $_SERVER, 'QUERY_STRING' ) != "")
		$sURL .= "?" . $_SERVER['QUERY_STRING'];
	$arTrace[] = date("d/m/Y H:i:s")." ".$sURL;
}

function ArrayInsert( &$ar, $sSearchIndex, $bAfter, $arNew )
{
    foreach ($arNew as $arNewKey => $arNewItem) {
        if (array_key_exists($arNewKey, $ar) && is_array($ar[$arNewKey]) && is_array($arNewItem)) {
            $ar[$arNewKey] = array_merge($ar[$arNewKey], $arNewItem);
        }
    }

	$arKeys = array_keys( $ar );
	$arValues = array_values( $ar );
	$nIndex = array_search( $sSearchIndex, $arKeys );
	if( $nIndex === False )
		DieTrace( "Index not found" );
	if( $bAfter )
		$nIndex++;
	array_splice( $arKeys, $nIndex, 0, array_keys( $arNew ) );
	array_splice( $arValues, $nIndex, 0, array_values( $arNew ) );
	$ar = array_combine( $arKeys, $arValues );
}

// call user defined function
function CallUserFunc( $sFunction )
{
	if( is_array( $sFunction ) )
	{
		$sObject = array_shift( $sFunction );
		if( is_object( $sObject ) )
		{
			$sMethod = array_shift( $sFunction );
			if( !method_exists( $sObject, $sMethod ) )
				DieTrace( "Object does not contain method $sMethod" );
			$arMethod = array( &$sObject, $sMethod );
			return call_user_func_array( $arMethod, $sFunction );
		}
		else
			return call_user_func_array( $sObject, $sFunction );
	}
	else
		return call_user_func( $sFunction );
}

function RandomStr( $nStart, $nEnd, $nLength )
{
	$sResult = '';
	for( $n = 0; $n < $nLength; $n++ )
		$sResult .= chr( rand( $nStart, $nEnd ) );
	return $sResult;
}

// create and store FormToken to prevent CSRF attacks
function GetFormToken($createNewToken = false){
    global $Config;
    $result = null;
    if(ArrayVal($Config, CONFIG_FORM_CSRF_CHECK, CSRF_CHECK_OFF) >= CSRF_CHECK_WARNING && isset($_SESSION)){

        $sessionToken = ArrayVal($_SESSION, 'FormToken', null);
        $generatedToken = hash('sha256', RandomStr(0, 255, 32));

        if(!empty($_SESSION['UserID']) && is_numeric($_SESSION['UserID'])){
            $cacheKey = 'CSRF_' . $_SESSION['UserID'];
            // logged in user
            $cachedToken = \Cache::getInstance()->get($cacheKey);

            if(false === $cachedToken){
                // set cache and session(?) entries
                if(empty($sessionToken)){
                    // set both session and cache tokens
                    $_SESSION['FormToken'] = $generatedToken;
                    $cachedToken = $generatedToken;
                }else{
                    // set cache token
                    $cachedToken = $sessionToken;
                }
            }else{
                $_SESSION['FormToken'] = $cachedToken;
            }
            // create new or extend lifetime of existing token
            \Cache::getInstance()->set($cacheKey, $cachedToken, 3 * 3600);
            $result = $cachedToken;
        }else{
            if(empty($sessionToken)){
                $_SESSION['FormToken'] = $generatedToken;
                $result = $generatedToken;
            }else{
                $result = $sessionToken;
            }
        }
    }
    return $result;
}

function ResetFormToken(){
	if(isset($_SESSION)) {
		unset($_SESSION['FormToken']);
		if (!empty($_SESSION['UserID']))
			\Cache::getInstance()->delete('CSRF_' . $_SESSION['UserID']);
	}
}

function isValidFormToken(){
    global $Config;
    // checks whether form crsf check enabled
	if(ArrayVal($Config, CONFIG_FORM_CSRF_CHECK, CSRF_CHECK_OFF) >= CSRF_CHECK_WARNING){
        $validFormTokenValues = array(
            ArrayVal($_GET, 'FormToken'),
            ArrayVal($_POST, 'FormToken'));

        $userFormToken = GetFormToken();

        $isValidToken = in_array($userFormToken, $validFormTokenValues, true);
		if(!$isValidToken && ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_PRODUCTION && ConfigValue(CONFIG_FORM_CSRF_CHECK) == CSRF_CHECK_WARNING){
			DieTrace( "Invalid FormToken", false);
		}
		if($Config[CONFIG_FORM_CSRF_CHECK] === CSRF_CHECK_STRICT){
			return $isValidToken;
		}else{
			return true;
		}
    }else{
        // CSRF_CHECK_OFF
        // do not brake non-AW sites
        return true;
    }
}

// convert string with M/K suffix to bytes
function StrToBytes($val) {
   $val = trim($val);
   $last = strtolower(substr($val, 1, 1));
   switch($last) {
       // The 'G' modifier is available since PHP 5.1.0
       case 'g':
           $val *= 1024;
       case 'm':
           $val *= 1024;
       case 'k':
           $val *= 1024;
   }
   return intval( $val );
}

function GetDBParam( $sName ){
	$q = new TQuery("select * from Param where Name = '".addslashes($sName)."'");
	if( $q->EOF )
		return "";
	switch ( $q->Fields["Type"] ){
		case PARAM_TYPE_FLOAT:
			return $q->Fields["FloatVal"];
		case PARAM_TYPE_INTEGER:
			return $q->Fields["IntVal"];
		case PARAM_TYPE_STRING:
			return $q->Fields["StringVal"];
		case PARAM_TYPE_TEXT:
			return $q->Fields["TextVal"];
		default:
			DieTrace("Unknown Param Type: ".$q->Fields["Type"]);
	}
}

function SetDBParam( $sName, $nType, $sValue, $bAddSlashes = true ){
	global $Connection;
	switch ( $nType ){
		case PARAM_TYPE_FLOAT:
			$sField = "FloatVal";
			break;
		case PARAM_TYPE_INTEGER:
			$sField = "IntVal";
			break;
		case PARAM_TYPE_STRING:
			$sField = "StringVal";
			break;
		case PARAM_TYPE_TEXT:
			$sField = "TextVal";
			break;
		default:
			DieTrace("Unknown Param Type: ".$nType);
	}
	if( $bAddSlashes )
		$sValue = "'".addslashes( $sValue )."'";

	$q = new TQuery("select * from Param where Name = '".addslashes($sName)."'");
	if( $q->EOF )
		$Connection->Execute("insert into Param( Name, Type, $sField ) values( '".addslashes($sName)."', $nType, $sValue )");
	else
		$Connection->Execute("update Param set $sField = $sValue, Type = $nType where Name = '".addslashes($sName)."'");
}

function EmailVerify(){
	AuthorizeUser();
	if(isset($_SESSION["EmailVerified"]) && ( $_SESSION["EmailVerified"] == EMAIL_VERIFIED ) )
  		return true;
	else
		ScriptRedirect("/security/emailNotVerifyed.php");
}

$arReportedErrors = array();
$ThrowErrorExceptions = false;

// report warnings and other errors
function LibErrorHandler( $errno, $errstr, $errfile = null, $errline = null ){
	global $arReportedErrors, $ThrowErrorExceptions;
	$bCritical = false;
	switch ($errno) {
		case E_USER_WARNING:
		case E_USER_NOTICE:
		case E_CORE_WARNING:
		case E_WARNING:
		case E_NOTICE:
		case E_DEPRECATED:
		case E_STRICT:
			break;
		default:
			$bCritical = true;
			break;
	}
	if((strpos($errfile, '/usr/share/php/') === 0 && $errno == 2048) /* pear Net errors */
	|| (stripos($errfile, 'paypal') !== false && $errno == 2048)
	|| (strpos($errfile, '/lib/cart/') !== false && $errno == 2048) /* paypal errors */
	|| (strpos($errfile, '/lib/3dParty/ufpdf') !== false && $errno == 2048)
	|| (strpos($errstr, 'PayPal::') !== false && $errno == 2048)
	|| (strpos($errstr, 'PEAR::') !== false && $errno == 2048)
	|| (strpos($errstr, 'The session id is too long or contains illegal characters') !== false && $errno == E_WARNING)
	|| ($errno == E_DEPRECATED || $errno == E_USER_DEPRECATED))
		return;
	if( $bCritical ){
		DieTrace("Error [{$errno}]: $errstr. File: {$errfile}:{$errline}", true, 2);
	}
	if( ConfigValue( CONFIG_SITE_STATE ) == SITE_STATE_PRODUCTION ){
		if(isset($_SERVER['SCRIPT_NAME']) && preg_match("/^\/forum/ims", $_SERVER['SCRIPT_NAME']))
			return;
		if(($errfile == '/usr/share/php/HTTP/Request.php') && ($errstr == 1507))
			return;
		if((stripos($errfile, 'PDOConnection.php') !== false || stripos($errfile, 'PDOStatement.php') !== false)
        &&
            (stripos($errstr, "Error reading result set") !== false
            || stripos($errstr, "MySQL server has gone away") !== false
        ))
			return;
		if(error_reporting() === 0)
			return;
		if(strpos($errstr, 'GC cache entry') > 0)
			return;
		$sSubject = "Error [{$errno}]: $errstr. File: {$errfile}:{$errline}";
		if(!in_array($sSubject, $arReportedErrors)){
			DieTrace($sSubject, false);
			$arReportedErrors[] = $sSubject;
		}
	}
}

function WrapText( $str, $length )
{
	if( mb_strlen( $str ) < $length )
		return $str;
	$p = false;
	foreach(array(" ", "\n", "\r", "\t", ".", ",", "!") as $sym){
		$p = strpos($str, $sym, $length);
		if($p !== false)
			break;
	}
	if($p === false)
		return $str;
	else
		return substr($str, $p)."...";
}

function get_form_field( $html, $form_name = "" ) {
  if( strlen( $form_name ) == 0 ) {
    if( !preg_match_all( "/<form.*?form>/ims", $html, $forms ) ) {
      return false;
    }
  }
  else {
    if( !preg_match_all( "/<form[^>]+name\s*=\s*\'?\"?" . $form_name . "\'?\"?/ims", $html, $forms, PREG_OFFSET_CAPTURE ) ) {
      return false;
    }
    foreach ( $forms as $key => $form ){
    	if( preg_match( "/<\/form>/ims", $html, $arMatches, PREG_OFFSET_CAPTURE, $form[0][1] ) ){
    		$forms[$key][0] = substr( $html, $form[0][1], $arMatches[0][1] - $form[0][1] + strlen( $arMatches[0][0] ) );
    	}
		else
			$forms[$key][0] = substr( $html, $form[0][1], 99999999 );
    }
  }
  foreach( $forms as $key => $form ) {
    $fields[ $key ] = array();
    preg_match_all( "/<input[^>]+name\s*=\s*\'?\"?([^\"\'\>]+)\'?\"?[^>]*>/ims", $form[ 0 ], $inputs  );
    foreach( $inputs[ 1 ] as $k => $input_name ) {
      $fields[ $key ][ $input_name ] = "";
      if ( preg_match( "/<[^>]*". preg_quote($input_name, '/') ."[^>]*value\s*=\s*\'?\"?([^\"\'\>]+)\'?\"?[^>]*>/ims", $inputs[ 0 ][ $k ], $value ) ) {
        $fields[ $key ][ $input_name ] = $value[ 1 ];
      } elseif ( preg_match( "/<[^>]*value\s*=\s*\'?\"?([^\"\'\>]+)\'?\"?[^>]*". preg_quote($input_name, '/') ."[^>]*>/ims", $inputs[ 0 ][ $k ], $value ) ) {
        $fields[ $key ][ $input_name ] = $value[ 1 ];
      }
    }
  }
  return $fields;
}

function BytesToStr($size, $points = 1)
{
	$count = 0;
	$format = array("B","KB","MB","GB","TB","PB","EB","ZB","YB");
	while(($size/1024)>1 && $count<8){
		$size=$size/1024;
		$count++;
	}
	return number_format($size,$points,'.',' ')." ".$format[$count];
}

function FindUSACountryID(){
	global $Config;
	$whereCondition = "";
	if(isset($Config["RussianSite"]))
		$whereCondition = " or Name = 'Соединённые Штаты Америки'  or Name = 'США'";
	$q = new TQuery("select CountryID from Country where Name = 'United States' or Name = 'USA'" . $whereCondition);
	if( $q->EOF )
		DieTrace("Can't find USA");
	return $q->Fields["CountryID"];
}

function FindCanadaCountryID(){
	global $Config;
	$whereCondition = "";
	if(isset($Config["RussianSite"]))
		$whereCondition = " or Name = 'Канада'";
	$q = new TQuery("select CountryID from Country where Name = 'Canada'" . $whereCondition);
	if( $q->EOF )
		DieTrace("Can't find Canada");
	return $q->Fields["CountryID"];
}

function ArrayToObject( $arParams ){
	$objParams = new stdClass();
	foreach ( $arParams as $sKey => $sValue )
		$objParams->$sKey = $sValue;
	return $objParams;
}

// return 's' if count > 1. for formatting messages
function s($nCount){
	if(abs($nCount) == 1)
		return "";
	else
		return "s";
}

// return 'have' if count > 1. for formatting messages
function have($nCount){
	if($nCount == 1)
		return "has";
	else
		return "have";
}

// output debug string, if there is named parameter in $_GET
function EchoDebug($sCategory, $sMessage){
	if(isset($_GET['Debug'.$sCategory])){
		if(isset($_SERVER['REQUEST_METHOD']) && !preg_match('/<br>[\n\r\s]*$/ims', $sMessage))
			$sMessage .= "<br>";
		if(!preg_match('/\n$/ims', $sMessage))
			$sMessage .= "\n";
		echo $sMessage;
	}
}

// convert strng to hex
function StrToHex($s, $allowedChars = "a-z0-9"){
	$sResult = "";
	for($n = 0; $n < strlen($s); $n++){
		$sChar = substr($s, $n, 1);
		if(!preg_match("/[$allowedChars]/ims", $sChar))
			$sResult .= "(".dechex(ord($sChar)).")";
		else
			$sResult .= $sChar;
	}
	return $sResult;
}

function BreakWords($s, $nMinSize, $nMaxSize){
	return preg_replace("/([\w\@\.\,]{".$nMinSize.",".$nMaxSize."})/ims", '$1<span style="font-size: 0px; overflow: hidden;"> </span>', $s);
}

function EmailBlocked($sEmail){
	$q = new TQuery("select * from DoNotSend where Email = '".addslashes($sEmail)."'");
	return !$q->EOF;
}

// Added By Alexi Vereschaga
// This finction accepts url, param name, and value. If parameter exists in the url value will be replaced. Otherwise added.
// injectURLParam("http://asdf.com?zzz=123", "zzz",  "321");
function injectURLParam($url, $key, $value){
	$urlArr = preg_split("/\?/", $url);
	if(count($urlArr) != 2)
		return  $url . "?$key=$value";
	$queryArr = explode("&", $urlArr[1]);
	foreach($queryArr as $arKey => $urlPart)
		$queryArr[$arKey]  =  preg_replace( "/^".$key."=.*/", $key."=".$value, $urlPart);
	$finalURL = $urlArr[0] . "?" . implode("&", $queryArr);
	if($finalURL == $url && stripos($finalURL, $key."=".$value) === false)
		$finalURL .= "&$key=$value";
	return $finalURL;
}

// check that user is member of some group, or this is admin interface
// forces user to authorize (if not admin)
// returns boolean. alwasy true for admin interface
function HaveUserGroup($sGroupName){
	if(isset($_SERVER['SCRIPT_NAME']) && preg_match("/^(\/lib)?\/admin\//ims", $_SERVER['SCRIPT_NAME']))
		return true;
	AuthorizeUser();
	$q = new TQuery("SELECT g.SiteGroupID, g.GroupName
	FROM SiteGroup g INNER JOIN GroupUserLink l ON g.SiteGroupID = l.SiteGroupID
	WHERE l.UserID = ".$_SESSION["UserID"]." and g.GroupName = '".addslashes($sGroupName)."'");
	return !$q->EOF;
}

// check that user is member of some group, or this is admin interface
// forces user to authorize (if not admin)
// display DiePage, if not. do nothing if yes.
function CheckUserGroup($sGroupName){
	global $Interface;
	if(!HaveUserGroup($sGroupName))
		$Interface->DiePage("You should be in group '".$sGroupName."' to access this page.");
}

function CheckUserGroupByUser($userId, $sGroupName) {
	$q = new TQuery("SELECT g.SiteGroupID, g.GroupName
	FROM SiteGroup g INNER JOIN GroupUserLink l ON g.SiteGroupID = l.SiteGroupID
	WHERE l.UserID = ".$userId." and g.GroupName = '".addslashes($sGroupName)."'");
	return !$q->EOF;
}

/**
 * @deprecated see AwardWallet\Common\Parsing\Html::convertHtmlToUtf
 */
function convertHtmlToUtf($html, $headers = null){
	if(isset($headers)){
		if(is_array($headers))
			$headers = ImplodeAssoc(":", "\n", $headers);
		if(preg_match("/Content\-Type\s*:\s*text\/html;\s*charset=([^\n\;]+)/ims", $headers, $matches))
			$encoding = strtolower($matches[1]);
	}
	if(preg_match("/<meta[^>]*http\-equiv=\"?Content\-Type\"?[^>]*>/ims", $html, $matches))
	    if(preg_match("/<meta[^>]*content=\"?text\/html;\s*charset=([^\"\;]+)\"?[^>]*>/ims", $matches[0], $matches))
            $encoding = strtolower($matches[1]);
	if(isset($encoding) && trim($encoding) != "" && strtolower($encoding) != 'utf-8') { //in_array($encoding, array("iso-8859-1", "latin1", "windows-1252", "windows-1251", "windows-1250"))
		$encoding = checkEncoding($encoding);
        $_html = @iconv($encoding, 'UTF-8', $html);
		if ( $_html === false )
            $_html = @mb_convert_encoding($html, "UTF-8", $encoding);
        if ( $_html !== false )
			$html = $_html;
		/*else {
			DieTrace("Wrong charset \"$encoding\"", false);
		}*/
	}
	
	# is utf8?
	$isUtf8 = mb_check_encoding($html, 'UTF-8');
	if (!$isUtf8) {
		$currEncoding = mb_detect_encoding($html);
		if ($currEncoding !== false) {
			if (($_html = @mb_convert_encoding($html, "UTF-8", $currEncoding)) != '')
				$html = $_html;
		}
	}

	return $html;
}

/**
 * @deprecated see AwardWallet\Common\Parsing\Html::checkEncoding
 */
function checkEncoding($encoding) {
	$encoding = strtolower($encoding);
	$wrongCharsetTable = array(
		'iso8859_1' => 'iso8859-1',
		'unicode' => 'UTF-8',
	);
	$encoding = str_replace(array_keys($wrongCharsetTable), $wrongCharsetTable, $encoding);
	
	return $encoding;
}

/**
 * @deprecated see AwardWallet\Common\Parsing\Html::tidyDoc
 */
function TidyDoc($html, $filter = true, $convertToUtf = true) {
  $config = array(
    'indent' => true,
    'output-xhtml' => true,
    'wrap' => 200,
//  	'numeric-entities' => true,
  	'doctype' => 'omit'
  );
  $disabledLoader = libxml_disable_entity_loader(true);
  $tidy = new tidy;
  if($convertToUtf)
	$html = convertHtmlToUtf($html);
  $tidy->parseString($html, $config, 'utf8');
  $tidy->cleanRepair();
  $doc=new DOMDocument('1.0', 'utf-8');
  $nErrorLevel = error_reporting(0);
  $loaded = $filter && $doc->loadHTML('<?xml encoding="UTF-8">'.$tidy);
  $tidy->parseString("", $config, 'utf8');
  $tidy = null;
  error_reporting($nErrorLevel);
  if(!$loaded){
	$nErrorLevel = error_reporting( E_ALL ^ E_WARNING ^ E_NOTICE );
    libxml_use_internal_errors(true);
	$doc->loadHTML( '<?xml encoding="UTF-8">'.$html );
    libxml_use_internal_errors(false);
	error_reporting( $nErrorLevel );
  }
  libxml_disable_entity_loader($disabledLoader);
  //file_put_contents("/mnt/projects/xml.xml", $doc->saveXML());
  return $doc;
}

/**
 * @deprecated see AwardWallet\Common\Parsing\Html::cleanXMLValue
 */
function CleanXMLValue( $s ){
    if($s instanceof DOMNode){
        $s = $s->nodeValue;
    }
	$s = mb_convert_encoding($s, 'UTF-8', 'UTF-8'); // remove bugged symbols
	$s = preg_replace('/[\x{0000}-\x{0019}]+/ums', ' ', $s); // remove unicode special chars, like \u0007
	$s = preg_replace("/\p{Mc}/u", ' ', $s); // normalize spaces
	$s = trim(preg_replace("/\s+/u", " ", preg_replace("/\r|\n|\t/u", ' ', $s)));
	$s = html_entity_decode($s, ENT_COMPAT, 'UTF-8');
	return $s;
}

function GetCountryOptions(&$nUSA, &$nCanada){
	global $Config;
	if(!isset($Config["RussianSite"])){
		$q = new TQuery("select * from Country where Name = 'United States' or Name = 'USA'");
		if( $q->EOF )
			DieTrace("Can't find USA in Country table");
		$nUSA = $q->Fields["CountryID"];
		$q = new TQuery("select * from Country where Name = 'Canada'");
		if( $q->EOF )
			DieTrace("Can't find Canada in Country table");
		$nCanada = $q->Fields["CountryID"];
		$arCountries = array(
			"$nUSA " => "United States",
			"$nCanada " => "Canada",
			"" => "------------",
		)
		+ SQLToArray( "select CountryID, Name from Country order by Name", "CountryID", "Name" );
	}
	else{
		$q = new TQuery("select * from Country where Name = 'Россия' or Name = 'Russia'");
		if( $q->EOF )
			DieTrace("Can't find Россия or Russia in Country table");
		$nRussia = $q->Fields["CountryID"];
		$arCountries = array(
			$nRussia => $q->Fields['Name'],
			"" => "------------",
		)
		+ SQLToArray( "select CountryID, Name from Country where CountryID not in($nRussia)", "CountryID", "Name" );
	}
	return $arCountries;
}

// this functions is identical to mail(), but corrects Date: header, if there is one
// this is fix for console long-running scripts
function mailTo($email, $subject, $body, $headers = null, $params = null){
	global $Config;
	if(isset($headers))
		$headers = preg_replace("/Date:([^\n]+)/ims", "Date: ".date("r"), $headers);

	if(isset($Config[CONFIG_EMAIL_HANDLER]))
		$result = call_user_func($Config[CONFIG_EMAIL_HANDLER], $email, $subject, $body, $headers, $params);
	else{
		$result = false;
		if(isset($Config[CONFIG_TEST_EMAIL])){
			if(preg_match('/^dir:(.+)$/ims', $Config[CONFIG_TEST_EMAIL], $matches)){
				saveEmail($matches[1], $email, $subject, $body, $headers);
				$result = true;
			} else {
                // trick to save original recipient, mainly for testing purposes
                $addHeader = 'X-Swift-Aw-To: ' . $email;
                if (null === $headers) {
                    $headers = $addHeader;
                } else {
                    $headers .= "\n" . $addHeader;
                }

                $email = $Config[CONFIG_TEST_EMAIL];
            }
		}
		if(!$result)
			$result = mail($email, $subject, $body, $headers, $params);
	}
	return $result;
}

// save email to dir. returns saved file name
// if headers is null - assumed that body contains raw message
function saveEmail($dir, $email, $subject, $body, $headers){
	if(!file_exists($dir))
		DieTrace("mail log directory not found: ".$dir);
	$dir .= "/".substr($email, 0, 2)."/".$email."/".date("d")."/".sprintf("%03d", rand(0, 999));
	if(!file_exists($dir))
		if(!mkdir($dir, 0777, true))
			DieTrace("failed to create $dir");
	$file = $dir."/".date("H-i-s_").substr(preg_replace("/[^\w\-]+/ims", "_", $subject), 0, 60);
	$suffix = "";
	while(file_exists($file.$suffix.".eml")){
		$suffix = intval($suffix) - 1;
	}
	$file .= $suffix.".eml";
	if(isset($headers))
		$data = "To: $email
Subject: $subject
$headers

$body";
	else
		$data = $body;
	if(!file_put_contents($file, $data))
		DieTrace("failed to save $file");
	return $file;
}

function openDatabaseConnection(){
	global $Connection, $Config;
	if(!isset($Connection)){
		$Connection = New $Config[CONFIG_CONNECTION_CLASS]();
		$Connection->Open(Array("Host" => HOST, "Login" => LOGIN, "Password" => PASSWORD, "Database" => DATABASE, "Persistent" => PERSISTENT, "ErrorHandler" => ArrayVal($Config, CONFIG_CONNECTION_ERROR_HANDLER, null)));
	}
}

function beautifulName($s){
	$s = str_replace(array("-", "'", "/", ".",","), array(" - ", " ' ", " / ", " . ", " , "), $s);
	$s = mb_convert_case($s, MB_CASE_TITLE, "UTF-8");
	$s = str_replace(array(" - ", " ' ", " / ", " . ", " , "), array("-", "'", "/", ".",","), $s);
	return $s;
}

function curlRequest($url, $timeout = 10, $options = array(), &$requestInfo = [], &$curlErrno = null) {
	$rQuery = curl_init($url);
	if( !$rQuery ){
		EchoDebug("Curl", "failed to init curl<Br>");
		return false;
	}
	curl_setopt( $rQuery, CURLOPT_CONNECTTIMEOUT, $timeout );
	curl_setopt( $rQuery, CURLOPT_TIMEOUT, $timeout );
	curl_setopt( $rQuery, CURLOPT_HEADER, false );
	curl_setopt( $rQuery, CURLOPT_FAILONERROR, true );
	curl_setopt( $rQuery, CURLOPT_DNS_USE_GLOBAL_CACHE, false );
	curl_setopt( $rQuery, CURLOPT_RETURNTRANSFER, true );
	foreach($options as $key => $value)
		curl_setopt($rQuery, $key, $value);
	$result = curl_exec( $rQuery );
	$curlErrno = curl_errno($rQuery);
	$requestInfoFilled = [];
	foreach ($requestInfo as $key)
		$requestInfoFilled[$key] = curl_getinfo($rQuery, $key);
	$requestInfo = $requestInfoFilled;
	curl_close($rQuery);
	return $result;
}

function curlXmlRequest($url, $timeout = 60){
	$sXML = curlRequest($url, $timeout);
	if( !$sXML ){
		EchoDebug("GeoTag", "failed to query address $url<Br>");
		return false;
	}
	libxml_use_internal_errors(true);
	$objXML = simplexml_load_string($sXML);
	if($objXML === false){
		EchoDebug("GeoTag", "failed to parse xml for address $url: $sXML<Br>");
		return false;
	}
	return $objXML;
}

function is_empty($mixed){
	return empty($mixed);
}

function urlPathAndQuery($url){
	$parts = parse_url($url);
	if(!empty($parts['path']) && preg_match("#^\/?([\w\-]+(\.\w+)?\/?)*$#ims", $parts['path']))
		$result = $parts['path'];
	else
		$result = '/';
	if(!empty($parts['query']))
		$result .= '?'.$parts['query'];
	if(!empty($parts['fragment']))
		$result .= '#'.$parts['fragment'];
	return $result;
}

/**
 * @param string $string
 * @return string
 */
function filterGlobalsSensitiveData($data)
{
    // hide passwords from global variables
    $globalFields = ['Pass', 'Credential', 'GoogleAuthSecret', 'GoogleAuthRecoveryCode', 'BrowserKey', 'XSRF-TOKEN', 'ItineraryCalendarCode', '_csrf\/\w*', 'FormToken', 'PHP_AUTH_PW', 'HTTP_AUTHORIZATION'];
    $globalFieldsGroup = '(?:' . implode('|', $globalFields) . ')';
    $data = preg_replace('/^(\s*)\[(' . $globalFieldsGroup .'[^]]*)\] => ([^\n]+)(\n)(?=\s*(\)|\[))/ims', '$1[$2] => xxx_$2_exists_and_hidden_xxx$4', $data);

    // hide bcrypt hashes in serialized tokens
    $data = preg_replace('/\$2y\$.{56}/', '\$2y\$13xxxxxxxxxxxxxxx_bcrypt_hash_is_hidden_xxxxxxxxxxxxxxxx', $data);

    // hide md5 hashes in serialized tokens
    $data = preg_replace('/s:32:"[a-f0-9]{32}"/i', 's:32:"xxxxxx_md5_hash_is_hidden_xxxxxx"', $data);

    // hide cookies
    $cookies = ['PwdHash', 'PHPSESSID', 'phpbb3_h543j_sid', 'BrowserKey', 'XSRF\-TOKEN', 'APv2\-\d+', 'CC'];
    $cookiesGroup = implode('|', $cookies);
    $data = preg_replace('/('. $cookiesGroup .')=[^;\n]+?([^;\n]{4})(;|$|\n)/ims', '$1[-4:]=$2$3', $data);

    return $data;
}
