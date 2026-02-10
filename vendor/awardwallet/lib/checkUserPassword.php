<?php
require "../kernel/public.php";

if( !isset( $_SESSION['UserID'] ) ){
	echo "Not logged in";
	exit();
}

$q = new TQuery("select Pass from Usr where UserID = {$_SESSION['UserID']}");
if( $q->EOF ){
	echo "User unknown";
	exit();
}

if( md5(ArrayVal($_POST, "Password")) != $q->Fields["Pass"] ){
	echo "Invalid password";
	exit();
}

echo "Authorized"; 
?>