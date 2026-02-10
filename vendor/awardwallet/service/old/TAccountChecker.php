<?php

use AwardWallet\Common\Itineraries\ItinerariesCollection;
use AwardWallet\Common\Parser\Data\Analyzer;
use AwardWallet\Common\Parser\Data\Jsonator;
use AwardWallet\Common\Parsing\Html;
use AwardWallet\Common\Strings;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Account\Message;
use AwardWallet\Schema\Parser\Component\InvalidDataException;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\Email\Email;
use Psr\Log\LoggerInterface;
use RobbieP\ZbarQrdecoder\Result\Result;
use RobbieP\ZbarQrdecoder\ZbarDecoder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Psr\Container\ContainerInterface;

require_once(__DIR__ . "/CheckException.php");
require_once(__DIR__ . "/TVirtuallyThereParser.php");

class TAccountChecker {

	const MAX_BROWSERSTATE_LENGTH = 1024 * 1024;

    public $isRewardAvailability = false; // for RewardAvailability parsers will be set to true automatically, by default
    // for RewardAvailability parsers
    const RA_TIME_GO_OUT = 80; // 110;
    protected $parseMode;
    const PARSE_MODE_AW = 'awardwallet';
    const PARSE_MODE_JM = 'juicymiles';
    const PARSE_MODE_LIST = [
        self::PARSE_MODE_JM,
        self::PARSE_MODE_AW
    ];

	public $ErrorCode = ACCOUNT_ENGINE_ERROR;
	public $ErrorMessage = "Unknown error";
	public $ErrorReason = null;
	public $DebugInfo = null;
	public $Properties = array();
	public $Balance = null;
	protected $allowHtmlProperties = [];

	const CONFIRMATION_ERROR_MSG = 'There was an error in retrieving your reservation, please contact us if the problem persists.';
	const PROVIDER_ERROR_MSG = 'The website is experiencing technical difficulties, please try to check your balance at a later time.';
	const NOT_MEMBER_MSG = 'You are not a member of this loyalty program.';
	const CAPTCHA_ERROR_MSG = 'We could not recognize captcha. Please try again later.';
	const CONFNO_REGEXP = '#^([\w\-/]+|UnknownNumber)$#ims';
	const AIRCODE_REGEXP = '#^([A-Z]{3}|UnknownCode)$#ims';
	const BALANCE_REGEXP = '#([\d\.\,\-]+)#ims';
	const BALANCE_REGEXP_EXTENDED = '#([\d\.\,\-\s]+)#ims';
	const PRICE_REGEXP = "#^([\d\.\,\-\s]+|)$#";
	const FIELD_ALLOWED = 'allowed';


    // for partners     // refs #11247
    const ERROR_REASON_BLOCK = 'AwardWallet sign-in attempt is blocked by provider.';/*review*/

    /** @internal don't use it. only for service */
    public $throttlerReason = null;
    const THROTTLER_REASON_START_RPM = 'RPM on start';

    /*
     * Recognize captcha
     */
    const CAPTCHA_RECOGNIZER_ANTIGATE = 1;
    const CAPTCHA_RECOGNIZER_RUCAPTCHA = 2;
    const CAPTCHA_RECOGNIZER_ANTIGATE_API_V2 = 3;

	/**
	 * @var HttpBrowser
	 */
	public $http;
	public $Question;
	public $Cancelled = false;
	public $ParseIts = true;
	public $ParsePastIts = false;
	public $EmailLogs = false;
	public $Itineraries = array();
	public $LogMode = "dir";
	public $KeepLogs = false;
	public $KeepState = false;
	public $ArchiveLogs = false;
	public $VirtuallyThere = false;
	public $Skin = 'desktop';

	/**
	 * Parsing the history of account?
	 */
	public $WantHistory = false;
    /**
     * @var int - timestamp, start incremental history parsing from this date
     */
	public $HistoryStartDate = null;
    /**
     * subAccount history start dates, like
     * [
     *      'subAccountCode1' => unixtimestamp,
     *      'subAccountCode2' => unixtimestamp,
     * ]
     * @var array
     */
    public $historyStartDates = [];
	/**
     * @var bool - could we return history before historyStartDate
     */
	public $strictHistoryStartDate = true;
    /**
     * history parsing results
     */
    public $History = array();

	/**
	 * Parsing files
	 */
	public $WantFiles = false;
	public $Files = array();
	public $FilesStartDate = null;

	/**
	 * ['ProviderKind' => 1, 'Name' => 'Delta', .. ]
	 * @var array
	 */
	public $AccountFields;
	/**
	 * @var \AwardWallet\MainBundle\Entity\Account
	 */
	public $account;

	protected $userFields = array();

	public $Answers = array();
	private $OriginalAnswers = array();
	protected $Step;
	protected $StepTimeout = 3600;
	protected $LastRequestTime;
	protected $ShowLogs = false;
	public $InvalidAnswers = array();
	protected $State = array();
	protected $BrowserState = array();

	/**
	 * @var string // null for desktop/unknown or "mobile/ios", "mobile/android"
	 */
	public $Device;
	/**
	 * @var bool - refresh data from provider site when parsing email
	 */
	public $RefreshData = false;
	/**
	 * @var bool - redirect or parse
	 */
	protected $Redirecting = false;
	public $TransferFields;
	public $TransferMethod;

	/**
	 * keep requests through this host
	 * @var string
	 */
	private $KeepHost;

	/** @var AccountCheckerLogger */
	public $logger;

	/** @var LoggerInterface */
	public $globalLogger;

	/** @var ItinerariesCollection */
	public $itinerariesCollection;
	/** @var  Master */
	public $itinerariesMaster;

    /** @var DatabaseHelper */
    public $db;

    // Set to "true" if you need to test rewards purchases on local machine without special proxy and fake credit card
    // NOTE: Production ignores this setting
	public $allowRealIPAndCreditCardForLocalUsage = false;

	/**
	 * @var AuthorizationCheckerInterface
	 */
	public $authorizationChecker;

	/**
	 * @var int
	 */
	private $CaptchaCount = 0;
	/**
	 * @var int
	 */
	private $CaptchaTime = 0;

	/**
	 * @var bool
	 */
	private $LoggedIn = false;
    /** @var Callable
     * по-умолчанию при успешном логине вызывается метод TAccountChecker::afterLogin(), описано в __construct()
     * можно менять, подобно как это сделан для функционала ChangePassword
     */
    public $onLoggedIn;
	/**
	 * @var int
	 */
	private $startTime;
    /**
     * @var int
     */
    public $requestDateTime;
    public $proxyAddressOnInit;
    public $proxyRegionOnInit;
    public $proxyProviderOnInit;

    /** @var Callable */
	public $onTimeLimitIncreased;
    /**
     * @var Callable
     */
    public $onBrowserReady;

    /** @var Callable */
    public $onStateLoaded;

	public $virtualUsers = false;

	public static $logDir = "/var/log/www/awardwallet";

	/**
	 * will be increased on retries
	 * @var int
	 */
	public $attempt = 0;

    public $useLastHostAsProxy = false;
    /**
     * set it to when you plan to use useLastHostAsProxy
     * @var string
     */
    public $hostName;

    /**
     * @var Memcached
     */
    private $memcached;
    /**
     * @var CurlDriver
     */
    private $curlDriver;

    /** @var string */
    protected $httpLogDir;
    /**
     * @var ContainerInterface
     */
    public $services;
    private $Source;

	function __construct() {
		if (isset($_SESSION['UserID']))
			$this->userFields = $_SESSION;
		$this->logger = new AccountCheckerLogger($this);
		$this->startTime = time();
        $this->httpLogDir = self::$logDir . "/tmp/logs/pid-" . getmypid() . "-" . sprintf("%03f", microtime(true));
		$this->onLoggedIn = [$this, 'afterLogin'];
        $this->callTraitMethods("construct");
	}

	function setUserFields($fields) {
		if ($fields instanceof Usr) {
			$this->userFields['Email'] = $fields->getEmail();
			$this->userFields['FirstName'] = $fields->getFirstname();
			$this->userFields['LastName'] = $fields->getLastname();
			$this->userFields['Login'] = $fields->getLogin();
			$this->userFields['AccountLevel'] = $fields->getAccountlevel();
			$this->userFields['UserID'] = $fields->getUserid();
		} else
			$this->userFields = $fields;
	}

	function SetAccount($AccountFields, $loadAnswers = true) {
		global $Connection;
		$this->AccountFields = $AccountFields;
		if (isset($Connection) && $loadAnswers && isset($this->AccountFields['AccountID']))
			$this->Answers = SQLToArray("select Question, Answer from Answer where AccountID = {$this->AccountFields['AccountID']}", "Question", "Answer");
	}

	/**
	 * @param array $arAccountFields
	 * @return array('Title' => '', 'SQLParams' => '', ..., other TBaseForm properties)
	 */
	function TuneForm($arAccountFields) {
		return array();
	}

	function LoadState()
    {
        $this->logger->debug("LoadState method is deprecated");
    }

    function setState($BrowserState)
    {
        $this->logger->debug("setState method is deprecated");
    }

	protected function initState()
    {
        $BrowserState = isset($this->AccountFields["BrowserState"]) ? $this->AccountFields["BrowserState"] : '';

        $this->logger->debug("state size: " . strlen($BrowserState));
        if ($BrowserState != "" && (strlen($BrowserState) < self::MAX_BROWSERSTATE_LENGTH)) {
            if (strpos($BrowserState, 'base64:') === 0)
                $BrowserState = base64_decode(substr($BrowserState, strlen('base64:')));

            $arState = @unserialize($BrowserState);
            if (is_array($arState)) {
                $this->logger->debug("restoring state, keys: " . implode(", ", array_keys($arState)));
                $this->LastRequestTime = $arState["Time"];
                $this->State = ArrayVal($arState, "State", array());
                foreach ($this->State as $key => $value) {
                    $this->logger->debug("restored state key {$key}: " . json_encode($value));
                }
                if (isset($arState["Step"])) {
                    if (isset($arState["Time"]) && ((time() - $arState["Time"]) < $this->StepTimeout)) {
                        $this->Step = $arState["Step"];
                        $this->logger->debug("restored to step: " . $arState["Step"]);

                        if (isset($arState['Question']) && $arState['Question'] !== Html::cleanXMLValue($arState['Question'])) {
                            $arState['Question'] = Html::cleanXMLValue($arState['Question']);
                            $this->logger->debug("trimmed question");
                        }

                        if (isset($arState['Question']) && isset($this->Answers[$arState['Question']])) {
                            $this->Question = $arState['Question'];
                            $this->logger->debug("restored question: " . $arState["Question"]);
                        } else {
                            $this->logger->debug("discarded question, no answer: " . $arState["Question"]);
                            $this->logger->debug("existing answers: " . implode(", ", array_keys($this->Answers)));
                        }
                    } else {
                        $this->Step = null;
                        $this->logger->debug("discarded step, StepTimeout exceeded: " . $arState["Step"]);
                    }
                }
                $this->BrowserState = $arState;
            } else {
                $this->logger->error("failed to restore state");
            }
        }

        if (!empty($this->onStateLoaded)) {
            call_user_func($this->onStateLoaded, $this);
        }

        if (isset($this->Step, $this->Question) && $this->Question !== Html::cleanXMLValue($this->Question)) {
            $this->logger->debug("trimmed question: '{$this->Question}'");
            $this->Question = Html::cleanXMLValue($this->Question);
            $this->logger->debug("set question: '{$this->Question}'");
        }

        if (isset($this->Step) && (!isset($this->Question) || !isset($this->Answers[$this->Question]))) {
            $this->logger->debug("discarded step, because question is not set or there is no answer for the question");
            $this->Step = null;
        }

        if (empty($this->Step) && !empty($this->BrowserState['MultiValuedForms'])) {
            $this->logger->debug("resetting MultiValuedForms, no Step");
            unset($this->BrowserState['MultiValuedForms']);
        }
    }

	public function setCheckerStateItem($key, $value)
    {
        $this->State[$key] = $value;
    }

	public function getCheckerState()
    {
        return $this->State;
    }

	public function setCheckerState($state)
    {
        $this->State = $state;
    }

	protected function LoadBrowserState()
    {
        if (!$this->KeepState) {
            return;
        }

        if (empty($this->BrowserState) || !$this->http->CheckState($this->BrowserState)) {
            $this->logger->notice("failed to load state");
            return;
        }

        $arState = $this->BrowserState;
        $this->http->SetState($arState);

        if($this->useLastHostAsProxy
            && !empty($arState["Host"])
            && $this->http instanceof HttpBrowser
            && $arState["Host"] != $this->hostName
            && empty($this->http->GetProxy())
            && curlRequest('https://awardwallet-public.s3-website-us-east-1.amazonaws.com/access_marker.txt', 3, [CURLOPT_PROXY => $arState["Host"], CURLOPT_PROXYPORT => 3128]) == 'access_ok'
        ) {
            $this->http->Log("trying to use last host as proxy: " . $arState['Host']);
            $this->http->SetProxy($arState["Host"] . ":3128", false);
            $this->KeepHost = $arState['Host'];
        }
	}

	function GetState() {
		if ($this->ErrorCode != ACCOUNT_QUESTION)
			$this->http->Step = null;
		if ($this->KeepState) {
			$arState = $this->http->GetState();
			$arState["Step"] = $this->Step;
			$arState["Question"] = $this->Question;
			$arState["Time"] = time();
			$arState["State"] = $this->State;
			if($this->useLastHostAsProxy) {
                if (!empty($this->KeepHost))
                    $arState["Host"] = $this->KeepHost;
                else {
                    if (!empty($this->hostName)) {
                        $arState["Host"] = $this->hostName;
                        $this->logger->debug("saved last host: {$this->hostName}");
                    }
                }
            }
			$result = 'base64:' . base64_encode(serialize($arState));
			if(strlen($result) > self::MAX_BROWSERSTATE_LENGTH)
                DieTrace("State too long", false, 0, strlen($result));
			return $result;
		} else
			return null;
	}

	function Cleanup() {
		if (!$this->KeepLogs) {
			if ($this->LogMode == "dir") {
				DeleteFiles($this->http->LogDir . "/*");
				rmdir($this->http->LogDir);
			}
			$this->SendLogs();
		}
		if ($this->http !== null) {
            $this->http->cleanup();
        }
		if ($this->ShowLogs)
			$this->ShowLogs();
	}

	function SendLogs() {
		if (((($this->ErrorCode == ACCOUNT_ENGINE_ERROR) && !$this->Cancelled) || $this->EmailLogs) && file_exists($this->http->LogDir . "/log.html")) {
			if (ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG)
				$this->SendLogsToEmail();
			TAccountChecker::ArchiveLogs($this->http->LogDir, $this->GetResultHTML());
		}
	}

	function GetResultHTML() {
		return "Login: {$this->AccountFields["Login"]}<br>
		Password: {$this->AccountFields["Pass"]}<br>
		AccountID: " . ArrayVal($this->AccountFields, "RequestAccountID", $this->AccountFields['AccountID']) . "<br>
		Balance: " . (is_null($this->Balance) ? 'n/a' : $this->Balance) . "<br>
		Error Code: {$this->ErrorCode}<br>
		Properties: "
		. print_r($this->Properties, true) . "<br>
		Log:<hr>\n"
		. file_get_contents($this->http->LogDir . "/log.html");
	}

