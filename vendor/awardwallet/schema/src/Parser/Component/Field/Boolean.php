<?php

namespace AwardWallet\Schema\Parser\Component\Field;


class Boolean implements FieldInterface {

	/**
	 * @param $value
	 * @param $property
	 * @param $attr
	 * @return string
	 */
	public static function validate(&$value, $property, $attr) {
		if (!is_bool($value))
			return 'boolean value is required';
		return null;
	}
}