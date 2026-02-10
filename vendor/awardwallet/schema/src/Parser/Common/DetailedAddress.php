<?php

namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Schema\Parser\Component\Base;

class DetailedAddress extends Base {

	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=long
	 */
	protected $addressLine;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $city;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $state;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $country;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=short
	 */
	protected $zip;

	/**
	 * @return string
	 */
	public function getAddressLine() {
		return $this->addressLine;
	}

	/**
	 * @param string $addressLine
	 * @return DetailedAddress
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setAddressLine($addressLine) {
		$this->setProperty($addressLine, 'addressLine', false, false);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCity() {
		return $this->city;
	}

	/**
	 * @param string $city
	 * @return DetailedAddress
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setCity($city) {
		$this->setProperty($city, 'city', false, false);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getState() {
		return $this->state;
	}

	/**
	 * @param string $state
	 * @return DetailedAddress
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setState($state) {
		$this->setProperty($state, 'state', false, false);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCountry() {
		return $this->country;
	}

	/**
	 * @param string $country
	 * @return DetailedAddress
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setCountry($country) {
		$this->setProperty($country, 'country', false, false);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getZip() {
		return $this->zip;
	}

	/**
	 * @param string $zip
	 * @return DetailedAddress
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setZip($zip) {
		$this->setProperty($zip, 'zip', false, false);
		return $this;
	}

	public function isFull()
    {
        return !empty($this->addressLine) && !empty($this->city) && !empty($this->country);
    }

	/**
	 * checks data and sets valid flag
	 * @return void
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function validate() {
	    if (!$this->isFull()) {
            $this->invalid('empty detailed address');
        }
	}

	public function implode()
    {
        if (!$this->isFull()) {
            return null;
        }
        $parts = [$this->addressLine, $this->city];
        if (!empty($this->state)) {
            $parts[] = $this->state;
        }
        $parts[] = $this->country;
        if (!empty($this->zip)) {
            $parts[] = $this->zip;
        }
        return implode(', ', $parts);
    }

	/**
	 * @return Base[]
	 */
	protected function getChildren() {
		return [];
	}
}