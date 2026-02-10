<?php

namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Common\DateTimeUtils;
use AwardWallet\Schema\Parser\Component\Base;

class CruiseSegment extends Base {

	/**
	 * @parsed Field
	 * @attr type=basic
	 * @attr length=medium
	 */
	protected $name;
	/**
	 * @parsed Field
	 * @attr type=clean
	 * @attr length=short
	 */
	protected $code;
	/**
	 * @parsed DateTime
	 */
	protected $ashore;
	/**
	 * @parsed DateTime
	 */
	protected $aboard;

	/**
	 * @return mixed
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param mixed $name
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return CruiseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setName($name, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($name, 'name', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * @param mixed $code
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return CruiseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setCode($code, $allowEmpty = false, $allowNull = false) {
		$this->setProperty($code, 'code', $allowEmpty, $allowNull);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getAshore() {
		return $this->ashore;
	}

	/**
	 * @param mixed $ashore
	 * @return CruiseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setAshore($ashore) {
		$this->setProperty($ashore, 'ashore', false, false);
		return $this;
	}

	/**
	 * @param $date
	 * @param int $relative
	 * @param string $format
	 * @param bool $after
	 * @return $this
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function parseAshore($date, $relative = null, $format = '%D% %Y%', $after = true) {
		$this->parseUnixTimeProperty($date, 'ashore', $relative, $after, $format);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getAboard() {
		return $this->aboard;
	}

	/**
	 * @param mixed $aboard
	 * @return CruiseSegment
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function setAboard($aboard) {
		$this->setProperty($aboard, 'aboard', false, false);
		return $this;
	}

	/**
	 * @param $date
	 * @param int $relative
	 * @param string $format
	 * @param bool $after
	 * @return $this
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function parseAboard($date, $relative = null, $format = '%D% %Y%', $after = true) {
		$this->parseUnixTimeProperty($date, 'aboard', $relative, $after, $format);
		return $this;
	}

	/**
	 * @return Base[]
	 */
	protected function getChildren() {
		return [];
	}


    public function validateBasic()
    {
        if (empty($this->ashore) && empty($this->aboard) && empty($this->name))
            $this->invalid('empty segment');
        if ($this->ashore && $this->aboard) {
            if ($this->aboard < $this->ashore) {
                $this->invalid('invalid dates');
            }
            if (abs($this->aboard - $this->ashore) > 20 * DateTimeUtils::SECONDS_PER_DAY) {
                $this->invalid('dates are too far apart');
            }
        }
        return $this->valid;
    }

    /**
     * checks data and sets valid flag
     * @return bool
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function validateData()
    {
        if (empty($this->name) && empty($this->code))
            $this->invalid('empty port');
        if (empty($this->ashore) && empty($this->aboard))
            $this->invalid('aboard/ashore dates are required');
        return $this->valid;
    }

}