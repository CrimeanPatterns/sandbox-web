<?php


namespace AwardWallet\Common\Parsing\Solver\Extra;


class Extra {

	/** @var SolverData $solverData */
	public $solverData;
	/** @var ProviderData $provider */
	public $provider;
    /** @var ProviderData $originalParserProvider */
    public $originalParserProvider;
	/** @var Data $data */
	public $data;
	/** @var Settings $settings */
	public $settings;
	/** @var Context $context */
	public $context;

	public function __construct() {
		$this->data = new Data();
		$this->solverData = new SolverData();
		$this->settings = new Settings();
		$this->context = new Context();
	}

}