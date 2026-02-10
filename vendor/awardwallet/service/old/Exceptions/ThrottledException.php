<?php

class ThrottledException extends \Exception implements CheckAccountExceptionInterface
{

	public $retryInterval;
    /**
     * @var int
     */
	private $maxThrottlingTime = 300;
    /**
     * @var bool
     */
	private $countAsRetry;
    /**
     * @var int
     */
    private $maxRetries;
    /**
     * @var string
     */
    private $provider;

    public function __construct($retryInterval, $maxThrottlingTime = null, \Throwable $previous = null, $message = null, $countAsRetry = true, $maxRetries = null, $provider = null) {
	    if ($message === null) {
	        $message = "Throttled";
        }
		parent::__construct($message, 0, $previous);
		$this->retryInterval = $retryInterval;
		if(!empty($maxThrottlingTime))
			$this->maxThrottlingTime = $maxThrottlingTime;
		$this->countAsRetry = $countAsRetry;
        $this->maxRetries = $maxRetries;
        $this->provider = $provider;
    }

	public function throwToParent(){
		return true;
	}

	public function isCountAsRetry(): bool
    {
        return $this->countAsRetry;
    }

    public function getMaxThrottlingTime() : int
    {
        return $this->maxThrottlingTime;
    }

    public function getMaxRetries() : ?int
    {
        return $this->maxRetries;
    }

    public function getProvider() : ?string
    {
        return $this->provider;
    }

}