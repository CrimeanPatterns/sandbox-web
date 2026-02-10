<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\Itinerary;

class General {

	/** @var Itinerary $parent */
	protected $parent;

	public function __construct(Itinerary $parent) {
		$this->parent = $parent;
	}

    /**
     * @param $confirmation
     * @param null $description
     * @param bool $isPrimary
     * @param null $numberAttr
     * @return General
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
	public function confirmation($confirmation, $description = null, $isPrimary = null, $numberAttr = null) {
		$this->parent->addConfirmationNumber($confirmation, $description, $isPrimary, true, true, $numberAttr);
		return $this;
	}

	/**
	 * @return General
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function noConfirmation() {
		$this->parent->setNoConfirmationNumber(true);
		return $this;
	}

	/**
	 * @param string[] $names
	 * @param boolean $areFull
	 * @return General
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     * @deprecated use travellers() instead
	 */
	public function names($names, $areFull = null) {
		$this->parent->setTravellers($names, $areFull);
		return $this;
	}

	/**
	 * @param $name
	 * @param boolean $isFull
	 * @return General
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     * @deprecated use traveller() instead
	 */
	public function name($name, $isFull = null) {
		$this->parent->addTraveller($name, $isFull);
		return $this;
	}

    /**
     * @param string[] $names
     * @param null $areNamesFull
     * @return General
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function travellers($names, $areNamesFull = null) {
        $this->parent->setTravellers($names, $areNamesFull);
        return $this;
    }

    /**
     * @param $name
     * @param null $isNameFull
     * @return General
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function traveller($name, $isNameFull = null) {
        $this->parent->addTraveller($name, $isNameFull);
        return $this;
    }

    /**
     * @param string[] $names
     * @param null $areNamesFull
     * @return General
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function infants($names, $areNamesFull = null) {
        $this->parent->setInfants($names, $areNamesFull);
        return $this;
    }

    /**
     * @param $name
     * @param null $isNameFull
     * @return General
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function infant($name, $isNameFull = null) {
        $this->parent->addInfant($name, $isNameFull);
        return $this;
    }

	/**
	 * @param $status
	 * @return General
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function status($status) {
		$this->parent->setStatus($status);
		return $this;
	}

	/**
	 * @param $date
	 * @return General
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function date($date) {
		$this->parent->setReservationDate($date);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param string $format
	 * @param bool $after
	 * @return General
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function date2($date, $relative = null, $format = '%D% %Y%', $after = true) {
		$this->parent->parseReservationDate($date, $relative, $format, $after);
		return $this;
	}

	/**
	 * @return General
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function cancelled() {
		$this->parent->setCancelled(true);
		return $this;
	}

    /**
     * @param $cancellation
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return General
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function cancellation($cancellation, $allowEmpty = false, $allowNull = false) {
        $this->parent->setCancellation($cancellation, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $number
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return General
     */
    public function cancellationNumber($number, $allowEmpty = false, $allowNull = false)
    {
        $this->parent->setCancellationNumber($number, $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @param $notes
     * @return General
     */
    public function notes($notes)
    {
        $this->parent->setNotes($notes);
        return $this;
    }

}