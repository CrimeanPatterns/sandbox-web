<?php

class CheckException extends Exception {
	
	public function __construct($message, $code = null) {
		if (is_null($code))
			$code = ACCOUNT_PROVIDER_ERROR;

        if (empty($message))
            DieTrace("You can’t throw emtpy message");

		if($code == ACCOUNT_QUESTION)
			DieTrace("You can't throw questions, use TAccountChecker->AskQuestion method");
			
        parent::__construct($message, $code);
    }
	
}

?>