	function SendLogsToEmail() {
		global $sPath;
		require_once("$sPath/lib/htmlMimeMail5/htmlMimeMail5.php");
		$mail = new htmlMimeMail5();
		$mail->setHTML($this->GetResultHTML());
		$files = glob($this->http->LogDir . "/step*.*");
		foreach ($files as $file)
			$mail->addAttachment(new fileAttachment($file));
		if ($this->EmailLogs)
			$mail->setSubject("provider {$this->AccountFields["ProviderCode"]} - logs");
		else
			$mail->setSubject("provider {$this->AccountFields["ProviderCode"]} failed");
		foreach (explode("\n", EMAIL_HEADERS) as $header) {
			$pair = explode(":", $header);
			$mail->setHeader(trim($pair[0]), trim($pair[1]));
		}
		$mail->send(array(ConfigValue(CONFIG_ERROR_EMAIL)));
	}

	// $logDir may be null to archive single log
	public static function ArchiveLogs($logDir, $mainLog, $baseName = null, $accountFields = null) {
		if (!isset($baseName))
			$baseName = basename($logDir);
		if (isset($logDir))
			$files = glob($logDir . "/*.*");
		else
			$files = array();
		$maskText = array();
		if (isset($accountFields['Pass']))
			$maskText[$accountFields['Pass']] = '**PASSWORD**';
		foreach (array('Login', 'Login2', 'CardNumber') as $field)
			if (isset($accountFields[$field]) && preg_match('/^\d{15,16}$/ims', $accountFields[$field]))
				$maskText[$accountFields[$field]] = '**CC_' . $field . '_' . substr($accountFields[$field], 12, 4) . '**';
		if (isset($accountFields["SecurityNumber"]))
			$maskText[$accountFields["SecurityNumber"]] = "***";
		if (count($maskText) > 0) {
			self::maskPasswords(array_merge(array($logDir . '/log.html'), $files), $maskText);
			foreach ($maskText as $search => $replace)
				$mainLog = str_replace($search, $replace, $mainLog);
		}
		$zipFile = self::ArchiveLogsToZip($mainLog, $baseName, $files);
		if (!ConfigValue(CONFIG_TRAVEL_PLANS) && isset($zipFile))
			self::ArchiveLogsToDatabase($accountFields, $zipFile);
		return $zipFile;
	}

	private static function maskPasswords(array $files, array $maskText) {
		foreach ($files as $file)
			if (file_exists($file)) {
				$text = file_get_contents($file);
				foreach ($maskText as $search => $replace)
					$text = str_replace($search, $replace, $text);
				file_put_contents($file, $text);
			}
	}

	public static function ArchiveLogsToZip($mainLog, $baseName, $files) {
		$zip = new ZipArchive;
		if (preg_match("/account\-(\d+)\-/ims", $baseName, $matches))
            // kept "checklogs" for compatibility with loyalty / email
			$filename = self::$logDir . "/checklogs/" . sprintf("%03d", floor($matches[1] / 1000)) . "/" . $baseName . ".zip";
		else
			$filename = self::$logDir . "/checklogs/" . $baseName . ".zip";
		if (!MkDirs(dirname($filename))) {
			DieTrace("Failed to create dirs for: $filename", false);
			return null;
		}
		$code = $zip->open($filename, ZipArchive::CREATE);
		if ($code === true) {
			if (!$zip->addFromString("log.html", $mainLog))
				DieTrace("Failed to add log to zip: $filename", false);
			foreach ($files as $index => $file) {
				if (is_numeric($index)) {
					$baseName = basename($file);
					if ($baseName != "log.html")
						if (!$zip->addFile($file, $baseName))
							DieTrace("Failed to add $file to zip: $filename", false);
				} else
					if (!$zip->addFromString($index, $file))
						DieTrace("Failed to add log to zip: $filename", false);
			}
			if (!$zip->close())
				DieTrace("Failed to close zip: $filename", false);
		} else
			DieTrace("Failed to create zip: $filename, code: $code", false);
		return $filename;
	}

	private static function ArchiveLogsToDatabase($accountFields, $zipFile) {
		global $Connection;
		$Connection->Execute(InsertSQL("AccountLog", array(
			"LogDate" => "now()",
			"Partner" => "'" . addslashes(ArrayVal($accountFields, 'Partner')) . "'",
			"ProviderID" => $accountFields['ProviderID'],
			"RequestAccountID" => "'" . addslashes(ArrayVal($accountFields, 'RequestAccountID')) . "'",
			"Login" => "'" . addslashes(ArrayVal($accountFields, 'Login')) . "'",
			"Login2" => empty($accountFields['Login2']) ? "null" : "'" . addslashes($accountFields['Login2']) . "'",
			"Login3" => empty($accountFields['Login3']) ? "null" : "'" . addslashes($accountFields['Login3']) . "'",
			"Zip" => "'" . addslashes(base64_encode(file_get_contents($zipFile))) . "'"
		), false, true));
		unlink($zipFile);
		$accountLogId = $Connection->InsertID();
		if ($accountLogId == 0)
			return;
	}

	// show logs to screen
	function ShowLogs() {
		if (ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_PRODUCTION)
			return;
		$logFile = $this->http->LogDir . "/log.html";
		if (file_exists($logFile))
			Redirect("/admin/common/logFile.php?Dir=" . urlencode($this->http->LogDir) . "&File=" . urlencode("log.html"));
		else
			DieTrace("log not found");
	}

	function CheckConfirmationNumber($arFields, &$it, $providerFields) {
//		global $sPath;
		$this->InitBrowser();
        if(!empty($this->onBrowserReady))
            call_user_func($this->onBrowserReady, $this);
        $this->checkThrottledOnRequest();
        if (isset($providerFields['Code']))
            $this->logger->info("Provider code: ".$providerFields['Code']);

        $confirmationNumberURL = $this->ConfirmationNumberURL($arFields);
        $this->logger->info("ConfirmationNumberURL: <a target='_blank' style='color:black; text-decoration:none;' href='{$confirmationNumberURL}'>{$confirmationNumberURL}</a>");

        $this->logger->info("<style>.time { display: none; }</style> <a href='#' onclick=\"$('span.time').show(); return false;\">show timings</a>", ["HtmlEncode" => false]);
        $this->logger->info("Fields:");
        $this->logger->debug(var_export($arFields, true), ['pre' => true]);
        try {
            if ($this->VirtuallyThere) {
                $virtuallyThere = new TVirtuallyThereParser($this->db);
                $url = "https://www.virtuallythere.com/new/homePage.html?language=0&clocktype=24&style=0&host=F9&pnr=" . urlencode($arFields['RecordLocator']) . "&name=" . urlencode($arFields['LastName']);
                $virtuallyThere->sPassword = $arFields["Password"];
                $result = $virtuallyThere->CheckConfirmationNumber($url, $it);
            } else {
                $result = $this->CheckConfirmationNumberInternal($arFields, $it);
            }
        }
        catch(InvalidDataException $e) {
            $this->logger->info('Itineraries Error:');
            $this->logger->info($e->getMessage());
            $this->itinerariesMaster->clearItineraries();
            $result = null;
        }
        $this->logger->info('Check Confirmation Result', ['Header' => 2]);
        $this->logger->info('CheckConfirmationNumberInternal Message:');
        $this->logger->error(var_export($result, true), ['pre' => true]);
        $this->logger->info("Got itineraries:");
        if ($this->itinerariesMaster !== null && !empty($this->itinerariesMaster->getItineraries())) {
            $data = $this->itinerariesMaster->toArray(true);
            $this->logger->info(Jsonator::html($data, (new Analyzer())->getArraySchema($data)), ['pre' => true]);
        } else {
            $this->logger->info(htmlspecialchars(var_export($it, true)), ['pre' => true]);
            if (ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG)
                $this->CheckRequiredFields();
        }

        if (!is_array($it)) {
            $it = [];
            $this->logger->info('itineraries expected to be an array!');
        }

        return $result;
	}

	function ConfirmationNumberURL($arFields) {
		return null;
	}

	function InitBrowser() {
        if(!empty($this->http)){
            StatLogger::getInstance()->debug(
                'Create HttpBrowser repeatedly',
                isset($this->AccountFields['AccountID']) ? ['accountId' => $this->AccountFields['AccountID']] : []
            );
        }

		switch ($this->AccountFields['ProviderEngine']) {
			case PROVIDER_ENGINE_SELENIUM:
				$this->UseSelenium();
				break;
			default:
				$this->UseCurlBrowser();
				break;
		}
        $this->logger->debug("attempt: {$this->attempt}");
	    $this->initState();
	}

	function ExecStep() {
        $this->logger->debug("proceeding to step: " . $this->Step);
		$step = $this->Step;
		$this->Step = null;
		$question = $this->Question;
		$loggedIn = $this->ProcessStep($step);
		// remove invalid answer
		if (isset($question) && ($question == $this->Question) && ($this->ErrorCode == ACCOUNT_QUESTION))
			unset($this->Answers[$this->Question]);
		return $loggedIn;
	}

	function AskQuestion($question, $errorMessage = null, $step = null) {
		$this->ErrorCode = ACCOUNT_QUESTION;
		$this->Question = Html::cleanXMLValue($question);
		if (isset($errorMessage))
			$this->ErrorMessage = $errorMessage;
		$this->Step = $step;
	}

