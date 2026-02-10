<?

require_once(__DIR__ . "/BaseUserAffiliate.php");

class TAdminAffiliateSchema extends TBaseUserAffiliateSchema 
{
	function TAdminAffiliateSchema()
	{
		parent::TBaseUserAffiliateSchema();
		$this->Fields = array(
			"AffApproved" => array(
				"Type" => "boolean",
				"Caption" => "Approved",
				"Required" => True,
			),
		)
		+ $this->Fields;
		$this->Admin = True;
	}
	
	function GetListFields()
	{
		$arFields = array( 
			"Login" => array(
				"Type" => "string",
			),
			"FirstName" => array(
				"Type" => "string",
			),
			"LastName" => array(
				"Type" => "string",
			),
			"AffRegisterDate" => array(
				"Caption" => "Registration Date",
				"Type" => "date",
			),
		)
		+ parent::GetListFields();
		unset( $arFields["AffBusinessBirthDate"] );
		unset( $arFields["AffSSN"] );
		unset( $arFields["Phone1"] );
		unset( $arFields["AffPaymentMethod"] );
		unset( $arFields["AffAddressSameAsUser"] );
		unset( $arFields["AffAddress1"] );
		unset( $arFields["AffAddress2"] );
		unset( $arFields["AffCity"] );
		unset( $arFields["AffCountryID"] );
		unset( $arFields["AffStateID"] );
		unset( $arFields["AffZip"] );
		return $arFields;
	}
	
	function TuneList( &$list )
	{
		parent::TuneList( $list );
		$list->SQL = "select * from Usr where AffRegistered = 1";
		$list->CanAdd = False;
		$list->AllowDeletes = False;
	}
	
	function TuneForm(\TBaseForm $form)
	{
		parent::TuneForm( $form );
		unset( $form->Fields["Over18"] );
		$form->SuccessURL = "list.php";
	}
}

?>
