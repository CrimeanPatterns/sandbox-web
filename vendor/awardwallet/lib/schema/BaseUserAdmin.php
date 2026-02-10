<?

require_once(__DIR__ . "/../../schema/User.php");

class TBaseUserAdminSchema extends TUserSchema{

    function __construct(){
		parent::__construct();
		$this->Description = array("User Admin", "Users");
		$this->bIncludeList = false;
		$this->ListClass = "TBaseAdminUserList";
		$this->Fields = $this->Fields + array(
			"EmailVerified" => array(
				"Caption" => "Verified",
				"Type" => "integer",
				"InputAttributes" => "readonly style=\"width: 300px;\"",
				"InputType" => "select",
				"Required" => false,
				"filterWidth" => 50,
				"Options" => array("" => "not set", 0 => "Unverified", 1 => "Verified", 2 => "NDR")
			),
		);
		unset($this->Fields['Login']['RegExp']);
		unset($this->Fields['Login']['MinSize']);

        if (!empty($_GET['dfrom']) || !empty($_GET['dto'])) {
            ArrayInsert($this->Fields, 'Login', true, [
                'CreationDateTime' => [
                    'Type' => 'string',
                ]
            ]);
        }
	}

	function GetListFields(){
		$arFields = parent::GetListFields();
		$arFields["DefaultBookerID"] = array(
			"Caption" => "Booker",
			"Type" => "integer",
			"InputType" => "select",
			"filterWidth" => 20,
			"Options" => SQLToArray("select UserID as BookerID, ServiceName from AbBookerInfo order by ServiceName", "BookerID", "ServiceName")
		);
		$arFields["Referer"] = array(
			"Type" => "string",
			"Size" => 250,
			"filterWidth" => 60,
		);
		return $arFields;
	}

	function GetFormFields(){
		global $QS, $Interface;
		$objGroupManager = new TCheckBoxLinksFieldManager();
		$objGroupManager->TableName = "GroupUserLink";
		$objGroupManager->ValueField = "SiteGroupID";
		$objGroupManager->Checkboxes = SQLToArray( "select SiteGroupID, concat('<nobr>', GroupName, ' - ', coalesce(Description, ''), '</nobr>') as GroupName from SiteGroup ORDER BY GroupName", "SiteGroupID", "GroupName");
		$arFields = array();
		if($QS["ID"] != 0){
			$arFields = array(
				"UserID" => array(
					"Caption" => "User ID",
					"Type" => "html",
					"HTML" => intval($QS["ID"]),
					"InputAttributes" => "disabled style=\"width: 300px;\"",
					"Required" => false
				),
			);
		}
		$arFields = $arFields + parent::GetFormFields();
		if($QS["ID"] != 0){
			$ndr = array("" => "not set", 0 => "Unverified", 1 => "Verified", 2 => "NDR");
			$userRS = new TQuery("SELECT * FROM Usr WHERE UserID = " . intval($QS["ID"]));
			if($userRS->EOF)
				$Interface->DiePage("Invalid URL. BaseUserAdmin.php:28");
			$arFields = $arFields + array(
				"EmailVerified" => array(
					"Caption" => "Email Verified",
					"Type" => "html",
					"HTML" => $ndr[$userRS->Fields["EmailVerified"]]
				),
				"UnlockAccount" => array(
					"Caption" => "Unlock account",
					"Type" => "integer",
					"InputType" => "checkbox",
					"Database" => false),
				"LogonCount" => array(
					"Caption" => "Numer of Logons",
					"Type" => "html",
					"HTML" => $userRS->Fields["LogonCount"]),
				"CreationDateTime" => array(
					"Caption" => "Created on",
					"Type" => "html",
					"HTML" => $userRS->Fields["CreationDateTime"]),
				"LastLogonDateTime" => array(
					"Caption" => "Last log on",
					"Type" => "html",
					"HTML" => $userRS->Fields["LastLogonDateTime"]),
				"RegistrationIP" => array(
					"Caption" => "Registration IP",
					"Type" => "html",
					"HTML" => $userRS->Fields["RegistrationIP"]),
				"LastLogonIP" => array(
					"Caption" => "LastLogon IP",
					"Type" => "html",
					"HTML" => $userRS->Fields["LastLogonIP"]),
				"LastScreenWidth" => array(
					"Caption" => "Last Screen Width",
					"Type" => "html",
					"HTML" => $userRS->Fields["LastScreenWidth"]),
				"LastScreenHeight" => array(
					"Caption" => "Last Screen Height",
					"Type" => "html",
					"HTML" => $userRS->Fields["LastScreenHeight"]),
				"Referer" => array(
					"Type" => "html",
					"HTML" => ArrayVal($userRS->Fields, "Referer")),
				"LastUserAgent" => array(
					"Caption" => "Last User Agent",
					"Type" => "html",
					"HTML" => "<div style='width: 300px;'>" . htmlspecialchars($userRS->Fields["LastUserAgent"]) . "</div>"),
				);
		}
		$arFields = $arFields + array(
			"GroupMembership" => array(
				"Caption" => "Group Membership",
				"Manager" => $objGroupManager,
				"Type" => "string"),
			);
		unset($arFields["PassConfirm"]);
		unset($arFields["Pass"]);
		return $arFields;
	}

