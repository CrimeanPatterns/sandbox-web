<?
class TBaseProspectSchema extends TBaseSchema
{
	function TBaseProspectSchema(){
		parent::TBaseSchema();
		$this->TableName = "Prospect";
		$this->KeyField = $this->TableName . "ID";
		$this->DefaultSort = $this->KeyField;
		$this->Fields = array(
			"ProspectID" => array(
				"Caption" => "id",
				"Type" => "integer",
				"Size" => 250,
				"Required" => true
			),
			"Name" => array(
				"Caption" => "Name",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Required" => false,
			),
			"Email" => array(
				"Caption" => "Email",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Required" => false,
				"RegExp" => EMAIL_REGEXP
			),
			"Phone" => array(
				"Caption" => "Phone",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Required" => false,
			),
			"Address" => array(
				"Caption" => "Address",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Required" => false,
			),
			"CityStateZip" => array(
				"Caption" => "City, State, Zip",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Required" => false,
			),
			"LastUseDate" => array( 
				"Caption" => "Date",
				"Type" => "date",
				"InputType" => "date",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Sort" => "LastUseDate DESC",
				"Required" => false,
			),
			"Uses" => array(
				"Caption" => "Number of Uses",
				"Type" => "float",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
				"Required" => false,
				"Note" => "Numeric value"
			),

		);
	}
	function GetListFields()
	{
		$arFields = $this->Fields;
		unset($arFields['Address']);
		unset($arFields['CityStateZip']);
		return $arFields;
	}
	
	function TuneList( &$list )
	{
		/* @var $list TBaseList */
		parent::TuneList( $list );
#		$objList->SQL = "SELECT * FROM " . $this->TableName;
		$list->MultiEdit = True;
		$list->KeyField = $this->KeyField;
	}

	function GetFormFields()
	{
		$arFields = $this->Fields;
		unset($arFields[$this->KeyField]);
		return $arFields;
	}
	
	function TuneForm(\TBaseForm $form){
		parent::TuneForm( $form );
		$form->Uniques = array(
			array(
				"Fields" => array( "Email" ),
				"ErrorMessage" => "Prospect with this email already exists. Please choose another email"
			)
		);
	}
}
?>
