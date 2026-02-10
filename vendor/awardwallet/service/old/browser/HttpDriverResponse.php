<?php

use AwardWallet\Common\Strings;

class HttpDriverResponse {

    const HTTP_OK = 200;

	/**
	 * @var HttpDriverRequest
	 */
	public $request;
	public $body = '';
	/**
	 * headers with keys in lower case
	 * [
	 * 	"date" => "2012-12-21",
	 * 	"status" => "Sent",
	 * 	"content-type" => "text/html",
	 * 	...
	 * ]
	 * @var array
	 */
	public $headers = [];
	public $rawHeaders;
	public $httpCode;
	public $errorCode;
	public $errorMessage;
	public $requestHeaders;
	public $attributes = [];
    /**
     * duration of request, milliseconds
     * @var int
     */
	public $duration = 0;

    public function __construct($body = null, $httpCode = null)
    {
        $this->body = $body;
        $this->httpCode = $httpCode;
    }

    public function toString(int $maxBodyChars = 512) : string
    {
        return "net: {$this->errorCode} {$this->errorMessage}, http: {$this->httpCode}, body: " . Strings::cutInMiddle($this->body, $maxBodyChars);
    }

}