	function Check($initBrowser = true) {
		global $Connection, $arAccountErrorCode;
		if ($initBrowser)
			$this->InitBrowser();
		$this->LoadBrowserState();
		if(!empty($this->onBrowserReady))
      			call_user_func($this->onBrowserReady, $this);
        $this->checkThrottledOnRequest();
		try {
            $this->http->start();
            if(method_exists($this, 'Start'))
                $this->Start();
			if (ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG) {
				$this->ShowLogs = isset($_GET['ShowLogs']);
				$this->ParseIts = $this->ParseIts || isset($_GET['ParseIts']);
			}
			$loggedIn = false;
			$this->logger->info('Account Check Parameters', ['Header' => 2]);
			$this->logger->info("Provider engine: " . strval(get_class($this->http)));
            $this->logger->info("Provider code: " . ArrayVal($this->AccountFields, 'ProviderCode'));
			if (ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG)
                $this->logger->info("Account ID: " . ArrayVal($this->AccountFields, 'AccountID'));
            $this->logger->info("Class name: " . get_class($this));
            $this->logger->info("Server: " . gethostname() . ", " . date("r")
                . "<style>.time { display: none; }</style> <a href='#' onclick=\"$('span.time').show(); return false;\">show timings</a>"
                , ["HtmlEncode" => false]);
			$this->http->Log("Credentials: " . print_r(array_intersect_key($this->AccountFields, array("Login" => true, "Login2" => true, "Login3" => true, "Pass" => true, 'Partner' => true, 'AccountID' => true)), true), LOG_LEVEL_NORMAL);
            if (isset($this->AccountFields['RaRequestFields'])) {
                $this->logger->info('Request Fields:');
                $this->logger->info(var_export($this->AccountFields['RaRequestFields'], true), ['pre' => true]);
            }
            $this->proxyAddressOnInit = $this->getProxyIpFromState() ?? $this->http->getProxyAddress();
            $this->proxyProviderOnInit = $this->http->getProxyProvider();
            $this->proxyRegionOnInit = $this->http->getProxyRegion();
            $this->http->Log("Answers on enter: " . print_r($this->Answers, true), LOG_LEVEL_NORMAL);
			$this->OriginalAnswers = $this->Answers;
            $this->logger->info("Question on enter: " . $this->Question);
            $this->logger->info("Step: " . $this->Step);
			if (isset($this->LastRequestTime)) {
				$time = date("r", $this->LastRequestTime);
                $this->logger->info("Last request time: " . $time . ", since last: " . (time() - $this->LastRequestTime));
			} else
                $this->logger->info("Last request time: none");
			$this->http->LogSplitter();
			if ($this->TransferMethod == 'register') {
                if (isset($this->Step) && $this->Step == 'Question') {
                    $loggedIn = $this->ExecStep();
                    if ($loggedIn) {
                        $this->ErrorCode = ACCOUNT_CHECKED;
                    } else {
                        $this->ErrorCode = ACCOUNT_ENGINE_ERROR;
                    }
                    $this->onLoggedIn = null;
                } else {
                    $loggedIn = true;
                }
			}
			else {
				if (isset($this->Step)) {
					$loggedIn = $this->ExecStep();
				} else {
				    $context = array_intersect_key(
                        $this->AccountFields,
                        ['Partner' => true, 'ProviderCode' => true, 'RequestAccountID' => true]
                    );
                    $this->logger->debug("checking, if already logged in");
					if ($this->attempt == 0 && $this->IsLoggedIn()) {
                        $this->logger->debug("yes, already logged in");
                        $loggedIn = true;
                        $context = array_merge($context, ["success" => true]);
                        StatLogger::getInstance()->info("IsLoggedIn statistic", $context);
					} else {
                        $this->logger->debug("not logged in, loading login form");
						$this->logger->info('Load Login Form', ['Header' => 2]);
                        if ($this->attempt == 0) {
                            $context = array_merge($context, ["success" => false]);
                            StatLogger::getInstance()->info("IsLoggedIn statistic", $context);
                        }
						if ($this->LoadLoginForm()) {
                            $this->logger->debug("form loaded, logging in");
							$this->logger->info('Login', ['Header' => 2]);
							$loggedIn = $this->Login();
							$this->LoggedIn = $this->LoggedIn || $loggedIn;
						}// if ($this->LoadLoginForm())
						else
                            $this->logger->error("failed to load login form");
					}
				}

                if (strstr($this->http->Error, 'Network error 7 - Failed to connect to')) {
                    $this->DebugInfo = $this->http->Error;
                }

				if (($this->ErrorCode == ACCOUNT_QUESTION) && !isset($this->Step)) {
                    $this->logger->error("question returned, but step is not set, assuming question");
					$this->Step = "Question";
				}
				if (($this->ErrorCode == ACCOUNT_QUESTION) && isset($this->Answers[$this->Question]) && isset($this->Step)) {
                    $this->logger->info("trying to apply saved answer");
					$this->ErrorCode = ACCOUNT_ENGINE_ERROR;
					$this->ErrorMessage = "Unknown error";
					$question = $this->Question;
					$loggedIn = $this->ExecStep();
					// remove invalid answer
					if (isset($question) && ($question == $this->Question) && ($this->ErrorCode == ACCOUNT_QUESTION))
						unset($this->Answers[$this->Question]);
				}
			}
		} catch (CheckException $e) {
			$this->ErrorCode = $e->getCode();
			$this->ErrorMessage = $e->getMessage();
			$loggedIn = false;
		} catch (Exception $e) {
		    $this->takeSeleniumScreenshot($e);
            $retryTimeout = 3;
            $checkAttemptsCount = 3;
            $this->logger->error(get_class($e));
            if (get_class($e) == 'ThrottledException') {
                $this->logger->error($e->getTraceAsString(), ['pre' => true]);
            }
            if ($this->isRewardAvailability) {
                $retryTimeout = 0;
                $checkAttemptsCount = 5;
            }
            if (strpos($e->getMessage(), 'No active session with ID') !== false) {
                throw new CheckRetryNeededException($checkAttemptsCount, $retryTimeout, null, null, $e);
            }
            if (
                $e instanceof ScriptTimeoutException
                || $e instanceof \Facebook\WebDriver\Exception\ScriptTimeoutException
            ) {
                $this->logger->error("exception caught - " . get_class($e) . ': ' . $e->getMessage() . "\n");
                if ($this->isRewardAvailability) {
                    throw new CheckRetryNeededException($checkAttemptsCount, $retryTimeout, null, null, $e);
                }
                else {
                    throw new ThrottledException(1, null, $e);
                }
            }
            if ($e instanceof WebDriverCurlException && stripos($e->getMessage(), 'DesiredCapabilities') !== false){
                $this->logger->notice("failed to start selenium: " . $e->getMessage(), ["exception" => $e]);
                if (!$this->isRewardAvailability) {
                    $retryTimeout = random_int(8, 20);
                }
                throw new CheckRetryNeededException($checkAttemptsCount, $retryTimeout, null, null, $e);
            }
            if (
                ($e instanceof \Facebook\WebDriver\Exception\WebDriverException || $e instanceof WebDriverException)
                && stripos($e->getMessage(), 'JSON decoding of remote response failed') !== false)
            {
                $this->logger->notice("failed to start selenium: " . $e->getMessage() . " at " . $e->getTraceAsString(), ["exception" => $e]);
                if (!$this->isRewardAvailability) {
                    $retryTimeout = random_int(8, 20);
                }
                throw new CheckRetryNeededException($checkAttemptsCount, $retryTimeout, null, null, $e);
            }
            if (
                $e instanceof UnknownServerException
                || $e instanceof \Facebook\WebDriver\Exception\UnknownServerException
                || $e instanceof \SessionNotCreatedException
                || $e instanceof \Facebook\WebDriver\Exception\SessionNotCreatedException
            ) {
                $this->logger->error("webdriver exception caught - " . get_class($e) . ': ' . $e->getMessage() . ", will retry");

                throw new CheckRetryNeededException($checkAttemptsCount, $retryTimeout, null, null, $e);
            }
            if (
                $e instanceof NoSuchWindowException
                || $e instanceof \Facebook\WebDriver\Exception\NoSuchWindowException
            ) {
                $this->logger->error("NoSuchWindowException caught - " . get_class($e) . ': ' . $e->getMessage() . ", will retry");

                throw new CheckRetryNeededException($checkAttemptsCount, $retryTimeout, null, null, $e);
            }
            if ($this->throwToParent($e)) {
                throw $e;
            } else {
                $this->logger->error("exception caught - " . get_class($e) . ': ' . $e->getMessage() . "\n" . "trace: " . $e->getTraceAsString(), ['HtmlEncode' => true]);
                $this->ErrorCode = ACCOUNT_ENGINE_ERROR;
                $this->ErrorMessage = 'Unknown error';
                $this->DebugInfo = 'exception caught - ' . (strlen($e->getMessage()) > 40 ? substr($e->getMessage(), 0,
                            37) . '...' : $e->getMessage());
                $loggedIn = false;
            }
		} catch (Throwable $e) {
            $loggedIn = false;
            $this->handleThrowable($e);
        } finally {
            $this->SaveAnswers();
        }

		if ($this->ErrorCode == ACCOUNT_QUESTION) {
            $this->logger->error("question returned");
			if (!isset($this->Question))
                DieTrace("ErrorCode is ACCOUNT_QUESTION, but Question is not set");

            $this->Question = Html::cleanXMLValue($this->Question);

			if (strlen($this->Question) > 250)
                DieTrace("Question is too long: " . $this->Question);
			$_SESSION['SecurityQuestion_' . $this->AccountFields['AccountID']] = $this->Question;
			if ($this->ErrorMessage == "Unknown error")
				$this->ErrorMessage = $this->Question;
            $this->logger->info("answers on exit: " . var_export($this->Answers ?? null, true));
		} else
			$this->Question = null;

		try {

		    if ($loggedIn && !empty($this->onLoggedIn))
		        call_user_func($this->onLoggedIn);

        } catch (CheckException $e) {
            $this->ErrorCode = $e->getCode();
            if ($e->getCode() == ACCOUNT_ENGINE_ERROR) {
                $this->DebugInfo = $e->getMessage();
                $this->ErrorMessage = 'Unknown error';
            } else {
                $this->ErrorMessage = $e->getMessage();
            }
        } catch (Throwable $e) {
		    $this->takeSeleniumScreenshot($e);
            $this->handleThrowable($e);
        } finally {
            $this->SaveAnswers();
        }

//		if(!isset($arAccountErrorCode[$this->ErrorCode]) || $this->ErrorCode == ACCOUNT_UNCHECKED)
//			$this->ErrorCode = ACCOUNT_ENGINE_ERROR;
		$this->SaveAnswers();
		$this->logger->info('Account Check Result', ['Header' => 2]);
        if ($this->ErrorCode == ACCOUNT_CHECKED)
            $errorMessage = 'No errors';
        else {
            $errorMessage = $this->ErrorMessage;

            if ($this->ErrorCode == ACCOUNT_QUESTION && $this->ErrorMessage == "Unknown error") {
                $this->Question = Html::cleanXMLValue($this->Question);
                $errorMessage = $this->Question;

                if ($this->KeepState === false) {
                    $this->logger->notice("[ATTENTION]: question returned, but KeepState is false");
                }
            }
        }
        $this->logger->error("error: " . $errorMessage . " [" . $arAccountErrorCode[$this->ErrorCode] . "]");
        $this->logger->debug("debug info: " . htmlspecialchars($this->DebugInfo));

        if ($this->TransferMethod == 'register') {
            if ($this->ErrorCode == ACCOUNT_QUESTION) {
                $this->logger->error("question returned");
                if (!isset($this->Question))
                    DieTrace("ErrorCode is ACCOUNT_QUESTION, but Question is not set");

                $this->Question = Html::cleanXMLValue($this->Question);

                if (strlen($this->Question) > 250)
                    DieTrace("Question is too long: " . $this->Question);
//                $_SESSION['SecurityQuestion_' . $this->AccountFields['AccountID']] = $this->Question;

                if ($this->ErrorMessage == "Unknown error")
                    $this->ErrorMessage = $this->Question;
                $this->logger->info("answers on exit: " . var_export($this->Answers ?? null, true));
            } else
                $this->Question = null;
            $this->logger->info("step: " . $this->Step);
            $this->logger->info("question: " . $this->Question);
            $this->logger->info("invalid answers:");
            $this->logger->info(htmlspecialchars(print_r($this->InvalidAnswers, true)), ['pre' => true]);
            return;
        }

        $this->logger->info("balance: " . (is_null($this->Balance) ? 'n/a' : $this->Balance));
        $this->logger->info("properties:");
        $this->logger->info(htmlspecialchars(var_export($this->Properties, true)), ['pre' => true]);
        $this->logger->info("files:");
        $this->logger->info(htmlspecialchars(var_export($this->Files, true)), ['pre' => true]);
        $this->logger->info("step: " . $this->Step);
        $this->logger->info("question: " . $this->Question);
        $this->logger->info("invalid answers:");
        $this->logger->info(htmlspecialchars(print_r($this->InvalidAnswers, true)), ['pre' => true]);
        $this->logger->info('itineraries:');
        if ($this->itinerariesMaster !== null && !empty($this->itinerariesMaster->getItineraries())) {
            $data = $this->itinerariesMaster->toArray(true);
            $this->logger->info(Jsonator::html($data, (new Analyzer())->getArraySchema($data)), ['pre' => true]);
        } elseif ($this->itinerariesMaster->getNoItineraries()) {
            $data = [
                'noItineraries' => $this->itinerariesMaster->getNoItineraries(),
            ];
            $this->logger->info(json_encode($data, JSON_PRETTY_PRINT), ['pre' => 'true']);
        } else {
            $this->logger->info(htmlspecialchars(var_export($this->Itineraries, true)), ['pre' => true]);
            if (ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG)
                $this->CheckRequiredFields();
        }
	}

    private function handleThrowable(Throwable $e)
    {
        if ($this->throwToParent($e)) {
            throw $e;
        }
        $title = "Exception - " . get_class($e) . ': ' . $e->getMessage();
        $trace = "<pre><b>" . $e->getFile() . ":" . $e->getLine() . "</b><br/></br>" . $e->getTraceAsString() . "</pre>";
        $this->sendNotification($title, 'all', true, $trace);
        $this->logger->debug("trace: " . $trace, ["HtmlEncode" => false]);
        $this->ErrorCode = ACCOUNT_ENGINE_ERROR;
        $this->ErrorMessage = 'Unknown error';
        $this->DebugInfo = 'exception caught - ' . (strlen($e->getMessage()) > 40 ? substr($e->getMessage(), 0,
                    37) . '...' : $e->getMessage());

    }

	private function throwToParent(Throwable $e)
    {
        return
            (interface_exists('\PHPUnit\Exception') && $e instanceof \PHPUnit\Exception)
            || ($e instanceof CheckAccountExceptionInterface && $e->throwToParent())
        ;
    }

	public function afterLogin()
    {
        $this->logger->error("logged in, parsing");
        if (!empty($this->TransferFields)) {
            $this->http->Log($this->TransferMethod);
            switch($this->TransferMethod) {
                case 'transfer':
                    $targetProvider = $this->TransferFields['targetProvider'];
                    $targetAccount = $this->TransferFields['targetAccount'];
                    $numberOfMiles = intval($this->TransferFields['numberOfMiles']);
                    $this->logger->notice("transfer {$numberOfMiles} miles to {$targetProvider} ({$targetAccount})");
                    $fields = $this->TransferFields;
                    unset($fields['targetProvider']);
                    unset($fields['targetAccount']);
                    unset($fields['numberOfMiles']);
                    $result = $this->transferMiles($targetProvider, $targetAccount, $numberOfMiles, $fields);
                    break;
                case 'register':
                    $this->logger->notice('register new acc with login ' . ArrayVal($this->TransferFields, 'Login'));
                    $result = $this->registerAccount($this->TransferFields);
                    if (!$result && $this->ErrorCode === ACCOUNT_QUESTION) {
                        return;
                    }
                    break;
                case 'purchase':
                    $this->logger->notice('purchasing ' . $this->TransferFields['numberOfMiles'] . ' miles');
                    $fields = $this->TransferFields;
                    $cc = $fields['ccFull'];
                    $miles = intval($fields['numberOfMiles']);
                    unset($fields['ccFull']);
                    unset($fields['numberOfMiles']);
                    $this->checkPurchaseParameters($cc);
                    $result = $this->purchaseMiles($fields, $miles, $cc);
                    break;
                default:
                    $result = false;
                    $this->logger->error('invalid transfer method');
                    break;
            }
            if (!$result)
                throw new CheckException('Unknown error', ACCOUNT_ENGINE_ERROR);
            $this->logger->notice('completed');
            $this->ErrorCode = ACCOUNT_CHECKED;
        }
        else {
            $this->logger->info('Parse', ['Header' => 2]);
            $this->Parse();
        }
        // do not parse anything if method Parse failed
        if (!in_array($this->ErrorCode, [ACCOUNT_CHECKED, ACCOUNT_WARNING, ACCOUNT_QUESTION])
            && ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG)
            throw new CheckException('Balance not found', ACCOUNT_ENGINE_ERROR);

        // do not parse anything if the balance is more than a billion 
        if ($this->Balance > 1000000000 && ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
            throw new CheckException('Balance too big', ACCOUNT_ENGINE_ERROR);
        }

        if ($this->ParseIts) {
            $this->logger->info('Parse Itineraries', ['Header' => 2]);
            if (isset($this->itinerariesMaster))
                $this->itinerariesMaster->getLogger()->pushHandler(new AccountCheckerLoggerHandler($this->logger));
            try {
                $this->Itineraries = $this->ParseItineraries();
                if (empty($this->Itineraries) && $this->itinerariesMaster !== null && !empty($this->itinerariesMaster->getItineraries()))
                    $this->itinerariesMaster->checkValid();
            }
            catch(InvalidDataException $e) {
                $this->logger->info('Itineraries Error:');
                $this->logger->info($e->getMessage());
                //$this->sendNotification($e->getMessage());
                $this->itinerariesMaster->clearItineraries();
            }
            finally {
                if (isset($this->itinerariesMaster))
                    $this->itinerariesMaster->getLogger()->popHandler();
            }
            /*
            $this->logger->info("Itineraries:");
            if ($this->itinerariesMaster !== null && !empty($its = $this->itinerariesMaster->getItineraries())) {
                $data = $this->itinerariesMaster->toArray(true);
                $this->logger->info(\AwardWallet\Common\Parser\Data\Jsonator::html($data, (new \AwardWallet\Common\Parser\Data\Analyzer())->getArraySchema($data)), ['pre' => true]);
            } else {
                $this->logger->info(htmlspecialchars(var_export($this->Itineraries, true)), ['pre' => true]);
                if (ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG)
                    $this->CheckRequiredFields();
            }
            */
        }
        if ($this->WantHistory) {
            $this->logger->info('Parse History', ['Header' => 2]);
            $this->History = $this->ParseHistory($this->HistoryStartDate);
            $this->logger->info("History:");
            $this->logger->info(htmlspecialchars(var_export($this->History, true)), ['pre' => true]);
        }
        if ($this->WantFiles) {
            $this->logger->info('Parse Files', ['Header' => 2]);
            $this->Files = $this->ParseFiles($this->FilesStartDate);
            $this->logger->info("Files:");
            $this->logger->info(htmlspecialchars(print_r(
                array_map(function ($el) {
                    if (isset($el["Content"])) $el["Content"] = strlen($el["Content"]) . " bytes encoded";
                    return $el;
                }, $this->Files), true)), ['pre' => true]);
        }
    }

    public function ChangePassword(string $newPassword) {
        $this->logger->info('Change Account Password', ['Header' => 2]);
        if (ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG)
            $this->logger->debug('NewPassword: '.$newPassword);

	    if(intval($this->AccountFields['CanChangePasswordServer']) !== 1)
           throw new CheckException('This provider does not support change password server method', ACCOUNT_PROVIDER_ERROR);

        if(trim($newPassword) === '')
            throw new UserInputError('Unavailable newPassword field value');

        $result = $this->changePasswordInternal($newPassword);
        if(true === $result)
            $this->ErrorCode = ACCOUNT_CHECKED;
        else
            throw new EngineError('changePasswordInternal method returned false');
    }

    /**
     * @param string $newPassword
     * @return boolean
     * @throws CheckException
     */
    protected function changePasswordInternal(string $newPassword) {
        throw new CheckException('This provider does not support change password method', ACCOUNT_PROVIDER_ERROR);
    }

    protected function getCurlDriver(): CurlDriver
    {
        return $this->curlDriver;
    }

    /**
     * @internal
     * @param CurlDriver $curlDriver
     */
    public function setCurlDriver(CurlDriver $curlDriver): void
    {
        $this->curlDriver = $curlDriver;
    }

    /**
     * @return string
     * @throws CheckException
     */
    protected function generatePassword() {
        throw new CheckException('This provider does not support change password method', ACCOUNT_PROVIDER_ERROR);
    }

