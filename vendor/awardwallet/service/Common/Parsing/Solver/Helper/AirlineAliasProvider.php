<?php

namespace AwardWallet\Common\Parsing\Solver\Helper;


use Doctrine\DBAL\Connection;

class AirlineAliasProvider {

	const TRUST = 3;

	/** @var Connection $connection */
	protected $connection;

	public function __construct(Connection $connection) {
		$this->connection = $connection;
	}

	public function lookup($alias) {
		$row = $this->_alias($alias);
		if ($row !== null && intval($row['Trusted']) >= self::TRUST)
			return $row;
		else
			return null;
	}

	/*
	public function set($alias, $airlineCode) {
		$row = $this->_alias($alias);
		$airlineId = $this->_codeId($airlineCode);
		if ($airlineId === null)
			throw Exception::unknownAirlineCode($airlineCode);
		if ($row === null) {
            $this->connection->executeQuery(
                'insert into AirlineAlias(AirlineID, Alias, LastUpdateDate, Trusted) values(?, ?, ?, ?) on duplicate key update Trusted = Trusted + 1',
                [$airlineId, $alias, new \DateTime(), 1],
                [\PDO::PARAM_INT, \PDO::PARAM_STR, 'datetime', \PDO::PARAM_INT]);
//            $this->connection->insert('AirlineAlias', ['AirlineID' => $airlineId, 'Alias' => $alias, 'LastUpdateDate' => new \DateTime(), 'Trusted' => 1], [\PDO::PARAM_INT, \PDO::PARAM_STR, 'datetime', \PDO::PARAM_INT]);
		}
		elseif($row['Code'] === $airlineCode) {
			if (intval($row['Trusted']) < self::TRUST)
				$this->connection->update('AirlineAlias', ['Trusted' => $row['Trusted'] + 1], ['AirlineAliasID' => $row['ID']], [\PDO::PARAM_INT, \PDO::PARAM_INT]);
		}
		else {
			if (intval($row['Trusted']) > 0)
				$this->connection->update('AirlineAlias', ['Trusted' => $row['Trusted'] - 1], ['AirlineAliasID' => $row['ID']], [\PDO::PARAM_INT, \PDO::PARAM_INT]);
			else
				$this->connection->update('AirlineAlias', ['Trusted' => 1, 'AirlineID' => $airlineId], ['AirlineAliasID' => $row['ID']], [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT]);
		}
	}
	*/

	protected function _alias($alias) {
		$row = $this->connection->executeQuery('select aa.Trusted, aa.AirlineAliasID as ID, a.Code, a.Name, a.ICAO from Airline a inner join AirlineAlias aa on a.AirlineID = aa.AirlineID where aa.Alias = ?', [$alias], [\PDO::PARAM_STR])->fetch(\PDO::FETCH_ASSOC);
		return $row !== false ? $row : null;
	}

	protected function _codeId($code) {
		$row = $this->connection->executeQuery('select AirlineID from Airline where Code = ? order by Active desc', [$code], [\PDO::PARAM_STR])->fetchColumn();
		return $row !== false ? $row : null;
	}

}