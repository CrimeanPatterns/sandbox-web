<?php

namespace AwardWallet\MainBundle\Service\Itinerary;

trait Loggable {

	/** @var \Psr\Log\LoggerInterface */
	public $logger;

	public $prefix;

	protected function logPropertySetting($setterMethodName, $args) {
		if (!$this->logger)
			return null;
		if (count($args) != 1) {
			$this->logger->error("'Loggable' trait error in $setterMethodName: Arguments array should consist of only one value, got ".var_export($args, true));
			return;
		}
		if (preg_match('#::set(.*)#i', $setterMethodName, $m)) {
			$propertyName = lcfirst($m[1]);
			$value = $args[0];
			$this->logger->debug('Setting "'.($this->prefix ? "$this->prefix->" : '').$propertyName.'" to '.var_export($value, true).'');
		} else {
			$this->logger->error("'Loggable' trait error: Invalid method $setterMethodName");
		}
	}

}
