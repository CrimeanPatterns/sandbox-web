<?
if (!function_exists('libAutoload')) {
    function libAutoload($class_name) {
        global $sPath;
        if( file_exists("$sPath/lib/classes/$class_name.php") )
            require_once("$sPath/lib/classes/$class_name.php");
        elseif (file_exists("$sPath/lib/$class_name/$class_name.php"))
            require_once("$sPath/lib/$class_name/$class_name.php");
    }
    spl_autoload_register('libAutoload');
}
if (!function_exists('loadProviderChecker')) {
	function loadProviderChecker($className) {
		if(strpos($className, 'TAccountChecker') === 0){
			$code = strtolower(substr($className, strlen('TAccountChecker')));
			if(!empty($code)) {
				$file = __DIR__ . '/../engine/' . $code . '/functions.php';
				if(file_exists($file))
					require_once $file;
			}
		}
	}
	spl_autoload_register('loadProviderChecker');
}
require_once( "$sPath/lib/functions.php" );
require_once( "$sPath/lib/constants.php" );
require_once( 'serverConstants.php' );
if(!defined('PERSISTENT'))
//	if(isset($_SERVER['REQUEST_METHOD']))
		define('PERSISTENT', true);
//	else
//		define('PERSISTENT', false);
require_once( "$sPath/kernel/constants.php" );

if (!defined('STDERR')) { define('STDERR', fopen('php://stderr', 'w')); }

define("EMAIL_RICH_HEADERS", "MIME-Version: 1.0\n" . str_ireplace("text/plain", "text/HTML", EMAIL_HEADERS));

// receive deciphered https from balancer
if(ConfigValue(CONFIG_THROUGH_PROXY)){
	if(isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
	&& ($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
	&& (!isset($_SERVER['HTTPS']) || ($_SERVER['HTTPS'] != 'on')))
		$_SERVER['HTTPS'] = 'on';
	if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
		$_SERVER['REMOTE_ADDR'] = preg_replace('/\,.+$/ims', '', trim($_SERVER['HTTP_X_FORWARDED_FOR'], "\r\n\t \0,"));
}

if(!isset($bNoSession) && session_id() == ""){
	if(ConfigValue(CONFIG_HTTPS_ONLY))
		ini_set('session.cookie_secure', 'true');
	ini_set('session.cookie_httponly', 'true');
	if(isset($_POST['DmnSsn']) && !empty($ALLOW_SESSIONID_IN_POST)){
		session_id($_POST['DmnSsn']);
		$_COOKIE[ini_get('session.name')] = $_POST['DmnSsn'];
	}
	// prevent new session creation on http pages
	if(!ConfigValue(CONFIG_HTTPS_ONLY) || ArrayVal($_SERVER, "HTTPS") == 'on' || (!empty($HTTP_SESSION_START) && isset($_COOKIE[ini_get('session.name')])))
		session_start();
}

$QS = $_GET;
$sTitle = "No title";
$cPage = "";
$bSecuredPage = True;

// include files
require_once( "$sPath/lib/textFunctions.php" );
require_once( "$sPath/lib/classes/TQuery.php" );
require_once( "$sPath/kernel/TInterface.php" );
require_once( "$sPath/kernel/siteFunctions.php" );

set_error_handler( "LibErrorHandler", E_ALL );

$Config["dateNote"] = "April 1, 1980 would look like \"04/01/1980\"";
if(isset($Config["RussianSite"]) && $Config["RussianSite"] == true)
	$Config["dateNote"] = "1 Апреля, 1980 выглядит вот так: \"01.04.1980\"";

NoCache();
Trace();
umask(003);

// connect to database
/* @var $Connection TMySQLConnection */
if(!isset($NO_DATABASE))
	openDatabaseConnection();

// create other objects
$Interface = New TInterface;

// correct HTTP/1.0
if(!isset($_SERVER['HTTP_HOST']) && isset($_SERVER['SCRIPT_URI'])){
	$url = parse_url($_SERVER['SCRIPT_URI']);
	if($url !== false)
		$_SERVER['HTTP_HOST'] = $url['host'];
}

// authorize user
if(isset($Connection))
	AuthorizeUser( false );
processRefCode();

$Interface->RedirectToLogin();

function processRefCode(){
	if(isset($_GET["invId"]))
		$_SESSION["invId"] = intval( $_GET["invId"] );
	if(isset($_GET["invrId"]))
		$_SESSION["invrId"] = intval( $_GET["invrId"] );
	if(isset($_GET["refCode"]) && (trim($_GET["refCode"]) != '')){
		$q = new TQuery("select * from Usr limit 1");
		if(array_key_exists("RefCode", $q->Fields)){
			$q = new TQuery("select UserID from Usr where RefCode = '".addslashes($_GET['refCode'])."'");
			if(!$q->EOF){
				$_SESSION['invrId'] = $q->Fields['UserID'];
				$_SESSION['ref'] = 4; // invite option on left bar
			}
		}
	}
}

$Interface->Init();
