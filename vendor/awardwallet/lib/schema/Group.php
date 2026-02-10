<?
class TGroupSchema extends TBaseSchema
{
	function TGroupSchema()
	{
		parent::TBaseSchema();
		$this->TableName = "SiteGroup";
		$this->KeyField = $this->TableName . "ID";
		$this->Description = array("User Admin", "Administrative Groups");
		$this->Fields = array(
			$this->KeyField => array(
				"Caption" => "id",
				"Type" => "integer",
				"Size" => 250,
			),
			"GroupName" => array( 
				"Caption" => "Group Name",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Required" => True,
			),
			"Description" => array( 
				"Caption" => "Description",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px; height: 200px;\"",
				"InputType" => "textarea",
				"HTML" => True,
				"Size" => 3000
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
//		$objList->SQL = "SELECT * FROM " . $this->TableName;
		$list->MultiEdit = False;
		$list->KeyField = $this->KeyField;
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
