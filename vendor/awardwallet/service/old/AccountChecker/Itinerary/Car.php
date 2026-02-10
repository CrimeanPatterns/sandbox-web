<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

class Car {

	use Loggable;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var string
	 */
	protected $model;

	/**
	 * @var string
	 */
	protected $imageUrl;

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
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
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * @param string $model
	 */
	public function setModel($model)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->model = $model;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getImageUrl()
	{
		return $this->imageUrl;
	}

	/**
	 * @param string $imageUrl
	 */
	public function setImageUrl($imageUrl)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->imageUrl = $imageUrl;
		return $this;
	}

	/** @param \Psr\Log\LoggerInterface */
	public function __construct($logger = null) {
		$this->logger = $logger;
	}

}
