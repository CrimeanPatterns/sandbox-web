<?php

use AwardWallet\Common\Selenium\HotSession\KeepActiveHotConfig;
use AwardWallet\Common\Selenium\HotSession\KeepActiveHotSessionManager;
use AwardWallet\Common\Selenium\HotSession\KeepActiveHotSessionResponse;
use AwardWallet\Common\Selenium\HotSession\KeepHotConfigFactory;

require_once __DIR__."/../kernel/public.php";
require_once __DIR__."/../../vendor/awardwallet/lib/classes/TBaseFormEngConstants.php";

$sTitle = "Debug RA-Register";
require __DIR__ . "/../design/header.php";

global $sPath;

$objForm = new TBaseForm([
    'Provider'  => [
        "Caption"  => "Provider",
        "Type"     => "string",
        "Required" => true,
    ],
    "ParseMode" => [
        "Type"     => "string",
        "Options"  => array_combine(TAccountChecker::PARSE_MODE_LIST,TAccountChecker::PARSE_MODE_LIST),
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
$tableWithListOfSessions = '';
$listConfig = '';

if ($objForm->IsPost && $objForm->Check()) {

	$providerCode = $objForm->Fields["Provider"]["Value"];
	$parseMode = $objForm->Fields["ParseMode"]["Value"];

	$q = new TQuery("SELECT Code, Engine, ProviderId FROM Provider WHERE Code = '$providerCode'");
	$providerId = $q->Fields['ProviderId'];
	$providerCode = $q->Fields['Code'];
	$providerEngine = $q->Fields['Engine'];
    if (!file_exists($sPath . "/engine/" . $providerCode . "/functions.php")) {
		echo "<strong>Your local copy is out of date. Provider is not found.</strong><br />";
	} else {
//        TAccountChecker::$logDir = __DIR__."/../../var/logs";
        TAccountChecker::$logDir = '/var/log/www/awardwallet/tmp/logs/';

        $configHotFactory = getSymfonyContainer()->get(KeepHotConfigFactory::class);
        $configHotFactory->setParseMode($parseMode);
        $configHot = $configHotFactory->load($providerCode);
		if (null === $configHot) {
            echo "<strong>Your local copy is out of date. Provider has no KeepHotConfig.</strong><br />";
        } else {
            $listConfig = renderConfigData();

            if (!$configHot->isActive()){
                echo '<strong>Can\'t run keep active hot session. Parser is off.</strong><br /><br />';
                unset($configHot);// for refreshing
            } else {
                $sessions = getSymfonyContainer()
                    ->get('doctrine_mongodb')
                    ->getRepository(\AwardWallet\Common\Document\HotSession::class)
                    ->findBy(['provider' => $providerCode]);

                if (empty($sessions)) {
                    echo '<strong>Nothing to keep.</strong><br /><br />';
                    unset($configHot);// for refreshing
                } else {
                    $tableWithListOfSessions = renderTable($sessions);
                }
            }
        }
        if (isset($configHot)) {
            $objForm->CompleteFields();
            $objForm->SubmitButtonCaption = "Run Keep";
            $objForm->OnCheck = "CheckForm";
        } else {
            $objForm->CompleteFields();
            $objForm->SubmitButtonCaption = "Refresh";
            $objForm->OnCheck = "Check";
        }
    }
}
if (isset($providerId) && isset($configHot))
    if ($objForm->IsPost) {
        $aa = &$_POST;
        $objForm->Check($aa);
    }

function CheckForm() {

	global $objForm, $configHot, $providerCode, $tableWithListOfSessions;

    $keepManager = getSymfonyContainer()->get(KeepActiveHotSessionManager::class);
    if (!$keepManager)
        return;
    /** @var KeepActiveHotSessionResponse $res */
    $res = $keepManager->runKeepHot($providerCode);

    if (!$res) {
        echo '<strong>Check parser with keep active hot session. Looks like it is off.</strong><br /><br />';
        unset($configHot);
        return;
    }
    if (file_exists($res->getLogDir() . '/log.html')) {
        echo '<strong>Log: </strong><a href="/admin/common/logFile.php?Dir=' . urlencode($res->getLogDir()) . '&File=' . urlencode("log.html") . '" target="_blank">' . $res->getLogDir() . '/log.html</a> <small id="reltime"></small><br /><br />';
    }
        $sessions = getSymfonyContainer()
            ->get('doctrine_mongodb')
            ->getRepository(\AwardWallet\Common\Document\HotSession::class)
            ->findBy(['provider' => $providerCode]);
        if (empty($sessions)) {
            $tableWithListOfSessions = '';
            echo '<strong>Sessions not found</strong><br /><br />';
        } else {
            echo '<strong>No need to activate. no time has elapsed since the last use. less than the specified interval</strong><br /><br />';
            $tableWithListOfSessions = renderTable($sessions);
        }

    unset($configHot);
}

function renderConfigData()
{
    global $configHot;
    $afterDate = $configHot->getAfterDateTime() ? date('Y-m-d H:i') : null;
    $isActive = var_export($configHot->isActive(), true);
    $listConfig = "
            <ul class='config'>
  <li>Keep Hot Config</li>
  <li><span>Active</span><em>{$isActive}</em></li>
  <li><span>Interval</span><em>{$configHot->getInterval()}</em></li>
  <li><span>Count To Keep</span><em>{$configHot->getCountToKeep()}</em></li>";
    if ($afterDate) {
        $listConfig .= "<li><span>After time</span><em>{$afterDate}</em></li>";
    }
    $listConfig .= "</ul><br />";

    return $listConfig;
}

function renderTable($sessions)
{
    $tableWithListOfSessions = "
<table class='table' style='width: 80%'>
<thead><tr><th>hotSessionId</th><th>prefix</th><th>accountKey</th><th>startDate</th><th>LastUseDate</th><th>isLocked</th></tr></thead><tbody>";
    /** @var \AwardWallet\Common\Document\HotSession $session */
    foreach ($sessions as $session) {
        $start = date("Y-m-d H:i:s", $session->getStartDate()->getTimestamp());
        $last = date("Y-m-d H:i:s", $session->getLastUseDate()->getTimestamp());
        $isLocked = json_encode($session->getIsLocked());
        $tableWithListOfSessions .=
            "
<tr><td>{$session->getId()}</td><td>{$session->getPrefix()}</td><td>{$session->getAccountKey()}</td><td>{$start}</td><td>{$last}</td><td>{$isLocked}</td></tr>
";
    }

    $tableWithListOfSessions .=
        "                    
</tbody></table>                
";
    return $tableWithListOfSessions;
}

echo $objForm->HTML();
echo $listConfig;
echo $tableWithListOfSessions;
echo "<style>
table.formTable{
width: 20%;
}
.table{
	border: 1px solid #eee;
	table-layout: fixed;
	width: 100%;
	margin-bottom: 20px;
}
.table th {
	font-weight: bold;
	padding: 5px;
	background: #efefef;
	border: 1px solid #dddddd;
}
.table td{
	padding: 5px 10px;
	border: 1px solid #eee;
	text-align: left;
}
.table tbody tr:nth-child(odd){
	background: #fff;
}
.table tbody tr:nth-child(even){
	background: #F7F7F7;
}
.config {
width: 20%;
list-style: none;
padding: 0;
//border: 1px solid rgba(0,0,0, .2);
}
.config li {
overflow: hidden;
padding: 6px 10px;
//font-size: 20px;
}
.config li:first-child {
font-weight: bold;
padding: 10px 0 10px 10px;
margin-bottom: 10px;
border-top: 1px solid rgba(0,0,0, .2);
border-bottom: 1px solid rgba(0,0,0, .2);
border-bottom-left-radius: 10px;
border-bottom-right-radius: 10px;
border-top-left-radius: 10px;
border-top-right-radius: 10px;
color: #888585;
//font-size: 24px;
//box-shadow: 0 10px 20px -5px rgba(0,0,0, .2);
}
//.config li:first-child:before {
//content: '\2749'';
//margin-right: 10px;
//}
.config span {
float: left;
width: 75%;
color: #7C7D7F;
}
.config em {
float: right;
color: #9c836e;
font-weight: bold;
}
</style>";
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
