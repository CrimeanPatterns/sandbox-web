<?php

class CheckRetryNeededException extends Exception implements CheckAccountExceptionInterface {

    // Explicit check attempts count setting
    public $checkAttemptsCount;
    // Explicit retry timeout setting
    public $retryTimeout;
    // Error code which should be set in checker if retry exception is thrown but attempts count exceeded
    public $errorCodeWhenAttemptsExceeded;
    // Error message which should be set in checker if retry exception is thrown but attempts count exceeded
    public $errorMessageWhenAttemptsExceeded;
    // only for wsdl https://redmine.awardwallet.com/issues/15873#note-44
    const MAX_RETRIES = 5;

    public function __construct($checkAttemptsCount = 2, $retryTimeout = 10, $errorMessage = null, $errorCode = null, \Throwable $previous = null) {
        parent::__construct($errorMessage ? $errorMessage : "CheckRetryNeededException", 0, $previous);

        if($checkAttemptsCount > self::MAX_RETRIES)
            throw new Exception("CheckAttemptsCount={$checkAttemptsCount} is unavailable attempts number. Max: ".self::MAX_RETRIES, 0, $previous);

        $this->checkAttemptsCount = $checkAttemptsCount;
        $this->retryTimeout = ConfigValue(CONFIG_SITE_STATE) === SITE_STATE_PRODUCTION ? $retryTimeout : 0;
        $this->errorMessageWhenAttemptsExceeded = $errorMessage;

        if (!is_null($errorMessage) && is_null($errorCode))
            $errorCode = ACCOUNT_PROVIDER_ERROR;

        $this->errorCodeWhenAttemptsExceeded = $errorCode;
    }

    public function throwToParent() {
        return true;
    }

}
