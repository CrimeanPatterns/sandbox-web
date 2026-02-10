<?php

ini_set("soap.wsdl_cache_enabled", "0");
require_once __DIR__."/../kernel/public.php";
require_once __DIR__."/../../vendor/awardwallet/lib/classes/TBaseFormEngConstants.php";
require_once __DIR__.'/../api/debugProxy/DebugProxyClient.php';

$sTitle = "Debug Proxy";

global $arAccountErrorCode;

require __DIR__ . "/../design/header.php";

define('SPRINT_SERVER_URL', 'http://sprint.awardwallet.com/api/debugProxy/debugProxy.php');
define('LOCAL_SERVER_URL', 'http://'.$_SERVER['HTTP_HOST'].'/api/debugProxy/debugProxy.php');

$objForm = new TBaseForm(array(
	"AccountID" => array(
		"Type" => "string",
		"Cols" => 30,
		"Caption" => "Account ID",
		"RegExp" => "/^\d+$/ims",
		"RegExpErrorMessage" => "Account ID must be an integer",
		"Value" => "",
		"Required" => true,
	),
	"Server" => array(
		"Type" => "string",
		"InputType" => "select",
		"Caption" => "Proxy",
		"Options" => array(
			'https://awardwallet.com/api/debugProxy/debugProxy.php' => 'awardwallet.com',
            LOCAL_SERVER_URL => 'Local',
		),
		"Required" => true,
	),
	"Engine" => array(
		"Type" => "string",
		"InputType" => "select",
		"Caption" => "Engine",
		"Options" => array(
			'' => '-',
			PROVIDER_ENGINE_CURL		=> 'Curl',
			PROVIDER_ENGINE_SELENIUM    => 'Selenium',
		),
		"Required" => false,
	),
	"ShowHeaders" => array(
		"Type" => "boolean",
		"InputType" => "checkbox",
		"Caption" => "Show headers",
		"Value" => true,
		"Required" => true,
	),
	"ParseIts" => array(
		"Type" => "boolean",
		"InputType" => "checkbox",
		"Caption" => "Parse Its",
		"Value" => true,
		"Required" => true,
	),
	"ParsePastIts" => array(
		"Type" => "boolean",
		"InputType" => "checkbox",
		"Caption" => "Parse Past Its",
		"Value" => false,
		"Required" => false,
	),
	"ParseHistory" => array(
		"Type" => "boolean",
		"InputType" => "checkbox",
		"Value" => false,
		"Required" => true,
	),
	"HistoryStartDate" => array(
		"Type" => "date",
		"Required" => false,
	),
	"ParseFiles" => array(
		"Type" => "boolean",
		"InputType" => "checkbox",
		"Value" => false,
		"Required" => true,
	),
	"FilesStartDate" => array(
		"Type" => "date",
	),
));
$objForm->SubmitButtonCaption = "Check account";
$objForm->CsrfEnabled = false;

