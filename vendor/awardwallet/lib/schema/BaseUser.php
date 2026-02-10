<?

use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;

require_once(__DIR__ . "/../geoFunctions.php");

class TBaseUserSchema extends TBaseSchema
{
	var $userID = 0;
	var $OldFields;

	function __construct(){
		global $Config;
		$this->authenticateUser();
		parent::TBaseSchema();
		$this->TableName = "Usr";
		$this->DefaultSort = "UserID";
		$arCountries = GetCountryOptions($nUSA, $nCanada);
		$nStateID = null;
		$sCity = null;
# begin doing extra stuff for invitees...
		$invEmail = "";
		if(isset($_SESSION["invId"])){
			$q = new TQuery("SELECT email FROM Invites WHERE InvitesID = " . $_SESSION["invId"]);
			if(!$q->EOF && preg_match(EMAIL_REGEXP, $q->Fields["email"]))
				$invEmail = $q->Fields["email"];
			$q->Close();
		}
# end doing extra stuff for invitees...

		if( $_SERVER['REQUEST_METHOD'] == "POST" )
		{
			$nCountryID = intval( ArrayVal( $_POST, "CountryID", null ) );
			if( !isset( $arCountries[$nCountryID] ) )
				$nCountryID = null;
		}
		else{
			$nCountryID = null;
			if( $this->userID == "0" ) {
				if( ConfigValue(CONFIG_DETECT_USER_LOCATION ) ) {
					/** @var \AwardWallet\MainBundle\Service\GeoLocation\GeoLocation $geo */
					$geo = getSymfonyContainer()->get(GeoLocation::class);
					/** @var \AwardWallet\MainBundle\Entity\State $state */
					$ip = $_SERVER['REMOTE_ADDR'];
					$state = $geo->getStateByIp($ip);
					if ($state instanceof \AwardWallet\MainBundle\Entity\State) {
                        $nStateID = $state->getStateid();
						$nCountryID = $state->getCountryid();
					} else {
						$country = $geo->getCountryByIp($ip);
						if ($country instanceof \AwardWallet\MainBundle\Entity\Country) {
							$nCountryID = $country->getCountryid();
						}
					}
                    $sCity = $geo->getCityByIp($ip);
				}
			}
			else
				$nCountryID = Lookup( "Usr", "UserID", "CountryID", $this->userID, False );
		}

# In case nothing worked - set the defaults...
		if(trim($sCity) == "(Unknown city)" || trim($sCity) == "(Private Address)")
			$sCity = "";

		$objStateFieldManager = new TStateFieldManager();
		$objStateFieldManager->CountryField = "CountryID";

		$objPictureManager = new TPictureFieldManager();
		$objPictureManager->Dir = "/images/uploaded/users";
		$objPictureManager->Prefix = "user";
		$objPictureManager->thumbWidth = 150;
		$objPictureManager->thumbHeight = 150;
		$this->Fields = array(
			"UserID" => array(
				"Caption" => "id",
				"Type" => "integer",
				"filterWidth" => 30),
			"Login" => array(
				"Caption" => "User name",
				"Note" => "4-30 characters. English letters only. No Spaces.",
				"RegExp" => "/^[a-z_0-9A-Z\-]+$/i",
				"RegExpErrorMessage" => "Please use only English letters or numbers. No Spaces.",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 30,
				"MinSize" => 4,
				"Cols" => 20,
				"filterWidth" => 50,
				"Required" => True ),
			"Pass" => array(
				"Caption" => "Password",
				"Note" => "8-32 characters",
				"Type" => "string",
				"InputType" => "password",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 32,
				"MinSize" => 8,
				"Cols" => 20,
				"HTML" => true,
				"Encoding" => ArrayVal($Config, CONFIG_PASSWORD_ENCODING, 'md5'),
				"Required" => True ),
			"PassConfirm" => array(
				"Caption" => "Confirm&nbsp;password",
				"Type" => "string",
				"InputType" => "password",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 32,
				"HTML" => true,
				"MinSize" => 8,
				"Cols" => 20,
				"Required" => True,
				"Database" => False ),
			"FirstName" => array(
				"Caption" => "First name",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 30,
				"Cols" => 20,
				"filterWidth" => 50,
				"Required" => True ),
			"MidName" => array(
				"Caption" => "Middle name",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 30,
				"Cols" => 20 ),
			"LastName" => array(
				"Caption" => "Last name",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 30,
				"Cols" => 20,
				"filterWidth" => 70,
				"Required" => True ),
			"BDay" => array(
				"Caption" => "Date of Birth",
				"Type" => "date",
				"InputType" => "date",
				"InputAttributes" => "style=\"width: 278px;\"",
				"Required" => False ),
			"Email" => array(
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 80,
				"Cols" => 50,
				"Value" => $invEmail,
				"Required" => True,
				"Note" => "We are not in business of selling your email to anybody, <br>please provide a valid address here.",
				"filterWidth" => 130,
				"RegExp" => EMAIL_REGEXP
			),
			"Phone1" => array(
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 40,
				"Caption" => "Primary phone",
				"Required" => False,
				"Cols" => 20
			),
			"Phone2" => array(
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 40,
				"Caption" => "Secondary phone",
				"Cols" => 20
			),
			"Address1" => array(
				"Caption" => "Address 1",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 128,
				"Cols" => 50
			),
			"Address2" => array(
				"Caption" => "Address 2",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 128,
				"Cols" => 50
			),
			"City" => array(
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 80,
				"Cols" => 20
			),
			"CountryID" => array(
				"Caption" => "Country",
				"Type" => "integer",
				"InputType" => "select",
				"InputAttributes" => "style=\"width: 322px;\" onchange=\"this.form.DisableFormScriptChecks.value=1;var stateInput = this.form.StateID; if(  stateInput.type=='select' ) stateInput.selectedIndex = 0; else stateInput.value = ''; EnableFormControls( this.form );this.form.submit();\"",
				"Options" => array(
                    "" => "Please select",
                ) + $arCountries,
				"Value" => $nCountryID,
			),
			"StateID" => array(
				"Caption" => "State/province",
				"Type" => "integer",
				"InputType" => "select",
				"InputAttributes" => "style=\"width: 322px;\"",
				"Manager" => $objStateFieldManager,
				"Size" => 40,
				"Cols" => 20,
				"Value" => $nStateID,
			),
			"Zip" => array(
				"Caption" => "ZIP / Postal code",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Size" => 40,
				"Cols" => 20,
				"filterWidth" => 40,
			),
			"UserPhoto" => array(
				"Caption" => "Photo",
				"Note" => "Optionally you can add your photo here.' style='font-size: 9px;'>forum profile</a>.",
				"Type" => "custom",
				"InputAttributes" => "style=\"width: 300px;\"",
				"Manager" => $objPictureManager,
			),
			"IsNewsSubscriber" => array(
				"Caption" => "<span style='font-size: 10px;'>I would like to receive offers and information from " . SITE_NAME . "</span>",
				"Type" => "boolean",
				"Value" => true,
				"InputType" => "checkbox",
				"InputAttributes" => "style=\"border:none;\""
			),
			"IsPartnersSubscriber" => array(
				"Caption" => "<span style='font-size: 10px;'>I would like to receive offers and information from " . SITE_NAME . " partners</span>",
				"Type" => "boolean",
				"Value" => true,
				"InputType" => "checkbox",
				"InputAttributes" => "style=\"border:none;\""
			),
		);
		if( $this->userID == 0 ){
			$this->Fields["Agree"] = array(
				"Caption" => "<label for='fldAgree'>I Agree to</label> <a href='javascript:showPolicy()'>".SITE_NAME." Terms of Use</a>",
				"Type" => "boolean",
				"InputType" => "checkbox",
				"Database" => False,
				"InputAttributes" => "style=\"border:none;\""
			);
		}
		else
		{
		    $this->Fields["Pass"]["Required"] = False;
		    $this->Fields["Pass"]["Type"] = "html";
		    $this->Fields["Pass"]["HTML"] = "<a href=\"/user/resetPassword.php?BackTo=".urlencode($_SERVER['REQUEST_URI'])."\">Click here to change</a>";
		    unset($this->Fields["Pass"]["Note"]);
			$this->Fields["Pass"]["Database"] = False;
		    unset( $this->Fields["PassConfirm"] );
		}
	}

