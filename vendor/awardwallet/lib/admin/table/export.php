<?
require( "../../../kernel/public.php" );
$objSchema = LoadSchema( ArrayVal( $QS, "Schema" ) );
$objSchema->Admin = True;
$objSchema->ExportCSV();

?>