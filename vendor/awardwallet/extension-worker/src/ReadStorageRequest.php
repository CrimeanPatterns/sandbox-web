<?php

namespace AwardWallet\ExtensionWorker;

class ReadStorageRequest {

    public int $tabId;
    public int $frameId;
    public string $itemName;

    public function __construct(int $tabId, int $frameId, string $itemName)
    {
        $this->tabId = $tabId;
        $this->frameId = $frameId;
        $this->itemName = $itemName;
    }

}
