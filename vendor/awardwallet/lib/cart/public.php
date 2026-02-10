<?
if( file_exists( __DIR__."/../../kernel/TCart.php" ) )
	require_once( __DIR__."/../../kernel/TCart.php" );
else
	require_once( __DIR__."/../classes/TCart.php" );

global $objCart;
$objCart = new TCart();

if( ( isset( $_SESSION['UserID'] ) || $objCart->Anonymous ) && !preg_match( "/^\/lib\/errordocs\//i", $_SERVER['SCRIPT_NAME'] ) )
	$objCart->Open();
