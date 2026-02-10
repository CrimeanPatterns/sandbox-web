<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\Event;

class EventPlace {

	/** @var Event $parent */
	protected $parent;

	public function __construct(Event $parent) {
		$this->parent = $parent;
	}

	/**
	 * @param $s
	 * @return EventPlace
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function address($s) {
		$this->parent->setAddress($s);
		return $this;
	}

	/**
	 * @param $s
	 * @return EventPlace
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function name($s) {
		$this->parent->setName($s);
		return $this;
	}

	/**
	 * @param $s
	 * @return EventPlace
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function type($s) {
		$this->parent->setEventType($s);
		return $this;
	}

	/**
	 * @param $phone
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return EventPlace
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function phone($phone, $allowEmpty = false, $allowNull = false) {
		$this->parent->setPhone($phone, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $fax
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return EventPlace
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function fax($fax, $allowEmpty = false, $allowNull = false) {
		$this->parent->setFax($fax, $allowEmpty, $allowNull);
		return $this;
	}
}