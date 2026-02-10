<?php

namespace AwardWallet\ExtensionWorker;

class ElementRequest {

    public string $tabId;
    public int $frameId;
    public string $elementId;
    public string $action;
    public $params;

    public function __construct(string $elementId, string $tabId, int $frameId, string $action, $params) {
        $this->tabId = $tabId;
        $this->frameId = $frameId;
        $this->elementId = $elementId;
        $this->action = $action;
        $this->params = $params;
    }
}