	protected function checkPurchaseParameters($creditCard) {
		$this->logger->info('Checking purchase parameters');

		// No check on production
		if (SITE_STATE_PRODUCTION == ConfigValue(CONFIG_SITE_STATE)) {
			$this->logger->info('No check on production');
			return;
		}

		$ip = null;
		$this->logger->info('Getting IP address');
		$this->http->GetURL('https://ipinfo.io/ip');
//		$this->http->GetURL('http://ip.appspot.com');
		$ip = $this->http->Response['body'];
		if (!$ip)
			throw new EngineError('Could not get IP address');
		if (!preg_match('#^\s*(<pre[^>]*>)?\s*(?<ip>\d+\.\d+\.\d+\.\d+)\s*(<\/pre>)?\s*$#i', $ip, $m)) // selenium returns body '<pre>1.1.1.1</pre>
			throw new EngineError('Invalid IP address in response "'.$this->http->Response['body'].'"');
		$ip = $m['ip'];
		$this->logger->info("IP address: $ip");

		$this->logger->info('Getting credit card number');
		if (!isset($creditCard['CardNumber']))
			throw new EngineError('Could not check credit card number, it is not set');
		$creditCardNumber = $creditCard['CardNumber'];
		if (!preg_match('#^\d+$#i', $creditCardNumber))
			throw new EngineError('Invalid credit card number "'.$creditCardNumber.'"');
		$this->logger->info('Got credit card number');

		$this->logger->info('Checking IP and credit card number');
		if ($this->allowRealIPAndCreditCardForLocalUsage) {
			$this->logger->notice('"ALLOW REAL PROXY AND CREDIT CARD NUMBER" parameter is set, use it very carefully');
			if ($ip == REWARDS_PURCHASE_TEST_PROXY and $creditCardNumber != REWARDS_PURCHASE_FAKE_CREDIT_CARD)
				throw new EngineError('You should never use rewards purchase test proxy with cards other then fake one');
			if ($ip != REWARDS_PURCHASE_TEST_PROXY and $creditCardNumber == REWARDS_PURCHASE_FAKE_CREDIT_CARD)
				throw new EngineError('You should never use fake credit card without special rewards purchase test proxy');
		} else {
			if ($ip != REWARDS_PURCHASE_TEST_PROXY)
				throw new EngineError("Wrong IP $ip, local purchase tests should be performed only with special proxy ".REWARDS_PURCHASE_TEST_PROXY.', unless "allowRealIPAndCreditCardForLocalUsage" is set to true');
			if ($creditCardNumber != REWARDS_PURCHASE_FAKE_CREDIT_CARD)
				throw new EngineError('Bad credit card number, you should use fake credit card when running local purchase tests, unless "allowRealIPAndCreditCardForLocalUsage" is set to true');
		}
		$this->logger->info('Purchase parameters checked, everything is OK');
	}

	/**
	 * this method will transfer miles from this provider to another one
	 * this method will be called after Login method, instead of Parse
	 * can throw CheckException with PROVIDER_ERROR code
	 *
	 * @param $targetProviderCode string like 'delta'
	 * @param $targetAccountNumber string like '1234ABCD'
	 * @param $numberOfMiles int how many miles to transfer
	 *
	 * @return bool true on successful transfer
	 *
	 * @throws CheckException
	 */

	public function transferMiles($targetProviderCode, $targetAccountNumber, $numberOfMiles, $fields = array())
	{
		throw new CheckException('Miles transfer is not implemented for this provider', ACCOUNT_PROVIDER_ERROR);
	}

	/**
	 * returns array of fields that are needed to register new account for this provider
	 * format is the same as GetConfirmationFields
	 *
	 * @return array
	 */
	public function getRegisterFields() {
		return null;
	}

	/**
	 * this method will register new account on provider site using data in $fields
	 * returns true on success, false otherwise
	 *
	 * @param array $fields
	 * @return bool
	 * @throws CheckException
	 */
	public function registerAccount(array $fields) {
		throw new CheckException("Registration for this provider is not supported");
	}

	/**
	 * returns array of fields that are needed to purchase miles or points for this provider
	 * format is the same as GetConfirmationFields
	 *
	 * @return array
	 */
	public function getPurchaseMilesFields() {
		return null;
	}

	/**
	 * this method will purchase miles or points using data in $fields
	 * returns true on success, false otherwise
	 *
	 * @param array $fields
	 * @param int $numberOfMiles
	 * @param array $creditCard: [
	 *	"Type" => "visa",
	 *	"CardNumber" => "4444444444444448",
	 *	"SecurityNumber" => "123",
	 *	"ExpirationMonth" => "7",
	 *	"ExpirationYear" => "17",
	 *	"Name" => "John Doe",
	 *	"AddressLine" => "12 Sesame Street",
	 *	"City" => "El Dorado",
	 *	"Country" => "United States of America",
	 *	"CountryCode" => "US",
	 *	"State" => "Pennsylvania",
	 *	"StateCode" => "PA",
	 *	"PhoneNumber" => "123 546 7890",
	 *	"Zip" => "12345"];
	 * @return bool
	 * @throws CheckException
	 */
	public function purchaseMiles(array $fields, $numberOfMiles, $creditCard) {
		throw new CheckException('This provider does not support miles purchase', ACCOUNT_PROVIDER_ERROR);
	}

	protected function SaveAnswers() {
		// answers on wsdl did not transferred back to prod, so, we will mark invalid answers in state
		foreach($this->OriginalAnswers as $question => $answer) {
			if (!isset($this->Answers[$question])) {
				$this->logger->notice("marking question '{$question}' as invalid");
				$this->InvalidAnswers[$question] = $answer;
			}
		}
	}

	function ParseFiles($filesStartDate) {
		return array();
	}

	static protected $itinerarySchema = array(
		'T' => array(
			'RecordLocator' => self::CONFNO_REGEXP,
			'TripNumber' => self::FIELD_ALLOWED,
            'ConfirmationNumbers' => self::FIELD_ALLOWED,
			'TripSegments' => array(
				'FlightNumber' => true,
				'DepCode' => self::AIRCODE_REGEXP,
                'DepartureTerminal' => self::FIELD_ALLOWED,
				'DepName' => false,
				'DepDate' => 'date',
				'ArrCode' => self::AIRCODE_REGEXP,
				'ArrivalTerminal' => self::FIELD_ALLOWED,
				'ArrName' => false,
				'ArrDate' => 'date',
				'AirlineName' => self::FIELD_ALLOWED,
				'Operator' => self::FIELD_ALLOWED,
				'Aircraft' => self::FIELD_ALLOWED,
				'TraveledMiles' => self::FIELD_ALLOWED,
				'Cabin' => self::FIELD_ALLOWED,
				'BookingClass' => self::FIELD_ALLOWED,
				'PendingUpgradeTo' => self::FIELD_ALLOWED,
				'Seats' => self::FIELD_ALLOWED,
				'Duration' => self::FIELD_ALLOWED,
				'Meal' => self::FIELD_ALLOWED,
				'Smoking' => self::FIELD_ALLOWED,
				'Stops' => self::FIELD_ALLOWED,
                'Status' => self::FIELD_ALLOWED,
                'Cancelled' => self::FIELD_ALLOWED,
			),
			'Kind' => self::FIELD_ALLOWED,
			'Passengers' => self::FIELD_ALLOWED,
			'AccountNumbers' => self::FIELD_ALLOWED,
			'Cancelled' => self::FIELD_ALLOWED,
			'TotalCharge' => 'price',
			'BaseFare' => 'price',
			'Currency' => self::FIELD_ALLOWED,
			'Tax' => 'price',
			'Fees' => self::FIELD_ALLOWED,
			'SpentAwards' => self::FIELD_ALLOWED,
			'EarnedAwards' => self::FIELD_ALLOWED,
			'Status' => self::FIELD_ALLOWED,
			'ReservationDate' => 'date',
			'NoItineraries' => self::FIELD_ALLOWED,
			'TripCategory' => self::FIELD_ALLOWED,
			'KioskCheckinCode' => self::FIELD_ALLOWED,
			'KioskCheckinCodeFormat' => self::FIELD_ALLOWED,
			'ExtProperties' => self::FIELD_ALLOWED,
			'TicketNumbers' => self::FIELD_ALLOWED,
		),
		'B' => array(
			'RecordLocator' => self::CONFNO_REGEXP,
			'TripNumber' => self::FIELD_ALLOWED,
			'TripSegments' => array(
				'AirlineName' => self::FIELD_ALLOWED,
				'DepCode' => self::AIRCODE_REGEXP,
				'DepName' => self::FIELD_ALLOWED,
                'DepartureTerminal' => self::FIELD_ALLOWED,
				'DepAddress' => self::FIELD_ALLOWED,
				'DepDate' => 'date',
				'ArrCode' => self::AIRCODE_REGEXP,
				'ArrName' => self::FIELD_ALLOWED,
                'ArrivalTerminal' => self::FIELD_ALLOWED,
				'ArrDate' => 'date',
				'ArrAddress' => self::FIELD_ALLOWED,
				'Type' => self::FIELD_ALLOWED,
				'TraveledMiles' => self::FIELD_ALLOWED,
				'Cabin' => self::FIELD_ALLOWED,
				'BookingClass' => self::FIELD_ALLOWED,
				'Seats' => self::FIELD_ALLOWED,
				'Duration' => self::FIELD_ALLOWED,
				'Meal' => self::FIELD_ALLOWED,
				'Smoking' => self::FIELD_ALLOWED,
				'Stops' => self::FIELD_ALLOWED,
				'Status' => self::FIELD_ALLOWED,
				'FlightNumber' => self::FIELD_ALLOWED,
				'Vehicle' => self::FIELD_ALLOWED,
			),
			'Kind' => self::FIELD_ALLOWED,
			'Passengers' => self::FIELD_ALLOWED,
			'AccountNumbers' => self::FIELD_ALLOWED,
			'Cancelled' => self::FIELD_ALLOWED,
			'TotalCharge' => 'price',
			'BaseFare' => 'price',
			'Currency' => self::FIELD_ALLOWED,
			'Tax' => 'price',
			'Fees' => self::FIELD_ALLOWED,
			'SpentAwards' => self::FIELD_ALLOWED,
			'EarnedAwards' => self::FIELD_ALLOWED,
			'Status' => self::FIELD_ALLOWED,
			'ReservationDate' => 'date',
			'NoItineraries' => self::FIELD_ALLOWED,
			'TripCategory' => self::FIELD_ALLOWED,
			'KioskCheckinCode' => self::FIELD_ALLOWED,
			'KioskCheckinCodeFormat' => self::FIELD_ALLOWED,
			'ExtProperties' => self::FIELD_ALLOWED,
            'TicketNumbers' => self::FIELD_ALLOWED,
		),
		'C' => array(
			'RecordLocator' => self::FIELD_ALLOWED,
			'TripNumber' => self::FIELD_ALLOWED,
			'TripSegments' => array(
				'DepName' => true,
				'DepDate' => 'date',
				'ArrName' => true,
				'ArrDate' => 'date',
			),
			'Kind' => self::FIELD_ALLOWED,
			'Passengers' => self::FIELD_ALLOWED,
			'AccountNumbers' => self::FIELD_ALLOWED,
			'Cancelled' => self::FIELD_ALLOWED,
			'ShipName' => self::FIELD_ALLOWED,
			'ShipCode' => self::FIELD_ALLOWED,
			'CruiseName' => self::FIELD_ALLOWED,
			'Deck' => self::FIELD_ALLOWED,
			'RoomNumber' => self::FIELD_ALLOWED,
			'RoomClass' => self::FIELD_ALLOWED,
			'Dining' => self::FIELD_ALLOWED,
			'Vendor' => self::FIELD_ALLOWED,
			'Status' => self::FIELD_ALLOWED,
			'TotalCharge' => 'price',
			'BaseFare' => 'price',
			'Currency' => self::FIELD_ALLOWED,
			'Tax' => 'price',
			'SpentAwards' => self::FIELD_ALLOWED,
			'EarnedAwards' => self::FIELD_ALLOWED,
			'ReservationDate' => 'date',
			'NoItineraries' => self::FIELD_ALLOWED,
			'TripCategory' => self::FIELD_ALLOWED,
			'ExtProperties' => self::FIELD_ALLOWED,
            'TicketNumbers' => self::FIELD_ALLOWED,
            'VoyageNumber' => self::FIELD_ALLOWED,
		),
		'R' => array(
			'ConfirmationNumber' => self::CONFNO_REGEXP,
			'TripNumber' => self::FIELD_ALLOWED,
			'Kind' => self::FIELD_ALLOWED,
			'HotelCategory' => self::FIELD_ALLOWED,
			'ConfirmationNumbers' => self::FIELD_ALLOWED,
			'HotelName' => true,
			'2ChainName' => self::FIELD_ALLOWED,
			'CheckInDate' => 'date',
			'CheckOutDate' => 'date',
			'Address' => true,
			'DetailedAddress' => self::FIELD_ALLOWED,
			'Phone' => 'phone',
			'Fax' => 'phone',
			'GuestNames' => self::FIELD_ALLOWED,
			'Guests' => self::FIELD_ALLOWED,
			'Kids' => self::FIELD_ALLOWED,
			'Rooms' => self::FIELD_ALLOWED,
			'Rate' => self::FIELD_ALLOWED,
			'RateType' => self::FIELD_ALLOWED,
			'CancellationPolicy' => self::FIELD_ALLOWED,
			'RoomType' => self::FIELD_ALLOWED,
			'RoomTypeDescription' => self::FIELD_ALLOWED,
			'Cost' => 'price',
			'Taxes' => 'price',
			'Total' => 'price',
			'Currency' => self::FIELD_ALLOWED,
			'SpentAwards' => self::FIELD_ALLOWED,
			'EarnedAwards' => self::FIELD_ALLOWED,
			'AccountNumbers' => self::FIELD_ALLOWED,
			'Status' => self::FIELD_ALLOWED,
			'Cancelled' => self::FIELD_ALLOWED,
			'ReservationDate' => 'date',
			'NoItineraries' => self::FIELD_ALLOWED,
			'ExtProperties' => self::FIELD_ALLOWED,
		),
		'L' => array(
			'Number' => self::CONFNO_REGEXP,
			'TripNumber' => self::FIELD_ALLOWED,
			'Kind' => self::FIELD_ALLOWED,
			'PickupLocation' => true,
			'PickupDatetime' => 'date',
			'DropoffLocation' => true,
			'DropoffDatetime' => 'date',
			'PickupPhone' => 'phone',
			'PickupFax' => 'phone',
			'PickupHours' => self::FIELD_ALLOWED,
			'DropoffPhone' => 'phone',
			'DropoffHours' => self::FIELD_ALLOWED,
			'DropoffFax' => 'phone',
			'RentalCompany' => self::FIELD_ALLOWED,
			'CarType' => self::FIELD_ALLOWED,
			'CarModel' => self::FIELD_ALLOWED,
			'CarImageUrl' => self::FIELD_ALLOWED,
			'RenterName' => self::FIELD_ALLOWED,
			'PromoCode' => self::FIELD_ALLOWED,
			'TotalCharge' => 'price',
			'BaseFare' => 'price',
			'Currency' => self::FIELD_ALLOWED,
			'TotalTaxAmount' => 'price',
			'SpentAwards' => self::FIELD_ALLOWED,
			'EarnedAwards' => self::FIELD_ALLOWED,
			'AccountNumbers' => self::FIELD_ALLOWED,
			'Status' => self::FIELD_ALLOWED,
			'ServiceLevel' => self::FIELD_ALLOWED,
			'Cancelled' => self::FIELD_ALLOWED,
			'PricedEquips' => self::FIELD_ALLOWED,
			'Discount' => self::FIELD_ALLOWED,
			'Discounts' => self::FIELD_ALLOWED,
			'Fees' => self::FIELD_ALLOWED,
			'ReservationDate' => 'date',
			'NoItineraries' => self::FIELD_ALLOWED,
			'ExtProperties' => self::FIELD_ALLOWED,
			'PaymentMethod' => self::FIELD_ALLOWED,
		),
		'E' => array(
			'ConfNo' => self::CONFNO_REGEXP,
			'TripNumber' => self::FIELD_ALLOWED,
			'Kind' => self::FIELD_ALLOWED,
            'EventType' => self::FIELD_ALLOWED,
			'Name' => true,
			'StartDate' => 'date',
			'EndDate' => self::FIELD_ALLOWED,
			'Address' => true,
			'Phone' => 'phone',
			'DinerName' => self::FIELD_ALLOWED,
			'Guests' => self::FIELD_ALLOWED,
			'DressCode' => self::FIELD_ALLOWED,
			'TotalCharge' => 'price',
			'Currency' => self::FIELD_ALLOWED,
			'Tax' => 'price',
			'SpentAwards' => self::FIELD_ALLOWED,
			'EarnedAwards' => self::FIELD_ALLOWED,
			'AccountNumbers' => self::FIELD_ALLOWED,
			'Status' => self::FIELD_ALLOWED,
			'Cancelled' => self::FIELD_ALLOWED,
			'ReservationDate' => 'date',
			'NoItineraries' => self::FIELD_ALLOWED,
			'ExtProperties' => self::FIELD_ALLOWED,
		),
	);

