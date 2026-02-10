<?php


namespace AwardWallet\Common\Parsing\Solver\Helper;


use AwardWallet\Common\Parsing\Solver\Exception;
use AwardWallet\Common\Parsing\Solver\Extra\AirlineData;
use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Common\Parsing\Solver\Extra\ProviderData;
use AwardWallet\Common\Parsing\Solver\Extra\SolvedCurrency;
use Doctrine\DBAL\Connection;
use PDO;
use Psr\Log\LoggerInterface;

class ExtraHelper {

	/** @var Connection  */
	private $connection;
	/** @var LoggerInterface  */
	private $logger;
	/** @var AirlineAliasProvider  */
	private $airlineAliasProvider;

	private $rentalProviders;

	public function __construct(AirlineAliasProvider $airlineAliasProvider, Connection $connection, LoggerInterface $logger)
    {
		$this->connection = $connection;
		$this->logger = $logger;
		$this->airlineAliasProvider = $airlineAliasProvider;
	}

	public function solveProvider($name, $code, Extra $extra): ?ProviderData
    {
        if ($code && $extra->data->existsProvider($code))
            return $extra->data->getProvider($code);
        if ($name && $extra->data->existsProvider($name))
            return $extra->data->getProvider($name);
        if (!$code && $name) {
            $byName = true;
            $query = $this->connection->executeQuery('select Code, Name, ShortName, KeyWords from Provider');
            $code = null;
            while($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $keywords = array_map(function($s){return strtolower(trim($s));}, array_merge(explode(',', $row['KeyWords']), [$row['Name'], $row['ShortName']]));
                if (in_array(strtolower($name), $keywords)) {
                    $code = $row['Code'];
                    break;
                }
            }
            if ((null === $code) && ($airline = $this->solveAirline($name, $extra)) && ($r = $this->connection->fetchColumn('select Code from Provider where IATACode = ?', [$airline->iata], 0, [PDO::PARAM_STR]))) {
                $code = $r;
            }
            if (null !== $code) {
                $this->logger->info('resolved provider name', ['component' => 'ExtraHelper', 'alias' => $name, 'providerCode' => $code]);
            }
            else {
                $this->logger->info('failed to resolve provider name', ['component' => 'ExtraHelper', 'alias' => $name]);
                throw Exception::unknownProviderKeyWord($name);
            }
        }
        if ($code) {
            if ($query = $this->connection->fetchAssoc('select ProviderID, Code, IATACode, Kind, ShortName from Provider where Code = ?', [$code], [PDO::PARAM_STR]))
                return $extra->data->addProviderArray(isset($byName) ? $name : $code, $query);
        }
        return null;
	}

    public function solveAirline($name, Extra $extra) : ?AirlineData
    {
        if (!$name)
            return null;
        if ($extra->data->existsAirline($name))
            return $extra->data->getAirline($name);
        $lookup = $this->lookupAirline($name);
        if ($lookup) {
            $this->logger->info('resolved airline', ['component' => 'ExtraHelper', 'alias' => $name, 'iata' => $lookup->iata, 'name' => $lookup->name]);
            $extra->data->addAirline($name, $lookup);
        }
        else {
            $this->logger->info('failed to resolve airline', ['component' => 'ExtraHelper', 'alias' => $name]);
            $extra->data->nullAirline($name);
        }
        return $lookup;
    }

