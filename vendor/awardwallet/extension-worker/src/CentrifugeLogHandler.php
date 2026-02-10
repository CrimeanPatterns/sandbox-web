<?php

namespace AwardWallet\ExtensionWorker;

use Monolog\Handler\AbstractHandler;
use Monolog\Handler\AbstractProcessingHandler;
use phpcent\Client;
use Monolog\Logger;

class CentrifugeLogHandler extends AbstractProcessingHandler
{

    private Client $centrifuge;
    private string $channel;

    public function __construct(Client $centrifuge, string $channel, $logLevel = Logger::DEBUG)
    {
        parent::__construct($logLevel);

        $this->centrifuge = $centrifuge;
        $this->channel = $channel;
        $this->setFormatter(new ParserLogFileFormatter());
    }

    protected function write(array $record): void
    {
        $this->centrifuge->publish($this->channel, $record);
    }
}