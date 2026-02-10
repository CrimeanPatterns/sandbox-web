<?php

use Facebook\WebDriver\Exception\UnknownErrorException;
use Facebook\WebDriver\Remote\RemoteWebDriver;

class SeleniumStarter
{

    public const CONTEXT_NEW_WEBDRIVER = 'new-webdriver';
    public const CONTEXT_WEBDRIVER_PATH = 'webdriver-path';
    public const CONTEXT_BROWSER_FAMILY = 'browser-family';
    public const CONTEXT_BROWSER_VERSION = 'browser-version';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var string
     */
    private $share;
    /**
     * @var ChromiumStarter
     */
    private $chromiumStarter;
    /**
     * @var FirefoxStarter
     */
    private $firefoxStarter;
    /**
     * @var string
     */
    private $startupUrl;

    public function __construct(\Psr\Log\LoggerInterface $logger, string $share, ChromiumStarter $chromiumStarter, FirefoxStarter $firefoxStarter, string $startupUrl)
    {
        $this->logger = $logger;
        $this->share = $share;
        $this->chromiumStarter = $chromiumStarter;
        $this->firefoxStarter = $firefoxStarter;
        $this->startupUrl = $startupUrl;
    }

    public function createSession(SeleniumServer $server, SeleniumFinderRequest $request, SeleniumOptions $options, Callable $onWebDriverCreated = null) : SeleniumConnection
    {
        $startTime = microtime(true);

        if ($options->userAgent === null && isset($options->fingerprint['fp2']['userAgent'])) {
            $options->userAgent = $options->fingerprint['fp2']['userAgent'];
            $this->logger->info("extracted userAgent from fingerprint: " . $options->userAgent);
        }

        $downloadFolder = "/tmp/seldownloads" . bin2hex(random_bytes(5)) . time();

        if ($request->getBrowser() === SeleniumFinderRequest::BROWSER_FIREFOX) {
            $starter = $this->firefoxStarter;
        }
        else {
            $starter = $this->chromiumStarter;
        }

        $sessionRequest = $starter->prepareSession($options, $downloadFolder, $request);
        $capabilities = $sessionRequest->getCapabilities()->toArray();
        $path = $sessionRequest->getContext()[self::CONTEXT_WEBDRIVER_PATH] ?? '/wd/hub';
        $webDriverClass = SeleniumDebugWebDriver::class;

        $startFunc = function () use ($server, $capabilities, $path, $onWebDriverCreated, &$webDriverClass, $options, $request) {
            $webDriver = $webDriverClass::createWithoutSession(
                'http://' . $server->host . ':' . $server->port . $path,
                60000,
                new \AwardWallet\Common\Selenium\ServerInfo($request)
            );
            $webDriver->setServerInfo(new \AwardWallet\Common\Selenium\ServerInfo($request));

            // typically debug proxy hook
            if ($onWebDriverCreated !== null) {
                call_user_func($onWebDriverCreated, $webDriver);
            }
            
            $capabilities["loggingContext"] = $options->loggingContext;

            try {
                $webDriver->createNewSession($capabilities);
            } catch (
                    WebDriverCurlException
                    | Facebook\WebDriver\Exception\WebDriverCurlException
                    | SessionNotCreatedException
                    | Facebook\WebDriver\Exception\SessionNotCreatedException
                    $e
            ) {
                $this->logger->notice("failed to create selenium session: " . \AwardWallet\Common\Selenium\Util::cleanupCurlError($e->getMessage()));
                usleep(random_int(300000, 2000000));
                $this->logger->notice("try again: createNewSession");
                $webDriver->createNewSession($capabilities);
            }
            return $webDriver;
        };

        if (isset($sessionRequest->getContext()[self::CONTEXT_NEW_WEBDRIVER])) {
            $webDriverClass = \AwardWallet\Common\Selenium\DebugWebDriver::class;
            $startFunc = function () use ($server, $capabilities, $path, $startFunc) {
                return new \AwardWallet\Common\Selenium\OldWebDriverTranslator(
                    $startFunc()
                );
            };
        }

        try {
            $webDriver = $startFunc();
        }
        catch (
                UnknownErrorException
                | Facebook\WebDriver\Exception\UnknownErrorException
                | \UnknownServerException
                | Facebook\WebDriver\Exception\UnknownServerException
                | WebDriverException
                | Facebook\WebDriver\Exception\WebDriverException
                $exception
        ) {
            $this->logger->notice("failed to start webDriver. repeat once. error: " . \AwardWallet\Common\Selenium\Util::cleanupCurlError($exception->getMessage()));
            usleep(random_int(750000, 2500000));
            $webDriver = $startFunc();
        }

        $sessionId = $webDriver->getSessionID();

        if (method_exists($webDriver->getCommandExecutor(), 'setRequestTimeout')) // TODO: remove after upgrading all projects
            $webDriver->getCommandExecutor()->setRequestTimeout(180000);
        $webDriver->getCommandExecutor()->setConnectionTimeout(180000);
        $webDriver->manage()->timeouts()->pageLoadTimeout(90);

        // typically set proxy auth
        if($sessionRequest->getOnDriverCreated() !== null)
            call_user_func($sessionRequest->getOnDriverCreated(), $webDriver);

        // sometimes firefox 59 starts on about:blank, we need some domain for control through extension
        if ($webDriver->getCurrentURL() === 'about:blank' && $this->startupUrl !== "about:blank") {
            $this->logger->info("started on about:blank, correcting");
            $webDriver->get($this->startupUrl . "?text=" . urlencode($options->startupText));
        }

        return new SeleniumConnection(
            $webDriver, 
            $sessionId, 
            $server->host, 
            $server->port, 
            $path, 
            $downloadFolder, 
            $request->getBrowser(), 
            $request->getVersion(), 
            array_merge($sessionRequest->getContext(), [
                self::CONTEXT_BROWSER_FAMILY => $request->getBrowser(),
                self::CONTEXT_BROWSER_VERSION => $request->getVersion(),
            ])
        );
    }

}
