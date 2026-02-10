<?php

namespace AwardWallet\Schema\Parser\Component\Field;


class Arr implements FieldInterface {

	protected static $default = [
		'item' => 'Field',
		'item_' => [],
		'unique' => false
	];

	/**
	 * @param $value
	 * @param $property
	 * @param $attr
	 * @return string
	 */
	public static function validate(&$value, $property, $attr) {
		$attr = array_merge(self::$default, $attr);
		if (!is_array($value))
			return 'array required';
		$new = [];
		foreach ($value as $item) {
			$error = self::validateItem($item, $new, $attr, true, true);
			if (!empty($error))
				return $error;
			if (!is_string($item) || strlen((string)$item) > 0)
				$new[] = $item;
		}
		$value = $new;
		return null;
	}

	public static function validateItem(&$value, $property, $attr, $allowEmpty, $allowNull) {
		$attr = array_merge(self::$default, $attr);
		$error = Validator::validateField($value, $attr['item'], $property, $attr['item_'], $allowEmpty, $allowNull);
		return $error;
	}
}