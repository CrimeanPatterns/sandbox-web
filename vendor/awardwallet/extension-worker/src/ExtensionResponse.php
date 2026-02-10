<?php

namespace AwardWallet\ExtensionWorker;

class ExtensionResponse
{

    public string $sessionId;
    public $result;
    public string $requestId;

    public function __construct(string $sessionId, $result, string $requestId) {
        $this->sessionId = $sessionId;
        $this->result = $result;
        $this->requestId = $requestId;
    }

}