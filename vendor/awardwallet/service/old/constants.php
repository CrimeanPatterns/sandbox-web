<?php
// this file contains constants common for awardwallet, wsdlawardwallet, email, loyalty
// you could autoload it by requiring class below, like \AwardWalletOldConstants::load() 

class AwardWalletOldConstants {
 
    // this method just for autoloading
    public static function load() {}
}

// Coupon state
define( "COUPON_VALID", 1 );
define( "COUPON_EXPIRES_SOON", 2 );
define( "COUPON_EXPIRED", 3 );
define( "COUPON_NO_EXPIRATION", 4 );

// Provider balanca n/a value
//define( 'ACCOUNT_BALANCE_NA', 'n/a' );

// account state
define( "ACCOUNT_ENABLED", 1 );
define( "ACCOUNT_DISABLED", 0 );
define( "ACCOUNT_PENDING", -1);
define( "ACCOUNT_IGNORED", -2);

// account error code
define( "ACCOUNT_UNCHECKED", 0 );
define( "ACCOUNT_CHECKED", 1 );
define( "ACCOUNT_INVALID_PASSWORD", 2 );
define( "ACCOUNT_LOCKOUT", 3 );
define( "ACCOUNT_PROVIDER_ERROR", 4 );
define( "ACCOUNT_PROVIDER_DISABLED", 5 );
define( "ACCOUNT_ENGINE_ERROR", 6 );
define( "ACCOUNT_TIMEOUT", 11 ); // check take too many time. wsdl only.
define( "ACCOUNT_MISSING_PASSWORD", 7 );
define( "ACCOUNT_PREVENT_LOCKOUT", 8 );
define( "ACCOUNT_WARNING", 9 );
define( "ACCOUNT_QUESTION", 10 );
define( "ACCOUNT_INVALID_USER_INPUT", 11 ); // Only for account registrators and rewards purchasers/transferers
global $arAccountErrorCode;
$arAccountErrorCode = array(
	ACCOUNT_UNCHECKED => "Unchecked",
	ACCOUNT_CHECKED => "Checked",
	ACCOUNT_INVALID_PASSWORD => "Invalid password",
	ACCOUNT_LOCKOUT => "Lockout",
	ACCOUNT_PROVIDER_ERROR => "Provider error",
	ACCOUNT_PROVIDER_DISABLED => "Provider disabled",
	ACCOUNT_ENGINE_ERROR => "Engine error",
	ACCOUNT_MISSING_PASSWORD => "Missing password",
	ACCOUNT_PREVENT_LOCKOUT => "Prevent lockout",
	ACCOUNT_WARNING => "Warning",
	ACCOUNT_QUESTION => "Question",
	ACCOUNT_TIMEOUT => "Timeout",
);

// soap codes
define('SOAP_SUCCESS', 0);
define('SOAP_INVALID_KEY', 101);
define('SOAP_INVALID_PROVIDER', 102);
define('SOAP_BAD_LOGIN', 103);
define('SOAP_BAD_PASSWORD', 104);
define('SOAP_HTTPS_REQUIRED', 105);
define('SOAP_BAD_LOGIN2', 106);
define('SOAP_ACCOUNT_NOT_FOUND', 107);
define('SOAP_TIMEOUT', 108);
define('SOAP_BAD_TIMEOUT', 109);
define('SOAP_FAILURE', 110);
define('SOAP_BAD_PRIORITY', 111);
define('SOAP_BAD_CALLBACKURL', 112);
define('SOAP_BAD_RETRIES', 113);
define('SOAP_BAD_PASSWORDTYPE', 114);
define('SOAP_INVALID_REGION', 115);
define('SOAP_BAD_COUPON', 116);
define('SOAP_USERID_REQUIRED', 117);
define('SOAP_BAD_ANSWERS', 118);
define('SOAP_BAD_BROWSERSTATE', 119);
define('SOAP_BAD_LOGIN3', 120);
define('SOAP_BAD_ACCOUNTID', 121);
define('SOAP_BAD_PARAMS', 122);
define('SOAP_BAD_OPTIONS', 123);

// confirmation number checking
define('CONFNO_CHECKED', 1);
define('CONFNO_INVALID', 100);
define('CONFNO_NOTCHECKED', 101);

