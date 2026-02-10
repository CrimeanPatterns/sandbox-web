<?php

namespace AwardWallet\ExtensionWorker;

class ShowMessageRequest {

    public int $tabId;
    public int $frameId;
    public string $message;

    public function __construct(int $tabId, int $frameId, string $message)
    {
        $this->tabId = $tabId;
        $this->frameId = $frameId;
        $this->message = $message;
    }

}
