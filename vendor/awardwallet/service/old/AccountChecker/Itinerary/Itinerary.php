<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

abstract class Itinerary {

	use Loggable;

	/**
	 * @var ProviderDetails
	 */
	protected $providerDetails;
	use ProviderDetailsShortMethods;

	/**
	 * @var TotalPrice
	 */
	protected $totalPrice;
	use TotalPriceShortMethods;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @param \AwardWallet\MainBundle\Service\Itinerary\ProviderDetails $providerDetails
	 */
	public function setProviderDetails($providerDetails)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->providerDetails = $providerDetails;
		$this->providerDetails->logger = $this->logger;
		return $this;
	}

	/**
	 * @return \AwardWallet\MainBundle\Service\Itinerary\ProviderDetails
	 */
	public function getProviderDetails()
	{
		return $this->providerDetails;
	}

	/**
	 * @param \AwardWallet\MainBundle\Service\Itinerary\TotalPrice $totalPrice
	 */
	public function setTotalPrice($totalPrice)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->totalPrice = $totalPrice;
		$this->totalPrice->logger = $this->logger;
		return $this;
	}

	/**
	 * @return \AwardWallet\MainBundle\Service\Itinerary\TotalPrice
	 */
	public function getTotalPrice()
	{
		return $this->totalPrice;
	}

	/**
	 * @param string $type
	 */
	public function setType($type)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->type = $type;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	public function __construct($logger = null) {
		$this->logger = $logger;
		$this->providerDetails = new ProviderDetails();
		$this->providerDetails->logger = $logger;
		$this->totalPrice = new TotalPrice();
		$this->totalPrice->logger = $logger;
		$this->type = lcfirst(preg_replace('/^.+\\\/ims', '', get_class($this)));
	}

	abstract public function convertToOldArrayFormat();

}
