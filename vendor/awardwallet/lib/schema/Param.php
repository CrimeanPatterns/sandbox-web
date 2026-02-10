<?
class TParamSchema extends TBaseSchema
{
	function TParamSchema()
	{
		parent::TBaseSchema();
		$this->TableName = "Param";
		$this->Fields = array(
			"Type" => array(
				"Options" => array(
					PARAM_TYPE_INTEGER => "Integer",
					PARAM_TYPE_FLOAT => "Float",
					PARAM_TYPE_STRING => "String",
					PARAM_TYPE_TEXT => "Text",
				),
				"InputType" => "select",
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Required" => True,
			),
			"Name" => array(
				"Type" => "string",
				"Size" => 40,
				"InputAttributes" => "style=\"width: 300px;\"",
				"Required" => True,
			),
			"IntVal" => array(
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
			),
			"FloatVal" => array(
				"Type" => "float",
				"InputAttributes" => "style=\"width: 300px;\"",
			),
			"StringVal" => array(
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 250,
			),
			"TextVal" => array(
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"InputType" => "textarea",
			),
		);
	}

	function TuneForm(\TBaseForm $form)
	{
		parent::TuneForm( $form );
		$form->Uniques = array(
			array( 
				"Fields"  => array( "Name" ),
				"ErrorMessage" => "Parameter with this Name already exists",
			),
		);
	}
}
?>
