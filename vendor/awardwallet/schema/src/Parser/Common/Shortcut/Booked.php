<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


class Booked {

	/** @var \AwardWallet\Schema\Parser\Common\Hotel $parent */
	protected $parent;

	public function __construct(\AwardWallet\Schema\Parser\Common\Hotel $hotel) {
		$this->parent = $hotel;
	}

	/**
	 * @param $checkInDate
	 * @return Booked
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function checkIn($checkInDate) {
		$this->parent->setCheckInDate($checkInDate);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param string $format
	 * @param bool $after
	 * @return Booked
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function checkIn2($date, $relative = null, $format = '%D% %Y%', $after = true) {
		$this->parent->parseCheckInDate($date, $relative, $format, $after);
		return $this;
	}

	/**
	 * @param $checkOutDate
	 * @return Booked
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function checkOut($checkOutDate) {
		$this->parent->setCheckOutDate($checkOutDate);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param string $format
	 * @param bool $after
	 * @return Booked
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function checkOut2($date, $relative = null, $format = '%D% %Y%', $after = true) {
		$this->parent->parseCheckOutDate($date, $relative, $format, $after);
		return $this;
	}

	/**
	 * @return $this
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function noCheckIn() {
		$this->parent->setNoCheckInDate(true);
		return $this;
	}

	/**
	 * @return $this
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function noCheckOut() {
		$this->parent->setNoCheckOutDate(true);
		return $this;
	}


	/**
	 * @param $guestCount
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Booked
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function guests($guestCount, $allowEmpty = false, $allowNull = false) {
		$this->parent->setGuestCount($guestCount, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $kidsCount
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Booked
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function kids($kidsCount, $allowEmpty = false, $allowNull = false) {
		$this->parent->setKidsCount($kidsCount, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $roomsCount
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Booked
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function rooms($roomsCount, $allowEmpty = false, $allowNull = false) {
		$this->parent->setRoomsCount($roomsCount, $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @param $freeNights
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Booked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
	public function freeNights($freeNights, $allowEmpty = false, $allowNull = false)
    {
        $this->parent->setFreeNights($freeNights, $allowEmpty, $allowNull);
        return $this;
    }

	/**
	 * @param $cancellation
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return Booked
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     * @deprecated use ->general()->cancellation() instead
	 */
	public function cancellation($cancellation, $allowEmpty = false, $allowNull = false) {
		$this->parent->setCancellation($cancellation, $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @return Booked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
	public function nonRefundable()
    {
        $this->parent->setNonRefundable(true);
        return $this;
    }

    /**
     * @param string $search
     * @return Booked
     */
    public function parseNonRefundable($search = \AwardWallet\Schema\Parser\Common\Hotel::NON_REFUNDABLE_REGEXP)
    {
        $this->parent->parseNonRefundable($search);
        return $this;
    }

    /**
     * @param $deadline
     * @return Booked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function deadline($deadline)
    {
        $this->parent->setDeadline($deadline);
        return $this;
    }

    /**
     * @param $deadline
     * @return Booked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function deadline2($deadline)
    {
        $this->parent->parseDeadline($deadline);
        return $this;
    }

    /**
     * @param $prior
     * @param $hour
     * @return Booked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function deadlineRelative($prior, $hour = null)
    {
        $this->parent->parseDeadlineRelative($prior, $hour);
        return $this;
    }

    /**
     * @return Booked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function host()
    {
        $this->parent->setHost(true);
        return $this;
    }

}