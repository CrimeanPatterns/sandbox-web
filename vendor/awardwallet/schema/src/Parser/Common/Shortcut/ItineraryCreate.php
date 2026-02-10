<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Component\Master;

class ItineraryCreate {

	/** @var Master $master */
	protected $master;

	public function __construct(Master $master) {
		$this->master = $master;
	}

	/**
	 * @return \AwardWallet\Schema\Parser\Common\Flight
	 */
	public function flight() {
		return $this->master->createFlight();
	}

	/**
	 * @return \AwardWallet\Schema\Parser\Common\Rental
	 */
	public function rental() {
		return $this->master->createRental();
	}

	/**
	 * @return \AwardWallet\Schema\Parser\Common\Hotel
	 */
	public function hotel() {
		return $this->master->createHotel();
	}

	/**
	 * @return \AwardWallet\Schema\Parser\Common\Bus
	 */
	public function bus() {
		return $this->master->createBus();
	}

	/**
	 * @return \AwardWallet\Schema\Parser\Common\Train
	 */
	public function train() {
		return $this->master->createTrain();
	}

	/**
	 * @return \AwardWallet\Schema\Parser\Common\Transfer
	 */
	public function transfer() {
		return $this->master->createTransfer();
	}

	/**
	 * @return \AwardWallet\Schema\Parser\Common\Cruise
	 */
	public function cruise() {
		return $this->master->createCruise();
	}

	/**
	 * @return \AwardWallet\Schema\Parser\Common\Event
	 */
	public function event() {
		return $this->master->createEvent();
	}

    /**
     * @return \AwardWallet\Schema\Parser\Common\Parking
     */
    public function parking() {
        return $this->master->createParking();
    }

    /**
     * @return \AwardWallet\Schema\Parser\Common\Ferry
     */
    public function ferry() {
        return $this->master->createFerry();
    }

	/**
	 * @return \AwardWallet\Schema\Parser\Common\BoardingPass
	 */
	public function bpass() {
		return $this->master->createBoardingPass();
	}

	/**
	 * @return \AwardWallet\Schema\Parser\Common\Statement
	 */
	public function statement() {
		return $this->master->createStatement();
	}

    /**
     * @return \AwardWallet\Schema\Parser\Common\OneTimeCode
     */
	public function oneTimeCode(){
	    return $this->master->createOneTimeCode();
    }

    /**
     * @return \AwardWallet\Schema\Parser\Common\Coupon
     */
    public function coupon()
    {
        return $this->master->addCoupon();
    }

    /**
     * @return \AwardWallet\Schema\Parser\Common\AwardRedemption
     */
    public function awardRedemption(){
        return $this->master->createAwardRedemption();
    }

    /**
     * @return \AwardWallet\Schema\Parser\Common\CardPromo
     */
    public function cardPromo()
    {
        return $this->master->createCardPromo();
    }
}