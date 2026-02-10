<?php

namespace AwardWallet\Common\Airport;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class AirportTime
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var LoggerInterface
     */
    private $logger;

    private $airportTimezones = [];

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * @return int - timestamp
     */
    public function convertToGmt(int $localTime, string $airportCode): int
    {
        if (isset($this->airportTimezones[$airportCode])) {
            $tz = $this->airportTimezones[$airportCode];
        } else {
            $tz = $this->connection->executeQuery("
                select TimeZoneLocation from AirCode where AirCode = ?",
                [$airportCode]
            )->fetchColumn();
            if ($tz === false) {
                $tz = $this->connection->executeQuery("
                select TimeZoneLocation from StationCode where StationCode = ?",
                    [$airportCode]
                )->fetchColumn();
            }

            if ($tz === false) {
                $this->logger->warning("Failed to get timezone for $airportCode");
                $tz = "UTC";
            }
            $this->airportTimezones[$airportCode] = $tz;
        }

        $d = new \DateTime(date("Y-m-d H:i:s", $localTime), new \DateTimeZone($tz));

        return $d->getTimestamp();
    }
}
