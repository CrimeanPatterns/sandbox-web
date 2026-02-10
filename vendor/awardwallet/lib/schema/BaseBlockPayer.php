<?

class TBaseBlockPayerSchema extends TBaseSchema
{
	function TBaseBlockPayerSchema()
	{
		parent::TBaseSchema();
		$this->TableName = "BlockPayer";
		$this->Fields = array(
			"Email" => array(
				"Size" => 80,
				"Type" => "string",
				"RequiredGroup" => "Filter",
				"RegExp" => EMAIL_REGEXP,
			),
			"IP" => array(
				"Size" => 15,
				"Type" => "string",
				"RequiredGroup" => "Filter",
				"RegExp" => "/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/",
				"Caption" => "IP",
				"InputAttributes" => "style='width: 100px;'"
			),
			"FullName" => array(
				"Size" => 80,
				"Type" => "string",
				"RequiredGroup" => "Filter",
			),
			"UserID" => array(
				"Type" => "integer",
				"RequiredGroup" => "Filter",
				"RegExp" => "/^\d+$/",
				"Caption" => "UserID",
			),
			"CreditCardNumber" => array(
				"Size" => 80,
				"Type" => "string",
				"RequiredGroup" => "Filter",
			),
		);
	}
}

?>
