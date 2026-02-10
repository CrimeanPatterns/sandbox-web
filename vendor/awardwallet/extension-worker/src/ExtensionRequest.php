<?php
namespace AwardWallet\ExtensionWorker;

class ExtensionRequest {

    public string $command;
    public $params;
    // used for response idempotency
    public string $requestId;

    public function __construct(string $command, $params) {
        $this->command = $command;
        $this->params = $params;
        $this->requestId = bin2hex(random_bytes(8));
    }
}