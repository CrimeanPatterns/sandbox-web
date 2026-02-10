<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

class TotalPrice {

	use Loggable;

	/**
	 * @var float
	 */
	protected $total;

	/**
	 * @var string
	 */
	protected $cost;

	/**
	 * @var string
	 */
	protected $spentAwards;

	/**
	 * @var string
	 */
	protected $earnedAwards;

	/**
	 * @var string
	 */
	protected $currencyCode;

	/**
	 * @var float
	 */
	protected $tax;

	/**
	 * @var array
	 */
	protected $fees;

	/**
	 * @param string $cost
	 */
	public function setCost($cost)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->cost = $cost;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCost()
	{
		return $this->cost;
	}

	/**
	 * @param string $currencyCode
	 */
	public function setCurrencyCode($currencyCode)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->currencyCode = $currencyCode;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCurrencyCode()
	{
		return $this->currencyCode;
	}

	/**
	 * @param string $earnedAwards
	 */
	public function setEarnedAwards($earnedAwards)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->earnedAwards = $earnedAwards;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getEarnedAwards()
	{
		return $this->earnedAwards;
	}

	/**
	 * @param array $fees
	 */
	public function setFees($fees)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->fees = $fees;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getFees()
	{
		return $this->fees;
	}

	/**
	 * @param string $spentAwards
	 */
	public function setSpentAwards($spentAwards)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->spentAwards = $spentAwards;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSpentAwards()
	{
		return $this->spentAwards;
	}

	/**
	 * @param float $tax
	 */
	public function setTax($tax)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->tax = $tax;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getTax()
	{
		return $this->tax;
	}

	/**
	 * @param float $total
	 */
	public function setTotal($total)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->total = $total;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getTotal()
	{
		return $this->total;
	}

}
