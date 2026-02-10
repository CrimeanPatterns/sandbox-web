<?php

class StatLogger
{

	/**
	 * @var Psr\Log\LoggerInterface
     */
	private static $logger;

	/**
	 * @return Psr\Log\LoggerInterface
     */
	public static function getInstance(){

        if (self::$logger === null) {
            self::$logger = new \Monolog\Logger('stat', [new \Monolog\Handler\NullHandler()]);
        }

		return self::$logger;
	}

    /**
     * called from Loaded on loyalty
     */
	public static function setLogger(\Psr\Log\LoggerInterface $logger){
        self::$logger = $logger;
    }
}
