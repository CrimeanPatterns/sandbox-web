<?

class TBaseAffTransactionSchema extends TBaseSchema
{
	function TBaseAffTransactionSchema()
	{
		parent::TBaseSchema();
		$this->TableName = "AffTransaction";
		$this->Fields = array(
			"CreationDate" => array(
				"Caption" => "Date",
				"InputAttributes" => "style=\"width: 279px;\"",
				"Type" => "date",
				"IncludeTime" => True,
				"Value" => date( FORM_DATE_TIME_FORMAT, time() ), 
			),
			"UserID" => array(
				"Caption" => "User ID",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Type" => "integer",
				"Required" => True, 
			),
			"Kind" => array(
				"Type" => "integer",
				"Size" => 80,
				"InputAttributes" => "style=\"width: 300px;\"",
				"Required" => True,
				"Value" => AFF_TRAN_KIND_PAYMENT,
				"InputType" => "select",
				"Options" => array(
					AFF_TRAN_KIND_PAYMENT => "Payment",
					AFF_TRAN_KIND_COMMISSION => "Commission",
					AFF_TRAN_KIND_REFUND => "Refund",
					AFF_TRAN_KIND_CHARGE_BACK => "Charge Back",
				),
			),
			"Product" => array(
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 80,
				"Required" => True,
			),
			"ItemKind" => array(
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
			),
			"ItemID" => array(
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Caption" => "Item ID",
			),
			"Cost" => array(
				"Caption" => "Product Cost",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Type" => "float",
			),
			"Commission" => array(
				"Type" => "float",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Required" => True,
			),
			"Comments" => array(
				"Type" => "string",
				"Size" => 512,
				"InputAttributes" => "style=\"width: 300px; height: 100px;\"",
				"InputType" => "textarea",
			),
			"State" => array(
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px\"",
				"Size" => 80,
				"Required" => True,
				"Value" => AFF_TRAN_STATE_APPROVED,
				"InputType" => "select",
				"Options" => array(
					AFF_TRAN_STATE_APPROVED => "Approved",
					AFF_TRAN_STATE_PENDING => "Pending",
				),
			),
			"Balance" => array(
				"Type" => "float",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Required" => True,
			),
		);
	}
	
	function GetListFields()
	{
		$arFields = parent::GetListFields();
		$arFields['CreationDate']['Type'] = 'datetime';
		$arFields['Cost']['Type'] = 'money';
		$arFields['Commission']['Type'] = 'money';
		$arFields['Balance']['Type'] = 'money';
		unset($arFields['Product']);
		unset($arFields['Comments']);
		return $arFields;
	}
	
}

?>
