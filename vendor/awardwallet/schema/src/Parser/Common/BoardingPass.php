<?php

namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Common\Parser\Util\NameHelper;
use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Component\InvalidDataException;

class BoardingPass extends Base {

	/**
	 * @parsed Field
	 * @attr regexp=/^[A-Z]{3}$/
	 */
	protected $depCode;
	/**
	 * @parsed DateTime
	 */
	protected $depDate;
	/**
	 * @parsed Field
	 * @attr regexp=/^([A-Z][A-Z\d]|[A-Z\d][A-Z])?\s*\d{1,5}$/
	 */
	protected $flightNumber;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=long
	 */
	protected $attachmentName;
	/**
	 * @parsed Field
	 * @attr regexp=/^https?:\/\/[\S ]+$/
	 * @attr maxlength=2000
	 */
	protected $url;
	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $traveller;
	/**
	 * @parsed Field
	 * @attr regexp=/^[A-Z\d]{5,7}$/
	 */
	protected $recordLocator;

	/**
	 * @return mixed
	 */
	public function getDepCode() {
		return $this->depCode;
	}

	/**
	 * @param mixed $depCode
	 * @return BoardingPass
	 * @throws InvalidDataException
	 */
	public function setDepCode($depCode) {
		$this->setProperty($depCode, 'depCode', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getDepDate() {
		return $this->depDate;
	}

	/**
	 * @param mixed $depDate
	 * @return BoardingPass
	 * @throws InvalidDataException
	 */
	public function setDepDate($depDate) {
		$this->setProperty($depDate, 'depDate', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getFlightNumber() {
		return $this->flightNumber;
	}

	/**
	 * @param mixed $flightNumber
	 * @return BoardingPass
	 * @throws InvalidDataException
	 */
	public function setFlightNumber($flightNumber) {
		$this->setProperty($flightNumber, 'flightNumber', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getAttachmentName() {
		return $this->attachmentName;
	}

	/**
	 * @param mixed $attachmentName
	 * @return BoardingPass
	 * @throws InvalidDataException
	 */
	public function setAttachmentName($attachmentName) {
		$this->setProperty($attachmentName, 'attachmentName', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @param mixed $url
	 * @return BoardingPass
	 * @throws InvalidDataException
	 */
	public function setUrl($url) {
		$this->setProperty($url, 'url', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getTraveller() {
		return $this->traveller;
	}

	/**
	 * @param mixed $traveller
	 * @return BoardingPass
	 * @throws InvalidDataException
	 */
	public function setTraveller($traveller) {
		$this->setProperty($traveller, 'traveller', false, false);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getRecordLocator() {
		return $this->recordLocator;
	}

	/**
	 * @param mixed $recordLocator
	 * @return BoardingPass
	 * @throws InvalidDataException
	 */
	public function setRecordLocator($recordLocator) {
		$this->setProperty($recordLocator, 'recordLocator', false, false);
		return $this;
	}

	/**
	 * checks data and sets valid flag
	 * @return bool
	 * @throws InvalidDataException
	 */
	public function validate() {
	    if (empty($this->flightNumber) || empty($this->depCode) && empty($this->depDate))
	        $this->invalid('missing data');
		if (empty($this->attachmentName) && empty($this->url))
			$this->invalid('missing attachmentName or url');
		if (!empty($this->traveller)) {
		    $this->traveller = NameHelper::removePrefix($this->traveller);
        }
		return $this->valid;
	}

	/**
	 * @return Base[]
	 */
	protected function getChildren() {
		return [];
	}
}