// provider kinds
define( "PROVIDER_KIND_AIRLINE", 1 );
define( "PROVIDER_KIND_HOTEL", 2 );
define( "PROVIDER_KIND_CAR_RENTAL", 3 );
define( "PROVIDER_KIND_TRAIN", 4 );
define( "PROVIDER_KIND_OTHER", 5 );
define( "PROVIDER_KIND_CREDITCARD", 6 );
define( "PROVIDER_KIND_SHOPPING", 7 );
define( "PROVIDER_KIND_DINING", 8 );
define( "PROVIDER_KIND_SURVEY", 9 );
define( "PROVIDER_KIND_CRUISES", 10 );
define( "PROVIDER_KIND_DOCUMENT", 11 );
define( "PROVIDER_KIND_PARKING", 12 );
global $arProviderKind;
$arProviderKind = array(
	PROVIDER_KIND_AIRLINE => "Airlines",
	PROVIDER_KIND_HOTEL => "Hotels",
	PROVIDER_KIND_CREDITCARD => "Credit Cards",
	PROVIDER_KIND_SHOPPING => "Shopping",
	PROVIDER_KIND_CAR_RENTAL => "Rentals",
	PROVIDER_KIND_DINING => "Dining",
	PROVIDER_KIND_TRAIN => "Trains",
    PROVIDER_KIND_CRUISES => "Cruises",
	PROVIDER_KIND_SURVEY => "Surveys",
    PROVIDER_KIND_PARKING => "Parking",
	PROVIDER_KIND_OTHER => "Other",
);

define( "PROVIDER_ENGINE_BASEBROWSER", 0 );//deprecated
define( "PROVIDER_ENGINE_CURL", 2 );
define( "PROVIDER_ENGINE_SELENIUM", 3 );

// provider state
define( "PROVIDER_RETAIL", -7 );
define( "PROVIDER_HIDDEN", -6 );
define( "PROVIDER_WSDL_ONLY", -5 );
define( "PROVIDER_TEST", -4 );
define( "PROVIDER_IN_BETA", -3 );
define( "PROVIDER_IN_DEVELOPMENT", -1 );
define( "PROVIDER_DISABLED", 0 );
define( "PROVIDER_ENABLED", 1 );
define( "PROVIDER_FIXING", 2 );
define( "PROVIDER_COLLECTING_ACCOUNTS", 3 );
define( "PROVIDER_CHECKING_OFF", 4 );
define( "PROVIDER_CHECKING_AWPLUS_ONLY", 5 );
define( "PROVIDER_CHECKING_EXTENSION_ONLY", 6 );
define( "PROVIDER_CHECKING_WITH_MAILBOX", 7 );
global $arProviderState;
$arProviderState = array(
	PROVIDER_COLLECTING_ACCOUNTS => "Collecting accounts",
	PROVIDER_IN_BETA => "Beta users only",
	PROVIDER_IN_DEVELOPMENT => "In development",
	PROVIDER_ENABLED => "Enabled",
	PROVIDER_FIXING => "Fixing",
	PROVIDER_CHECKING_OFF => "Checking off",
	PROVIDER_CHECKING_AWPLUS_ONLY => "Checking AWPlus only",
	PROVIDER_CHECKING_EXTENSION_ONLY => "Checking only through extension",
    PROVIDER_CHECKING_WITH_MAILBOX => "Checking with mailbox",
	PROVIDER_DISABLED => "Disabled",
	PROVIDER_TEST => "Test",
	PROVIDER_WSDL_ONLY => "WSDL Only",
	PROVIDER_HIDDEN => 'Hidden provider(e-mail parsing)',
	PROVIDER_RETAIL => 'Retail',
);
// For WSDL clients
global $arProviderStateForWsdlClients;
$arProviderStateForWsdlClients = array(
    PROVIDER_CHECKING_OFF,
    PROVIDER_ENABLED,
    PROVIDER_CHECKING_AWPLUS_ONLY,
    PROVIDER_CHECKING_WITH_MAILBOX,
);

