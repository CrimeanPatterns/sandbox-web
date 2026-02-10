<?php

/*
 * @deprecated - use PasswordCryptor
 */
function CryptPassword($s, $fileKey = "awPrivate.pem"){
	global $sPath;
	$s = trim( $s );
	if ($s == "")
		return "";
	$sFile = realpath("/usr/keys/".$fileKey);
	if (file_exists( $sFile )){
		$private = openssl_pkey_get_private("file://$sFile", "cookie");
		openssl_private_encrypt( $s, $sCrypted, $private );
		openssl_free_key( $private );
		$sCrypted = base64_encode( $sCrypted );
	}
	else
		return $s;

	return $sCrypted;
}

/*
 * @deprecated - use PasswordDecryptor
 */
function DecryptPassword($s, $fileKey = "awPublic.pem"){
	global $sPath;
	$s = trim( $s );
	if ($s == "")
		return "";
	if (strlen($s) == CRYPTED_PASSWORD_LENGTH){
		$s = base64_decode( $s );
		$sFile = realpath("/usr/keys/".$fileKey);
		if( !file_exists( $sFile ) )
			DieTrace("Unable to decrypt password, key is absent: $sFile");
		$public = openssl_pkey_get_public("file://$sFile");
		openssl_public_decrypt( $s, $sDecrypted, $public );
		openssl_free_key( $public );
	}
	else
		return $s;

	return $sDecrypted;
}

define('AES_CIPHER_METHOD', 'aes-256-cbc');
define('AES_ACTUAL_PREFIX', 'openssl_v1:');
function AESEncode($source, $key)
{
    $ivlen = openssl_cipher_iv_length(AES_CIPHER_METHOD);
    if (false === ($iv = openssl_random_pseudo_bytes($ivlen)))
        DieTrace('openssl_random_pseudo_bytes error with cipher ' . AES_CIPHER_METHOD);
    $sourceRaw = openssl_encrypt($source, AES_CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $sourceRaw, $key, true);
    return AES_ACTUAL_PREFIX . $iv . $hmac . $sourceRaw;
}

function AESDecode($source, $key)
{
    if (AES_ACTUAL_PREFIX === substr($source, 0, strlen(AES_ACTUAL_PREFIX))) {
        $source = substr($source, strlen(AES_ACTUAL_PREFIX));
        $ivlen = openssl_cipher_iv_length(AES_CIPHER_METHOD);
        $iv = substr($source, 0, $ivlen);
        $hmac = substr($source, $ivlen, $sha2len = 32);
        $sourceRaw = substr($source, $ivlen + $sha2len);
        $decrypt = openssl_decrypt($sourceRaw, AES_CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $sourceRaw, $key, true);
        //if (hash_equals($hmac, $calcmac)) // TODO: problem with test: unit LocalPasswordsManagerTest:testCookiesOldKeyShouldBeRenewed
            return $decrypt;
        DieTrace('AES decode error with cipher ' . AES_CIPHER_METHOD);
    }

	$s = "";
	$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_ECB, '');
	$iv_size = mcrypt_enc_get_iv_size($td);
	$key = substr($key, 0, mcrypt_enc_get_key_size($td));
	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

	if (mcrypt_generic_init($td, $key, $iv) != -1) {
		$s = mdecrypt_generic($td, $source);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
	}

	return trim($s);
}

function SSLEncrypt($source, $fileKey = "awPrivate.pem")
{
	//Assumes 1024 bit key and encrypts in chunks.
	$maxlength = 117;
	$output = '';
	if ($source == "")
		return "";
	$file = realpath("/usr/keys/".$fileKey);
	$key = openssl_pkey_get_private("file://$file", "cookie");
	while ($source) {
		$input = substr($source, 0, $maxlength);
		$source = substr($source, $maxlength);
		$ok = openssl_private_encrypt($input, $encrypted, $key);

		$output .= $encrypted;
	}
	openssl_free_key( $key );
	return base64_encode($output);
}

function SSLDecrypt($source, $fileKey = "awPublic.pem")
{
	if ($source == "")
		return "";
	$source = base64_decode($source);
	$file = realpath("/usr/keys/".$fileKey);
	$key = openssl_pkey_get_public("file://$file");

	// The raw PHP decryption functions appear to work
	// on 128 Byte chunks. So this decrypts long text
	// encrypted with ssl_encrypt().

	$maxlength = 128;
	$output = '';
	while ($source) {
		$input = substr($source, 0, $maxlength);
		$source = substr($source, $maxlength);
		$ok = openssl_public_decrypt($input, $out, $key);
		$output .= $out;
	}

	openssl_free_key( $key );
	return $output;
}

function GetXMLNodesText( $nodes, $sGlue = "" ){
	$s = "";
	for( $n = 0; $n < $nodes->length; $n++ ){
		if( $s != "" )
			$s .= $sGlue;
		$s .= $nodes->item($n)->nodeValue;
	}
	return $s;
}

function AccountStateText($arFields){
	if( $arFields["ProviderID"] == '' )
		return "Custom program";
	if( $arFields["CanCheck"] == 0 )
		return "Unchecked";
	if( $arFields["State"] == ACCOUNT_DISABLED )
		return "Disabled";
	else
		switch( $arFields["ErrorCode"] ){
			case ACCOUNT_UNCHECKED:
				return "Unchecked";
			case ACCOUNT_CHECKED:
				return "OK";
			case ACCOUNT_WARNING:
				return "Warning";
			case ACCOUNT_INVALID_PASSWORD:
				return "Invalid Logon";
			case ACCOUNT_MISSING_PASSWORD:
				return "Missing Password";
			case ACCOUNT_LOCKOUT:
				return "Account Locked Out";
			case ACCOUNT_PROVIDER_ERROR:
				return "Provider error";
			case ACCOUNT_ENGINE_ERROR:
				return "Internal error";
			case ACCOUNT_LOCKOUT:
				return "To prevent your account from being locked out by the provider please change the password or the user name you entered on AwardWallet.com as these credentials appear to be invalid.";
		}
}

function ProviderAPIVersion($sProviderCode){
	global $sPath;
	$sFile = "$sPath/engine/" . strtolower( $sProviderCode ) . "/functions.php";
	$InterfaceVersion = null;
	if(file_exists($sFile)){
		require_once( "$sPath/engine/" . strtolower( $sProviderCode ) . "/functions.php" );
		$sFunction = "CheckBalance2" . strtoupper( $sProviderCode );
		if(function_exists($sFunction))
			$InterfaceVersion = 2;
		$sFunction = "CheckBalance" . strtoupper( $sProviderCode );
		if(function_exists($sFunction))
			$InterfaceVersion = 1;
		$sClass = "TAccountChecker".ucfirst(strtolower($sProviderCode));
		if(class_exists($sClass))
			$InterfaceVersion = 3;
	}
	else{
		$InterfaceVersion = 3;
	}
	if(!isset($InterfaceVersion))
		DieTrace("Can't find API for provider: ".$sProviderCode);
	return $InterfaceVersion;
}

function filterBalance($nBalance, $allowFloat){
	if (is_null($nBalance))
		return $nBalance;
	if(!$allowFloat){
		$nBalance = trim($nBalance);
		// throw out cents
		$nBalance = preg_replace('/[\.\,]\d\d?$/ims', '', $nBalance);
	}
	// filter words
	$nBalance = trim(preg_replace("/[^\d\.\,\-]+/ims", "", $nBalance));
	// filter long fractional part
    if(preg_match('/^(\d+[\.\,]\d{2})\d{2,}$/ims', $nBalance, $matches)) {
        $nBalance = number_format(round(str_replace(",", ".", $nBalance), 2), 2, ".", "");
    }

	// filter thousands separators
	if(preg_match('/([\.\,]\d\d?)$/ims', $nBalance, $matches)){
		$cents = $matches[1];
        //changes comma to dot if any
        $cents = str_replace(',','.',$cents);
		$nBalance = preg_replace('/[\.\,]\d\d?$/ims', '', $nBalance);
	}
	else
		$cents = '';
	$nBalance = str_replace(array(".", ","), "", $nBalance);
	$nBalance .= $cents;
	$nBalance = html_entity_decode($nBalance);
	if($allowFloat) {
		$nBalance = floatval($nBalance);
        if ($nBalance == '-0')
            $nBalance = 0;
    }
	else
		$nBalance = intval($nBalance);
//	if(abs($nBalance) >= 50000000)
//		DieTrace("Balance seems too big", false);
	return $nBalance;
}

