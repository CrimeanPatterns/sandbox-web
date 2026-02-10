<?php

class EngineError extends CheckException {

	public function __construct($message) {
		if (!$message)
			DieTrace('Exception message should not be empty');
		parent::__construct($message, ACCOUNT_ENGINE_ERROR); // TODO: Another code specially for new service?
	}

}
