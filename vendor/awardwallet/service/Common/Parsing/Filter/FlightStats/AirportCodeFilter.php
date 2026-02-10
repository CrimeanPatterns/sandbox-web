<?php


namespace AwardWallet\Common\Parsing\Filter\FlightStats;


use AwardWallet\Common\Itineraries\FlightPoint;
use AwardWallet\Common\Itineraries\FlightSegment;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class AirportCodeFilter implements TripSegmentFilterInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Connection
     */
    private $connection;

    /**
     * AirportCodeFilter constructor.
     * @param LoggerInterface $logger
     * @param Connection $connection
     */
    public function __construct(LoggerInterface $logger, Connection $connection)
    {
        $this->logger = $logger;
        $this->connection = $connection;
    }

    /**
     * фильтрует данные полученные от парсера, дополняет их, возвращает в том же формате что и получает.
     * providerCode - код провайдера по нашей базе
     * @param string $providerCode
     * @param FlightSegment $flightSegment
     * @return void
     */
    public function filterTripSegment($providerCode = '', FlightSegment $flightSegment)
    {
        $this->setAirportCode($flightSegment->departure);
        $this->setAirportCode($flightSegment->arrival);
    }

    /**
     * @param FlightPoint $flightPoint
     * @return void
     */
    private function setAirportCode(FlightPoint $flightPoint)
    {
        if('' != $flightPoint->airportCode) {
            $this->logger->debug('FlightPoint already contains airport code', ['flightPoint' => $flightPoint]);
            return;
        }
        if(null === $flightPoint->address) {
            $this->logger->debug('FlightPoint address property is undefined', ['flightPoint' => $flightPoint]);
            return;
        }

        if(!preg_match('/([^(]+)\((\w+)\)/', $flightPoint->address->text, $matches)) {
            $this->logger->notice("Failed to parse airport address: " . $flightPoint->address->text, ['flightPoint' => $flightPoint]);
            return;
        }

        $query = 'SELECT AirCode FROM AirCode WHERE airName LIKE :airName AND (state = :code or CityCode = :code)';
        $result = $this->connection->executeQuery($query, [':airName' => '%' . $matches[1] . '%', ':code' => $matches[2]])->fetchAll();
        if(1 === count($result)) {
            $flightPoint->airportCode = $result[0]['AirCode'];
            $this->logger->notice(
                'Airport code was found. ' . $result[0]['AirCode'],
                ['flightPoint' => $flightPoint]
            );
            return;
        }
        $message = "Got " . count($result) . " results for airport codes";
        $this->logger->notice($message, ['flightPoint' => $flightPoint]);
    }
}