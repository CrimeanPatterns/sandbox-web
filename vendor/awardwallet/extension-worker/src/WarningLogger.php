<?php

namespace AwardWallet\ExtensionWorker;

use Psr\Log\LoggerInterface;

class WarningLogger
{

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /** @var string[] */
    private array $warnings = [];

    public function addWarning(string $message) : void
    {
        $this->logger->info("addWarning: $message");
        $this->warnings[] = $message;
    }

    /**
     * @return string[]
     */
    public function getWarnings() : array
    {
        return $this->warnings;
    }

}