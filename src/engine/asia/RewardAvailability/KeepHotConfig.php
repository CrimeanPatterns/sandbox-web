<?php

namespace AwardWallet\Engine\asia\RewardAvailability;

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
        return 20;
    }

    public function getInterval(): int
    {
        // refresh every N minutes
        return 15; // 8 minutes and ask continue
    }

    public function getLimitLifeTime(): ?int
    {
        // close after N minutes
        return 40;
    }

    public function getAfterDateTime(): ?int
    {
        return null;
    }

    public function run(): bool
    {
        $curUrl = $this->http->currentUrl();
        $this->logger->debug('[currentUrl]: ' . $curUrl);

        if (strpos($curUrl, 'cathaypacific.com') === false) {
            $this->logger->debug('unexpected page');

            return false;
        }

        // <h1 tabindex="0" translate="" class="ng-scope ng-binding">Your session will expire in:</h1>
        // <button type="button" class="btn btn-confirm ng-scope ng-binding" ng-click="KeepAlive.continueBtn();" translate="">Continue</button>
        // https://book.cathaypacific.com/CathayPacificAwardV3/dyn/air/booking/availability

        $this->http->GetURL('https://www.cathaypacific.com/cx/en_US/membership/my-account.html');

        if (!$this->waitForElement(\WebDriverBy::xpath("//span[@class='welcomeLabel']"), 10)) {
            $this->logger->debug('hot session expired');

            return false;
        }

        return true;
    }
}
