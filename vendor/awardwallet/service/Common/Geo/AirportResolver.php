<?php

namespace AwardWallet\Common\Geo;

use Doctrine\DBAL\Connection;

class AirportResolver
{

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function lookupAirPort(string $code): ?array
    {
        if (!isset($code) || ($code == '')) {
            return null;
        }

        $code = str_replace(array('"', "'"), '', $code);

        $sql = "SELECT * FROM AirCode WHERE AirCode = :AIRCODE";
        $result = $this->connection->executeQuery($sql, [':AIRCODE' => $code])->fetch();
        if (!$result) {
            $result = $this->connection->executeQuery("select * from AirCode 
            where CityCode = :CITYCODE order by Classification limit 1", ["CITYCODE" => $code])->fetch();
            if (!$result) {
                return null;
            }
            $result['AirCode'] = $code;
        }

        return $this->buildAirPortName($result);
    }

    private function buildAirPortName(array $info): array
    {
        $info['Name'] = $info['CityName'] . " (" . ucwords(strtolower($info['AirName'])) . ")";
        if (($info['StateName'] != '') && ($info['State'] != '')) {
            $info['Name'] .= ", " . $info['State'];
        }
        if ($info['CountryCode'] !== 'US') {
            $info['Name'] .= ", " . ucwords(strtolower($info['CountryName']));
        }
        $info["CityStateName"] = ucwords(strtolower($info['CityName']));
        if (($info['StateName'] != '') && ($info['State'] != '')) {
            $info['CityStateName'] .= ", " . $info['State'];
        }

        return $info;
    }

}