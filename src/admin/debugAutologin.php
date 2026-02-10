<?php

require_once __DIR__."/../kernel/public.php";
require_once __DIR__."/../../vendor/awardwallet/lib/classes/TBaseFormEngConstants.php";

$sTitle = "Debug Autologin";

global $arAccountErrorCode;

require __DIR__ . "/../design/header.php";

$form = new TBaseForm(array(
	"ProviderCode" => array(
		"Type" => "string",
		"Cols" => 30,
		"Value" => "",
		"Required" => true,
	),
    "AutologinType" => array(
        "Type"    => "integer",
        "Options" => [
            ""                         => "Auto, from provider",
            AUTOLOGIN_SERVER           => "Server",
            AUTOLOGIN_EXTENSION        => "Extension",
        ],
    ),
	"Login" => array(
		"Type" => "string",
        "Required" => true,
	),
	"Login2" => array(
		"Type" => "string",
	),
    "Login3" => array(
		"Type" => "string",
	),
	"Pass" => array(
		"Type" => "string",
	),
    "AccountProperties" => [
        "Type" => "string",
        "Note" => "Account Properties, for example AccountNumber, in format Name=Value, one pair one each line",
        "InputType" => "textarea",
    ],
    "ConfirmationNumber" => array(
   		"Type" => "string",
        "Note" => "Redirect to this reservation after autologin",
   	),
    "TargetURL" => array(
        "Caption" => "Target URL",
   		"Type" => "string",
   	),
));
$form->SubmitButtonCaption = "Autologin";
$provider = null;
$form->OnCheck = function() use($form, &$provider){
	$q = new TQuery("select * from Provider where Code = '" . addslashes($form->Fields['ProviderCode']['Value']) . "'");
	if($q->EOF)
		return "Provider not found";
	$provider = $q->Fields;
	return null;
};

if($form->IsPost && $form->Check()){
    try {
        $params = [
            'itineraryAutologin' => false,
            "accountId" => 0,
            "login" => "",
            "password" => "",
            "properties" => array(),
        ];
        $params['accountId'] = 1;
        $params['providerCode'] = $form->Fields["ProviderCode"]["Value"];
        $params['login'] = $form->Fields["Login"]["Value"];
        $params['login2'] = $form->Fields["Login2"]["Value"];
        $params['login3'] = $form->Fields["Login3"]["Value"];
        $params['password'] = $form->Fields["Pass"]["Value"];
        $params['properties'] = array_merge($params['properties'], parseKeyValueLines($form->Fields["AccountProperties"]["Value"] ?? ''));
        if (!empty($form->Fields["ConfirmationNumber"]["Value"])) {
            $params['itineraryAutologin'] = true;
            $params['properties']['confirmationNumber'] = $form->Fields["ConfirmationNumber"]["Value"];
        }

        $loginWithExtension = false;
        switch ($form->Fields["AutologinType"]["Value"]) {
            case AUTOLOGIN_EXTENSION:
                $loginWithExtension = true;
                break;
            case AUTOLOGIN_SERVER:
                break;
            default:
                $loginWithExtension = in_array($provider['AutoLogin'], array(AUTOLOGIN_EXTENSION, AUTOLOGIN_MIXED));
        }

        $frameURL = '/account/redirectFrame.php?ID=1';

        $onLoad = "startRedirecting()";
        if($loginWithExtension)
            $onLoad = "initExtension()";

        $programName = preg_replace("/(\(.*\))/", "<span class='silver'>$1</span>", $provider["DisplayName"]);

        $mode = "autologin";
        $error = null;

        if (!$loginWithExtension) {
            $checker = GetAccountChecker($form->Fields['ProviderCode']['Value']);
            getSymfonyContainer()->get("logger")->pushHandler(new \Monolog\Handler\PsrHandler($checker->logger));
            $checker->SetAccount(array_merge($provider, [
                "ProviderEngine" => PROVIDER_ENGINE_CURL,
                "Login" => $form->Fields["Login"]["Value"],
                "Login2" => $form->Fields["Login2"]["Value"],
                "Login3" => $form->Fields["Login3"]["Value"],
                "Pass" => $form->Fields["Pass"]["Value"],
                "UserID" => 1,
                "AccountID" => 1,
            ]), false);

            $arg = $checker->Redirect($form->Fields["TargetURL"]["Value"], null);
            if (is_array($arg)) {
                $manager = new AutologinManager(1, $arg);
                ob_start();
                $manager->drawPage();
                $frameContents = ob_get_clean();
                $cacheKey = bin2hex(random_bytes(5));
                $cache = getSymfonyContainer()->get("aw.shared_memcached")->set("autologin_" . $cacheKey,
                    $frameContents, 600);

                $frameURL = "/admin/autologinFrame.php?cacheKey=" . urlencode($cacheKey);
            }
            else {
                $error = "Checker returned error code $arg, could not redirect";
            }
            $log = '/admin/common/logFile.php?Dir='.urlencode($checker->http->LogDir).'&File='.urlencode("log.html");
            echo '<strong>Log: </strong><a href="'.$log.'" target="_blank">'.$checker->http->LogDir.'/log.html</a> <small id="reltime"></small><br /><br />
    	';
        }

        $twig = getSymfonyContainer()->get("twig");
        $response = $twig->render("@AwardWalletMain/redirect.html.twig", [
            "params" => $params,
            "loginWithExtension" => $loginWithExtension,
            "mode" => $mode,
            "displayName" => $provider["DisplayName"],
            "providerName" => $provider["Name"],
            "autologin" => true,
            "askLocalPassword" => false,
            "login" => $form->Fields["Login"]["Value"],
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
	$('[id *= \"fld\"]').click(function () {
        $(this).select();
    });
});
</script>";

echo '<h2 id="status"></h2>';
echo '<div id="log"></div>';

require __DIR__ . "/../design/footer.php";

function parseKeyValueLines(string $text) : array
{
    $lines = explode("\n", $text);
    $result = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) {
            continue;
        }
        $pair = explode("=", $line);
        if (count($pair) !== 2) {
            throw new \InvalidArgumentException("Invalid Key=Value line: $line");
        }
        $pair[0] = trim($pair[0]);
        $pair[1] = trim($pair[1]);
        if (empty($pair[0])) {
            throw new \InvalidArgumentException("Invalid key in Key=Value line: $line");
        }
        if (empty($pair[1])) {
            throw new \InvalidArgumentException("Invalid value in Key=Value line: $line");
        }
        $result[$pair[0]] = $pair[1];
    }
    return $result;
}