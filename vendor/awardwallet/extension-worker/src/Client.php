<?php

namespace AwardWallet\ExtensionWorker;

use AwardWallet\ExtensionWorker\Commands\CompleteRequest;
use AwardWallet\ExtensionWorker\Commands\NewTabRequest;
use Psr\Log\LoggerInterface;

class Client {

    private Communicator $communicator;
    private LoggerInterface $logger;
    private FileLogger $fileLogger;

    public function __construct(Communicator $communicator, LoggerInterface $logger, FileLogger $fileLogger) {
        $this->communicator = $communicator;
        $this->logger = $logger;
        $this->fileLogger = $fileLogger;
    }

    public function newTab($url, $active) : Tab
    {
        $extensionRequest = new ExtensionRequest("newTab", new NewTabRequest($url, $active));
        $response = $this->communicator->sendMessageToExtension($extensionRequest);
        return new Tab($response["tabId"], $this->communicator, 0, $this->logger, $this->fileLogger);
    }

    public function complete(?string $error = null) : void
    {
        $extensionRequest = new ExtensionRequest("complete", new CompleteRequest($error));
        $this->communicator->sendMessageToExtension($extensionRequest);
    }

}
