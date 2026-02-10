<?
require_once(__DIR__ . "/Forum.php");

class TPressReleaseSchema extends TForumSchema{
	var $forumNumber = 9;
	function TPressReleaseSchema(){
		parent::TForumSchema();
		$this->ListClass = "TPressReleaseList";
		$this->Description = array("Resources", "Press Releases");
	}
	function GetListFields(){
		$arFields = $this->Fields;

		unset($arFields['Email']);
		unset($arFields['FullName']);
        unset($arFields['Rank']);
		unset($arFields['BodyText']);

		return $arFields;
	}
	function GetFormFields(){
		$arFields = $this->Fields;

        $arFields["Email"]["Caption"] = "Description (list only)";
        $arFields["Email"]["InputType"] = "textarea";

        $arFields["Title"]["Caption"] = "Title (list only)";
        $arFields["Title"]["InputType"] = "textarea";

		unset($arFields['FullName']);
		unset($arFields['URL']);
        unset($arFields['Rank']);

		unset($arFields['ForumID']);

		return $arFields;
	}
}
?>
