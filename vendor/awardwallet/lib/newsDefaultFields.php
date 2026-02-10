<?
// create picture manager
require_once( "$sPath/lib/classes/TPictureFieldManager.php" );
$objPictureManager = new TPictureFieldManager();
$objPictureManager->Dir = "/images/uploaded/news";
$objPictureManager->Prefix = "news";
$objPictureManager->thumbWidth = 150;
$objPictureManager->thumbHeight = 150;

$sTableName = "News";
$vSql = "SELECT * FROM News WHERE NewsNumber = $iNewsNum";
$vKeyField = "NewsID";
$AllowDeletes = true;
$vSqlParams = array( "NewsNumber" => $iNewsNum);

$arFormFields = array(
	"NewsID" => array(
		"Caption" => "id",
		"Type" => "integer",
		"Size" => 250,
		"Required" => True
	),
	"FullName" => array( 
		"Caption" => "Full Name",
		"Type" => "string",
		"InputAttributes" => "style=\"width: 300px;\"",
		"Size" => 250
	),
	"Email" => array( 
		"Caption" => "Email",
		"Type" => "string",
		"InputAttributes" => "style=\"width: 300px;\"",
		"Size" => 250
	),
	"NewsTime" => array( 
		"Caption" => "Date",
		"Type" => "date",
		"Note" => $Config["dateNote"],
		"InputType" => "date",
		"InputAttributes" => "style=\"width: 300px;\""
	),
	"Title" => array( 
		"Caption" => "Title",
		"Type" => "string",
		"InputAttributes" => "style=\"width: 300px;\"",
		"Size" => 250
	),
	"BodyText" => array( 
		"Caption" => "Text Body",
		"Type" => "string",
		"InputAttributes" => "style=\"width: 300px; height: 200px;\"",
		"InputType" => "textarea",
		"HTML" => True,
		"Required" => True,
		"Size" => 4000
	),
	"Visible" => array( 
		"Caption" => "Visible",
		"Type" => "integer",
		"InputAttributes" => "style=\"width: 300px;\"",
		"Size" => 250,
		"InputType" => "select",
		"Options" => array( "1" => "Visible", 0 => "Hidden" )
	),
	"Rank" => array( 
		"Caption" => "Rank",
		"Type" => "integer",
		"InputAttributes" => "style=\"width: 300px;\"",
		"Size" => 250
	),
	"NewsPhoto" => array( 
		"Caption" => "News Picture",
		"Note" => "Optionaly upload a picture for this news article",
		"Type" => "custom",
		"InputAttributes" => "style=\"width: 300px;\"",
		"Manager" => $objPictureManager,
	)
);
$listFormFields = $arFormFields;
$editFormFields = $arFormFields;

unset($editFormFields['NewsID']);
unset($listFormFields['NewsPhoto']);
?>