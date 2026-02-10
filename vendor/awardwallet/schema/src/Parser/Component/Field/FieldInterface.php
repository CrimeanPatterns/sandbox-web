<?php

namespace AwardWallet\Schema\Parser\Component\Field;


interface FieldInterface {

	/**
	 * @param $value
	 * @param $property
	 * @param $attr
	 * @return string
	 */
	public static function validate(&$value, $property, $attr);

}