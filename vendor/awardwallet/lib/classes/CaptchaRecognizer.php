<?

class CaptchaRecognizer{

	public $DownloadTimeout = 30;
	public $FirstPause = 10;
	public $NextPause = 3;
	public $RecognizeTimeout = 60;
	public $CurlTimeout = 10;

	public $Russian = 0;
	public $MinLen = 0;
	public $MaxLen = 0;
	public $IsNumeric = 0;
	public $IsPhrase = 0;
	public $IsRegSense = 0;

	public $OnMessage;

	public $APIKey;
    public $domain = "antigate.com";

    public $captcha_id = null;

	const ERROR_NO_SLOT_AVAILABLE = 'ERROR_NO_SLOT_AVAILABLE';
	/**
	 * @var Callable
	 */
	public $onRecognize;

	public function __construct($onRecognize = null){
		$this->onRecognize = $onRecognize;
	}

    /*
     * @param array $postDataExtended
     * rucaptcha can be expanded parameters https://rucaptcha.com/api-rucaptcha
     */
	public function recognizeUrl($url, $extension = null, $postDataExtended = array()) {
		$image = curlRequest($url, $this->DownloadTimeout);
        if ($image === false)
			throw new CaptchaException("Can't download captcha");

		$file = "/tmp/captcha-".getmypid()."-".microtime(false).".".md5(FileExtension($url));
        if (isset($extension) && strlen($extension) <= 5)
            $file .= ".".$extension;
		file_put_contents($file, $image);
		$result = $this->recognizeFile($file, $postDataExtended);
		unlink($file);

		return $result;
	}

    /*
     * @param array $postDataExtended
     * rucaptcha can be expanded parameters https://rucaptcha.com/api-rucaptcha
     */
    public function recognizeFile($file, $postDataExtended = array()) {
		$postdata = array(
			'method'    => 'post',
			'key'       => $this->APIKey,
//			'file'      => '@'.$file,
			'phrase'	=> $this->IsPhrase,
			'regsense'	=> $this->IsRegSense,
			'numeric'	=> $this->IsNumeric,
			'min_len'	=> $this->MinLen,
			'max_len'	=> $this->MaxLen,
		);

        // only for https://rucaptcha.com
        if ($this->domain == "rucaptcha.com")
            $postdata = array_merge($postdata, $postDataExtended);

		$result = null;
		$beginTime = time();
		try {
            for ($n = 0; $n < 6 && (!isset($result) || $result == self::ERROR_NO_SLOT_AVAILABLE || strpos($result, '301 Moved Permanently') !== false); $n++) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "http://{$this->domain}/in.php");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt($ch, CURLOPT_POST, 1);
                $cfile = new CURLFile($file);
                $postdata['file'] = $cfile;
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                $result = curl_exec($ch);
                if (curl_errno($ch))
                    throw new CaptchaException("CURL returned error: " . curl_error($ch));
                curl_close($ch);
                if ($result == self::ERROR_NO_SLOT_AVAILABLE) {
                    $this->log("slot not available, waiting");
                    sleep(5);
                    continue;
                }
                if (strpos($result, '301 Moved Permanently') !== false) {
                    $this->log("service not available [301 Moved Permanently], waiting");
                    sleep(5);
                    continue;
                }
                if (strpos($result, "ERROR") !== false)
                    throw new CaptchaException("server returned error: $result");
                // 404 Page Not Found
                if (strpos($result, "<h1>404 Page Not Found</h1>") !== false) {
                    $this->log("service not available [404 Page Not Found], waiting");
                    sleep(5);
                }
                continue;
            }
            if ($result == self::ERROR_NO_SLOT_AVAILABLE)
                throw new CaptchaException("slot not available");
            if (strpos($result, '301 Moved Permanently') !== false)
                throw new CaptchaException("service not available");

