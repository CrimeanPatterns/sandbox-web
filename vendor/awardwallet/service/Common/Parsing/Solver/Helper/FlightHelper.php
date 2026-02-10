<?php

namespace AwardWallet\Common\Parsing\Solver\Helper;


use AwardWallet\Common\Parsing\Solver\Exception;
use AwardWallet\Common\Parsing\Solver\Extra\AircraftData;
use AwardWallet\Common\Parsing\Solver\Extra\AirlineData;
use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class FlightHelper {

	/**
	 * @var Connection $connection
	 */
	private $connection;

	/**
	 * @var LoggerInterface $logger
	 */
	private $logger;
	/**
	 * @var AirCodeAliasProvider $airCodeAliasProvider
	 */
	private $airCodeAliasProvider;
	/**
	 * @var ExtraHelper $eh
	 */
	private $eh;

	private $tzCache = [];
    /**
     * @var AddressHelper $ah
     */
    private $ah;

    private static $faaProviders = ['allegiant'];

    public function __construct(Connection $connection,
								LoggerInterface $logger,
								AirCodeAliasProvider $airCodeAliasProvider,
								ExtraHelper $eh,
								AddressHelper $ah)
    {
		$this->connection = $connection;
		$this->logger = $logger;
		$this->airCodeAliasProvider = $airCodeAliasProvider;
		$this->eh = $eh;
        $this->ah = $ah;
    }

	public function checkAirCode($code, Extra $extra): ?string
    {
        $iata = $this->connection->executeQuery('select AirCode from AirCode where AirCode = ?', [$code])->fetchColumn();
        $faa = $this->connection->executeQuery('select AirCode from AirCode where Faa = ?', [$code])->fetchColumn();
        if ($faa && in_array($extra->provider->code, self::$faaProviders))
            return $faa;
        if ($iata)
            return $iata;
        if ($this->connection->executeQuery('select 1 from AirCode where CityCode = ?', [$code])->fetchColumn() !== false)
            return null;
        throw Exception::unknownAirCode($code);
    }

	public function solveAirCode($name, Extra $extra): ?string
    {
		if (!$name)
			return null;
		if ($extra->data->existsAirCode($name))
			return $extra->data->getAirCode($name);
        $solved = $this->lookupAirport($name, $extra->provider->id);
        $code = $extra->provider->code;
        if (!$solved && isset($extra->originalParserProvider) && $extra->provider->id !== $extra->originalParserProvider->id) {
            $solved = $this->lookupAirport($name, $extra->originalParserProvider->id);
            $code = $extra->originalParserProvider->code;
        }
		if ($solved) {
			$this->logger->info('resolved airport alias', ['component' => 'FlightHelper', 'alias' => $name, 'provider' => $code, 'aircode' => $solved]);
		}
		else {
			$this->logger->info('failed to resolve airport alias', ['component' => 'FlightHelper', 'alias' => $name, 'provider' => $extra->provider->code]);
		}
		$extra->data->addAirCode($name, $solved);
		return $solved;
	}

	public function solveAircraft($name, Extra $extra): ?AircraftData
    {
		if (!$name)
			return null;
		if ($extra->data->existsAircraft($name))
			return $extra->data->getAircraft($name);
		if ($solved = $this->lookupAircraft($name)) {
			$this->logger->info('resolved aircraft', ['component' => 'FlightHelper', 'alias' => $name, 'iata' => $solved->iataCode, 'name' => $solved->name]);
            $extra->data->addAircraft($name, $solved);
		}
		else {
			$this->logger->info('failed to resolve aircraft', ['component' => 'FlightHelper', 'alias' => $name]);
			$extra->data->nullAircraft($name);
		}
		return $solved;
	}

	private function lookupAirport($name, $providerId): ?string
    {
        $rows = $this->connection->executeQuery('select AirCode from AirCode where AirName = ? and CityName != AirName', [$name], [\PDO::PARAM_STR])->fetchAll(\PDO::FETCH_COLUMN,0);
        if (count($rows) === 1 && ($iata = array_shift($rows)))
            return $iata;
		return $this->airCodeAliasProvider->lookup($providerId, $name);
	}

	private function lookupAircraft($name): ?AircraftData
    {
		if ($row = $this->connection->fetchAssoc('select * from Aircraft where Name = ? or IataCode = ?', [$name, $name], [\PDO::PARAM_STR, \PDO::PARAM_STR]))
			return AircraftData::fromArray($row);
		return null;
	}

	public function parseTicketPrefix($ticket): ?AirlineData
    {
        $prefix = substr($ticket, 0, 3);
        if ($row = $this->connection->executeQuery('select a.Code, a.Name, a.ICAO from Airline a inner join AirlineTicketPrefix atp on a.AirlineID = atp.AirlineID where atp.Prefix = ?', [$prefix], [\PDO::PARAM_STR])->fetch(\PDO::FETCH_ASSOC))
            return AirlineData::fromArray($row);
        return null;
    }

	public function parseTicketingInfo($name, $code, Extra $extra) : ?AirlineData
    {
		if ($name && ($data = $this->eh->solveAirline($name, $extra)))
			return $data;
		if ($code && ($iata = $this->connection->executeQuery('select IATACode from Provider where Code = ?', [$code], [\PDO::PARAM_STR])->fetchColumn())) {
            if ($query = $this->connection->executeQuery('select Code, ICAO, Name from Airline where Code = ? order by Active desc', [$iata])->fetch(\PDO::FETCH_ASSOC))
                return AirlineData::fromArray($query);
        }
		return null;
	}

	public function isWetlease($code)
    {
		return $this->connection->fetchColumn('select 1 from Provider where IATACode = ?', [$code], 0, [\PDO::PARAM_STR]) === false;
	}

	public function getAirportOffset($code,?bool $checkStation = false)
    {
        if (!$code)
            return null;
		if (!array_key_exists($code, $this->tzCache)) {
			$offset = null;
			$name = $this->connection->fetchColumn('select TimeZoneLocation from AirCode where AirCode = ?', [$code]);
            if ($name === false && $checkStation){
                $name = $this->connection->fetchColumn('select TimeZoneLocation from StationCode where StationCode = ?', [$code]);
            }
			try {
				$tz = new \DateTimeZone($name);
				$dt = new \DateTime();
				$offset = $tz->getOffset($dt);
			}
			catch(\Exception $e) {}
			if (!isset($offset)) {
				$tag = $this->ah->parseAirport($code);
                if (!empty($tag['TimeZoneLocation'])) {
                    try {
                        $tz = new \DateTimeZone($tag['TimeZoneLocation']);
                        $dt = new \DateTime();
                        $offset = $tz->getOffset($dt);
                    }
                    catch(\Exception $e) {
                        $offset = 0;
                    }
                }
			}
			$this->tzCache[$code] = $offset;
		}
		return $this->tzCache[$code];
	}

}