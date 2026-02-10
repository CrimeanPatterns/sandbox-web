<?php

interface AccountAuditorInterface {
	
	public function setCheckOptions(AuditorOptions $options);
	
	public function check();
	
	public function save($account, AccountCheckReport $report, AuditorOptions $options);
	
	public function getReport();
	
}

?>