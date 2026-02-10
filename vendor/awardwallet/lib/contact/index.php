<?
require( "$sPath/lib/classes/TBaseContactForm.php" );
if(!isset($arFormFields))
	require( "$sPath/lib/contact/contactFields.php" );

$objForm = New TBaseContactForm( $arFormFields );
$objForm->CheckAndSend();
$objForm->Show();

?>
