<?php

namespace AwardWallet\Schema\Parser\Component;


use AwardWallet\Schema\Parser\Common\Price;

interface HasPrice {

	/**
	 * @return Price
	 */
	public function obtainPrice();

}