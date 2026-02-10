<?
$sTableName = "SiteAd";
$vSql = "SELECT * FROM SiteAd";
$vKeyField = "SiteAdID";
$AllowDeletes = true;
$sItem = "Site Ad";
$vDefaultOrder = "Registers";

$arFormFields = array(
	"SiteAdID" => array(
		"Caption" => "id",
		"Type" => "integer",
		"Size" => 250,
		"Required" => True
	),
	"Description" => array( 
		"Caption" => "Description",
		"Type" => "string",
		"InputAttributes" => "style=\"width: 300px;\"",
		"Size" => 250,
		"Required" => True
	),
	"StartDate" => array( 
		"Caption" => "Start",
		"Type" => "date",
		"Note" => $Config["dateNote"],
		"InputType" => "date",
		"InputAttributes" => "style=\"width: 300px;\"",
		"Required" => True
	),
	"LastClick" => array( 
		"Caption" => "Last Click",
		"Type" => "date",
		"Note" => $Config["dateNote"],
		"InputType" => "date",
		"InputAttributes" => "style=\"width: 300px;\"",
	),
	"LastRegister" => array( 
		"Caption" => "Last Register",
		"Type" => "date",
		"Note" => $Config["dateNote"],
		"InputType" => "date",
		"InputAttributes" => "style=\"width: 300px;\"",
	),
	"Clicks" => array( 
		"Caption" => "Clicks",
		"Type" => "integer",
		"InputAttributes" => "style=\"width: 300px;\"",
		"Size" => 250,
		"Value" => 0
	),
	"Registers" => array( 
		"Caption" => "Registers",
		"Type" => "integer",
		"InputAttributes" => "style=\"width: 300px;\"",
		"Size" => 250,
		"Value" => 0
	),
	"Effectiveness" => array(
		"Caption" => "Effectiveness",
		"Type" => "customCode",
		"InputAttributes" => "style=\"width: 300px;\"",
		"Size" => 250,
		"Value" => "if(\$arFields[\"Clicks\"] != 0){\$mainWidth = number_format( \$arFields[\"Registers\"] / \$arFields[\"Clicks\"] * 100, 0, \".\", \",\" ); \$remWidth = 100 - (float) \$mainWidth; \$result = \"<table width=170 border=0 id='noBorder' cellspacing=0 cellpadding=0><tr>\"; if(\$mainWidth != 0){\$result .= \"<td bgcolor='#AE0001' width='\" . \$mainWidth . \"'>\" . PIXEL . \"</td>\";}  if(\$remWidth != 0){\$result .= \"<td bgcolor='#FFD090' width='\" . \$remWidth . \"'>\" . PIXEL . \"</td>\";} \$result .= \"<td nowrap style='font-size: 10px;'>&nbsp;(\" . number_format( \$arFields[\"Registers\"] / \$arFields[\"Clicks\"] * 100, 2, \".\", \",\" ) . \"%)</td></tr></table>\"; return \$result;}"
	),
	"Link Sample" => array(
		"Caption" => "Link Sample",
		"Type" => "customCode",
		"InputAttributes" => "style=\"width: 300px;\"",
		"Size" => 250,
		"Value" => "return \"<a target='_blank' href='/index.php?ref=\" . \$arFields[\"SiteAdID\"] .\"'>sample</a>\";"
	),
);
$listFormFields = $arFormFields;
$editFormFields = $arFormFields;
unset($editFormFields['SiteAdID']);
unset($editFormFields['Effectiveness']);
unset($editFormFields['Link Sample']);
unset($listFormFields['StartDate']);
?>