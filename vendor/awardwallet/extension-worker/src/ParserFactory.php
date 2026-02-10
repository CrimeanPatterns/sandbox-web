<?php

namespace AwardWallet\ExtensionWorker;

use Psr\Log\LoggerInterface;

class ParserFactory
{

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getParser(string $providerCode, FileLogger $fileLogger, WarningLogger $warningLogger, SelectParserRequest $selectParserRequest, ProviderInfo $providerInfo) : ?AbstractParser
    {

        $className = $this->getParserClassName($providerCode, $selectParserRequest);
        if (!class_exists($className)) {
            $this->logger->warning("extension parser $className not found");

            return null;
        }

        return new $className($this->logger, $fileLogger, $warningLogger, $providerInfo);
    }

    private function getParserClassName(string $providerCode, SelectParserRequest $selectParserRequest) : string
    {
        $result = 'AwardWallet\\Engine\\' . $providerCode . '\\' . ucfirst($providerCode) . 'Extension';

        $parserSelectorClass = 'AwardWallet\\Engine\\' . $providerCode . '\\' . ucfirst($providerCode) . 'ExtensionParserSelector';
        if (($selectParserRequest->getLogin2() || $selectParserRequest->getLogin3()) && class_exists($parserSelectorClass)) {
            /** @var ParserSelectorInterface $selector */
            $selector = new $parserSelectorClass();

            $result = $selector->selectParser($selectParserRequest, $this->logger);
            if (!class_exists($result)) {
                throw new \Exception("invalid parser selector result, class not exists: $result");
            }
        }

        return $result;
    }

    public function getParserOptions(string $providerCode) : ?ParseAllowedInterface
    {
        $className = 'AwardWallet\\Engine\\' . $providerCode . '\\' . ucfirst($providerCode) . 'ExtensionOptions';
        if (!class_exists($className)) {
            return null;
        }

        return new $className($this->logger);
    }

}