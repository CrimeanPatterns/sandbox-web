<?php

namespace AwardWallet\ExtensionWorker;

class NoParamsRequest {

    public int $tabId;
    public int $frameId;

    public function __construct(int $tabId, int $frameId)
    {
        $this->tabId = $tabId;
        $this->frameId = $frameId;
    }

}
