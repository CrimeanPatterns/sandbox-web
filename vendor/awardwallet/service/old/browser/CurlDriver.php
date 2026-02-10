<?

class CurlDriver implements HttpDriverInterface
{
    /**
	 * @var resource
	 */
	public $curl;
	private $sslOptions;
    /**
     * @var Memcached
     */
    private $memcached;
    private $handlers;

    public function __construct(\Memcached $memcached = null){
	    // https://www.openssl.org/docs/manmaster/apps/ciphers.html
        // https://wiki.openssl.org/index.php/Manual:Ciphers(1)
		$ciphers = [
			'DHE-RSA-AES256-SHA',
			'DHE-DSS-AES256-SHA',
			'AES256-SHA:KRB5-DES-CBC3-MD5',
			'KRB5-DES-CBC3-SHA',
			'EDH-RSA-DES-CBC3-SHA',
			'EDH-DSS-DES-CBC3-SHA',
			'DES-CBC3-SHA:DES-CBC3-MD5',
			'DHE-RSA-AES128-SHA',
			'DHE-DSS-AES128-SHA',
			'AES128-SHA:RC2-CBC-MD5',
			'KRB5-RC4-MD5:KRB5-RC4-SHA',
			'RC4-SHA:RC4-MD5:RC4-MD5',
			'KRB5-DES-CBC-MD5',
			'KRB5-DES-CBC-SHA',
			'EDH-RSA-DES-CBC-SHA',
			'EDH-DSS-DES-CBC-SHA:DES-CBC-SHA',
			'DES-CBC-MD5:EXP-KRB5-RC2-CBC-MD5',
			'EXP-KRB5-DES-CBC-MD5',
			'EXP-KRB5-RC2-CBC-SHA',
			'EXP-KRB5-DES-CBC-SHA',
			'EXP-EDH-RSA-DES-CBC-SHA',
			'EXP-EDH-DSS-DES-CBC-SHA',
			'EXP-DES-CBC-SHA',
			'EXP-RC2-CBC-MD5',
			'EXP-RC2-CBC-MD5',
			'EXP-KRB5-RC4-MD5',
			'EXP-KRB5-RC4-SHA',
			'EXP-RC4-MD5:EXP-RC4-MD5',
		];
		$this->sslOptions = [
			[
				'ciphers' => ['ALL:!EXPORT:!EXPORT40:!EXPORT56:!aNULL:!LOW:!RC4:@STRENGTH'],
				'sslVersion' => 0
			],
			[
				'ciphers' => $ciphers,
				'sslVersion' => 0
			],
//			[ // china
//				'ciphers' => $ciphers,
//				'sslVersion' => 3
//			],
			[ // fastpark
				'ciphers' => array_merge($ciphers, ['ECDHE-RSA-AES256-SHA384']),
				'sslVersion' => 0
			],
            [ // sportmaster
				'ciphers' => array_merge($ciphers, ['ALL']),
				'sslVersion' => 0
			],
            [
                'ciphers' => \array_merge($ciphers, ['DEFAULT:!DH']),
                'sslVersion' => 0,
            ],
//            [ // bestbuy
//				'ciphers' => array_merge($ciphers, ['ECDHE-RSA-AES256-GCM-SHA384']),
//				'sslVersion' => 3
//			]
		];
        $this->memcached = $memcached;
        // @TODO: check all projects, make memcached argument mandatory
        if ($this->memcached === null && class_exists('Cache')) {
            $this->memcached = Cache::getInstance();
        }
    }

    public function start($proxy = null, $proxyLogin = null, $proxyPassword = null, $userAgent = null)
	{
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_HEADER, true);
		curl_setopt($this->curl, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($this->curl, CURLOPT_MAXREDIRS, 0);
		curl_setopt($this->curl, CURLOPT_SAFE_UPLOAD, true);
		curl_setopt($this->curl, CURLOPT_ENCODING, '');
//		curl_setopt($this->curl, CURLOPT_VERBOSE, true);
	}

	public function isStarted()
	{
		return !empty($this->curl);
	}