// property kinds
define('PROPERTY_KIND_OTHER', 0);
define('PROPERTY_KIND_NUMBER', 1);
define('PROPERTY_KIND_EXPIRATION', 2);
define('PROPERTY_KIND_STATUS', 3);
define('PROPERTY_KIND_LIFETIME', 4);
define('PROPERTY_KIND_MEMBER_SINCE', 5);
define('PROPERTY_KIND_EXPIRING_BALANCE', 6);
define('PROPERTY_KIND_YTD_MILES', 7);
define('PROPERTY_KIND_YTD_SEGMENTS', 8);
define('PROPERTY_KIND_NEXT_ELITE_LEVEL', 9);
define('PROPERTY_KIND_MILES_TO_NEXT_LEVEL', 10);
define('PROPERTY_KIND_SEGMENTS_TO_NEXT_LEVEL', 11);
define('PROPERTY_KIND_NAME', 12);
define('PROPERTY_KIND_LAST_ACTIVITY', 13);
define('PROPERTY_KIND_MILES_TO_NEXT_REWARD', 14);
define('PROPERTY_KIND_STATUS_EXPIRATION', 15);
define('PROPERTY_KIND_MILES_TO_RETAIN_STATUS', 16);
define('PROPERTY_KIND_SEGMENTS_TO_RETAIN_STATUS', 17);
define('PROPERTY_KIND_FAMILY_BALANCE', 18);
define('PROPERTY_KIND_STATUS_MILES', 19);
global $arPropertiesKinds;
$arPropertiesKinds = [
    PROPERTY_KIND_OTHER                     => "Basic",
    PROPERTY_KIND_NAME                      => "Name",
    PROPERTY_KIND_NUMBER                    => "Account number",
    PROPERTY_KIND_STATUS                    => "Status",
    PROPERTY_KIND_STATUS_MILES              => "Status miles/points",
    PROPERTY_KIND_STATUS_EXPIRATION         => "Status expiration",
    PROPERTY_KIND_YTD_MILES                 => "YTD Miles/Points",
    PROPERTY_KIND_YTD_SEGMENTS              => "YTD Segments/Nights",
    PROPERTY_KIND_LIFETIME                  => "Lifetime miles/points",
    PROPERTY_KIND_MILES_TO_NEXT_LEVEL       => "Miles/Points needed to next level",
    PROPERTY_KIND_SEGMENTS_TO_NEXT_LEVEL    => "Segments/Nights needed to next level",
    PROPERTY_KIND_MILES_TO_RETAIN_STATUS    => "Miles/Points to retain status",
    PROPERTY_KIND_SEGMENTS_TO_RETAIN_STATUS => "Segments to retain status",
    PROPERTY_KIND_MILES_TO_NEXT_REWARD      => "Miles/Points needed for next reward",
    PROPERTY_KIND_MEMBER_SINCE              => "Member since",
    PROPERTY_KIND_LAST_ACTIVITY             => "Last activity",
    PROPERTY_KIND_EXPIRING_BALANCE          => "Expiring balance",
    PROPERTY_KIND_FAMILY_BALANCE            => "Family balance",
    PROPERTY_KIND_NEXT_ELITE_LEVEL          => "Next elite level",
    PROPERTY_KIND_EXPIRATION                => "Expiration",
];

define( 'CRYPTED_PASSWORD_LENGTH', 172 );

//Service Pricing
global $Config;
$Config["servicePrices"] = array(
	1 => 250,
	2 => 350,
	3 => 450,
);
#45 - Swiss International Airlines (Swiss Travel Club)
#73 - TUIfly.com - Bluemiles
#123 - Barclaycard Rewards
$Config["programsToExclude"] = array(45, 73, 123);

/*
select DISTINCT(p.ProviderID), p.DisplayName from Answer a
INNER JOIN Account ac ON ac.AccountID = a.AccountID
INNER JOIN Provider p ON p.ProviderID = ac.ProviderID
104	Capital One (Credit Cards)
76	Capital One (No Hassle)
98	Discover Rewards
103	US Bank (FlexPerks)
119	Talbots (Classing Awards | Red)
87	Chase online
*/
$Config["programsWithSecurityQuestions"] = array(104, 76, 98, 103, 119, 87);