	function authenticateUser(){
		global $QS;
		if( isset( $_SESSION["UserID"] ) )
			$QS["ID"] = $_SESSION["UserID"];
		else
			$QS["ID"] = 0;
		$this->userID = $QS["ID"];
	}

	function GetListFields()
	{
		$arFields = $this->Fields;
		unset($arFields['UserPhoto']);
		unset($arFields['Pass']);
		unset($arFields['MidName']);
		unset($arFields['Phone2']);
		unset($arFields['Address1']);
		unset($arFields['City']);
		unset($arFields['CountryID']);
		unset($arFields['StateID']);
		unset($arFields['Address2']);
		unset($arFields['IsNewsSubscriber']);
		unset($arFields['IsPartnersSubscriber']);
		unset($arFields['BDay']);
		unset($arFields['Phone1']);
		unset($arFields['PassConfirm']);
		unset($arFields['Agree']);
		unset($arFields['Zip']);
		$arFields = $arFields + array(
			"LogonCount" => array(
				"Caption" => "Logons",
				"Type" => "integer",
				"filterWidth" => 20
			),
			"LastScreenWidth" => array(
				"Caption" => "w-th",
				"Type" => "integer",
				"filterWidth" => 20
			),
/*
			"LastScreenHeight" => array(
				"Caption" => "h-th",
				"Type" => "integer",
				"filterWidth" => 30
			),
*/
			"CameFrom" => array(
				"Caption" => "From",
				"Type" => "integer",
				"InputType" => "select",
				"filterWidth" => 20,
				"Options" => SQLToArray( "SELECT SiteAdID, Description FROM SiteAd ORDER BY Description", "SiteAdID", "Description")
			),
		);
		return $arFields;
	}

