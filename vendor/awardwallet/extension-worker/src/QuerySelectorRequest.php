<?php

namespace AwardWallet\ExtensionWorker;

class QuerySelectorRequest {

    public int $tabId;
    public string $selector;
    public bool $all;
    public string $method;
    public ?string $contextElementId;
    public bool $visible;
    public bool $notEmptyString;
    public bool $allFrames;
    public int $frameId;

    public function __construct(string $selector, bool $all, int $tabId, string $method, ?string $contextElementId, bool $visible, bool $notEmptyString, bool $allFrames, int $frameId)
    {
        $this->tabId = $tabId;
        $this->all = $all;
        $this->selector = $selector;
        $this->method = $method;
        $this->contextElementId = $contextElementId;
        $this->visible = $visible;
        $this->notEmptyString = $notEmptyString;
        $this->allFrames = $allFrames;
        $this->frameId = $frameId;
    }

}