            $ex = explode("|", $result);
            if (!isset($ex[1])) {
                // wrong response
                if ((empty($result) || $result == 'Not Found'))
                    throw new CaptchaException("service not available, captcha ID not found: $result");
                // 502 Bad Gateway
                if (preg_match("/502 Bad Gateway/", $result))
                    throw new CaptchaException("service not available, 502 Bad Gateway");
                // 404 Page Not Found
                if (strpos($result, "<h1>404 Page Not Found</h1>") !== false)
                    throw new CaptchaException("service not available, 404 Page Not Found");
                // 403 Forbidden
                if (strpos($result, "<h1>403 Forbidden</h1>") !== false)
                    throw new CaptchaException("service not available, 403 Forbidden");
                // 503 Service Unavailable
                if (strpos($result, "503 Service Unavailable") !== false)
                    throw new CaptchaException("service not available, 503 Service Unavailable");
                if (strpos($result, "500 Internal Server Error") !== false)
                    throw new CaptchaException("service not available, 500 Internal Server Error");
                // MYSQL error: Access denied for user
                if (strpos($result, "MYSQL error: Access denied for user") !== false)
                    throw new CaptchaException("service not available, MYSQL error: Access denied for user");
                if (strpos($result, "<h4>A PHP Error was encountered</h4>") !== false)
                    throw new CaptchaException("service not available, A PHP Error was encountered");

                try {
                    $title = "[Dev Notification]: {$this->domain} - Captcha result wasn't received";
                    mail(
                        ConfigValue(CONFIG_ERROR_EMAIL),
                        $title,
                        $this->domain . ': Captcha Result: [' . $result . '] - wrong format ' . var_export($ex, true),
                        EMAIL_HEADERS
                    );
                } catch (ErrorException $e) {
                    $this->log("[{$title} | TRACE]: " . $e->getMessage());
                }
                unset($title);

                $this->log("[Result]: {$result}");
            }// if (!isset($ex[1]))

            $captcha_id = $ex[1];
            $this->captcha_id = $captcha_id;
            $this->log("captcha sent, got captcha ID $captcha_id");
            $waittime = 0;
            $startTime = time();
            $this->log("waiting for {$this->FirstPause} seconds");
            sleep($this->FirstPause);
            while ($waittime < $this->RecognizeTimeout) {
                $info = [];
                $action = 'get';
                if ($this->domain == "rucaptcha.com") {
                    $action = 'get2';
                }
                $result = curlRequest("http://{$this->domain}/res.php?key={$this->APIKey}&action={$action}&id={$captcha_id}", $this->CurlTimeout, [], $info, $curlError);
                if ($curlError !== 0)
                    $this->log('curl error while getting result:  ' . $curlError);
                $this->log("result: " . $result);
                if (strpos($result, 'ERROR') !== false)
                    throw new CaptchaException("server returned error: $result");
                if ($result == "CAPCHA_NOT_READY" || $result === false) {
                    $this->log("captcha is not ready yet");
                    $waittime += $this->NextPause;
                    $this->log("waiting for {$this->NextPause} seconds");
                    sleep($this->NextPause);
                } else {
                    $ex = explode('|', $result);
                    if (trim($ex[0]) == 'OK') {

                        if (isset($ex[2])) {
                            $cost = $ex[2];
                            if (isset($cost) && is_numeric($cost))
                                $this->log("[Cost]: {$cost}");
                            else
                                $this->log("[Result]: " . var_export($ex[2], true));
                        }// if (isset($ex[2]))

                        $this->log("recognized: " . $ex[1] . ", duration: " . (time() - $startTime). ", length: " . strlen(trim($ex[1])));

                        return trim($ex[1]);
                    }
                }
            }
            throw new CaptchaException("timelimit ({$this->RecognizeTimeout}) hit");
        }
        finally{
            if(!empty($this->onRecognize))
                call_user_func($this->onRecognize, time() - $beginTime);
        }
	}

    /**
     * recognize reCaptcha v.2, reCaptcha v.3, funcaptcha, geetest, hcaptcha
     *
     * @param string $key
     * captcha key
     * @param array $postDataExtended
     *
     * RuCaptcha can be expanded parameters https://rucaptcha.com/api-rucaptcha
     * supporting 4 methods now: userrecaptcha, funcaptcha, geetest, hcaptcha
     *
     * @link https://rucaptcha.com/newapi-recaptcha
     * @link https://rucaptcha.com/api-rucaptcha#solving_funcaptcha_new
     * @link https://rucaptcha.com/api-rucaptcha#solving_hcaptcha
     * @link https://rucaptcha.com/blog/recaptcha-v3-obhod
     * @link https://rucaptcha.com/api-rucaptcha#solving_geetest
     *
     * ReCaptcha V2 Invisible vs V3
     * @link https://rucaptcha.com/blog/avtomaticheskoe-reshenie-recaptcha-v3
     *
     * @throws Exception
     *
     * @return string|array
     */
    public function recognizeByRuCaptcha($key, $postDataExtended = array()) {
        // only for https://rucaptcha.com
        if ($this->domain != "rucaptcha.com")
            throw new CaptchaException("Unfortunately, service {$this->domain} do not support this method");

        $postDataExtended['method'] = $postDataExtended['method'] ?? 'userrecaptcha';
        switch ($postDataExtended['method']) {
            case 'funcaptcha':
                $keyName = 'publickey';
                break;
            case 'geetest':
                $keyName = 'gt';
                break;
            case 'hcaptcha':
                $keyName = 'sitekey';
                break;
            default:
                $keyName = 'googlekey';
                break;
        }

        $postdata = [
            'key'    => $this->APIKey,
            $keyName => $key,
            'json'   => '1'
        ];
        // expanded parameters
        $postdata = array_merge($postdata, $postDataExtended);

        // improvements for reCaptcha v.3
        $captchaVersion = $postdata["version"] ?? null;
        $action = 'get2';
        if ($captchaVersion === "v3" || $captchaVersion === "enterprise") {
            $action = 'get';
        }
        $this->log("[Post data]: <pre>" . var_export($postdata, true)."</pre>");

        $result = null;
        $beginTime = time();
        try {
            for ($n = 0; $n < 6 && (!isset($result) || strpos($result, self::ERROR_NO_SLOT_AVAILABLE)); $n++) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "http://{$this->domain}/in.php");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                $result = curl_exec($ch);
                if (curl_errno($ch))
                    throw new CaptchaException("CURL returned error: " . curl_error($ch));
                curl_close($ch);
                $this->log("Result: " . var_export($result, true));
                if (strpos($result, self::ERROR_NO_SLOT_AVAILABLE)) {
                    $this->log("slot not available, waiting");
                    sleep(5);
                    continue;
                }
//            if (strpos($result, '301 Moved Permanently') !== false) {
//                $this->log("service not available [301 Moved Permanently], waiting");
//                sleep(5);
//                continue;
//            }
//            if (strpos($result, "ERROR") !== false)
//                throw new CaptchaException("server returned error: $result");
//            // 404 Page Not Found
//            if (strpos($result, "<h1>404 Page Not Found</h1>") !== false) {
//                $this->log("service not available [404 Page Not Found], waiting");
//                sleep(5);
//            }
                continue;
            }// for ($n = 0; $n < 6 && (!isset($result) || strpos($result, self::ERROR_NO_SLOT_AVAILABLE)); $n++)
            if (strpos($result, self::ERROR_NO_SLOT_AVAILABLE))
                throw new CaptchaException("slot not available");
