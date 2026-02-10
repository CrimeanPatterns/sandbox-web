<?php

class AccountCheckerLogger extends CheckerLogger {

	/** @var TAccountChecker $checker */
	protected $checker;
    /**
     * @var \Monolog\Formatter\LineFormatter
     */
	protected $formatter;

	public function __construct($checker) {
	    parent::__construct($checker->http);
	}

    public function refreshHttp($http) {
        if ($this->http)
            return;
        $this->http = $http;
    }

}