    private function lookupAirline($name) : ?AirlineData
    {
        if (preg_match('/^[A-Z\d]{2,3}$/', $name) > 0 && ($query = $this->connection->executeQuery('select Code, ICAO, Name from Airline where Code = ? or ICAO = ? order by Active desc', [$name, $name])->fetch(PDO::FETCH_ASSOC)))
            return AirlineData::fromArray($query);
        if ($query = $this->connection->executeQuery('select Code, ICAO, Name from Airline where Name = ? order by Active desc', [$name])->fetch(PDO::FETCH_ASSOC))
            return AirlineData::fromArray($query);
        $query = $this->connection->executeQuery('select IATACode, Name, ShortName, KeyWords from Provider where Kind = ? and IATACode != ""', [PROVIDER_KIND_AIRLINE], [PDO::PARAM_INT]);
        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $keywords = array_map(function($s){return strtolower(trim($s));}, array_merge(explode(',', $row['KeyWords']), [$row['Name'], $row['ShortName']]));
            if (in_array(strtolower($name), $keywords) && ($data = $this->connection->executeQuery('select Code, ICAO, Name from Airline where Code = ? order by Active desc', [$row['IATACode']])->fetch(PDO::FETCH_ASSOC)))
                return AirlineData::fromArray($data);
        }
        if ($arr = $this->airlineAliasProvider->lookup($name))
            return AirlineData::fromArray($arr);
        return null;
    }

	public function solveCurrency($sign): ?SolvedCurrency
    {
        if (!$sign)
            return null;
        if (preg_match('/^[A-Z]{3}$/', $sign) > 0)
            return new SolvedCurrency($sign, true);
        $codes = $this->connection->executeQuery('select Code from Currency where Sign = ?', [$sign], [PDO::PARAM_STR])->fetchAll(PDO::FETCH_COLUMN);
        if (count($codes) === 0)
            $this->logger->notice('unknown currency', ['sign' => $sign]);
        return !empty($codes) ? new SolvedCurrency($codes[0], count($codes) === 1) : null;
	}

	public function getMaxPriceEstimate($type)
    {
        switch($type) {
            case 'hotel':
                return 200000;
            case 'flight':
            case 'cruise':
                return 150000;
            default:
                return 100000;
        }
    }

	public function getProviderPhone($providerCode, $iataCode)
    {
		if ($providerCode)
			return ($phone = $this->connection->fetchColumn('select pp.Phone from ProviderPhone pp inner join Provider p on pp.ProviderID = p.ProviderID where p.Code = ? and pp.DefaultPhone = "1" and pp.EliteLevelID is null', [$providerCode])) ? $phone : null;
		if ($iataCode)
			return ($phone = $this->connection->fetchColumn('select pp.Phone from ProviderPhone pp inner join Provider p on pp.ProviderID = p.ProviderID where p.IATACode = ? and pp.DefaultPhone = "1" and pp.EliteLevelID is null', [$iataCode])) ? $phone : null;
		return null;
	}

	public function solveRentalCompany(string $company): ?ProviderData
    {
        $company = trim($company);
        if (empty($company))
            return null;
        $clean = trim(str_ireplace(['rent a car', 'rental company', 'company', 'corporation', 'car rental'], '', str_ireplace('-', ' ', $company)));
        $firstWord = !empty($clean) ? explode(' ', $clean)[0] : '';
        $confidence = 0;
        $result = null;

        if ($this->rentalProviders === null) {
            $this->rentalProviders = $this->connection->executeQuery('select ProviderID, Code, Name, ShortName, IATACode, Kind from Provider where Kind = 3 and State > 0')->fetchAll(PDO::FETCH_ASSOC);
        }

        foreach($this->rentalProviders as $row) {
            if ($this->compareRentalNames($company, $row, 10, false) > 0) {
                return ProviderData::fromArray($row);
            }
            if (!empty($clean) && $this->compareRentalNames($clean, $row, 5, false) > $confidence) {
                $result = $row;
                $confidence = 5;
            }
            elseif (!empty($firstWord) && $this->compareRentalNames($firstWord, $row, 1, true) > 0) {
                $result = $row;
                $confidence = 1;
            }
        }
        return $confidence > 0 ? ProviderData::fromArray($result) : null;
    }

    private function compareRentalNames(string $name, array $row, int $confidence, bool $firstWordOnly): int
    {
        if (empty($name)) {
            return 0;
        }
        foreach(['Name', 'ShortName', 'Code'] as $key) {
            if (empty($row[$key])) {
                continue;
            }
            if (strcasecmp($name, $firstWordOnly ? explode(' ', $row[$key])[0] : $row[$key]) === 0)
                return $confidence;
        }
        return 0;
    }

}