//        if (strpos($result, '301 Moved Permanently') !== false)
//            throw new CaptchaException("service not available");

            $res = json_decode($result, true);
            $status = ArrayVal($res, 'status');
            $captcha_id = ArrayVal($res, 'request');
            // wrong response
            if ($status == 0)
                throw new CaptchaException("service not available, captcha ID not found: {$captcha_id}");

            // debug
            if (empty($captcha_id) && $captcha_id !== 0) {

                if (!defined('EMAIL_HEADERS')) {
                    define("EMAIL_HEADERS", "Content-Type: text/plain; charset=utf-8\nDate: ". date('r'). " \nFrom: ".SITE_NAME." <".FROM_EMAIL.">\nReply-To: ".SITE_NAME." <".FROM_EMAIL.">\nBcc: notifications@awardwallet.com");
                }

                try {
                    $title = "[Dev Notification]: {$this->domain} - Captcha result wasn't received";
                    mail(
                        ConfigValue(CONFIG_ERROR_EMAIL),
                        $title,
                        $this->domain . ': Captcha Result: [' . var_export($result, true) . '] - wrong format',
                        EMAIL_HEADERS
                    );
                } catch (ErrorException $e) {
                    $this->log("[{$title} | TRACE]: " . $e->getMessage());
                }
                unset($title);
            }// if (empty($captcha_id) && $captcha_id !== 0)

            $this->captcha_id = $captcha_id;
            $this->log("captcha sent, got captcha ID $captcha_id");
            $waittime = 0;
            $startTime = time();
            $this->log("waiting for {$this->FirstPause} seconds");
            sleep($this->FirstPause);
            while ($waittime < $this->RecognizeTimeout) {
                $info = [];
                $result = curlRequest("http://{$this->domain}/res.php?key={$this->APIKey}&action=get&json=1&id={$captcha_id}", $this->CurlTimeout, [], $info, $curlError);
                if ($curlError !== 0)
                    $this->log('curl error while getting result:  ' . $curlError);
                $res = json_decode($result, true);
                $status = ArrayVal($res, 'status');
                $request = ArrayVal($res, 'request');

                if ($request && !is_array($request) && strpos($request, 'ERROR_') !== false)
                    throw new CaptchaException("server returned error: $request");
                if ($request == "CAPCHA_NOT_READY" || empty($result)) {
                    $this->log("captcha is not ready yet");
                    $waittime += $this->NextPause;
                    $this->log("waiting for {$this->NextPause} seconds");
                    sleep($this->NextPause);
                }// if ($request == "CAPCHA_NOT_READY" || $result === false)
                else {
                    if ($status == 1) {
                        // cost
                        $result = curlRequest("http://{$this->domain}/res.php?key={$this->APIKey}&action={$action}&taskinfo=1&json=1&id={$captcha_id}", $this->CurlTimeout, [], $info, $curlError);
                        if ($curlError !== 0)
                            $this->log('curl error while getting cost result:  ' . $curlError);
//                        $this->log("[Response]: " . var_export($result, true));
                        $res = json_decode($result, true);
                        if ($workerID = ArrayVal($res, 'user_check', null)) {
                            $this->log("[Worker ID]: {$workerID}");
                        }
                        if ($workerScore = ArrayVal($res, 'user_score', null)) {
                            $this->log("[Worker Score]: {$workerScore}");
                        }
                        $requestCost = ArrayVal($res, 'request', null);

                        if (is_array($res) && ($cost = ArrayVal($res, 'price', null))) {
                            $this->log("[Cost]: {$cost}");
                        } elseif (is_string($requestCost) && strstr($requestCost, '|')) {
                            $ar = explode('|', $requestCost);
                            $cost = array_pop($ar);
                            if (isset($cost) && is_numeric($cost))
                                $this->log("[Cost]: {$cost}");
                            else
                                $this->log("[Result]: " . var_export($requestCost, true));
                        }// if (strstr($requestCost, '|'))

                        // geetest workaround
                        if (is_array($requestCost) && ArrayVal($requestCost, 'geetest_challenge', null)) {
                            $request = json_encode($requestCost);
                        }
                        if (is_array($request) && ArrayVal($request, 'geetest_challenge', null)) {
                            $this->log("[Request, debug]: " . var_export($request, true));//todo: debug
                            $request = json_encode($request);
                            $this->log("[Request, debug]: " . var_export($request, true));//todo: debug
                        }

                        $this->log("recognized: {$request}, duration: " . (time() - $startTime) . ", length: " . strlen(trim($request)));

                        return trim($request);
                    }// if ($status == 1)
                    else
                        $this->log("[Result]: " . var_export($result, true));
                }
            }// while ($waittime < $this->RecognizeTimeout)

            throw new CaptchaException("timelimit ({$this->RecognizeTimeout}) hit");
        }
        finally{
            if(!empty($this->onRecognize))
                call_user_func($this->onRecognize, time() - $beginTime);
        }
    }

	protected function log($message){
		if(isset($this->OnMessage))
			call_user_func($this->OnMessage, $message);
	}

    /*
     * Report an incorrectly solved CAPTCHA.
     * Make sure the CAPTCHA was in fact incorrectly solved!
     *
     * only for https://rucaptcha.com, https://api.anti-captcha.com
     *
     * @link https://anticaptcha.atlassian.net/wiki/spaces/API/pages/632193041/reportIncorrectRecaptcha+send+complaint+on+a+Recaptcha
     */
    public function reportIncorrectlySolvedCAPTCHA()
    {
        if ($this->domain == "api.anti-captcha.com") {
            $postData = [
                "clientKey" => $this->APIKey,
                "taskId"    => $this->captcha_id,
            ];
            $postResult = $this->jsonPostRequest("reportIncorrectRecaptcha", $postData);
            $this->log("Response: ".json_encode($postResult));
            if ($postResult == false) {
                $this->log("API error");
            }
            if ($postResult->errorId == 16) {
                $this->log("captcha not found or expired");
                return false;
            }
            elseif ($postResult->errorId == 0) {
                if ($postResult->status == "success") {
                    $this->log("complaint accepted");
                    $this->log("Incorrectly solved CAPTCHA ($this->captcha_id) has been reported");

                    return true;
                }// if ($postResult->status == "ready")

                throw new CaptchaException("unknown API status, update your software");
            }// if ($postResult->errorId == 0)
        } elseif ($this->domain != "rucaptcha.com") {
            $this->log("Unfortunately, service {$this->domain} do not support this method");
            return false;
        }

        $info = [];
        $result = curlRequest("http://{$this->domain}/res.php?key=".$this->APIKey."&action=reportbad&id=".$this->captcha_id, $this->CurlTimeout, [], $info, $curlError);
        if ($curlError !== 0)
            $this->log('curl error while report incorrect result:  ' . $curlError);
        $this->log("Incorrectly solved CAPTCHA ($this->captcha_id) has been reported");
        $this->log("Response: ".$result);

        return true;
    }

    /**
     * Report an correctly solved CAPTCHA.
     *
     * Needed for reCaptcha v.3!
     * Move worker to our WhiteList on the service for some time
     *
     * only for https://rucaptcha.com
     * @url https://rucaptcha.com/blog/recaptcha-v3-obhod
     * @url https://rucaptcha.com/api-rucaptcha#solving_recaptchav3
     */
    public function reportGoodCaptcha() {
        if ($this->domain != "rucaptcha.com") {
            $this->log("Unfortunately, service {$this->domain} do not support this method");
            return;
        }
        $info = [];
        $result = curlRequest("http://{$this->domain}/res.php?key=".$this->APIKey."&action=reportgood&id=".$this->captcha_id, $this->CurlTimeout, [], $info, $curlError);
        if ($curlError !== 0)
            $this->log('curl error while report incorrect result:  ' . $curlError);
        $this->log("Correctly solved CAPTCHA ($this->captcha_id) has been reported");
        $this->log("Response: ".$result);
    }

    /**
     * Return current Captcha ID
     *
     * @return int|null
     */
    public function getCaptchaID() {
        $this->log("Captcha ID: {$this->captcha_id}");
        return $this->captcha_id;
    }

    /**
     * recognize via Anti Captcha API v2
     *
     * @param array $postDataExtended
     * AntCaptcha can be expanded parameters https://anticaptcha.atlassian.net/wiki/spaces/API/pages/196635/Documentation+in+English
     * @link https://github.com/AdminAnticaptcha/anticaptcha-php
     *
     * @throws Exception
     *
     * @return string|object
     */
    public function recognizeAntiCaptcha($postDataExtended = []) {
        // only for Anti Captcha API v2
        if ($this->domain != "api.anti-captcha.com")
            throw new CaptchaException("Unfortunately, service {$this->domain} do not support this method");

        $this->log("[Post data]: <pre>" . var_export($postDataExtended, true)."</pre>");

        $keyMem = 'anti_captcha_' . substr($this->APIKey, -5);
        $checkStatus = \Cache::getInstance()->get($keyMem);
        if ($checkStatus === false) {
            $response = $this->curlGet("https://anti-captcha.com/res.php?key=" . $this->APIKey . "&action=getbalance");
            if (is_numeric($response) && round($response, 2) > 0) {
                \Cache::getInstance()->set($keyMem, $response, 60 * 60); // for 1 hour
            } else {
                throw new CaptchaException("Unfortunately, service is down: " . json_decode($response));
            }
        } elseif (strpos($checkStatus, 'ERROR_') !== false) {
            throw new CaptchaException("Unfortunately, service is down: " . json_decode($checkStatus));
        }

        $beginTime = time();
        try {
            $postData = [
                "clientKey" => $this->APIKey,
            ];
            $submitResult = $this->jsonPostRequest("createTask", array_merge($postData, ["task" => $postDataExtended]));

            if (!isset($submitResult->errorId)) {
                $this->log("Result has other format: " . json_encode($submitResult));
                throw new CaptchaException('Something went wrong. Result has other format.');
            }
            if ($submitResult->errorId != 0) {
                $this->log("API error {$submitResult->errorCode} : {$submitResult->errorDescription}");
                if (strpos($submitResult->errorCode, 'ERROR_ZERO_BALANCE') !== false
                    || strpos($submitResult->errorCode, 'ERROR_ACCOUNT_SUSPENDED') !== false
                ) {
                    \Cache::getInstance()->set($keyMem, $submitResult->errorCode, 60 * 60); // for 1 hour
                }
                throw new CaptchaException("{$submitResult->errorCode} : {$submitResult->errorDescription}");
            }
            $captcha_id = $submitResult->taskId;
            $this->captcha_id = $captcha_id;
            $this->log("captcha sent, got captcha ID $captcha_id");
            $postDataResult = array_merge($postData, ["taskId" => $captcha_id]);
            $waitTime = 0;
            $startTime = time();
            $this->log("waiting for {$this->FirstPause} seconds");
            sleep($this->FirstPause);
            $result = null;
            while ($waitTime < $this->RecognizeTimeout) {
                $this->log("requesting task status");
                $postResult = $this->jsonPostRequest("getTaskResult", $postDataResult);
                if ($postResult == false) {
                    $this->log("API error");
                    $this->log("waiting for {$this->NextPause} seconds");
                    sleep($this->NextPause);
                    continue;
                }
                if ($postResult->errorId == 0) {
                    if ($postResult->status == "processing") {
                        $this->log("captcha is not ready yet");
                        $waitTime += $this->NextPause;
                        $this->log("waiting for {$this->NextPause} seconds");
                        sleep($this->NextPause);
                        continue;
                    }
                    if ($postResult->status == "ready") {
                        $this->log("task is complete");
                        if (isset($postResult->solution->token))
                            $result = $postResult->solution->token;
                        elseif (isset($postResult->solution->text))
                            $result = $postResult->solution->text;
                        elseif (isset($postResult->solution->gRecaptchaResponse))
                            $result = $postResult->solution->gRecaptchaResponse;
                        // GeetTest
                        elseif (isset($postResult->solution->challenge, $postResult->solution->validate, $postResult->solution->seccode))
                            $result = $postResult->solution;
                        else
                            $result = null;

                        if (is_object($result)) {
                            $this->log("recognized: " . json_encode($result) . ", duration: " . (time() - $startTime));
                            return $result;
                        } else {
                            $this->log("recognized: {$result}, duration: " . (time() - $startTime) . ", length: " . strlen(trim($result)));
                            return trim($result);
                        }
                    }// if ($postResult->status == "ready")
                    throw new CaptchaException("unknown API status, update your software");
                }// if ($postResult->errorId == 0)
                else {
                    $this->log("API error {$postResult->errorCode}: {$postResult->errorDescription}");
                    if (strpos($postResult->errorCode, 'ERROR_ZERO_BALANCE') !== false
                        || strpos($postResult->errorCode, 'ERROR_ACCOUNT_SUSPENDED') !== false
                    ) {
                        \Cache::getInstance()->set($keyMem, $postResult->errorCode, 60 * 60); // for 1 hour
                    }
                    throw new CaptchaException("{$postResult->errorCode}: {$postResult->errorDescription}");
                }
            }// while ($waitTime < $this->RecognizeTimeout)

            throw new CaptchaException("timelimit ({$this->RecognizeTimeout}) hit");
        }
        finally{
            if(!empty($this->onRecognize))
                call_user_func($this->onRecognize, time() - $beginTime);
        }
    }

    public function jsonPostRequest($methodName, $postData) {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,"https://{$this->domain}/$methodName");
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_ENCODING,"gzip,deflate");
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST, "POST");
        $postDataEncoded = json_encode($postData);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$postDataEncoded);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json',
            'Content-Length: ' . strlen($postDataEncoded)
        ));
        curl_setopt($ch,CURLOPT_TIMEOUT,30);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,30);
        $result = curl_exec($ch);
        if (curl_errno($ch))
            throw new CaptchaException("CURL returned error: ".curl_error($ch));

        curl_close($ch);
        return json_decode($result);
    }

    private function curlGet($url)
    {
        $query = curl_init($url);
        curl_setopt($query, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($query, CURLOPT_TIMEOUT, 15);
        curl_setopt($query, CURLOPT_HEADER, false);
        curl_setopt($query, CURLOPT_FAILONERROR, false);
        curl_setopt($query, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
        curl_setopt($query, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($query);
        $code = curl_getinfo($query, CURLINFO_HTTP_CODE);

        curl_close($query);

        return $response;
    }

}

class CaptchaException extends Exception{

}
