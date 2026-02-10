<?php

require_once __DIR__."/../kernel/public.php";
require_once __DIR__."/../../vendor/awardwallet/lib/classes/TBaseFormEngConstants.php";

$sTitle = "Debug Autologin";

global $arAccountErrorCode;

require __DIR__ . "/../design/header.php";

$fields = [
    "ProviderCode" => array(
   		"Type" => "string",
   		"Cols" => 30,
   		"Value" => "",
   		"Required" => true,
   	),
];
[$confFields, $error] = loadProviderConfFields();
$fields = array_merge($fields, $confFields);

$form = new TBaseForm($fields);

$form->SubmitButtonCaption = "Autologin";
$provider = null;
$form->OnCheck = function() use($form, &$provider, &$error){
    if ($error !== null) {
        return $error;
    }

	$q = new TQuery("select * from Provider where Code = '" . addslashes($form->Fields['ProviderCode']['Value']) . "'");
	if($q->EOF)
		return "Provider not found";

	$provider = $q->Fields;

	return null;
};

if($form->IsPost && $form->Check() && isset($form->Fields["ConfNo"])) {
    try {
        $confFields = $form->GetFieldValues();
        unset($confFields['ProviderCode']);
        $params = [
            'itineraryAutologin' => true,
            "accountId" => 0,
            "providerCode" => $form->Fields["ProviderCode"]["Value"],
            "login" => "",
            "password" => "",
            "properties" => [
                "confFields" => $confFields,
                "confirmationNumber" => $confFields["ConfNo"],
            ],
        ];

        $loginWithExtension = true;
        $frameURL = '/account/redirectFrame.php?ID=1';
        $onLoad = "initExtension()";

        $programName = preg_replace("/(\(.*\))/", "<span class='silver'>$1</span>", $provider["DisplayName"]);

        $mode = "autologin";
        $error = null;

        $twig = getSymfonyContainer()->get("twig");
        $response = $twig->render("@AwardWalletMain/redirect.html.twig", [
            "params" => $params,
            "loginWithExtension" => $loginWithExtension,
            "mode" => $mode,
            "displayName" => $provider["DisplayName"],
            "providerName" => $provider["Name"],
            "autologin" => true,
            "askLocalPassword" => false,
            "login" => "",
            "userName" => "John Smith",
            "frameURL" => $frameURL,
            "error" => $error,
            "onLoad" => $onLoad,
            "programName" => $programName,
        ]);

        echo $response;
    }
    catch (SoapFault $e) {
        echo "<strong>".$e->getMessage()."</strong><br />";
    }
}

echo $form->HTML();

echo "<script type='text/javascript'>
$(function(){
	$('#fldProviderCode').click(function () {
        $(this).select();
    });
});
</script>";

echo '<h2 id="status"></h2>';
echo '<div id="log"></div>';

require __DIR__ . "/../design/footer.php";

function loadProviderConfFields() : array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return [[], null];
    }

    $providerCode = $_POST['ProviderCode'];
    $q = new TQuery("select Engine from Provider where Code = '" . addslashes($providerCode) . "'");
    if ($q->EOF) {
        return [[], "provider not found"];
    }

    if (!file_exists(__DIR__."/../engine/".$providerCode."/functions.php")) {
        return [[], "Your local copy is out of date? Provider $providerCode files not found."];
    }

    $checker = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\CheckerFactory::class)->getAccountChecker($providerCode, false);
    $doctrineConnection = getSymfonyContainer()->get('database_connection');
    $checker->db = new DatabaseHelper($doctrineConnection);
    $checker->SetAccount(array('ProviderCode' => $providerCode, 'ProviderEngine' => $q->Fields['Engine']));

    $fields = $checker->GetConfirmationFields();
    if (empty($fields)) {
        return [[], "GetConfirmationFields returned no fields for $providerCode"];
    }

    return [$fields, null];
}