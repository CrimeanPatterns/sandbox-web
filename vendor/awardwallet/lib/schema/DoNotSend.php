<?

class TDoNotSendSchema extends TBaseSchema
{
	function TDoNotSendSchema()
	{
		parent::TBaseSchema();
		$this->TableName = "DoNotSend";
		$this->Fields = array(
			"Email" => array(
				"Size" => 80,
				"Type" => "string",
				"Required" => True,
				"RegExp" => EMAIL_REGEXP,
			),
			"AddTime" => array(
				"Type" => "date",
				"IncludeTime" => true,
				"Required" => True,
				"Value" => date(DATE_FORMAT),
			),
			"IP" => array(
				"Caption" => "IP",
				"Size" => 20,
				"Type" => "string",
				"Required" => True,
				"Value" => "127.0.0.1",
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