	function TuneForm(\TBaseForm $form){
		parent::TuneForm( $form );
		$form->OnCheck = array($this, "CheckForm", &$form);
		$form->OnSave = NULL;
		$form->SuccessURL = "list.php?".$_SERVER["QUERY_STRING"];
		if(isset($form->Pages))
			unset($form->Pages);
	}

	function CheckForm($objForm){
		if(isset($objForm->Fields['UnlockAccount']) && ($objForm->Fields['UnlockAccount']['Value'] == '1')){
			$objForm->SQLParams["LockoutStart"] = "null";
			$objForm->SQLParams["LoginAttempts"] = "0";
		}
	}

	function TuneList( &$list ){
		parent::TuneList( $list );
		$q = new TQuery("show tables like 'Cart'");
		if( !$q->EOF ) {
			$list->DeleteQueries[] = "delete from CartItem where CartID in( select CartID from Cart where UserID = [ID] and PayDate is null )";
			$list->DeleteQueries[] = "delete from Cart where UserID = [ID]";
		}
		$q = new TQuery("show tables like 'UsrPicture'");
		if( !$q->EOF )
			$list->DeleteQueries[] = "delete from UsrPicture where UserID = [ID]";
		$list->MultiEdit = True;
		$list->ShowImport = true;
	}

	function authenticateUser(){
		global $QS;
		if(isset($QS["ID"]))
			$this->userID = $QS["ID"];
	}
}

class TBaseAdminUserList extends TBaseList{

	function __construct( $table, $fields, $defaultSort ){
		parent::__construct($table, $fields, $defaultSort);
	}

	function DrawButtons($closeTable=true){
		parent::DrawButtons(false);
		if( !$this->Query->IsEmpty )
		{
			echo "<input class='button' type=button value=\"Email\" onclick=\"EditSelectedFromList(this.form, '/lib/admin/user/email.php?BackTo=" . urlencode( $_SERVER['SCRIPT_NAME'] . "?" . $_SERVER['QUERY_STRING'] ) . "')\"> ";
			echo "<input class='button' type=button value=\"Email All\" onclick=\"document.location.href='/lib/admin/user/emailAll.php?BackTo=" . urlencode( $_SERVER['SCRIPT_NAME'] . "?" . $_SERVER['QUERY_STRING'] ) . "'\"> ";
		}
		if($closeTable)
			echo "</td></tr></table>";
	}

	function FormatFields($output = 'html'){
		parent::FormatFields();
		$arFields = &$this->Query->Fields;
		if(isset($arFields['Referer']) && ($arFields['Referer'] != '')){
			$link = preg_replace("/^https?:\/\//ims", '', $arFields['Referer']);
			if(strlen($link) > 20)
				$link = substr($link, 0, 20)."..";
			$arFields['Referer'] = '<a href="'.$arFields['Referer'].'" target="_blank">'.$link."</a>";
		}
	}
}
?>
