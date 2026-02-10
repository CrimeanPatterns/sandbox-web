<?php

namespace AwardWallet\Engine\iberia\RewardAvailability;

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

        if (strpos($this->http->currentUrl(), '.iberia.com') === false) {
            $this->logger->error('unexpected page');

            return false;
        }

        try {
            $this->http->GetURL('https://www.iberia.com/us/search-engine-flights-with-avios/');
        } catch (\UnexpectedJavascriptException $e) {
            $this->logger->error("UnexpectedJavascriptException exception on saveResponse: " . $e->getMessage());
        } catch (\ScriptTimeoutException | \TimeOutException $e) {
            $this->logger->error("TimeOutException exception: " . $e->getMessage());
        }

        if ($this->waitForElement(\WebDriverBy::xpath("
                        //span[contains(text(), 'This site can’t be reached')]
                        | //h1[normalize-space()='Access Denied']
                        | //div[@id = 'Error_Subtitulo' and contains(text(), 'The connection was interrupted due to an error,')]
                    "), 0)
        ) {
            $this->logger->error('proxy failed');

            return false;
        }

        $cookies = $this->driver->manage()->getCookies();

        $oldToken = null;

        foreach ($cookies as $cookie) {
            if ($cookie['name'] === 'IBERIACOM_SSO_ACCESS') {
                $oldToken = $cookie['value'];
                $this->logger->debug(var_export($cookie, true), ['pre' => true]);
            }
        }

        if (!$oldToken) {
            $this->logger->error('token failed');

            return false;
        }

        $this->saveResponse();

        $this->keepSession(true);

        return true;
    }
}
