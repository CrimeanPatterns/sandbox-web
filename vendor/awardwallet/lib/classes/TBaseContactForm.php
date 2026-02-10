<?

class TBaseContactForm extends TForm{

	public $Status = "";

	function CompleteFields(){
		global $Connection;
		parent::CompleteFields();
		$this->SubmitButtonCaption = BUTTON_CAPTION;
		$this->Title = FORM_TITLE;
		$this->SuccessURL = "/contact";
	}

	function CheckAndSend(){
		global $Connection;
		if($this->IsPost && $this->Check()){
			$userID = null;
            $lifetimeContribution = null;
			if(isset($_SESSION["UserID"])){
				$uid = (isset($_SESSION['ManagerFields']['UserID'])) ? $_SESSION['ManagerFields']['UserID'] : $_SESSION["UserID"];
				$q = new TQuery("SELECT u.UserID, u.FirstName, u.LastName, u.Email, u.Phone1, sum(ci.Price * ci.Cnt * (100-ci.Discount)/100) LifetimeContribution
				FROM Usr u, Cart c
				JOIN CartItem ci ON c.CartID = ci.CartID
				WHERE u.UserID = c.UserID AND c.UserID = " . $uid. " AND c.PayDate is not null
				AND ci.TypeID <> ". CART_ITEM_BOOKING ."
				AND (ci.Price * ci.Cnt * ((100-ci.Discount)/100)) > 0", $Connection);
				$userID = $q->Fields["UserID"];
				$fullName = $q->Fields["FirstName"] . " " . $q->Fields["LastName"];
				$email = $q->Fields["Email"];
				$phone = $q->Fields["Phone1"];
				$lifetimeContribution = round($q->Fields["LifetimeContribution"], 2);
			}
			else{
				$fullName = $this->Fields["fullName"]["Value"];
				$email = $this->Fields["email"]["Value"];
				$phone = $this->Fields["phone1"]["Value"];
			}
			$recordId = $this->SaveToDB($userID, $fullName, $email, $phone, $lifetimeContribution);
			$subj = SITE_NAME . " request type: '" . $this->Fields["requestType"]["Value"] . "'";
			$msgBody = html_entity_decode( fixText($this->Fields["message"]["Value"]) );
			$msgBody .= "\n\nName: " . $fullName . "\n";
            if (isset($lifetimeContribution))
                $msgBody .= "Lifetime contribution: $" . $lifetimeContribution . "\n";
			if (isset($_SESSION['ManagerFields']['UserID'])) {
				$msgBody .= "Business Name: " . $_SESSION["UserFields"]["Company"] . "\n";
				$msgBody .= "Business UserID: " . $_SESSION["UserFields"]["UserID"] . "\n";
			}
			$msgBody .= "Email: " . $email . "\n";
			$msgBody .= "Phone: " . $phone . "\n";
			$msgBody .= "Request Type: " . $this->Fields["requestType"]["Value"] . "\n";
			if(isset($userID))
				$msgBody .= "userID: " . $userID . "\n";
			if(!empty($recordId))
				$subj .= ", #".$recordId;
			$EMAIL_HEADERS = "Content-Type: text/plain; charset=utf-8
Date: ". date('r'). "
From: \"".$fullName."\" <".$email.">
Reply-To: \"".$fullName."\" <".$email.">
Bcc: ".ConfigValue(CONFIG_CONTACT_BCC);
			mailTo(SUPPORT_EMAIL, $subj, $msgBody, $EMAIL_HEADERS);
			$this->Status = MSG_SENT;
			$this->Clear();
		}
	}

	/**
	 * Saves form to table ContactUs
	 * @param  $userID
	 * @param  $fullName
	 * @param  $email
	 * @param  $phone
	 * @return int|null saved record id
	 */
	function SaveToDB($userID, $fullName, $email, $phone, $lifetimeContribution = null){
		global $Connection;
		$recordId = null;
		$q = new TQuery("show tables like 'ContactUs'");
		if(!$q->EOF){
			$values = array(
				"UserID" => (isset($userID)?$userID:"null"),
				"FullName" => "'".addslashes($fullName)."'",
				"Email" => "'".addslashes($email)."'",
				"Phone" => "'".addslashes($phone)."'",
				"RequestType" => "'".addslashes($this->Fields["requestType"]["Value"])."'",
				"Message" => "'".addslashes($this->Fields["message"]["Value"])."'",
				"DateSubmitted" => "now()",
				"UserIP" => "'".addslashes(ArrayVal($_SERVER, "REMOTE_ADDR"))."'",
				"LifetimeContribution" => "'".addslashes($lifetimeContribution)."'",
			);
			$Connection->Execute(InsertSQL("ContactUs", $values));
			$recordId = $Connection->InsertID();
		}
		return $recordId;
	}

	function Show(){
		global $Interface, $showFAQ, $SHOW_FAQS;
		?>
		<!-- begin header -->
		<?php
		if($this->Status != "")
			$Interface->DrawMessage($this->Status, "success");
		?>

		<br/>
		<table cellspacing="0" cellpadding="0" border="0" align="center" width="436">
		<tr>
			<td>
		<?php
		if(isset($_SESSION["UserID"]))
			print LOGGED_IN_MSG;
		else
			print NOT_LOGGED_IN_MSG;
		if(isset($showFAQ))
			print $SHOW_FAQS;
		?>
		</td>
		</tr>
		</table>
		<br/>
		<div align="center">
		<table cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td width="100%" height="100%" align="center" valign="middle">
			<?=$this->HTML()?></td>
		</tr>
		</table>
		</div>
		<!-- end header -->
		<?
	}

}

?>