	public function checkItineraries($itineraries, $checkDate = false) {
		global $arProviderKind;
		$result = true;
		$this->http->Log("Check itineraries required fields:", LOG_LEVEL_NORMAL);
		$this->http->LogBlockBegin();
		for ($i = 0; $i < count($itineraries); $i++) {
			$it = null;
			if (isset($itineraries[$i]))
				$it = $itineraries[$i];
			if (empty($it)) {
				$result = false;
				$this->http->Log("empty");
				break;
			}
			if (empty($it['NoItineraries'])) {
				$s = "$i - ";
				$kind = $this->getItineraryKind($it);
				$s .= "Kind: $kind - ";
				$slen = strlen($s);
				if (empty($it['Cancelled'])) {
					if ($kind == 'Unknown')
						$s .= " could not detect Kind. add Kind field to reservation. ";
					if (isset(self::$itinerarySchema[$kind])) {
						$warnings = $this->CheckArrayForFields($it, self::$itinerarySchema[$kind], 2, $checkDate);
						$warnings .= $this->CheckAllowedFields($it, self::$itinerarySchema[$kind], 2);
						$s .= $warnings;
						if ($kind == 'T' && $warnings == '')
							$s .= $this->checkAirTrip($it);
						if ($slen < strlen($s))
							$s = trim($s, ', ') . '. ';
					}
				} else {
					$keyField = array_keys(self::$itinerarySchema[$kind])[0];
					if (empty($it[$keyField]))
						$s .= "missing $keyField for cancelled itinerary, ";
				}
				$itOK = $slen == strlen($s);
				$result = $result && $itOK;
				$this->http->Log($s . ($itOK ? 'OK' : 'FAIL'), $slen == strlen($s) ? LOG_LEVEL_NORMAL : LOG_LEVEL_ERROR);
			}
		}
		$this->http->LogBlockEnd();
		return $result;
	}

	protected function getItineraryKind(array $itinerary) {
		global $arProviderKind;
		if (isset($itinerary['Kind'])) {
			switch (ArrayVal($itinerary, 'TripCategory')) {
				case TRIP_CATEGORY_CRUISE:
					$kind = 'C';
					break;
				case TRIP_CATEGORY_BUS:
				case TRIP_CATEGORY_TRAIN:
				case TRIP_CATEGORY_TRANSFER:
					$kind = 'B';
					break;
				default:
					$kind = $itinerary['Kind'];
			}
		} else {
			if (!class_exists('TQuery'))
				$providerKind = null; // sandbox
			else
				$providerKind = Lookup('Provider', 'Code', 'Kind', "'{$this->AccountFields['ProviderCode']}'");
			$this->logger->notice("Provider kind: " . ArrayVal($arProviderKind, $providerKind, "Unknown"));
			switch ($providerKind) {
				case PROVIDER_KIND_AIRLINE:
					$kind = 'T';
					break;
				case PROVIDER_KIND_HOTEL:
					$kind = 'R';
					break;
				case PROVIDER_KIND_CAR_RENTAL:
					$kind = 'L';
					break;
				case PROVIDER_KIND_DINING:
					$kind = 'E';
					break;
				default:
					$kind = 'Unknown';
			}
		};
		return $kind;
	}

	public static function getAirportOffset($code, $time = null) {
		$name = QueryTopDef('select TimeZoneLocation from AirCode where AirCode = "'.addslashes($code).'"', 'TimeZoneLocation', '');
		try {
			$tz = new DateTimeZone($name);
			$dt = new DateTime();
			if (isset($time)) {
                $dt->setTimestamp($time);
            }

			return $tz->getOffset($dt);
		}
		catch(Exception $e) {}
		$tag = FindGeoTag($code);
		if (!empty($tag['TimeZoneLocation'])) {
            try {
                $tz = new \DateTimeZone($tag['TimeZoneLocation']);
                return $tz->getOffset(new \DateTime());
            }
            catch(\Exception $e) {
                return 0;
            }
        }

		return null;
	}

	protected function correctOvernight($fromLocation, $fromDate, $toLocation, &$toDate, $maxCorrection = 1) {
		$depOffset = $this->getAirportOffset($fromLocation, $fromDate);
		$arrOffset = $this->getAirportOffset($toLocation, $toDate);
		if (isset($depOffset) && isset($arrOffset)) {
			$depDate = intval($fromDate) - $depOffset;
			$arrDate = intval($toDate) - $arrOffset;
			while ($arrDate < $depDate && ($depDate - $arrDate) < ($maxCorrection * SECONDS_PER_DAY)) {
				$toDate += SECONDS_PER_DAY;
				$arrDate += SECONDS_PER_DAY;
			}
		}
	}

	protected function checkPhone($phone) {
		$filteredPhone = preg_replace('#[\-\+\s\(\)/]+#', '', $phone);
		if (preg_match('#^\d{5,}#', $filteredPhone))
			return $phone;
		else
			return null;
	}

	protected function checkAirTrip($it) {
		$result = '';
		foreach ($it['TripSegments'] as $segment) {
			if ($segment['DepCode'] == $segment['ArrCode'] && $segment['DepCode'] != TRIP_CODE_UNKNOWN)
				$result .= 'DepCode equals ArrCode (' . $segment['DepCode'] . '), ';
			if (!empty($segment['DepName']) && !empty($segment['ArrName']) && $segment['DepName'] == $segment['ArrName'] && $segment['ArrCode'] != TRIP_CODE_UNKNOWN)
				$result .= 'DepName equals ArrName (' . $segment['DepName'] . '), ';
			if (empty($segment['AirlineName']) || empty($segment['FlightNumber']) || ($segment['FlightNumber'] === FLIGHT_NUMBER_UNKNOWN && $segment['AirlineName'] === AIRLINE_UNKNOWN)) {
				if ($segment['DepCode'] == TRIP_CODE_UNKNOWN && empty($segment['DepName']))
					$result .= 'DepName required when DepCode == TRIP_CODE_UNKNOWN, ';
				if ($segment['ArrCode'] == TRIP_CODE_UNKNOWN && empty($segment['ArrName']))
					$result .= 'ArrName required when ArrCode == TRIP_CODE_UNKNOWN, ';
			}
		}
		return $result;
	}

	function CheckRequiredFields($checkDate = true) {
		if (is_array($this->Itineraries) && count($this->Itineraries) >= 0)
			$this->checkItineraries($this->Itineraries, $checkDate);
	}

	function CheckAllowedFields($it, $allowed, $maxDepth = 2) {
		$result = "";
		if ($maxDepth <= 0) {
			return $result;
		}
		if (!is_array($it))
			return " not an array";
		foreach ($it as $key => $value) {
			if (!isset($allowed[$key]))
				$result .= "'$key' field is not allowed, ";
			elseif (is_array($allowed[$key])) {
				if (empty($it[$key]))
					$result .= "empty '$key', ";
				else {
					foreach ($it[$key] as $ts) { // each trip segment
						$result .= $this->CheckAllowedFields($ts, $allowed[$key], $maxDepth - 1);
					}
				}
			}
		}
		return $result;
	}

	/**
	 * @param array $it
	 * @param mixed $required
	 * @param int $maxDepth
	 * @param bool $checkDate
	 * @return string - warnings, empty string - no warnings
	 */
	function CheckArrayForFields($it, $required, $maxDepth = 2, $checkDate = true) {
		$result = "";
		if ($maxDepth <= 0) {
			return $result;
		}
		foreach ($required as $key => $type) {
			if ($type === self::FIELD_ALLOWED)
				continue;
			if ($type === true) {
				if (empty($it[$key]))
					$result .= "empty '$key', ";
			} elseif ($type == 'date') {
				if (empty($it[$key]) || !is_numeric($it[$key])) {
					if ($key !== 'ReservationDate')
						$result .= "invalid '$key', ";
				} elseif ($it[$key] !== MISSING_DATE) {
					if ($it[$key] < time() - SECONDS_PER_DAY && $checkDate) {
						if ($key !== 'ReservationDate')
							$result .= "'$key' in past, ";
					}
					if ($it[$key] % 10 > 0) {
						$result .= "'$key' contains seconds - incorrect value, ";
					}
					if ($it[$key] < mktime(0, 0, 0, 1, 1, 1971)) {
						$result .= "'$key' is near 1970";
					}
					// whether the date was created by strtotime(empty . ' 08:34 PM')
					if (ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG
						&& $it[$key] > strtotime('today')
						&& $it[$key] < strtotime('tomorrow')
					) {
						$this->http->Log("'$key' is today, ", LOG_LEVEL_NORMAL);
					}
				}
			} elseif ($type == 'phone') {
				if (isset($it[$key]) && $this->checkPhone($it[$key]) === null) {
					$errorMessage = "Incorrect '$key' value '" . $it[$key] . "'";
					if ($it['Kind'] == 'E') {
						$result = $errorMessage;
					} else {
						$this->http->Log($errorMessage, LOG_LEVEL_NORMAL);
					}
				}
			} elseif ($type == 'price') {
				if (isset($it[$key])) {
					if (!preg_match(self::PRICE_REGEXP, $it[$key])) {
						$errorMessage = "Incorrect '$key' value '" . $it[$key] . "'";
						$result = $errorMessage;
						$this->http->Log($errorMessage, LOG_LEVEL_NORMAL);
					}
					elseif (intval($it[$key]) > 50000000) {
						$errorMessage = "Value {$it[$key]} in field '$key' seems too big";
						$result = $errorMessage;
						$this->http->Log($errorMessage, LOG_LEVEL_NORMAL);
					}
				}
			} elseif (is_string($type) && strpos($type, '#') === 0) {
				if (!isset($it[$key]) || !preg_match($type, trim($it[$key])))
					$result .= "'" . (isset($it[$key]) ? $it[$key] : 'null') . " ($key) should match '$type'";
			} elseif (is_array($type)) {
				if (empty($it[$key]))
					$result .= "empty '$key', ";
				else {
					foreach ($it[$key] as $ts) { // each trip segment
						$result .= $this->CheckArrayForFields($ts, $type, $maxDepth - 1, $checkDate);
					}
				}
			}
		}
		return $result;
	}

	/*
	 * call this method if you are sure, that there are no itineraries on site,
	 * you've parsed 'No active reservations' string or something similar
	 * this will cause engine to delete (hide) all user reservations on awardwallet
	 */
	function ClearItineraries() {
		$this->Properties['NoItineraries'] = array('no itineraries'); // this is array to prevent saving as property
	}

	function ParseItineraries() {
		return array();
	}

	function ProcessStep($step) {
		$this->Step = null;
		$this->ErrorCode = ACCOUNT_ENGINE_ERROR;
		$this->ErrorMessage = "Unknown error";
        DieTrace("Unimplemented step: $step", false);
	}

	function IsLoggedIn() {
		return false;
	}

	function LoadLoginForm() {
		return false;
	}

	function Login() {
		return false;
	}

	function GetRedirectParams($targetURL = null) {
		return array(
			"URL" => $this->http->FormURL,
			"RequestMethod" => "POST",
			"PostValues" => $this->http->Form,
		);
	}

	function UpdateGetRedirectParams(&$arg) {

	}

	function Redirect($targetURL, $targetType = null) {
		$this->InitBrowser();
		if($this->http->driver instanceof SeleniumDriver)
		    throw new ThrottledException(99); // 99 hack for logging autologin selenium warning

        $this->Redirecting = true;
		try {
			$isLoadLoginForm = $this->LoadLoginForm();
		} catch (CheckException $e) {
		    $this->logger->error("CheckException: " . $e->getMessage() . " (" . $e->getCode() . ") at " . $e->getFile() . ":" . $e->getLine());
			$this->ErrorCode = $e->getCode();
			$this->ErrorMessage = $e->getMessage();
			$isLoadLoginForm = false;
		} catch (CheckRetryNeededException $e) {
            $this->logger->warning("CheckRetryNeededException: " . $e->getMessage() . " (" . $e->getCode() . ") at " . $e->getFile() . ":" . $e->getLine());
			$this->ErrorCode = ACCOUNT_ENGINE_ERROR;
			$this->ErrorMessage = 'Unknown error';
			$this->DebugInfo = 'Checker requested retry, but retries are not supported for redirects';
			$isLoadLoginForm = false;
		}
		if (!$isLoadLoginForm) {
            $this->logger->warning("LoadLoginForm returned false, autologin failed");
			return ACCOUNT_PROVIDER_ERROR;
		}
		$args = $this->GetRedirectParams($targetURL);
		if ((count($this->http->Form) > 0 && isset($this->http->FormURL) && $args['RequestMethod'] == 'POST') || ($args['RequestMethod'] == 'GET' && isset($this->http->FormURL)))
			return $args;
		else
			return ACCOUNT_PROVIDER_ERROR;
	}

	function TuneFormFields(&$arFields, $values = null) {

	}

	/**
	 * @return Message[]
	 */
	public function getFormMessages() {
		return [];
	}

	/**
	 * @param array $values array(ID => '', ProviderID => '', ... , FormFields)
	 */
	function SaveForm($values) {

	}

	function SetProperty($Name, $Value) {
		if (in_array($Name, array("AccountExpirationDate")))
            DieTrace("Property $Name should not be set through this method", false);
		if (isset($Value)) {
			if (is_array($Value))
				$this->logger->info("property set - $Name: <pre>"
					. preg_replace(array('/^\s*Array\s*\(/', '/\s*\)\s*$/'), '', print_r($Value, true)) . "</pre>");
			else
                $this->logger->info("property set - $Name: $Value");
			$this->Properties[$Name] = $Value;
			return true;
		}
		return false;
	}

