<?

class TAdminLinksSchema extends TBaseSchema
{
	function TAdminLinksSchema()
	{
		parent::TBaseSchema();
		$this->KeyField = "id";
		$this->TableName = "adminLeftNav";
		$currentID = 0;
		if(isset($QS["ID"]))
			$currentID = $QS["ID"];
		$this->Fields = array(
			$this->KeyField => array(
				"Caption" => "id",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"filterWidth" => 30,
				"Required" => True
			),
			"caption" => array(
				"Caption" => "Caption",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Required" => True
			),
			"parentID" => array(
				"Caption" => "Parent",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"InputType" => "select",
				"Options" => array( "" => "No Parent") + SQLToArray( "SELECT id, caption FROM adminLeftNav Where ID <> $currentID AND parentID IS NULL;", "id", "caption")
			),
			"path" => array(
				"Caption" => "Path",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"filterWidth" => 250,
				"Required" => False
			),
			"note" => array(
				"Caption" => "Note",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"filterWidth" => 50,
			),
			"rank" => array(
				"Caption" => "Rank",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Value" => 50,
				"filterWidth" => 25,
				"Required" => True
			),
			"visible" => array(
				"Caption" => "Visible",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"filterWidth" => 50,
				"InputType" => "select",
				"Options" => array( "1" => "Visible", 0 => "Hidden" )
			)
		);
	}
	
	function GetListFields()
	{
		$arFields = $this->Fields;
		unset( $arFields["Description"] );
		return $arFields;
	}
	
	function TuneList( &$list )
	{
		/* @var $list TBaseList */
		parent::TuneList( $list );
		$list->SQL = "SELECT * FROM adminLeftNav";
		$list->MultiEdit = False;
		$list->KeyField = $this->KeyField;
#		$objList->DeleteQueries[] = "delete from PictureCategoryLink where PictureID = [ID]";
	}

	function TuneForm(\TBaseForm $form){
		$form->KeyField = $this->KeyField;
		$form->Uniques = array(
			array(
				"Fields" => array( "caption" ),
				"ErrorMessage" => "A link with this name already exists. Please choose another link name."
		 	)
		);
	}
	
	function GetFormFields()
	{
		global $sPath;
		$arFields = $this->Fields;
		unset($arFields['id']);
		return $arFields;
	}
}
?>
