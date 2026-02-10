<?php

namespace AwardWallet\Common\Selenium\HotSession;

use AwardWallet\Common\Selenium\HotSession\KeepActiveHotSessionInterface;

class TestKeepHotConfigFactory extends KeepHotConfigFactory
{
    public function setParseMode(string $parseMode){
        $this->parseMode = $parseMode;
    }
}