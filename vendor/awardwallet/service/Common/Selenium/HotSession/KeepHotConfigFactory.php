<?php

namespace AwardWallet\Common\Selenium\HotSession;

use AwardWallet\Common\Selenium\HotSession\KeepActiveHotSessionInterface;

class KeepHotConfigFactory {

    protected $parseMode;

    public function __construct(?string $parseMode){
        $this->parseMode = $parseMode;
    }
    
    public function load($providerCode): ?KeepActiveHotSessionInterface {
        $className = $this->getClassKeepHotConfig($providerCode);
        if (!class_exists($className) || !is_a($className, KeepActiveHotSessionInterface::class, true))
            return null;
        $obj = new $className($this->parseMode);
//        $obj->services = $this->hotPoolServices;

        return $obj;
    }

    private function getClassKeepHotConfig(string $providerCode): string
    {
        return sprintf('AwardWallet\\Engine\\%s\\RewardAvailability\\KeepHotConfig', $providerCode);
    }

}