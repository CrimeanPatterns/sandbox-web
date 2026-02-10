<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\Event;

class EventBooked {

	/** @var Event $parent */
	protected $parent;

	public function __construct(Event $parent) {
		$this->parent = $parent;
	}

	/**
	 * @param $d
	 * @return EventBooked
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function start($d) {
		$this->parent->setStartDate($d);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param string $format
	 * @param bool $after
	 * @return EventBooked
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function start2($date, $relative = null, $format = '%D% %Y%', $after = true) {
		$this->parent->parseStartDate($date, $relative, $format, $after);
		return $this;
	}

	/**
	 * @param $d
	 * @return EventBooked
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function end($d) {
		$this->parent->setEndDate($d);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param string $format
	 * @param bool $after
	 * @return EventBooked
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function end2($date, $relative = null, $format = '%D% %Y%', $after = true) {
		$this->parent->parseEndDate($date, $relative, $format, $after);
		return $this;
	}

	/**
	 * @return EventBooked
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function noStart() {
		$this->parent->setNoStartDate(true);
		return $this;
	}

	/**
	 * @return EventBooked
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function noEnd() {
		$this->parent->setNoEndDate(true);
		return $this;
	}

	/**
	 * @param $g
	 * @return EventBooked
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function guests($g) {
		$this->parent->setGuestCount($g);
		return $this;
	}

    /**
     * @param $k
     * @return EventBooked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function kids($k) {
        $this->parent->setKidsCount($k);
        return $this;
    }

	/**
	 * @param $seats
	 * @return EventBooked
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function seats($seats) {
		$this->parent->setSeats($seats);
		return $this;
	}

	/**
	 * @param $seat
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return EventBooked
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function seat($seat, $allowEmpty = false, $allowNull = false) {
		$this->parent->addSeat($seat, $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @return EventBooked
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function host()
    {
        $this->parent->setHost(true);
        return $this;
    }

}