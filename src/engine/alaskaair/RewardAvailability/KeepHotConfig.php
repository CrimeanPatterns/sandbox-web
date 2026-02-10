<?php

namespace AwardWallet\Engine\alaskaair\RewardAvailability;

use AwardWallet\Common\Selenium\HotSession\KeepActiveHotConfig;

class KeepHotConfig extends KeepActiveHotConfig
{
    use \SeleniumCheckerHelper;

    public function isActive(): bool
    {
        return true;
    }

    public function getCountToKeep(): int
    {
        return 10;
    }

    public function getInterval(): int
    {
        // refresh every N minutes
        return 15;
    }

    public function getLimitLifeTime(): ?int
    {
        // close after N minutes
        return 60;
    }

    public function getAfterDateTime(): ?int
    {
        // close before date (UTC)
        return null;
    }

    public function run(): bool
    {
        $this->logger->debug('[currentUrl]: ' . $this->http->currentUrl());

        if (strpos($this->http->currentUrl(), '.alaskaair.com') === false) {
            $this->http->Log('unexpected page');

            return false;
        }
        $this->logger->debug('[currentUrl]: ' . $this->http->currentUrl());

        $this->http->GetURL("https://www.alaskaair.com");
        $from = $this->waitForElement(\WebDriverBy::id('fromCity1'), 10);
        $this->logger->debug('[currentUrl]: ' . $this->http->currentUrl());
        $this->saveResponse();

        if (!$from) {
            $this->logger->debug('unexpected data on the page');

            return false;
        }
        $this->keepSession(true);

        return true;
    }
}
