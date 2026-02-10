<?php


class SeleniumDebugRequest
{

	/**
	 * @var string
	 */
	public $url;
	/**
	 * @var WebDriverCommand
	 */
	public $command;

	/**
	 * @param string $url
	 * @param WebDriverCommand $command
	 */
	public function __construct($url, $command){
		$this->url = $url;
		$this->command = $command;
	}

}