<?php

class AccountCheckReport {
	
	public $balance = null;
	public $properties = array();
	public $errorCode = ACCOUNT_ENGINE_ERROR;
	public $errorReason = null;
	public $errorMessage = 'Unknown error';
	public $debugInfo = null;
	public $question;
	public $logPath;
	public $warnings;
	public $traffic = 0;
	public $duration = 0;
	public $browserState;
	public $files = array();
	public $invalidAnswers = array();
	public $options = [];
	public $providerCode = null;
	public $isTransfer = false;
	/** @var TAccountChecker */
	public $checker;
    /**
     * @var int one of UpdaterEngineInterface::SOURCE_ constants
     */
    public $source;

	/**
	 * @var Account $account
	 */
	public $account;

    private static $purifier;

	/*
	 * filter out some symbols before saving to database
	 */
	public function filter(){
		global $arAccountErrorCode;
		$accountInfo = $this->account->getAccountInfo(false);
        if ($this->balance > 1000000000) {
            if (isset($this->checker) && $this->errorCode !== ACCOUNT_ENGINE_ERROR) {
                $this->checker->logger->notice('errorCode was changed');
            }
            $this->debugInfo = 'Balance too big';
            $this->errorCode = ACCOUNT_ENGINE_ERROR;
        }
		$this->balance = filterBalance($this->balance, $accountInfo['AllowFloat'] == '1');

		$this->errorCode = intval($this->errorCode);
		if(!isset($arAccountErrorCode[$this->errorCode]))
			$this->errorCode = ACCOUNT_ENGINE_ERROR;

		# Filter properties
        $allowHtml = [];
        if ($this->checker instanceof TAccountChecker) {
            $allowHtml = $this->checker->getAllowHtmlProperties();
        }
		FilterAccountProperties($this->properties, $accountInfo, true, $allowHtml);
        foreach ($this->properties['SubAccounts'] ?? [] as $index => $subAccount) {
            if (strlen($subAccount['Code'] ?? '') > 250) {
                unset($this->properties['SubAccounts'][$index]['Code']);
            }
        }

		$this->errorMessage =  CleanXMLValue($this->errorMessage);

        if(empty(self::$purifier)) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'a[href]');
            $config->set('HTML.TargetBlank', true);
            $config->set('Cache.DefinitionImpl', null);
            self::$purifier = new \HTMLPurifier($config);
        }
        $this->errorMessage = self::$purifier->purify($this->errorMessage);

		$this->errorReason =  CleanXMLValue($this->errorReason);

		$this->debugInfo =  CleanXMLValue($this->debugInfo);

		if (($this->errorCode == ACCOUNT_INVALID_PASSWORD) && ($this->errorMessage == "")){
			$this->errorMessage = 'Invalid username or password';
		}

		if (($this->errorCode == ACCOUNT_QUESTION) && $this->errorMessage == "Unknown error"){
			$this->errorMessage = $this->question;
		}

		unset($this->properties['ParseIts']);

		if ($this->errorCode == ACCOUNT_CHECKED && !$this->isTransfer)
			$this->errorMessage = '';

		if ($this->balance == 0 && isset($this->properties['AccountExpirationDate']) && ($this->properties['AccountExpirationDate'] !== false))
			unset($this->properties['AccountExpirationDate']);

		$this->filterItineraries();
	}

	protected function filterItineraries()
	{
		// seats
		if (isset($this->properties['Itineraries']) && is_array($this->properties['Itineraries'])) {
			foreach($this->properties['Itineraries'] as $kit => $it) {
				if (isset($it['TripSegments']) && is_array($it['TripSegments'])) {
					foreach($it['TripSegments'] as $kts => $ts) {
						if (isset($ts['Seats']) && is_string($ts['Seats'])) {
							$parts = array_map("trim", explode(",", trim($ts['Seats'])));
							foreach ($parts as $i => $seat) {
								if (!preg_match("/^\w*\d+\w*(\s+\([^\)]+\))?$/ims", $seat)) {
									unset($parts[$i]);
								}
							}
							if (!sizeof($parts)) {
								unset($this->properties['Itineraries'][$kit]['TripSegments'][$kts]['Seats']);
							} else {
								$this->properties['Itineraries'][$kit]['TripSegments'][$kts]['Seats'] = implode(", ", $parts);
							}
						}
					}
				}
			}
		}
	}
	
}

