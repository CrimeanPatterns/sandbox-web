<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

trait CarShortMethods {

	public function getCarType()
	{
		return $this->getCar()->getType();
	}

	/**
	 * @param string $type
	 */
	public function setCarType($type)
	{
		return $this->getCar()->setType($type);
	}

	/**
	 * @return string
	 */
	public function getCarModel()
	{
		return $this->getCar()->getModel();
	}

	/**
	 * @param string $model
	 */
	public function setCarModel($model)
	{
		return $this->getCar()->setModel($model);
	}

	/**
	 * @return string
	 */
	public function getCarImageUrl()
	{
		return $this->getCar()->getImageUrl();
	}

	/**
	 * @param string $imageUrl
	 */
	public function setCarImageUrl($imageUrl)
	{
		return $this->getCar()->setImageUrl($imageUrl);
	}

}