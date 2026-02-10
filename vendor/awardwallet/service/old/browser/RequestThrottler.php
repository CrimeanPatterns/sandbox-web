<?php

class RequestThrottler implements HttpPluginInterface
{

	/**
	 * @var Throttler
	 */
	private $throttler;

	private $prefix;

	private $totalSleep = 0;

	/**
	 * @var HttpBrowser
	 */
	private $http;
	/**
	 * @var int
	 */
	private $reserveOnFirstRequest;

    /**
     * could be negative - then it means accounts per minute
     * @var int
     */
	private $requestsPerMinute;

	/**
	 * @var int
	 */
	private $requestCount;

	public function __construct($prefix, \Memcached $memcached, $requestsPerMinute, HttpBrowser $http, $reserveOnFirstRequest = 5){
		$this->prefix = $prefix;
		$this->throttler = new Throttler($memcached, 10, 6, abs($requestsPerMinute));
		$this->http = $http;
		$this->reserveOnFirstRequest = $reserveOnFirstRequest;
		$this->requestsPerMinute = $requestsPerMinute;
	}

	public function onRequest(HttpDriverRequest $request, $noIncrement = false)
	{
		$key = $this->prefix . "_";

		$this->requestCount++;

        if($this->requestsPerMinute > 0) {
            // requests per minute
            if(!empty($request->proxyAddress))
          			$key .= $request->proxyAddress;
          		else
          			$key .= gethostname();

            if ($this->requestCount == 1) {
                $sleep = $this->throttler->getDelay($key, true);
                if (!empty($sleep))
                    throw new ThrottledException($sleep, null, null, null, false, null, $this->prefix);
                elseif (!$noIncrement)
                    $this->throttler->increment($key, $this->reserveOnFirstRequest);
            } elseif (!$noIncrement && $this->requestCount > $this->reserveOnFirstRequest)
                $this->throttler->increment($key);
        }
        else{
            // accounts per minute
            $key .= "accounts";

            if ($this->requestCount == 1) {
                $sleep = $this->throttler->getDelay($key, true);
                if (!empty($sleep))
                    throw new ThrottledException($sleep, null, null, null, false, null, $this->prefix);
                elseif (!$noIncrement)
                    $this->throttler->increment($key);
            }
        }
        if ($noIncrement) {
            $this->requestCount--;
        }
	}

	public function onResponse(HttpDriverResponse $response) {}

}