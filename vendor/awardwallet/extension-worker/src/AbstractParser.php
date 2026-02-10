<?php

namespace AwardWallet\ExtensionWorker;

use Psr\Log\LoggerInterface;

abstract class AbstractParser
{

    protected LoggerInterface $logger;
    protected FileLogger $fileLogger;
    protected WarningLogger $warningLogger;
    protected ProviderInfo $providerInfo;

    public function __construct(LoggerInterface $logger, FileLogger $fileLogger, WarningLogger $warningLogger, ProviderInfo $providerInfo)
    {
        $this->logger = $logger;
        $this->warningLogger = $warningLogger;
        $this->fileLogger = $fileLogger;
        $this->providerInfo = $providerInfo;

        ParserFunctions::load();
    }

}