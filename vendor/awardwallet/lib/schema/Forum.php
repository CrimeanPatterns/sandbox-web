<?
class TForumSchema extends TBaseSchema
{
	var $forumNumber;
	function TForumSchema(){
		global $QS, $Config;
		parent::TBaseSchema();
		$this->TableName = "Forum";
		$this->KeyField = "ForumID";
		$currentID = 0;
		if(isset($QS["ID"]))
			$currentID = $QS["ID"];
		$this->Fields = array(
			$this->KeyField => array(
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
				"Note" => $Config["dateNote"] ?? '',
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
				"InputType" => "htmleditor",
				"Width" => 600,
				"Height" => 400,
				"HTML" => True,
				"Required" => True,
				"Size" => 100000
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
		if(isset($QS["plainText"])){
			$this->Fields["BodyText"] = array(
				"Type" => "string",
				"InputType" => "textarea",
				"InputAttributes" => " style=\"width: 300px; height: 300px;\"",
				"Required" => false,
				"Size" => 100000
			);
		}
	}
	
	function GetListFields()
	{
		$arFields = $this->Fields;
		return $arFields;
	}
	
	function TuneList( &$list )
	{
		/* @var $list TBaseList */
		parent::TuneList( $list );
		$list->SQL = "SELECT * FROM Forum WHERE forumNumber = " . $this->forumNumber;
		$list->MultiEdit = False;
		$list->KeyField = $this->KeyField;
#		$objList->DeleteQueries[] = "delete from PictureCategoryLink where PictureID = [ID]";
	}

	function TuneForm(\TBaseForm $form){
		$form->KeyField = $this->KeyField;
		$form->SQLParams = array( "IP" => "'".addslashes( $_SERVER["REMOTE_ADDR"] )."'", "forumNumber" => $this->forumNumber);
	}
	
	function GetFormFields()
	{
		$arFields = $this->Fields;
		unset($arFields[$this->KeyField]);
		return $arFields;
	}
}
?>
