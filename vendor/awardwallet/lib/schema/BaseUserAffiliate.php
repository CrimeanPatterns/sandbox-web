<?

class TBaseUserAffiliateSchema extends TBaseSchema
{
	private   $UserSchema;
	private   $Form;
	private   $NewAffiliate;
	
	function TBaseUserAffiliateSchema()
	{
		parent::TBaseSchema();
		$this->TableName = "Usr";
		$sCheckScriptCondition = "( radioValue( Form, 'AffAddressSameAsUser' ) == '0' )";
		$arOnGetRequired = array( &$this, "AddressFieldRequired" );
		$this->Fields = array(
			"AffEntityType" => array(
				"Type" => "integer",
				"Options" => array(
					""	=> "Not Selected",
					"1" => "Individual",
					"2" => "Sole Proprietor",
					"3" => "LLC",
					"4" => "Partnership",
					"5" => "Corporation",
					"6" => "Foreign",
					"7" => "Non-Profit",
					"8" => "LLP",
				),
				"Caption" => "Entity Type",
				"InputAttributes" => "style=\"width: 300px;\"",
				"InputType" => "select",
				"Required" => True,
			),
			"AffBusinessName" => array(
				"Caption" => "Legal Business Name",
				"Note" => "If Individual or Sole Proprietor you can use your name",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Type" => "string",
				"Size" => 250,
				"Required" => True,
			),
			"AffBusinessBirthDate" => array(
				"Caption" => "Date of Birth",
				"InputAttributes" => "style=\"width: 278px;\"",
				"Type" => "date",
				"Note" => "US format only - April 1, 1980 would look like \"04/01/1980\"",
				"Required" => True,
			),
			"AffSSN" => array(
				"Caption" => "Social Security number or EIN Number",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Type" => "string",
				"Size" => 40,
				"Required" => True,
			),
			"AffSite" => array(
				"Caption" => "Site URL",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Type" => "string",
				"Note" => "URL of the website where you are going to place our link",
				"Size" => 40,
				"Required" => True,
			),
			"Phone1" => array(
				"Caption" => "Phone",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Type" => "string",
				"Size" => 40,
				"Required" => True,
			),
			"AffPaymentMethod" => array(
				"Type" => "integer",
				"Options" => array(
					"1" => "PayPal",
					"2" => "Check",
				),
				"Caption" => "Payment Method",
				"InputType" => "radio",
				"Required" => True,
			),
			"AffAddressSameAsUser" => array(
				"Caption" => "Mailing address",
				"Type" => "boolean",
				"InputType" => "radio",
				"Value" => "1",
				"InputAttributes" => "onclick=\"affLocationChanged( this.form, radioValue( this.form, 'AffAddressSameAsUser' ) == '0' )\"",
				"Options" => array( "1" => "Same As User", "0" => "Specified below" ),
			),
			"AffAddress1" => array( 
				"Caption" => "Address 1",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 128,
				"Cols" => 50,
				"Required" => True,
				"OnGetRequired" => $arOnGetRequired,
				"CheckScriptCondition" => $sCheckScriptCondition,
			),
			"AffAddress2" => array( 
				"Caption" => "Address 2",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 128,
				"Cols" => 50,
				"CheckScriptCondition" => $sCheckScriptCondition,
			),
			"AffCity" => array( 
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 80,
				"Cols" => 20,
				"OnGetRequired" => $arOnGetRequired,
				"Required" => True,
				"CheckScriptCondition" => $sCheckScriptCondition,
			),
			"AffCountryID" => array( 
				"Caption" => "Country",
				"Type" => "integer",
				"InputType" => "select",
				"InputAttributes" => "style=\"width: 300px;\" onchange=\"this.form.DisableFormScriptChecks.value=1;var stateInput = this.form.AffStateID; if(  stateInput.type=='select' ) stateInput.selectedIndex = 0; else stateInput.value = ''; EnableFormControls( this.form );this.form.submit();\"",
				"Required" => True,
				"OnGetRequired" => $arOnGetRequired,
				"Options" => SQLToArray( "select CountryID, Name from Country where CountryID <> 7", "CountryID", "Name" ),
				"CheckScriptCondition" => $sCheckScriptCondition,
			),
			"AffStateID" => array( 
				"Caption" => "State/province",
				"Type" => "integer",
				"InputType" => "select",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 40,
				"Cols" => 20,
				"Required" => True,
				"OnGetRequired" => $arOnGetRequired,
				"CheckScriptCondition" => $sCheckScriptCondition,
			),
			"AffZip" => array( 
				"Caption" => "ZIP / Postal code",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 40,
				"Cols" => 20,
				"filterWidth" => 40,
				"Required" => True,
				"OnGetRequired" => $arOnGetRequired,
				"CheckScriptCondition" => $sCheckScriptCondition,
			),
		);
	}
	
	function GetFormFields()
	{
		$arFields = parent::GetFormFields();
		if( isset( $_SESSION['UserID'] ) && ( Lookup( "Usr", "UserID", 'AffRegistered', $_SESSION["UserID"], True ) != '1' ) )
		{
			$q = new TQuery("select * from Usr where UserID = {$_SESSION['UserID']}");
			$arFields['AffZip']['Value'] = $q->Fields['Zip'];
			$arFields['AffCountryID']['Value'] = $q->Fields['CountryID'];
			$arFields['AffStateID']['Value'] = $q->Fields['StateID'];
			$arFields['AffCity']['Value'] = $q->Fields['City'];
			$arFields['AffAddress1']['Value'] = $q->Fields['Address1'];
			$arFields['AffAddress2']['Value'] = $q->Fields['Address2'];
		}
		return $arFields;
	}
	
