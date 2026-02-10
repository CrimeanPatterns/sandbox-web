<?php

/**
 * Created by PhpStorm.
 * User: puzakov
 * Date: 03/08/16
 * Time: 13:48
 */

use Doctrine\DBAL\Connection;

class DatabaseHelper
{

    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param array $criteria ['FieldName' => 'Value', 'FieldName2' => 'Value2'...]
     * @param $unique
     * @return array|bool
     */
    public function getProvidersBy(array $criteria, $unique = false)
    {
        if(empty($criteria))
            return false;

        $sql = "SELECT * FROM Provider WHERE ";
        $sqlInject = [];
        $params = [];
        foreach($criteria as $field => $value){
            $placeholder = ":".strtoupper($field);
            $sqlInject[] = $field." = ".$placeholder;
            $params[$placeholder] = $value;
        }

        $result = $this->connection->executeQuery($sql.implode(' AND ', $sqlInject), $params)->fetchAll();

        if ($unique) {
            if (sizeof($result) === 1) {
                return $result[0];
            }

            return false;
        }

        return $result;
    }

    /**
     * @param array $criteria
     * @param bool $partial
     * @param bool $unique
     * @return bool|mixed
     */
    public function getAirportBy(array $criteria, $partial = false, $unique = true)
    {
        if(empty($criteria))
            return false;

        $sql = "SELECT * FROM AirCode WHERE ";
        $sqlInject = [];
        $params = [];
        foreach($criteria as $field => $value){
            $placeholder = ":".strtoupper($field);
            if($partial === true){
                $sqlInject[] = $field." LIKE ".$placeholder;
                $params[$placeholder] = "%".$value."%";
            } else {
                $sqlInject[] = $field." = ".$placeholder;
                $params[$placeholder] = $value;
            }
        }

        $result = $this->connection->executeQuery($sql.implode(' AND ', $sqlInject), $params)->fetchAll();
        if ($unique && sizeof($result) === 1)
            return $result[0];
        return false;
    }

    /**
     * @param string $iata
     * @return bool|mixed
     */
    public function getFareClassesByAirlineCode(string $iata)
    {
        $sql = "SELECT f.FareClass,f.ClassOfService 
                FROM AirlineFareClass f, Airline a 
                WHERE a.AirlineID = f.AirlineID AND a.Code = :iataCode 
                ORDER BY f.ClassOfService";
        $params['iataCode'] = $iata;
        $result = $this->connection->executeQuery($sql, $params)->fetchAll();
        return $result;
    }

    /**
     * @param string $iata
     * @return bool|mixed
     */
    public function getFingerprints()
    {
        $sql = "SELECT f.Fingerprint 
                FROM Fingerprint f 
            ";
        $results = $this->connection->executeQuery($sql)->fetchAll();

        return $results;
    }

}
