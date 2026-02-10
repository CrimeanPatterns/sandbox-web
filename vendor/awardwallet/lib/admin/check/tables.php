<?
require("../../../kernel/public.php");
if(isset($Config["RussianSite"]))
	require_once("$sPath/lib/classes/TBaseFormRusConstants.php");
elseif(isset($Config["SpanishSite"]))
	require_once("$sPath/lib/classes/TBaseFormSpaConstants.php");
else
	require_once("$sPath/lib/classes/TBaseFormEngConstants.php");

require("$sPath/lib/admin/design/header.php");

$objSchemaManager = new TSchemaManager();
/*echo "<pre>";
var_dump( $objSchemaManager->Tables );
echo "</pre>";
die();*/
$arTables = array( "All" );
$arTables += array_keys( $objSchemaManager->Tables );
$arTables = array_combine( $arTables, $arTables );
$objForm = new TBaseForm( array(
	"Table" => array(
		"Type" => "string",
		"InputType" => "select",
		"Options" => $arTables,
		"Required" => True,
	),
	"Check" => array(
		"Type" => "string",
		"InputType" => "select",
		"Options" => array( "References" => "References", "Files" => "Files" ),
		"Required" => True,
	),
) );
$objForm->SubmitButtonCaption = "Check integrity";

if( $objForm->IsPost )
{
	$objForm->Check();
	switch( $objForm->Fields["Check"]["Value"] ) {
		case "References":
			CheckReferences();
			break;
		case "Files":
			CheckFiles();
			break;
	}
}

echo $objForm->HTML();

require("$sPath/lib/admin/design/footer.php");

function CheckTableReferences( $sTable ) {
	global $objSchemaManager;	
	echo "<b>Checking references table $sTable</b><br>\n";
	$q = new TQuery("select * from $sTable");
	$sPrimaryKey = $objSchemaManager->Tables[$sTable]["PrimaryKey"];
	$n = 0;
	while( !$q->EOF )
	{
		$arRows = $objSchemaManager->ParentRows( $sTable, $q->Fields );
		foreach( $arRows as $arRow )
			if( !$arRow["Exist"] )
			{
				$n++;
				echo "<input type=checkbox name=Row{$n} value=1> {$q->Fields[$sPrimaryKey]}: Broken reference {$arRow["Field"]} = {$q->Fields[$arRow["Field"]]} to {$arRow["Table"]}<input type=hidden name=Table{$n} value={$sTable}><input type=hidden name=ID{$n} value={$q->Fields[$sPrimaryKey]}><input type=hidden name=Field{$n} value={$arRow["Field"]}><br>\n";
			}
		$q->Next();
	}
}

function CheckReferences(){
	global $objForm, $objSchemaManager, $Connection;
	if( ArrayVal( $_POST, "Null" ) != "" )
	{
		echo "setting selected references to null..<br>\n";
		$arRows = array();
		foreach ( $_POST as $sKey => $sValue )
			if( preg_match( "/^Row(\d+)$/i", $sKey, $arMatches ) && ( $sValue == "1" ) )
			{
				$n = $arMatches[1];
				$sTable = $_POST["Table$n"];
				$sField = $_POST["Field$n"];
				$nID = $_POST["ID$n"];
				$sKey = $objSchemaManager->Tables[$sTable]["PrimaryKey"];
				$sSQL = "update $sTable set $sField = null where $sKey = $nID";
				echo $sSQL . "<br>\n";
				$Connection->Execute( $sSQL );
			}
				
		echo "<p>finished nulling</p>\n";
	}
	else
	{
		if( !isset( $objForm->Error ) )
		{
			echo "checking references..<br>\n";
			echo "<form method=post>\n";
			echo "<input type=hidden name=Table value={$objForm->Fields["Table"]["Value"]}>\n";
			echo "<input type=hidden name=Check value={$objForm->Fields["Check"]["Value"]}>\n";
			if( $objForm->Fields["Table"]["Value"] == "All" )
			{
				foreach ( $objSchemaManager->Tables as $sTable => $arTable )
					if( count( $arTable["References"] ) > 0 )
						CheckTableReferences( $sTable );
			}
			else
				CheckTableReferences( $objForm->Fields["Table"]["Value"] );
			echo "<p>finished checking</p>\n";
			//echo "<input type=submit class=button name=Explore value=\"Explore selected records\"> ";
			echo "<input type=submit class=button name=Null value=\"Set selected records to null\"> ";
			echo "</form>\n";
			echo "<p>";
		}
	}
}

function CheckFiles(){
	global $objForm, $objSchemaManager, $Connection;
	if( ArrayVal( $_POST, "DeleteAndNull" ) != "" )
	{
		echo "setting to null..<br>\n";
		$arRows = array();
		foreach ( $_POST as $sKey => $sValue )
			if( preg_match( "/^Row(\d+)$/i", $sKey, $arMatches ) && ( $sValue == "1" ) )
			{
				$n = $arMatches[1];
				$sTable = $_POST["Table$n"];
				$sField = $_POST["Field$n"];
				$nID = $_POST["ID$n"];
				$sKey = $objSchemaManager->Tables[$sTable]["PrimaryKey"];
				$sSQL = "update $sTable set $sField = null where $sKey = $nID";
				echo $sSQL . "<br>\n";
				$Connection->Execute( $sSQL );
			}
				
		echo "<p>finished nulling</p>\n";
	}
	else
	{
		if( !isset( $objForm->Error ) )
		{
			echo "checking files..<br>\n";
			echo "<form method=post>\n";
			echo "<input type=hidden name=Table value={$objForm->Fields["Table"]["Value"]}>\n";
			echo "<input type=hidden name=Check value={$objForm->Fields["Check"]["Value"]}>\n";
			if( $objForm->Fields["Table"]["Value"] == "All" )
			{
				foreach ( $objSchemaManager->Tables as $sTable => $arTable )
					if( count( $arTable["Files"] ) > 0 )
						CheckTableFiles( $sTable );
			}
			else
				CheckTableFiles( $objForm->Fields["Table"]["Value"] );
			echo "<p>finished checking</p>\n";
			//echo "<input type=submit class=button name=Explore value=\"Explore selected records\"> ";
			echo "<input type=submit class=button name=Null value=\"Set selected records to null\"> ";
			echo "</form>\n";
			echo "<p>";
		}
	}
}

function CheckTableFiles( $sTable ){
	global $objSchemaManager, $sPath;
	$arTable = $objSchemaManager->Tables[$sTable];
	echo "<b>Checking files of table $sTable</b><br>\n";
	$q = new TQuery("select * from $sTable");
	$sPrimaryKey = $objSchemaManager->Tables[$sTable]["PrimaryKey"];
	$n = 0;
	while( !$q->EOF )
	{
		$arFiles = $objSchemaManager->RowFiles( $sTable, $q->Fields );
		foreach( $arFiles as $arFile )
			if( !$arFile["Exist"] ) {
				$n++;
				echo "<input type=checkbox name=Row{$n} value=1> {$q->Fields[$sPrimaryKey]}: Missing {$arFile["Field"]} file {$sPath}{$arFile["File"]}<input type=hidden name=Table{$n} value={$sTable}><input type=hidden name=ID{$n} value={$q->Fields[$sPrimaryKey]}><input type=hidden name=Field{$n} value={$arFile["Field"]}><br>\n";
			}
		$q->Next();
	}
}

?>