<?php
// -----------------------------------------------------------------------
// link management
// Author: Alexi Vereschaga, ITlogy LLC, alexi@itlogy.com, www.ITlogy.com 
// -----------------------------------------------------------------------
require( "../../kernel/public.php" );
require_once( "$sPath/lib/classes/TPictureFieldManager.php" );
require_once( "$sPath/lib/classes/TTableLinksFieldManager.php" );
require_once( "$sPath/kernel/TForm.php" );

if(!isset($QS["cnf"]))
	DieTrace( "Invalid URL" );
else
	$configFile = $QS["cnf"];
if( !isset( $QS["ID"] ) )
	$QS["ID"] = 0;
# Sometimes it is required to create the form object with this parameter set to false. By default it is true...
$completeFeilds = true;

$nID = $QS["ID"];
#$_SESSION["UserID"] = $QS["ID"];
#print "<script>alert('{$_SESSION["UserID"]}')</script>";

require($sPath . $configFile);
if(!isset($editFormFields))
	$editFormFields = $arFormFields;
#print "<script>alert('pesda')</script>";
$objForm = new TForm($editFormFields, $completeFeilds);
if(isset($formDetailsPath)){
	require($sPath . $formDetailsPath);
}
$objForm->KeyField = $vKeyField;
if( $nID == 0 )
{
	$pageTitle = "Add a New {$sItem}";
	$sButtonCaption = "Add";
}
else
{
	$pageTitle = "Edit {$sItem} Details";
	$sButtonCaption = "Update";
}
$objForm->SubmitButtonCaption = $sButtonCaption;
if(isset($avSuccessURL))
	$objForm->SuccessURL = $avSuccessURL;
else
	$objForm->SuccessURL = "/lib/admin/list.php";

if(isset($vSqlParams))
	$objForm->SQLParams = $vSqlParams;

if(isset($uniques))
	$objForm->Uniques = $uniques;
$objForm->Title = $pageTitle;
$objForm->TableName = $sTableName;
if( !$completeFeilds )
	$objForm->CompleteFields();

require( "$sPath/lib/admin/design/header.php" );
?>
<div align="center">
<?
if(isset($objForm->Pages))
	$objForm->DrawPageNavigation();
print "<br>";
echo $objForm->Edit();
?>
</div>
<?
require( "$sPath/lib/admin/design/footer.php" );

function CheckAgree()
{
	global $objForm, $nID;
	if( ( $nID != 0 ) || ( ArrayVal( $_POST, "submitButton" ) == "" ) )
		return NULL;
	if( $objForm->Fields["Agree"]["Value"] != "1" )
		return "You have to agree to the ".SITE_NAME." policy";
	else
		return NULL;
}
function LoginNewUser( $nUserID ){}
?>