	public function stop()
	{
		if($this->isStarted()) {
			curl_close($this->curl);
			$this->curl = null;
		}
	}

	public function request(HttpDriverRequest $request)
	{
        if(!$this->isStarted())
            $this->start();

        $cacheKey = "aw_curl_sslOptions2_" . parse_url($request->url, PHP_URL_HOST);

        $this->setCurlOptions($request, $cacheKey);

        $sslOptions = $this->sslOptions;
        if(isset($this->memcached))
            $preferredOption = $this->memcached->get($cacheKey);
        else
            $preferredOption = false;
        if($preferredOption !== false && $preferredOption < count($this->sslOptions) && isset($sslOptions[$preferredOption])){
            $option = $sslOptions[$preferredOption];
            unset($sslOptions[$preferredOption]);
            array_unshift($sslOptions, $option);
        }

        foreach($sslOptions as $n => $params) {
            curl_setopt($this->curl, CURLOPT_SSL_CIPHER_LIST, implode(':', $params['ciphers']));
            curl_setopt($this->curl, CURLOPT_SSLVERSION, $params['sslVersion']);
            $result = $this->sendRequest();
            if(($result->errorCode != 35 && $result->errorCode != 27) || stripos($result->errorMessage, 'SSL') === false) {
                if(empty($result->errorCode) && isset($this->memcached))
                    $this->memcached->set($cacheKey, $n, 3600);
                break;
            }
        }

        $result->request = $request;
        return $result;
    }

    /**
     * @return HttpDriverResponse
     */
    protected function sendRequest()
    {
        $result = new HttpDriverResponse();
        $startTime = microtime(true);
        $response = curl_exec($this->curl);
        $result->duration = round((microtime(true) - $startTime) * 1000);
        $info = curl_getinfo($this->curl);

        $this->parseCurlResponse($result, $response, $info);

        return $result;
    }

    protected function requestAsync(HttpDriverRequest $request)
    {
        if(!$this->isStarted())
            $this->start();

        $cacheKey = "aw_curl_sslOptions2_" . parse_url($request->url, PHP_URL_HOST);

        $this->setCurlOptions($request, $cacheKey);

        $sslOptions = $this->sslOptions[0] ?? null;

        if ($sslOptions) {
            curl_setopt($this->curl, CURLOPT_SSL_CIPHER_LIST, implode(':', $sslOptions['ciphers']));
            curl_setopt($this->curl, CURLOPT_SSLVERSION, $sslOptions['sslVersion']);
        }

        $this->handlers[] = curl_copy_handle($this->curl);
    }

    /**
     * @var array $requests
     * @return array $responses
     */
    public function sendAsyncRequests(array $requests): array
    {
        foreach ($requests as $request) {
            $this->requestAsync($request);
        }

        $responses = [];
        $multiHandler = curl_multi_init();

        foreach ($this->handlers as $handler) {
            curl_multi_add_handle($multiHandler, $handler);
        }

        $running = 0;
        $startTime = microtime(true);

        do {
            $status = curl_multi_exec($multiHandler, $running);

            if ($running) {
                curl_multi_select($multiHandler);
            }
        } while ($running > 0 && $status == CURLM_OK);

        $duration = round((microtime(true) - $startTime) * 1000);

        foreach ($this->handlers as $key => $handler) {
            $result = new HttpDriverResponse();
            $response = curl_multi_getcontent($handler);
            $info = curl_getinfo($handler);

            $this->parseCurlResponse($result, $response, $info);
            $result->duration = $duration;
            $result->request = $requests[$key];
            $responses[] = $result;
        }

        foreach ($this->handlers as $handler) {
            curl_multi_remove_handle($multiHandler, $handler);
            curl_close($handler);
        }

        curl_multi_close($multiHandler);
        $this->handlers = [];

        return $responses;
    }

