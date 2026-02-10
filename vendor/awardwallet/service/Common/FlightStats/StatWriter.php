<?php


namespace AwardWallet\Common\FlightStats;


use Psr\Log\LoggerInterface;

class StatWriter
{

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var string
     */
    private $appName;

    public function __construct($appName, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->appName = $appName;
    }

    public function onCall(CallEvent $event)
    {
        $this->logger->info('FlightStats call', [
            'app' => $this->appName,
            'partner' => $event->getPartnerLogin(),
            'api' => $event->getMethod(),
            'reasons' => $event->getReason(),
            'appId' => $event->getAppId(),
        ]);
    }

}