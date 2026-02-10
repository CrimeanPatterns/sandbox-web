<?
class TFaqCategorySchema extends TBaseSchema
{
	function TFaqCategorySchema()
	{
		parent::TBaseSchema();
		$this->TableName = "FaqCategory";
		$this->KeyField = "FaqCategoryID";
		$this->Description = array("Resources", "FAQ Categories");
		$this->Fields = array(
			"FaqCategoryID" => array(
				"Caption" => "id",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Required" => True
			),
			"CategoryTitle" => array(
				"Caption" => "Title",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Required" => True
			),
			"Rank" => array(
				"Caption" => "Rank",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Value" => 50,
				"Required" => True
			),
			"Visible" => array( 
				"Caption" => "Visible",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"InputType" => "select",
				"Options" => array( "1" => "Visible", 0 => "Hidden" )
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
		$list->DeleteQueries[] = "delete from Faq where FaqCategoryID = [ID]";
	}

	function TuneForm(\TBaseForm $form){
		$form->KeyField = $this->KeyField;
	}

	function GetFormFields()
	{
		$arFields = $this->Fields;
		unset($arFields[$this->KeyField]);
		return $arFields;
	}
}
?>
