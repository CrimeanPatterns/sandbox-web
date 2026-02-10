<?php

namespace AwardWallet\ExtensionWorker;

class GotoUrlRequest {

    public int $tabId;
    public int $frameId;
    public string $url;

    public function __construct(int $tabId, int $frameId, string $url)
    {
        $this->tabId = $tabId;
        $this->frameId = $frameId;
        $this->url = $url;
    }

}
