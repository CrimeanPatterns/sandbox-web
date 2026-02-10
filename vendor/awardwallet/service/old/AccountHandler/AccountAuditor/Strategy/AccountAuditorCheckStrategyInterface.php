<?php

interface AccountAuditorCheckStrategyInterface {
	
	/**
	 * @param Account $account
	 * @param AuditorOptions $options
	 * 
	 * @return AccountCheckReport
	 */
	public function check(Account $account, AuditorOptions $options);
	
}

?>