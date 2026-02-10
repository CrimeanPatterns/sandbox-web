<?php

class UserEmulator
{

	const BROWSER_FIREFOX = 1;
	const BROWSER_CHROME = 2;

	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	private $connection;
	/**
	 * @var \GeoIp2\Database\Reader
	 */
	private $geoReader;
	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $logger;

	public function __construct(\Doctrine\DBAL\Connection $connection, \GeoIp2\Database\Reader $geoReader){
		$this->connection = $connection;
		$this->geoReader = $geoReader;
		$this->logger = new \Psr\Log\NullLogger();
	}

	/**
	 * @param string $ip
	 * @param string $userAgent
	 * @return UserInfo
	 */
	public function getUser($ip, $browser, $userAgent, $shift = null){
		$qb = new \Doctrine\DBAL\Query\QueryBuilder($this->connection);
		$qb
			->select("ui.*")
			->from("UserInfo", "ui")
			->andWhere("ui.LockDate is null")
			->andWhere("ui.Browser = :browser")->setParameter("browser", $browser);

		if($shift === null)
			$shift = floor(getdate()["hours"] / 8);
		$qb->andWhere("ui.Shift = :shift")->setParameter("shift", $shift);

		try {
			$record = $this->geoReader->city($ip);
		}
		catch(\GeoIp2\Exception\AddressNotFoundException $e){
			$this->logger->info("ip address not found in geo db: " . $ip);
		}
		if(!empty($record) && !empty($record->country))
			$qb->andWhere("ui.CountryCode = :countryCode")->setParameter("countryCode", $record->country->isoCode);
		else
			$qb->andWhere("ui.CountryCode is null");

		if(!empty($record) && !empty($record->subdivisions) && !empty($stateIsoCode = $record->subdivisions[0]->isoCode))
			$qb->andWhere("ui.StateCode = :stateCode")->setParameter("stateCode", $stateIsoCode);
		else
			$qb->andWhere("ui.StateCode is null");

		$qb->setMaxResults(1);
		$result = $qb->execute()->fetch(\PDO::FETCH_ASSOC);

		if(!empty($result)){
			if($this->connection->executeUpdate(
				"update UserInfo set LockDate = now(), LastUseDate = now() where UserInfoID = :UserInfoID",
				["UserInfoID" => $result['UserInfoID']]
			) != 1) {
				$this->logger->warning("can't lock UserInfo row {$result['UserInfoID']}");
				$result = null;
			}
		}

		if(empty($result)){
			$this->logger->info("creating new user");
			$result = [
				"Shift" => $shift,
				"Browser" => $browser,
				"UserAgent" => $userAgent,
				"CountryCode" => null,
				"StateCode" => null
			];
			if(!empty($record) && !empty($record->country))
				$result["CountryCode"] = $record->country->isoCode;
			if(!empty($stateIsoCode))
				$result["StateCode"] = $stateIsoCode;

			$this->connection->executeUpdate(
				"insert into UserInfo(Shift, Browser, UserAgent, CreateDate, LockDate, LastUseDate, CountryCode, StateCode)
					values (:Shift, :Browser, :UserAgent, now(), now(), now(), :CountryCode, :StateCode)",
				$result
			);
			$result['UserInfoID'] = $this->connection->lastInsertId();
		}

		if(empty($result['Cookies']))
			$result['Cookies'] = [];
		else
			$result['Cookies'] = json_decode($result['Cookies'], true);

		return new UserInfo($result['UserInfoID'], $result['Cookies'], $result['UserAgent']);
	}

	public function saveCookies($id, array $cookies){
		$this->connection->update(
			"UserInfo",
			["Cookies" => json_encode($cookies)],
			["UserInfoID" => $id]
		);
		$this->logger->info("unlocked", ["UserInfoID" => $id]);
	}

	public function unlock($id){
		$this->connection->executeUpdate(
			"update UserInfo set LastUseDate = now(), LockDate = null where UserInfoID = :UserInfoID",
			["UserInfoID" => $id]
		);
		$this->logger->info("unlocked", ["UserInfoID" => $id]);
	}

	public function setLogger(\Psr\Log\LoggerInterface $logger){
		$this->logger = $logger;
	}

}