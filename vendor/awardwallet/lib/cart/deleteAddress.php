<?php

// -----------------------------------------------------------------------
// delete billing address
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com 
// -----------------------------------------------------------------------

require( "../../kernel/public.php" );
$Interface->requireHTTPS = true;
$nID = intval( $QS["ID"] );
$sPrefix = $QS["Type"];
if( ( $sPrefix != "Billing" ) && ( $sPrefix != "Shipping" ) )
	DieTrace( "Unknown address type" );

$q = new TQuery( "select UserID from {$sPrefix}Address where {$sPrefix}AddressID = $nID" );
if( $q->EOF )
	$Interface->DiePage( "{$sPrefix} address not found." );
if( $q->Fields["UserID"] != $_SESSION["UserID"] )
	$Interface->DiePage( "You can not delete other user {$sPrefix} address" );
	
$Connection->Execute( "delete from {$sPrefix}Address where {$sPrefix}AddressID = $nID" );	
Redirect(urlPathAndQuery($_SERVER['HTTP_REFERER']));
	
?>