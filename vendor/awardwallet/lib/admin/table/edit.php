<?
require( "../../../kernel/public.php" );
$objSchema = LoadSchema( ArrayVal( $QS, "Schema" ) );
$objSchema->Admin = true;
require( "$sPath/lib/admin/design/header.php" );
if(isset($objSchema->Description) && $objSchema->Description != "")
	print $objSchema->DrawDescription() . "<br>";
?>
<div align="center">
<?$objSchema->ShowForm();?>
</div>
<?
require( "$sPath/lib/admin/design/footer.php" );
if(isset($objSchema->footerScripts))
    print $objSchema->footerScripts;
?>