define('CONFIG_TRAVEL_PLANS', 'travel plans');
global $arExtPropertyStructure;
$arExtPropertyStructure = array(
	"Discounts" => array(
		"Code" => array("FilterHTML" => true),
		"Name" => array("FilterHTML" => true),
	),
	"PricedEquips" => array(
		"Name" => array("FilterHTML" => true),
		"Charge" => array("FilterHTML" => true)
	),
	"Fees" => array(
		"Name" => array("FilterHTML" => true),
		"Charge" => array("FilterHTML" => true)
	),
	"Certificates" => array(
		"File" => array("Required" => false),
		"Caption" => array("FilterHTML" => true, "Required" => false),
		"Used" => array(),
		"Id" => array(),
		"ExpiresAt" => array(),
		"PurchasedAt" => array("Required" => false),
		"Status" => array("Required" => false)
	),
	"Picture" => array(
		"Url" => array()
	),
    "Locations" => array(
		"Url" => array(),
	),
    "DetailedAddress" => array(
        "AddressLine" => array("FilterHTML" => true),
        "CityName" => array("FilterHTML" => true, "Required" => false),
        "PostalCode" => array("FilterHTML" => true, "Required" => false),
        "StateProv" => array("FilterHTML" => true, "Required" => false),
        "Country" => array("FilterHTML" => true, "Required" => false),
    ),
    "DetectedCards" => array(
        "Code" => array(),
        "DisplayName" => array(),
        "CardDescription" => array(),
    ),
);

global $arDetailTable;
$arDetailTable = array(
	"T" => "Trip",
	"L" => "Rental",
	"R" => "Reservation",
	"D" => "Direction",
	"E" => "Restaurant",
);

define('DEEP_LINKING_SUPPORTED', 1);
define('DEEP_LINKING_NOT_SUPPORTED', 0);
define('DEEP_LINKING_UNKNOWN', 2);
global $arDeepLinking;
$arDeepLinking = array(
	"0" => "Not supported",
	"1" => "Supported",
	"2" => "Unknown",
);

define('TRIPS_PAST_DAYS', 3);
define('OFFERS_PAST_DAYS', 5);
define('TRIPS_DELETE_DAYS', 365 * 2);

