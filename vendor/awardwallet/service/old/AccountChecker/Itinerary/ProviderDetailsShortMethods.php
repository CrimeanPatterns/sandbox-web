<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

trait ProviderDetailsShortMethods {

	/**
	 * @param string $code
	 */
	public function setProviderCode($code)
	{
		return $this->getProviderDetails()->setProviderCode($code);
	}

	/**
	 * @return string
	 */
	public function getProviderCode()
	{
		return $this->getProviderDetails()->getProviderCode();
	}

	/**
	 * @param string $confirmationNumber
	 */
	public function setConfirmationNumber($confirmationNumber)
	{
		return $this->getProviderDetails()->setConfirmationNumber($confirmationNumber);
	}

	/**
	 * @return string
	 */
	public function getConfirmationNumber()
	{
		return $this->getProviderDetails()->getConfirmationNumber();
	}

    /**
	 * @param string $confirmationNumbers
	 */
	public function setConfirmationNumbers($confirmationNumber)
	{
		return $this->getProviderDetails()->setConfirmationNumbers($confirmationNumber);
	}

	/**
	 * @return string
	 */
	public function getConfirmationNumbers()
	{
		return $this->getProviderDetails()->getConfirmationNumbers();
	}

    /**
	 * @param string $tripNumber
	 */
	public function setTripNumber($tripNumber)
	{
		return $this->getProviderDetails()->setTripNumber($tripNumber);
	}

	/**
	 * @return string
	 */
	public function getTripNumber()
	{
		return $this->getProviderDetails()->getTripNumber();
	}

	/**
	 * @param string $name
	 */
	public function setProviderName($name)
	{
		return $this->getProviderDetails()->setCompanyName($name);
	}

	/**
	 * @return string
	 */
	public function getProviderName()
	{
		return $this->getProviderDetails()->getCompanyName();
	}

	/**
	 * @param string $reservationDate
	 */
	public function setReservationDate($reservationDate)
	{
		return $this->getProviderDetails()->setReservationDate($reservationDate);
	}

	/**
	 * @return string
	 */
	public function getReservationDate()
	{
		return $this->getProviderDetails()->getReservationDate();
	}

	/**
	 * @param string $status
	 */
	public function setStatus($status)
	{
		return $this->getProviderDetails()->setStatus($status);
	}

	/**
	 * @return string
	 */
	public function getStatus()
	{
		return $this->getProviderDetails()->getStatus();
	}

    /**
	 * @param boolean $cancelled
	 */
	public function setCancelled($cancelled)
	{
		return $this->getProviderDetails()->setCancelled($cancelled);
	}

	/**
	 * @return boolean
	 */
	public function getCancelled()
	{
		return $this->getProviderDetails()->getCancelled();
	}

}
