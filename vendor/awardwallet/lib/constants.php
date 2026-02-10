<?
# regexps
define( "MONEY_REGEXP", "/^\d+(\.\d\d?)?$/" );
define('EMAIL_REGEXP', '/^[_a-zA-Z\d\-\+\.]+@([_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+)$/i');
define( "MONEY_REGEXP_ERROR", "Do not use a \"$\" or \",\" when entering the price. Use numbers only." );
define( "FORM_DATE_TIME_FORMAT", "m/d/Y H:i:s" );
define( 'REGEXP_USA_ZIP', '/^\d{5}(\-\d{1,10})?$/' );

define("PIXEL", "<img src=\"/lib/images/pixel.gif\" width=\"1\" height=\"1\" border=\"0\" alt=\"\">");

#Measurements:
#size:
define( 'MM', 1 );
define( 'INCH', 2 );
/** @deprecated use AwardWallet\Common\DateTimeUtils::SECONDS_PER_DAY */
define( 'SECONDS_PER_DAY', 24 * 60 * 60 );
/** @deprecated use AwardWallet\Common\DateTimeUtils::SECONDS_PER_HOUR */
define( 'SECONDS_PER_HOUR', 60 * 60);


#weight:
define('LBS', 1);
define('OZ', 4);
define('KG', 2);
define('MG', 3);

#Measurements size array
global $sizeUnitTable;
$sizeUnitTable = array(
	MM		=> "Millimeters",
	INCH	=> "Inches",
);

#Measurements weight array
global $weightUnitTable;
$weightUnitTable = array(
	LBS		=> "Pounds",
	OZ		=> "Ounces",
	KG		=> "Kilograms",
	MG		=> "Milligrams"
);

define('SITE_STATE_PRODUCTION', 1);
define('SITE_STATE_DEBUG', 2);
# hits
define('HITKIND_ALBUM',101);
# how many last-visited urls store in session
define('TRACE_URL_COUNT', 30);
# picture to cart
define('CART_PICTURE',101);
define('CART_PRODUCT',102);

#payment types
define( 'PAYMENTTYPE_CREDITCARD', 1 ); // \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_CREDITCARD
define( 'PAYMENTTYPE_CHECKBYINTERNET', 2 ); // \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_CHECKBYINTERNET
define( 'PAYMENTTYPE_MAILINCHECK', 3 ); // \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_MAILINCHECK
define( 'PAYMENTTYPE_TEST', 4 ); // \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_TEST
define( 'PAYMENTTYPE_PAYPAL', 5 ); // \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_PAYPAL
define( 'PAYMENTTYPE_TEST_PAYPAL', 6 ); // \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_TEST_PAYPAL
define( 'PAYMENTTYPE_TEST_CREDITCARD', 7 ); // \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_TEST_CREDITCARD
define( 'PAYMENTTYPE_APPSTORE', 8 ); // \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_APPSTORE
define( 'PAYMENTTYPE_ANDROIDMARKET', 9 ); // \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_ANDROIDMARKET
define( 'PAYMENTTYPE_BITCOIN', 10 ); // \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_BITCOIN
define( 'PAYMENTTYPE_RECURLY', 11 ); // \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_RECURLY
define( 'PAYMENTTYPE_BUSINESS_BALANCE', 12 ); // \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_BUSINESS_BALANCE
define( 'PAYMENTTYPE_ETHEREUM', 13 ); // \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_ETHEREUM
define( 'PAYMENTTYPE_STRIPE', 14 );
define('PAYMENTTYPE_QSTRANSCATION', 20);
define('PAYMENTTYPE_STRIPE_INTENT', 21);