    function SetBalance($Balance)
    {
		if (isset($Balance) && preg_match("#\d#ims", $Balance)) {
            $Balance = trim($Balance);
            $this->logger->info("balance set: {$Balance}");
			$this->Balance = $Balance;
			$this->ErrorCode = ACCOUNT_CHECKED;
			return true;
		}
		else {
            $this->logger->error("failed to set balance: {$Balance}");
        }

		return false;
	}

	function SetBalanceNA() {
        $this->logger->warning("balance set: n/a");
		$this->Balance = null;
		$this->ErrorCode = ACCOUNT_CHECKED;
		return true;
	}

    function SetWarning($message) {
        if (!empty($message)) {
            $this->logger->warning("set Warning: {$message}");
            $this->ErrorCode = ACCOUNT_WARNING;
            $this->ErrorMessage = $message;
            return true;
        }
        return false;
    }

	static function FormatBalance($fields, $properties) {
		return formatFullBalance($fields['Balance'], $fields['ProviderCode'], $fields['BalanceFormat']);
	}

	static function GetStatusParams($arFields, &$title, &$img, &$msg) {

	}

	static function DisplayName($fields) {
		return $fields['DisplayName'];
	}

	function Parse() {

	}

    function AddSubAccount($properties, $logs = false, $notify = false)
    {
        $this->logger->debug("Adding subAccount...");

        if (!empty($properties['Code']) && strripos($properties['Code'], $this->AccountFields['ProviderCode']) === false) {
            $properties['Code'] = $this->AccountFields['ProviderCode'].ucfirst($properties['Code']);
        }

        $this->logger->debug(Strings::cutInMiddle(var_export($properties, true), 4000), ['pre' => true]);
        if (
            empty($properties['Code'])
            || empty($properties['DisplayName'])
            || !array_key_exists('Balance', $properties)
            || $properties['Balance'] === ''
        ) {
            $this->logger->error("wrong subAccount, skip it");
            if ($notify) {
                $this->sendNotification('failed to add subaccount');
            }
            return null;
        }
        if (!isset($this->Properties['SubAccounts'])) {
            $this->Properties['SubAccounts'] = [];
        }
        // skip adding subaccount with same code
        foreach ($this->Properties['SubAccounts'] as $subAccount) {
            if (isset($subAccount['Code']) && $subAccount['Code'] == $properties['Code']) {
                $this->logger->notice("such subAccount already exist");
                return false;
            } // if (isset($subAccount['Code']) && $subAccount['Code'] == $properties['Code'])
        }
        $this->Properties['SubAccounts'][] = $properties;
        $this->logger->debug("subAccount was added successfully");
        if ($logs) {
            $this->logger->info("property set - SubAccounts:");
            $this->logger->info(htmlspecialchars(var_export($this->Properties['SubAccounts'], true)), ['pre' => true]);
        }

        return true;
    }

    function AddDetectedCard($properties, $logs = false, $rewriteInfo = true)
    {
        $this->logger->debug("Adding detectedCard...");

        if (!empty($properties['Code']) && strripos($properties['Code'], $this->AccountFields['ProviderCode']) === false) {
            $properties['Code'] = $this->AccountFields['ProviderCode'].ucfirst($properties['Code']);
        }

        $this->logger->debug(var_export($properties, true), ['pre' => true]);

        if (!isset($this->Properties['DetectedCards'])) {
            $this->Properties['DetectedCards'] = [];
        }

        $subAccounts = [];
        $duplicate = false;

        if (empty($properties['Code']) || empty($properties['DisplayName']) || empty($properties['CardDescription'])) {
            $this->logger->error("wrong detectedCard, skip it");
            return null;
        }

        // skip adding detectedCard with same code
        foreach ($this->Properties['DetectedCards'] as $subAccount) {
            if (isset($subAccount['Code']) && $subAccount['Code'] == $properties['Code']) {
                if ($rewriteInfo) {
                    if ($logs) {
                        $this->logger->debug("detectedCard info was overwritten");
                    }
                    $subAccount['DisplayName'] = $properties['DisplayName'];
                    $subAccount['CardDescription'] = $properties['CardDescription'];
                }// if ($rewriteInfo)
                $duplicate = true;
            }// if (isset($subAccount['Code']) && $subAccount['Code'] == $properties['Code'])
            $subAccounts[] = $subAccount;
        }// foreach($this->Properties['DetectedCards'] as $subAccount)
        $this->Properties['DetectedCards'] = $subAccounts;
        if (!$duplicate) {
            $this->logger->debug("detectedCard was added successfully");
            $this->Properties['DetectedCards'][] = $properties;
        } else {
            $this->logger->notice("such detectedCard already exist");
        }
        if ($logs) {
            $this->logger->info("property set - DetectedCards:");
            $this->logger->info(htmlspecialchars(var_export($this->Properties['DetectedCards'], true)), ['pre' => true]);
        }

        return !$duplicate;
    }

	function FindSubAccount($displayName) {
		foreach ($this->Properties["SubAccounts"] as $subAccount) {
			if (stripos($subAccount['DisplayName'], $displayName) !== false)
				return $subAccount['Balance'];
		}
		return null;
	}

	function FindSubAccountByCode($code) {
		foreach ($this->Properties["SubAccounts"] as $subAccount) {
			if (strcasecmp($subAccount['Code'], $code) == 0)
				return $subAccount;
		}
		return null;
	}

	function SetExpirationDate($date) {
		if ($date < mktime(0, 0, 0, 1, 1, 1990)) {
            $this->sendNotification("invalid expiration date: $date");
        } else {
			$this->Properties["AccountExpirationDate"] = $date;
			$this->logger->info("expiration date set to: " . date("Y-m-d", $date));
		}
	}

	function SetExpirationDateNever() {
        $this->logger->info("expiration date set to never");
		$this->Properties["AccountExpirationDate"] = false;
	}

	function ClearExpirationDate() {
        $this->logger->info("clear the old expiration date");
		$this->Properties["ClearExpirationDate"] = "Y";
	}

	function GetUserField($fieldName) {
		if (!isset($this->userFields['AccountLevel']) || $this->userFields['AccountLevel'] != ACCOUNT_LEVEL_BUSINESS)
			$result = ArrayVal($this->userFields, $fieldName);
		else
			$result = "";
		return $result;
	}

	function GetConfirmationFields() {
		if ($this->VirtuallyThere) {
			return array(
				"RecordLocator" => array(
					"Caption" => "Reservation Code",
					"Type" => "string",
					"Size" => 20,
					"Required" => true,
				),
				"LastName" => array(
					"Caption" => "Last Name",
					"Type" => "string",
					"Size" => 40,
					"Value" => $this->GetUserField('LastName'),
					"Required" => true,
				),
				"Password" => array(
					"Caption" => "E-mail address or the password<br/> provided by your travel arranger",
					"Type" => "string",
					"Size" => 40,
					"Required" => true,
				)
			);
		}
	}

	function ParseHistory($startDate = null) {
		return null;
	}

	function GetHistoryColumns() {
		return null;
	}

	/*
	 * Return array of HistoryColumns keys hidden for users
	 */
	function GetHiddenHistoryColumns() {
		return [];
	}

	function CheckError($message, $errorCode = ACCOUNT_INVALID_PASSWORD) {
		if (isset($message))
			throw new CheckException($message, $errorCode);
	}

	public static function getNoItinerariesArray(): array
    {
        $result = array();
        foreach (array('T', 'R', 'L', 'E') as $kind) {
            $result[] = array(
                'Kind' => strtoupper($kind),
                'NoItineraries' => true,
            );
        }
        return $result;
    }

	function noItinerariesArr() {
	    return self::getNoItinerariesArray();
	}

    /**
     * @param string $imageContent
     * @param null|string|array $expectedFormat
     *
     * @return null|string
     */
    public function recognizeBarcode($imageContent, $expectedFormat = null)
    {
        if (!class_exists('\RobbieP\ZbarQrdecoder\ZbarDecoder')) {
            $this->logger->debug('Decoder class does not exist');
            return null;
        }

        if (strlen($imageContent) > 1024 * 1024 || strlen($imageContent) === 0) {
            return null;
        }

        if (false === file_put_contents($fileName = sprintf('/tmp/barcode-%s-%s-%s', getmygid(), microtime(true), md5($imageContent)), $imageContent)) {
            return null;
        }

        $result = null;
        try {
            $barcodeDecoder = new ZbarDecoder();
            $result = $barcodeDecoder->make($fileName);
        } catch (Exception $e) {
            return null;
        } finally {
            unlink($fileName);
        }

        if (null === $expectedFormat) {
            $expectedFormat = [];
        } else {
            $expectedFormat = (array) $expectedFormat;
        }

        if (
            $result instanceof Result &&
            200 === $result->code &&
            strlen($result->text) > 0 &&
            (count($expectedFormat) === 0 || in_array($result->format, $expectedFormat, true))
        ) {
            return $result->text;
        } else {
            return null;
        }
    }

    /**
     * @param $http
     * @param string $url
     * @param null|string|array $expectedFormat
     *
     * @return null|string
     */
    public function recognizeBarcodeByUrl(HttpBrowser $http, $url, $expectedFormat = null)
    {
        if (!class_exists('\RobbieP\ZbarQrdecoder\ZbarDecoder')) {
            return null;
        }

        $http->GetURL($url);

        if (200 != $http->Response['code']) {
            return null;
        }

        return $this->recognizeBarcode($http->Response['body'], $expectedFormat);
    }

	/**
	 * @static
	 * @return CaptchaRecognizer
	 */
	public function getCaptchaRecognizer($service = self::CAPTCHA_RECOGNIZER_RUCAPTCHA, $logMessages = true) {
		$result = new CaptchaRecognizer(
			function($duration) use($service){
				$context = array_merge(array_intersect_key($this->AccountFields, ['Partner' => true, 'ProviderCode' => true, 'RequestAccountID' => true]), ["service" => ($service == self::CAPTCHA_RECOGNIZER_RUCAPTCHA ? "rucaptcha" : "antigate" ), "Duration" => $duration, "CaptchaIndex" => $this->CaptchaCount]);
				StatLogger::getInstance()->info("captcha", $context);
				$this->CaptchaCount++;
				$this->CaptchaTime += $duration;
			}
		);
        if ($service == self::CAPTCHA_RECOGNIZER_RUCAPTCHA) {
            $result->APIKey = RUCAPTCHA_KEY;
            $result->domain = "rucaptcha.com";
        }
        elseif ($service == self::CAPTCHA_RECOGNIZER_ANTIGATE_API_V2) {
            $result->APIKey = ANTIGATE_KEY;
            $result->domain = "api.anti-captcha.com";
        }
        else {
            $result->APIKey = ANTIGATE_KEY;
        }
		if ($logMessages)
			$result->OnMessage = array($this->logger, "debug");
		return $result;
	}

    /**
     * Return recognized captcha or false
     *
     * @param CaptchaRecognizer $recognizer
     * @param string $url
     * Captcha's url
     * @param string $extension
     * File extension (jpg, gif, png etc.)
     * @param mixed $parameters
     * Advanced Options https://rucaptcha.com/api-rucaptcha
     * @param int $checkAttemptsCount
     * Count of retries
     * @param int $retryTimeout
     * Retry after N second
     * @throws Exception
     *
     * @return string|false
     */
    public function recognizeCaptchaByURL($recognizer, $url, $extension = null, $parameters = [], $checkAttemptsCount = 3, $retryTimeout = 7) {
        try {
            $captcha = trim($recognizer->recognizeUrl($url, $extension, $parameters));
        }
        catch (CaptchaException $e) {
            $this->logger->warning("exception: " . $e->getMessage());
            // Notifications
            if (strstr($e->getMessage(), "ERROR_ZERO_BALANCE")) {
                $this->sendNotification("WARNING! ".$recognizer->domain." - balance is null");
                throw new CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }// if (strstr($e->getMessage(), "ERROR_ZERO_BALANCE"))

            if (strstr($e->getMessage(), "ERROR_ACCOUNT_SUSPENDED")) {
                $this->sendNotification("WARNING! ".$recognizer->domain." - Account suspended. Contact support via tickets for details.");
                throw new CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }// if (strstr($e->getMessage(), "ERROR_ACCOUNT_SUSPENDED"))

            // retries
            if ($e->getMessage() == 'server returned error: ERROR_CAPTCHA_UNSOLVABLE'
                || $e->getMessage() == "timelimit ({$recognizer->RecognizeTimeout}) hit"
                || $e->getMessage() == 'slot not available'
                || $e->getMessage() == 'service not available'
                || $e->getMessage() == 'server returned error: ERROR_IMAGE_TYPE_NOT_SUPPORTED'
                || $e->getMessage() == 'server returned error: ERROR_PROXY_CONNECTION_FAILED'
                || strstr($e->getMessage(), 'CURL returned error: Failed to connect to rucaptcha.com port')
                || strstr($e->getMessage(), 'CURL returned error: Could not resolve host: rucaptcha.com')
                || strstr($e->getMessage(), 'CURL returned error: Recv failure: Connection reset by peer')
                || strstr($e->getMessage(), 'CURL returned error: Connection timed out after ')
                || strstr($e->getMessage(), 'CURL returned error: Operation timed out after 6000'))
                throw new CheckRetryNeededException($checkAttemptsCount, $retryTimeout, self::CAPTCHA_ERROR_MSG);
            if ($e->getMessage() == 'server returned error: ERROR_WRONG_CAPTCHA_ID') {
                $recognizer->reportIncorrectlySolvedCAPTCHA();
                throw new CheckRetryNeededException($checkAttemptsCount, $retryTimeout, self::CAPTCHA_ERROR_MSG);
            }
            // Antigate may change authorization key for security reasons if we did not log in for 180 days.
            if ($e->getMessage() == 'server returned error: ERROR_KEY_DOES_NOT_EXIST') {
                $this->sendNotification("ATTENTION! ".$recognizer->domain." - authorization key not exist or has been changed");
                throw new CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }

            return false;
        }// catch (CaptchaException $e)

        return $captcha;
    }

