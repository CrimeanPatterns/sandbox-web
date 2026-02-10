<?php


namespace AwardWallet\Common\Parsing\Solver\Helper;


use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class DataHelper {

	/**
	 * @var Connection
	 */
	protected $connection;
	/**
	 * @var LoggerInterface
	 */
	protected $logger;
	/**
	 * @var AddressHelper
	 */
	protected $ah;

    /**
     * @var HotelHelper
     */
    protected $hh;

	public function __construct(Connection $connection, LoggerInterface $logger, AddressHelper $ah, HotelHelper $hh) {
		$this->connection = $connection;
		$this->ah = $ah;
        $this->hh = $hh;
		$this->logger = $logger;
	}

	public function parseAirCode($code, Extra $extra) {
		if (!$code || strlen($code) !== 3)
			return false;
		if (!$extra->data->existsGeo($code)) {
            $airCode = $this->connection->executeQuery('select AirName, Lat, Lng from AirCode where AirCode = ?',
                [$code], [\PDO::PARAM_STR])->fetchAssociative();
			if ($airCode === false)
			    return false;
			$row = $this->ah->parseAirport($code);
			if (!empty($row['Lat'])) {
                if (!empty($airCode['Lat']) && !empty($airCode['Lng'])) {
                    $row['Lat'] = $airCode['Lat'];
                    $row['Lng'] = $airCode['Lng'];
                }
                $extra->data->addGeoArray($code, array_merge($this->getDefaultGeoArray($airCode['AirName']), $row));
            }
			else {
				$extra->data->nullGeo($code);
				$this->logger->error(sprintf('failed to find geotag for airport `%s`', $code), ['component' => 'DataHelper', 'aircode' => $code]);
			}
		}
		return true;
	}

    public function parseStationCode($code, $name, Extra $extra) {
        if (!$code || strlen($code) !== 3) {
            return false;
        }
        if (!$extra->data->existsGeo($code)) {
            $query = '
              SELECT StationName, AddressLine, CityName as City, StateName as State, Country, PostalCode, Lat, Lng, TimeZoneLocation
              FROM StationCode
              WHERE StationCode =  ?
            ';
            $row = $this->connection->executeQuery($query, [$code], [\PDO::PARAM_STR])->fetch();
            if (!$row) {
                return false;
//                throw Exception::unknownStationCode($code);
            }

            if (!empty($row['Lat']) && !empty($name)) {
                $maxSim = 0;
                foreach([$row['StationName'], $row['City']] as $cmp) {
                    if (!empty($cmp)) {
                        similar_text(strtolower($cmp), strtolower($name), $p);
                        if ($maxSim < $p) {
                            $maxSim = $p;
                        }
                    }
                }
                $this->logger->info('station code lookup debug', ['code' => $code, 'name' => $name, 'parsed' => $row, 'similarity' => $maxSim]);
                if ($maxSim > 50) {
                    $extra->data->addGeoArray($code, array_merge($this->getDefaultGeoArray($row['StationName']), $row));
                    return true;
                }
            }
            $extra->data->nullGeo($code);
            $this->logger->error(sprintf('failed to find geotag for station `%s`', $code),
                ['component' => 'DataHelper', 'stationcode' => $code]);
            return false;
        }
        return true;
    }

    public function parseAddress($address, Extra $extra, $tip = null) {
		if (!$address)
			return;
		if (!$extra->data->existsGeo($address)) {
			$row = $this->ah->parseAddress($address, false, $tip);
			if (!empty($row['Lat']))
				$extra->data->addGeoArray($address, array_merge($this->getDefaultGeoArray(), $row));
			else
				$extra->data->nullGeo($address);
		}
	}

    public function lookupPlace($name, Extra $extra)
    {
        if (!$name) {
            return;
        }
        if (!$extra->data->getGeo($name)) {
            $row = $this->hh->lookupHotel($name);
            if (!empty($row['Lat']))
                $extra->data->addGeoArray($name, array_merge($this->getDefaultGeoArray(), $row));
            else
                $extra->data->nullGeo($name);
        }
    }

    public function parsePlace($name, Extra $extra)
    {
        if (!$name) {
            return;
        }
        if (!$extra->data->getGeo($name)) {
            $row = $this->ah->parseAddress($name, true);
            if (!empty($row['Lat']))
                $extra->data->addGeoArray($name, array_merge($this->getDefaultGeoArray(), $row));
            else
                $extra->data->nullGeo($name);
        }
    }

	protected function getDefaultGeoArray($name = null) {
		return [
			'Name' => $name,
			'AddressLine' => null,
			'City' => null,
			'State' => null,
			'Country' => null,
            'CountryCode' => null,
			'PostalCode' => null,
			'Lat' => null,
			'Lng' => null,
            'TimeZoneLocation' => null,
		];
	}

}