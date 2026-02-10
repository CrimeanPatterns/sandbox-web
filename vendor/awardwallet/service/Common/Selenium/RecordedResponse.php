<?php

namespace AwardWallet\Common\Selenium;

class RecordedResponse
{
    /**
     * @var string
     */
    private $status;
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

    public function __construct(array $response)
    {
        $this->status = $response['status'];
        $this->time = $response['time'];
        $this->headers = $response['headers'];
        $this->body = $response['body'] ?? null;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
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