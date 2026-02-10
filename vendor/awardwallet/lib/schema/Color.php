<?

class TColorSchema extends TBaseSchema
{
	function TColorSchema()
	{
		parent::TBaseSchema();
		$this->TableName = "Color";
		$this->KeyField = $this->TableName . "ID";
		$this->DefaultSort = "Name";
		$this->Fields = array(
			$this->KeyField => array(
				"Caption" => "id",
				"Type" => "integer",
				"Size" => 250,
				"Required" => True
			),
			"Name" => array(
				"Caption" => "Name",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 150px;\"",
				"Size" => 250,
				"Required" => True,
			),
			"Color" => array(
				"Caption" => "Color",
				"Type" => "customCode",
				"Size" => 250,
				"Value" => "return \"<table cellspacing=0 cellpadding=0 width='150'><tr><td bgcolor=#{\$arFields[\"Hex\"]}>&nbsp;</td></table>\";"
			),
			"Hex" => array(
				"Caption" => "Hex",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 150px;\"",
				"Size" => 250,
				"Required" => False,
			),
		);
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
		$list->SQL = "SELECT * FROM " . $this->TableName;
		$list->MultiEdit = False;
		$list->KeyField = $this->KeyField;
#		$objList->DeleteQueries[] = "delete from PictureCategoryLink where PictureID = [ID]";
#		unset($objList->Sorts["Color"]);
	}

	function TuneForm(\TBaseForm $form){
		$form->KeyField = $this->KeyField;
		$form->Uniques = array(
			array(
				"Fields" => array( "Name" ),
				"ErrorMessage" => "A color with this name already exists. Please choose another color name."
		 	)
		);
	}

	function GetFormFields()
	{
		global $sPath;
		$arFields = $this->Fields;
		unset($arFields[$this->KeyField]);
		unset($arFields['Color']);
		return $arFields;
	}
}
?>
