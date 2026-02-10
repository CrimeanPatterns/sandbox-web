<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\TransferSegment;

class TransferExtra {
	
	/** @var TransferSegment $segment */
	protected $segment;

	public function __construct(TransferSegment $segment) {
		$this->segment = $segment;
	}

	/**
	 * @param $type
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TransferExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function type($type, $allowEmpty = false, $allowNull = false) {
		$this->segment->setCarType($type, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $model
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TransferExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function model($model, $allowEmpty = false, $allowNull = false) {
		$this->segment->setCarModel($model, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $image
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TransferExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function image($image, $allowEmpty = false, $allowNull = false) {
		$this->segment->setCarImageUrl($image, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $adults
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TransferExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function adults($adults, $allowEmpty = false, $allowNull = false) {
		$this->segment->setAdults($adults, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $kids
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TransferExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function kids($kids, $allowEmpty = false, $allowNull = false) {
		$this->segment->setKids($kids, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $miles
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TransferExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function miles($miles, $allowEmpty = false, $allowNull = false) {
		$this->segment->setMiles($miles, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $duration
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TransferExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function duration($duration, $allowEmpty = false, $allowNull = false) {
		$this->segment->setDuration($duration, $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @param $status
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return TransferExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function status($status, $allowEmpty = false, $allowNull = false) {
		$this->segment->setStatus($status, $allowEmpty, $allowNull);
		return $this;
	}
	
}