	function TuneList( &$list )
	{
		/* @var $list TBaseList */
		parent::TuneList( $list );
		$list->SQL = "SELECT * FROM Usr";
		$list->MultiEdit = true;
		$list->KeyField = "UserID";
		$list->DefaultSort2 = "ID";
		$list->Sorts["ID"] = array(
			"Caption" => "ID DESC",
			"OrderBy" => "UserID DESC" );
		$list->DeleteQueries[] = "delete from GroupUserLink where UserID = [ID]";
	}

	function FormSQLParams(){
		return array( "CreationDateTime" => "now()", "RegistrationIP" => "'".addslashes( $_SERVER["REMOTE_ADDR"] )."'");
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm( $form );
		if(($_SERVER['REQUEST_METHOD'] == 'POST') && !isset($_COOKIE[ini_get('session.name')]))
			throw new \Exception('Further work with disabled cookies is impossible');
		if( $this->userID == 0 )
		{
			$form->SQLParams = $this->FormSQLParams();
			$form->SubmitButtonCaption = "Register";
		}
		else{
			$form->SubmitButtonCaption = "Update";
			$q = new TQuery("select * from Usr where UserID = {$this->userID}");
			$this->OldFields = $q->Fields;
		}
		$form->OnCheck = array( &$this, "CheckAgree", &$form );
		$form->OnSave = array( &$this, "LoginNewUser", &$form );
		$form->TableName = "Usr";
		$form->KeyField = "UserID";
		$form->SuccessURL = "/index.php";
		$form->Uniques = array(
			array(
				"Fields" => array( "Login" ),
				"ErrorMessage" => "User with this login already exists. Please choose another login."
		 	),
			array(
				"Fields" => array( "Email" ),
				"ErrorMessage" => "User with this email already exists. Please choose another email"
		)
		);
		if(!isset($_SESSION["UserID"]))
			$form->Title = "Create a new account";
		else
			$form->Title = "Edit my details";
	}

	function GetFormFields()
	{
		$arFields = $this->Fields;
		unset($arFields['UserID']);
		return $arFields;
	}

