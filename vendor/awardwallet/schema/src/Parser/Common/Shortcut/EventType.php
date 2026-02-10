<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\Event;

class EventType {

	/** @var Event $parent */
	protected $parent;

	public function __construct(Event $parent) {
		$this->parent = $parent;
	}

	/**
	 * @return EventType
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function restaurant() {
		$this->parent->setEventType(Event::TYPE_RESTAURANT);
		return $this;
	}
	/**
	 * @return EventType
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function meeting() {
		$this->parent->setEventType(Event::TYPE_MEETING);
		return $this;
	}
	/**
	 * @return EventType
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function show() {
		$this->parent->setEventType(Event::TYPE_SHOW);
		return $this;
	}
	/**
	 * @return EventType
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function event() {
		$this->parent->setEventType(Event::TYPE_EVENT);
		return $this;
	}
}