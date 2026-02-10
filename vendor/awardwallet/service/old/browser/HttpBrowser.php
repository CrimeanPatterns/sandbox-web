<?php

use AwardWallet\Common\Parsing\Html;

require_once __DIR__.'/HttpDriverRequest.php';
require_once __DIR__.'/HttpDriverResponse.php';
require_once __DIR__.'/CookieManager.php';
require_once __DIR__.'/Redirector.php';
require_once __DIR__.'/../constants.php';

class HttpBrowser implements HttpLoggerInterface
{
    const PUBLIC_USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 (AwardWallet Service. Contact us at awardwallet.com/contact)';
    const PROXY_USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36';
    const FIREFOX_USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:126.0) Gecko/20100101 Firefox/126.0';

    public const BROWSER_VERSION_MIN = 125; // 1 version less than main UA

	const STATE_VERSION = 3;

    /*
	public $ParseForms = true;
    */
	public $Response;
    public $asyncResponses;
	public $RetryCount = 2;
	protected $StartTime;
	public $TimeLimit;
	public $LogMode = "console"; // console, html, dir, nothing
	public $LogDir;
	/* @var $DOM DOMDocument() */
	public $DOM;
	/**
	 * @var $XPath DOMXPath  - xpath object for searching through document
	 */
	public $XPath;
	public $Form = array();
	public $FormURL;
	public $FormContentType;
	public $Inputs = array();
	public $ParseDOM = true;
	public $ParseEncoding = true;
	public $ParseMetaRedirects = true;

	public $LogHeaders = true;
	public $LogXML = false;
	public $LogResponses = true;

	public $Error;
	public $SaveLogs = false;
	public $SSLv3 = false;
	public $setOriginHeader = true;
	public $Step;
	public $FilterHTML = true;
	public $ResponseNumber;
	public $saveScreenshots = false; // Works only for Selenium-based checkers

	public $MultiValuedForms = false;

	public $maxRequests = 0;
	protected $requestCount = 0;

	/**
	 * @var Callable : function($message, $level)
	 */
	public $OnLog;

	/**
	 * @var HttpDriverInterface
	 */
	public $driver;
	/**
	 * @var array
	 */
    protected $defaultHeaders = [
        "Accept"          => "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        "Accept-Language" => "en-us,en;q=0.5",
        "Connection"      => "Keep-Alive",
        "Expect"          => "",
        "User-Agent"      => self::PUBLIC_USER_AGENT,
    ];
	/**
	 * @var string
	 */
	protected $url;
	/**
	 * @var Redirector
	 */
	protected $redirector;
	/**
	 * @var CookieManager
	 */
	protected $cookieManager;
	/**
	 * @var HttpPluginInterface[]
	 */
	protected $plugins = [];
	protected $proxyHost;
	protected $proxyAddress;
	protected $proxyPort;
	protected $proxyType = CURLPROXY_HTTP;
	private $proxyLogin;
	private $proxyPassword;
    private $proxyProvider;
    private $proxyRegion;
    
    // for selenium statistics
    /** @var string */
    public $seleniumServer;
    /** @var string */
    public $seleniumBrowserFamily;
    /** @var string */
    public $seleniumBrowserVersion;

    public $LogBrother = null;
	/**
	 * send only ssl through insecure external proxy
	 * @var bool
	 */
	private $proxtHttpsOnly = false;
	private $hideUserAgent = true;

    /**
     * @var array ["1.2.3.4:3128", "4.5.6.7:3128", ..
     */
	private $proxyList = [];
    /**
     * @var array ["1.2.3.4:3128", "4.5.6.7:3128", ..
     */
	private $usedProxies = [];

	public $userAgent = self::PUBLIC_USER_AGENT;

	private $http2 = false;
    /**
     * @var bool
     */
	private $keepUserAgent = false;

	public function __construct($logMode, $driver, string $logDir = null){
		$this->LogMode = $logMode;
		$this->LogDir = $logDir;
        if ($logMode === "dir" && $logDir !== null && !is_dir($logDir) && !file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }

		$this->DOM = new DOMDocument();

		if($this->LogMode === "console"){
			if( !file_exists( '/tmp/browser' ) )
				mkdir( '/tmp/browser' );
			foreach (glob("/tmp/browser/*.html") as $filename) {
			 	$this->Log("deleting $filename", LOG_LEVEL_NORMAL);
			  unlink($filename);
			}
		}

		$this->ResponseNumber = 0;
		$this->StartTime = time();
		$this->driver = $driver;
		$this->driver->setLogger($this);
		$this->cookieManager = new CookieManager();
		$this->redirector = new Redirector();
		$this->plugins["cookies"] = $this->cookieManager;
		$this->plugins["redirector"] = $this->redirector;
		$this->TimeLimit = PARSER_TIME_LIMIT - 30; // give some time for parent thread
	}

	public function currentUrl(){
        if ($this->driver instanceof SeleniumDriver && !empty($this->driver->webDriver))
            return $this->driver->webDriver->getCurrentURL();

		return $this->url;
	}

	public function setDefaultHeader($name, $value){
	    if (isset($this->LogDir)) {
            $this->Log("set Header: '{$name}' => '{$value}'", LOG_LEVEL_USER);
        }
		$this->defaultHeaders[$name] = $value;
	}

    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
        $this->setDefaultHeader("User-Agent", $this->userAgent);
    }

    public function getDefaultHeader($name) {
		return $this->defaultHeaders[$name] ?? null;
	}

    public function getDefaultHeaders() : array
    {
		return $this->defaultHeaders;
	}

	public function getProxyLogin(){
	    return $this->proxyLogin;
    }

    public function unsetDefaultHeader($name) {
        if (isset($this->LogDir)) {
            $this->Log("remove Header: '{$name}'", LOG_LEVEL_USER);
            if (strtolower($name) == 'referer')
                $this->Log("Header '{$name}' couldn't be removed via this method", LOG_LEVEL_ERROR);
        }
		unset($this->defaultHeaders[$name]);
	}

	public function getQueryParam($name){
		$query = parse_url($this->currentUrl(), PHP_URL_QUERY);
		parse_str($query, $params);
		return $params[$name] ?? null;
	}

    /**
     * @deprecated
     * run time is monitored by watchdog
     */
	function TimeLeft($setError = false){
		$timeRunning = time()-$this->StartTime;
		$timeLeft = $this->TimeLimit - $timeRunning;
		if($timeLeft <= 0){
			if($setError)
				$this->SetError("Timed out, parsing took {$timeRunning} seconds, with limit {$this->TimeLimit}");
		}
		return $timeLeft;
	}

	public function SetEmailBody($html, $filter = false) {
        $html = preg_replace('/<meta[^>]+http-equiv="?Content-Type[^>]*>/i', '', $html);
		$this->SetBody($html, $filter);
	}

	public function SetBody($html, $filter = false){
		if ($filter) {
			$html = mb_convert_encoding($html, 'UTF-8', 'UTF-8'); // remove bugged symbols
			$html = preg_replace('/[\x{0000}-\x{0019}]+/ums', ' ', $html); // remove unicode special chars, like \u0007
			$html = preg_replace("/\p{Mc}/u", ' ', $html); // normalize spaces
			$html = str_ireplace(['&nbsp;', '&#160;'], ' ', $html); // normalize spaces
            $html = str_ireplace(['&zwnj;', html_entity_decode('&zwnj;')], '', $html); // remove zero-widths
			$html = trim(preg_replace("/\s+/u", " ", $html));
		}
		$this->Response['body'] = $html;
		if (isset($this->Response['headers']) && isset($this->Response['headers']['content-type'])) {
            $this->Response['headers']['content-type'] = "text/html";
        }
		$this->parseBody();
	}

    public function SaveResponse($extension = null)
    {
		$this->requestCount++;

        if ($this->driver instanceof SeleniumDriver && $this->driver->webDriver !== null && !isset($this->Response['body'])) {
            $this->Log("[Failed to save body]: set body ''", LOG_LEVEL_ERROR);
            $this->Response['body'] = '';
        }

		$this->Response['original_body'] = $this->Response['body'];
		if(empty($extension))
			$extension = "html";
		if($this->LogResponses && isset($this->Response['body'])) {
            $this->LogFile(sprintf("step%02d." . $extension, $this->ResponseNumber), $this->Response['body']);
        }
		if ($this->saveScreenshots && $this->driver instanceof SeleniumDriver && $this->driver->webDriver !== null) {
            try {
                $this->driver->webDriver->takeScreenshot($this->LogDir . '/' . sprintf("step%02d-screenshot.png",
                    $this->ResponseNumber));
            } catch (
                Facebook\WebDriver\Exception\UnknownErrorException
                | Facebook\WebDriver\Exception\WebDriverException
                | Facebook\WebDriver\Exception\UnknownServerException
                | WebDriverException
                | UnknownServerException
                | ErrorException
                $e
            ) {
                $this->Log("[Failed to save screenshot]: exception - " . (strlen($e->getMessage()) > 300 ? substr($e->getMessage(), 0,
                            297) . '...' : $e->getMessage()), LOG_LEVEL_ERROR);
            }
        }
		$this->ResponseNumber++;
        if (isset($this->LogBrother))
            $this->LogBrother->ResponseNumber = $this->ResponseNumber;
	}

	function CheckResponse() {
		if($this->maxRequests > 0 && $this->requestCount > $this->maxRequests){
			$this->Log("too many requests", LOG_LEVEL_ERROR);
			$this->Response['code'] = 0;
			$this->Response['body'] = 'too many requests, allowed only: '.$this->maxRequests;
			throw new BrowserException("Too many requests");
		}
		$arAllowedCodes = array( 200, 201, 403 );
		if( $this->getMaxRedirects() == 0 ) {
			$arAllowedCodes[] = 301;
			$arAllowedCodes[] = 302;
		}
		if( !in_array( $this->Response["code"], $arAllowedCodes ) ){
			if(!empty($this->Response["code"]))
				$this->SetError("HTTP Code {$this->Response["code"]}");
			else {
				$this->SetError("Network error {$this->Response["errorCode"]} - {$this->Response["errorMessage"]}");
				if((($this->Response['errorCode'] == 56 && stripos($this->Response["errorMessage"], "proxy")) || ($this->Response['errorCode'] == 7 && stripos($this->Response["errorMessage"], $this->proxyAddress)))
				&& !empty($this->proxyAddress) && !empty($this->proxyList)){
					$this->Log("proxy error, trying next proxy in list");
					$proxies = array_diff($this->proxyList, $this->usedProxies);
					if(count($proxies) > 0){
						$this->SetProxy($proxies[array_rand($proxies)], $this->hideUserAgent);
					}
					else
						$this->Log("no proxies in list");
				}
			}
			$this->parseBody();
			$this->LogSplitter();
			return false;
		}
		if(($this->getMaxRedirects() > 0) && (strlen($this->Response['body']) == 0)){
			$this->SetError("empty body");
			$this->parseBody();
			return false;
		}
		$this->SetError(null);
		$this->parseBody();
		$this->LogSplitter();
		return true;
	}

    /**
     * @deprecated use logger instead it
     *
     * @param string $s
     * @param integer|null $level
     * @param bool $htmlEncode
     */
	function Log($s, $level = null, $htmlEncode = true) {
		global $arLogLevel;

		if (is_bool($level)) {
			$htmlEncode = $level;
			$level = null;
		}
		if (is_null($level))
			$level = LOG_LEVEL_USER;
		if(isset($this->OnLog))
			call_user_func($this->OnLog, $s, $level);

		$color = $arLogLevel[$level] ?? 'black';

		switch($this->LogMode){
			case "none":
				break;
			case "console":
				echo $s."\n";
				break;
			case "html":
				if($htmlEncode){
					$s = htmlspecialchars($s);
					$s = nl2br($s);
				}
				$html = ($color == 'black') ? $s : "<span style='color: ".$color.";'>$s</span>";
				echo "$html<br>\n";
				break;
			case "dir":
				if(!file_exists($this->LogDir) || !is_dir($this->LogDir))
					DieTrace("Log directory not found: {$this->LogDir}");
				$r = fopen($this->LogDir."/log.html", "a");
				if($r === false)
					DieTrace("failed to open log file ".$this->LogDir."/log.html");
				if($htmlEncode){
					$s = htmlspecialchars($s);
					$s = nl2br($s);
				}
				$html = ($color == 'black') ? $s : "<span style='color: ".$color.";'>$s</span>";
				fwrite($r, "<span class='time'>" . date("H:i:s") . " </span>$html<br>");
				fclose($r);
				break;
		}
	}

	function SetError( $sError ){
		if ($sError)
			$this->Log("error: ".$sError, LOG_LEVEL_ERROR);
		$this->Error = $sError;
	}

	function InputValue( $sName ){
		$this->Log("looking for input '{$sName}'", LOG_LEVEL_NORMAL);
		$entries = $this->XPath->query("//input[( @type=\"TEXT\" or @type=\"text\" or @type=\"hidden\" or @type=\"HIDDEN\" ) and @name=\"$sName\"][1]/@value");
		if( $entries->length == 0 ){
			$this->Log("not found", LOG_LEVEL_NORMAL);
			return null;
		}
		else{
			$this->Log("found: ".$entries->item(0)->nodeValue, LOG_LEVEL_NORMAL);
			return $entries->item(0)->nodeValue;
		}
	}

	function LogSplitter(){
		switch($this->LogMode){
			case "html":
			case "dir":
				$this->Log("<hr>", LOG_LEVEL_NORMAL, false);
				break;
			default:
				$this->Log("--------------------------------------------------------------------", LOG_LEVEL_NORMAL);
		}
	}

	function BodyContains( $s, $bSetError = true ){
		$this->Log("looking for '$s'", LOG_LEVEL_NORMAL);
		if( strpos( $this->Response["body"], $s ) === false ){
			$this->Log("not found", LOG_LEVEL_NORMAL);
			if( $bSetError )
				$this->SetError("body should contain '$s'");
			return false;
		}
		$this->Log("found", LOG_LEVEL_NORMAL);
		return true;
	}

	function DownloadFile($url, $extension = null, $headers = []){
		$this->ParseDOM = false;
        /*
		$this->ParseForms = false;
        */
		$this->ParseEncoding = false;
		$this->ParseMetaRedirects = false;
		$this->LogResponses = false;
		$this->GetURL($url, $headers);
		$file = "/tmp/captcha-".getmypid()."-".microtime(true);
		if(isset($extension))
			$file .= ".".$extension;
		file_put_contents($file, $this->Response['body']);
		$this->ParseDOM = true;
        /*
		$this->ParseForms = true;
        */
		$this->ParseEncoding = true;
		$this->ParseMetaRedirects = true;
		$this->LogResponses = true;
		$this->Log("downloaded file: ".$file, LOG_LEVEL_NORMAL);
		$this->SaveResponse($extension);
		return $file;
	}

    /**
     * Posting form data
     *
     * @param array $headers
     * @param integer|null $timeout
     * You can increase timeout for very slow sites (exceptional cases)
     *
     * @return bool|null
     */
    function PostForm(array $headers = [], $timeout = null) {
		if(count($this->Form) == 0){
			$this->Log("Form is empty, something wrong", LOG_LEVEL_ERROR);
			return false;
		}
		if(!isset($this->FormURL)){
			$this->Log("Form URL is not set, something wrong", LOG_LEVEL_ERROR);
			return false;
		}
		if($this->FormContentType == 'multipart/form-data')
			$headers['content-type'] = 'multipart/form-data';

        // detect case when there are multiple inputs with same name (amex)
        $multipleValues = false;
		if($this->MultiValuedForms){
			$data = array();
			foreach($this->Form as $key => $value){
				if(isset($this->Inputs[$key]["values"]) && count($this->Inputs[$key]["values"]) > 1){
					$multipleValues = true;
					foreach($this->Inputs[$key]["values"] as $mValue)
						$data[] = urlencode($key)."=".urlencode($mValue);
				}
				else
					$data[] = urlencode($key)."=".urlencode($value);
			}
		}
        if($multipleValues){
            $this->Log("posting form with multiple inputs", LOG_LEVEL_NORMAL);
            return $this->PostURL($this->FormURL, implode("&", $data), $headers, $timeout);
        }
        else
		    return $this->PostURL($this->FormURL, $this->Form, $headers, $timeout);
	}

    /**
     * Get data from the URL
     *
     * @param string $url
     * The URL of requested page
     * @param array $headers
     * @param integer|null $timeout
     * You can increase timeout for very slow sites (exceptional cases)
     *
     * @return bool|null
     */
    public function GetURL($url, $headers = [], $timeout = null) {
		return $this->sendRequest('GET', $url, null, $headers, $timeout);
	}

    /**
     * OPTIONS request
     *
     * @param string $url
     * The URL of requested page
     * @param array $headers
     * @param integer|null $timeout
     * You can increase timeout for very slow sites (exceptional cases)
     *
     * @return bool|null
     */
    public function OptionsURL($url, $headers = [], $timeout = null) {
        return $this->sendRequest("OPTIONS", $url, null, $headers, $timeout);
    }

    /**
     * Send Request
     *
     * @param string $method
     * @param string $sURL
     * The URL of requested page
     * @param string|array|null $postData
     * Post data
     * @param array $headers
     * @param integer|null $timeout
     * You can increase timeout for very slow sites (exceptional cases)
     *
     * @return bool|null
     */
    protected function sendRequest($method, $sURL, $postData = null, $headers = [], $timeout = null) {
		if(empty($sURL)){
			$this->SetBody("Invalid request, empty URL");
			return false;
		}
		if(!$this->driver->isStarted())
			$this->driver->start($this->GetProxy(), $this->proxyLogin, $this->proxyPassword, $this->userAgent);
        if (isset($this->LogBrother) && empty($this->LogBrother->getSeleniumServer())) {
            $this->LogBrother->setSeleniumServer($this->getSeleniumServer());
            $this->LogBrother->setSeleniumBrowserFamily($this->getSeleniumBrowserFamily());
            $this->LogBrother->setSeleniumBrowserVersion($this->getSeleniumBrowserVersion());
        }
        $result = false;
		for($n = 0; $n <= $this->RetryCount; $n++){
			if($n > 0){
				$this->setDefaultHeader("connection", "Close");
				$this->Log("retry $n", LOG_LEVEL_ERROR);
				sleep(3);
			}
            /*
			if($this->ParseForms)
				$this->FormURL = $sURL;
            */
            $headers = $this->prepareHeaders($method, $sURL, $headers);

			$request = new HttpDriverRequest($sURL, $method, $postData, array_merge($this->defaultHeaders, $headers), $timeout);

			while(!empty($request)){
			    $this->prepareRequest($request, $timeout);

                $startTime = microtime(true);
                $response = $this->driver->request($request);
                $response->request = $request;

                $this->processResponse($response, $startTime);

                $request = null;
                foreach($this->plugins as $plugin){
                    $request = $plugin->onResponse($response);
                    if(!empty($request))
                        break;
                }
                $this->Response = [
                    'body' => $response->body,
                    'headers' => $response->headers,
                    'code' => $response->httpCode,
                    'errorCode' => $response->errorCode,
                    'errorMessage' => $response->errorMessage,
                    'rawHeaders' => $response->rawHeaders,
                ];
                $this->SaveResponse();
			}
			$result = $this->CheckResponse();
			if ($result || $this->Response['code'] == 404)
				break;
		}
		return $result;
	}

    /**
     * Send async Request
     *
     * @param array $options
     * Example:
     *  [
     *      [
     *          'method' => string, required - Http method
     *          'sURL' => string, required - The URL of requested page
     *          'postData' => string|array|null - Post data
     *          'headers' => array - Http headers
     *          'timeout' => integer|null - You can increase timeout for very slow sites (exceptional cases)
     *      ], [
     *          ...
     *      ],
     *  ]
     *
     * @return bool|null
     */

    public function sendAsyncRequests(array $options = [])
    {
        if (!($this->driver instanceof CurlDriver)) {
            return false;
        }

        if(!$this->driver->isStarted())
            $this->driver->start($this->GetProxy(), $this->proxyLogin, $this->proxyPassword, $this->userAgent);

        $requests = [];
        foreach ($options as $option) {
            $method = $option['method'];
            $sURL = $option['sURL'];
            $headers = $option['headers'] ?? [];
            $postData = $option['postData'] ?? null;
            $timeout = $option['timeout'] ?? null;

            if(empty($sURL)){
                $this->SetBody("Invalid request, empty URL");
                continue;
            }

            $headers = $this->prepareHeaders($method, $sURL, $headers);

            $request = new HttpDriverRequest($sURL, $method, $postData ?? null, array_merge($this->defaultHeaders, $headers), $timeout);

            $this->prepareRequest($request, $timeout);

            $requests[] = $request;
        }

        $startTime = microtime(true);
        $responses = $this->driver->sendAsyncRequests($requests);

        $this->asyncResponses = [];
        foreach ($responses as $response) {
            $this->processResponse($response, $startTime);

            $this->Response = [
                'body' => $response->body,
                'headers' => $response->headers,
                'code' => $response->httpCode,
                'errorCode' => $response->errorCode,
                'errorMessage' => $response->errorMessage,
                'rawHeaders' => $response->rawHeaders,
            ];
            $this->SaveResponse();
            $this->CheckResponse();
            $this->asyncResponses[] = $this->Response;
        }

        return !empty($this->asyncResponses);
    }

    /**
     * Posting data to the URL
     *
     * @param string $sURL
     * The URL of requested page
     * @param string|array|null $postData
     * Post data
     * @param array $headers
     * @param integer|null $timeout
     * You can increase timeout for very slow sites (exceptional cases)
     *
     * @return bool|null
     */
    function PostURL($sURL, $postData, $headers = [], $timeout = null) {
		return $this->sendRequest('POST', $sURL, $postData, $headers, $timeout);
	}

    /**
     * PUT request
     *
     * @param $sURL
     * @param $postData
     * @param array $headers
     * @param null $timeout
     * @return bool|null
     */
    function PutURL($sURL, $postData, $headers = [], $timeout = null) {
        return $this->sendRequest('PUT', $sURL, $postData, $headers, $timeout);
    }

    /**
     * DELETE request
     *
     * @param $url
     * @param array $headers
     * @param null $timeout
     * @return bool|null
     */
    function DeleteURL($url, $headers = [], $timeout = null)
    {
        return $this->sendRequest('DELETE', $url, null, $headers, $timeout);
    }

    /*
	function SubmitForm(){
		$this->Log("submitting form to {$this->FormURL}", LOG_LEVEL_NORMAL);
		return $this->PostURL( $this->FormURL, $this->Form );
	}
    */

	function InputExists( $sName ){
		$this->Log("looking for input '$sName'", LOG_LEVEL_NORMAL);
		if( !isset( $this->Form[$sName] ) ){
			$this->SetError("input not found: $sName", LOG_LEVEL_NORMAL);
			return false;
		}
		return true;
	}

	function SetInputValue( $sName, $sValue ){
		$this->Log("setting input $sName to '$sValue'", LOG_LEVEL_NORMAL);
		if(isset($this->Inputs[$sName]['maxlength']) && (strlen($sValue) > $this->Inputs[$sName]['maxlength'])){
			$this->Log("truncating to ".$this->Inputs[$sName]['maxlength'].' chars', LOG_LEVEL_NORMAL);
			if (!defined('DebugProxyClient::UNKNOWN_PASSWORD') || DebugProxyClient::UNKNOWN_PASSWORD != $sValue)
				$sValue = substr($sValue, 0, $this->Inputs[$sName]['maxlength']);
		}
		$this->Form[$sName] = $sValue;
		return true;
	}

	/**
	 * Removing input from Form if it exist
     *
     * @param string $sName
     * Name of input
     *
     * @return bool
	 */

    function unsetInputValue($sName) {
        if ($this->InputExists($sName)) {
            $this->Log("remove input $sName", LOG_LEVEL_NORMAL);
            unset($this->Form[$sName]);

            return false;
        }

        return true;
    }

	function LogFile($fileName, $contents){
		switch($this->LogMode){
			case "html":
				echo "$fileName:<br><div style='border: 1px solid gray; max-height: 300px; width: 95%; overflow: auto;'><pre>".htmlspecialchars($contents).'</pre></div>';
				break;
			case "console":
				$sFileName = "/tmp/browser/$fileName";
                $this->Log("saved $sFileName", LOG_LEVEL_NORMAL, false);
				file_put_contents( $sFileName, $contents );
				break;
			case "dir":
                $this->Log("saved $fileName<!-- url:".$this->currentUrl()." -->", LOG_LEVEL_NORMAL, false);
				if(file_put_contents($this->LogDir."/$fileName", $contents) === false)
					DieTrace("failed to write ".$this->LogDir."/$fileName");
				break;
		}
	}

	function CreateXPath()
    {
        $nErrorLevel = error_reporting(E_ALL ^ E_WARNING);
        if (
            empty($this->Response['body'])
            || isset($this->Response['headers']['content-type'])
            && (stripos($this->Response['headers']['content-type'], 'application/json') !== false
                || stripos($this->Response['headers']['content-type'], 'text/javascript') !== false)
        ) {
            // no sense to create dom model for JSON document or JavaScript
            $this->DOM = new DOMDocument('1.0', 'utf-8');
        } else {
            $this->DOM = Html::tidyDoc($this->Response['body'], $this->FilterHTML, false);
            if ($this->LogMode == 'dir') {
                $filename = $this->LogDir . '/' . sprintf("step%02d-parsed.html", $this->ResponseNumber - 1);
                if (file_put_contents($filename, $this->DOM->saveHTML()) === false)
                    DieTrace("failed to write $filename");
            }
        }
		error_reporting( $nErrorLevel );
		$this->XPath = new DOMXPath( $this->DOM );
	}

	function parseBody(){
		$this->FilterResponse();
		if($this->ParseDOM)
			$this->CreateXPath();
        /*
		if($this->ParseForms)
			$this->ParseForm();
        */
	}

	function LoadFile($file){
		$this->Response['body'] = file_get_contents($file);
		$this->parseBody();
	}

	function FilterResponse(){
		// convert encodings
		if($this->ParseEncoding) {
            $this->Response['body'] = Html::convertHtmlToUtf($this->Response['body'],
                $this->Response['headers'] ?? null);
        }
	}

    function ParseForm($formName = null, $formFilter = null, $requireURL = true, $position = 1)
    {
        // for compatibility
        if (is_int($formFilter)) {
            $this->Log("[ParseForm]: second parameter should be xpath or null", LOG_LEVEL_ERROR);
            $formXpath = $position;
            $position = $formFilter;
            $formFilter = null;
            if (is_string($formXpath)) {
                $formFilter = $formXpath;
            }
        }

		// find inputs
		//file_put_contents("/mnt/projects/awardwallet/lastDOM.xml", $this->DOM->saveXML() );
		$this->Form = array();
		$this->FormContentType = 'application/x-www-form-urlencoded';
		$this->FormURL = null;
		$this->Inputs = array();
		if(!isset($this->XPath))
			return false;
		if (!isset($formFilter)) {
			if(isset($formName))
				$formFilter = "//form[@name = '$formName' or @id = '$formName']";
			else
				$formFilter = "//form";
		}
		if($position > 1)
			$formFilter = "($formFilter)[$position]";
        // find inputs
		$entries = $this->XPath->query("$formFilter//input[( @type=\"TEXT\" or @type=\"text\" or @type=\"hidden\" or @type=\"HIDDEN\" or @type=\"password\" or @type=\"PASSWORD\" )]");
		for( $n = 0; $n < $entries->length; $n++ ){
			$name = $entries->item($n)->getAttribute("name");
			if (!empty($name)) {
				$this->Form[$name] = $entries->item($n)->getAttribute("value");
            	if(!isset($this->Inputs[$name]))
			    	$this->Inputs[$name] = array("values" => array());
            	if (intval($entries->item($n)->getAttribute("maxlength")) > 0)
                	$this->Inputs[$name]['maxlength'] = intval($entries->item($n)->getAttribute("maxlength"));
            	elseif (intval($entries->item($n)->getAttribute("data-max-length")) > 0)
                	$this->Inputs[$name]['maxlength'] = intval($entries->item($n)->getAttribute("data-max-length"));
            // collect values for case when there are multiple inputs with same name (amex)
            	$this->Inputs[$name]['values'][] = $this->Form[$name];
//			if(count($this->Inputs[$name]['values']) > 1)
//				$this->MultiValuedForms = true;
			}
		}
		// find selects
		$selects = $this->XPath->query("$formFilter//select");
		for( $nSelect = 0; $nSelect < $selects->length; $nSelect++ ){
			$select = $selects->item($nSelect);
			$sSelectName = $select->getAttribute("name");
			$entries = $this->XPath->query(".//option", $select);
			$this->Form[$sSelectName] = "";
			$arOptions = array();
			for( $n = 0; $n < $entries->length; $n++ ){
				$sValue = Html::cleanXMLValue($entries->item($n)->getAttribute("value"));
				$sName = Html::cleanXMLValue($entries->item($n)->nodeValue);
				if( !isset( $this->Form[$sSelectName] ) )
					$this->Form[$sSelectName] = $sValue;
				$arOptions[$sValue] = $sName;
				if( $entries->item($n)->hasAttribute("selected") )
					$this->Form[$sSelectName] = $sValue;
			}
			$this->Inputs[$sSelectName]["Options"] = $arOptions;
		}
		// find radio
		$entries = $this->XPath->query("$formFilter//input[( @type='RADIO' or @type='radio' )]");
		for( $n = 0; $n < $entries->length; $n++ ){
			$sName = $entries->item($n)->getAttribute("name");
			$sValue = $entries->item($n)->getAttribute("value");
			if( !isset( $this->Form[$sName] ) )
				$this->Form[$sName] = "";
			if( $entries->item($n)->hasAttribute("checked") )
				$this->Form[$sName] = $sValue;
		}
		// find checkboxes
		$entries = $this->XPath->query("$formFilter//input[( @type=\"CHECKBOX\" or @type=\"checkbox\" )]");
		for( $n = 0; $n < $entries->length; $n++ ){
			$sName = $entries->item($n)->getAttribute("name");
			$sValue = $entries->item($n)->getAttribute("value");
			if( $entries->item($n)->hasAttribute("checked") )
				$this->Form[$sName] = $sValue;
		}
		// find textarea !!! correct this to include form name
		if( preg_match_all( "/<textarea[^>]+name=['\"]([^'\"]+)['\"][^>]*>([^<]*)<\/textarea>/ims", $this->Response['body'], $arMatches, PREG_SET_ORDER ) ){
			foreach ( $arMatches as $arMatch ){
				$this->Form[$arMatch[1]] = $arMatch[2];
			}
		}
		for( $n = 0; $n < $entries->length; $n++ )
			$this->Form[$entries->item($n)->getAttribute("name")] = $entries->item($n)->getAttribute("value");
		// action
		$entries = $this->XPath->query("$formFilter/@action");
		if( $entries->length > 0 )
			$this->FormURL = $entries->item(0)->nodeValue;
		if(!isset($this->FormURL))
			$this->FormURL = "";
		$this->NormalizeURL($this->FormURL);
		// content-type
		$entries = $this->XPath->query("$formFilter/@enctype");
		if( $entries->length > 0 && strtolower($entries->item(0)->nodeValue) == 'multipart/form-data' )
			$this->FormContentType = 'multipart/form-data';

		$result = (count($this->Form) > 0) && (isset($this->FormURL) || !$requireURL);
        if (!$result) {
            if (!empty($formName)) {
                $this->Log("failed to parse form: {$formName}", LOG_LEVEL_NORMAL);
            } elseif (!empty($formFilter)) {
                $this->Log("failed to parse form: {$formFilter}", LOG_LEVEL_NORMAL);
            } else {
                $this->Log("failed to parse form", LOG_LEVEL_NORMAL);
            }
        }

		return $result;
	}

	// load form values from plain, url-encoded text like:
	// name%25x=value%20x
	// name%25y=value%20y
	function SetFormText($text, $delimiter="\n", $decodeName=true, $decodeValue=true){
		$lines = explode($delimiter, $text);
		foreach($lines as $line){
			$line = trim($line);
			if($line == "")
				continue;
			$pos = strpos($line, "=");
			if($pos === false)
				DieTrace("Invalid pair: ".$line);
			// rawurldecode?
			if($decodeName)
				$name = urldecode(substr($line, 0, $pos));
			else
				$name = substr($line, 0, $pos);
			if($decodeValue)
				$value = urldecode(substr($line, $pos + 1));
			else
				$value = substr($line, $pos + 1);
			$this->Form[$name] = $value;
		}
	}

	function LogBlockBegin() {
		$s = "<div style='border: 1px solid #d3d3d3; overflow: auto; padding: 2px;' class='scroll'>";
		switch($this->LogMode){
			case "none":
				break;
			case "html":
				echo "$s\n";
				break;
			case "dir":
				if(!file_exists($this->LogDir) || !is_dir($this->LogDir))
					DieTrace("Log directory not found: {$this->LogDir}");
				$r = fopen($this->LogDir."/log.html", "a");
				if($r === false)
					DieTrace("failed to open log file ".$this->LogDir."/log.html");
				fwrite($r, "$s\n");
				fclose($r);
				break;
		}
	}

	function LogBlockEnd() {
		switch($this->LogMode){
			case "none":
				break;
			case "html":
				echo "</div>\n";
				break;
			case "dir":
				if(!file_exists($this->LogDir) || !is_dir($this->LogDir))
					DieTrace("Log directory not found: {$this->LogDir}");
				$r = fopen($this->LogDir."/log.html", "a");
				if($r === false)
					DieTrace("failed to open log file ".$this->LogDir."/log.html");
				fwrite($r, "</div>\n");
				fclose($r);
				break;
		}
	}

    /**
     * Evaluates the given XPath expression and returns filtered text, or null if not found
     *
     * @param string  $xpath
     * The XPath expression to execute.
     * @param DOMNode $root [optional]
     * The optional contextnode can be specified for
     * doing relative XPath queries. By default, the queries are relative to
     * the root element.
     * @param bool    $allowEmpty [optional] allow empty node
     * @param string  $regexp [optional]
     * The pattern to search for, as a string.
     * @param int     $nodeIndex - return n-th (starting from 0) node from result set, otherwise return first, and require that it is the only node
     *
     * @return string|null
     */
	function FindSingleNode($xpath, $root = null, $allowEmpty = true, $regexp = null, $nodeIndex = null){
		if(!isset($this->XPath))
			return null;
		if(isset($root))
			$nodes = $this->XPath->query($xpath, $root);
		else
			$nodes = $this->XPath->query($xpath);
		if($nodes->length == 0){
			$this->Log("node not found: $xpath", LOG_LEVEL_NORMAL);
			return null;
		}
		if(!isset($nodeIndex) && ($nodes->length != 1)){
			$this->Log("multiple ({$nodes->length}) nodes found: $xpath, expected single node", LOG_LEVEL_NORMAL);
			return null;
		}
		if(isset($nodeIndex) && ($nodeIndex >= $nodes->length)){
			$this->Log("looking for $nodeIndex-nth node, but only {$nodes->length} nodes found: $xpath", LOG_LEVEL_NORMAL);
			return null;
		}
		if(!isset($nodeIndex))
			$nodeIndex = 0;
		$result = Html::cleanXMLValue($nodes->item($nodeIndex)->nodeValue);
		if($regexp != null){
			if(preg_match($regexp, $result, $m)){
				if(count($m) > 1)
					$result = $m[1];
				else
					$result = $m[0];
			}
			else {
				$this->Log("no match: [$result] with [$regexp]", LOG_LEVEL_NORMAL);
				$result = null;
			}
		}
		if(($result == "") && !$allowEmpty){
			$this->Log("node empty: $xpath", LOG_LEVEL_NORMAL);
			return null;
		}
		return $result;
	}
    /**
     * Perform a regular expression match and returns filtered text, or null if not found
     *
     * @param string $regexp
     * @param boolean $searchByBody
     * @param boolean $cleanValue
     * The pattern to search for, as a string.
     * @param string $text [optional]
     * The input string.
     *
     * @return string|null
     */
    function FindPreg($regexp, $searchByBody = true, $text = null, $cleanValue = true)
    {
        $includeText = true;
        if (is_bool($searchByBody) && $searchByBody) {
            $includeText = false;
            $text = $this->Response['body'] ?? null;
        }
        if (preg_match($regexp, $text, $matches)) {
            if (isset($matches[1])) {
                return ($cleanValue == true) ? Html::cleanXMLValue($matches[1]) : $matches[1];
            } else {
                return ($cleanValue == true) ? Html::cleanXMLValue($matches[0]) : $matches[0];
            }
        } else {
            if ($includeText) {
                $this->Log("no match: [$text] with [$regexp]", LOG_LEVEL_NORMAL);
            } else {
                $this->Log("regexp not found: ".$regexp, LOG_LEVEL_NORMAL);
            }

            return null;
        }
    }
    /**
     * Perform a global regular expression match and returns array of filtered values, or empty array if not found
     *
     * @param string $regexp
     * The pattern to search for, as a string.
     * @param string $text
     * The input string.
     * @param int $flags [optional]
     * Can be a combination of the following flags (note that it doesn't make
     * sense to use PREG_PATTERN_ORDER together with PREG_SET_ORDER) PREG_PATTERN_ORDER
     *
     * Orders results so that $matches[0] is an array of full
     * pattern matches, $matches[1] is an array of strings matched by
     * the first parenthesized subpattern, and so on.
     *
     * @param bool $removeDuplicates
     * Remove duplicates from results
     * @param bool $logs
     * Show all results in logs
     *
     * @return array
     */
    function FindPregAll($regexp, $text = null, $flags = PREG_PATTERN_ORDER, $removeDuplicates = false, $logs = true)
    {
        if (is_null($text)) {
            $text = $this->Response['body'] ?? null;
        }

        if (preg_match_all($regexp, $text, $matches, $flags)) {
            if ($flags == PREG_SET_ORDER) {
                // remove duplicates
                if ($removeDuplicates) {
                    $matches = array_map("unserialize", array_unique( array_map("serialize", $matches) ));
                    $this->Log("Total " . count($matches) . " unique nodes were found, regexp: $regexp", LOG_LEVEL_NORMAL);
                }
                else
                    $this->Log("Total " . count($matches) . " nodes were found, regexp: $regexp", LOG_LEVEL_NORMAL);
                if ($logs)
                    $this->Log("<pre>".var_export($matches, true)."</pre>", false);
                return $matches;
            }
            if (isset($matches[1]))
                $matchesCleaned = $matches[1];
            else
                $matchesCleaned = $matches[0];
            foreach ($matchesCleaned as &$m)
                $m = Html::cleanXMLValue($m);
            $this->Log("Total " . count($matchesCleaned) . " nodes were found, regexp: $regexp", LOG_LEVEL_NORMAL);
            return $matchesCleaned;
        } else {
            if ($text == $this->Response['body'])
                $this->Log("regexp not found: " . $regexp, LOG_LEVEL_NORMAL);
            else
                $this->Log("no match: [$text] with [$regexp]", LOG_LEVEL_NORMAL);
            return [];
        }
    }
    /**
     * Evaluates the given XPath expression and returns array of filtered text, or empty array if not found
     *
     * @param string  $xpath
     * The XPath expression to execute.
     * @param DOMNode $root [optional]
     * The optional contextnode can be specified for
     * doing relative XPath queries. By default, the queries are relative to
     * the root element.
     * @param string  $regexp [optional]
     * The pattern to search for, as a string.
     *
	 * @return array
	 */
	function FindNodes($xpath, $root = null, $regexp = null){
		if(isset($root))
			$nodes = $this->XPath->query($xpath, $root);
		else
			$nodes = $this->XPath->query($xpath);
		$result = array();
		if($nodes->length > 0){
			for($n = 0; $n < $nodes->length; $n++){
				$nodeResult = Html::cleanXMLValue($nodes->item($n)->nodeValue);
				if($regexp != null){
					if(preg_match($regexp, $nodeResult, $m)){
						if(count($m) > 1)
							$nodeResult = $m[1];
						else
							$nodeResult = $m[0];
					}else{
						$this->Log("no match: [$nodeResult] with [$regexp]", LOG_LEVEL_NORMAL);
						$nodeResult = null;
					}
				}
				$result[] = $nodeResult;
			}
			$this->Log("found ".$nodes->length." nodes: $xpath", LOG_LEVEL_NORMAL);
		}
		else
			$this->Log("nodes not found: $xpath", LOG_LEVEL_NORMAL);
		return $result;
	}
    /**
     * Evaluates the given XPath expression and returns filtered text, or null if not found
     *
     * @param string  $xpath
     * The XPath expression to execute.
     * @param string  $regexp [optional]
     * The pattern to search for, as a string.
     * @param DOMNode $root [optional]
     * The optional contextnode can be specified for
     * doing relative XPath queries. By default, the queries are relative to
     * the root element.
     *
     * @return string the HTML, or null if an error occurred.
     */
    function FindHTMLByXpath($xpath, $regexp = null, $root = null) {
        if (!isset($this->XPath))
            return null;

        if (isset($root))
            $nodes = $this->XPath->query($xpath, $root);
        else
            $nodes = $this->XPath->query($xpath);
        if ($nodes->length > 0) {
            $tmp_doc = new DOMDocument();
            for ($z = 0; $z < $nodes->length; $z++) {
                $tmp_doc->appendChild($tmp_doc->importNode($nodes->item($z),true));
            }
            $result = $tmp_doc->saveHTML();
            if ($regexp != null){
                if (preg_match($regexp, $result, $m)){
                    if (count($m) > 1)
                        $result = $m[1];
                    else
                        $result = $m[0];
                }
                else {
                    $this->Log("no match: [$result] with [$regexp]", LOG_LEVEL_NORMAL);
                    $result = null;
                }
            }
            return $result;
        } else {
            $this->Log("node not found: $xpath", LOG_LEVEL_NORMAL);
            return null;
        }
    }

    function LiveDebug() {
		if(php_sapi_name() == 'cli')
			return;
        $oldLogMode = $this->LogMode;
        $this->LogMode = 'html';

        $uuid = uniqid();

        $body = base64_encode($this->Response['body']);

$html = <<<HTML
<div style="margin-right: 15px;">
<form id="awardwallet_livedebug_form_{$uuid}" action="/" method="post">
    <input type="hidden" name="body" value="{$body}">
    <table style="width: 100%;">
    <tr><td width=10>XPath:</td><td><input type="text" name="xpath" style="width: 100%;" value=''></td></tr>
    <tr><td width=10>RegExp:</td><td><input type="text" name="regexp" style="width: 100%;" value=''></td></tr>
    <tr><td colspan="2"><input type="submit"></td></tr>
    </table>
    <div style="border: 1px solid black;">
        <pre style="margin: 0;" class="awardwallet_livedebug_result">Ready</pre>
    </div>
</form>
<!--<script type="text/javascript" src="/lib/3dParty/jquery/jq.js"></script>-->
<script type="text/javascript">
    $(function () {
        var getValues = function () {
            console.log('awardwallet_livedebug get values');
            try {
                var data = JSON.parse(localStorage.getItem('awardwallet_livedebug'));
                var form = $('#awardwallet_livedebug_form_{$uuid}');
                form.find('input[name=xpath]').val(data.xpath);
                form.find('input[name=regexp]').val(data.regexp);
            } catch (e) {}
        };
        var setValues = function () {
            console.log('awardwallet_livedebug set values');
            var form = $('#awardwallet_livedebug_form_{$uuid}');
            var data = {
                xpath: form.find('input[name=xpath]').val(),
                regexp: form.find('input[name=regexp]').val()
            };
            localStorage.setItem('awardwallet_livedebug', JSON.stringify(data));
        };
        getValues();
        var form = $('#awardwallet_livedebug_form_{$uuid}');
        form.submit(function (event) {
            event.preventDefault();
            var form = $('#awardwallet_livedebug_form_{$uuid}');
            var data = {
                body: form.find('input[name=body]').val(),
                xpath: form.find('input[name=xpath]').val(),
                regexp: form.find('input[name=regexp]').val()
            };
            console.log(data);
            $.ajax({
                url: '/admin/livedebug.php',
                type: 'post',
                cache: false,
                data: data,
                dataType: 'text',
                beforeSend: function () {
                    form.find('.awardwallet_livedebug_result').text('Loading...');
                },
                error: function (xhr, status) {
                    form.find('.awardwallet_livedebug_result').text('Error! '+status);
                },
                success: function (data) {
                    form.find('.awardwallet_livedebug_result').text(data);
                    setValues();
                }
            });
        });
        $(window).bind('storage', function () {
            getValues();
            var form = $('#awardwallet_livedebug_form_{$uuid}');
            form.submit();
        });
        console.log('awardwallet_livedebug {$uuid} ready');
    });
</script>
</div>
HTML;
        $this->Log($html, false);

        $this->LogMode = $oldLogMode;
    }

	// remove noscript tags
	function removeTag($body, $tag){
		$p = 0;
		do{
			$p = stripos($body, "<".$tag, $p);
			if($p === false)
				$p = strlen($body);
			else{
				$endPos =  stripos($body, "</".$tag, $p);
				if($endPos === false)
					$p++;
				else{
					$closePos = strpos($body, ">", $endPos);
					if($closePos === false)
						$closePos = $endPos + strlen("</".$tag);
					else
						$closePos++;
					$body = substr($body, 0, $p) . substr($body, $closePos);
				}
			}
		}while($p < strlen($body));
		return $body;
	}

	function CreateLogMessage(){
		global $sPath;
		require_once( "$sPath/lib/htmlMimeMail5/htmlMimeMail5.php" );
		$mail = new htmlMimeMail5();
		$files = glob($this->LogDir."/step*.html");
		foreach($files as $file)
			$mail->addAttachment(new fileAttachment($file));
		foreach(explode("\n", EMAIL_HEADERS) as $header){
			$pair = explode(":", $header);
			$mail->setHeader(trim($pair[0]), trim($pair[1]));
		}
		return $mail;
	}

	/**
	 * creates temporary file, saves last response to it, and returns filename.
	 * don't forget to remove this file
	 * @return string filename
	 */
	function LastResponseFile(){
		$filename = tempnam("/tmp", "file");
		file_put_contents($filename, $this->Response['body']);
		return $filename;
	}

	function NormalizeURL(&$url){
		$currentUrl = $this->currentUrl();
		if(!empty($currentUrl))
			$urlParts = parse_url($currentUrl);
		if(($url == "") && isset($currentUrl)){
			$url = $currentUrl;
		}
        if (!preg_match("/^\//ims", $url) && !preg_match("/^https?:\/\//ims", $url) && isset($currentUrl)) {
            // fix for link like a './FreeSpiritLogin.aspx'
            $url = preg_replace("/^\.\//ims", '', $url);
            // https://1865.langhamhotels.com?aspxerrorpath=/member_profile.aspx
            if (isset($urlParts['path']) && preg_match('/\/$/ims', $currentUrl))
				$url = $urlParts['path'].$url;
			else
                if (isset($urlParts['path']) && preg_match('/^\?/ims', $url))
					$url = $urlParts['path'].$url;
				else{
                    if (!isset($urlParts['path']))
                        $urlParts['path'] = "/";
					$dir = dirname($urlParts['path']);
					if($dir != "/")
						$url = $dir."/".$url;
					else
						$url = "/".$url;
				}
		}
		$url = preg_replace("/\/[^\/]+\/\.\.\//ims", '/', $url);
        if (!preg_match("/^https?:\/\//ims", $url) && isset($currentUrl) && !empty($urlParts['scheme']) && !empty($urlParts['host'])) {
            // fix for link like a '//www.agoda.com/account/editbooking.html?bookingId=WEWE344'
            if (strpos($url, "//{$urlParts['host']}") === 0)
                $url = $urlParts['scheme'].":".$url;
            else
                $url = $urlParts['scheme']."://".$urlParts['host'].$url;
        }
		$url = str_ireplace("&amp;", "&", $url);
	}

	function LogRequestHeaders($method, $url, $postData, $headers){
    	$this->LogSplitter();
    	$this->Log("{$method}: ".$url, LOG_LEVEL_NORMAL);
		if($method == "POST"){
			$this->Log("post fields:", LOG_LEVEL_NORMAL);
			$this->LogBlockBegin();
			if (!empty($postData)) {
				if(is_array($postData))
					foreach ( $postData as $sName => $sValue ){
						if(strlen($sValue) > 250)
							$sValue = substr($sValue, 0, 250)."...";
						$this->Log("{$sName}: $sValue", LOG_LEVEL_HEADERS);
					}
				else
					$this->Log($postData, LOG_LEVEL_HEADERS);
			}
			$this->LogBlockEnd();
		}
		if($this->LogHeaders){
			$this->Log("Request headers:", LOG_LEVEL_NORMAL);
			$this->LogBlockBegin();
			foreach ($headers as $sName => $sValue )
				$this->Log("{$sName}: {$sValue}", LOG_LEVEL_HEADERS);
			$this->LogBlockEnd();
		}
 	}

	function getCurrentHost(){
		$url = $this->currentUrl();
		$domain = null;
		if(!empty($url))
			$urlParts = parse_url($url);
		if (isset($urlParts['host']))
			$domain = $urlParts['host'];
		return $domain;
	}

    function getCurrentScheme(){
		$url = $this->currentUrl();
		$scheme = null;
		if(!empty($url))
			$urlParts = parse_url($url);
		if (isset($urlParts['scheme']))
            $scheme = $urlParts['scheme'];
		return $scheme;
	}

	function getCookieByName($name, $domain = null, $path = "/", $secure = null)
	{
		if (!isset($domain))
			$domain = $this->getCurrentHost();
		if($secure === null){
			$url = $this->currentUrl();
			if(!empty($url)){
				$scheme = parse_url($url, PHP_URL_SCHEME);
				$secure = strcasecmp($scheme, 'https') == 0;
			}
			else
				$secure = false;
		}
		$cookies = $this->GetCookies($domain, $path, $secure);
		if (isset($cookies[$name]))
			return $cookies[$name];

		return null;
	}

	function setCookie($name, $value, $domain = null, $path = "/", $expires = null, $secure = false)
	{
		if (!isset($domain))
			$domain = $this->getCurrentHost();
		$expStamp = strtotime("+1 year");
		if (isset($expires))
			$expStamp = $expires;

		$expires = date("D, d-m-Y H:i:s", $expStamp) . ' GMT';
        $this->Log("Set-Cookie: {$name} => {$value}; domain={$domain}; expires=".date("D, d M Y H:i:s", $expStamp)." GMT; path={$path}", LOG_LEVEL_USER);
		$this->cookieManager->setCookie($name, $value, $domain, $path, $secure, $expires);
	}

	function LogResponseHeaders($response){
		$this->Response = $response;
		$size = 0;
		if(isset($this->Response['headers']) && isset($this->Response['headers']['content-length']))
			$size = intval($this->Response['headers']['content-length']);
		else
			if(isset($this->Response['body']))
				$size += strlen($this->Response['body']);
		if(isset($_SESSION['DownloadedTraffic'])){
			$_SESSION['DownloadedTraffic'] += $size;
			$this->Log("Downloaded: $size bytes", LOG_LEVEL_NORMAL);
		}
		if($this->LogHeaders){
			$this->LogSplitter();
			$this->Log("Response headers:", LOG_LEVEL_NORMAL);
			$this->LogBlockBegin();
			if(isset($this->Response['headers']))
				foreach($this->Response['headers'] as $name => $value)
					$this->Log($name.": ".$value, LOG_LEVEL_HEADERS);
			else
				$this->Log("None", LOG_LEVEL_HEADERS);
			$this->LogBlockEnd();
//			$this->Log("Response cookies:", LOG_LEVEL_NORMAL);
//			$cookies = $this->Request->getResponseCookies();
//			$this->LogBlockBegin();
//			if(is_array($cookies)){
//				foreach ($cookies as $cookie )
//					$this->Log("{$cookie["name"]}: {$cookie["value"]}, domain: {$cookie["domain"]}, path: {$cookie["path"]}, expires: {$cookie["expires"]}", LOG_LEVEL_HEADERS);
//			}
//			else
//				$this->Log(print_r($cookies, true), LOG_LEVEL_HEADERS);
//			$this->LogBlockEnd();
		}
	}

	function GetCookies($host, $path = "/", $secure = false){
		return $this->cookieManager->getCookies($host, $path, $secure);
	}

	// returns browser state as array, cookies and forms
	function GetState(){
		if(!isset($this->Form))
			$this->Form = array();
		if(!isset($this->FormURL))
			$this->FormURL = null;
		$result = array(
			"Version" => self::STATE_VERSION,
			"CookieManager" => $this->cookieManager->getState(),
			"Form" => $this->Form,
			"FormURL" => $this->FormURL,
			"MultiValuedForms" => $this->MultiValuedForms,
			"Inputs" => $this->Inputs,
			"Step" => $this->Step,
			"Driver" => $this->driver->getState(),
			"Engine" => get_class($this),
		);

		if ($this->keepUserAgent) {
            $result["UserAgent"] = $this->getUserAgentForState();
        }

		return $result;
	}

	// restore browser state
	function SetState($arState){
		if(is_array($arState) && ($arState["Version"] == self::STATE_VERSION)){
			$this->Log("loaded state", LOG_LEVEL_NORMAL);
			$this->cookieManager->setState($arState["CookieManager"]);
			$this->Form = $arState["Form"];
			$this->FormURL = $arState["FormURL"];
			$this->MultiValuedForms = $arState["MultiValuedForms"] ?? false;
			$this->Inputs = $arState["Inputs"] ?? [];
			$this->Step = $arState["Step"];
			$this->driver->setState($arState["Driver"]);
			if (isset($arState['UserAgent']) && $this->keepUserAgent) {
			    $this->restoreUserAgent($arState['UserAgent']);
            }
		}
	}

	public function setMaxRedirects($n){
		$this->redirector->maxRedirects = $n;
	}

	public function getMaxRedirects(){
		return $this->redirector->maxRedirects;
	}

	public function CheckState($arState){
		return is_array($arState) && isset($arState["Engine"]) && $arState["Engine"] == get_class($this) && isset($arState['Version']) && $arState['Version'] == self::STATE_VERSION;
	}

    public function cleanup()
    {
		$this->driver->stop();
	}

    public function removeCookies() {
        if ($this->driver instanceof SeleniumDriver && !empty($this->driver->webDriver))
            $this->driver->webDriver->manage()->deleteAllCookies();
        else
		    $this->cookieManager->reset();
	}

	public function getProxyAddress(){
		return $this->proxyAddress;
	}

    public function getProxyProvider()
    {
        return $this->proxyProvider;
    }

    public function getProxyRegion()
    {
        return $this->proxyRegion;
    }

	public function getIpAddress(){
		if(!empty($this->proxyAddress))
			return $this->proxyAddress;
		else
			return $this->detectMyIpAddress();
	}

	private function detectMyIpAddress(){
		$data = @json_decode(curlRequest('http://ipinfo.io'), true);
		if(!empty($data['ip']))
			return $data['ip'];
		else
			return '127.0.0.1';
	}

	/**
	 * @param $address - something like some-proxy.awardwallet.com:80
	 */
	public function SetProxy($address, $hideUserAgent = true, $provider = 'unknown', $region = ''){
		if(empty($address)){
			$this->proxyAddress = null;
			$this->proxyPort = null;
            $this->proxyProvider = null;
            $this->proxyRegion = null;
            if (!$hideUserAgent)
                $this->setUserAgent(self::PUBLIC_USER_AGENT);
		}
		else {
            if ($hideUserAgent && $this->getDefaultHeader("User-Agent") == self::PUBLIC_USER_AGENT)
                $this->setUserAgent(self::PROXY_USER_AGENT);
			$parts = explode(":", $address);
			$ip = gethostbyname($parts[0]);
            if(preg_match("#[a-z]#ims", $ip)){
                if (!function_exists('DieTrace'))
                    throw new Exception("failed dns lookup: " . $ip);
                DieTrace("failed dns lookup: " . $ip, false);
            }
			$this->proxyHost = $parts[0];
//            $this->proxyAddress = strpos($address, 'goproxies') !== false ? 'https://' . $parts[0] : $ip;
            $this->proxyAddress = $ip;
			$this->proxyPort = $parts[1];
            $this->proxyProvider = $provider;
            $this->proxyRegion = $region;
			$this->Log("set proxy $address, proxy ip: ".$ip);
			$this->usedProxies[] = $this->proxyAddress . ":" . $this->proxyPort;
			$this->hideUserAgent = $hideUserAgent;
		}
	}

	public function setProxyAuth($login, $password){
		$this->Log("set proxy auth: " . $login);
		$this->proxyLogin = $login;
		$this->proxyPassword = $password;
	}

	public function GetProxy(){
		if(empty($this->proxyAddress))
			return null;
		else
			return $this->proxyAddress . ':' . $this->proxyPort;
	}

	public function beginDebug(DebugProxyClient $client, $accountId){
		if($this->driver instanceof CurlDriver)
			$this->driver = new CurlDebugProxyDriver($client, $accountId);
	}

	public function UseSSLv3(){
//		$this->Log("deprecated UseSSLc3 call", LOG_LEVEL_ERROR);
		$this->SSLv3 = true;
	}

    public function disableOriginHeader()
    {
        $this->Log("disable Origin header by default");
        $this->setOriginHeader = false;
    }

	/**
	 * @param null|string $url - null - do not check that proxy is live
	 * @param int $timeout
	 * @param null $badRegexp
	 * @return bool|string
     *
     * @deprecated We haven't been using this service since 01 Oct 2016
	 */
	public function setExternalProxy($url = null, $timeout = 5, $badRegexp = null){
        $this->Log("This proxy not available since 01 Oct 2016", LOG_LEVEL_ERROR);
		// http://www.reverseproxies.com/
		$proxies = [
//            "http:62.210.106.172:2546",
//            "http:62.210.106.172:2547",
//            "http:62.210.106.172:2548",
//            "http:62.210.106.172:2549",
//            "http:62.210.106.172:2550",
//            "http:62.210.106.172:2551",
//            "http:62.210.106.172:2552",
//            "http:62.210.106.172:2553",
//            "http:62.210.106.172:2554",
//            "http:62.210.106.172:2555",
			"http:195.154.161.93:5556",
			"http:195.154.161.93:5557",
			"http:195.154.161.93:5558",
			"http:195.154.161.93:5559",
			"http:195.154.161.93:5560",
			"http:195.154.161.93:5561",
			"http:195.154.161.93:5562",
			"http:195.154.161.93:5563",
			"http:195.154.161.93:5564",
			"http:195.154.161.93:5565"
		];
        if (empty($url) || ConfigValue(CONFIG_TRAVEL_PLANS))//refs #12892
			$proxy = str_replace("http:", "", $proxies[array_rand($proxies)]);
		else
			$proxy = $this->findLiveProxy($proxies, $url, $timeout, $badRegexp);
		$this->setProxyList($proxies);
		if(!empty($proxy)){
			$this->SetProxy($proxy);
			$this->proxtHttpsOnly = true;
		}
	}

	public function setProxyList(array $proxies){
		$this->proxyList = $proxies;
	}

	/**
	 * This method will try to load specified url through all of our proxies, and return first successful proxy,
	 * which returns response with not empty body, and http code < 400, and page content doesn't match passed $badRegexp
	 * (if this regexp is set)
	 * @return bool|string - proxy address with port on success, or false if no live proxy found
	 **/
	public function getLiveProxy($url, $timeout = 5, $badRegexp = null, $allowedHttpCodes = [])
	{
		// memcached will contain list of proxies in the format:
		// http:198.199.87.152:80
		// http:198.211.123.63:80
		// http:107.170.206.168:80
		// this list maintained by scheduled ruby script
		$proxies = Cache::getInstance()->get('awardwallet_proxy_list_2');
		if (strlen($proxies) == 0) {
			$this->Log("no proxies found in cache", LOG_LEVEL_ERROR);
			return false;
		}
        $proxies = json_decode($proxies, true);
		$proxies = array_map(function(array $proxy){
		    return $proxy['ip'] . ':' . $proxy['port'];
        }, $proxies);

		return $this->findLiveProxy($proxies, $url, $timeout, $badRegexp, $allowedHttpCodes);
	}

	/**
	 * This method will try to load specified url through all of our proxies, and return first successful proxy,
	 * which returns response with not empty body, and http code < 400, and page content doesn't match passed $badRegexp
	 * (if this regexp is set)
	 * @return bool|string - proxy address with port on success, or false if no live proxy found
	 **/
	private function findLiveProxy(array $proxies, $url, $timeout = 5, $badRegexp = null, $allowedHttpCodes = [])
	{
		$this->Log("proxies list: ".var_export($proxies, true), LOG_LEVEL_DEBUG);
        if ($badRegexp) {
            $this->Log("Filter proxy by regexp: '{$badRegexp}'", LOG_LEVEL_DEBUG);
        }

		$handlers = array();
		$multiHandler = curl_multi_init();

		$result = false;

        $headers = [];
        foreach ($this->defaultHeaders as $key => $value)
            $headers[] = $key . ": " . $value;

		foreach ($proxies as $proxy) {
			if(empty($proxy))
				continue;
			$handler = curl_init($url);
			curl_setopt($handler, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt($handler, CURLOPT_TIMEOUT, $timeout);
			curl_setopt($handler, CURLOPT_HEADER, false);
			curl_setopt($handler, CURLOPT_FAILONERROR, true);
			curl_setopt($handler, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
			curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($handler, CURLOPT_PROXY, str_replace("http:", "", $proxy));
            curl_setopt($handler, CURLOPT_HTTPHEADER, $headers);
			curl_multi_add_handle($multiHandler, $handler);
			$handlers[] = $handler;
		}

		$running = 0;
		do {
			curl_multi_exec($multiHandler, $running);
		} while ($running > 0);

		foreach ($handlers as $index => $handler) {
            $this->Log('['.$index.']: Checking proxy '.$proxies[$index]);
			$returned = curl_multi_getcontent($handler);
			$code = curl_getinfo($handler, CURLINFO_HTTP_CODE);
			$curlErrno = curl_errno($handler);
			if ($badRegexp)
				$pregMatched = preg_match($badRegexp, $returned);
			// <for debugging purposes>
            $logMessage = "HTTP code: {$code} / Curl errno: {$curlErrno}";
            if ($badRegexp) {
                $this->Log("Preg match: ".$pregMatched, LOG_LEVEL_DEBUG);
            }
			$this->LogFile(sprintf('proxy-attempt-%02d.html', $index), $returned);
			// </for debugging purposes>
            if (
                ($returned and !$curlErrno and $code >= 200 and $code < 400 and ($badRegexp === null or !$pregMatched))
                or (!empty($allowedHttpCodes) && in_array($code, $allowedHttpCodes))
            ) {
				$this->Log("Got it! ($logMessage)", LOG_LEVEL_NOTICE);
				$result = $proxies[$index];
                $result = str_replace("http:", "", $result);
				break;
			} else {
				$logMessage = "Blocked ({$logMessage})";
                if ($badRegexp and $pregMatched) {
                    $logMessage .= " / matched 'bad regexp' $badRegexp)";
                } elseif (!$returned) {
                    $logMessage .= " / empty response";
                }
				$this->Log($logMessage, LOG_LEVEL_ERROR);
			}
		}

		foreach ($handlers as $h)
			curl_multi_remove_handle($multiHandler, $h);

		curl_multi_close($multiHandler);

		foreach ($handlers as $h)
			curl_close($h);

		if ($result) {
			$this->Log("Selected proxy: $result");
			$this->proxyList = $proxies;
		}
		else
			$this->Log('No live proxy found', LOG_LEVEL_NOTICE);
		return $result;
	}

    /**
     * Convert Json to object|array with log
     * @param $body - string with json. if NULL then work with body (defaut NULL)
     * @param $log - depth of collapsed (defaut NULL)
     *      old type of value is bool. When FALSE - no log, when TRUE - log with depth = 3
     *      new type of value is int. When 0 - no log, When n(>0) - log with depth = n
     * @param $assoc - When TRUE, returned objects will be converted into associative arrays
     * @return object|array|null
     */
    public function JsonLog(?string $body = null, $log = 3, $assoc = false, $fieldSearch = null)
    {
        if (!isset($log)) {
            $log = 3;
        } elseif (is_bool($log)) {
            $this->Log("[JsonLog]: second parameter couldn't boolean", LOG_LEVEL_ERROR);
            $log = $log ? 3 : 0;
        }

        if (is_null($body)) {

            if (!isset($this->Response['body'])) {
                $this->Log("JsonLog: response body not found", LOG_LEVEL_ERROR);
                return null;
            }

            $body = $this->Response['body'];
        }

        $result = json_decode($body, $assoc);
        if (json_last_error() === JSON_ERROR_UTF8) {
            $body = utf8_encode($body);
            $result = json_decode($body, $assoc);
        }

        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                $message = 'No errors';
                break;
            case JSON_ERROR_DEPTH:
                $message = 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $message = 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $message = 'Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $message = 'Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $message = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $message = 'Unknown error';
                break;
        }

        if ($message != 'No errors') {
            $this->Log("JsonLog: $message", LOG_LEVEL_ERROR);
            // if broken json - no use json-viewer
            if ($log > 0) {
                $this->Log("<pre>" . var_export($result, true) . "</pre>", false);
            }
            return $result;
        }

        if ($log > 0) {
            $body = htmlentities($body, ENT_NOQUOTES);
            $textDepthChecked = (empty($fieldSearch)) ? 'checked=""' : '';
            $textExceptChecked = (!empty($fieldSearch)) ? 'checked=""' : '';
            $textExceptValue = (!empty($fieldSearch)) ? 'value="' . $fieldSearch . '"' : '';

            $htmlBlockInsert = "
    <p class=\"options\">
      Options:
      <label title=\"Generate node as collapsed\">
        <input type=\"checkbox\" data-type=\"collapsed\">Collapse nodes
      </label>
      <label title=\"Generate node as collapsed with set depth\">
        <input type=\"checkbox\" data-type=\"collapsed-depth\" {$textDepthChecked}>
        Collapse nodes with set depth ({$log})
      </label>
      <label title=\"Surround keys with quotes\">
        <input type=\"checkbox\" data-type=\"with-quotes\">Keys with quotes
      </label>
      <label title=\"Generate anchor tags for URL values\">
        <input type=\"checkbox\" data-type=\"with-links\" checked=\"\">
        With Links
      </label>
      <label title=\"Collapse all except\">
        <input type=\"checkbox\" data-type=\"except\" {$textExceptChecked}>
        Except: 
        <input type=\"text\" data-type=\"except-value\" {$textExceptValue}>
      </label>
    <button style=\"cursor:pointer;padding:0;background:none;border:none;color:gray;margin-right: 20px;\" title=\"Copy json to buffer\" onclick=\"textToBuffer($(this).parent().next()); return false;\">&nbsp;❒ Copy</button>
    </p>
    <pre style='display: none;' class='json-data'>{$body}</pre>
    <pre class='json-renderer' data-depth='{$log}'></pre>
            ";
            $this->Log($htmlBlockInsert, LOG_LEVEL_DEBUG,false);
        }

        return $result;
    }

	public function brotherBrowser(HttpBrowser $http) {
	    $this->Log("linking brother browser");
        $this->LogBrother = $http;
        $http->LogBrother = $this;
        $http->seleniumServer = $this->seleniumServer;
        $http->seleniumBrowserFamily = $this->seleniumBrowserFamily;
        $http->seleniumBrowserVersion = $this->seleniumBrowserVersion;
		$http->LogMode = $this->LogMode;
		$http->LogDir = $this->LogDir;
		$http->ResponseNumber = $this->ResponseNumber;
	}

	public function start(){
		$this->driver->start($this->GetProxy(), $this->proxyLogin, $this->proxyPassword, $this->userAgent);
	}

    public function setRandomUserAgent(int $count = null, $firefox = true, $chrome = true, $safari = true, $windows = true, $linux = true, $mobile = false, $macintosh = true)
    {
//	    $agents = $this->getTopUserAgentsFromWeb($count);

        $cache = Cache::getInstance();
        $top_user_agents = $cache->get("top_user_agents");

        $agents = [];

        if (!empty($top_user_agents)) {
            foreach ($top_user_agents as $top_user_agent) {
                $agents[] = $top_user_agent['userAgent'];
            }
        }

        $this->Log("userAgent list: " . (null === $agents ? 0 : count($agents)));

	    if (empty($agents) || $agents < 10) {

            $this->Log("choose agents from hardcoded list", LOG_LEVEL_ERROR);

            $ffVersion = rand(118, 122);
            // yes we will ignore count on this backup variant
            $agents = [
                // firefox
                'Mozilla/5.0 (X11; Linux i586; rv:' . $ffVersion . '.0) Gecko/20100101 Firefox/' . $ffVersion . '.0',
                'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:' . $ffVersion . '.0) Gecko/20100101 Firefox/' . $ffVersion . '.0',
                'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:' . $ffVersion . '.0) Gecko/20100101 Firefox/' . $ffVersion . '.0',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:' . $ffVersion . '.0) Gecko/20100101 Firefox/' . $ffVersion . '.0',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:' . $ffVersion . '.0) Gecko/20100101 Firefox/' . $ffVersion . '.0',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.14; rv:' . $ffVersion . '.0) Gecko/20100101 Firefox/' . $ffVersion . '.0',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:' . $ffVersion . '.0) Gecko/20100101 Firefox/' . $ffVersion . '.0',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.16; rv:' . $ffVersion . '.0) Gecko/20100101 Firefox/' . $ffVersion . '.0',

                // IE
                'Mozilla/5.0 (Windows NT 10.0; Trident/7.0; rv:11.0) like Gecko',
                'Mozilla/5.0 (Windows NT 6.1; Win64; x64; Trident/7.0; rv:11.0) like Gecko',
                'Mozilla/5.0 (compatible, MSIE 11, Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko',
                'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; Touch; rv:11.0) like Gecko',

                // Edge
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.63 Safari/537.36 Edg/93.0.961.38',

                // safari
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Safari/602.1.50',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/603.1.30 (KHTML, like Gecko) Version/10.1 Safari/603.1.30',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_5) AppleWebKit/603.2.4 (KHTML, like Gecko) Version/10.1.1 Safari/603.2.4',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/603.3.8 (KHTML, like Gecko) Version/10.1.2 Safari/603.3.8',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0.2 Safari/605.1.15',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/604.4.7 (KHTML, like Gecko) Version/11.0.2 Safari/604.4.7',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_3) AppleWebKit/604.5.6 (KHTML, like Gecko) Version/11.0.3 Safari/604.5.6',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/11.1 Safari/605.1.15',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/11.1.1 Safari/605.1.15',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/11.1.2 Safari/605.1.15',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0.2 Safari/605.1.15',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0 Safari/605.1.15',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_1) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0.1 Safari/605.1.15',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_2) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0.2 Safari/605.1.15',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.0.3 Safari/605.1.15',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1 Safari/605.1.15',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1.1 Safari/605.1.15',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1.2 Safari/605.1.15',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0 Safari/605.1.15',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.2 Safari/605.1.15',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_2) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.4 Safari/605.1.15',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.5 Safari/605.1.15',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1.1 Safari/605.1.15',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1.2 Safari/605.1.15',
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Safari/605.1.15",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.1 Safari/605.1.15",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.2 Safari/605.1.15",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Safari/605.1.15",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.2 Safari/605.1.15",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Safari/605.1.15",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.1 Safari/605.1.15",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.3 Safari/605.1.15",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.4 Safari/605.1.15",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.5 Safari/605.1.15",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.6.1 Safari/605.1.15",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Safari/605.1.15",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.1 Safari/605.1.15",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.2 Safari/605.1.15",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.3 Safari/605.1.15",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Safari/605.1.15",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1.2 Safari/605.1.15",

                // chrome
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.63 Safari/537.36",
                "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.63 Safari/537.36",
                "Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.63 Safari/537.36",
                "Mozilla/5.0 (Macintosh; Intel Mac OS X 11_5_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.63 Safari/537.36",
                "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/93.0.4577.63 Safari/537.36",
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.77 Safari/537.36",
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36",
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36",
            ];
        }

        if (!$firefox) {
	        $agents = array_filter($agents, function($agent){
                return stripos($agent, 'firefox') === false;
            });
            $this->Log("userAgent list without firefox: " . count($agents));
        }

        if (!$chrome) {
	        $agents = array_filter($agents, function($agent){
                return stripos($agent, 'chrome') === false;
            });
            $this->Log("userAgent list without chrome: " . count($agents));
        }

        if (!$windows) {
	        $agents = array_filter($agents, function($agent){
                return stripos($agent, 'windows') === false;
            });
            $this->Log("userAgent list without windows: " . count($agents));
        }

        if (!$safari) {
            $agents = array_filter($agents, function ($agent) {
                return preg_match("/Version\/[\d.]+ Safari/ims", $agent) === 0;
            });
            $this->Log("userAgent list without safari: " . count($agents));
        }

        if (!$linux) {
	        $agents = array_filter($agents, function($agent){
                return stripos($agent, 'linux') === false;
            });
            $this->Log("userAgent list without linux: " . count($agents));
        }

        if (!$macintosh) {
            $agents = array_filter($agents, function($agent){
                return stripos($agent, 'macintosh') === false;
            });
            $this->Log("userAgent list without macintosh: " . count($agents));
        }

        if (!$mobile) {
	        $agents = array_filter($agents, function($agent){
                return stripos($agent, 'mobile') === false;
            });
            $this->Log("userAgent list without mobile: " . count($agents));
        }

        if (!empty($top_user_agents)) {
            if ($count !== null) {
                $this->Log("choose only {$count} unique userAgent(s) from list of " . count($agents));
                $agents = array_slice($agents, 0, $count);
            }

            $list = [];
            foreach ($top_user_agents as $top_user_agent) {
                foreach ($agents as $userAgent) {
                    if ($top_user_agent['userAgent'] == $userAgent) {
                        $list = array_merge($list, array_fill(0, round($top_user_agent["percent"] * 10), $top_user_agent['userAgent']));
                    }
                }
            }

            $agents = $list;
        }

        $cnt = count($agents);
        if ($cnt == 0) {
            return;
        }

        shuffle($agents);
        $userAgent = $agents[random_int(0, $cnt - 1)];
        $this->Log("selected userAgent from list of " . $cnt
            . " (firefox: ".json_encode($firefox)
            . " , chrome: ".json_encode($chrome)
            . " , safari: ".json_encode($safari)
            . " , windows: ".json_encode($windows)
            . " , macintosh: ".json_encode($macintosh)
            . " , linux: ".json_encode($linux)
            . " , mobile: ".json_encode($mobile)
            . "): " . $userAgent);

        $this->setUserAgent($userAgent);
	}

	public function setRequestsPerMinute($prefix, $requestsPerMinute){
		$this->Log("set throttling $requestsPerMinute for $prefix");
		$this->plugins["rpm"] = new RequestThrottler($prefix, Cache::getInstance()->memcached, $requestsPerMinute, $this);
	}

    public function getPluginRPM()
    {
        return $this->plugins["rpm"] ?? null;
    }

	private $proxyParams = ['proxyAddress', 'proxyHost', 'proxyPort', 'proxyType', 'proxyLogin', 'proxyPassword'];

	public function getProxyParams()
    {
        $result = [];
        foreach ($this->proxyParams as $key)
            $result[$key] = $this->$key;
        return $result;
    }

    public function setProxyParams(array $params){
        foreach ($this->proxyParams as $key)
            if(array_key_exists($key, $params)) {
                if (!empty($params[$key])) {
                    $logValue = $params[$key];
                    if ($key === 'proxyPassword') {
                        $logValue = "xxx";
                    }
                    $this->Log("setProxyParam {$key}: $logValue");
                }
                $this->$key = $params[$key];
            }
    }

    /**
     * @param bool $http2
     * @return HttpBrowser
     */
    public function setHttp2(bool $http2): HttpBrowser
    {
        $this->Log("set http2: " . var_export($http2, true));
        $this->http2 = $http2;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHttp2(): bool
    {
        return $this->http2;
    }

    private function getUserAgentForState() : string
    {
        if ($this->userAgent === self::PROXY_USER_AGENT) {
            return 'proxy';
        }
        if ($this->userAgent === self::PUBLIC_USER_AGENT) {
            return 'public';
        }
        return $this->userAgent;
    }

    private function restoreUserAgent(string $userAgent)
    {
        if ($userAgent === 'proxy') {
            $this->Log("restoring proxy user agent");
            $this->setUserAgent(self::PROXY_USER_AGENT);
            return;
        }

        if ($userAgent === 'public') {
            $this->Log("restoring public user agent");
            $this->setUserAgent(self::PUBLIC_USER_AGENT);
            return;
        }

        $this->Log("restoring custom user agent");
        $this->setUserAgent($userAgent);
    }

    private function prepareHeaders(string $method, string $sURL, array $headers)
    {
        if ($this->setOriginHeader === true && empty($this->defaultHeaders['Origin']) && empty($headers['Origin'])) {
            $scheme = null;
            $urlParts = parse_url($sURL);
            if (isset($urlParts['scheme'], $urlParts['host'])) {
                $headers['Origin'] = $urlParts['scheme']."://".$urlParts['host'];
            }
        }

        if (array_key_exists('Referer', $headers) && $headers['Referer'] === null) {
            unset($headers['Referer']);
        } elseif (!empty($this->currentUrl()) && empty($headers['Referer'])) {
            $headers['Referer'] = $this->currentUrl();
        }

        if ($method == 'OPTIONS') {
            $headers['Access-Control-Request-Method'] = "POST";
            unset($headers['Referer']);
        }

        return $headers;
    }

    private function prepareRequest(HttpDriverRequest $request, $timeout)
    {
        if($this->SSLv3)
            $request->sslVersion = 3;
        if($this->http2)
            $request->http2 = true;

        $request->url = str_replace(' ', '%20', $request->url);
        if(!empty($this->proxyAddress)){
            $request->proxyAddress = $this->proxyAddress;
            $request->proxyPort = $this->proxyPort;
            $request->proxyType = $this->proxyType;
            $request->proxyLogin = $this->proxyLogin;
            $request->proxyPassword = $this->proxyPassword;
        }
        if(!empty($request->proxyAddress) && $this->proxtHttpsOnly && stripos($request->url, 'https://') !== 0){
            $this->Log("direct connection, do not use insecure proxy");
            $request->proxyAddress = null;
            $request->proxyPort = null;
        }
        $this->url = $request->url;
        // check Meta Redirects
        if (!$this->ParseMetaRedirects)
            $this->Log("Meta Redirects are disabled", LOG_LEVEL_ERROR);
        $this->redirector->parseMetaRedirects = $this->ParseMetaRedirects;
        $this->redirector->curlRequestTimeout = $timeout;
        foreach($this->plugins as $plugin)
            $plugin->onRequest($request);
    }

    private function processResponse(HttpDriverResponse $response, $startTime)
    {
        if ($this->LogHeaders) {
            if (preg_match("/^([\s\S]+?)\n(Cookie:[^\n]+)([\s\S]*)$/i",$response->requestHeaders, $matches)){
                $cutCookie = $matches[2];
                $requestHeaders = $matches[1].$matches[3];
                if (preg_match_all("/ (.+?)=(.*?)(?:;|$)/",$cutCookie,$matches, PREG_SET_ORDER)){
                    $cutCookie = '';
                    foreach ($matches as $match){
                        $cutCookie .= "<b>" . htmlspecialchars($match[1]) . "</b>&nbsp;=&nbsp;" . htmlspecialchars($match[2]) . "<br>";
                    }
                    $cutCookie = substr($cutCookie, 0, -4);
                }
            } else {
                $requestHeaders = $response->requestHeaders;
            }
            $this->Log(sprintf('<b>%s <a target="_blank" href="%s" style="color:black; text-decoration:none;">%s</a></b>', $response->request->method, $response->request->url, $response->request->url), LOG_LEVEL_HEADERS, false);
            if (!empty($response->request->postData)) {
                $this->Log("<b>post fields:</b>", LOG_LEVEL_NORMAL, false);
                $this->LogBlockBegin();
                if (is_array($response->request->postData))
                    foreach ($response->request->postData as $name => $value)
                        $this->Log($name.": ".htmlspecialchars($value), LOG_LEVEL_HEADERS, false);
                else {
                    $data = explode("&", $response->request->postData);
                    foreach ($data as $value)
                        $this->Log(htmlspecialchars(str_replace('=', ": ", $value)), LOG_LEVEL_HEADERS, false);
                }
                $this->LogBlockEnd();
            }
            $this->Log("<b>request headers:</b>", LOG_LEVEL_NORMAL, false);
            $this->LogBlockBegin();
            $this->Log("<pre>".htmlspecialchars(trim(preg_replace('#Authorization: Basic[^\n]*#ims', "Authorization: Basic ***", $requestHeaders))), LOG_LEVEL_HEADERS, false);
            if (isset($cutCookie)) {
                $this->Log("<b>Cookies:</b><br>".$cutCookie, LOG_LEVEL_NORMAL, false);
            }
            $this->Log("</pre>", LOG_LEVEL_HEADERS, false);
            $this->LogBlockEnd();
        }
        $lines = array_filter(explode("\n", $response->rawHeaders));
        if (!empty($lines)) {
            $status = array_shift($lines);
            $this->Log("<b>{$status}</b> " . round((microtime(true) - $startTime) * 1000) . " msec",LOG_LEVEL_HEADERS, false);
            if ($this->LogHeaders) {
                $cutHeaders = $cutCookies = [];
                foreach ($lines as $line) {
                    if (preg_match("/Set-Cookie: /i", $line)) {
                        $row = trim(str_ireplace("Set-Cookie:", '', $line));
                        if (preg_match("/^([^=]+)=(.*)$/", $row, $matches)) {
                            $cutCookies[] = "<b>" . htmlspecialchars($matches[1]) . "</b>&nbsp;=&nbsp;" . htmlspecialchars($matches[2]);
                        } else {
                            $cutCookies[] = htmlspecialchars($row);
                        }
                    } else {
                        $cutHeaders[] = $line;
                    }
                }
                $cutCookies = implode("\n", $cutCookies);
                $this->Log("<b>response headers:</b>", LOG_LEVEL_NORMAL, false);
                $this->LogBlockBegin();
                $this->Log("<pre>" . htmlspecialchars(trim(implode("\n", $cutHeaders))), LOG_LEVEL_HEADERS, false);
                if (!empty($cutCookies)) {
                    $this->Log("<b>Set-Cookies:</b><br>" . $cutCookies, LOG_LEVEL_NORMAL, false);
                }
                $this->Log("</pre>", LOG_LEVEL_HEADERS, false);
                $this->LogBlockEnd();
            }
        }
    }

    public function setKeepUserAgent(bool $keep)
    {
        $this->Log("keepUseAgent: " . var_export($keep, true));
        $this->keepUserAgent = $keep;
    }

    public function getProxyPassword() : ?string
    {
        return $this->proxyPassword;
    }

    public function getProxyPort() : ?int
    {
        return $this->proxyPort;
    }

    public function setSeleniumServer(?string $address)
    {
        $this->seleniumServer = $address;
    }

    public function getSeleniumServer()
    {
        return $this->seleniumServer;
    }

    public function setSeleniumBrowserFamily(?string $browserFamily)
    {
        $this->seleniumBrowserFamily = $browserFamily;
    }

    public function getSeleniumBrowserFamily()
    {
        return $this->seleniumBrowserFamily;
    }

    public function setSeleniumBrowserVersion(?string $browserVersion)
    {
        $this->seleniumBrowserVersion = $browserVersion;
    }

    public function getSeleniumBrowserVersion()
    {
        return $this->seleniumBrowserVersion;
    }

}
