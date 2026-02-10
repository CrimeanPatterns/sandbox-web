<?php

namespace AwardWallet\Common\Geo;

use AwardWallet\Common\Entity\Aircode;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class GeoAirportFinder
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function getNearestAirport(float $lat, float $lng, float $maxMiles) : ?Aircode
    {
        $airports = $this->findAirportsByGeoSquare($lat, $lng, $maxMiles);
        $this->logger->log(count($airports) ? Logger::INFO : Logger::DEBUG, "got " . count($airports) . " nearest airports for $lat,$lng within $maxMiles miles");
        if (count($airports) === 0) {
            return null;
        }

        usort($airports, function(Aircode $a, Aircode $b) use ($lat, $lng) : float {
            // airports with flight history go first
            $diff = (int)$b->haveFlightHistory() <=> (int)$a->haveFlightHistory();
            if ($diff !== 0) {
                return $diff;
            }
            // then select by distance
            return Geo::distance($lat, $lng, $a->getLat(), $a->getLng()) <=> Geo::distance($lat, $lng, $b->getLat(), $b->getLng());
        });

        $result = $airports[0];
        $distance = Geo::distance($lat, $lng, $result->getLat(), $result->getLng());
        if ($distance > $maxMiles) {
            return null;
        }
        $distance = round($distance, 1);
        $this->logger->info("selected {$result->getAircode()} at {$result->getLat()},{$result->getLng()} nearest airport ({$distance} miles) for $lat,$lng");
        return $result;
    }

    /**
     * @return Aircode[]
     */
    public function findAirportsByGeoSquare(float $lat, float $lng, float $square, int $limit = null)
    {
        list($conditions, $paramsList) = Geo::getSquareGeofenceSQLCondition(
            $lat,
            $lng,
            'a.lat',
            'a.lng',
            true,
            $square
        );

        $values = [];
        foreach ($paramsList as list($name, $value, $type)) {
            $values[$name] = $value;
        }
//        $conditions .= " and a.classification <= 4";

        $q = $this->entityManager->createQuery("select a from ServiceBundle:Aircode a where {$conditions}");
        $q->setMaxResults($limit);
        return $q->execute($values);
    }


}