	// event handler, check user is agree with terms of use
	// -----------------------------------------------------------------------
	function CheckAgree( &$objForm )
	{
		if( ( $this->userID != 0 ) || ( ArrayVal( $_POST, "submitButton" ) == "" ) )
			return NULL;
		if( $objForm->Fields["Agree"]["Value"] != "1" ){
			$objForm->Fields["Agree"]["Error"] = "You must agree to the ".SITE_NAME." policy";
			return $objForm->Fields["Agree"]["Error"];
		}
		else
			return NULL;
	}

	function NotifyInviter($nUserID, $sFirstName, $sLastName, $new = 0){

	}

	function UpdateInviterScore($nUserID){
	}

	function emailNotify(&$objForm){
		$q = new TQuery("select u.Referer, sa.Description as SiteAdDescription, u.CameFrom
		from Usr u left outer join SiteAd sa on u.CameFrom = sa.SiteAdID
		where u.UserID = {$objForm->ID}");
		mailTo( SECURITY_EMAIL, "New account was registered at " . SITE_NAME,
"Referer: ".ArrayVal($q->Fields, "Referer")."
Came from: ".ArrayVal($q->Fields, "SiteAdDescription")." ({$q->Fields['CameFrom']})
Login: {$objForm->Fields["Login"]["Value"]}
Email: {$objForm->Fields["Email"]["Value"]}
ID: $objForm->ID
IP: " . $_SERVER["REMOTE_ADDR"]
, EMAIL_HEADERS);
		mailTo($objForm->Fields["Email"]["Value"], "Welcome to ".SITE_NAME."!", $this->getEmailText($objForm), EMAIL_HEADERS);
	}

	function getEmailText(&$objForm){
		$msgBody = "Thank you for registering with ".SITE_NAME.".

Your login ID is: {$objForm->Fields["Login"]["Value"]}

In order to verify your email address please follow this link:

http://".SITE_NAME."/user/verifyEmail.php?login=".urlencode($objForm->Fields["Login"]["Value"])."&id=".md5($objForm->ID)."

We appreciate your interest in our service and wanted to give you a quick introduction to the most frequently used features of ".SITE_NAME.":

";
		$q = new TQuery( "SELECT Email, Title, BodyText FROM Forum WHERE forumNumber = 7 AND Visible = 1 ORDER BY `Rank`;" );
		while( !$q->EOF ){
			$msgBody .= "* " . $q->Fields["Title"] . " - " . $q->Fields["BodyText"] . "\n\n";
			$q->Next();
		}
		$msgBody .= "Thank you,
The ".SITE_NAME." team
http://www.".SITE_NAME;
		return $msgBody;
	}

	function addToPhpBB(){
		global $Connection, $Config;
		$insert1 = $insert2 = "";
		if(isset($Config["RussianSite"]) && $Config["RussianSite"] == true){
			$insert1 = ", user_lang";
			$insert2 = ", 'russian'";
		}
		$sSQL = "select * from phpbb_users where user_id={$_SESSION["UserID"]}";
		$q = new TQuery( $sSQL );
		if( $q->EOF ) {
			$sSQL = "INSERT INTO phpbb_users (user_id, user_active, username, user_password, user_email, user_regdate{$insert1}) VALUES ";
			$sSQL .= "( {$_SESSION["UserID"]}, 1, '{$_SESSION["Login"]}', '".RandomStr(ord('a'), ord('z'), 20)."', '{$_SESSION["Email"]}', " . time() . "{$insert2})";
			$Connection->Execute( $sSQL );
			$avQ1 = new TQuery( "SELECT MAX(group_id) as maxgroupID FROM phpbb_groups;" );
			if(!$avQ1->EOF){
				$groupID = $avQ1->Fields["maxgroupID"] + 1;
				$Connection->Execute( "INSERT INTO phpbb_groups (group_id, group_type, group_name, group_description, group_moderator, group_single_user) VALUES(".$groupID.", 1, '', 'Personal User', 0, 1)" );
				$Connection->Execute( "INSERT INTO phpbb_user_group (group_id, user_id, user_pending) VALUES(".$groupID.", ".$_SESSION["UserID"].", 0)" );
			}
		}
	}

    function LoginNewUser($nUserID){}
}
?>
