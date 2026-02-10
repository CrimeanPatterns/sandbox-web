<?
$arFormFields = "";

if(!isset($_SESSION["UserID"])){
	require_once("$sPath/lib/classes/TCaptchaFieldManager.php")	;
	$objCaptchaManager = new TCaptchaFieldManager();
	$arFormFields = array(
		"fullName" => array( 
			"Caption" => NAME_CAPTION,
			"Type" => "string",
			"Size" => 50,
			"InputAttributes" => "style=\"width: 250;\"",
			"MinSize" => 4,
			"Cols" => 34,
			"Required" => True 
		),
		"email" => array( 
			"Caption" => EMAIL_CAPTION,
			"Type" => "string",
			"Size" => 50,
			"InputAttributes" => "style=\"width: 250;\"",
			"Cols" => 34,
			"Required" => True,
			"RegExp" => EMAIL_REGEXP
		),
		"phone1" => array( 
			"Caption" => PHONE_CAPTION,
			"Type" => "string",
			"InputAttributes" => "style=\"width: 250;\"",
			"Size" => 50,
			"Cols" => 34
		),
		"captcha" => array(
			"Caption" => "Security Number",
			"Type" => "string",
			"Note" => "Input the number that you see on the image above",
			"Required" => True,
			"Manager" => $objCaptchaManager,
		),
	);
}
if(!is_array($arFormFields))
	$arFormFields = array();

$arFormFields["requestType"] = array(
		"Caption" => REQUSEST_CAPTION,
		"Type" => "string",
		"Size" => 30,
		"Cols" => 40,
		"Required" => True,
		"InputAttributes" => "style=\"width: 250;\"",
		"Options" => $REQUEST_TYPE_OPTIONS
	);
$arFormFields["message"] = array(
		"Caption" => MSG_CAPTION,
		"InputType" => "textarea",
		"Type" => "string",
		"Cols" => 52,
		"Rows" => 15,
		"Required" => True 
);
?>