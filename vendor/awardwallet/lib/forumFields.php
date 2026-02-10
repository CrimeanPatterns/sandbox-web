<?
$sTableName = "Forum";
$vSql = "SELECT * FROM Forum WHERE forumNumber = $vForumNum";
$vKeyField = "ForumID";
$AllowDeletes = true;
$vSqlParams = array( "IP" => "'".addslashes( $_SERVER["REMOTE_ADDR"] )."'", "forumNumber" => $vForumNum);

$arFormFields = array(
	"ForumID" => array(
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
	"PostTime" => array( 
		"Caption" => "Date",
		"Type" => "date",
		"Note" => $Config["dateNote"],
		"InputType" => "date",
		"InputAttributes" => "style=\"width: 300px;\"",
		"Required" => True
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
		"Size" => 10000
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
	)
);
$listFormFields = $arFormFields;
$editFormFields = $arFormFields;
?>