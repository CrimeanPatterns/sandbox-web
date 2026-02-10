<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

class Person {

	use Loggable;

	/**
	 * @var string
	 */
	protected $fullName;

	/**
	 * @return string
	 */
	public function getFullName()
	{
		return $this->fullName;
	}

	/**
	 * @param string $fullName
	 */
	public function setFullName($fullName)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->fullName = $fullName;
		return $this;
	}

	/** @param \Psr\Log\LoggerInterface */
	public function __construct($fullName = null, $logger = null) {
		$this->fullName = $fullName;
		$this->logger = $logger;
	}

}
