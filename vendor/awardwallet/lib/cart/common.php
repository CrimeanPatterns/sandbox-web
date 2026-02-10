<?

if( file_exists( __DIR__."/../../kernel/transactionFunctions.php" ) )
	require_once( __DIR__."/../../kernel/transactionFunctions.php" );

require_once __DIR__.'/../geoFunctions.php';

// return array of form fields, suitable for billing address editing
function CreateAddressFields( $sCheckScriptCondition, $sPage, $sOnGetRequired, $sTablePrefix, $sFieldPrefix )
{
	global $nID, $nLastAddressID, $sPath;
	$arCountries = GetCountryOptions($nUSA, $nCanada);
	$nCountryID = $nUSA;
	$nStateID = "";

	if(!empty($detectedCountryID))
		$nCountryID = $detectedCountryID;
	if(!empty($detectedStateID))
		$nStateID = $detectedStateID;
	if( isset( $_SESSION['UserID'] ) ) {
		if( isset( $nID ) && ( $nID > 0 ) )
			$q = new TQuery( "select a.CountryID, a.StateID, a.City, s.Name as StateName, a.Zip, c.Name as CountryName,
			u.FirstName, u.LastName, u.Phone1, u.Email, a.Address1, a.Address2 from Usr u, Country c, State s, BillingAddress a where a.UserID = u.UserID and a.BillingAddressID = $nID and u.CountryID = c.CountryID and s.StateID = u.StateID and u.UserID = {$_SESSION["UserID"]}" );
		else
			$q = new TQuery( "select u.CountryID, u.StateID, u.City, s.Name as StateName, u.Zip, c.Name as CountryName,
			u.FirstName, u.LastName, u.Phone1, u.Email, u.Address1, u.Address2 from Usr u, Country c, State s where u.CountryID = c.CountryID and s.StateID = u.StateID and u.UserID = {$_SESSION["UserID"]}" );
		if( $_SERVER['REQUEST_METHOD'] == "POST" )
		{
			$nCountryID = intval( ArrayVal($_POST, $sFieldPrefix."CountryID" ));
			if( !isset( $arCountries[$nCountryID] ) )
				$nCountryID = $q->Fields["CountryID"];
		}
		else
			if(!empty($q->Fields["CountryID"]))
				$nCountryID = $q->Fields["CountryID"];
	}
	// state field
	require_once( "$sPath/lib/classes/TStateFieldManager.php" );
	$objStateFieldManager = new TStateFieldManager();
	$objStateFieldManager->CountryField = "{$sFieldPrefix}CountryID";
	$objStateFieldManager->DBFieldName = $sFieldPrefix . "StateID";
	// get user
	if( isset( $_SESSION['UserID'] ) ) {
		$qUser = new TQuery( "select * from Usr where UserID = {$_SESSION["UserID"]}" );
	}
	// get last added address
	$q = new TQuery("show tables like '{$sTablePrefix}Address'");
	if(!$q->EOF){
		if( isset( $_SESSION['UserID'] ) ) {
			$qAddress = new TQuery( "select max( {$sTablePrefix}AddressID ) as ID from {$sTablePrefix}Address where UserID = {$_SESSION["UserID"]}" );
			$nLastAddressID = trim($qAddress->Fields["ID"]);
		}
		else
			$nLastAddressID = 0;
	}
	if( isset( $_SESSION['UserID'] ) ){
		if ( ( $qUser->Fields["CountryID"] != "" ) && ( $qUser->Fields["StateID"] != "" ) ) {
			$nStateID = $qUser->Fields["StateID"];
			$bHaveStates = Lookup( "Country", "CountryID", "HaveStates", $qUser->Fields["CountryID"], True );
			if ( !$bHaveStates ) {
				$nStateID = Lookup("State", "StateID", "Name", $nStateID, True);
			}
		}
	}
 	$arFields = array(
		$sFieldPrefix . "AddressName" => array(
			"Caption" => "Address Nick",
			"Type" => "string",
			"Note" => "i.e. \"Home\" or \"Work\"",
			"InputAttributes" => "style=\"width: 200px;\"",
			"Size" => 128,
			"Page" => $sPage,
			"CheckScriptCondition" => $sCheckScriptCondition,
			"Cols" => 20
			),
		$sFieldPrefix . "FirstName" => array(
			"Caption" => "First Name",
			"Type" => "string",
			"InputAttributes" => "style=\"width: 200px;\"",
			"Size" => 40,
			"Page" => $sPage,
			"Required" => True,
			"OnGetRequired" => $sOnGetRequired,
			"CheckScriptCondition" => $sCheckScriptCondition,
			"Value" => ( isset( $_SESSION['UserID'] ) && $qUser->Fields["FirstName"] != 'Business' ? $qUser->Fields["FirstName"] : "" ),
			"Cols" => 20
			),
		$sFieldPrefix . "LastName" => array(
			"Caption" => "Last Name",
			"Type" => "string",
			"InputAttributes" => "style=\"width: 200px;\"",
			"Size" => 40,
			"Page" => $sPage,
			"Required" => True,
			"OnGetRequired" => $sOnGetRequired,
			"CheckScriptCondition" => $sCheckScriptCondition,
			"Value" => ( isset( $_SESSION['UserID'] ) && $qUser->Fields["LastName"] != 'Account' ? $qUser->Fields["LastName"] : "" ),
			"Cols" => 20
			),
		$sFieldPrefix . "Address1" => array(
			"Caption" => "Street Address 1",
			"Type" => "string",
			"InputAttributes" => "style=\"width: 200px;\"",
			"Size" => 128,
			"Required" => True,
			"OnGetRequired" => $sOnGetRequired,
			"Page" => $sPage,
			"CheckScriptCondition" => $sCheckScriptCondition,
			"Value" => ( isset( $_SESSION['UserID'] ) ? $qUser->Fields["Address1"] : "" ),
			"Cols" => 20
			),
		$sFieldPrefix . "Address2" => array(
			"Caption" => "Street Address 2",
			"Type" => "string",
			"InputAttributes" => "style=\"width: 200px;\"",
			"Size" => 128,
			"Page" => $sPage,
			"CheckScriptCondition" => $sCheckScriptCondition,
			"Value" => ( isset( $_SESSION['UserID'] ) ? $qUser->Fields["Address2"] : "" ),
			"Cols" => 20
			),
		$sFieldPrefix . "City" => array(
			"Type" => "string",
			"Caption" => "City",
			"InputAttributes" => "style=\"width: 200px;\"",
			"Size" => 80,
			"Cols" => 20,
			"Page" => $sPage,
			"CheckScriptCondition" => $sCheckScriptCondition,
			"OnGetRequired" => $sOnGetRequired,
			"Value" => ( isset( $_SESSION['UserID'] ) ? $qUser->Fields["City"] : "" ),
			"Note" => "Enter APO, FPO or DPO for APO addresses",
			"Required" => True
			),
		$sFieldPrefix . "CountryID" => array(
			"Caption" => "Country",
			"Type" => "integer",
			"InputType" => "select",
			"InputAttributes" => "style=\"width: 222px;\" onchange=\"this.form.DisableFormScriptChecks.value=1; var stateInput = this.form.{$sFieldPrefix}StateID; if(  stateInput.type=='select' ) stateInput.selectedIndex = 0; else stateInput.value = ''; this.form.submit();\"",
			"Value" => $nCountryID,
			"DefaultValue" => $nCountryID,
			"Required" => True,
			"OnGetRequired" => $sOnGetRequired,
			"Page" => $sPage,
			"CheckScriptCondition" => $sCheckScriptCondition,
			"Options" => $arCountries
			),
		$sFieldPrefix . "StateID" => array(
			"Caption" => "State/province",
			"Type" => "integer",
			"InputAttributes" => "style=\"width: 222px;\"",
			"Size" => 40,
			"LocationField" => True,
			"Cols" => 20,
			"Required" => True,
			"Manager" => $objStateFieldManager,
			"CheckScriptCondition" => $sCheckScriptCondition,
			"Value" => $nStateID,
			"OnGetRequired" => $sOnGetRequired,
			"Page" => $sPage,
			),
		$sFieldPrefix . "Zip" => array(
			"Caption" => "Zip/Postal code",
			"Type" => "string",
			"InputAttributes" => "style=\"width: 200px;\"",
			"Size" => 40,
			"Cols" => 20,
			"Page" => $sPage,
			"CheckScriptCondition" => $sCheckScriptCondition,
			"Value" => ( isset( $_SESSION['UserID'] ) ? $qUser->Fields["Zip"] : "" ),
			"OnGetRequired" => $sOnGetRequired,
			"Required" => True
			),
		);
	if(isset($nLastAddressID)){
		$arFields += array(
			$sTablePrefix . "AddressID" => array(
				"Type" => "integer",
				"InputType" => "radio",
				"Options" => array( "0" => "0" ) + ( isset( $_SESSION['UserID'] ) ? SQLToArray( "select {$sTablePrefix}AddressID from {$sTablePrefix}Address where UserID = {$_SESSION["UserID"]}", $sTablePrefix . "AddressID", $sTablePrefix . "AddressID" ) : array() ),
				"Required" => True,
				"Page" => "Confirmation",
				"Value" => ArrayVal( $_GET, "PageBy{$sTablePrefix}AddressID", $nLastAddressID ),
				"CheckScripts" => False,
			),
		);
	}
	return $arFields;
}

// return, is new billing address required
function NewBillingAddressRequired( $sField, &$arField )
{
	global $objForm;
	return ( $objForm->Fields["BillingAddressID"]["Value"] == "0" );
}

// return, is new billing address required
function NewShippingAddressRequired( $sField, &$arField )
{
	global $objForm;
	return ( $objForm->Fields["ShippingAddressID"]["Value"] == "0" );
}

// save new address to database
function SaveAddress( $sPrefix )
{
	global $objForm, $Connection;
	if( $objForm->Fields[$sPrefix."AddressID"]["Value"] == "0" )
	{
		$objForm->CalcSQLValues();
		$sAddressName = $objForm->Fields["{$sPrefix}AddressName"]["SQLValue"];
		if($sAddressName == 'null')
			$sAddressName = $objForm->Fields["{$sPrefix}Address1"]["SQLValue"];
		$q = new TQuery( "select 1 from {$sPrefix}Address where UserID = {$_SESSION["UserID"]}
		and AddressName = {$sAddressName}" );
		$arValues = array(
			"AddressName" => $sAddressName,
			"FirstName" => $objForm->Fields["{$sPrefix}FirstName"]["SQLValue"],
			"LastName" => $objForm->Fields["{$sPrefix}LastName"]["SQLValue"],
			"Address1" => $objForm->Fields["{$sPrefix}Address1"]["SQLValue"],
			"Address2" => $objForm->Fields["{$sPrefix}Address2"]["SQLValue"],
			"City" => $objForm->Fields["{$sPrefix}City"]["SQLValue"],
			"StateID" => $objForm->Fields["{$sPrefix}StateID"]["SQLValue"],
			"CountryID" => $objForm->Fields["{$sPrefix}CountryID"]["SQLValue"],
			"Zip" => $objForm->Fields["{$sPrefix}Zip"]["SQLValue"],
			"UserID" => $_SESSION["UserID"],
		);
		if( $objForm->Fields["{$sPrefix}StateID"]["InputType"] == "text" )
		{
			// save state
			$sState = "'" . addslashes( $objForm->Fields["{$sPrefix}StateID"]["Value"] ) . "'";
			$nCountryID = $objForm->Fields["{$sPrefix}CountryID"]["Value"];
			$qState = new TQuery( "select * from State
			where CountryID = $nCountryID and Name = $sState" );
			if( $qState->EOF )
			{
				// save new state
				$nStateID = TableMax( "State", "StateID" ) + 1;
				$Connection->Execute( InsertSQL( "State", array(
					"StateID" => $nStateID,
					"CountryID" => $nCountryID,
					"Name" => $sState,
					"Code" => $nStateID,
				) ) );
			}
			else
				$nStateID = $qState->Fields["StateID"];
			// update state id
			$arValues["StateID"] = $nStateID;
			if( $sPrefix == "Billing" )
				$_SESSION['Address']['StateID'] = $nStateID;
			if( $sPrefix == "Shipping" )
				$_SESSION['ShippingAddress']['StateID'] = $nStateID;
		}
		if( $q->EOF )
			$Connection->Execute( InsertSQL( "{$sPrefix}Address", $arValues ) );
		else
			$Connection->Execute( UpdateSQL( "{$sPrefix}Address",
			array(
				"AddressName" => $sAddressName,
				"UserID" => $_SESSION["UserID"]
			),
			$arValues ) );
	}
}

// open paypal services
/**
 * @param string $nPaymentType
 * @param array $options - array('code' => ..., 'password' => ...)
 * @return CallerServices
 */
function OpenPayPal( $nPaymentType, $options = array() )
{
	error_reporting( error_reporting() & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT );
	$handler =& ProfileHandler_File::getInstance(array (
	  'path' => PAYPAL_PROFILE_PATH,
	  'charset' => 'iso-8859-1',
	));
	if (PayPal::isError($handler))
	    DieTrace( "Error opening paypal service: ".$handler->getMessage() );
	switch ( $nPaymentType )
	{
		case PAYMENTTYPE_CREDITCARD:
		case PAYMENTTYPE_PAYPAL:
			$sCode = (isset($options['code'])) ? $options['code'] : PAYPAL_PROFILE_CODE;
			$sPassword = (isset($options['password'])) ? $options['password'] : PAYPAL_PASSWORD;
			break;
		case PAYMENTTYPE_TEST_CREDITCARD:
		case PAYMENTTYPE_TEST_PAYPAL:
			$sCode = PAYPAL_TEST_PROFILE_CODE;
			$sPassword = PAYPAL_TEST_PASSWORD;
			break;
		default:
			DieTrace( "Invalid payment type" );
	}
	$profile =& APIProfile::getInstance($sCode, $handler);
	if (PayPal::isError($profile))
	    DieTrace( "Error opening paypal profile: ".$profile->getMessage() );
	$profile->setAPIPassword( $sPassword );
	$caller =& PayPal::getCallerServices($profile);
	if (PayPal::isError($caller))
	    DieTrace( "Error getting paypal services: ".$caller->getMessage() );
	$arrayCiphers = array(
		'DHE-RSA-AES256-SHA',
		'DHE-DSS-AES256-SHA',
		'AES256-SHA:KRB5-DES-CBC3-MD5',
		'KRB5-DES-CBC3-SHA',
		'EDH-RSA-DES-CBC3-SHA',
		'EDH-DSS-DES-CBC3-SHA',
		'DES-CBC3-SHA:DES-CBC3-MD5',
		'DHE-RSA-AES128-SHA',
		'DHE-DSS-AES128-SHA',
		'AES128-SHA:RC2-CBC-MD5',
		'KRB5-RC4-MD5:KRB5-RC4-SHA',
		'RC4-SHA:RC4-MD5:RC4-MD5',
		'KRB5-DES-CBC-MD5',
		'KRB5-DES-CBC-SHA',
		'EDH-RSA-DES-CBC-SHA',
		'EDH-DSS-DES-CBC-SHA:DES-CBC-SHA',
		'DES-CBC-MD5:EXP-KRB5-RC2-CBC-MD5',
		'EXP-KRB5-DES-CBC-MD5',
		'EXP-KRB5-RC2-CBC-SHA',
		'EXP-KRB5-DES-CBC-SHA',
		'EXP-EDH-RSA-DES-CBC-SHA',
		'EXP-EDH-DSS-DES-CBC-SHA',
		'EXP-DES-CBC-SHA',
		'EXP-RC2-CBC-MD5',
		'EXP-RC2-CBC-MD5',
		'EXP-KRB5-RC4-MD5',
		'EXP-KRB5-RC4-SHA',
		'EXP-RC4-MD5:EXP-RC4-MD5'
	);
	$caller->setOpt('curl', CURLOPT_SSL_CIPHER_LIST, implode(':', $arrayCiphers));
	$caller->setOpt('curl', CURLOPT_SSLVERSION, 0);
	return $caller;
}

// get selected address
function GetAddressInfo( $sPrefix )
{
	global $objForm;
	// get address info
	if( !isset($objForm->Fields["{$sPrefix}AddressID"])
	|| ($objForm->Fields["{$sPrefix}AddressID"]["Value"] == "0")
	|| ($objForm->Fields["{$sPrefix}AddressID"]["Value"] == ""))
	{
		$arAddress = array();
		if(isset($objForm->Fields["{$sPrefix}AddressName"]["Value"])){
			$arAddress["AddressName"] = $objForm->Fields["{$sPrefix}AddressName"]["Value"];
		}
		if(!isset($objForm->Fields["{$sPrefix}CountryID"]["Value"])){
			$objForm->Fields["{$sPrefix}CountryID"]["Value"] = $objForm->Fields["{$sPrefix}CountryID"]["DefaultValue"];			var_dump($objForm->Fields["{$sPrefix}CountryID"]["Value"]);
		}
		$arAddress += array(
			"FirstName" => $objForm->Fields["{$sPrefix}FirstName"]["Value"],
			"LastName" => $objForm->Fields["{$sPrefix}LastName"]["Value"],
			"Address1" => $objForm->Fields["{$sPrefix}Address1"]["Value"],
			"Address2" => $objForm->Fields["{$sPrefix}Address2"]["Value"],
			"City" => $objForm->Fields["{$sPrefix}City"]["Value"],
			"StateName" => $objForm->Fields["{$sPrefix}StateID"]["Value"],
			"StateID" => $objForm->Fields["{$sPrefix}StateID"]["Value"],
			"CountryName" => ArrayVal( $objForm->Fields["{$sPrefix}CountryID"]["Options"], $objForm->Fields["{$sPrefix}CountryID"]["Value"] ),
			"CountryID" => $objForm->Fields["{$sPrefix}CountryID"]["Value"],
			"Zip" => $objForm->Fields["{$sPrefix}Zip"]["Value"],
		);
		if(!isset($objForm->Fields["{$sPrefix}CountryID"]["Value"])){
			$objForm->Fields["{$sPrefix}CountryID"]["Value"] = $objForm->Fields["{$sPrefix}CountryID"]["DefaultValue"];			var_dump($objForm->Fields["{$sPrefix}CountryID"]["Value"]);
		}
		if($objForm->Fields["{$sPrefix}CountryID"]["Value"] > 0)
			$bHaveStates = Lookup( "Country", "CountryID", "HaveStates", $objForm->Fields["{$sPrefix}CountryID"]["Value"], True );
		else
			$bHaveStates = False;
		if( $bHaveStates )
			$arAddress["StateName"] = ArrayVal( $objForm->Fields["{$sPrefix}StateID"]["Options"], $objForm->Fields["{$sPrefix}StateID"]["Value"] );
	}
	else
		if( $objForm->Fields["{$sPrefix}AddressID"]["Value"] > 0 )
		{
			$q = new TQuery( "select ba.*, s.Name as StateName, c.Name as CountryName from {$sPrefix}Address ba, Country c, State s where ba.UserID = {$_SESSION["UserID"]}
			and ba.{$sPrefix}AddressID = ".intval($objForm->Fields["{$sPrefix}AddressID"]["Value"])."
			and ba.StateID = s.StateID and ba.CountryID = c.CountryID" );
			$arAddress = $q->Fields;
		}
		else
			$arAddress = false;
	$q = new TQuery("select * from Country where CountryID = {$arAddress["CountryID"]}");
	assert(!$q->EOF);
	$bHaveStates = $q->Fields["HaveStates"] == "1";
	if( ( ArrayVal( $arAddress, "StateName" ) != "" ) && $bHaveStates )
		$arAddress["StateCode"] = Lookup( "State", "Name", "Code", "'" . addslashes( $arAddress["StateName"] ) . "'" );
	else
		$arAddress["StateCode"] = $arAddress["StateName"];
	if(isset($q->Fields["Code"]) && ($q->Fields["Code"] != ""))
		$arAddress["CountryCode"] = $q->Fields["Code"];
	else{
		if( ArrayVal( $arAddress, "CountryName" ) != "" )
			$arAddress["CountryCode"] = strtoupper( substr( $arAddress["CountryName"], 0, 2 ) );
		if( ArrayVal( $arAddress, "CountryName" ) == "United States" )
			$arAddress["CountryCode"] = "US";
		if( ArrayVal( $arAddress, "CountryName" ) == "Mexico" )
			$arAddress["CountryCode"] = "MX";
		if( ArrayVal( $arAddress, "CountryName" ) == "Netherlands, the" )
			$arAddress["CountryCode"] = "NL";
		if( ArrayVal( $arAddress, "CountryName" ) == "Netherlands Antilles" )
			$arAddress["CountryCode"] = "AN";
	}
	return $arAddress;
}

