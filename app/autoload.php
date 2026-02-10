<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

$loader = require __DIR__.'/../vendor/autoload.php';

require_once __DIR__.'/AppKernel.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

if (!function_exists('loadProviderChecker')) {
	function loadProviderChecker($className) {
		if(strpos($className, 'TAccountChecker') === 0){
			$code = strtolower(substr($className, strlen('TAccountChecker')));
			if(!empty($code)) {
				$file = __DIR__ . '/../src/engine/' . $code . '/functions.php';
				if(file_exists($file))
					require_once $file;
			}
		}
	}
	spl_autoload_register('loadProviderChecker');
}

require __DIR__ . '/../vendor/awardwallet/lib/constants.php';
require __DIR__ . '/../vendor/awardwallet/lib/functions.php';
require __DIR__ . '/../vendor/awardwallet/service/old/constants.php';
require __DIR__ . '/../vendor/awardwallet/service/old/functions.php';

define('DATEFORMAT_US', 1);
define('DATEFORMAT_EU', 2);
define('MEMCACHED_HOST', 'memcached');

$Config[CONFIG_SITE_STATE] = SITE_STATE_DEBUG;
$Config[CONFIG_TRAVEL_PLANS] = true;

// save password mode
define( 'SAVE_PASSWORD_DATABASE', 1 );
define( 'SAVE_PASSWORD_LOCALLY', 2 );
$SAVE_PASSWORD = array(
	SAVE_PASSWORD_DATABASE => "With AwardWallet.com",
	SAVE_PASSWORD_LOCALLY => "Locally on this computer",
);

initRegionalSettings();

function initRegionalSettings(){
	global $UserSettings, $decimalPoints;
	if (isset($UserSettings) && is_array($UserSettings))
		return;
	$dateFormat = DATEFORMAT_US;
	$thousandsSeparator = ",";
	if(isset($_SESSION['UserFields']['DateFormat']))
		$dateFormat = $_SESSION['UserFields']['DateFormat'];
	if(isset($_SESSION['UserFields']['ThousandsSeparator']))
		$thousandsSeparator = $_SESSION['UserFields']['ThousandsSeparator'];
	$UserSettings['DateFormat'] = $dateFormat;
	$UserSettings['ThousandsSeparator'] = $thousandsSeparator;
	$dateFormats = DateFormats($dateFormat);
	$UserSettings = array_merge($UserSettings, $dateFormats);
	if(isset($_SESSION['UserID']))
		$UserSettings['UserID'] = $_SESSION['UserID'];
	if(!defined("DATE_TIME_FORMAT")) {
		define("DATE_TIME_FORMAT", $dateFormats['datetime']);
		define("DATE_FORMAT", $dateFormats['date']);
		define("TIME_FORMAT", $dateFormats['time']);
		define("MONTH_DAY_FORMAT", $dateFormats['monthday']);
		define("DATE_LONG_FORMAT", $dateFormats['datelong']);
		define("DATE_SHORT_FORMAT", $dateFormats['dateshort']);
		define("TIME_LONG_FORMAT", $dateFormats['timelong']);
	}
}

// return date formats by mode
function DateFormats($dateFormat){
	switch($dateFormat){
		case DATEFORMAT_US:
			return array(
				'datetime' => "F d, Y H:i:s",
				'date' => "m/d/Y",
				'dateshort' => "m/d/y",
				'time' => "h:ia",
				'datelong' => "F j, Y",
				'monthday' => "F j",
				'timelong' => "g:i A",
				'datetimelong' => "F j, Y g:i A",
				'datepicker' => "mm/dd/yy",
			);
		case DATEFORMAT_EU:
			return array(
				'datetime' => "d F, Y H:i:s",
				'date' => "d/m/Y",
				'dateshort' => "d/m/y",
				'time' => "H:i",
				'datelong' => "j F, Y",
				'monthday' => "j F",
				'timelong' => "G:i",
				'datetimelong' => "j F, Y G:i",
				'datepicker' => "dd/mm/yy",
			);
		default:
			DieTrace("Unknown date format: $dateFormat");
	}
}

return $loader;

