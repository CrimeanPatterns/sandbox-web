<?php

require_once __DIR__."/../kernel/public.php";
require_once __DIR__."/../../vendor/awardwallet/lib/classes/TBaseFormEngConstants.php";

$sTitle = "Debug RA-Register";
require __DIR__ . "/../design/header.php";


$objForm = new TBaseForm([
    'Provider'  => [
        "Caption"  => "Provider",
        "Type"     => "string",
//        "Options"  => ["" => ""] + SQLToArray("SELECT ProviderID, `Code` FROM Provider WHERE CanCheckConfirmation > ".CAN_CHECK_CONFIRMATION_NO." ORDER BY Code", "ProviderID", "Code"),
        "Required" => true,
    ],
    "ShowHeaders" => [
        "Type"      => "boolean",
        "InputType" => "checkbox",
        "Caption"   => "Show headers",
        "Value"     => true,
        "Required"  => true,
    ],
]);
$objForm->CsrfEnabled = false;
$objForm->SubmitButtonCaption = "Select provider";

if ($objForm->IsPost && $objForm->Check()) {

	$providerCode = $objForm->Fields["Provider"]["Value"];

	$q = new TQuery("SELECT Code, Engine, ProviderId FROM Provider WHERE Code = '$providerCode'");
    if (!$q->Fields) {
        echo "<strong>Your local copy is out of date. Provider code is not found.</strong><br />";
    } else {
        $providerId = $q->Fields['ProviderId'];
        $providerCode = $q->Fields['Code'];
        $providerEngine = $q->Fields['Engine'];
        if (!file_exists($sPath . "/engine/" . $providerCode . "/functions.php")
            && !file_exists($sPath . "/engine/" . $providerCode . "/RewardAvailability/Register.php")) {
            echo "<strong>Your local copy is out of date. Provider is not found.</strong><br />";
        } else {
            $checker = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\CheckerFactory::class)->getRewardAvailabilityRegister($providerCode,
                true);
            $doctrineConnection = getSymfonyContainer()->get('database_connection');
            $checker->db = new DatabaseHelper($doctrineConnection);

            $checker->SetAccount(array('ProviderCode' => $providerCode, 'ProviderEngine' => $providerEngine));

            $answersFiled = [
                "Answers" => [
                    "Type" => "string",
                    "InputType" => "textarea",
                    "Note" => "Question=Answer, one per line",
                    "HTML" => true,
                ],
                "CloseSelenium" => [
                    "Type"      => "boolean",
                    "InputType" => "checkbox",
                    "Note"      => "If you need to check hot pool...",
                    "Value"     => false,
                    "Required"  => true,
                ],
            ];
            if (method_exists($checker, 'getRegisterFields') && is_array($checker->getRegisterFields())) {
                $objForm->Fields = array_merge($objForm->Fields, $checker->getRegisterFields());
                $objForm->Fields = array_merge($objForm->Fields, $answersFiled);
                $objForm->CompleteFields();

                $objForm->SubmitButtonCaption = "Register";
                $objForm->OnCheck = "CheckForm";
            } else {
                echo "<strong>Provider has no method getRegisterFields.</strong><br />";
            }
        }
    }
}
if (isset($providerId) && isset($checker))
    if ($objForm->IsPost) {
        $aa = &$_POST;
        $loadDefault = count($objForm->Fields) !== count(array_intersect_key($aa, $objForm->Fields));
        if ($loadDefault) {
            foreach ($objForm->Fields as $field => $prop) {
                if (in_array($field, $aa) && isset($prop['Value']) && $prop['Value'] !== $aa[$field]) {
                    $aa[$field] = $prop['Value'];
                } elseif (isset($prop['Value'])) {
                    $aa[$field] = $prop['Value'];
                }
            }
        }
        $objForm->Check($aa);
    }