    private function setCurlOptions(HttpDriverRequest $request, string $cacheKey)
    {
        if($this->memcached !== null) {
            $sslVersion = (int)$this->memcached->get($cacheKey);
        }
        else
            $sslVersion = 0;

        if(!empty($request->proxyAddress)){
            curl_setopt($this->curl, CURLOPT_PROXY, $request->proxyAddress);
            curl_setopt($this->curl, CURLOPT_PROXYPORT, $request->proxyPort);
            curl_setopt($this->curl, CURLOPT_PROXYTYPE, $request->proxyType);
            if(!empty($request->proxyLogin))
                curl_setopt($this->curl, CURLOPT_PROXYUSERPWD, $request->proxyLogin . ':' . $request->proxyPassword);
        }
        else{
            curl_setopt($this->curl, CURLOPT_PROXY, null);
            curl_setopt($this->curl, CURLOPT_PROXYPORT, null);
        }

        curl_setopt($this->curl, CURLOPT_SSLVERSION, $sslVersion);

        curl_setopt($this->curl, CURLOPT_URL, $request->url);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getRequestHeaderLines($request->headers));

        if ($request->http2) {
            curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2);
        } else {
            curl_setopt($this->curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_NONE);
        }

        if ($request->method == 'POST') {
            curl_setopt($this->curl, CURLOPT_POST, true);
            if(is_array($request->postData)){
                if(isset($request->headers['content-type']) && $request->headers['content-type'] == 'multipart/form-data')
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $request->postData);
                else
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($request->postData));
            }
            else{
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $request->postData);
            }
        }
        else{
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, null);
            curl_setopt($this->curl, CURLOPT_POST, false);
        }

        if ($request->method == 'OPTIONS' || $request->method == 'PUT' || $request->method == 'DELETE' || $request->method == 'PATCH') {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $request->method);
            if(is_array($request->postData))
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($request->postData));
            else
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $request->postData);
        } else
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, null);

        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, empty($request->timeout) ? 30 : ($request->timeout / 2));
        curl_setopt($this->curl, CURLOPT_TIMEOUT, empty($request->timeout) ? 60 : $request->timeout);
    }

    private function parseCurlResponse(HttpDriverResponse $result, string $curlResponse, array $curlInfo)
    {
        // cURL automatically handles Proxy rewrites, remove the "HTTP/1.0 200 Connection established" string
        while (preg_match("#^HTTP/[^\r\n]+\r\n([^\r\n]+\r\n)*\r\nHTTP/#ims", $curlResponse)) {
            $curlResponse = preg_replace("#^HTTP[^\r\n]+\r\n([^\r\n]+\r\n)*\r\nHTTP#ims", 'HTTP', $curlResponse);
        }

        $result->httpCode = (int)$curlInfo['http_code'];
        $result->errorCode = curl_errno($this->curl);
        $result->errorMessage = curl_error($this->curl);
        if(!empty($curlInfo['request_header']))
            $result->requestHeaders = $curlInfo['request_header'];

        $bodyStart = strpos($curlResponse, "\r\n\r\n");
        if ($bodyStart !== false) {
            $headersText = substr($curlResponse, 0, $bodyStart);
            $result->rawHeaders = $headersText;
            $headers = explode("\r\n", $headersText);
            foreach ($headers as $line) {
                $line = trim($line);
                $pos = strpos($line, ':');
                if ($pos !== false) {
                    $name = trim(strtolower(substr($line, 0, $pos)));
                    $value = trim(substr($line, $pos + 1));
                    if (isset($result->headers[$name])) {
                        if (is_string($result->headers[$name]))
                            $result->headers[$name] = [$result->headers[$name]];
                        $result->headers[$name][] = $value;
                    } else
                        $result->headers[$name] = $value;
                }

            }
            $result->body = substr($curlResponse, $bodyStart + 4);

            if($result->body === false){
                $result->body = "";
            }
        }
    }

	private function getRequestHeaderLines(array $headers)
	{
		$result = [];
		foreach ($headers as $key => $value)
			$result[] = $key . ": " . $value;
		return $result;
	}

	public function getState()
	{
		return [];
	}

	public function setState(array $state)
	{

	}

	public function setLogger(HttpLoggerInterface $logger){
	}
}
