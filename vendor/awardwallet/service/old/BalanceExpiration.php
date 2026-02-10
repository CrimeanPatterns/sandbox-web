<?php

class BalanceExpiration {
	
	const BALANCE_IS_EARN = 0;
	const BALANCE_IS_BALANCE = 1;
	const ROWS_ORDER_BY_DESC = 0;
	const ROWS_ORDER_BY_ASC	 = 1;
	
	const ERROR_INVALID_ROWS = 1;
	
	protected $orderRows = null;
	protected $rowsActivity = array();
	protected $commonBalance = 0;
	protected $expirationPeriod = null;
	protected $result = array(
		'EarningsDate'	 => null,
		'ExpirationDate' => null,
		'Balance'		 => 0,
	);
	
	public function __construct($order = self::ROWS_ORDER_BY_DESC, array $rowsActivity = array()) {
		$this->orderRows = $order;
		$this->setRowsActivity($rowsActivity);
	}
	
	/**
	 * Add Row Activity
	 * 
	 * @param array $rowActivity 
	 * @return BalanceExpiration
	 */
	public function addRowActivity(array $rowActivity) {
		$this->rowsActivity[] = $rowActivity;
		
		return $this;
	}
	
	/**
	 * Set Rows Activity
	 * 
	 * Format: 
	 *  array(
	 *    array('EarningsDate' => 'Date of earnings', 'ExpirationDate' => 'Expiration Date', 'Balance' => 'Balance/Earn Miles'), # Row #1
	 *    array('EarningsDate' => 'Date of earnings', 'ExpirationDate' => 'Expiration Date', 'Balance' => 'Balance/Earn Miles'), # Row #2
	 *    ...
	 * 	  array('EarningsDate' => '31 dec 2011', 'ExpirationDate' => '31 dec 2013', 'Balance' => '250'),
	 *    array('EarningsDate' => '06/23/2011', 'Balance' => '1000'),
	 *    array('ExpirationDate' => '31 dec 2013', 'Balance' => '500'),
	 *  )
	 *  IMPORTANT:
	 *     - Balance - required
	 *     - EarningsDate or ExpirationDate - required
	 * 
	 * @param array $rowsActivity 
	 * @return BalanceExpiration
	 */
	public function setRowsActivity(array $rowsActivity = array()) {
		$this->rowsActivity = $rowsActivity;
		
		return $this;
	}
	
	/**
	 * Get Rows Activity
	 * 
	 * @return array
	 */
	public function getRowsActivity() {
		return $this->rowsActivity;
	}
	
	/**
	 * Set Common Balance
	 * 
	 * @param float $balance
	 * @return BalanceExpiration
	 */
	public function setCommonBalance($balance) {
		$this->commonBalance = $balance;
		
		return $this;
	}
	
	/**
	 * Set Expiration Period
	 * 
	 * Examples:
	 *   setExpirationPeriod('+1 year');
	 *   setExpirationPeriod('+1 week 2 days 4 hours 2 seconds');
	 * 
	 * @param string $period
	 * @return BalanceExpiration
	 */
	public function setExpirationPeriod($period) {
		$this->expirationPeriod = $period;
		
		return $this;
	}
	
