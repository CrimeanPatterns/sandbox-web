<?php

namespace AwardWallet\ExtensionWorker\Commands;

class NewTabRequest {
    public $url;
    public $active;

    public function __construct($url, $active) {
        $this->url = $url;
        $this->active = $active;
    }
}
