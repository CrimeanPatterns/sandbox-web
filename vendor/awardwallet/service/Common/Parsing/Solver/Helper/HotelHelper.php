<?php

namespace AwardWallet\Common\Parsing\Solver\Helper;

use AwardWallet\Common\Geo\Geoapify\Client;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;

class HotelHelper
{

    /** @var Client  */
    private $gfy;
    
    /** @var Connection  */
    private $db;
    
    /** @var LoggerInterface  */
    private $logger;

    private $ignore = [
        'Caesars Palace',
    ];

    public function __construct(Client $gfy, Connection $db, LoggerInterface $logger)
    {
        $this->gfy = $gfy;
        $this->db = $db;
        $this->logger = $logger;
    }

    public function lookupHotel(string $name): array
    {
        if (in_array($name, $this->ignore)) {
            $this->logger->info('hotel dictionary lookup ignored');
            return [];
        }
        try {
            $coords = $this->db->executeQuery('select latitude, longitude from TpoHotel where name = ?', [$name])->fetchAllAssociative();
        }
        catch (Exception $exception) {
            $coords = [];
        }
        $this->logger->info('hotel dictionary lookup', ['success' => count($coords) === 1, 'count' => count($coords), 'query' => $name]);
        if (1 === count($coords) && !empty($found = $this->gfy->reverseGeoCode($coords[0]['latitude'], $coords[0]['longitude']))) {
            $found = array_shift($found);
            return [
                'Name' => $name,
                'Address' => $found->formattedAddress,
                'FoundAddress' => $found->formattedAddress,
                'AddressLine' => $found->detailedAddress['AddressLine'] ?? null,
                'City' => $found->detailedAddress['City'] ?? null,
                'State' => $found->detailedAddress['State'] ?? null,
                'Country' => $found->detailedAddress['Country'] ?? null,
                'CountryCode' => $found->detailedAddress['CountryCode'] ?? null,
                'PostalCode' => $found->detailedAddress['PostalCode'] ?? null,
                'Lat' => $found->lat,
                'Lng' => $found->lng,
                'TimeZoneLocation' => $found->tzId,
            ];
        }
        return [];
    }

}