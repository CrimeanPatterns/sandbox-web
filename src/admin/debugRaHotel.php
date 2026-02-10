<?php

require_once __DIR__."/../kernel/public.php";
require_once __DIR__."/../../vendor/awardwallet/lib/classes/TBaseFormEngConstants.php";

$sTitle = "Debug Hotel Reward Availability";
require __DIR__ . "/../design/header.php";

global $arAccountErrorCode;


$form = new TBaseForm([
    "ProviderCode"     => [
        "Type"     => "string",
        //        "Options"  => ["" => ""] + SQLToArray("SELECT `Code` as ProviderID, `Code` FROM Provider WHERE CanCheckRewardAvailability = 1 ORDER BY Code", "ProviderID", "Code"),
        "Required" => true,
    ],
    "Destination" => [
        "Type"     => "string",
        "Required" => true,
    ],
    "Adults" => [
        "Type"     => "integer",
        "Required" => true,
        "Value" => 1
    ],
    "Kids" => [
        "Type"     => "integer",
        "Required" => true,
        "Value" => 1
    ],
    "Rooms" => [
        "Type"     => "integer",
        "Required" => true,
        "Value" => 1
    ],
    "CheckIn" => [
        "Type"     => "date",
        "Required" => true,
    ],
    "CheckOut" => [
        "Type"     => "date",
        "Required" => true,
    ],
    "DownloadPreview" => [
        "Type"      => "boolean",
        "InputType" => "checkbox",
        "Value"     => false,
        "Required"  => true,
    ],
    "Login"            => [
        "Type"     => "string",
        "Required" => false,
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
    "KeepBrowserState" => [
        "Type"      => "boolean",
        "InputType" => "checkbox",
        "Value"     => false,
        "Required"  => true,
    ],
    "DebugState" => [
        "Type"      => "boolean",
        "InputType" => "checkbox",
        "Value"     => false,
        "Required"  => true,
    ],
    "BrowserState"     => [
        "Type"      => "string",
        "InputType" => "textarea",
        "Note"      => "Browser state",
        "HTML"      => true,
    ],
    "Engine"           => [
        "Type"      => "string",
        "InputType" => "select",
        "Caption"   => "Engine",
        "Options"   => [
            ''                       => '-',
            PROVIDER_ENGINE_CURL     => 'Curl',
            PROVIDER_ENGINE_SELENIUM => 'Selenium',
        ],
        "Required"  => false,
    ],
    "Attempts"         => [
        "Type"  => "integer",
        "Value" => 1,
    ],
    "ShowHeaders"      => [
        "Type"      => "boolean",
        "InputType" => "checkbox",
        "Caption"   => "Show headers",
        "Value"     => true,
        "Required"  => true,
    ],
]);
$form->SubmitButtonCaption = "Check";
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
        $accountInfo = [
            "Login" => $form->Fields["Login"]["Value"],
            "Login2" => $form->Fields["Login2"]["Value"],
            "Login3" => $form->Fields["Login3"]["Value"],
            "Pass" => $form->Fields["Pass"]["Value"],
            "AccountID" => 0,
            "UserID" => 0,
            'ProviderCode' => $form->Fields["ProviderCode"]["Value"],
            'ProviderEngine' => ($form->Fields["Engine"]["Value"] == '') ? $provider['Engine'] : $form->Fields["Engine"]["Value"],
            //            'SavePassword' => SAVE_PASSWORD_DATABASE,
            'BrowserState' => $form->Fields['BrowserState']["Value"],
            'DebugState' => $form->Fields["DebugState"]["Value"]
        ];
        //        $checker = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\CheckerFactory::class)->getAccountChecker($form->Fields["ProviderCode"]["Value"], true, $accountInfo);
        /** @var TAccountChecker $checker */
        $checker = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\CheckerFactory::class)->getRaHotelChecker($form->Fields["ProviderCode"]["Value"], true, $accountInfo);
        $doctrineConnection = getSymfonyContainer()->get('database_connection');
        $checker->db = new DatabaseHelper($doctrineConnection);

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
                    $checker->checkInitBrowserRewardAvailability();
                    $checker->http->LogHeaders = $form->Fields["ShowHeaders"]["Value"];
                    $checker->ParseIts = false;
                    $checker->ParsePastIts = false;
                    $checker->WantHistory = false;
                    $checker->WantFiles = false;

                    $checker->KeepLogs = true;
                    $answers = explode("\n", $form->Fields["Answers"]["Value"]);
                    foreach ($answers as $line) {
                        $pair = explode("=", $line);
                        if (count($pair) === 2) {
                            $checker->Answers[$pair[0]] = trim($pair[1]);
                        }
                    }

                    $checkin = strtotime($form->Fields["CheckIn"]["Value"]);
                    $checkout = strtotime($form->Fields["CheckOut"]["Value"]);

                    $checker->AccountFields['RaRequestFields'] = [
                        'Destination' => trim($form->Fields["Destination"]["Value"]),
                        'Adults' => $form->Fields["Adults"]["Value"],
                        'Kids' => $form->Fields["Kids"]["Value"],
                        'Rooms' => $form->Fields["Rooms"]["Value"],
                        'CheckIn' => $checkin,
                        'CheckOut' => $checkout,
                        'DownloadPreview' => $form->Fields["DownloadPreview"]["Value"]
                    ];

                    $checker->onLoggedIn = function () use ($checker) {
                        try {
                            $result = $checker->ParseRewardAvailability($checker->AccountFields['RaRequestFields']);
                            $checker->logger->info("Parsed Result:", ['Header' => 3]);
                            $checker->logger->info(var_export($result ?? null, true), ['pre' => true]);
                            if (!isValid($result, $checker)) {
                                $result = null;
                            }
                            if (!empty($result)) {
                                if ($checker->ErrorMessage !== 'Unknown error') {
                                    $checker->ErrorCode = ACCOUNT_WARNING;
                                } else {
                                    $checker->ErrorCode = ACCOUNT_CHECKED;
                                }
                            }
                        } catch (\Exception $e) {
                            throw $e;
                        }
                    };

                    $checker->Check(false);

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
                } catch (\ThrottledException | \CheckException $e) {
                    echo "<strong>" . $e->getMessage() . "</strong><br />";
                }
                finally {
                    //                    $checker->itinerariesMaster->getLogger()->popHandler();
                    //                // close Selenium browser
                    if ($checker->http instanceof HttpBrowser) {
                        $checker->http->cleanup();
                    }
                }
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
function isValid(array $result, $checker): bool
{
    if (empty($result)) {
        return true;
    }
    if (!isset($result['hotels'])) {
        $checker->logger->error('no hotels');
        return false;
    }
    $errors = [];
    $listFields = ['name', 'checkInDate', 'checkOutDate', 'pointsPerNight', 'cachePerNight'];
    foreach ($result['hotels'] as $i => $hotel) {
        foreach ($listFields as $field) {
            if (empty($hotel[$field])) {
                $errors['hotel[' . $i . ']'][] = $field;
            }
        }
    }
    if (!empty($errors)) {
        $checker->logger->error('Empty fields');
        $checker->logger->error(var_export($errors, true));
        return false;
    }
    return true;
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
