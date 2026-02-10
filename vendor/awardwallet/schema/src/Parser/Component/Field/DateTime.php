<?php

namespace AwardWallet\Schema\Parser\Component\Field;


class DateTime implements FieldInterface {

	protected static $default = [
		'seconds' => false,
	];

	/**
	 * @param $value
	 * @param $property
	 * @param $attr
	 * @return string
	 */
	public static function validate(&$value, $property, $attr) {
		$attr = array_merge(self::$default, $attr);
		if (!is_int($value))
			return 'invalid value type';
		elseif ($value < strtotime('2000-01-01'))
			return 'datetime value is too old';
		elseif (!$attr['seconds'] && $value % 60 !== 0)
			return 'datetime value contains seconds';
		return null;
	}
}