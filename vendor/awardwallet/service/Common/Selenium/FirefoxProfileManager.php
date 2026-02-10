<?php
namespace AwardWallet\Common\Selenium;

use Psr\Log\LoggerInterface;

class FirefoxProfileManager
{

    /**
     * @var \HttpDriverInterface
     */
    private $httpDriver;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(\HttpDriverInterface $httpDriver, LoggerInterface $logger)
    {
        $this->httpDriver = $httpDriver;
        $this->logger = $logger;
    }

    public function getProfileAndStop(\SeleniumConnection $connection) : ?string
    {
        $this->logger->info("trying to get firefox profile from {$connection->getHost()}:{$connection->getPort()}");
        $sessionInfo = $this->seleniumRequest(new \HttpDriverRequest($connection->getSeleniumEndpoint() . "/session/{$connection->getWebDriver()->getSessionID()}"));
        if($sessionInfo === null) {
            return null;
        }
        if (!isset($sessionInfo['moz:processID'])) {
            throw new \Exception("moz:processID not found in session info: " . json_encode($sessionInfo));
        }
        if (!isset($sessionInfo['moz:profile'])) {
            throw new \Exception("moz:profile not found in session info: " . json_encode($sessionInfo));
        }

        $response = $this->request(new \HttpDriverRequest($connection->getAwEndpoint() . "/kill/{$sessionInfo['moz:processID']}"));
        if ($response === null) {
            return null;
        }
        $profile = $this->request(new \HttpDriverRequest($connection->getAwEndpoint() . "/zip{$sessionInfo['moz:profile']}"));
        if ($profile === null) {
            return null;
        }
        $this->request(new \HttpDriverRequest($connection->getAwEndpoint() . "/rm{$sessionInfo['moz:profile']}"));

        return $profile;
    }

    private function seleniumRequest(\HttpDriverRequest $request) : ?array
    {
        $result = $this->jsonRequest($request);

        if ($result === null) {
            return null;
        }

        if (($result["state"] ?? "") !== "success") {
            throw new \Exception("Invalid selenium response: " . json_encode($result));
        }

        return $result["value"];
    }

    private function jsonRequest(\HttpDriverRequest $request) : ?array
    {
        $response = $this->request($request);

        if ($response === null) {
            return null;
        }

        $result = json_decode($response, true);

        if (!is_array($result)) {
            $this->logger->notice("error decoding response: " . $response);
            return null;
        }

        return $result;
    }

    private function request(\HttpDriverRequest $request) : ?string
    {
        $response = $this->httpDriver->request($request);

        if ($response->httpCode < 200 || $response->httpCode > 299) {
            $this->logger->notice("error on requesting {$request->url}: " . $response->toString());
            return null;
        }

        return $response->body;
    }

}
