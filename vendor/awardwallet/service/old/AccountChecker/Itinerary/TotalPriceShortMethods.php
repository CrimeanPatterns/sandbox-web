<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

trait TotalPriceShortMethods {

	public function setCost($cost)
	{
		return $this->getTotalPrice()->setCost($cost);
	}

	/**
	 * @return string
	 */
	public function getCost()
	{
		return $this->getTotalPrice()->getCost();
	}

	/**
	 * @param string $currencyCode
	 */
	public function setCurrencyCode($currencyCode)
	{
		return $this->getTotalPrice()->setCurrencyCode($currencyCode);
	}

	/**
	 * @return string
	 */
	public function getCurrencyCode()
	{
		return $this->getTotalPrice()->getCurrencyCodes();
	}

	/**
	 * @param string $earnedAwards
	 */
	public function setEarnedAwards($earnedAwards)
	{
		return $this->getTotalPrice()->setEarnedAwards($earnedAwards);
	}

	/**
	 * @return string
	 */
	public function getEarnedAwards()
	{
		return $this->getTotalPrice()->getEarnedAwards();
	}

	/**
	 * @param array $fees
	 */
	public function setFees($fees)
	{
		return $this->getTotalPrice()->setFees($fees);
	}

	/**
	 * @return array
	 */
	public function getFees()
	{
		return $this->getTotalPrice()->getFees();
	}

	/**
	 * @param string $spentAwards
	 */
	public function setSpentAwards($spentAwards)
	{
		return $this->getTotalPrice()->setSpentAwards($spentAwards);
	}

	/**
	 * @return string
	 */
	public function getSpentAwards()
	{
		return $this->getTotalPrice()->getSpentAwards();
	}

	/**
	 * @param float $tax
	 */
	public function setTax($tax)
	{
		return $this->getTotalPrice()->setTax($tax);
	}

	/**
	 * @return float
	 */
	public function getTax()
	{
		return $this->getTotalPrice()->getTax();
	}

	/**
	 * @param float $total
	 */
	public function setTotal($total)
	{
		return $this->getTotalPrice()->setTotal($total);
	}

	/**
	 * @return float
	 */
	public function getTotal()
	{
		return $this->getTotalPrice()->getTotal();
	}

}