	function TuneForm(\TBaseForm $form)
	{
		parent::TuneForm( $form );
		$form->Fields["AffScript"] = array(
			"Type" => "html",
			"HTML" => "<script src=/lib/scripts/affiliateEdit.js></script>",
			"IncludeCaption" => False,
		);
		ArrayInsert( $form->Fields, "AffBusinessName", False, array( "Over18" => array(
				"Type" => "integer",
				"Caption" => "Are You Over 18?",
				"Options" => array( "0" => "No", "1" => "Yes" ),
				"Database" => False,
				"InputType" => "radio",
				"Required" => True,
				"RegExp" => "/^1$/i",
				"RegExpErrorMessage" => "You MUST be over 18 years of age to join the affiliate program",
		) ) );
		if( !isset( $_SESSION['UserID'] ) && !$this->Admin )
			$this->CreateRegisterForm( $form );
		else
			$form->Fields['AffAgree']['Page'] = 'Affiliate';
		$objStateFieldManager = new TStateFieldManager();
		$objStateFieldManager->CountryField = "AffCountryID";
		$form->Fields["AffStateID"]["Manager"] = $objStateFieldManager;
		$this->NewAffiliate = !$this->Admin && ( !isset( $_SESSION['UserID'] ) || ( Lookup( "Usr", "UserID", 'AffRegistered', $_SESSION["UserID"], True ) != '1' ) );
		if( $this->NewAffiliate )
		{
			$form->SQLParams["AffRegistered"] = "1";
			$form->SQLParams["AffApproved"] = "1";
			$form->SQLParams["AffRegisterDate"] = "now()";
			$form->OnSave = array( $this, "SaveForm", $form );
			$form->OnCheck = array( &$this, "CheckAgree", &$form );
			$form->Fields["AffAgree"] = array(
				"Caption" => "<span style='font-size: 10px;'>I Agree to <a href='javascript:showAffiliatePolicy()'>".SITE_NAME." Affiliate Terms of Use</a></span>",
				"Type" => "boolean",
				"InputType" => "checkbox",
				"Database" => False,
				"Page" => "Affiliate",
				"InputAttributes" => "style=\"border:none;\""
			);
		}
		else{
			unset( $form->Fields['Over18'] );
			unset( $form->Fields['AffAgree'] );
		}
		if( isset( $_SESSION['UserID'] ) )
			unset($form->Fields['AffAgree']['Page']);
		$form->SuccessURL = "/affiliate/selectFormat.php";
		$this->Form = &$form;
#		print "<textarea rows=30 cols=100>";
#		print_r($this->Form);
#		print "</textarea>";
	}
	
	function SaveForm( &$objForm )
	{
		global $Connection;
		mail( SECURITY_EMAIL, "New affiliate registered", "User ID: {$objForm->ID}", EMAIL_HEADERS );
		$_SESSION['UserAffRegistered'] = True;
		if( !isset( $_SESSION['UserID'] ) )
		{
			$Connection->Execute( "update Usr set Address1 = AffAddress1, Address2 = AffAddress2, City = AffCity, Zip = AffZip, CountryID = AffCountryID, StateID = AffStateID, AffAddressSameAsUser = 1 where UserID = {$objForm->ID}" );
			$this->UserSchema->LoginNewUser( $objForm );
		}
	}
	
	function CheckAgree( &$objForm )
	{
		$sResult = NULL;
		if( !isset( $sResult ) && $this->NewAffiliate && isset( $_SESSION['UserID'] ) && ( $objForm->Fields['AffAddressSameAsUser']['Value'] == '1' ) )
		{
			$q = new TQuery("select * from Usr where UserID = {$_SESSION['UserID']}");
			if( ( $q->Fields['Address1'] == '' )
			|| ( $q->Fields['City'] == '' )
			|| ( $q->Fields['StateID'] == '' )
			|| ( $q->Fields['CountryID'] == '' )
			|| ( $q->Fields['Zip'] == '' ) )
			{
				$sResult = "Your used address is incomplete. Please <a href=/user/edit.php>complete your user address</a>, or specify other address";
				$objForm->Fields["AffAddressSameAsUser"]['Error'] = $sResult;
			}
		}
		if( !isset( $sResult ) && $this->NewAffiliate && ( ( ArrayVal( $_POST, "submitButton" ) != "" ) ) )
			if( $objForm->Fields["AffAgree"]["Value"] != "1" ){
				$objForm->Fields["AffAgree"]["Error"] = "You must agree to the ".SITE_NAME." Affiliate policy";
				$sResult = $objForm->Fields["AffAgree"]["Error"];
			}
			else 
				$sResult = NULL;
		if( !isset( $sResult ) && isset( $this->UserSchema ) )
			$sResult = $this->UserSchema->CheckAgree( $objForm );
		return $sResult;
	}
	
	function AddressFieldRequired( $sFieldName, $arField )
	{
		return isset( $this->Form->Fields['AffAddressSameAsUser'] ) && ( $this->Form->Fields['AffAddressSameAsUser']['Value'] != '1' );
	}
	
}

?>
