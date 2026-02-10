<?

use AwardWallet\MainBundle\Form\Type\ContactUsAuthType;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;

class TBaseContactUsSchema extends TBaseSchema{

	function TBaseContactUsSchema(){
		parent::TBaseSchema();
		$this->TableName = "ContactUs";
		$this->KeyField = $this->TableName . "ID";
		$this->Description = array("User Admin", "Contact Us");
        $types = array_combine(array_keys(ContactUsAuthType::getRequesttypes()), array_keys(ContactUsAuthType::getRequesttypes()));
		$this->Fields = array(
			$this->KeyField => array(
				"Caption" => "id",
				"Type" => "integer",
				"Size" => 250,
				"filterWidth" => 20,
			),
			"UserID" => array(
				"Caption" => "User ID",
				"Type" => "integer",
				"InputAttributes" => "disabled style=\"width: 300px;\"",
				"Required" => false,
				"filterWidth" => 40,
			),
			"FullName" => array(
				"Caption" => "Full Name",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"filterWidth" => 150,
				"Required" => true,
			),
			"Email" => array(
				"Caption" => "Email",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"filterWidth" => 150,
				"Required" => true,
			),
			"Phone" => array(
				"Caption" => "Phone",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"filterWidth" => 100,
			),
			"RequestType" => array(
				"Caption" => "RequestType",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px;\"",
				"InputType" => "select",
				"filterWidth" => 130,
				"Options" => array_merge(["" => "Please select"], $types),
				"Required" => true,
			),
			"Message" => array(
				"Caption" => "Message",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px; height: 200px;\"",
				"InputType" => "textarea",
				"HTML" => True,
				"Size" => 30000,
				"Required" => true,
			),
			"Comments" => array(
				"Caption" => "Comments",
				"Type" => "string",
				"InputAttributes" => "style=\"width: 300px; height: 200px;\"",
				"InputType" => "textarea",
				"HTML" => True,
				"Size" => 30000
			),
			"DateSubmitted" => array(
				"Caption" => "Date",
				"Type" => "date",
				"InputAttributes" => "style=\"width: 300px;\"",
			),
			"Replied" => [
				"Caption" => "Replied",
				"Type" => "integer",
				"Required" => True,
				"Value" => 0,
				"InputType" => "checkbox"
			],
		);
	}

	function GetListFields(){
		$arFields = $this->Fields;
		unset($arFields["Message"]);
		unset($arFields["Comments"]);
		return $arFields;
	}

	function TuneList( &$list ){
		parent::TuneList( $list );
        $list->CanAdd = false;
	}

    /**
     * tune dialog form
     *
     * @param TBaseForm $form
     */
	function TuneForm(\TBaseForm $form){
		global $_SESSION;
		parent::TuneForm( $form );
#		$objForm->OnCheck = array( &$this, "CheckAgree", &$objForm );
		$form->OnSave = array( &$this, "SendForm", $form );
        //$objForm->SQLParams = array( "FullName" => $objForm->Fields["FullName"]["Value"]);
		//$objForm->SuccessURL = "/contact";
		$form->Title = "Send us a message";
		$form->SubmitButtonCaption = "Send";
	}

	function GetFormFields(){
		global $QS;
		$arFields = $this->Fields;
		unset($arFields[$this->KeyField]);
		unset($arFields["UserID"]);
		//unset($arFields["UserID"]);

        $requestRS = new TQuery("SELECT * FROM ContactUs WHERE ContactUsID = " . intval($QS["ID"]));
        foreach(array("DateSubmitted","FullName","Email","Phone","RequestType","Message") as $value){
            $arFields[$value]["Type"] = "html";
            $arFields[$value]["HTML"] = $requestRS->Fields[$value];
            $arFields[$value]["Required"] = false;
        }
        $arFields["Message"]["HTML"] = fixText($requestRS->Fields["Message"], true);
		return $arFields;
	}

	/**
	 * @param  $objForm TForm
	 * @return void
	 */
	function SendForm($objForm){
        global $QS;
        /** @var \AwardWallet\MainBundle\Entity\Contactus $cu */
        $contactUs = getSymfonyContainer()->get('doctrine')
            ->getRepository(\AwardWallet\MainBundle\Entity\Contactus::class)->find(intval($QS["ID"]));
        $globals = getSymfonyContainer()->get('aw.globals');
        $subj = "AwardWallet.com request type: '" . $contactUs->getRequesttype() . "'";
        # TODO: fixText remove
        $body = html_entity_decode(fixText($contactUs->getMessage()));
        $body .= "\n\nName: " . $contactUs->getFullname() . "\n";
        $uid = $contactUs->getUserid();
        $user = null;
        if (isset($uid)) {
            /** @var \AwardWallet\MainBundle\Entity\Usr $user */
            $user = getSymfonyContainer()->get('doctrine')
                ->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($uid);
            $lifetimeContribution = getSymfonyContainer()->get('doctrine')
                ->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->getPaymentStatsByUser($uid)['LifetimeContribution'];
        }
        if (isset($uid, $lifetimeContribution))
            $body .= "Lifetime contribution: \$" . $lifetimeContribution . "\n";
        if ($user) {
            $business = $user->getBusiness();
            if (!empty($business)) {
                $body .= "Business Name: " . $business->getFullName() . "\n";
                $body .= "Business UserID: " . $business->getUserid() . "\n";
            }
        }
        $body .= "Email: " . $contactUs->getEmail() . "\n";
        $body .= "Phone: " . $contactUs->getPhone() . "\n";
        $body .= "Request Type: " . $contactUs->getRequesttype() . "\n";
        if($brandName = $globals->getBrandFullName())
            $body .= "From: " . $brandName . "\n";
        if (isset($uid)){
            $body .= "userID: " . $uid . "\n";
        }

        $mailer = getSymfonyContainer()->get('aw.email.mailer');
        $message = $mailer->getMessage('contact_us');
        $message->setTo($mailer->getEmail('support'));
        $message->setSubject($subj . ", #". $contactUs->getContactusid())
            ->setBody($body, 'text/plain')
            ->setFrom($mailer->getEmail('from'), $contactUs->getFullname())
            ->setReplyTo($contactUs->getEmail(), $contactUs->getFullname());
        $mailer->send($message, [Mailer::OPTION_FIX_BODY => false]);
	}
}
?>