if($objForm->IsPost && $objForm->Check()){

	try {
        if ($objForm->Fields["Server"]["Value"] === 'https://awardwallet.com/api/debugProxy/debugProxy.php') {
            $server = "Remote";
        } else {
            $server = "Local";
        }
        $authFile = __DIR__ . '/../../app/config/debugProxyToken' . $server . '.json';
        if (!file_exists($authFile)) {
            throw new \AwardWallet\MainBundle\FrameworkExtension\Exceptions\DebugProxyAuthException("Missing debugProxy auth file");
        }
        $token = json_decode(file_get_contents($authFile), true);
        if (!is_array($token) || !isset($token['access_token'])) {
            throw new \AwardWallet\MainBundle\FrameworkExtension\Exceptions\DebugProxyAuthException("Invalid debugProxy auth file");
        }

		$options = array(
			"trace"=> true,
			"exceptions"=> true,
			"cache_wsdl "=> WSDL_CACHE_NONE,
			"location" => $objForm->Fields["Server"]["Value"]/* . '?XDEBUG_SESSION_START=PHPSTORM'*/,
			"wsse-login" => 'token',
			"wsse-password" => $token['access_token'],
//			'proxy_host' => '192.168.0.2',
//			'proxy_port' => '8888'
		);
		$client = new DebugProxyClient(
			$options,
			__DIR__ . '/../api/debugProxy/debugProxy.wsdl'
		);
		if(isset($_SESSION['DebugProxyCookie']))
			$client->__setCookie('PHPSESSID', $_SESSION['DebugProxyCookie']);
		if(isset($_SESSION['DebugProxyCookieAWS']))
			$client->__setCookie('AWSELB', $_SESSION['DebugProxyCookieAWS']);
        $client->__setCookie('XDEBUG_SESSION', "PHPSTORM");

		$request = new AccountInfo($objForm->Fields["AccountID"]["Value"]);
		$response = $client->GetAccountInfo($request);
		foreach ($response as $key => $value) {
		    if ($value === "") {
		        $response->$key = null;
            }
        }
		if(isset($client->_cookies['PHPSESSID']))
			$_SESSION['DebugProxyCookie'] = $client->_cookies['PHPSESSID'][0];
		if(isset($client->_cookies['AWSELB']))
			$_SESSION['DebugProxyCookieAWS'] = $client->_cookies['AWSELB'][0];

		$AccountID = (int)$objForm->Fields["AccountID"]["Value"];
		if (!file_exists($sPath."/engine/".$response->ProviderCode."/functions.php")) {
			echo "<strong>Your local copy is out of date. Provider is not found.</strong><br />";
		} else {
			$AccountFields = array(
				'Login' => $response->Login,
				'Login2' => $response->Login2,
				'Login3' => $response->Login3,
				'Pass' => $response->Pass,
				'AccountID' => $AccountID,
				'ProviderCode' => $response->ProviderCode,
				'DisplayName' => $response->DisplayName,
				'ProviderEngine' => ($objForm->Fields["Engine"]["Value"] == '') ? $response->ProviderEngine : $objForm->Fields["Engine"]["Value"],
				'BrowserState' => base64_decode($response->BrowserState),
                'SavePassword' => SAVE_PASSWORD_DATABASE,
                'Partner' => 'all',
                'UserID' => null,
			);
			$checker = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\CheckerFactory::class)->getAccountChecker($response->ProviderCode, false, $AccountFields);
            $doctrineConnection = getSymfonyContainer()->get('database_connection');
            $checker->db = new DatabaseHelper($doctrineConnection);
            $checker->SetAccount($AccountFields, false);
			$checker->Answers = unserialize(base64_decode($response->Answers));

			$server = $objForm->Fields['Server']['Value'];
			if ($server != LOCAL_SERVER_URL && getSymfonyContainer()->getParameter("selenium_consul_address") === null) {
                getSymfonyContainer()->get("aw.selenium_finder")->setServer("selenium-dev.infra.awardwallet.com");
            }
            getSymfonyContainer()->get("aw.selenium_connector")->setOnWebDriverCreated(function($driver) use ($client, $AccountID){
                /** @var \AwardWallet\Common\Selenium\DebugWebDriver|SeleniumDebugWebDriver $driver */
                if ($driver instanceof SeleniumDebugWebDriver) {
                    $driver->setCommandExecutor(new DebugCommandExecutor($driver->getCommandExecutor()->getAddressOfRemoteServer(), $client, $AccountID));
                } else {
                    $driver->setCommandExecutor(new \AwardWallet\Common\Selenium\DebugCommandExecutor($driver->getCommandExecutor()->getAddressOfRemoteServer(), $client, $AccountID));
                }
            });

            $closeSeleniumBrowser = function() use ($checker) {
                if ($checker->http instanceof HttpBrowser) {
                    if ($checker->http->driver instanceof SeleniumDriver) {
                        $checker->http->driver->dontSaveStateOnStop();
                    }
                    $checker->http->cleanup();
                }
            };

			$checkAttemptsCount = 1;
            getSymfonyContainer()->get("logger")->pushHandler(new \Monolog\Handler\PsrHandler($checker->logger));
            try {
                for ($attempt = 1; $attempt <= $checkAttemptsCount && $attempt <= 7; $attempt++) {
                    try {
                        $checker->attempt = $attempt - 1;

                        $masterOptions = new \AwardWallet\Schema\Parser\Component\Options();
                        $masterOptions->throwOnInvalid = false;
                        $masterOptions->logDebug = true;
                        $masterOptions->logContext['class'] = get_class($checker);
                        $checker->itinerariesMaster = new \AwardWallet\Schema\Parser\Component\Master('itineraries', $masterOptions);

                        $checker->InitBrowser();
                        $checker->http->beginDebug($client, $AccountID);
                        $checker->http->LogHeaders = $objForm->Fields["ShowHeaders"]["Value"];
                        $checker->ParseIts = $objForm->Fields["ParseIts"]["Value"];
                        $checker->ParsePastIts = $objForm->Fields["ParsePastIts"]["Value"];
                        $checker->WantHistory = $objForm->Fields["ParseHistory"]["Value"];
                        $checker->WantFiles = $objForm->Fields["ParseFiles"]["Value"];
                        if (!empty($objForm->Fields["FilesStartDate"]["Value"])) {
                            $checker->FilesStartDate = StrToDate($objForm->Fields["FilesStartDate"]["Value"]);
                        }
                        $checker->KeepLogs = true;
                        $checker->http->LogMode = 'dir';
                        if (!empty($objForm->Fields["HistoryStartDate"]["Value"])) {
                            $checker->HistoryStartDate = StrToDate($objForm->Fields["HistoryStartDate"]["Value"]);
                        }

                        $checker->Check(false);

                        $extra = new \AwardWallet\Common\Parsing\Solver\Extra\Extra();
                        /** @var \AwardWallet\MainBundle\Entity\Provider $providerEntity */
                        $providerEntity = getSymfonyContainer()->get("aw.repository.provider")->findOneBy(["code" => $response->ProviderCode]);
                        $extra->provider = \AwardWallet\Common\Parsing\Solver\Extra\ProviderData::fromArray([
                            'Code' => $response->ProviderCode,
                            'ProviderID' => $providerEntity->getProviderid(),
                            'IATACode' => $providerEntity->getIATACode(),
                            'Kind' => $providerEntity->getKind(),
                            'ShortName' => $providerEntity->getShortname(),
                        ]);
                        $extra->context->partnerLogin = 'awardwallet';
                        $solver = getSymfonyContainer()->get("aw.solver.master");
                        try {
                            $solver->solve($checker->itinerariesMaster, $extra);
                        } catch (\AwardWallet\Common\Parsing\Solver\Exception $e) {
                            $checker->logger->error("[Solver]");
                            $checker->logger->error($e->getMessage());
                        }
                        if (!empty($checker->Properties)) {
                            $masterOptions->logDebug = false;
                            $master = new \AwardWallet\Schema\Parser\Component\Master('m', $masterOptions);
                            $master->getLogger()->pushHandler(new AccountCheckerLoggerHandler($checker->logger));
                            \AwardWallet\Schema\Parser\Util\ArrayConverter::convertMaster(['Properties' => $checker->Properties], $master);
                            $solver->solve($master, $extra);
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
                            $closeSeleniumBrowser();
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
                    } finally {
                        $closeSeleniumBrowser();
                    }
                }
            }
            finally {
                getSymfonyContainer()->get("logger")->popHandler();
            }

			echo '<strong>DisplayName: </strong>'.$response->DisplayName.'<br />
				  <strong>ProviderCode: </strong>'.$response->ProviderCode.'<br />
				  <strong>ProviderEngine: </strong>'.get_class($checker->http).'<br />
				  <strong>ProviderID: </strong>'.$response->ProviderID.'<br />
				  <strong>Login: </strong>'.htmlspecialchars($response->Login).'<br />
				  <strong>Login2: </strong>'.htmlspecialchars($response->Login2).'<br />
				  <strong>Login3: </strong>'.htmlspecialchars($response->Login3).'<br />
				  <strong>Log: </strong><a href="/admin/common/logFile.php?Dir='.urlencode($checker->http->LogDir).'&File='.urlencode("log.html").'" target="_blank">'.$checker->http->LogDir.'/log.html</a> <small id="reltime"></small><br /><br />
			';
			if(!empty($checker->Files)){
				echo "<table border=1><tr><td>Date</td><td>Name</td><td>Extension</td><td>Account Number</td><td>Account Name</td><td>Account Type</td><td></td></tr>";
				foreach($checker->Files as $file){
					$hash = sha1($file['Contents']);
					if(empty($_SESSION['Files']))
						$_SESSION['Files'] = [];
					$_SESSION['Files'][$hash] = $file['Contents'];
					echo "<tr><td>".date("Y-m-d", $file["FileDate"])."</td><td>{$file["Name"]}</td><td>{$file["Extension"]}</td><td>{$file["AccountNumber"]}</td><td>{$file["AccountName"]}</td><td>{$file["AccountType"]}</td><td><a target=_blank href='/admin/file.php?ID={$hash}'>Download</a></td></tr>";
				}
				echo "</table>";
			}
		}
		
	}
	catch(SoapFault $e){
		echo "<strong>".$e->getMessage()."</strong><br />";

		if ($e->getMessage() === 'Check your UserName and Password') {
		    showAuth($objForm->Fields["Server"]["Value"], $server);
        } else {
            echo "Response:<br><pre>".trim($client->__getLastResponseHeaders().$client->__getLastResponse())."</pre>";
        }
	}
	catch(\AwardWallet\MainBundle\FrameworkExtension\Exceptions\DebugProxyAuthException $e) {
	    echo "<strong>".$e->getMessage()."</strong><br />";
	    showAuth($objForm->Fields["Server"]["Value"], $server);
    }
}
echo $objForm->HTML();

