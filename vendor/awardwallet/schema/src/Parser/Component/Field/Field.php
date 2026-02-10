<?php

namespace AwardWallet\Schema\Parser\Component\Field;


class Field implements FieldInterface {

	const SHORT = 50;
	const MEDIUM = 250;
	const LONG = 2000;
	const EXTRA_LONG = 5000;

	const BASIC_SOFT_REGEXP = '/^[^\{\}]+$/';
	const BASIC_REGEXP = '/^[^<>\{\}]+$/';
	const CLEAN_REGEXP = '/^[^<>@$%,.\{\}]+$/';
	const SENTENCE_REGEXP = '/^[^<>@$%\{\}]+$/';
	const NUMBER_REGEXP = '/^\d+$/';
	const PROVIDER_CODE_REGEXP = '/^[a-z][a-z\d]{1,30}$/';
	const PHONE_REGEXP = '/^[-+()\dA-Z\s.,\\\\\/:]+\d+[-+()\dA-Z\s.,\\\\\/:]+$/i';
	const PRICE_REGEXP = '/^\d{1,9}([.]\d{1,3})?$/';
	const BALANCE_REGEXP = '/^[-]?\d{1,9}([.]\d{1,3})?$/';
	const CONFNO_REGEXP = '/^[\w\-\/\\\\\.\?]+$/u';
	const HAVE_NUMBER_REGEXP = '/^.*\d{1,8}([.]\d{1,3})?.*$/';
	const ONE_TIME_CODE_REGEXP = '/^[a-z\d]+$/i';

	protected static $default = [
		'type' => '', // clean, basic, number, have_number, provider, phone, price, confno
		'length' => '', // short, medium, long, extra
		'maxlength' => null,
		'minlength' => null,
		'regexp' => null,
		'max' => null, // only for numbers
        'enum' => null,
	];

	/**
	 * @param $value
	 * @param $property
	 * @param $attr
	 * @return string
	 */
	public static function validate(&$value, $property, $attr) {
		$attr = array_merge(self::$default, $attr);
		if (!is_scalar($value) || is_bool($value))
			return 'invalid value type';
		$value = (string)$value;
		if (!isset($attr['regexp'])) {
			switch ($attr['type']) {
				case 'clean':
					$attr['regexp'] = self::CLEAN_REGEXP;
					break;
                case 'sentence':
                    $attr['regexp'] = self::SENTENCE_REGEXP;
                    break;
                case 'soft':
                    $attr['regexp'] = self::BASIC_SOFT_REGEXP;
                    break;
				case 'basic':
					$attr['regexp'] = self::BASIC_REGEXP;
					break;
				case 'number':
					$attr['regexp'] = self::NUMBER_REGEXP;
					break;
				case 'have_number':
					$attr['regexp'] = self::HAVE_NUMBER_REGEXP;
					break;
				case 'provider':
					$attr['regexp'] = self::PROVIDER_CODE_REGEXP;
					break;
				case 'phone':
					$attr['regexp'] = self::PHONE_REGEXP;
					if (!isset($attr['minlength']))
						$attr['minlength'] = 5;
					if (!isset($attr['maxlength']))
						$attr['maxlength'] = 50;
					break;
				case 'price':
					$attr['regexp'] = self::PRICE_REGEXP;
					break;
                case 'balance':
                    $attr['regexp'] = self::BALANCE_REGEXP;
                    break;
				case 'confno':
					$attr['regexp'] = self::CONFNO_REGEXP;
					break;
                case 'onetimecode':
                    $attr['regexp'] = self::ONE_TIME_CODE_REGEXP;
                    break;
			}
		}
		if (isset($attr['regexp']) && preg_match($attr['regexp'], $value) === 0)
			return sprintf('value did not match regexp `%s`', $attr['regexp']);
		if (!isset($attr['maxlength'])) {
			switch ($attr['length']) {
				case 'short':
					$attr['maxlength'] = self::SHORT;
					break;
				case 'medium':
					$attr['maxlength'] = self::MEDIUM;
					break;
				case 'long':
					$attr['maxlength'] = self::LONG;
					break;
                case 'extra':
                    $attr['maxlength'] = self::EXTRA_LONG;
                    break;
			}
		}
		if (isset($attr['enum']) && !in_array($value, $attr['enum']))
		    return sprintf('value must be one of enum %s', json_encode($attr['enum']));
		if (isset($attr['minlength']) && strlen($value) < $attr['minlength'])
			return sprintf('value length must be at least %d chars', $attr['minlength']);
		if (isset($attr['maxlength']) && strlen($value) > $attr['maxlength'] && mb_strlen($value) > $attr['maxlength'])
			return sprintf('value length exceeded %d chars', $attr['maxlength']);
		if ($attr['type'] === 'number') {
			$value = intval($value);
			if (isset($attr['max']) && $value > $attr['max'])
				return sprintf('number value exceeded %d', $attr['max']);
		}
		if (in_array($attr['type'], ['balance', 'price'])) {
			$value = floatval($value);
		}
		return null;
	}

}