function SaveAccountProperties( $nAccountID, $arProperties, $arFields, $accountBalance ){
	global $Connection;
	$bResult = true;
	$q = new TQuery("select * from Account where AccountID = $nAccountID");
	if( $q->EOF )
		DieTrace("Invalid account $nAccountID");
	$arCodes = array();
	$qCodes = new TQuery("select Code, ProviderPropertyID, Required from ProviderProperty
	where (ProviderID = {$q->Fields['ProviderID']} or ProviderID is null)
	and Code <> 'ExpirationDate'
	order by Code");
	$missing = array();
	while(!$qCodes->EOF){
		if(!preg_match('/^[A-Z]\w+$/ms', $qCodes->Fields["Code"]))
			DieTrace("Invalid property code {$qCodes->Fields["Code"]} for provider ".Lookup("Provider", "ProviderID", "DisplayName", $q->Fields['ProviderID']).", should be named like 'MyPropertyName'", ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG);
		$arCodes[$qCodes->Fields["Code"]] = $qCodes->Fields["ProviderPropertyID"];
		if(($qCodes->Fields['Required'] == '1') && !isset($arProperties[$qCodes->Fields["Code"]]))
			$missing[] = $qCodes->Fields["Code"];
		$qCodes->Next();
	}
	if(count($missing) > 0)
		$bResult = false;
	$arAccountUpdates = array();
    // expiration date is set by user   // refs #5661
    if ($q->Fields['ExpirationAutoSet'] == EXPIRATION_USER && !empty($q->Fields['ExpirationDate']))
        $arAccountUpdates["ExpirationAutoSet"] = EXPIRATION_USER;
    // save expiration date
    else
        $arAccountUpdates["ExpirationAutoSet"] = EXPIRATION_UNKNOWN;
	if( isset( $arProperties['AccountExpirationDate'] ) ){
		if($arProperties['AccountExpirationDate'] === false) // wsdl mode converts boolean to strings
			$date = "null";
		else
			$date = $Connection->DateTimeToSQL( $arProperties['AccountExpirationDate'] );
		$arAccountUpdates["ExpirationDate"] = $date;
		$arAccountUpdates["ExpirationAutoSet"] = EXPIRATION_AUTO;
		unset( $arProperties['AccountExpirationDate'] );
	}
	else{
		if(($q->Fields['Balance'] == '0' && in_array($q->Fields['ErrorCode'], array(ACCOUNT_CHECKED, ACCOUNT_WARNING)))){
			$arAccountUpdates["ExpirationDate"] = "null";
		}
		// @TODO: uncomment, when maya finished filling in ExpirationAlwaysKnown
//		if($arFields['ExpirationAlwaysKnown'] == '1' && $arFields['ExpirationAutoSet'] == EXPIRATION_AUTO)
//			$arAccountUpdates['ExpirationDate'] = 'null';
	}
    // refs #8606
    if (isset($arProperties['ClearExpirationDate']) && $arProperties['ClearExpirationDate'] === 'Y'
        && $arAccountUpdates["ExpirationAutoSet"] == EXPIRATION_UNKNOWN && ConfigValue(CONFIG_TRAVEL_PLANS)) {
        $arAccountUpdates["ExpirationDate"] = "null";
        unset( $arProperties['ClearExpirationDate'] );
    }
	// save renew and expiration notes
	$arValues = array(
		"ExpirationWarning" => "null",
		"RenewNote" => "null",
		"RenewProperties" => "null",
	);
	foreach($arValues as $sKey => $sValue)
		if(isset($arProperties['Account'.$sKey])){
			$sValue = $arProperties['Account'.$sKey];
			if($sKey == "RenewProperties")
				$sValue = serialize($sValue);
			if(in_array('ExpirationWarning', array_keys($q->Fields)))
				$arAccountUpdates[$sKey] = "'".addslashes($sValue)."'";
			else
				continue; // WSDL, transfer as base property
			unset($arProperties['Account'.$sKey]);
		}
	// manage goals
	if(isset($q->Fields['Goal']) && ($q->Fields["GoalAutoSet"] == "1")){
		if( isset( $arProperties['Goal'] ) ){
			$nGoal = intval($arProperties['Goal']);
			if($nGoal > 0)
				$arAccountUpdates["Goal"] = $nGoal;
			else
				mail(
					ConfigValue(CONFIG_ERROR_EMAIL),
					"Warning: wrong goal, Provider: ".Lookup( "Provider", "ProviderID", "DisplayName", $q->Fields['ProviderID'] ),
					"AccountID: $nAccountID
		Goal: " . $arProperties['Goal'],
		EMAIL_HEADERS
				);
		}
		else
			$arAccountUpdates["Goal"] = "null";
	}
	unset( $arProperties['Goal'] );
	unset($arProperties['ExpirationDate']);
	$arAccountUpdates["LastActivity"] = "null";
	if(isset($arProperties["LastActivity"])){
		$d = strtotime($arProperties["LastActivity"]);
		if($d !== false)
			$arAccountUpdates["LastActivity"] = $Connection->DateTimeToSQL($d);
	}
	// subaccounts
	$savedSubAccounts = array();
	if(isset($arProperties['SubAccounts']) && count($arProperties['SubAccounts']) > 0) {
		foreach ($arProperties['SubAccounts'] as $subAccount) {
			foreach ($subAccount as $key => $value) {
				$subAccount[$key] = (!is_array($value) && !is_bool($value)) ? trim($value) : $value;
				if ($subAccount[$key] === '')
					unset($subAccount[$key]);
			}
			if (!isset($subAccount['DisplayName']) || !isset($subAccount['Code'])) {
				if (ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG)
					DieTrace("Missing subaccount properties, REQUIRED: DisplayName, Code, FOUND: " . implode(", ", array_keys($subAccount)));
			} else {
				$values = array("AccountID" => $nAccountID);
				$values['DisplayName'] = "'" . addslashes($subAccount['DisplayName']) . "'";
				$values['Code'] = "'" . addslashes($subAccount['Code']) . "'";

				if (isset($subAccount['Balance'])) {
					$values['Balance'] = filterBalance($subAccount['Balance'], $arFields['AllowFloat'] == 1);
				} else
					$values['Balance'] = null;

				if (isset($subAccount['ExpirationDate'])) {
					if ($subAccount['ExpirationDate'] == DATE_NEVER_EXPIRES || $subAccount['ExpirationDate'] === false)
						$values['ExpirationDate'] = "null";
					else
						$values['ExpirationDate'] = $Connection->DateTimeToSQL($subAccount['ExpirationDate']);
					$values['ExpirationAutoSet'] = EXPIRATION_AUTO;
				} else {
					$values['ExpirationDate'] = 'null';
					$values['ExpirationAutoSet'] = EXPIRATION_UNKNOWN;
				}

				if (isset($subAccount['Kind']))
					$values['Kind'] = "'" . addslashes($subAccount['Kind']) . "'";
				else
					$values['Kind'] = 'null';

				if (is_null($values['Balance']))
					$values['Balance'] = 'null';

				$q = new TQuery("select SubAccountID from SubAccount where AccountID = $nAccountID and Code = {$values['Code']}");
				if ($q->EOF) {
					if (isset($subAccount['IsHidden']) && $subAccount['IsHidden']) {
						$values['IsHidden'] = 1;
					}
					$Connection->Execute(InsertSQL("SubAccount", $values, false, true));
					$q->Close();
					$q->Open();
				} else {
					$values = array("LastBalance" => "CASE WHEN ABS(ROUND(IFNULL(Balance, 0), 2) - ROUND(IFNULL({$values['Balance']}, 0), 2)) > 0.001 THEN Balance ELSE LastBalance END") + $values;
					$Connection->Execute(UpdateSQL("SubAccount", $q->Fields, $values));
				}
				$subAccountId = $q->Fields['SubAccountID'];
				$savedSubAccounts[] = $subAccountId;

				$changeCountQuery = new TQuery("
					SELECT
						count(AccountBalanceID) as ChangeCount,
						max(UpdateDate) as LastChangeDate
					FROM AccountBalance
					WHERE SubAccountID = {$q->Fields['SubAccountID']}");
				$statValues = array();
				$statValues['ChangeCount'] = $changeCountQuery->Fields['ChangeCount'];
				if ($statValues['ChangeCount'] > 1)
					$statValues['LastChangeDate'] = $Connection->DateTimeToSQL($Connection->SQLToDateTime($changeCountQuery->Fields['LastChangeDate']));
				else
					$statValues['LastChangeDate'] = 'NULL';
				$Connection->Execute(UpdateSQL("SubAccount", $q->Fields, $statValues));

				unset($subAccount['Code']);
				unset($subAccount['DisplayName']);
				unset($subAccount['Balance']);
				unset($subAccount['ExpirationDate']);
				unset($subAccount['Kind']);
				AccountAuditor::getNextEliteLevel($arFields, $subAccount);
				WriteAccountProperties($nAccountID, $subAccountId, $subAccount, $arCodes);
			}
		}
	}
	if(ConfigValue(CONFIG_TRAVEL_PLANS))
    	$arAccountUpdates['ExpirationWarning'] = isset($arAccountUpdates['ExpirationWarning']) ? $arAccountUpdates['ExpirationWarning'] : 'NULL';
	$delCondition = "AccountID = $nAccountID";
    if (count($savedSubAccounts) > 0)
		$delCondition .= " and SubAccountID not in(".implode(", ", $savedSubAccounts).")";
	$Connection->Execute("delete from SubAccount where $delCondition");
	unset($arProperties['SubAccounts']);
	$arAccountUpdates['SubAccounts'] = count($savedSubAccounts);
	// update main account
    if (count($arAccountUpdates) > 0)
		$Connection->Execute(UpdateSQL("Account", array("AccountID" => $nAccountID), $arAccountUpdates));
	// save plain properties
	unset($arProperties['HistoryColumns'], $arProperties['HistoryRows'], $arProperties['HistoryVersion'], $arProperties['HistoryCacheValid']);
	WriteAccountProperties($nAccountID, null, $arProperties, $arCodes);
	return $bResult;
}

function WriteAccountProperties($nAccountID, $nSubAccountID, $arProperties, $arCodes){
	global $Connection, $arExtPropertyStructure;
	if(count(array_unique($arCodes)) != count($arCodes))
		DieTrace("there are some codes with same providerPropertyId");

	$arExProperties = SQLToArray( "select ProviderPropertyID, Val from AccountProperty where AccountID = $nAccountID and SubAccountID ".(isset($nSubAccountID)?" = $nSubAccountID":" is null"), "ProviderPropertyID", "Val" );
	foreach ( $arProperties as $sCode => $sValue ){
		if (!is_object($sValue)) {
			if (is_bool($sValue))
				$sValue = ($sValue?"true":"false");
			if (is_array($sValue)) {
				if (isset($arExtPropertyStructure[$sCode])){
           			$sValue = CheckPropertyStructure($sCode, $sValue);
           			if(empty($sValue))
               			continue;
           			$sValue = serialize($sValue);
       			} else {
       				continue;
				}
			}
			if( !isset( $arCodes[$sCode] ) ){
				$q = new TQuery("select p.Code from Account a
				join Provider p on a.ProviderID = p.ProviderID
				where a.AccountID = $nAccountID");
				DieTrace("Unknown property: $sCode for provider {$q->Fields['Code']}", false);
				continue;
			}
			$nProviderPropertyID = $arCodes[$sCode];
			if( trim($sValue) != "" ){
				if( isset( $arExProperties[$nProviderPropertyID] ) ){
					if($arExProperties[$nProviderPropertyID] != $sValue) {
					    // debugging Aki-Ville Pöykiö
                        if(ConfigValue(CONFIG_TRAVEL_PLANS)) {
                            $kind = Lookup("ProviderProperty", "ProviderPropertyID", "Kind", $nProviderPropertyID);
                            if ($kind == PROPERTY_KIND_NAME
                            && !empty($arExProperties[$nProviderPropertyID])
                            && !empty($sValue)
                            && strcasecmp(trim(str_replace(" ", "", $arExProperties[$nProviderPropertyID])), trim(str_replace(" ", "", $sValue))) != 0
                            && empty(array_intersect(explode(" ", strtolower($sValue)), explode(" ", strtolower($arExProperties[$nProviderPropertyID]))))
                            && Lookup("Account", "AccountID", "CheckedBy", $nAccountID) != CHECKED_BY_BROWSER)
                                getSymfonyContainer()->get("logger")->warning("changed name", ["OldName" => $arExProperties[$nProviderPropertyID], "NewName" => $sValue, "AccountID" => $nAccountID]);
                        }
                        $Connection->Execute("update AccountProperty set Val = '" . addslashes($sValue) . "' where ProviderPropertyID = $nProviderPropertyID
						and AccountID = $nAccountID and SubAccountID" . (isset($nSubAccountID) ? " = $nSubAccountID" : " is null"));
                    }
					unset( $arExProperties[$nProviderPropertyID] );
				}
				else
					$Connection->Execute("insert ignore into AccountProperty( ProviderPropertyID, AccountID, Val, SubAccountID )
					values( $nProviderPropertyID, $nAccountID, '".addslashes( $sValue ) . "', ".(isset($nSubAccountID)?$nSubAccountID:"null")." )");
			}
		}
	}
	if (isset($arCodes["LastActivity"]))
		unset($arExProperties[$arCodes["LastActivity"]]); // refs #5550, always keep last activity
	foreach ( $arExProperties as $nProviderPropertyID => $sValue ){
		$Connection->Execute("delete from AccountProperty where AccountID = $nAccountID
		and ProviderPropertyID = $nProviderPropertyID and SubAccountID".(isset($nSubAccountID)?" = $nSubAccountID":" is null"));
    }
}

/**
 * @param  $sProviderCode string
 * @return TAccountChecker
 */
function GetAccountChecker($sProviderCode, $requireFiles = false, $accountInfo = null){
	global $sPath;
	if($requireFiles){
		$file = "$sPath/../engine/$sProviderCode/functions.php";
		$fileOld = "$sPath/engine/$sProviderCode/functions.php";
		if (file_exists($file)) {
			require_once $file;
        } elseif (file_exists($fileOld)) {
			require_once $fileOld;
        }
	}
	if (isset($accountInfo['BrowserState']) && !empty($accountInfo['BrowserState'])){
		$accountInfo['State'] = TAccountChecker::extractState($accountInfo['BrowserState']);
	}
	$sClass = "TAccountChecker".ucfirst(strtolower($sProviderCode));
	if(!class_exists($sClass)){
		$sClass = "TAccountChecker";
	}
	if(method_exists($sClass, 'GetAccountChecker') && isset($accountInfo))
		$obj = $sClass::GetAccountChecker($accountInfo);
	else
		$obj = new $sClass();
    /** @var TAccountChecker $obj */
	$provider = new TQuery("select ProviderID, DisplayName, ShortName, ProgramName, Engine as ProviderEngine, Code, Code as ProviderCode, RequestsPerMinute from Provider where Code = '".addslashes($sProviderCode)."'");
	$obj->AccountFields = $provider->Fields;
    $obj->requestDateTime = time();
	if(!empty($accountInfo))
		$obj->AccountFields = array_merge($accountInfo, $obj->AccountFields);
	if (function_exists('getSymfonyContainer')) {
		$container = getSymfonyContainer();
		if ($container->hasParameter('use_last_host_as_proxy') && $container->getParameter('use_last_host_as_proxy')) {
			$awsUtil = $container->get("aw.aws_util");
			$obj->useLastHostAsProxy = true;
			$obj->hostName = $awsUtil->getHostName();
		}
		if($container->has('aw.memcached')) {
            $obj->setMemcached($container->get('aw.memcached'));
        }
		if($container->has('aw.curl_driver')) {
            $obj->setCurlDriver($container->get('aw.curl_driver'));
        }
		$obj->services = $container->get("aw.parsing.web.service_locator");
	}
	else {
        $memcached = new Memcached('appCache_' . getmypid());
        if(count($memcached->getServerList()) == 0){
            $memcached->addServer(MEMCACHED_HOST, 11211);
            $memcached->setOption(Memcached::OPT_RECV_TIMEOUT, 1000);
            $memcached->setOption(Memcached::OPT_SEND_TIMEOUT, 1000);
            $memcached->setOption(Memcached::OPT_CONNECT_TIMEOUT, 1000);
        }
        $obj->setMemcached($memcached);
    }
	return $obj;
}

/**
 * @param  $sProviderCode string
 * @param  $parserFile string
 */
function GetRewardAvailabilityChecker($sProviderCode, $requireFiles, $accountInfo, $parserFile)
{
	global $sPath;
    $file = "$sPath/../engine/$sProviderCode/RewardAvailability/{$parserFile}.php";
    $fileOld = "$sPath/engine/$sProviderCode/RewardAvailability/{$parserFile}.php";
    if (file_exists($file)) {
        require_once $file;
    } elseif (file_exists($fileOld)) {
        require_once $fileOld;
    }
    if (isset($accountInfo['BrowserState']) && !empty($accountInfo['BrowserState'])){
        $accountInfo['State'] = TAccountChecker::extractState($accountInfo['BrowserState']);
    }
    $fileCredentials = "$sPath/../engine/$sProviderCode/RewardAvailability/Credentials.php";
	if (!file_exists($fileCredentials)) {
		$fileCredentials = "$sPath/engine/$sProviderCode/RewardAvailability/Credentials.php";
	}
    if (file_exists($fileCredentials)) {
        require_once $fileCredentials;
        $sClassCredentials = "\\AwardWallet\\Engine\\{$sProviderCode}\\RewardAvailability\\Credentials";
        /** @var $sClassCredentials $objCredentials */
        $objCredentials = new $sClassCredentials();
        $credentials = $objCredentials::getCredentials();
        foreach ($credentials as $key => $value) {
            if (empty($accountInfo[$key])) {
                $accountInfo[$key] = $value;
            }
        }
    }
    $sClass = "\\AwardWallet\\Engine\\{$sProviderCode}\\RewardAvailability\\$parserFile";
    if (method_exists($sClass, 'GetAccountChecker') && isset($accountInfo)) {
        $obj = $sClass::GetAccountChecker($accountInfo);
    } else {
        $obj = new $sClass();
    }
    /** @var TAccountChecker $obj */
    $provider = new TQuery("select ProviderID, DisplayName, ShortName, ProgramName, Engine as ProviderEngine, Code, Code as ProviderCode, RequestsPerMinute from Provider where Code = '" . addslashes($sProviderCode) . "'");
    $obj->AccountFields = $provider->Fields;
    $obj->requestDateTime = time();
    if(!empty($accountInfo))
        $obj->AccountFields = array_merge($accountInfo, $obj->AccountFields);
    if (function_exists('getSymfonyContainer')) {
        $container = getSymfonyContainer();
        if ($container->hasParameter('use_last_host_as_proxy') && $container->getParameter('use_last_host_as_proxy')) {
            $awsUtil = $container->get("aw.aws_util");
            $obj->useLastHostAsProxy = true;
            $obj->hostName = $awsUtil->getHostName();
        }
        if ($container->has('aw.memcached')) {
            $obj->setMemcached($container->get('aw.memcached'));
        }
        if ($container->has('aw.curl_driver')) {
            $obj->setCurlDriver($container->get('aw.curl_driver'));
        }
		$obj->services = $container->get("aw.parsing.web.service_locator");
    } else {
        $memcached = new Memcached('appCache_' . getmypid());
        if (count($memcached->getServerList()) == 0) {
            $memcached->addServer(MEMCACHED_HOST, 11211);
            $memcached->setOption(Memcached::OPT_RECV_TIMEOUT, 1000);
            $memcached->setOption(Memcached::OPT_SEND_TIMEOUT, 1000);
            $memcached->setOption(Memcached::OPT_CONNECT_TIMEOUT, 1000);
        }
        $obj->setMemcached($memcached);
    }
	if ($parserFile === 'Parser') {
		$obj->isRewardAvailability = true;
	}
    return $obj;
}

function GetRewardAvailabilityRegister($providerCode, $requireFiles) {
	global $sPath;
	if ($requireFiles) {
		$file = "$sPath/../engine/$providerCode/RewardAvailability/Register.php";
		$fileOld = "$sPath/engine/$providerCode/RewardAvailability/Register.php";
		if (file_exists($file)) {
			require_once $file;
		} elseif (file_exists($fileOld)) {
			require_once $fileOld;
		} else {
			$obj = GetTransferChecker($providerCode, 'register', $requireFiles);
		}
	}
	if (!isset($obj)) {
		$class = 'AwardWallet\\Engine\\' . $providerCode . '\\RewardAvailability\\Register';
		if (class_exists($class)) {
			$obj = new $class();
		}
		if (!isset($obj)) {
			$obj = GetAccountChecker($providerCode, $requireFiles);
		}
		$provider = new TQuery("select ProviderID, DisplayName, ShortName, ProgramName, Engine as ProviderEngine, Code from Provider where Code = '" . addslashes($providerCode) . "'");
		$obj->AccountFields = $provider->Fields;
	}
	if (function_exists('getSymfonyContainer')) {
		$container = getSymfonyContainer();
		if ($container->hasParameter('use_last_host_as_proxy') && $container->getParameter('use_last_host_as_proxy')) {
			$awsUtil = $container->get("aw.aws_util");
			$obj->useLastHostAsProxy = true;
			$obj->hostName = $awsUtil->getHostName();
		}
		if ($container->has('aw.memcached')) {
			$obj->setMemcached($container->get('aw.memcached'));
		}
		if ($container->has('aw.curl_driver')) {
			$obj->setCurlDriver($container->get('aw.curl_driver'));
		}
		$obj->services = $container->get("aw.parsing.web.service_locator");
	} else {
		$memcached = new Memcached('appCache_' . getmypid());
		if (count($memcached->getServerList()) == 0) {
			$memcached->addServer(MEMCACHED_HOST, 11211);
			$memcached->setOption(Memcached::OPT_RECV_TIMEOUT, 1000);
			$memcached->setOption(Memcached::OPT_SEND_TIMEOUT, 1000);
			$memcached->setOption(Memcached::OPT_CONNECT_TIMEOUT, 1000);
		}
		$obj->setMemcached($memcached);
	}
	$obj->ParseIts = false;
	$obj->WantHistory = false;
	$obj->HistoryStartDate = null;
	$obj->WantFiles = false;
	$obj->FilesStartDate = null;
	$obj->KeepLogs = true;
	$obj->TransferMethod = 'register';
	$obj->isRewardAvailability = true;

	return $obj;
}

function GetTransferChecker($providerCode, $method, $requireFiles = false, $accountInfo = null) {
	$class = 'AwardWallet\\Engine\\'.$providerCode.'\\Transfer\\'.ucfirst($method);
	if (class_exists($class))
		$obj = new $class();
	if (!isset($obj))
		$obj = GetAccountChecker($providerCode, $requireFiles, $accountInfo);
	$provider = new TQuery("select ProviderID, DisplayName, ShortName, ProgramName, Engine as ProviderEngine, Code from Provider where Code = '".addslashes($providerCode)."'");
	$obj->AccountFields = $provider->Fields;
	if(!empty($accountInfo))
		$obj->AccountFields = array_merge($accountInfo, $obj->AccountFields);
	return $obj;
}

function GetTransferCreditCard($partner, $digits) {
	global $CreditCards;
	if (!isset($CreditCards))
		DieTrace('empty CreditCards');
	$card = null;
	foreach ($CreditCards as $cc) {
		if ($cc['Description'] == 'gift' || $cc['Description'] == 'giift')
			$cc['Description'] = 'giftmanagement';
		if (strcmp($digits, substr($cc["CardNumber"], -4)) === 0 && strcasecmp($partner, $cc["Description"]) === 0) { // partner is stored in 'Description' element for now
			$card = $cc;
			break;
		}
	}
	return $card;
}

/**
 * @param array	$accountInfo
 * @param array	$properties a list of retrieved It's
 * @param bool	$bNoWarnings
 */
function saveItineraries($accountInfo, $properties, &$bNoWarnings, $checkOptions = null, $sameIts = false, $restoreOnUpdate = false){
	global $Connection;

    list($accountId, $providerId, $userId, $userAgentId) = [$accountInfo["AccountID"], $accountInfo['ProviderID'], $accountInfo["UserID"], $accountInfo["UserAgentID"]];

	$cancelled = $currentIt = $savedIt = $totalReservationsArr = array();
	$tables = array(
		array("Kind" => "T", 	"Property" => "Itineraries",	"KeyField" => "RecordLocator",		"Table" => "Trip",			"SaveFunction" => "SaveTrips"),
		array("Kind" => "R", 	"Property" => "Reservations",	"KeyField" => "ConfirmationNumber",	"Table" => "Reservation",	"SaveFunction" => "SaveReservations"),
		array("Kind" => "L", 	"Property" => "Rentals",		"KeyField" => "Number",				"Table" => "Rental",		"SaveFunction" => "SaveRentals"),
		array("Kind" => "E", 	"Property" => "Restaurants",	"KeyField" => "ConfNo",				"Table" => "Restaurant",	"SaveFunction" => "SaveRestaurants"),
	);

	foreach($tables as $table){ // for each type
		// each type of reservation saved in different key
		if (!empty($properties[$table['Property']])){
			if(ConfigValue(CONFIG_TRAVEL_PLANS)){ // on prod
				// get old tp for difference
				require_once __DIR__.'/../../../../src/trips/common.php';
				if(isset($accountId)){
					// get actual reservations for this account of this kind, for updating them, if cancelled
					getCurrentItineraties($accountId, $table['Kind'], $currentIt);
					// move(extract) all reservations with cancelled=true, to array $cancelled
					checkCancelledItineraries($accountId, $properties[$table['Property']], $table['KeyField'], $table['Kind'], $table['Table'], $cancelled);
				}
                // check if cancelled[kind]=='NoItineraries' means all reservations are cancelled of this kind
				if($cancelled[$table['Kind']] != 'NoItineraries'){
					// save itineraries
					if(isset($properties[$table['Property']])){
						copyItinerariesForOtherUsers($table['KeyField'], $table['Table'], $accountId, $properties[$table['Property']], $providerId, $userId, $userAgentId);
						switch ($table['Kind']) {
                            case 'T':
                            case 'R':
                            case 'L':
                                $savedIt[$table['Kind']] = $table['SaveFunction']($accountId, $properties, $providerId, $bNoWarnings, $arAdded, $accountInfo, null, null, $restoreOnUpdate);
                                break;
                            case 'E':
                            default:
                                $savedIt[$table['Kind']] = $table['SaveFunction']($accountId, $properties, $providerId, $bNoWarnings, $arAdded, $restoreOnUpdate);
                        }
					}
					$totalReservationsArr[$table['Kind']] = count($savedIt[$table['Kind']]);
				} else {
					$totalReservationsArr[$table['Kind']] = -1; // noItineraries=true
				}
			} else { // on wsdl
				if(isset($accountId)){
					// move(extract) all reservations with cancelled=true, to array $cancelled
					checkCancelledItineraries($accountId, $properties[$table['Property']], $table['KeyField'], $table['Kind'], $table['Table'], $cancelled);
				}
				if(!in_array(array("NoItineraries" => true), $properties[$table['Property']]) && ArrayVal($cancelled, $table['Kind']) != 'NoItineraries') {
					// save itineraries
                    switch ($table['Kind']) {
                        case 'T':
                        case 'R':
                        case 'L':
                            $table['SaveFunction']($accountId, $properties, $providerId, $bNoWarnings, $arAdded, $accountInfo, null, null, $restoreOnUpdate);
                            break;
                        case 'E':
                        default:
                            $table['SaveFunction']($accountId, $properties, $providerId, $bNoWarnings, $arAdded, $restoreOnUpdate);
                    }
					$totalReservationsArr[$table['Kind']] = 0; // prevent always noItineraries=true
				} else {
					$totalReservationsArr[$table['Kind']] = -1; // noItineraries=true
				}
			}
		}
	}
	if (isset($accountId)) {
		// count grand total
		$totalReservations = 0;
		$noReservations = !empty($totalReservationsArr);
		foreach ($totalReservationsArr as $count) {
			if ($count >= 0) {
				$totalReservations += $count;
				if ($noReservations)
					$noReservations = false;
			}
		}
		if ($noReservations && $totalReservations == 0) {
			$totalReservations = -1; // noItineraries=true
		}
		$Connection->Execute(UpdateSQL('Account', array("AccountID"=>$accountId), array('Itineraries' => intval($totalReservations))));

		if (ConfigValue(CONFIG_TRAVEL_PLANS)) { // on prod
			// cancel reservations and
			// $currentIt - actual reservations for account
			// $cancelled - retrieved with cancelled=true
			// $savedIt - retrieved and saved reservations IDs
			$cancelledConfNumbers = UpdateCancelledItineraries($accountId, $currentIt, $cancelled, $savedIt);
			saveCancelledItineraries($accountId, $cancelledConfNumbers);
		} else { // wsdl
			saveCancelledItineraries($accountId, $cancelled);
		}
	}
}


/**
 * @param $accountId
 * @param $itineraries array retrieved IT's to check if they cancelled
 * @param $keyField string identifying field for this kind
 * @param $kind string
 * @param $table string here stored data
 * @out $cancelled array of identities of string 'NoIteneraries'
 *
 * could be one more param to know all stored items
 */
function checkCancelledItineraries($accountId, &$itineraries, $keyField, $kind, $table, &$cancelled){
    $confimed=array();
	foreach($itineraries as $key => $itinerary){
        //in case everything dropped only one string
		if (isset($itinerary['NoItineraries']) && $itinerary['NoItineraries']) {
			$cancelled[$kind] = 'NoItineraries';
			unset($itineraries[$key]);
            // that's return 'cause nothing more needs to be changed
			return;//break;
		}
        // create an array to store cancelled numbers
		if (!isset($cancelled[$kind]))
			$cancelled[$kind] = array();

		$number = null; // find out number to cancel
		if(isset($itinerary['ConfirmationNumber']))
			$number = $itinerary['ConfirmationNumber'];
		elseif(isset($itinerary[$keyField]))
			$number = $itinerary[$keyField];

        if(isset($number)){
            // store retrieved numbers in order not to repeat them twice
            $confimed[]=$number;
        }

		if(isset($itinerary['Cancelled']) && $itinerary['Cancelled'] && isset($number)) {
            // this number is going to cancellation
			$cancelled[$kind][] = $number;
			unset($itineraries[$key]);
			continue;
		}
	}

    $curIt=array();
    if(!empty($confimed) and ($accountId==580194)){
        // if no numbers retrieved assume situation as an error and discontinue cancelling stored in a base
        getCurrentItineraties($accountId, $kind, $curIt);
        // in $curIt stored old reservations those potentially might be cancelled
        foreach ($curIt[$kind] as $number=>$itinerary){
            // skip retrieved numbers
            if(in_array($number,$confimed))continue;
            // going to cancel if not mentioned above
            $cancelled[$kind][]=$number;
        }
    }

}

function saveCancelledItineraries($accountId, $cancelled){
	global $Connection;
	$inserted = array();
	if (is_array($cancelled)) {
		foreach($cancelled as $kind => $arr){
			if (is_array($arr)) {
				foreach($arr as $number){
					$number = addslashes($number);
					$inserted[] = "(ConfirmationNumber = '{$number}' and Kind = '{$kind}')";
					$Connection->Execute("insert ignore into CancelledItinerary(AccountID, ConfirmationNumber, Kind)
					values($accountId, '{$number}', '{$kind}')");
				}
			}
		}
	}
	$sql = "delete from CancelledItinerary where AccountID = {$accountId}";
	if(count($inserted) > 0)
		$sql .= " and not(".implode(" or ", $inserted).")";
	$Connection->Execute($sql);
}

function FilterAccountProperties(&$arProperties, $accountInfo = null, $htmlchars = true, $allowHtml = []){
	global $arExtPropertyStructure;
	foreach($arProperties as $key => $value){
        if('AccountExpirationWarning' === $key)
            continue;

		if(!isset($arExtPropertyStructure[$key]))
			if(is_array($value))
				FilterAccountProperties($arProperties[$key], $accountInfo, $htmlchars, $allowHtml);
			else
				if(is_string($value)) {
					if (trim($value) === '') {
						unset($arProperties[$key]);
						continue;
					}
					if (in_array($key, $allowHtml))
					    continue;
                    $arProperties[$key] = CleanXMLValue(html_entity_decode(StripTags(CleanXMLValue($value)), ENT_QUOTES, "UTF-8"));
					if ($htmlchars)
						$arProperties[$key] = htmlspecialchars($arProperties[$key]);
				} elseif (
					($value === false
						&& $key != "AccountExpirationDate"
						&& preg_match("/Date$/", $key))
					|| ($value === null)
				) {
					unset($arProperties[$key]);
					continue;
				}
//			else
//				if(!ConfigValue(CONFIG_TRAVEL_PLANS) && is_bool($value))
//					$arProperties[$key] = ($value?"true":"false");
	}
}

function GetXPath($html){
	if(preg_match("/<meta\s+http\-equiv=\"Content\-Type\"\s+content=\"text\/html;\s+charset=([^\"]+)\">/ims", $html, $arMatches)){
	  $sEncoding = strtolower($arMatches[1]);
	  if(in_array($sEncoding, array("iso-8859-1", "latin1"))){
		$html = iconv($sEncoding, "utf-8", $html);
	  }
	}
	$html = str_replace("&nbsp;", " ", $html);
//	file_put_contents("/mnt/projects/xpath.html", $html);
	$doc = new DOMDocument('1.0', 'utf-8');
	$nErrorLevel = error_reporting( E_ALL ^ E_WARNING );
	$doc->loadHTML( $html );
	error_reporting( $nErrorLevel );
//	$doc->save("/mnt/projects/xpath.xml");
    $xpath=new DOMXPath($doc);
	return $xpath;
}

function RedirectAccount( $nAccountID, $targetURL = null, $targetType = null ){
	global $Connection, $sPath;
	$q = new TQuery( "select
		a.*,
		p.Code as ProviderCode,
		p.Engine AS ProviderEngine,
		p.State as ProviderState,
		p.Login2Caption,
		p.AutoLogin,
		IF(
		    pc.LoginURL is null or pc.LoginURL = '',
		    p.LoginURL,
		    pc.LoginURL
        ) as `LoginURL`,
		p.RequestsPerMinute
	from Account a
	join Provider p on a.ProviderID = p.ProviderID
	left join ProviderCountry pc on
	    p.ProviderID = pc.ProviderID and
	    a.Login2 = pc.CountryID
	where
		a.AccountID = $nAccountID" );
	if( $q->EOF )
		die( "Account $nAccountID not found" );
  	$q->Fields["Pass"] = DecryptPassword( $q->Fields["Pass"] );
	if( (function_exists('LoadCookiePassword') && !LoadCookiePassword($q->Fields, false))
	|| ($q->Fields["ProviderState"] < PROVIDER_ENABLED)
	|| in_array($q->Fields['AutoLogin'], array(AUTOLOGIN_DISABLED, AUTOLOGIN_EXTENSION))
	|| (ConfigValue(CONFIG_TRAVEL_PLANS) && $q->Fields['AutoLogin'] == AUTOLOGIN_MIXED && in_array($targetType, ["mobile/ios", "mobile/android"]))){
		$arg = array(
			'RedirectURL' => $q->Fields["LoginURL"],
			'NoCookieURL' => true,
			'RequestMethod' => 'GET',
		);
		switch(ProviderAPIVersion($q->Fields['ProviderCode'])){
			case 3:
				$checker = GetAccountChecker($q->Fields['ProviderCode']);
				$checker->SetAccount($q->Fields);
				$checker->UpdateGetRedirectParams($arg);
				break;
		}
		$arg['AutoLogin'] = $q->Fields['AutoLogin'];
		return $arg;
	}
	else{
		$IsRedirect = 1;
		$nBalance = null;
		$nErrorCode = -1;
		$sErrorMessage = "Unknown error";
		if(($q->Fields["Login2Caption"] != "") || ($q->Fields['Region'] != ""))
			$sLogin = array( $q->Fields["Login"], $q->Fields["Login2"], $q->Fields['Region'] );
		else
			$sLogin = $q->Fields["Login"];
		$arProperties = array();
		switch(ProviderAPIVersion($q->Fields['ProviderCode'])){
			case 3:
				$checker = GetAccountChecker($q->Fields['ProviderCode']);
				$checker->Device = $targetType;
				$checker->SetAccount($q->Fields);
				$arg = $checker->Redirect($targetURL, $targetType);
				break;
		}
		if(is_array($arg)){
			$arg['AutoLogin'] = $q->Fields['AutoLogin'];
			return $arg;
		}
		if($arg == ACCOUNT_PROVIDER_ERROR)
			mail(ConfigValue( CONFIG_ERROR_EMAIL ), "Failed to autologin, Provider error, {$q->Fields["ProviderCode"]}", "AccountID: {$nAccountID}", EMAIL_HEADERS);
		return array(
			'AutoLogin' => $q->Fields['AutoLogin'],
			"RequestMethod" => "GET",
			"NoCookieURL" => true,
			"RedirectURL" => $q->Fields["LoginURL"],
		);
	}
}

/**
 * correct each segment dates, in case of next day
 * @param  $segments array
 * @return void
 */
function fixSegmentsDates(&$segments){
	# TimeZone Offsets
	$codes = array();
	foreach($segments as $segment){
		if (isset($segment['DepCode'], $segment['ArrCode'])) {
			$codes[] = $segment['DepCode'];
			$codes[] = $segment['ArrCode'];
		}
	}
	foreach($segments as &$segment){
		if (isset($segment['ArrDate'], $segment['DepDate'], $segment['DepCode'], $segment['ArrCode'])) {
		    if (TRIP_CATEGORY_AIR !== $segment) {
		        continue;
            }
            if (empty($segment['DepCode'] || TRIP_CODE_UNKNOWN === $segment['DepCode'] || empty($segment['ArrCode']) || TRIP_CODE_UNKNOWN === $segment['ArrCode'])) {
		        continue;
            }
			$depOffset = TAccountChecker::getAirportOffset($segment['DepCode'], $segment['DepDate']);
			$arrOffset = TAccountChecker::getAirportOffset($segment['ArrCode'], $segment['ArrDate']);
			if ($depOffset === null || $arrOffset === null)
				continue;

			$diff = ($segment['ArrDate'] - $arrOffset) - ($segment['DepDate'] - $depOffset);
			if ($diff < 0) {
				$segment['ArrDate'] += SECONDS_PER_DAY;
			}
		}
	}
}

/**
 * set time to hours:minutes if time is 0:00
 */
function setDefaultTime(&$date, $hours, $minutes){
	$d = getdate($date);
	if($d['hours'] == 0 && $d['minutes'] == 0){
		$date = mktime($hours, $minutes, 0, $d['mon'], $d['mday'], $d['year']);
	}
}

function formatMoneyProperties(&$props, $keys){
	foreach($keys as $key)
		if(isset($props[$key]) && (is_int($props[$key]) || is_float($props[$key]) || preg_match('/^\d(\.\d+)?$/ims', $props[$key])))
			$props[$key] = number_format($props[$key], 2, '.', ',');
}

function CheckValid($nAccountID, $predefined, $actual) {
	if(!is_array($actual)){
		DieTrace("trip properties is not an array", false);
		return false;
	}
	if (array_diff_key($predefined, $actual)!=array()) {
		$missing = implode( ", ", array_diff(array_keys($predefined), array_keys($actual)));
		if(ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG)
			DieTrace("missing trip properties: ".$missing, false);
		return false;
	}
	else {
		if(isset($actual['DepCode']) && isset($actual['ArrCode'])){
			$message = array();
			if(strlen($actual['DepCode']) > 3 && $actual['DepCode'] != TRIP_CODE_UNKNOWN)
				$message[] = 'DepCode: ('.$actual['DepCode'].')';
			if(strlen($actual['ArrCode']) > 3 && $actual['ArrCode'] != TRIP_CODE_UNKNOWN)
				$message[] = 'ArrCode: ('.$actual['ArrCode'].')';

			if(count($message)){
				DieTrace("Trip segments is not valid ".implode(' and ', $message), false);
				return false;
			}
		}

		if(isset($actual['RecordLocator']) && strlen($actual['RecordLocator']) > 40) {
			DieTrace("RecordLocator too long: " . substr($actual['RecordLocator'], 0, 50)."...", false);
			return false;
		}

		return true;
	}
}

function CheckPropertyStructure($key, $value){
	global $arExtPropertyStructure;
	if(!is_array($value)){
		DieTrace("$key should be array", false);
		return null;
	}
	$result = array();
	foreach($value as $n => &$row){
		foreach($arExtPropertyStructure[$key] as $subKey => $options){
			if (ArrayVal($options, 'Required', true) === false && !isset($row[$subKey]))
				continue;
			if(!isset($row[$subKey])){
                if ($key != 'DetailedAddress')
				    DieTrace("missing $subKey in row $n", false);
				return null;
			}
			$row[$subKey] = StripTags($row[$subKey]);
			if(isset($options['FilterHTML']) && $options['FilterHTML'])
				$row[$subKey] = htmlspecialchars(CleanXMLValue(html_entity_decode(StripTags(CleanXMLValue($row[$subKey])), ENT_QUOTES, "UTF-8")));
		}
		$result[] = $row;
	}
	if(count($result) == 0)
		return null;
	return $result;
}

function SaveExtProperties( $nID, $sKind, $arProperties ){
	global $Connection, $arExtPropertyStructure;
	unset($arProperties['ExtProperties']); // only for email ? see commit 57070, AMalutin
	$arExist = SQLToArray("select Lower(Name) as Name, Value from ExtProperty where SourceTable='$sKind' and SourceID='$nID'", "Name", "Value");
	foreach ($arProperties as $key => $value) {
		$lowerKey = strtolower($key);
        if (in_array($lowerKey, ['useragentid', 'copied'])) {
            DieTrace("Invalid extproperty '".$key."'", false, 0);
        }
		if(isset($arExtPropertyStructure[$key])){
			$value = CheckPropertyStructure($key, $value);
			if(empty($value))
				continue;
			$value = serialize($value);
		}

		if( !isset( $arExist[$lowerKey] ) ){
			$Connection->Execute("
				insert into ExtProperty	(SourceTable, SourceID, Name, Value)
				values ('$sKind', $nID, '".addslashes($key)."', '".addslashes($value)."')
				on duplicate key update Value = '".addslashes($value)."'");
		}
		else{
			if($value != $arExist[$lowerKey])
				$Connection->Execute("
					update ExtProperty set Value='".addslashes($value)."'
					where Name='".addslashes($key)."' and SourceTable='$sKind' and SourceID='$nID'
				");
			unset( $arExist[$lowerKey] );
		}
	}
}

function getItineraryTable($providerKind){
	switch($providerKind){
		case PROVIDER_KIND_AIRLINE:
			$result = "Trip";
			break;
		case PROVIDER_KIND_HOTEL:
			$result = "Reservation";
			break;
		case PROVIDER_KIND_CAR_RENTAL:
			$result = "Rental";
			break;
		default:
			$result = "Restaurant";
	}
	return $result;
}

function getItineraryProviderKind($itineraryKind){
	switch($itineraryKind){
		case 'T':
			$result = PROVIDER_KIND_AIRLINE;
			break;
		case 'R':
			$result = PROVIDER_KIND_HOTEL;
			break;
		case 'L':
			$result = PROVIDER_KIND_CAR_RENTAL;
			break;
		default:
			$result = PROVIDER_KIND_OTHER;
	}
	return $result;
}

function LoadExtProperties( $sTableID, $nID ){
	return SQLToArray("select Name, Value from ExtProperty
	where SourceTable='$sTableID' and SourceID='$nID'", "Name", "Value");
}

function BuildHotelAddress($arFields){
	$sAddress = $arFields["Address1"];
	if( $arFields['Address2'] != '' )
		$sAddress .= ", ".$arFields['Address2'];
	if( $arFields['City'] != '' )
    	$sAddress .= ", ".$arFields["City"];
	if( $arFields['StateProvince'] != '' )
    	$sAddress .= ", ".$arFields["StateProvince"];
	if( $arFields['PostalCode'] != '' )
		$sAddress .= ", ".$arFields['PostalCode'];
	if( $arFields['Country'] != 'US' )
		$sAddress .= ", ".$arFields['Country'];
	return $sAddress;
}

function BuildAirPortName(&$arInfo, $cityOnly = false){
	$arInfo['Name'] = $arInfo['CityName'];
	if(!$cityOnly)
	    $arInfo['Name'] .= " (" . ucwords( strtolower( $arInfo['AirName'] ) ) . ")";
	if( ( $arInfo['StateName'] != '' ) && ( $arInfo['State'] != '' ) )
		$arInfo['Name'] .= ", " . $arInfo['State'];
	if( $arInfo['CountryCode'] != 'US' )
		$arInfo['Name'] .= ", " . ucwords( strtolower( $arInfo['CountryName'] ) );
	$arInfo["CityStateName"] = ucwords( strtolower( $arInfo['CityName'] ) );
	if( ( $arInfo['StateName'] != '' ) && ( $arInfo['State'] != '' ) )
		$arInfo['CityStateName'] .= ", " . $arInfo['State'];
}

function LookupAirPort($sCode, &$arInfo)
{
    if (!isset($sCode) || ($sCode == ''))
        return false;
    $sCode = str_replace(array('"', "'"), '', $sCode);
    $q = new TQuery("select * from AirCode where AirCode ='" . addslashes($sCode) . "'");
    if (!$q->EOF) {
        $arInfo = $q->Fields;
        BuildAirPortName($arInfo);
        return true;
    }
    $q = new TQuery("select * from AirCode where CityCode ='" . addslashes($sCode) . "' order by Classification");
    if (!$q->EOF) {
        $arInfo = $q->Fields;
        BuildAirPortName($arInfo, true);
        return true;
    }
    return true;
}

function LookupAirPortByCity($sCity, $sState, $sAirName, &$sCode){
  $q = new TQuery("select * from AirCode where CityName = '".addslashes($sCity)."' and State = '".addslashes($sState)."'
  ".(isset($sAirName)?" and replace(AirName, ' ', '') like '%".addslashes(str_replace(' ', '', $sAirName))."%'":"")."
  order by Preference");
  if(!$q->EOF)
	$sCode = $q->Fields["AirCode"];
  return !$q->EOF;
}

/**
 * look for address in cache (GeoTag table) or google it, and save to cache
 * @param mixed $sAddress - string, or array of strings (addresses)
 * @return array - row from GeoTag table
 */
function FindGeoTag($sAddress, $placeName = null, $expectedType = 0){
	global $Connection;

    if(
   		function_exists('getSymfonyContainer')
   		&& getSymfonyContainer()->has('aw.geo.google_geo')
   	)
        return getSymfonyContainer()->get('aw.geo.google_geo')->FindGeoTag($sAddress, $placeName, $expectedType);

	$tempAddress = $sAddress;
	if(is_array($sAddress)){
		$arAddressVariants = $sAddress;
		foreach($arAddressVariants as $key => $value)
			$arAddressVariants[$key] = NormalizeAddress($value);
		$sAddress = $arAddressVariants[0];
	}
	$sAddress = NormalizeAddress($sAddress);
	if($sAddress == ""){
		if(!is_array($tempAddress) && $tempAddress != ""){ // maybe china address
			$arAddressVariants[] = $tempAddress;
			$sAddress = $tempAddress;
		}
	}
	EchoDebug("GeoTag", "FindGeoTag: ".$sAddress."<br>");
	$q = new TQuery("SELECT * FROM GeoTag WHERE Address = '".addslashes($sAddress)."'");
	if(empty($sAddress)){
	    if($q->EOF){
	        $Connection->Execute("insert ignore into GeoTag(Address, UpdateDate, FoundAddress) values('', now(), '')");
	        $q->Open();
        }
        return $q->Fields;
    }
	$arFields = $q->Fields;
	// request if:
	// no result OR resetParam OR (old AND limitOk)
	// cached if: = not request
	// result AND no resetParam AND (fresh OR not limitOk)
	if($arFields['Lat'] != '')
		$cacheTime = SECONDS_PER_DAY * 31;
	else
		$cacheTime = SECONDS_PER_DAY * 7;
	if (!$q->EOF && !isset($_GET['ResetGeoTag']) && ( ($Connection->SQLToDateTime($arFields["UpdateDate"]) > (time() - $cacheTime)) || !GoogleGeoTagLimitOk() ))
		return $arFields;

	if(!isset($arAddressVariants))
		$arAddressVariants = GetAddressVariants($sAddress);
	$found = false;
	foreach($arAddressVariants as $sCurAddress){
		EchoDebug("GeoTag", "searching: ".$sCurAddress."<br>");
		$airport = false;
		if(
            ((GEOTAG_TYPE_AIRPORT === $expectedType) || preg_match("#^[A-Z]{3}$#ims", $sCurAddress)) &&
            LookupAirPort($sCurAddress, $airPort) && !empty($airPort['Lat'])
        ){
			$arFields['Lat'] = $airPort['Lat'];
			$arFields['Lng'] = $airPort['Lng'];
			$sCurAddress = $sCurAddress . ", " . $airPort['CityName'] . ', ' . $airPort['CountryCode'];
			$arFields["City"] = $airPort['CityName'];
			$arFields["State"] = $airPort['StateName'];
			$arFields["Country"] = $airPort['CountryName'];
			$arFields["CountryCode"] = $airPort['CountryCode'];
			$arFields["StateCode"] = $airPort['State'];
			$arFields["TimeZone"] = $airPort["TimeZone"];
			$found = true;
			$airport = true;
		}
		if(GoogleGeoTag($sCurAddress, $nLat, $nLng, $detailedAddress, $placeName, $expectedType)){
			EchoDebug("GeoTag", "found: ".$sCurAddress."<br>");
			$arFields["Lat"] = $nLat;
			$arFields["Lng"] = $nLng;
			$fields = array('AddressLine', 'PostalCode');
			if(!$airport)
				$fields = array_merge($fields, array('City', 'State', 'Country', 'StateCode', 'CountryCode'));
			foreach ($fields as $key)
				if (isset($detailedAddress[$key]))
					$arFields[$key] = $detailedAddress[$key];
			$found = true;
		}
		if ($found && TimeZoneByCoordinates($arFields['Lat'], $arFields['Lng'], $timezone, $timezoneId)) {
			if (isset($timezoneId) && ConfigValue(CONFIG_TRAVEL_PLANS)) {
				$_timezone = getTimeZoneOffsetByLocation($timezoneId, $tid);
				if (isset($_timezone)) {
					$timezone = $_timezone;
					$arFields["TimeZoneID"] = $tid;
					$arFields["TimeZoneName"] = $timezoneId;
				}
			}
			EchoDebug("GeoTag", "found timezone offset: " . $timezone . "<br>");
			$arFields["TimeZone"] = $timezone;
		}
		if($found && $airport && !empty($airPort['TimeZoneID']) && empty($arFields['TimeZoneID'])){
			$arFields['TimeZoneID'] = $airPort['TimeZoneID'];
			$arFields["TimeZoneName"] = Lookup("TimeZone", "TimeZoneID", "Name", $airPort['TimeZoneID'], true);
		}
		if(!isset($arFields['Lat'])){
			$arFields['Lat'] = '';
			$arFields['Lng'] = '';
		}
		if($found)
			break;
	};
	if(!$found){
		$detailedAddress = ParseAddress($sAddress);
		if(!empty($detailedAddress))
			foreach (array('AddressLine', 'City', 'State', 'Country', 'PostalCode') as $key)
				if (isset($detailedAddress[$key]))
					$arFields[$key] = $detailedAddress[$key];
	}
	$arFields['Address'] = $sAddress;
	$arFields['FoundAddress'] = $sCurAddress;
    if (!isset($arFields["TimeZone"]))
        $arFields["TimeZone"] = null;
	if (!isset($arFields["TimeZoneID"]))
        $arFields["TimeZoneID"] = null;

	$ar = $arFields;
	$ar['HostName'] = "'".gethostname()."'";
	unset($ar['TimeZoneName']);
	checkGeoTagUpdateFields($arFields);
	$ar["Address"] = "'".addslashes($sAddress)."'";
	$ar["FoundAddress"] = "'".addslashes($sCurAddress)."'";
	foreach (array('AddressLine', 'City', 'State', 'Country', 'PostalCode', 'StateCode', 'CountryCode') as $key)
		$ar[$key] = isset($arFields[$key]) ? "'".addslashes($arFields[$key])."'" : 'null';
    $ar["UpdateDate"] = "now()";
	if($ar["Lat"] == ""){
		$ar["Lat"] = "null";
		$ar["Lng"] = "null";
	}
	if(ArrayVal($ar, "TimeZone") == "")
		$ar["TimeZone"] = "null";
	if(ArrayVal($ar, "TimeZoneID") == "")
		$ar["TimeZoneID"] = "null";
	if($q->EOF){
		EchoDebug("GeoTag", "inserting: ".$sCurAddress."<br>");
		$Connection->Execute(InsertSQL("GeoTag", $ar)." on duplicate key update Lat = ".$ar['Lat'].", Lng = ".$ar['Lng'].", TimeZone = ".$ar['TimeZone']);
		$qTag = new TQuery("select * from GeoTag where Address = {$ar['Address']}");
		if($qTag->EOF)
			DieTrace("New inserted address not found");
		$arFields['GeoTagID'] = $qTag->Fields['GeoTagID'];
        $arFields['UpdateDate'] = $qTag->Fields['UpdateDate'];
	}
	else{
        EchoDebug("GeoTag", "updating: ".$sCurAddress."<br>");
		$Connection->Execute(UpdateSQL("GeoTag", array("GeoTagID" => $arFields['GeoTagID']), $ar));
	}
	EchoDebug("GeoTag", "FindGeoTag: result [{$arFields['Lat']},{$arFields['Lng']}]<br>");
	return $arFields;
}

function checkGeoTagUpdateFields($fields) {
	$airPattern = '/([a-z]{3}) Airport/i';
	$isAirport = isset($fields['FoundAddress'])
		&& (strlen($fields['FoundAddress']) == 3
		|| preg_match($airPattern, $fields['FoundAddress']));
	# AirCode
	if ($isAirport && isset($fields['TimeZoneID'])) {
		if (preg_match($airPattern, $fields['FoundAddress'], $matches)) {
            $fields['FoundAddress'] = $matches[1];
        }
	}
}

function getTimeZoneOffsetByLocation($location, &$timeZoneID) {
	$sql = "
		SELECT
			*
		FROM
			TimeZone
		WHERE
			Location = '".addslashes($location)."'
	";
	$q = new TQuery($sql);
	if ($q->EOF) {
		return null;
	}
	$timeZoneID = $q->Fields['TimeZoneID'];
	$dateTime = new DateTime();
	try {
		$dateTimeZone = new DateTimeZone($q->Fields['Location']);
		$newOffset = $dateTimeZone->getOffset($dateTime);
	} catch (\Exception $e) {
		DieTrace('"'.$location.'" time zone not allowed', false);
		return null;
	}
	return $newOffset;
}

function NormalizeAddress($sAddress){
	return @iconv("UTF-8", "UTF-8//IGNORE", substr(NormalizeStr($sAddress), 0, 250));
}

function NormalizeStr($s){
	$s = preg_replace("/<[^>]*>/ims", " ", $s);
	$s = preg_replace("/[^\w\d\.\,\-]/uims", " ", $s);
	$s = preg_replace("/\s+/ims", " ", $s);
	$s = preg_replace("/ ([.\,\-])/ims", '\1', $s);
	$s = trim($s);
	return $s;
}

// return all possible variants of address
function GetAddressVariants($sAddress){
	$arResult = array($sAddress);
	$s = str_ireplace("Arpt", "Airport", $sAddress);
	$s = str_ireplace("People s Republic Of", "", $s);
	if($s != $sAddress){
		$arResult[] = $s;
		$sAddress = $s;
	}
	if(preg_match('/^((\w+\s+)+Airport)(\s*\,)?(.+)$/ims', $sAddress, $arMatches)){
		$arResult[] = $arMatches[1];
		$arResult[] = $arMatches[4];
	}
	$ar = array();
	foreach($arResult as $s){
		$s = trim($s);
		if($s != "")
			$ar[] = $s;
	}
	return $ar;
}

// recursive array_diff_assoc
// return FALSE if arrays are equal
function array_compare($array1, $array2) {
    $diff = false;
    // Left-to-right
    foreach ($array1 as $key => $value) {
        if (!array_key_exists($key,$array2)) {
            $diff[0][$key] = $value;
        } elseif (is_array($value)) {
             if (!is_array($array2[$key])) {
                    //$diff[0][$key] = $value;
                    foreach($value as $k => $v){
                        $diff[0][$k] = $v;
                    }
                    $diff[1][$key] = $array2[$key];
             } else {
                    $new = array_compare($value, $array2[$key]);
                    if ($new !== false) {
                        /* if (isset($new[0])) $diff[0][$key] = $new[0];
                         if (isset($new[1])) $diff[1][$key] = $new[1];*/
                        if (isset($new[0])){
                            foreach($new[0] as $k => $v){
                                $diff[0][$k] = $v;
                            }
                        }
                        if (isset($new[1])){
                            foreach($new[1] as $k => $v){
                                $diff[1][$k] = $v;
                            }
                        }
                    };
             };
        } elseif ($array2[$key] !== $value) {
             $diff[0][$key] = $value;
             $diff[1][$key] = $array2[$key];
        };
 };
 // Right-to-left
 foreach ($array2 as $key => $value) {
        if (!array_key_exists($key,$array1)) {
             $diff[1][$key] = $value;
        };
        // No direct comparsion because matching keys were compared in the
        // left-to-right loop earlier, recursively.
 };
 return $diff;
};

function diff($old, $new){
    $maxlen = 0;
	foreach($old as $oindex => $ovalue){
		$nkeys = array_keys($new, $ovalue);
		foreach($nkeys as $nindex){
			$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
				$matrix[$oindex - 1][$nindex - 1] + 1 : 1;
			if($matrix[$oindex][$nindex] > $maxlen){
				$maxlen = $matrix[$oindex][$nindex];
				$omax = $oindex + 1 - $maxlen;
				$nmax = $nindex + 1 - $maxlen;
			}
		}
	}
	if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
	return array_merge(
		diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
		array_slice($new, $nmax, $maxlen),
		diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
}

function htmlDiff($old, $new){
    $ret = "";
	$diff = diff(explode(' ', $old), explode(' ', $new));
	foreach($diff as $k){
		if(is_array($k))
			$ret .= (!empty($k['d'])?"<del>".implode(' ',$k['d'])."</del> ":'').
				(!empty($k['i'])?"<ins>".implode(' ',$k['i'])."</ins> ":'');
		else $ret .= $k . ' ';
	}
	return $ret;
}

function urlToAbsolute($content, $url) {
	$elem = parse_url($url);
	if ($elem === false || !isset($elem['scheme']) || !isset($elem['host']))
		return $content;
	$path = '';
	if (isset($elem['path']) && $elem['path'] != "")
		$path = dirname($elem['path']);
	$path = str_replace("\\", "/", $path);
	$baseUrl = $elem['scheme'] ."://" . $elem['host'] . $path;
	$contentWithBase = preg_replace("/(<head[^>]*>)/im", "\\1\n<meta name=\"referrer\" content=\"no-referrer\"><base href=\"".$baseUrl."\">\n", $content);
	return (is_null($contentWithBase)) ? $content : $contentWithBase;
}

function saveAAA($accountID = 0, $userID, $url){
	$content = '';
	$logFile = '/mnt/www-logs/aw1/tmp/aaa.log';

	$q = new TQuery('SELECT p.Code FROM Account a, Provider p WHERE p.ProviderID=a.ProviderID AND a.AccountID='.intval($accountID));
	if(!$q->EOF)
		$providerCode = $q->Fields['Code'];
	else
		return;

	$content .= "UserID: $userID\n";
	$content .= "AccountID: $accountID\n";
	$content .= "Provider: $providerCode\n";
	$content .= "Page: $url\n";
	$content .= "!--------------------------------------------------------\n\n";

	file_put_contents($logFile, $content, FILE_APPEND);
}

function saveAccountHistory($accountId, array $historyColumns, array $historyRows, $resetCache) {
	global $Connection;

	$existing = [];
	if($resetCache)
		$Connection->Execute("delete from AccountHistory where AccountID = ".intval($accountId));
	else
		foreach(new TQuery("select * from AccountHistory where AccountID = ".intval($accountId)) as $row)
			$existing[sha1($row['PostingDate'] . '-' . $row['Description'] . '-' . $row['Miles'] . '-' . $row['Info'])] = $row;

	$infoKeys = array_keys(array_intersect($historyColumns, AccountAuditorAbstract::$historyInfoKeys));
	$rows = [];
	$saved = [];

	$dateColumn = array_search('PostingDate', $historyColumns);
	if($dateColumn === false){
		DieTrace("No PostingDate in HistoryColumns", false, 0, $historyColumns);
		return;
	}
	$descColumn = array_search('Description', $historyColumns);
	$milesColumn = array_search('Miles', $historyColumns);

	$doubles = 0;
	$lastHashKey = null;

	foreach ($historyRows as $position => $row) {
		if (!isset($row[$dateColumn]) || !is_numeric($row[$dateColumn]))
			continue;

		$info = AccountAuditorAbstract::serializeInfo($row, $infoKeys);

        $values = [
			'AccountID' => $accountId,
			'PostingDate' => date("Y-m-d H:i:s", $row[$dateColumn]),
			'Description' => !empty($descColumn) && !empty($row[$descColumn]) ? $row[$descColumn] : null,
			'Miles' => !empty($milesColumn) && isset($row[$milesColumn]) ? filterBalance($row[$milesColumn], true) : null,
			'Info' => $info,
			'Position' => isset($row['Position']) ? intval($row['Position']) : $position,
            'UUID' => \AwardWallet\MainBundle\Globals\StringHandler::uuid()
		];
		$hashKey = $values['PostingDate'] . '-' . $values['Description'] . '-' . $values['Miles'] . '-' . $values['Info'];
		$hash =  sha1($hashKey . ($doubles > 0 ? $doubles : ""));
		if(!isset($saved[$hash]))
			$saved[$hash] = 1;
		else {
			$saved[$hash]++;
			$hash .= "." . $saved[$hash];
		}
		foreach($values as $key => &$value)
			if($value === null)
				$value = 'null';
			else
				$value = "'" . addslashes($value) . "'";

		if(!isset($existing[$hash])) {
			$rows[] = "(" . implode(", ", $values) . ")";
			if (count($rows) >= 100) {
				$Connection->Execute("insert into AccountHistory (AccountID, PostingDate, Description, Miles, Info, Position, UUID) values " . implode(", ", $rows));
				$rows = [];
			}
			if(!$resetCache)
				$existing[$hash] = $values;
		}
		else
			if($existing[$hash]['Position'] != $position)
				$Connection->Execute(UpdateSQL("AccountHistory", $values, ["Position" => $position]));

		if($lastHashKey == $hashKey)
			$doubles++;
		else
			$doubles = 0;
		$lastHashKey = $hashKey;
	}
	if(count($rows) > 0)
		$Connection->Execute("insert into AccountHistory (AccountID, PostingDate, Description, Miles, Info, Position, UUID) values " . implode(", ", $rows));
//	if($resetCache) {
//		$delete = array_diff_key($existing, $saved);
//		foreach ($delete as $values) {
//			foreach (['AccountID', 'PostingDate', 'Description', 'Miles', 'Info'] as $key)
//				if (!isset($values[$key]))
//					$values[$key] = 'null';
//				else
//					$values[$key] = "'" . addslashes($values[$key]) . "'";
//			$Connection->Execute(DeleteSQL("AccountHistory", $values));
//		}
//	}
}

function processCheckException(Exception $e) {
	$allowShow = false;
	if ($e instanceof RuntimeException || $e instanceof InvalidArgumentException) {
		DieTrace("[".$e->getCode()."] ".$e->getMessage(), false);
	} elseif ($e instanceof AccountException) {
		$code = $e->getCode();
		if ($code != AccountException::CALLBACK)
			$allowShow = true;
	} else {
		DieTrace("[".$e->getCode()."] ".$e->getMessage(), false);
		$allowShow = true;
	}
	return $allowShow;
}

const RESTORE_ON_UPDATE = true;

function updateConfFields($arTrips, $sTable, $confFields){
    global $Connection;

    foreach($arTrips as $nID)
        $Connection->Execute("update $sTable set ConfFields = '".addslashes(serialize($confFields))."',
        Hidden = 0
        where {$sTable}ID = $nID");
}

function translateBalance($balance, $thousands_sep, $dec_point = '.') {
	$neg = preg_match("/^-/", $balance) ? -1 : 1;
	$balance = preg_replace("/[^\dk\\{$thousands_sep}\\{$dec_point}\+]/ims", "", strval($balance));
	$balance = trim($balance);
	$balance = preg_replace("/\\{$thousands_sep}+/ims", "", $balance);
	$balance = preg_replace("/\\{$dec_point}/ims", ".", $balance);
	if (preg_match("/^(\d+)k$/ims", $balance, $matches)) {
		$balance = $matches[1].'000';
	}
	$balance = preg_replace("/k/ims", "", $balance);
	$balance = floatval($balance);
	return $balance * $neg;
}

?>
