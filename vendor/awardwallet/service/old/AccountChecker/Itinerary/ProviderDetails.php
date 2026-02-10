<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

class ProviderDetails {

	use Loggable;

	/**
	 * @var string
	 */
	protected $confirmationNumber;

    /**
	 * @var string
	 */
	protected $confirmationNumbers;

    /**
	 * @var string
	 */
	protected $tripNumber;

	/**
	 * @var string
	 */
	protected $reservationDate;

	/**
	 * @var string
	 */
	protected $status;

    /**
	 * @var string
	 */
	protected $cancelled = false;

	/**
	 * @var string
	 */
	protected $companyName;

	/**
	 * @var string
	 */
	protected $code;

	/**
	 * @param string $code
	 */
	public function setCode($code)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->code = $code;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @param string $confirmationNumber
	 */
	public function setConfirmationNumber($confirmationNumber)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->confirmationNumber = $confirmationNumber;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getConfirmationNumber()
	{
		return $this->confirmationNumber;
	}

    /**
	 * @param string $confirmationNumbers
	 */
	public function setConfirmationNumbers($confirmationNumbers)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->confirmationNumbers = $confirmationNumbers;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getConfirmationNumbers()
	{
		return $this->confirmationNumbers;
	}

    /**
	 * @param string $tripNumber
	 */
	public function setTripNumber($tripNumber)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->tripNumber = $tripNumber;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTripNumber()
	{
		return $this->tripNumber;
	}

	/**
	 * @param string $companyName
	 */
	public function setCompanyName($companyName)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->companyName = $companyName;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCompanyName()
	{
		return $this->companyName;
	}

	/**
	 * @param string $reservationDate
	 */
	public function setReservationDate($reservationDate)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->reservationDate = $reservationDate;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getReservationDate()
	{
		return $this->reservationDate;
	}

	/**
	 * @param string $status
	 */
	public function setStatus($status)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->status = $status;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getStatus()
	{
		return $this->status;
	}

    /**
     * @param boolean $cancelled
	 */
	public function setCancelled($cancelled)
	{
		$this->logPropertySetting(__METHOD__, func_get_args());
		$this->cancelled = $cancelled;
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getCancelled()
	{
		return $this->cancelled;
	}

	public function setFromOptions($confNo, array $options, array $itinerary){
//		if ($confNo !== CONFNO_UNKNOWN)
//			$this->confirmationNumber = $confNo;
//		$this->name = $options['Provider']['ShortName'];
//		$this->code = $options['Provider']['Code'];
//		if(isset($itinerary['ReservationDate']))
//			$this->reservationDate = date(Util::ISO_DATE_FORMAT, $itinerary['ReservationDate']);
//		if (isset($itinerary['Status']))
//			$this->status = $itinerary['Status'];
	}

}
