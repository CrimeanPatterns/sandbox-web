<?php
namespace AwardWallet\Common\Monolog\ActivationStrategy;

use Monolog\Handler\FingersCrossed\ActivationStrategyInterface;
use Monolog\Logger;

class DevNotificationActivationStrategy implements ActivationStrategyInterface
{

    public function isHandlerActivated(array $record)
    {
        return
            $record['level'] === Logger::ALERT
            &&
            $record['message'] === "dev notification"
        ;
    }

}