function CheckForm() {
    /** \TAccountChecker $checker */
	global $objForm, $checker, $providerCode;

	$arFields = array_diff_key($objForm->GetFieldValues(), array('Provider' => true, 'ShowHeaders' => true));

	$checker->KeepLogs = true;
	$checker->LogMode = 'dir';
	$checker->InitBrowser();
	$checker->http->LogHeaders = $objForm->Fields["ShowHeaders"]["Value"];

    $checker->logger->info("Provider code: ".$providerCode);
    $checker->logger->info("<style>.time { display: none; }</style> <a href='#' onclick=\"$('span.time').show(); return false;\">show timings</a>", ["HtmlEncode" => false]);
    $checker->logger->info("Fields:");
    $checker->logger->debug(var_export($arFields, true), ['pre' => true]);

    getSymfonyContainer()->get("logger")->pushHandler(new \Monolog\Handler\PsrHandler($checker->logger));

    $checker->TransferFields = $arFields;
    if (isset($arFields["Answers"]) && !empty(trim($arFields["Answers"]))) {
        $answers = explode("\n", $arFields["Answers"]);
        foreach ($answers as $line) {
            $pair = explode("=", $line);
            if (count($pair) === 2) {
                $checker->Answers[$pair[0]] = trim($pair[1]);
            }
        }
        $checker->AskQuestion('link', null, 'Question');
        $checker->ErrorCode = ACCOUNT_UNCHECKED;
    }
    $checker->http->RetryCount = 0;

    $msg = null;
    try {
        $checker->Check(false);
    }
    catch(\AwardWallet\Schema\Parser\Component\InvalidDataException $e) {
        $checker->logger->info('Itineraries Error:');
        $checker->logger->info($e->getMessage());
    }
    finally {
        getSymfonyContainer()->get("logger")->popHandler();
        // close Selenium browser
        if ($objForm->Fields["CloseSelenium"]["Value"] && $checker->http instanceof HttpBrowser) {
            $checker->http->cleanup();
        }
    }

	$checker->http->LogSplitter();
    $checker->logger->info('Register Message:');
    $checker->logger->error(var_export($checker->ErrorMessage, true), ['pre' => true]);
    $checker->logger->debug("", ['pre' => true]);
    $checker->logger->debug("", ['pre' => true]);

	echo '<strong>Log: </strong><a href="/admin/common/logFile.php?Dir='.urlencode($checker->http->LogDir).'&File='.urlencode("log.html").'" target="_blank">'.$checker->http->LogDir.'/log.html</a> <small id="reltime"></small><br /><br />';
}

echo $objForm->HTML();

echo "<script type='text/javascript'>
$(function(){
	$('input[id *= \"fld\"]').click(function () {
        $(this).select();
    });

	var nowis = new Date();
	$('#reltime').text('('+nowis.toRelativeTime()+' ago)');
	setInterval(function(){
		$('#reltime').text('('+nowis.toRelativeTime()+' ago)');
	}, 60000);
});
Date.prototype.toRelativeTime = function(now_threshold) {
	var delta = new Date() - this;
	now_threshold = parseInt(now_threshold, 10);
	if (isNaN(now_threshold)) { now_threshold = 60000; }
	if (delta < now_threshold) { return 'less than a minute'; }
	var units = null;
	var conversions = {
		millisecond: 1, // ms    -> ms
		second: 1000,   // ms    -> sec
		minute: 60,     // sec   -> min
		hour:   60,     // min   -> hour
		day:    24,     // hour  -> day
		month:  30,     // day   -> month (roughly)
		year:   12      // month -> year
	};
	for (var key in conversions) {
		if (delta < conversions[key]) {
			break;
		} else {
			units = key; // keeps track of the selected key over the iteration
			delta = delta / conversions[key];
		}
	}
	delta = Math.floor(delta);
	if (delta !== 1) { units += 's'; }
	return [delta, units].join(' ');
};
</script>";

require __DIR__ . "/../design/footer.php";
