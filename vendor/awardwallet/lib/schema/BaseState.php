<?

class TBaseStateSchema extends TBaseSchema{
	function TBaseStateSchema(){
		parent::TBaseSchema();
		$this->TableName = "State";
		$this->KeyField = $this->TableName . "ID";
		$this->DefaultSort = $this->KeyField;
		$this->Description = array("Resources", "States");
		$this->Fields = array(
			$this->KeyField => array(
				"Caption" => "id",
				"Type" => "integer",
				"Size" => 250,
				"filterWidth" => 30,
				"Required" => True
			),
			"CountryID" => array(
				"Caption" => "Country",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\" disabled",
				"Size" => 250,
				"InputType" => "select",
				"Options" => SQLToArray( "select CountryID, Name from Country", "CountryID", "Name" ),
				"Required" => False
			),
			"Code" => array(
				"Caption" => "Code",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Required" => True,
			),
			"Name" => array(
				"Caption" => "Name",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Required" => True,
			),
			"AreaID" => array(
				"Caption" => "Area",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\" disabled",
				"Size" => 250,
				"InputType" => "select",
				"Options" => SQLToArray( "select AreaID, Name from StateArea", "AreaID", "Name" ),
				"Required" => False
			),
		);
	}

	function GetFormFields()
	{
		$arFields = parent::GetFormFields();
		unset($arFields[$this->KeyField]);
		return $arFields;
	}

	function TuneList( &$list )
	{
		/* @var $list TBaseList */
		parent::TuneList( $list );
		$list->ReadOnly = false;
		$list->CanAdd = true;
		$list->AllowDeletes = true;
		$list->ShowEditors = true;
		$list->ShowFilters = True;
		$list->MultiEdit = True;
	}
}
?>