$post = !empty($_POST) ? 'true' : 'false';
echo "<script type='text/javascript'>
$(function(){
	$('#fldAccountID').blur(function(){
		var elem = $(this);
		var saved = localStorage.getItem(dpKey(elem.attr('name')));
        if ( elem.val() == '' && saved !== null)
            elem.val(saved);
		if (elem.val().indexOf('http') >= 0) {
			var re = /(\d+)/g;
			var match = re.exec(elem.val());
			if (match !== null) {
				elem.val(match[0]);
			}
		}
	}).click(function () {
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

	$(':text, :checkbox, select').each(function() {
		var elem = $(this);
		elem.bind(elem.is(':text') ? 'keyup change blur' : 'change', dpSave);
	});
	$('form').bind('submit', function() {
		$(':text, :checkbox, select').each(dpSave);
	});
	$(':text, :checkbox, select').each(function() {
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

function showAuth($endPoint, $server){
    $urlParts = parse_url($endPoint);
    $authUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . '/api/oauth2/authorize.php?client_id=parserbox&response_type=code&scope=debugProxy&state=' . strtolower($server) . '&redirect_uri=' . urlencode('http://parserbox-web.awardwallet.docker/admin/save-debug-proxy-token');
    echo "<a href='{$authUrl}'>Please authenticate for debug proxy with {$urlParts['host']}</a><br/></br>";
}