global $arPaymentType;
$arPaymentType = array(
	PAYMENTTYPE_CREDITCARD => "<img src='/lib/images/cc/visa.gif'>&nbsp;<img src='/lib/images/cc/mastercard.gif'>&nbsp;<img src='/lib/images/cc/amex.gif'>&nbsp;<img src='/lib/images/cc/discover.gif'>",
	PAYMENTTYPE_PAYPAL => "<img src='/lib/images/cc/payPal.gif'> Save time. Check out securely. <br>Pay without sharing your financial information.",
	PAYMENTTYPE_MAILINCHECK => "Mail In a Check",
);
global $arPaymentTypeName;
$arPaymentTypeName = array(
	"" => "Free",
	PAYMENTTYPE_CREDITCARD => "Credit Card",
	PAYMENTTYPE_STRIPE => "Stripe (old)",
	PAYMENTTYPE_STRIPE_INTENT => "Credit Card",
	PAYMENTTYPE_CHECKBYINTERNET => "Check by internet",
	PAYMENTTYPE_MAILINCHECK => "Mail in check",
	PAYMENTTYPE_TEST => "Test",
	PAYMENTTYPE_PAYPAL => "PayPal",
	PAYMENTTYPE_TEST_PAYPAL => "Test PayPal",
	PAYMENTTYPE_TEST_CREDITCARD => "Test Credit Card",
	PAYMENTTYPE_APPSTORE  => "App Store (iOS)",
	PAYMENTTYPE_ANDROIDMARKET  => "Google Play",
	PAYMENTTYPE_BITCOIN  => "Bitcoin",
    PAYMENTTYPE_ETHEREUM  => "Ethereum",
	PAYMENTTYPE_RECURLY  => "Credit Card",
	PAYMENTTYPE_BUSINESS_BALANCE  => "Business balance",
    PAYMENTTYPE_QSTRANSCATION => 'QsTransaction'
);

define( 'OHIO_TAX', 6.75 );

global $arPrefix;
$arPrefix = array(
	"" => "Select",
	"Dr." => "Dr.",
	"Miss" => "Miss",
	"Mr." => "Mr.",
	"Mrs." => "Mrs.",
	"Ms." => "Ms.",
	"Prof." => "Prof."
);

global $arSuffix;
$arSuffix = array(
	"" => "Select",
	"II" => "II",
	"III" => "III",
	"IV" => "IV",
	"CPA" => "CPA",
	"D.D.S." => "D.D.S.",
	"Esq." => "Esq.",
	"J.D." => "J.D.",
	"Jr." => "Jr.",
	"LL.D." => "LL.D.",
	"M.D." => "M.D.",
	"Ph.D." => "Ph.D.",
	"Ret." => "Ret.",
	"RN" => "RN",
	"Sr." => "Sr."
);

#Availability:
define('IN_STOCK', 1);
define('OUT_OF_STOCK', 2);
define('BACKORDERED', 3);

#Availability array:
global $availabilityAr;
$availabilityAr = array(
	IN_STOCK		=> "In Stock",
	OUT_OF_STOCK	=> "Out of Stock",
	BACKORDERED		=> "Backordered"
);

# Csrf checks
define('CSRF_CHECK_OFF',     0);
define('CSRF_CHECK_WARNING', 1);
define('CSRF_CHECK_STRICT',  2);

// config
define('CONFIG_SITE_STATE', 'site state');
define('CONFIG_DETECT_USER_LOCATION', 'detect user location');
define('CONFIG_CHAT_USER_PING_TTL', 'chat user ping ttl' );
define('CONFIG_CHAT_INVITE_TTL', 'chat invite ttl' );
define('CONFIG_CHAT_USER_ONLINE_TTL', 'chat user online ttl' );
define('CONFIG_CHAT_PRELOAD_COUNT', 'chat preload count' );
define('AVAILABILITY_ARRAY', 'product availability array' );
define('CONFIG_PEPHOTO_EMAILS', 'pephoto emails' );
define('CONFIG_PAYPAL_PROFILE_PATH', 'paypal profile path');
define('CONFIG_CONTACT_SCRIPT', '/contact');
define('CONFIG_FONT_PATH', 'fonts location');
define('CONFIG_SALES_EMAIL', 'sales email');
define('CONFIG_ERROR_EMAIL', 'error email');
define('CONFIG_SECURE_EMAIL', 'secure email');
define('CONFIG_RESIZE_SLIDE', 'resize slide');
define('CONFIG_HTML_EDITOR_CLASS', 'html editor class');
define('CONFIG_BCC_EMAIL', 'bcc email');
define('CONFIG_CONTACT_BCC', 'contact bcc');
define('CONFIG_CONNECTION_CLASS', 'connection class');
define('CONFIG_CONNECTION_ERROR_HANDLER', 'connection error handler');
define('CONFIG_THROUGH_PROXY', 'through proxy');
define('CONFIG_TEST_EMAIL', 'test email');
define('CONFIG_EMAIL_HANDLER', 'email handler');
define('CONFIG_BOOKING_SMTP', 'booking smtp');
define('CONFIG_FORM_CSRF_CHECK', 'form csrf check');
define('CONFIG_PASSWORD_ENCODING', 'password encoding');
define('CONFIG_BRUTEFORCE_IP_LOCKOUT', 'bruteforce ip lockout');
define('CONFIG_HTTPS_ONLY', 'https only');

