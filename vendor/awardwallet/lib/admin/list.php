<?php
// -----------------------------------------------------------------------
// link management
// Author: Alexi Vereschaga, ITlogy LLC, alexi@itlogy.com, www.ITlogy.com 
// -----------------------------------------------------------------------
require( "../../kernel/public.php" );
require( "$sPath/lib/admin/design/header.php" );

if(!isset($QS["cnf"]))
	DieTrace( "Invalid URL" );
else
	$configFile = $QS["cnf"];
$nID = 0;
require($sPath . $configFile);

if(!isset($listFormFields))
	$listFormFields = $arFormFields;
$objList = New TBaseList( $sTableName, $listFormFields, $vDefaultOrder );
if(isset($AllowDeletes))
	$objList->AllowDeletes = true;
if( isset( $arDeleteQueries ) )
	$objList->DeleteQueries += $arDeleteQueries;
if(isset($vKeyField))
	$objList->KeyField = $vKeyField;
$objList->SQL = $vSql;
$objList->URLParams = $QS;
$objList->ReadOnly = false;
$objList->CanAdd = true;
$objList->AllowDeletes = true;
$objList->ShowEditors = true;
$objList->ShowFilters = True;
$objList->Update();
$objList->Draw();

require( "$sPath/lib/admin/design/footer.php" );
?>
