<?php

namespace AwardWallet\Schema\Parser\Component\Field;


class KeyValue {

	protected static $default = [
		'key' => 'Field',
		'val' => 'Field',
		'key_' => [],
		'val_' => [],
		'unique' => false,
	];

	public static function validatePair(&$key, &$val, $property, array $attr, bool $allowEmptyKey, bool $allowNullKey, bool $allowEmptyVal, bool $allowNullVal, array $keyAttr) {
		$attr = array_merge(self::$default, $attr);
        $attr['key_'] = array_merge($attr['key_'], $keyAttr);
		$error = Validator::validateField($key, $attr['key'], $property, $attr['key_'], $allowEmptyKey, $allowNullKey);
		if (empty($error))
			$error = Validator::validateField($val, $attr['val'], $property, $attr['val_'], $allowEmptyVal, $allowNullVal);
		return $error;
	}
}