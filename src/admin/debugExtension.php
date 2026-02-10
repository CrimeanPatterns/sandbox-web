<?php

require_once __DIR__."/../kernel/public.php";
require_once __DIR__."/../../vendor/awardwallet/lib/classes/TBaseFormEngConstants.php";

$sTitle = "Debug Extension";

global $arAccountErrorCode;

require __DIR__ . "/../design/header.php";

$form = new TBaseForm([
    "ProviderCode"     => [
        "Type"     => "string",
        "Cols"     => 30,
        "Value"    => "",
        "Required" => true,
        "HTML"     => true,
    ],
    "Login"            => [
        "Type"     => "string",
        "Required" => true,
        "HTML"     => true,
    ],
    "Login2"           => [
        "Type" => "string",
        "HTML" => true,
    ],
    "Login3"           => [
        "Type" => "string",
        "HTML" => true,
    ],
    "Pass"             => [
        "Type" => "string",
        "HTML" => true,
    ],
    "Answers"          => [
        "Type"      => "string",
        "InputType" => "textarea",
        "Note"      => "Question=Answer, one per line",
        "HTML"      => true,
    ],
    "ParseIts"         => [
        "Type"      => "boolean",
        "InputType" => "checkbox",
        "Caption"   => "Parse Its",
        "Value"     => true,
        "Required"  => true,
    ],
    "ParsePastIts"     => [
        "Type"      => "boolean",
        "InputType" => "checkbox",
        "Caption"   => "Parse Past Its",
        "Value"     => false,
        "Required"  => false,
    ],
    "ParseHistory"     => [
        "Type"      => "boolean",
        "InputType" => "checkbox",
        "Value"     => false,
        "Required"  => true,
    ],
    "HistoryStartDate" => [
        "Type"     => "date",
        "Required" => false,
    ],
    "ParseFiles"       => [
        "Type"      => "boolean",
        "InputType" => "checkbox",
        "Value"     => false,
        "Required"  => true,
    ],
    "FilesStartDate"   => [
        "Type" => "date",
    ],
    "UserID"         => [
        "Type"  => "integer",
        "Caption" => "User ID",
    ],
]);
$form->SubmitButtonCaption = "Check account";
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
        $answers = [];
        foreach (explode("\n", $form->Fields["Answers"]["Value"]) as $line) {
            $pair = explode("=", $line);
            if (count($pair) === 2) {
                $answers[$pair[0]] = trim($pair[1]);
            }
        }

        $credentials = new \AwardWallet\ExtensionWorker\Credentials(
            $form->Fields["Login"]["Value"],
            $form->Fields["Login2"]["Value"],
            $form->Fields["Login3"]["Value"],
            $form->Fields["Pass"]["Value"],
            $answers
        );

        $sessionManager = getSymfonyContainer()->get(\AwardWallet\ExtensionWorker\SessionManager::class);
        $session = $sessionManager->create();

        $checkAttemptsCount = 1;
        $maxAttempts = (int)$form->Fields["Attempts"]["Value"];
        getSymfonyContainer()->get("logger")->pushHandler(new \Monolog\Handler\PsrHandler($checker->logger));
        try {
            for ($attempt = 1; $attempt <= $checkAttemptsCount && $attempt <= $maxAttempts; $attempt++) {
                try {
                    $checker->attempt = $attempt - 1;

                    $masterOptions = new \AwardWallet\Schema\Parser\Component\Options();
                    $masterOptions->throwOnInvalid = false;
                    $masterOptions->logDebug = true;
                    $masterOptions->logContext['class'] = get_class($checker);
                    $checker->itinerariesMaster = new \AwardWallet\Schema\Parser\Component\Master('itineraries', $masterOptions);
                    //$checker->itinerariesMaster->getLogger()->pushHandler(new \AccountCheckerLoggerHandler($checker->logger));

                    $checker->InitBrowser();
                    $checker->http->LogHeaders = $form->Fields["ShowHeaders"]["Value"];
                    $checker->ParseIts = $form->Fields["ParseIts"]["Value"];
                    $checker->ParsePastIts = $form->Fields["ParsePastIts"]["Value"];
                    $checker->WantHistory = $form->Fields["ParseHistory"]["Value"];
                    $checker->WantFiles = $form->Fields["ParseFiles"]["Value"];
                    if (!empty($form->Fields["FilesStartDate"]["Value"])) {
                        $checker->FilesStartDate = StrToDate($form->Fields["FilesStartDate"]["Value"]);
                    }
                    if (!empty($form->Fields["HistoryStartDate"]["Value"])) {
                        $checker->HistoryStartDate = StrToDate($form->Fields["HistoryStartDate"]["Value"]);
                    }
                    $checker->KeepLogs = true;
                    $answers = explode("\n", $form->Fields["Answers"]["Value"]);
                    foreach ($answers as $line) {
                        $pair = explode("=", $line);
                        if (count($pair) === 2) {
                            $checker->Answers[$pair[0]] = trim($pair[1]);
                        }
                    }
                    $checker->Check(false);

                    $extra = new \AwardWallet\Common\Parsing\Solver\Extra\Extra();
                    $q = new TQuery("select * from Provider where Code = '" . addslashes($form->Fields["ProviderCode"]["Value"]) . "'");
                    $extra->provider = \AwardWallet\Common\Parsing\Solver\Extra\ProviderData::fromArray([
                        'Code' => $form->Fields["ProviderCode"]["Value"],
                        'ProviderID' => $q->Fields['ProviderID'],
                        'IATACode' => $q->Fields['IATACode'],
                        'Kind' => $q->Fields['Kind'],
                        'ShortName' => $q->Fields['ShortName'],
                    ]);
                    $extra->context->partnerLogin = 'awardwallet';
                    $solver = getSymfonyContainer()->get("aw.solver.master");
                    try {
                        $solver->solve($checker->itinerariesMaster, $extra);
                    } catch (\AwardWallet\Common\Parsing\Solver\Exception $e) {
                        $checker->logger->error("[Solver]");
                        $checker->logger->error($e->getMessage());
                    }
                    break;
                } catch (CheckRetryNeededException $e) {
                    $checkAttemptsCount = $e->checkAttemptsCount;
                    $retryTimeout = $e->retryTimeout;
                    // reset properties during retries
                    $checker->Properties = [];

                    $checker->logger->notice("[Attempt {$checker->attempt}]: Checker signalized that retry is needed from {$e->getFile()}:{$e->getLine()}");

                    if ($attempt <= $checkAttemptsCount - 1) {
                        $msg = "$attempt/{$checkAttemptsCount} attempt failed, sleeping "
                            . "{$retryTimeout} seconds and trying again";
                        $checker->logger->notice($msg);
                        sleep($retryTimeout);
                        // close Selenium browser
                        if ($checker->http instanceof HttpBrowser) {
                            $checker->http->cleanup();
                        }
                    } else {
                        $msg = "Max attempts count ({$checkAttemptsCount}) exceeded, no more retries";
                        $checker->logger->notice($msg);
                        if ($e->errorMessageWhenAttemptsExceeded !== null) {
                            $checker->ErrorMessage = $e->errorMessageWhenAttemptsExceeded;
                            $checker->ErrorCode = $e->errorCodeWhenAttemptsExceeded;
                            $checker->logger->error("error: " . $checker->ErrorMessage . " [" . $arAccountErrorCode[$checker->ErrorCode] . "]");
                        }
                    }
                    $checker->http->LogSplitter();
                    continue;
                } catch (\ThrottledException $e) {
                    echo "<strong>" . $e->getMessage() . "</strong><br />";
                }
//                finally {
//                    $checker->itinerariesMaster->getLogger()->popHandler();
//                // close Selenium browser
//                if ($checker->http instanceof HttpBrowser)
//                    $checker->http->cleanup();
//                }
            }
        }
        finally {
            getSymfonyContainer()->get("logger")->popHandler();
        }

        if ($form->Fields["KeepBrowserState"]["Value"]) {
            $state = $checker->GetState();
            $state = base64_decode(substr($state, strlen('base64:')));
            $form->Fields["BrowserState"]["Value"] = $state;
        }

        $log = '/admin/common/logFile.php?Dir='.urlencode($checker->http->LogDir).'&File='.urlencode("log.html");
        echo '<strong>DisplayName: </strong>'.$provider['DisplayName'].'<br />
		  <strong>Log: </strong><a href="'.$log.'" target="_blank">'.$checker->http->LogDir.'/log.html</a> <small id="reltime"></small><br /><br />
	';
