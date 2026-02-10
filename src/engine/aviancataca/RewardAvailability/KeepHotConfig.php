<?php

namespace AwardWallet\Engine\aviancataca\RewardAvailability;

use AwardWallet\Common\Selenium\HotSession\KeepActiveHotConfig;

class KeepHotConfig extends KeepActiveHotConfig
{
    use \SeleniumCheckerHelper;

    private $closeOldSession = false;

    public function isActive(): bool
    {
        return true;
    }

    public function getCountToKeep(): int
    {
        return 20;
    }

    public function getInterval(): int
    {
        // refresh every N minutes
        return 10; // 10 minutes and ask continue
    }

    public function getLimitLifeTime(): ?int
    {
        return 45;
    }

    public function getAfterDateTime(): ?int
    {
        // все горячие созданные до указанного времени будут закрыты
        return null;
    }

    public function run(): bool
    {
        $this->logger->debug('[currentUrl]: ' . $this->http->currentUrl());

        $this->http->GetURL('https://www.lifemiles.com/account/overview');
        $lmNumber = $this->waitForElement(\WebDriverBy::xpath("//div[@data-cy='OverviewCardLmNumberDiv']"), 30);
        $this->saveResponse();

        if (!$lmNumber) {
            $this->logger->debug('unexpected check page');

            return false;
        }

        return true;
    }
}
