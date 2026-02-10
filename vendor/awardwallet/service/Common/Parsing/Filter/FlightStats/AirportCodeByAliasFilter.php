<?php


namespace AwardWallet\Common\Parsing\Filter\FlightStats;


use AwardWallet\Common\Itineraries\FlightPoint;
use AwardWallet\Common\Itineraries\FlightSegment;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class AirportCodeByAliasFilter implements TripSegmentFilterInterface
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
     * @param FlightSegment $flightSegment
     * @param string $providerCode
     * @return void
     */
    public function filterTripSegment($providerCode, FlightSegment $flightSegment)
    {
        $flightSegment->departure->airportCode = $this->setAirportCode($providerCode, $flightSegment->departure);
        $flightSegment->arrival->airportCode = $this->setAirportCode($providerCode, $flightSegment->arrival);
    }

    private function setAirportCode($providerCode, FlightPoint $flightPoint)
    {
        if('' != $flightPoint->airportCode) {
            $this->logger->debug('FlightPoint already contains airport code', ['flightPoint' => $flightPoint]);
            return $flightPoint->airportCode;
        }
        if(null === $flightPoint->address) {
            return $flightPoint->airportCode;
        }
        if('' == $flightPoint->address->text) {
            $this->logger->notice('Address text is empty', ['providerCode' => $providerCode, 'flightPoint' => $flightPoint]);
            return $flightPoint->airportCode;
        }

        $query = 'SELECT AirportCode FROM ProviderAirportAlias WHERE ProviderID = :providerId AND Alias LIKE :alias';
        $result = $this->connection->executeQuery($query, [':providerId' => $providerCode, 'alias' => '%' . $flightPoint->address->text . '%'])->fetch();
        if(false === $result) {
            $this->logger->notice('Could not find an alias for ' . $flightPoint->address->text, ['providerCode' => $providerCode, 'flightPoint' => $flightPoint]);
            return $flightPoint->airportCode;
        }

        $this->logger->info('Assigned airport code ' . $result['AirportCode'], ['providerCode' => $providerCode, 'flightPoint' => $flightPoint]);
        return $result['AirportCode'];
    }
}