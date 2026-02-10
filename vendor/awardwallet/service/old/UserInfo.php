<?php

class UserInfo
{

	/**
	 * @var array
	 */
	public $cookies;

	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var string
	 */
	public $userAgent;

	public function __construct($id, array $cookies, $userAgent){
		$this->id = $id;
		$this->cookies = $cookies;
		$this->userAgent = $userAgent;
	}

}