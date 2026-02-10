<?php

namespace AwardWallet\Schema\Parser\Component;


use AwardWallet\Schema\Parser\Common\DetailedAddress;

interface HasDetailedAddress {

	/**
	 * @param $option
	 * @return DetailedAddress
	 */
	public function obtainDetailedAddress($option);

}