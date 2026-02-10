<?php

namespace AwardWallet\Schema\Parser\Component;


class Options {

	public $throwOnInvalid = true;

	public $checkProviderProperties = false;

	public $clearNamePrefix = true;

	public $logDebug = false;

	public $logContext = ['component' => 'parser'];

}