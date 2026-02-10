<?php

namespace AwardWallet\Engine\tapportugal\RewardAvailability;

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
        return 10;
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

        if (strpos($this->http->currentUrl(), '.flytap.com') === false) {
            $this->http->Log('unexpected page');

            return false;
        }
        $this->http->GetURL("https://booking.flytap.com/booking");

        if (!$this->waitForElement(\WebDriverBy::xpath("//a//*[@alt='TAP Air Portugal logo']|//*[@class='flight-actions__item flight-search']"),
            30)) {
            $this->logger->error('page not load');

            return false;
        }
        $script = "return sessionStorage.getItem('userData');";
        $this->logger->debug("[run script]");
        $this->logger->debug($script, ['pre' => true]);
        $userData = $this->driver->executeScript($script);

        if (empty($userData)) {
            $this->logger->error("no logged in data");

            return false;
        }
        $this->saveResponse();

        $this->keepSession(true);

        return true;
    }
}
