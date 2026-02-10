<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\BaseSegment;

class Point {

	/** @var  BaseSegment $segment */
	protected $segment;
	
	protected $type;
	
	public function __construct(BaseSegment $segment, $type) {
		$this->segment = $segment;
		$this->type = $type;
	}

	/**
	 * @param $code
	 * @return Point
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function code($code) {
		if ($this->type === 'd')
			$this->segment->setDepCode($code);
		else
			$this->segment->setArrCode($code);
		return $this;
	}

	/**
	 * @param $name
	 * @return Point
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function name($name) {
		if ($this->type === 'd')
			$this->segment->setDepName($name);
		else
			$this->segment->setArrName($name);
		return $this;
	}

    /**
     * @param $tip
     * @return Point
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
	public function geoTip($tip)
    {
        if ($this->type === 'd') {
            $this->segment->setDepGeoTip($tip);
        }
        else {
            $this->segment->setArrGeoTip($tip);
        }
        return $this;
    }

	/**
	 * @param $address
	 * @return Point
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function address($address) {
		if ($this->type === 'd')
			$this->segment->setDepAddress($address);
		else
			$this->segment->setArrAddress($address);
		return $this;
	}

	/**
	 * @param $date
	 * @return Point
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function date($date) {
		if ($this->type === 'd')
			$this->segment->setDepDate($date);
		else
			$this->segment->setArrDate($date);
		return $this;
	}

	/**
	 * @param $date
	 * @param $relative
	 * @param string $format
	 * @param bool $after
	 * @return Point
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function date2($date, $relative = null, $format = '%D% %Y%', $after = true) {
		if ($this->type === 'd')
			$this->segment->parseDepDate($date, $relative, $format, $after);
		else
			$this->segment->parseArrDate($date, $relative, $format, $after);
		return $this;
	}

	/**
	 * @return Point
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function noDate() {
		if ($this->type === 'd')
			$this->segment->setNoDepDate(true);
		else
			$this->segment->setNoArrDate(true);
		return $this;
	}

    /**
     * @return Point
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function strict()
    {
        $this->segment->setDatesStrict(true);
        return $this;
    }

}