    /**
     * Return recognized captcha or false
     *
     * @param CaptchaRecognizer $recognizer
     * @param string $file
     * File path of image with captcha
     * @param mixed $parameters
     * Advanced Options https://rucaptcha.com/api-rucaptcha
     * @param int $checkAttemptsCount
     * Count of retries
     * @param int $retryTimeout
     * Retry after N second
     * @throws Exception
     *
     * @return string|false
     */
    public function recognizeCaptcha($recognizer, $file, $parameters = [], $checkAttemptsCount = 3, $retryTimeout = 7) {
        try {
            $captcha = trim($recognizer->recognizeFile($file, $parameters));
        }
        catch (CaptchaException $e) {
            $this->logger->warning("exception: " . $e->getMessage());
            // Notifications
            if (strstr($e->getMessage(), "ERROR_ZERO_BALANCE")) {
                $this->sendNotification("WARNING! ".$recognizer->domain." - balance is null");
                throw new CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }// if (strstr($e->getMessage(), "ERROR_ZERO_BALANCE"))

            if (strstr($e->getMessage(), "ERROR_ACCOUNT_SUSPENDED")) {
                $this->sendNotification("WARNING! ".$recognizer->domain." - Account suspended. Contact support via tickets for details.");
                throw new CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }// if (strstr($e->getMessage(), "ERROR_ACCOUNT_SUSPENDED"))

            // retries
            if ($e->getMessage() == 'server returned error: ERROR_CAPTCHA_UNSOLVABLE'
                || $e->getMessage() == "timelimit ({$recognizer->RecognizeTimeout}) hit"
                || $e->getMessage() == 'slot not available'
                || stristr($e->getMessage(), 'service not available')
                || $e->getMessage() == 'server returned error: ERROR_IMAGE_TYPE_NOT_SUPPORTED'
                || $e->getMessage() == 'server returned error: ERROR_PROXY_CONNECTION_FAILED'
                || strstr($e->getMessage(), 'CURL returned error: Failed to connect to rucaptcha.com port')
                || strstr($e->getMessage(), 'CURL returned error: Could not resolve host: rucaptcha.com')
                || strstr($e->getMessage(), 'CURL returned error: Recv failure: Connection reset by peer')
                || strstr($e->getMessage(), 'CURL returned error: Connection timed out after ')
                || strstr($e->getMessage(), 'CURL returned error: Operation timed out after 6000'))
                throw new CheckRetryNeededException($checkAttemptsCount, $retryTimeout, self::CAPTCHA_ERROR_MSG);
            if ($e->getMessage() == 'server returned error: ERROR_WRONG_CAPTCHA_ID') {
                $recognizer->reportIncorrectlySolvedCAPTCHA();
                throw new CheckRetryNeededException($checkAttemptsCount, $retryTimeout, self::CAPTCHA_ERROR_MSG);
            }
            // Antigate may change authorization key for security reasons if we did not log in for 180 days.
            if ($e->getMessage() == 'server returned error: ERROR_KEY_DOES_NOT_EXIST') {
                $this->sendNotification("ATTENTION! ".$recognizer->domain." - authorization key not exist or has been changed");
                throw new CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }

            return false;
        }// catch (CaptchaException $e)

        // fixed stupid answers
        if ($captcha === '') {
            $recognizer->reportIncorrectlySolvedCAPTCHA();
            throw new CheckRetryNeededException($checkAttemptsCount, $retryTimeout, self::CAPTCHA_ERROR_MSG);
        }

		return $captcha;
	}

    /**
     * Return recognized ReCaptcha v.2 / ReCaptcha v.3 / FunCaptcha / Geetest by RuCaptcha service or false
     *
     * @param CaptchaRecognizer $recognizer
     * @param string $key
     * Public google reCaptcha key
     * @param mixed $parameters
     * Advanced Options:
     * 1 - https://rucaptcha.com/api-rucaptcha
     * 2 - https://rucaptcha.com/newapi-recaptcha
     * 3 - https://rucaptcha.com/api-rucaptcha#solving_funcaptcha_new
     * 4 - https://rucaptcha.com/api-rucaptcha#solving_geetest
     * @param bool $retry
     * Determines the need for retries
     * @param int $checkAttemptsCount
     * Count of retries
     * @param int $retryTimeout
     * Retry after N second
     *
     * @throws Exception
     *
     * @return string|false
     */
    public function recognizeByRuCaptcha($recognizer, $key, $parameters = [], $retry = true, $checkAttemptsCount = 3, $retryTimeout = 7) {
        try {
            if (empty($parameters['userAgent'])) {
                $parameters['userAgent'] = $this->http->getDefaultHeader('User-Agent');
            }
            $captcha = trim($recognizer->recognizeByRuCaptcha($key, $parameters));
        }
        catch (CaptchaException $e) {
            $this->logger->warning("[CaptchaException]: " . $e->getMessage());
            // Notifications
            if (strstr($e->getMessage(), "ERROR_ZERO_BALANCE")) {
                $this->sendNotification("WARNING! ".$recognizer->domain." - balance is null");
                throw new CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }// if (strstr($e->getMessage(), "ERROR_ZERO_BALANCE"))

            if (strstr($e->getMessage(), "ERROR_ACCOUNT_SUSPENDED")) {
                $this->sendNotification("WARNING! ".$recognizer->domain." - Account suspended. Contact support via tickets for details.");
                throw new CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }// if (strstr($e->getMessage(), "ERROR_ACCOUNT_SUSPENDED"))

            // retries
            if (
                $retry == true
                && ($e->getMessage() == 'server returned error: ERROR_CAPTCHA_UNSOLVABLE'
                || $e->getMessage() == "timelimit ({$recognizer->RecognizeTimeout}) hit"
                || $e->getMessage() == 'slot not available'
                || stristr($e->getMessage(), 'service not available')
                || $e->getMessage() == 'server returned error: ERROR_IMAGE_TYPE_NOT_SUPPORTED'
                || $e->getMessage() == 'server returned error: ERROR_PROXY_CONNECTION_FAILED'
                || strstr($e->getMessage(), 'CURL returned error: Failed to connect to rucaptcha.com port')
                || strstr($e->getMessage(), 'CURL returned error: Could not resolve host: rucaptcha.com')
                || strstr($e->getMessage(), 'CURL returned error: Recv failure: Connection reset by peer')
                || strstr($e->getMessage(), 'CURL returned error: Connection timed out after ')
                || strstr($e->getMessage(), 'CURL returned error: Operation timed out after 6000'))
            ) {
                throw new CheckRetryNeededException($checkAttemptsCount, $retryTimeout, self::CAPTCHA_ERROR_MSG);
            }
            if (
                $retry == true
                && $e->getMessage() == 'server returned error: ERROR_WRONG_CAPTCHA_ID'
            ) {
                $recognizer->reportIncorrectlySolvedCAPTCHA();
                throw new CheckRetryNeededException($checkAttemptsCount, $retryTimeout, self::CAPTCHA_ERROR_MSG);
            }
            // Antigate may change authorization key for security reasons if we did not log in for 180 days.
            if ($e->getMessage() == 'server returned error: ERROR_KEY_DOES_NOT_EXIST') {
                $this->sendNotification("ATTENTION! ".$recognizer->domain." - authorization key not exist or has been changed");
                throw new CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }
            return false;
        }// catch (CaptchaException $e)
        // fixed stupid answers
        if (strlen($captcha) < 100) {
            $recognizer->reportIncorrectlySolvedCAPTCHA();
            throw new CheckRetryNeededException($checkAttemptsCount, $retryTimeout, self::CAPTCHA_ERROR_MSG);
        }

		return $captcha;
	}

    /**
     * Return recognized ReCaptcha v.2 / GeetTest / FunCaptcha by Anti-captcha service or false
     *
     * @param CaptchaRecognizer $recognizer
     * @param mixed $parameters
     * Advanced Options: https://anti-captcha.com/apidoc
     * @param bool $retry
     * Determines the need for retries
     * @param int $checkAttemptsCount
     * Count of retries
     * @param int $retryTimeout
     * Retry after N second
     *
     * @throws Exception
     *
     * @return string|false
     */
    public function recognizeAntiCaptcha($recognizer, $parameters = [], $retry = true, $checkAttemptsCount = 3, $retryTimeout = 7) {
        try {
            if (empty($parameters['userAgent'])) {
                $parameters['userAgent'] = $this->http->getDefaultHeader('User-Agent');
            }
            $captcha = $recognizer->recognizeAntiCaptcha($parameters);
        }
        catch (CaptchaException $e) {
            $this->logger->warning("[CaptchaException]: " . $e->getMessage());
            // Notifications
            if (strstr($e->getMessage(), "ERROR_ZERO_BALANCE")) {
                $this->sendNotification("WARNING! ".$recognizer->domain." - balance is null");
                throw new CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }// if (strstr($e->getMessage(), "ERROR_ZERO_BALANCE"))

            if (strstr($e->getMessage(), "ERROR_ACCOUNT_SUSPENDED")) {
                $this->sendNotification("WARNING! ".$recognizer->domain." - Account suspended. Contact support via tickets for details.");
                throw new CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }// if (strstr($e->getMessage(), "ERROR_ACCOUNT_SUSPENDED"))

            // retries
            // https://anticaptcha.atlassian.net/wiki/spaces/API/pages/196679
            if (
                $retry == true
                && ($e->getMessage() == "timelimit ({$recognizer->RecognizeTimeout}) hit"
                || stristr($e->getMessage(), 'ERROR_CAPTCHA_UNSOLVABLE: Captcha could not be solved by 5 different workers')
                || stristr($e->getMessage(), 'ERROR_NO_SLOT_AVAILABLE : No idle workers are available at the moment.')
                || stristr($e->getMessage(), 'ERROR_PROXY_CONNECT_TIMEOUT: Could not connect to proxy related to the task, connection timeout')
                || stristr($e->getMessage(), 'ERROR_PROXY_READ_TIMEOUT: Connection to proxy for task has timed out')
                || stristr($e->getMessage(), 'ERROR_PROXY_CONNECT_REFUSED: Could not connect to proxy related to the task, connection refused')
                || stristr($e->getMessage(), 'ERROR_TOKEN_EXPIRED: Captcha provider reported that additional variable token has expired')
                || stristr($e->getMessage(), 'ERROR_FAILED_LOADING_WIDGET: Could not load captcha provider widget in worker browser. Please try again.')
                || stristr($e->getMessage(), 'ERROR_PROXY_BANNED: Proxy IP is banned by target service')
                || stristr($e->getMessage(), 'ERROR_RECAPTCHA_INVALID_SITEKEY: Recaptcha server reported that site key is invalid')// lanpass bug fix
                || $e->getMessage() == "No idle workers are available at the moment. Please try a bit later or increase your maximum bid in menu Settings - API Setup in Anti-Captcha Customers Area."
                || $e->getMessage() == "ERROR_ALL_WORKERS_FILTERED: You have reported that all our workers produce incorrect captcha tokens. Try again in 1 hour."
                || strstr($e->getMessage(), "CURL returned error: Connection timed out after 3000")
                || $e->getMessage() == "CURL returned error: OpenSSL SSL_connect: SSL_ERROR_SYSCALL in connection to api.anti-captcha.com:443 "
                )
            ) {
                throw new CheckRetryNeededException($checkAttemptsCount, $retryTimeout, self::CAPTCHA_ERROR_MSG);
            }
            if (
                $retry == true
                && $e->getMessage() == 'server returned error: ERROR_WRONG_CAPTCHA_ID'
            ) {
                $recognizer->reportIncorrectlySolvedCAPTCHA();
                throw new CheckRetryNeededException($checkAttemptsCount, $retryTimeout, self::CAPTCHA_ERROR_MSG);
            }
            // Antigate may change authorization key for security reasons if we did not log in for 180 days.
            if ($e->getMessage() == 'server returned error: ERROR_KEY_DOES_NOT_EXIST') {
                $this->sendNotification("ATTENTION! ".$recognizer->domain." - authorization key not exist or has been changed");
                throw new CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }
            return false;
        }// catch (CaptchaException $e)

        return $captcha;
    }

    /**
     * @deprecated
     * @static
     * @return DeathByCaptchaRecognizer
     */
    public function getDeathbycaptchaRecognizer($captchaID = null, $logMessages = true) {
        $result = new DeathByCaptchaRecognizer();
        $result->username = DEATH_BY_CAPTCHA_USERNAME;
        $result->password = DEATH_BY_CAPTCHA_PASSWORD;
        // Report an incorrectly solved CAPTCHA.
        // Make sure the CAPTCHA was in fact incorrectly solved!
        if ($captchaID)
            $result->reportIncorrectlySolvedCAPTCHA($captchaID);
		if ($logMessages)
			$result->OnMessage = array($this->http, "Log");
		return $result;
	}

	function ModifyDateFormat($date = null, $separator = '/', $logs = false) {
		// logs
		if ($logs) {
			$this->http->LogSplitter();
			$this->logger->debug("Transfer Date In Other Format");
            $this->logger->debug("Date: ".var_export($date, true));
            $this->logger->debug("Separator: ".var_export($separator, true));
		}

		if ($date !== null) {
			$new_date = explode($separator, $date);
			if (isset($new_date[1]))
				$date = $new_date[1].'/'.$new_date[0].'/'.$new_date[2];
			else {
                $this->logger->error("Please set the correct separator!");
				$this->http->LogSplitter();
				return null;
			}
			// logs
			if ($logs) {
                $this->logger->debug("Date In New Format: ".var_export($date, true));
				$this->http->LogSplitter();
			}
			return $date;
		} else {
            $this->logger->error("Date format is not valid!");
			$this->http->LogSplitter();
			return null;
		}
	}

    /**
     * analog Math.round()
     *
     * @return float number like as 0.027567467665098
     */
    protected function random() {
        return (float)rand()/(float)getrandmax();
    }

    /**
     * metering runtime of functions
     *
     * @param mixed|bool $time
     * @return mixed
     */
    protected function getTime($time = false) {
        if ($time === false) {
            $timer = microtime(true);
            $this->logger->debug("[Set timer: true]");
        }
        else {
            $timer = round(microtime(true) - $time, 2);
            $this->logger->debug("[Time of parsing: {$timer}]");
        }
        return $timer;
    }

	/**
	 * @param PlancakeEmailParser $parser
	 * @throws Exception
	 * @return array
	 */
	function ParsePlanEmail(PlancakeEmailParser $parser) {
		throw new Exception(__METHOD__ . " not implemented, override");
	}

