<?php

class UserInputError extends CheckException {

	public function __construct($message) {
		if (!$message)
			DieTrace('Exception message should not be empty');
		parent::__construct($message, ACCOUNT_INVALID_USER_INPUT);
	}

}