//        echo curlRequest("http://awardwallet.docker" . $log);
    }
    catch (SoapFault $e) {
        echo "<strong>".$e->getMessage()."</strong><br />";
    }
}

echo $form->HTML();

$post = !empty($_POST) ? 'true' : 'false';
echo "<script type='text/javascript'>
$(function(){
	$('[id *= \"fld\"]').click(function () {
        $(this).select();
    });

	function dpKey(s) {
		var url = document.location.pathname + '~';
		return url + s;
	}
	function dpSave() {
		var elem = $(this);
		var val = null;
		if (elem.is(':checkbox'))
			val = elem.attr('checked') ? 1 : 0;
		else
			val = elem.val();

        var re = /(\d+)/g;
		var match = re.exec(val);
		if (match !== null)
		    localStorage.setItem(dpKey(elem.attr('name')), val);
	}

	$(':checkbox, select').each(function() {
		var elem = $(this);
		elem.bind('change', dpSave);
	});
	$('form').bind('submit', function() {
		$(':checkbox, select').each(dpSave);
	});
	$(':checkbox, select').each(function() {
		var elem = $(this);
		if (!{$post}) {
			var saved = localStorage.getItem(dpKey(elem.attr('name')));
			if (saved !== null) {
				if (elem.is(':checkbox'))
					elem.attr('checked', saved == 1);
				else
					elem.val(saved);
			}
		}
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
