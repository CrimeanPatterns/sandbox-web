<?php

// -----------------------------------------------------------------------
// edit billing address
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

require( "../../kernel/public.php" );
$Interface->requireHTTPS = true;
require( "$sPath/kernel/TForm.php" );
require( "common.php" );
require( "$sPath/kernel/TCartManager.php" );
$objCart->CalcTotals();
$sPrefix = $QS["Type"];
if( ( $sPrefix != "Billing" ) && ( $sPrefix != "Shipping" ) )
	die( "Unknown address type" );
$objCartManager = new TCartManager();

$nID = intval( $QS["ID"] );
$q = new TQuery( "select UserID from {$sPrefix}Address where {$sPrefix}AddressID = $nID" );
if( $q->EOF )
	$Interface->DiePage( "{$sPrefix} address not found." );
if( $q->Fields["UserID"] != $_SESSION["UserID"] )
	$Interface->DiePage( "You can not edit other user {$sPrefix} address" );

$sTitle = "Edit {$sPrefix} address";

$arFields = CreateAddressFields( NULL, NULL, NULL, $sPrefix, "" );
unset( $arFields["{$sPrefix}AddressID"] );
if( $_SERVER['REQUEST_METHOD'] == "GET" )
	$sBackTo = ArrayVal($_SERVER, "HTTP_REFERER", '/');
else
	$sBackTo = $_POST["BackTo"];
$arFields += array( "BackTo" => array(
	"Type" => "html",
	"HTML" => "<input type=hidden name=BackTo value=\"" . htmlspecialchars( urlPathAndQuery($sBackTo) ) . "\">",
	"IncludeCaption" => False,
) );
$objForm = new TForm( $arFields );
$objForm->TableName = "{$sPrefix}Address";
$objForm->Filters = array( "UserID" => $_SESSION["UserID"] );
$objForm->SuccessURL = urlPathAndQuery($sBackTo);
$objForm->Uniques = array(
	array(
		"Fields" => array( "UserID", "AddressName" ),
		"ErrorMessage" => "Address with this nick already exists. Please choose another nick",
	),
);
$objForm->KeyField = "{$sPrefix}AddressID";
$objForm->Title = "Edit {$sPrefix} address";

$objCartManager->DrawHeader();
$objForm->Edit();
$objCartManager->DrawFooter();
