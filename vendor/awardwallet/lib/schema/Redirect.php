<?

class TRedirectSchema extends TBaseSchema
{
	function TRedirectSchema(){
		parent::TBaseSchema();
		$this->TableName = "Redirect";
//		$this->bIncludeList = false;
		$this->ListClass = "TRedirectList";
		$this->Fields = array(
			"RedirectID" => array(
				"Caption" => "ID",
			    "Type" => "integer",
			    "Required" => True,
				"FilterField" => "r.RedirectID",
			),
			"Name" => array(
			    "Type" => "string",
			    "Size" => 128,
				"Required" => True,
			),
			"URL" => array(
				"Caption" => "URL",
				"InputAttributes" => "style=\"width: 500px;\"",
			    "Type" => "string",
			    "Size" => 1000,
			    "Required" => True
			),
		);
	}

	function GetFormFields(){
		$arFields = parent::GetFormFields();
		unset($arFields["RedirectID"]);
		return $arFields;
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm($form);
		$form->Uniques = array(
		  array(
		    "Fields" => array( "URL" ),
		    "ErrorMessage" => "This URL already exists. Please choose another URL."
		  )
		);
	}

}
?>