	/**
	 * Calculate
	 * 
	 * @param int $type
	 * @return BalanceExpiration
	 */
	public function Calculate($type = self::BALANCE_IS_EARN) {
		$currentDate = time();
		
		if ($this->commonBalance <= 0)
			return $this;
		
		$rows = $this->getRowsActivity();
		if ($type == self::BALANCE_IS_EARN) {
			if ($this->orderRows == self::ROWS_ORDER_BY_DESC)
				$rows = array_reverse($rows);
		} else {
			if ($this->orderRows == self::ROWS_ORDER_BY_ASC)
				$rows = array_reverse($rows);
		}
		
		$maxBalance = 0;
		foreach ($rows as $index => &$row) {
			# Balance required
			if (!isset($row['Balance']) || !is_numeric($row['Balance'])) {
				unset($rows[$index]);
				continue;
			}
			if (isset($row['EarningsDate']) && isset($row['ExpirationDate'])) {
				$row['EarningsDate'] = $this->checkDate($row['EarningsDate']);
				$row['ExpirationDate'] = $this->checkDate($row['ExpirationDate']);
				if ($row['EarningsDate'] === false)
					unset($row['EarningsDate']);
				if ($row['ExpirationDate'] === false)
					unset($row['ExpirationDate']);
				if ($row['EarningsDate'] === false && $row['ExpirationDate'] === false) {
					unset($rows[$index]);
					continue;
				}
			}
			
			if (!isset($row['EarningsDate']) && isset($row['ExpirationDate'])) {
				$expirationDate = $this->checkDate($row['ExpirationDate']);
				if ($expirationDate === false) {
					unset($rows[$index]);
					continue;
				}
				$row['ExpirationDate'] = $expirationDate;
				if (!is_null($this->expirationPeriod)) {
					$earningsDate = strtotime(str_replace("+", "-", $this->expirationPeriod), $expirationDate);
					if ($earningsDate !== false) {
						$row['EarningsDate'] = $earningsDate;
					}
				}
			}
			
			if (isset($row['EarningsDate']) && !isset($row['ExpirationDate'])) {
				$earningsDate = $this->checkDate($row['EarningsDate']);
				if ($expirationDate === false) {
					unset($rows[$index]);
					continue;
				}
				$row['EarningsDate'] = $earningsDate;
				if (!is_null($this->expirationPeriod)) {
					$expirationDate = strtotime($this->expirationPeriod, $earningsDate);
					if ($expirationDate !== false) {
						$row['ExpirationDate'] = $expirationDate;
					} else {
						unset($rows[$index]);
						continue;
					}
				} else {
					unset($rows[$index]);
					continue;
				}
			}
			
			# Check Expiration
			if ($type == self::BALANCE_IS_EARN) {
				if ($row['ExpirationDate'] > $currentDate)
					$maxBalance += $row['Balance'];
				else {
					unset($rows[$index]);
					continue;
				}
			}
		}
		
		if (!sizeof($rows))
			throw new Exception('Invalid rows', self::ERROR_INVALID_ROWS);
		
		if ($type == self::BALANCE_IS_EARN) {
			$redeem = $maxBalance - $this->commonBalance;
			foreach ($rows as $index => &$row) {
				if ($redeem >= $row['Balance']) {
					$redeem = $redeem - $row['Balance'];
					continue;
				} else {
					$this->setResult(
						(isset($row['EarningsDate'])) ? $row['EarningsDate'] : null,
						$row['ExpirationDate'],
						$row['Balance'] - $redeem
					);
					break;
				}
			}
		} else {
			$row = array_shift($rows);
			$this->setResult(
				(isset($row['EarningsDate'])) ? $row['EarningsDate'] : null,
				$row['ExpirationDate'],
				$this->commonBalance
			);
		}
		
		return $this;
	}
	
	/**
	 * Get Result
	 * 
	 * @return array
	 */
	public function getResult() {
		return $this->result;
	}
	
	/**
	 * Check Date
	 * 
	 * @param string|int $date
	 * @return int|false
	 */
	protected function checkDate($date) {
		if (!is_int($date))
			$date = strtotime($date);
		return $date;
	}
	
	/**
	 * Set Result
	 * 
	 * @param int|null $earningsDate
	 * @param int|null $expirationDate
	 * @param float $miles
	 * @return void
	 */
	protected function setResult($earningsDate = null, $expirationDate = null, $miles = 0) {
		$this->result = array(
			'EarningsDate'	 => $earningsDate,
			'ExpirationDate' => $expirationDate,
			'Balance'		 => $miles,
		);
	}
	
}


function BalanceExpirationTest() {
	$fixture = array(
		array('EarningsDate' => '01/10/2009', 'ExpirationDate' => '12/31/2011', 'Balance' => '1000'),
		array('EarningsDate' => '01/10/2010', 'ExpirationDate' => '12/31/2012', 'Balance' => '1000'),
		array('EarningsDate' => '01/25/2011', 'ExpirationDate' => '12/31/2013', 'Balance' => '2500'),
	);
	$test = new BalanceExpiration(BalanceExpiration::ROWS_ORDER_BY_ASC);
	foreach ($fixture as $row)
		$test->addRowActivity($row);
	
	$result = 	$test->setCommonBalance(2500)
					 //->setExpirationPeriod('+1 year')
					 ->Calculate()
					 ->getResult();
	var_dump($result);
}


?>