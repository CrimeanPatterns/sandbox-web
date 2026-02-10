<?php

class AuditorOptions {
	## Common
	public $checkIts					= null; // auto
	public $checkHistory				= null; // auto
	public $checkFiles					= false;
	public $historyStartDate			= null;
	public $filesStartDate				= null;
	public $preventLockouts				= true;
	public $saveLog						= true;
	public $keepLogs					= false;
	public $couponMark					= array();
	public $filterSubAccounts			= true;
	public $noWarnings					= true;
	public $checkedBy					= CHECKED_BY_USER;
	public $checkStrategy				= null;
    public $groupCheck                  = false;
	public $onBrowserReady				= null;
	public $onComplete					= null;
	public $timeout						= null;
	public $wsdlTimeout 				= null;
	public $dumpReport					= null;
    /** @var int UpdaterEngineInterface SOURCE:: constants */
    public $source                      = null;
    public $priority                    = null;
	/**
	 * @var TransferMilesType
	 */
	public $transferMiles				= null;
	public $transferFields				= null;
	
}

?>