<?php


namespace AwardWallet\Common\FlightStats;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Psr\Log\LoggerInterface;

class Cache
{

    const API_SCHEDULE = 1;
    const API_HISTORICAL = 2;

    private $enabled;

    /** @var Connection */
    private $con;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->enabled = false;
        $this->con = $connection;
        $this->logger = $logger;
        try {
            if ($connection->executeQuery('show tables like "FlightStatsCache"')->fetchColumn())
                $this->enabled = true;
        }
        catch(TableNotFoundException $e) {}
        catch(DBALException $e) {
            $this->logger->error($e);
        }
    }

    public function getSchedule(?string $depCode, ?string $arrCode, ?\DateTime $depDate, ?\DateTime $arrDate, ?string $fn, ?string $airline, bool $inc)
    {
        return $this->getData(self::API_SCHEDULE, $depCode, $arrCode, $depDate, $arrDate, $fn, $airline, $inc);
    }

    public function getHistorical(?string $depCode, ?string $arrCode, ?\DateTime $depDate, ?\DateTime $arrDate, ?string $fn, ?string $airline, bool $inc)
    {
        return $this->getData(self::API_HISTORICAL, $depCode, $arrCode, $depDate, $arrDate, $fn, $airline, $inc);
    }

    public function delete($api, $depCode, $arrCode, $depDate, $arrDate, $fn, $airline) {
        $query = 'select FlightStatsCacheID from FlightStatsCache where ';
        $cond = [];
        $params = [];
        $types = [];
        foreach([
                    [$api, 'Api', \PDO::PARAM_INT],
                    [$depCode, 'DepCode', \PDO::PARAM_STR],
                    [$arrCode, 'ArrCode', \PDO::PARAM_STR],
                    [$depDate, 'DepDate', 'datetime'],
                    [$arrDate, 'ArrDate', 'datetime'],
                    [$fn, 'FlightNumber', \PDO::PARAM_STR],
                    [$airline, 'AirlineCode', \PDO::PARAM_STR]] as list($val, $field, $type)) {
            if (is_null($val))
                $cond[] = sprintf('%s is null', $field);
            else {
                $cond[] = sprintf('%s = ?', $field);
                $params[] = $val;
                $types[] = $type;
            }
        }
        $query .= implode(' and ', $cond);
        if ($this->enabled) {
            $ids = $this->con->executeQuery($query, $params, $types)->fetchAll(\PDO::FETCH_COLUMN);
            if (count($ids) === 1)
                $this->con->delete('FlightStatsCache', ['FlightStatsCacheID' => $ids[0]], [\PDO::PARAM_INT]);
        }
    }

    private function getData($api, ?string $depCode, ?string $arrCode, ?\DateTime $depDate, ?\DateTime $arrDate, ?string $fn, ?string $airline, bool $inc)
    {
        $query = 'select FlightStatsCacheID as ID, Response from FlightStatsCache where ';
        $cond = [];
        $params = [];
        $types = [];
        foreach([
            [$api, 'Api', \PDO::PARAM_INT],
            [$depCode, 'DepCode', \PDO::PARAM_STR],
            [$arrCode, 'ArrCode', \PDO::PARAM_STR],
            [$depDate, 'DepDate', 'datetime'],
            [$arrDate, 'ArrDate', 'datetime'],
            [$fn, 'FlightNumber', \PDO::PARAM_STR],
            [$airline, 'AirlineCode', \PDO::PARAM_STR]] as list($val, $field, $type)) {
            if (is_null($val))
                $cond[] = sprintf('%s is null', $field);
            else {
                $cond[] = sprintf('%s = ?', $field);
                $params[] = $val;
                $types[] = $type;
            }
        }
        $query .= implode(' and ', $cond);
        if ($this->enabled && !empty($r = $this->con->executeQuery($query, $params, $types)->fetch(\PDO::FETCH_ASSOC))) {
            $this->logger->info('FS cache hit', ['query' => sprintf('%s/%s/%s/%s/%s/%s/%s', $api, $depCode, $arrCode, $depDate ? $depDate->getTimestamp() : null, $arrDate ? $arrDate->getTimestamp() : null, $fn, $airline)]);
            if ($inc)
                $this->con->executeUpdate('update FlightStatsCache set Hits = Hits + 1 where FlightStatsCacheID = ?',
                    [$r['ID']], [\PDO::PARAM_INT]);
            return $r['Response'];
        }
        else
            return null;
    }

    public function addSchedule(?string $depCode, ?string $arrCode, ?\DateTime $depDate, ?\DateTime $arrDate, ?string $fn, ?string $airline, string $data)
    {
        $this->addData(self::API_SCHEDULE, $depCode, $arrCode, $depDate, $arrDate, $fn, $airline, $data);
    }

    public function addHistorical(?string $depCode, ?string $arrCode, ?\DateTime $depDate, ?\DateTime $arrDate, ?string $fn, ?string $airline, string $data)
    {
        $this->addData(self::API_HISTORICAL, $depCode, $arrCode, $depDate, $arrDate, $fn, $airline, $data);
    }

    private function addData($api, $depCode, $arrCode, $depDate, $arrDate, $fn, $airline, $data)
    {
        if (!$this->enabled)
            return;
        $params = [];
        $types = [];
        foreach([
            [$api, 'Api', \PDO::PARAM_INT],
            [$depCode, 'DepCode', \PDO::PARAM_STR],
            [$arrCode, 'ArrCode', \PDO::PARAM_STR],
            [$depDate, 'DepDate', 'datetime'],
            [$arrDate, 'ArrDate', 'datetime'],
            [$fn, 'FlightNumber', \PDO::PARAM_STR],
            [$airline, 'AirlineCode', \PDO::PARAM_STR]] as list($val, $field, $type)) {
            if (!is_null($val)) {
                $params[$field] = $val;
                $types[] = $type;
            }
        }
        $params['Response'] = $data;
        $types[] = \PDO::PARAM_STR;
        $params['UpdateDate'] = new \DateTime();
        $types[] = 'datetime';
        $this->con->insert('FlightStatsCache', $params, $types);
    }

}