	public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email) {
		return $this->ParsePlanEmail($parser);
	}

    /**
     * @param array $fields
     * @throws Exception
     * @return array
     */
    public function ParseRewardAvailability(array $fields)
    {
        throw new Exception(__METHOD__ . " not implemented, override");
    }

    public function getRewardAvailabilitySettings()
    {
        return [
            'supportedCurrencies' => [],
            'supportedDateFlexibility' => 0
        ];
    }

    protected function isMobile() {
		return strpos($this->Device, 'mobile/') === 0;
	}

	/**
	 * @param array $fields
	 * @return mixed
	 * here you can overrider final url using $fields['Login2'] (region), etc
	 */
	function GetExtensionFinalURL(array $fields) {
		return $fields['LoginURL'];
	}

	protected function UseBrowserClass($class) {
		if (get_class($this->http) == $class)
			return;

		$this->http = new $class($this->LogMode);
		$this->initBrowserSettings();
	}

	protected function initBrowserSettings() {
		$this->http->maxRequests = 500;
		$this->http->TimeLimit = 290;
		$this->logger->refreshHttp($this->http);
	}

	function UseCurlBrowser() {
		if (isset($this->http, $this->http->driver) && get_class($this->http->driver) == 'CurlDriver')
			return;
		$driver = new CurlDriver();
		$this->http = new HttpBrowser($this->LogMode, $driver, $this->httpLogDir);
		$this->initBrowserSettings();
	}

	function isSkinMobile() {
		return $this->Skin == 'mobile';
	}

	protected function Cancel() {
		throw new CancelCheckException('Cancelled');
	}

	protected function isBackgroundCheck() {
		return isset($this->AccountFields['Partner']) && $this->AccountFields['Partner'] == 'awardwallet' && $this->AccountFields['Priority'] < 7;
	}

    protected function isStartedByStaff()
    {
        return $this->Source === 4; // UpdaterEngineInterface::SOURCE_OPERATIONS
    }

    public function setSource(int $source)
    {
        $this->Source = $source;
    }

    public function getSource()
    {
        return $this->Source;
    }

	/**
	 * plugin can force some strategy for specific accounts, through GetCheckStragegy
	 * @return null or one of STRATEGY_CHECK_XXX constants
	 */

	public static function GetCheckStrategy($fields) {
		return null;
	}

	function sendNotification($title = 'Notification', $partner = 'all', $localPassword = true, $body = null)
    {
		if (
            isset($this->AccountFields['Partner']) && in_array($partner, array($this->AccountFields['Partner'], 'all'))
            &&
            ($localPassword === true || ($localPassword === false && !$this->isLocalPassword()))
        ) {
            $this->logger->warning("Notification sent: {$title} {$body}");

            // pack all information into the context
            // formatting will be done in vendor/awardwallet/service/Common/Monolog/Formatter/HtmlFormatter.php::formatNotification

            $accountId = ArrayVal($this->AccountFields, 'RequestAccountID',
                ArrayVal($this->AccountFields, 'AccountID', null));

            $userId = $this->AccountFields['UserID'] ?? null;

            $prevTitle = isset($this->AccountFields['Method']) ? $this->AccountFields['Method'] . ' | ' : '';
            $context = [
                'Title' => $prevTitle . $this->AccountFields['ProviderCode'] . ". " . $title,
                'Method' => $this->AccountFields['Method'] ?? null,
                'Partner' => $this->AccountFields['Partner'],
                'Provider' => $this->AccountFields['ProviderCode'],
                'UserID' => $userId,
                'AccountID' => $accountId,
                'Login' => !empty(ArrayVal($this->AccountFields, 'Login')) ? preg_replace('#^\d{12}(\d{4})$#ims', '...$1',
                    ArrayVal($this->AccountFields, 'Login')) : null,
                'Body' => $body,
                'RequestID' => $this->AccountFields['RequestID'] ?? null,
                'DevNotification' => true,
                'extra' => [],
            ];

            if (isset($this->AccountFields['ConfFields'])) {
                foreach ($this->AccountFields['ConfFields'] as $field => $value) {
                    $context[$field] = $value;
                }
                if (isset($this->AccountFields['ConfFields']['ConfNo'])) {
                    $context['extra']['ConfNo'] = [
                        'value' => 'https://awardwallet.com/manager/loyalty/logs?ConfNo=' . $this->AccountFields['ConfFields']['ConfNo']
                    ];
                }
            }

            if ($this->AccountFields['Partner'] === "awardwallet") {
                $impersonateUrl = "https://awardwallet.com/manager/impersonate?UserID={$userId}&AutoSubmit&AwPlus=1";
                if ($accountId !== null) {
                    $context['extra']['AccountID'] = [
                        "value" => "https://awardwallet.com/manager/loyalty/logs?AccountID={$accountId}&Partner=awardwallet"
                    ];
                    $impersonateUrl .= "&Goto=" . urlencode("/account/list/?account={$accountId}");
                    $context['extra']['Login'] = [
                        "value" => "https://awardwallet.com/manager/passwordVault/requestPassword.php?ID={$accountId}"
                    ];
                }

                $context['extra']['UserID'] = ["value" => $impersonateUrl];
            }

            if (isset($this->AccountFields['RequestID'])) {
                $context['extra']['RequestID'] = [
                    "value" => "https://awardwallet.com/manager/loyalty/logs?Method={$this->AccountFields['Method']}&RequestID={$this->AccountFields['RequestID']}&Partner={$this->AccountFields['Partner']}",
                    "last" => "https://awardwallet.com/manager/loyalty/logs?Method={$this->AccountFields['Method']}&RequestID={$this->AccountFields['RequestID']}&Partner={$this->AccountFields['Partner']}&ShowLatest=1",
                ];
            }

            try {
                if ($this->globalLogger !== null) {
                    $this->globalLogger->alert("dev notification", $context);
                }
            }
            catch (Swift_TransportException| ErrorException $e) {
                $this->logger->error("Error while sending notification: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . ". Will retry once");
                try {
                    $this->globalLogger->alert("dev notification", $context);
                }
                catch (Swift_TransportException| ErrorException $e) {
                    $this->logger->error("Repeated error while sending notification: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . ". Ignore.");
                }
            }
        } else {
		    // for local debug
            $this->logger->warning("Notification should be sent: {$title} {$body}");
        }
	}

	/**
	 * this function should recognize, is this email message belongs to this parser, using message headers
	 * this function is called first in email recongnition sequence
	 * @param array $headers
	 * @return bool
	 */
	public function detectEmailByHeaders(array $headers) {
		return false; // override
	}

	/**
	 * this function should recognize, is this email message belongs to this parser, using message body
	 * this function is called second in email recognition sequence, only if detectEmailByHeaders fails
	 * detection by body is cpu-intensive on large messages, try to use detectEmailByHeaders in most cases
	 * try to use stripos instead of preg_match for simple searches - it is significantly faster
	 * @param PlancakeEmailParser $parser
	 * @return bool
	 */
	public function detectEmailByBody(PlancakeEmailParser $parser) {
		return false; // override
	}

	/**
	 * @param string $from - email address
	 * @return bool - whether message comes from provider
	 * @throws Exception
	 */
	public function detectEmailFromProvider($from) {
		return false; // override
	}

	function getTimestamp($date, $maxRemoval = 5, $currentRemoval = 1) {
		$result = false;
		try {
			$obj = new DateTime($date);
			$result = $obj->getTimestamp();
		} catch (Exception $e) {
			if ($currentRemoval <= $maxRemoval) {
				if (preg_match("/at position (\d+)/i", $e->getMessage(), $matches)) {
					$_date = substr($date, 0, $matches[1]) . preg_replace("/^([^\W]+)/i", "", substr($date, $matches[1]));
					$result = $this->getTimestamp($_date, $maxRemoval, ++$currentRemoval);
				}
			}
		}
		return $result;
	}
    
    public static function getRASearchLinks(): array {
        // FE: ['https://searchmain.com'=>'main','https://searchmain.com/partner'=>'partner',];
        return [];
    }
    
	public static function getEmailLanguages() {
		return ["en"];
	}

	public static function getEmailTypesCount() {
		return 1;
	}

    public static function getEmailProviders() {
        return [];
    }

    public static function getEmailCompanies()
    {
        return [];
    }

	static public function getItinerarySchema() {
		return self::$itinerarySchema;
	}

	/**
	 * returns parsed email addresses in usable for imap search format (just "some@address.com" is ok)
	 * @return array
	 */
	public function getCredentialsImapFrom() {
		return [];
	}

	/**
	 * returns parsed email subjects. if element is a string the first symbol is "/" or "#", it is interpreted as regexp, otherwise as substring.
	 *        if it is requested email - regexp form is not allowed, only imap compatible
	 * @return array
	 */
	public function getCredentialsSubject() {
		return [];
	}

	/**
	 * returns array of fields that are parsed by this parser. fields with priorities (!) are returned without exclamation mark
	 * @return array
	 */
	public function getParsedFields() {
		return [];
	}

	/**
	 * parses credentials emails. keys of returned array should match getParsedFields but may exceed them
	 * @param PlancakeEmailParser $parser
	 * @return array
	 */
	public function ParseCredentialsEmail(PlancakeEmailParser $parser) {
		return [];
	}

	/**
	 * returns array of required fields for credentials request on provider site or false if it doesn't request
	 * @return array|bool
	 */
	public function getRetrieveFields() {
		return false;
	}

	/**
	 * requests credentials on provider site. returns true if request is successful, false otherwise
	 * @param $data - array with request data, keys are fields returned by getRetrieveFields
	 * @return bool
	 */
	public function RetrieveCredentials($data) {
		return false;
	}

	protected function isLocalPassword() {
		if (ConfigValue(CONFIG_TRAVEL_PLANS))
			return $this->AccountFields['SavePassword'] == SAVE_PASSWORD_LOCALLY;
		else
			return stripos($this->AccountFields['Options'], 'LocalPassword') !== false;
	}

	/**
	 * Is this provider can send emails from other providers
	 * like this situation: amadeus can forward emails from avis, aplus, etc.
	 * if this returned false - messages recognized as "from provider" will not be fed to other parsers
	 * @return bool
	 */
	public function IsEmailAggregator() {
		return false;
	}

	/**
	 * should we combine Bonus and Miles columns to one
	 * introduced for spg-like providers, when there are no Bonus column on site and we synthesised it
	 * used for compatibility, partners will receive 2 columns, we will show one column on frontend
	 *
	 * for example history row
	 * ["Miles": 0, "Bonus": 200]
	 * will be combined to
	 * ["Miles": 200]
	 * when this option is on
	 */
	public function combineHistoryBonusToMiles(){
		return false;
	}

    /**
     * @param string $subAccountCode
     * @param int $startDate
     */
	public function setHistoryStartDate($subAccountCode, $startDate)
    {
        $this->historyStartDates[$subAccountCode] = $startDate;
    }

    protected function getSubAccountHistoryStartDate($subAccountCode)
    {
        if(!empty($this->historyStartDates[$subAccountCode]))
            return $this->historyStartDates[$subAccountCode];
        else
            return null;
    }

    /**
     * override in parsers to force check through extension
     *
     * @return null | bool
     */
    public static function isClientCheck(\AwardWallet\MainBundle\Entity\Account $account, $isMobile)
    {
        return null;
    }

    /**
     * @param Memcached $memcached
     * @internal
     */
    public function setMemcached(Memcached $memcached){
        $this->memcached = $memcached;
    }

    /**
     * @return Memcached
     * @throws Exception
     */
    protected function getMemcached(){
        if(empty($this->memcached))
            throw new Exception("memcached not set");
        return $this->memcached;
    }

    /**
     * @return array
     */
    public function getAllowHtmlProperties()
    {
        return $this->allowHtmlProperties;
    }
    
    public function getParseMode()
    {
        return $this->parseMode;
    }

    public function getCaptchaTime(){
        return $this->CaptchaTime;
    }

    public function increaseTimeLimit(int $time = 60)
    {
        if(!empty($this->onTimeLimitIncreased))
            call_user_func($this->onTimeLimitIncreased, $time);
    }

    public function getLogDir()
    {
        return $this->httpLogDir;
    }

    private function callTraitMethods($prefix, ...$arguments)
    {
        $reflClass = new ReflectionClass($this);

        do {
            foreach ($reflClass->getTraits() as $reflTrait) {
                foreach ($reflTrait->getMethods() as $reflMethod) {
                    if (strpos($reflMethod->getName(), $prefix) === 0) {
                        call_user_func_array([$this, $reflMethod->getName()], $arguments);
                    }
                }
            }
            $reflClass = $reflClass->getParentClass();
        } while($reflClass instanceof ReflectionClass);
    }

    public function setParseIts(bool $ParseIts)
    {
        $this->ParseIts = $ParseIts;
    }

    /**
     * @throws CheckException
     */
    public function throwProfileUpdateMessageException() {
        throw new CheckException("{$this->AccountFields['DisplayName']} website is asking you to update your profile, until you do so we would not be able to retrieve your account information.", ACCOUNT_PROVIDER_ERROR);
    }

    /**
     * @throws CheckException
     */
    public function throwAcceptTermsMessageException() {
        throw new CheckException("{$this->AccountFields['DisplayName']} website is asking you to accept their new Terms and Conditions, until you do so we would not be able to retrieve your account information.", ACCOUNT_PROVIDER_ERROR);
    }

    private function takeSeleniumScreenshot(\Throwable $e)
    {
        if($this->http->driver instanceof SeleniumDriver) {
            $this->http->driver->keepSession = false;
            if ($this->http->driver->webDriver !== null) {
                $this->logger->info("saving screenshot of webdriver error: " . $e->getMessage(), ['HtmlEncode' => true]);
                try {
                    $this->http->driver->webDriver->takeScreenshot($this->http->LogDir . '/error.png');
                }
                catch (\Exception $exception) {
                    $this->logger->warning("error saving screenshot: " . $e->getMessage(), ['pre' => true]);
                }
            }
        }
    }

    /**
     * Report an correctly / incorrectly solved CAPTCHA.
     *
     * @param $recognizer CaptchaRecognizer
     * @param $success    boolean
     * @throws CaptchaException
     */
    public function captchaReporting($recognizer, $success = true): void
    {
        $this->logger->notice(__METHOD__);
        if (!$recognizer) {
            $this->logger->debug("captcha not found");

            return;
        }

        if ($success === false) {
            $recognizer->reportIncorrectlySolvedCAPTCHA();

            return;
        }

        $recognizer->reportGoodCaptcha();
    }

    private function checkThrottledOnRequest()
    {
        /** @var RequestThrottler $plugin */
        $plugin = $this->http->getPluginRPM();
        if (null === $plugin) {
            return;
        }
        $request = new HttpDriverRequest('https:\\no.matter.what.url.for.run.onRequest');
        $request->proxyAddress = $this->http->getProxyAddress();
        // if no exception onRequest, then reset ErrorReason
        $this->throttlerReason = self::THROTTLER_REASON_START_RPM;
        $plugin->onRequest($request, true);
        $this->throttlerReason = null;
    }

    // check proxy & headers
    public function checkInitBrowserRewardAvailability()
    {
        $wrongData = false;
        if (!$this->http->getProxyAddress() && $this->AccountFields['ParseMode'] === 'awardwallet') {
            if (ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
                $wrongData = true;
            }
            $this->logger->emergency("!!!REWARD AVAILABILITY SHOULD BE ONLY WITH PROXY!!!");

        } /*elseif (strpos($this->http->getProxyLogin(), 'lum-customer-') === false
            && !$this->hasStateWithOneOfKeywords(['netnut-' => 'start-with', 'luminaty-' => 'start-with'])) {
            $this->logger->emergency("CHOOSE ONLY LUMINATI OR NETNUT PROXY");
            $wrongData = true;
        }*/
        foreach ($this->http->getDefaultHeaders() as $key => $value) {
            if (strtolower($key) === 'user-agent' and $value === HttpBrowser::PUBLIC_USER_AGENT) {
                $this->http->setRandomUserAgent(10, true, true, false, true, false);
                continue;
            }
            if (stripos($value, 'awardwallet') !== false) {
                $this->logger->emergency("BAD HEADER (with awardwallet):" . $key . ":" . $value);
                $wrongData = true;
            }
        }
        if ($wrongData) {
            throw new \CheckException('InitBrowser has wrong parameters', ACCOUNT_ENGINE_ERROR);
        }
    }

    private function hasStateWithOneOfKeywords(array $keywords)
    {
        $has = false;
        foreach ($this->State as $key => $value) {
            foreach ($keywords as $keyword => $type) {
                switch ($type) {
                    case 'start-with':
                        if (strpos($key, $keyword) === 0) {
                            $has = true;
                        }
                        break;
                    case 'contains':
                        if (strpos($key, $keyword) !== false) {
                            $has = true;
                        }
                        break;
                    case 'equal':
                        if ($key === $keyword) {
                            $has = true;
                        }
                        break;
                }
            }
        }
        return $has;
    }

    public function getProxyIpFromState()
    {
        return $this->State['proxy-ip'] ?? null;
    }
    
    public static function extractState(string $browserState):array
    {
        if ((strlen($browserState) < self::MAX_BROWSERSTATE_LENGTH)) {
            if (strpos($browserState, 'base64:') === 0) {
                $browserState = base64_decode(substr($browserState, strlen('base64:')));
            }
            $arState = @unserialize($browserState);
            if (is_array($arState)) {
                return ArrayVal($arState, "State", array());
            }
        }
        return [];
    }
}
