<?

class TBlockIPSchema extends TBaseSchema
{
	function TBlockIPSchema()
	{
		parent::TBaseSchema();
		$this->TableName = "BlockIP";
		$this->Fields = array(
			"IP" => array(
				"Size" => 23,
				"Type" => "string",
				"Required" => True,
				"RegExp" => "/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/",
				"Caption" => "IP",
			),
			"DenySiteAccess" => array(
				"Type" => "boolean",
				"Required" => True,
			),
		);
	}
	
	function TuneForm(\TBaseForm $form)
	{
		parent::TuneForm( $form );
		$form->Uniques = array(
		array(
			"Fields" => array( "IP" ),
			"ErrorMessage" => "This IP already exists",
		) );
	}
}

?>
