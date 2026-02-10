<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

class Fee {

	use Loggable;

	/**
	 * @var string
	 */
	protected $name;
	/**
	 * @var string
	 */
	protected $charge;

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->name = $name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCharge()
	{
		return $this->charge;
	}

	/**
	 * @param string $charge
	 */
	public function setCharge($charge)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->charge = $charge;
		return $this;
	}

	/** @param \Psr\Log\LoggerInterface */
	public function __construct($logger = null) {
		$this->logger = $logger;
	}

}
