<?php

namespace AwardWallet\Common\Selenium;

class RecordedRequest
{

    /**
     * @var string
     */
    private $uri;
    /**
     * @var string
     */
    private $verb;
    /**
     * @var string
     */
    private $time;
    /**
     * @var string
     */
    private $headers;
    /**
     * @var string|array
     */
    private $body;

    public function __construct(array $request)
    {
        $this->uri = $request['uri'];
        $this->verb = $request['verb'];
        $this->time = $request['time'];
        $this->headers = $request['headers'];
        $this->body = $request['body'] ?? null;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return string
     */
    public function getVerb()
    {
        return $this->verb;
    }

    /**
     * @return string
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return string
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return array|string
     */
    public function getBody()
    {
        return $this->body;
    }

}