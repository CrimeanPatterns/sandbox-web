<?
require_once(__DIR__ . "/Forum.php");

class TInformationalPagesSchema extends TForumSchema
{
	var $forumNumber;
	function TInformationalPagesSchema()
	{
		parent::TForumSchema();
		$this->forumNumber = 5;
		$this->Description = array("Resources", "Informational Pages");
	}
	
	function GetListFields()
	{
		$arFields = parent::GetListFields();
		unset($arFields['FullName']);
		unset($arFields['Email']);
		unset($arFields['BodyText']);
		unset($arFields['Rank']);
		unset($arFields['PostTime']);
		return $arFields;
	}

	function GetFormFields()
	{
		$arFields = parent::GetFormFields();
		$arFields["BodyText"]["InputAttributes"] = "style=\"width: 600px; height: 500px;\"";
		unset($arFields['ForumID']);
		unset($arFields['FullName']);
		unset($arFields['Email']);
		unset($arFields['PostTime']);
		unset($arFields['Rank']);
		return $arFields;
	}
	
	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$form->SQLParams = $form->SQLParams + array("PostTime" => "now()");
	}
	
	function TuneList( &$list )
	{
		parent::TuneList( $list );
		$list->AllowDeletes = false;
	}
}
?>
