<?
class TBaseAdminCouponSchema extends TBaseSchema
{
	function TBaseAdminCouponSchema()
	{
		parent::TBaseSchema();
		$this->TableName = "Coupon";
		$this->Fields = array(
			"Name" => array(
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 80,
				"Required" => True,
			),
			"Code" => array(
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 80,
				"FilterField" => "c.Code",
				"Required" => True,
				"Note" => "No spaces",
				"RegExp" => '/^[^ ]+$/ims'
			),
			"Discount" => array(
				"Type" => "integer",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Note" => "Discount percent",
				"Min" => 1,
				"Max" => 100,
				"Required" => True,
			),
			"StartDate" => array(
				"Type" => "date",
				"Note" => "format: mm/dd/yyyy",
				"InputType" => "date",
				"InputAttributes" => "style=\"width: 300px;\"",
			),
			"EndDate" => array(
				"Type" => "date",
				"Note" => "format: mm/dd/yyyy",
				"InputType" => "date",
				"InputAttributes" => "style=\"width: 300px;\"",
			),
			"MaxUses" => array(
				"Type" => "integer",
				"Required" => False,
			),
			"Uses" => array(
				"Type" => "integer",
				"Required" => False,
                'FilterType' => 'having',
			),
			"LastUseDate" => array(
				"Type" => "date",
				"Required" => False,
                'FilterType' => 'having',
			),
			"UserId" => array(
				"Type" => "integer",
				"Required" => False,
				"AllowFilters" => True,
				"FilterField" => "SUBSTRING( c.Code, LOCATE(  '-', c.Code ) +1, LOCATE(  '-', c.Code, LOCATE(  '-', c.Code ) +1 ) - LOCATE(  '-', c.Code ) - 1)"
			)
		);
		$this->ListClass = \AwardWallet\MainBundle\Manager\Schema\AdminCouponList::class;
	}

	function GetListFields(){
		$arFields = $this->Fields;
		return $arFields;
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm( $form );
		$form->OnSave = array( &$this, "OnSaveForm", $form );
	}


	function OnSaveForm( &$objForm ){

	}

	function GetFormFields(){
		$arFields = $this->Fields;
		unset( $arFields["LastUseDate"] );
		unset( $arFields["Uses"] );
		unset( $arFields["UserId"] );
		return $arFields;
	}

}

?>
