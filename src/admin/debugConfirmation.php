<?php

$bNoSession = true;
require_once __DIR__."/../kernel/public.php";
require_once __DIR__."/../../vendor/awardwallet/lib/classes/TBaseFormEngConstants.php";

$sTitle = "Debug Confirmation";

require __DIR__ . "/../design/header.php";

$objForm = new TBaseForm([
    'ProviderID'  => [
        "Caption"  => "Provider",
        "Type"     => "integer",
        "Options"  => ["" => ""] + SQLToArray("SELECT ProviderID, `Code` FROM Provider WHERE CanCheckConfirmation > ".CAN_CHECK_CONFIRMATION_NO." ORDER BY Code", "ProviderID", "Code"),
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
	$providerId = (int)$objForm->Fields["ProviderID"]["Value"];

	$q = new TQuery("SELECT Code, Engine FROM Provider WHERE ProviderID = $providerId");
	$providerCode = $q->Fields['Code'];
	$providerEngine = $q->Fields['Engine'];
	if (!file_exists($sPath."/engine/".$providerCode."/functions.php")) {
		echo "<strong>Your local copy is out of date. Provider is not found.</strong><br />";
	} else {
		$checker = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\CheckerFactory::class)->getAccountChecker($providerCode, false);
        $doctrineConnection = getSymfonyContainer()->get('database_connection');
        $checker->db = new DatabaseHelper($doctrineConnection);

		$checker->SetAccount(array('ProviderCode' => $providerCode, 'ProviderEngine' => $providerEngine));

		$objForm->Fields = array_merge($objForm->Fields, $checker->GetConfirmationFields());
		$objForm->CompleteFields();

		$objForm->SubmitButtonCaption = "Retrieve";
		$objForm->OnCheck = "CheckForm";
	}
}
if (isset($providerId) && isset($checker))
	if($objForm->IsPost)
		$objForm->Check();

function CheckForm() {
    /** \TAccountChecker $checker */
	global $objForm, $checker, $providerCode;

	$arFields = array_diff_key($objForm->GetFieldValues(), array('ProviderID' => true, 'ShowHeaders' => true));

    $masterOptions = new \AwardWallet\Schema\Parser\Component\Options();
    $masterOptions->throwOnInvalid = false;
    $masterOptions->logDebug = true;
    $masterOptions->logContext['class'] = get_class($checker);
    $checker->itinerariesMaster = new \AwardWallet\Schema\Parser\Component\Master('itineraries', $masterOptions);
    $checker->itinerariesMaster->getLogger()->pushHandler(new \AccountCheckerLoggerHandler($checker->logger));

	$checker->KeepLogs = true;
	$checker->LogMode = 'dir';
	$checker->InitBrowser();
	$checker->http->LogHeaders = $objForm->Fields["ShowHeaders"]["Value"];

    $checker->logger->info("Provider code: ".$providerCode);
    $confirmationNumberURL = $checker->ConfirmationNumberURL($arFields);
    $checker->logger->info("ConfirmationNumberURL: <a target='_blank' style='color:black; text-decoration:none;' href='{$confirmationNumberURL}'>{$confirmationNumberURL}</a>");
    $checker->logger->info("<style>.time { display: none; }</style> <a href='#' onclick=\"$('span.time').show(); return false;\">show timings</a>", ["HtmlEncode" => false]);
    $checker->logger->info("Fields:");
    $checker->logger->debug(var_export($arFields, true), ['pre' => true]);

	$checker->Itineraries = array();
    getSymfonyContainer()->get("logger")->pushHandler(new \Monolog\Handler\PsrHandler($checker->logger));
    $msg = null;
    try {
        $msg = $checker->CheckConfirmationNumberInternal($arFields, $it);
        if (!isset($it[0]))
            $it = array($it);
    }
    catch(\AwardWallet\Schema\Parser\Component\InvalidDataException $e) {
        $checker->logger->info('Itineraries Error:');
        $checker->logger->info($e->getMessage());
        foreach($checker->itinerariesMaster->getItineraries() as $it)
            $checker->itinerariesMaster->removeItinerary($it);
    }
    finally {
        getSymfonyContainer()->get("logger")->popHandler();
    }
    $extra = new \AwardWallet\Common\Parsing\Solver\Extra\Extra();
    /** @var \AwardWallet\MainBundle\Entity\Provider $providerEntity */
    $providerEntity = getSymfonyContainer()->get("aw.repository.provider")->findOneBy(["code" => $providerCode]);
    $extra->provider = \AwardWallet\Common\Parsing\Solver\Extra\ProviderData::fromArray([
        'Code' => $providerCode,
        'ProviderID' => $providerEntity->getProviderid(),
        'IATACode' => $providerEntity->getIATACode(),
        'Kind' => $providerEntity->getKind(),
        'ShortName' => $providerEntity->getShortname(),
    ]);
    $extra->context->partnerLogin = 'awardwallet';
    $solver = getSymfonyContainer()->get("aw.solver.master");
    $solver->solve($checker->itinerariesMaster, $extra);

    if (!empty($it) && is_array($it) && isset($it[0]))
	    $checker->Itineraries = $it;
	$checker->http->LogSplitter();
    $checker->logger->info('CheckConfirmationNumberInternal Message:');
    $checker->logger->error(var_export($msg, true), ['pre' => true]);
    $checker->logger->info("CheckConfirmationNumberInternal Itinerary:");
    if ($checker->itinerariesMaster !== null && !empty($its = $checker->itinerariesMaster->getItineraries())) {
        $checker->Itineraries = $checker->itinerariesMaster->toArray(true);
        $checker->logger->info(\AwardWallet\Common\Parser\Data\Jsonator::html($checker->Itineraries, (new \AwardWallet\Common\Parser\Data\Analyzer())->getArraySchema($checker->Itineraries)), ['pre' => true]);
    }
    else {
        $checker->logger->info(var_export($checker->Itineraries, true), ['pre' => true]);
        $checker->CheckRequiredFields();
    }

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
