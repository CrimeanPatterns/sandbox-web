<?php

namespace AwardWallet\Schema\Parser\Component\Field;

class Validator {

	public static function validateField(&$value, $type, $property, $attr, $allowEmpty, $allowNull) {
		if (is_string($value)) {
			$value = trim($value);
			if (strlen($value) === 0) {
				if (!$allowEmpty)
					return 'empty string is not allowed';
				else
					return null;
			}
		}
		if (is_null($value)) {
			if (!$allowNull)
				return '`null` value is not allowed';
			else
				return null;
		}

		/**
		 * @var \AwardWallet\Schema\Parser\Component\Field\FieldInterface $class
		 */
		$class = sprintf('\\AwardWallet\\Schema\\Parser\\Component\\Field\\%s', $type);
		return $class::validate($value, $property, $attr);
	}

}