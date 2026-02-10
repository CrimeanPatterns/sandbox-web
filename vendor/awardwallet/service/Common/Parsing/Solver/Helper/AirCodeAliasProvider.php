<?php

namespace AwardWallet\Common\Parsing\Solver\Helper;


use AwardWallet\Common\Parsing\Solver\Exception;
use Doctrine\DBAL\Connection;

class AirCodeAliasProvider {

	const TRUST = 3;

	/**
	 * @var Connection
	 */
	protected $connection;

	public function __construct(Connection $connection) {
		$this->connection = $connection;
	}

	public function lookup($providerId, $alias) {
		$row = $this->_alias($providerId, $alias);
		if ($row !== null && intval($row['Trusted']) >= self::TRUST)
			return $row['AirCode'];
		else
			return null;
	}

	public function set($providerId, $alias, $airCode) {
		$row = $this->_alias($providerId, $alias);
		$airCodeId = $this->_codeId($airCode);
		if (null === $airCodeId)
			throw Exception::unknownAirCode($airCode);
		if ($row === null) {
		    $this->connection->executeQuery(
		        'insert into AirCodeProviderAlias(ProviderID, AirCodeID, UpdateDate, Trusted, Alias) values(?, ?, ?, ?, ?) on duplicate key update Trusted = Trusted + 1',
                [$providerId, $airCodeId, new \DateTime(), 1, $alias],
                [\PDO::PARAM_INT, \PDO::PARAM_INT, 'date', \PDO::PARAM_INT, \PDO::PARAM_STR]);
		}
		elseif ($row['AirCode'] === $airCode) {
			if (intval($row['Trusted']) < self::TRUST)
				$this->connection->update('AirCodeProviderAlias', ['Trusted' => $row['Trusted'] + 1], ['AirCodeProviderAliasID' => $row['ID']], [\PDO::PARAM_INT, \PDO::PARAM_INT]);
		}
		else {
			if (intval($row['Trusted']) > 0)
				$this->connection->update('AirCodeProviderAlias', ['Trusted' => $row['Trusted'] - 1], ['AirCodeProviderAliasID' => $row['ID']], [\PDO::PARAM_INT, \PDO::PARAM_INT]);
			else
				$this->connection->update('AirCodeProviderAlias', ['Trusted' => 1, 'AirCodeID' => $airCodeId], ['AirCodeProviderAliasID' => $row['ID']], [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT]);
		}
	}
	
	protected function _alias($providerId, $alias) {
		$row = $this->connection->fetchAssoc('select aa.AirCodeProviderAliasID as ID, aa.Alias, aa.Trusted, aa.ProviderID, aa.AirCodeID, ac.AirCode
												from AirCodeProviderAlias aa
												left join AirCode ac on aa.AirCodeID = ac.AirCodeID
												where aa.ProviderID = ? and aa.Alias = ?', [$providerId, $alias]);
		return $row !== false ? $row : null;
	}

	protected function _codeId($airCode) {
		$id = $this->connection->fetchColumn('select AirCodeID from AirCode where AirCode = ?', [$airCode]);
		return $id !== false ? $id : null;
	}

}