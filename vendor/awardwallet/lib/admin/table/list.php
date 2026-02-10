<?
require( "../../../kernel/public.php" );
$objSchema = LoadSchema( ArrayVal( $QS, "Schema" ) );
$objSchema->Admin = True;
require( "$sPath/lib/admin/design/header.php" );
if(isset($objSchema->Description) && $objSchema->Description != "")
	print $objSchema->DrawDescription();
?>
<div align="center">
<?$objSchema->ShowList();?>
</div>
<?
require( "$sPath/lib/admin/design/footer.php" );
?>