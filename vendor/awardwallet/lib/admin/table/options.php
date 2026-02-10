<?php
require( "../../../kernel/public.php" );
$objSchema = LoadSchema( ArrayVal( $QS, "Schema" ) );
$objSchema->Admin = True;
if(!isset($objSchema->Fields["Name"]))
	die("Name field not found");
DrawArrayOptions(SQLToArray("select {$objSchema->KeyField}, Name from {$objSchema->TableName} order by Name",
	$objSchema->KeyField, "Name"), "");
?>