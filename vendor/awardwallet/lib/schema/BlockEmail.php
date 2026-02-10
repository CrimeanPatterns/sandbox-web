<?

class TBlockEmailSchema extends TBaseSchema
{
	function TBlockEmailSchema()
	{
		parent::TBaseSchema();
		$this->TableName = "BlockEmail";
		$this->Fields = array(
			"Email" => array(
				"Size" => 80,
				"Type" => "string",
				"Required" => True,
				"RegExp" => EMAIL_REGEXP,
			),
		);
	}
	
	function TuneForm(\TBaseForm $form)
	{
		parent::TuneForm( $form );
		$form->Uniques = array(
		array(
			"Fields" => array( "Email" ),
			"ErrorMessage" => "This Email already exists",
		) );
	}
}

?>