global $Config;
$Config = array(
	CONFIG_SITE_STATE => SITE_STATE_PRODUCTION,
	CONFIG_DETECT_USER_LOCATION => True,
	CONFIG_CHAT_USER_PING_TTL => 60,
	CONFIG_CHAT_USER_ONLINE_TTL => 20,
	CONFIG_CHAT_INVITE_TTL => 60,
	CONFIG_CHAT_PRELOAD_COUNT => 50,
	CONFIG_PEPHOTO_EMAILS => 'lisa@pephoto.com, info@itlogy.com, henry@pephoto.com',
	AVAILABILITY_ARRAY => array(IN_STOCK => "In Stock", OUT_OF_STOCK => "Out of Stock", BACKORDERED => "Backordered"),
	CONFIG_PAYPAL_PROFILE_PATH => '/usr/paypal/cert',
	CONFIG_CONTACT_SCRIPT => '/contact',
	CONFIG_SALES_EMAIL => 'info@itlogy.com',
	CONFIG_ERROR_EMAIL => 'error@itlogy.com',
	CONFIG_SECURE_EMAIL => 'alexi@itlogy.com',
	CONFIG_CONTACT_BCC => 'alexi@itlogy.com',
	CONFIG_BCC_EMAIL => 'error@itlogy.com',
	CONFIG_RESIZE_SLIDE => true,
	CONFIG_HTML_EDITOR_CLASS => 'TCKEditorFieldManager',
	CONFIG_CONNECTION_CLASS => 'TMySQLConnection',
	CONFIG_THROUGH_PROXY => false,
    CONFIG_FORM_CSRF_CHECK => CSRF_CHECK_OFF,
    CONFIG_PASSWORD_ENCODING => 'md5',
    CONFIG_BRUTEFORCE_IP_LOCKOUT => false,
    CONFIG_HTTPS_ONLY => false
);

define('PARAM_TYPE_INTEGER', 1 );
define('PARAM_TYPE_FLOAT', 2 );
define('PARAM_TYPE_STRING', 3 );
define('PARAM_TYPE_TEXT', 4 );

// email state
define('EMAIL_UNVERIFIED', 0);
define('EMAIL_VERIFIED', 1);
define('EMAIL_NDR', 2);

// picture sizes
define('PICTURE_SIZE_DOWNLOAD_ONLY', 1);

//Your session has expired. Please start again
const SESSION_HAS_EXPIRED = 'Your session has expired. Please start again';

// return config value
function ConfigValue( $sIndex )
{
	global $Config;
	if( !isset( $Config[$sIndex] ) )
		DieTrace("Unknown config index use CONFIG_ constants");
	return $Config[$sIndex];
}

// determine os type
function IsUnix()
{
	return !isset( $_SERVER['SERVER_SOFTWARE'] ) || !preg_match( '/Microsoft/i', $_SERVER['SERVER_SOFTWARE'] );
}

if(!defined('E_DEPRECATED'))
	define('E_DEPRECATED', 8192);

?>