if(isset($_SERVER['HTTP_HOST'])){
	if( $_SERVER['HTTP_HOST'] == 'awardwallet.local' )
		define('GOOGLE_MAPS_KEY', 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBSuuR8A2qe-omiXDlLjUIjHkdwf7xRW3EEGhYCFBdJVhBcQPTjXfL-5CA');
	if( $_SERVER['HTTP_HOST'] == 'test.awardwallet.com' )
		define('GOOGLE_MAPS_KEY', 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBTW00WWfNlIzaR1Bxvy1xkfV5IAABRIGcHkVsC28ZvP_3RqnQW5XIXifg');
	if( $_SERVER['HTTP_HOST'] == 'sprint.awardwallet.com' )
		define('GOOGLE_MAPS_KEY', 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBQsvP3FuTh6TsBZOKNw1fYSkKa5bRT61yPEriFnF2Dwhch-Xu8pZgIu4Q');
	if( $_SERVER['HTTP_HOST'] == 'business.awardwallet.com' )
		define('GOOGLE_MAPS_KEY', 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBSL6hmMcQuzt0veTmEueLWt_cw9PxSNaBB62lJ7xInsQYtfL04Jra3_GA');
	if( $_SERVER['HTTP_HOST'] == 'business.awardwallet.local' )
		define('GOOGLE_MAPS_KEY', 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBRrcWJDrBFsdpmMVmXmqAFIghnYERQFzIEJCuv0eC563y8X3f1EjlvZLQ');
	if( $_SERVER['HTTP_HOST'] == 'business.test.awardwallet.com' )
		define('GOOGLE_MAPS_KEY', 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBSJmk-f8WDe02iCNsv0K2pM13LB-xRRLHndrs_HJnG-TCFDMhMN4hNwww');
	if( $_SERVER['HTTP_HOST'] == 'business.sprint.awardwallet.com' )
		define('GOOGLE_MAPS_KEY', 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBRBiL2sKbyKDD9PNGdhnw1cmsP1axR12LRG1P9jqyPmqk8C8VICDW19Yw');
	if( $_SERVER['HTTP_HOST'] == 'iframe.test.awardwallet.com' )
		define('GOOGLE_MAPS_KEY', 'ABQIAAAAUVlD2zcUDrGzUH4tQpXBmBQfsWQ1NE5_A7DbcexwhbVHFNTUpxQitcJKCLERfLjpDRDRX-h_RikFrA');
	if( $_SERVER['HTTP_HOST'] == 'aw1.awardwallet.com' )
		define('GOOGLE_MAPS_KEY', 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBSxkLohAPwA9BUw7qAuDcI8QFZlzhSYR9sJ7oG_SPlA_lWXHr1HezNm6w');
	if( $_SERVER['HTTP_HOST'] == 'aw2.awardwallet.com' )
		define('GOOGLE_MAPS_KEY', 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBTjqYW3l-k94ZFd6pfrcF0pzrznEhTkpklklp4I1hnNqmY9FuXzFPfNtg');
}
if(!defined('GOOGLE_MAPS_KEY'))
	define('GOOGLE_MAPS_KEY', 'ABQIAAAAlVOIwT21Z_de6C9LywVBwBQ8lW716zarLhk6AW2Z7Z8rVIlJ-RT6v4k0uWRbkS5lu6idXzPj8LfaBg');

define('GOOGLE_PLACES_KEY', 'AIzaSyDPPMLHwk-ZO99S8XBjmzklq6vwWhaFwJc');

define('HOME_RADIUS', 50);

define('DATE_NEVER_EXPIRES', -1);

//moved for itineraries
define('MOVED_NONE', 0);
define('MOVED_AUTO', 1);
define('MOVED_MANUALLY', 2);

// Account.CheckedBy field
const CHECKED_BY_LOYALTY = 1; // background service, same server
const CHECKED_BY_USER = 2; // user request, apache process
const CHECKED_BY_WSDL = 3; // remote call to wsdl server
const CHECKED_BY_BROWSER = 4; // user request, apache process
const CHECKED_BY_EMAIL = 5; // emailed statement
const CHECKED_BY_SUBACCOUNT = 6; // subaccount from bank account
global $arCheckedBy;
$arCheckedBy = array(
    CHECKED_BY_LOYALTY => "Loyalty",
	CHECKED_BY_USER => "User",
	CHECKED_BY_WSDL => "WSDL",
	CHECKED_BY_BROWSER => "Browser",
	CHECKED_BY_EMAIL => "Email",
	CHECKED_BY_SUBACCOUNT => "Subaccount",
);

// Account.ActivityScore field
const ACTIVITY_SCORE_ON_EXPIRATION_DATE = -1;
const ACTIVITY_SCORE_ON_LAST_CHANGE_DATE = -2;
const ACTIVITY_SCORE_ON_NEAREST_TRIP_DATE = -3;
const ACTIVITY_SCORE_ON_TRIP_DELAY = -4;

// ProviderProperty.Visible field
const PROPERTY_INVISIBLE = 0;
const PROPERTY_VISIBLE = 1;
const PROPERTY_VISIBLE_TO_PARTNERS = 2;
const PROPERTY_INVISIBLE_TO_PARTNERS = 3;

// messages
const PROVIDER_NOT_SUPPORTED = 'Sorry, we currently do not support %s'; /*checked*/
const PROVIDER_CHECKING_VIA_EXTENSION_ONLY = 'At this time you can only update this account using a browser extension (must be enabled if you are using a laptop computer) or mobile app.';// refs #15071

// credit card descriptions
const C_CARD_DESC_ACTIVE = 'Active';
const C_CARD_DESC_CANCELLED = 'Cancelled';
const C_CARD_DESC_CLOSED = 'Closed';
const C_CARD_DESC_DO_NOT_EARN = 'Does not earn points';
const C_CARD_DESC_LINKED = 'This card is linked to the same reward balance as one of the other cards in your profile';
// for delta
const C_CARD_DESC_DELTA = 'Will be tracked via separate Delta account added to AwardWallet';
// aa
const C_CARD_DESC_AA = 'Should be tracked separately as a separate <a target = "_blank" href="/account/select-provider#/custom">American Airlines account added to AwardWallet</a>';
// alaskaair
const C_CARD_DESC_ALASKA_AIR = 'Should be tracked separately as a separate <a target = "_blank" href="/account/add/18">Alaska Airlines account added to AwardWallet</a>';
// for marriott (ritz, spg)
const C_CARD_DESC_MARRIOTT = 'Should be tracked separately as a separate <a target = "_blank" href="/account/add/17">Marriott account added to AwardWallet</a>';
// for hhonors
const C_CARD_DESC_HHONORS = 'Should be tracked separately as a separate <a target = "_blank" href="/account/add/22">Honors account added to AwardWallet</a>';
// Universal description
const C_CARD_DESC_UNIVERSAL = 'Should be tracked separately as a separate <a target = "_blank" href="/account/add/[Program_ID]">[Program] account added to AwardWallet</a>';
// for amex
const C_CARD_DESC_AMEX_LINKED = 'Linked to Membership Rewards Account #%s';

// Provider.CanCheckExpiration
const CAN_CHECK_EXPIRATION_NO = 0;
const CAN_CHECK_EXPIRATION_YES = 1;
const CAN_CHECK_EXPIRATION_NEVER_EXPIRES = 2;

// Provider.CanCheckConfirmation
const CAN_CHECK_CONFIRMATION_NO = 0;
const CAN_CHECK_CONFIRMATION_YES_SERVER = 1;
const CAN_CHECK_CONFIRMATION_YES_EXTENSION = 2;
const CAN_CHECK_CONFIRMATION_YES_EXTENSION_AND_SERVER = 3;

// Provider.AutoLogin field constants
const AUTOLOGIN_DISABLED = 0;
const AUTOLOGIN_SERVER = 1;
const AUTOLOGIN_MIXED = 2;
const AUTOLOGIN_EXTENSION = 3;

// Provider.iPhoneAutoLogin field constants
const MOBILE_AUTOLOGIN_DISABLED = 0;
const MOBILE_AUTOLOGIN_SERVER = 1;
const MOBILE_AUTOLOGIN_EXTENSION = 2;
const MOBILE_AUTOLOGIN_DESKTOP_EXTENSION = 3;

// Provider.ItineraryAutologin
const ITINERARY_AUTOLOGIN_DISABLED = 0;
const ITINERARY_AUTOLOGIN_ACCOUNT = 1;
const ITINERARY_AUTOLOGIN_CONFNO = 2;
const ITINERARY_AUTOLOGIN_BOTH = 3;

// trip categories
define('TRIP_CATEGORY_AIR', 1);
define('TRIP_CATEGORY_BUS', 2);
define('TRIP_CATEGORY_TRAIN', 3);
define('TRIP_CATEGORY_CRUISE', 4);
define('TRIP_CATEGORY_FERRY', 5);
define('TRIP_CATEGORY_TRANSFER', 6);

// hotel categories
define('HOTEL_CATEGORY_STAY', 1);
define('HOTEL_CATEGORY_SHOP', 2);
global $arTripCategoryTable;
$arTripCategoryTable = [
    TRIP_CATEGORY_AIR => 'Air',
    TRIP_CATEGORY_BUS => 'Bus',
    TRIP_CATEGORY_TRAIN => 'Train',
    TRIP_CATEGORY_CRUISE => 'Cruise',
    TRIP_CATEGORY_FERRY => 'Ferry',
];

define('EVENT_RESTAURANT', 1);
define('EVENT_MEETING', 2);
define('EVENT_SHOW', 3);
define('EVENT_EVENT', 4);
global $arEventType;
$arEventType = array(
	EVENT_RESTAURANT => "Restaurant",
	EVENT_MEETING => "Meeting",
	EVENT_SHOW => "Show",
	EVENT_EVENT => "Event",
);

// browser checking, Provider.CheckInBrowser field
define('CHECK_IN_SERVER', 0); // keep data and passwords in database
define('CHECK_IN_CLIENT', 1); // keep data and passwords in browser
define('CHECK_IN_MIXED', 2); // keep data and passwords both in browser and database
define('CHECK_IN_AUTOLOGIN', 3); // autologin only

// Account.ExpirationAutoSet field
define('EXPIRATION_USER', -1);
define('EXPIRATION_UNKNOWN', 0);
define('EXPIRATION_AUTO', 1);
define('EXPIRATION_FROM_SUBACCOUNT', 2);

// Provider.CanScanEmail
define('PROVIDER_SCAN_EMAIL_ENABLED', 1);
define('PROVIDER_SCAN_EMAIL_DISABLED', 0);

const TRIP_CODE_UNKNOWN = 'UnknownCode';
const CONFNO_UNKNOWN = 'UnknownNumber';
const MISSING_DATE = -1;
const FLIGHT_NUMBER_UNKNOWN = 'UnknownFlightNumber';
const AIRLINE_UNKNOWN = 'UnknownAirlineName';// only for email parsing

define('LOG_LEVEL_USER', 1);
define('LOG_LEVEL_HEADERS', 2);
define('LOG_LEVEL_EMERGENCY', 27);
define('LOG_LEVEL_ALERT', 28);
define('LOG_LEVEL_CRITICAL', 29);
define('LOG_LEVEL_ERROR', 3);
define('LOG_LEVEL_WARNING', 33);
define('LOG_LEVEL_NOTICE', 36);
define('LOG_LEVEL_NORMAL', 4);
define('LOG_LEVEL_INFO', 4);
define('LOG_LEVEL_DEBUG', 5);

global $arLogLevel;
$arLogLevel = array(
	LOG_LEVEL_USER => 'green',
	LOG_LEVEL_HEADERS => '#696969',
	LOG_LEVEL_ERROR => 'red',
	LOG_LEVEL_NORMAL => 'black', // do not change
    LOG_LEVEL_NOTICE => '#8A2BE2',
    LOG_LEVEL_DEBUG => '#686868'
);

global $arLogLevelText;
$arLogLevelText = array(
	LOG_LEVEL_USER => "info",
	LOG_LEVEL_HEADERS => "headers",
	LOG_LEVEL_EMERGENCY => "emergency",
	LOG_LEVEL_ALERT => "alert",
	LOG_LEVEL_CRITICAL => "critical",
	LOG_LEVEL_ERROR => "error",
	LOG_LEVEL_WARNING => "warning",
	LOG_LEVEL_NOTICE => "notice",
	LOG_LEVEL_NORMAL => "normal",
	LOG_LEVEL_INFO => "info",
	LOG_LEVEL_NORMAL => "debug",
);

// BarCodes
define('BAR_CODE_CUSTOM', 'custom'); // Custom
define('BAR_CODE_UPC_A', 'upca'); // UPC-A
define('BAR_CODE_CODE_39', 'code39'); // Code 39
define('BAR_CODE_EAN_13', 'ean13'); // EAN-13
define('BAR_CODE_CODE_128', 'code128'); // Code 128
define('BAR_CODE_INTERLEAVED', 'interleaved25'); // Interleaved 2 of 5
define('BAR_CODE_PDF_417', 'Pdf417'); // Pdf417
define('BAR_CODE_GS1_128', 'gs1128'); // gs1128
define('BAR_CODE_QR', 'qrcode');
global $barCodes;
$barCodes = [
    BAR_CODE_CUSTOM => "Custom",
    BAR_CODE_UPC_A => "UPC-A",
    BAR_CODE_CODE_39 => "Code 39",
    BAR_CODE_EAN_13 => "EAN-13",
    BAR_CODE_CODE_128 => "Code 128",
    BAR_CODE_INTERLEAVED => "Interleaved 2 of 5",
    BAR_CODE_PDF_417 => "PDF 417",
    BAR_CODE_GS1_128 => "GS1-128",
    BAR_CODE_QR => "QR Code",
];


// Provider constants. For faster usage search
const AA_PROVIDER_ID = 1;

// ListProviders options
const LIST_PROVIDERS_CHECK = 1;
const LIST_PROVIDERS_TRANSFER = 2;

const ACCOUNT_REQUEST_OPTION_PROVIDER_GROUP_CHECK = 'ProviderGroupCheck';

const PARSER_TIME_LIMIT = 600;

const TRANSFER_FIELDS_MAX_LENGTH = 4000;

const GEOTAG_TYPE_AIRPORT = 1;

const SOURCE_KIND_EMAIL = 'E';
