<?php

namespace AwardWallet\Schema\Parser\Common\Shortcut;


use AwardWallet\Schema\Parser\Common\Rental;

class RentalExtra {

	/** @var Rental $parent */
	protected $parent;

	public function __construct(Rental $parent) {
		$this->parent = $parent;
	}

	/**
	 * @param $code
	 * @param $name
	 * @return RentalExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function discount($code, $name) {
		$this->parent->addDiscount($code, $name);
		return $this;
	}

	/**
	 * @param $name
	 * @param $charge
	 * @return RentalExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function equip($name, $charge) {
		$this->parent->addEquipment($name, $charge);
		return $this;
	}

	/**
	 * @param $company
	 * @param bool $allowEmpty
	 * @param bool $allowNull
	 * @return RentalExtra
	 * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
	 */
	public function company($company, $allowEmpty = false, $allowNull = false) {
		$this->parent->setCompany($company, $allowEmpty, $allowNull);
		return $this;
	}

    /**
     * @return RentalExtra
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function host()
    {
        $this->parent->setHost(true);
        return $this;
    }

}