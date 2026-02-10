<?php

namespace AwardWallet\ExtensionWorker;

class FetchRequest {

    public int $tabId;
    public int $frameId;
    public string $url;
    public array $options;

    public function __construct(int $tabId, int $frameId, string $url, array $options)
    {
        $this->tabId = $tabId;
        $this->frameId = $frameId;
        $this->url = $url;
        $this->options = $options;
    }

}
