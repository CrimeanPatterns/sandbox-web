<?php

namespace AwardWallet\Schema\Parser\Component;


use AwardWallet\Schema\Parser\Common\TravelAgency;

interface HasTravelAgency {

	/**
	 * @return TravelAgency
	 */
	public function obtainTravelAgency();

}