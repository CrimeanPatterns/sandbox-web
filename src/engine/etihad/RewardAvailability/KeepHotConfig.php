<?php

namespace AwardWallet\Engine\etihad\RewardAvailability;

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
        // close after N minutes
        return null;
    }

    public function getAfterDateTime(): ?int
    {
        // все горячие созданные до указанного времени будут закрыты
        if ($this->closeOldSession) {
            $closeBefore = date('Y-m-d') . ' 11:45'; // UTC

            return strtotime($closeBefore);
        }

        return null;
    }

    public function run(): bool
    {
        $this->logger->debug('[currentUrl]: ' . $this->http->currentUrl());

        if (strpos($this->http->currentUrl(), '.etihad.com') === false) {
            $this->http->Log('unexpected page');

            return false;
        }
        $curUrl = $this->http->currentUrl();
        $this->logger->debug('[currentUrl]: ' . $this->http->currentUrl());

        $btn = $this->waitForElement(\WebDriverBy::xpath("//button[normalize-space(@aria-label)='Go home']"), 10);

        if (!$btn) {
            $this->http->GetURL('https://www.etihad.com/en-us/');
        } else {
            $this->logger->debug('[click]');
            $btn->click();
            sleep(2);

            if ($this->http->currentUrl() === $curUrl) {
                $this->http->GetURL('https://www.etihad.com/en-us/');
            }
        }
        $this->logger->debug('[currentUrl]: ' . $this->http->currentUrl());
        $this->saveResponse();

        $inp = $this->waitForElement(\WebDriverBy::xpath("//label[normalize-space(@for)='roundTripOrigin']"), 10);

        if (!$inp) {
            $this->logger->debug('page not load');

            return false;
        }
        $this->keepSession(true);

        return true;
    }
}
