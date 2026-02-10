<?php


class AccountCheckerLoggerHandler extends \Monolog\Handler\AbstractHandler
{

    /**
     * @var AccountCheckerLogger $logger
     */
    private $logger;

    public function __construct(AccountCheckerLogger $logger)
    {
        parent::__construct(\Monolog\Logger::DEBUG, true);
        $this->logger = $logger;
    }

    public function handle(array $record)
    {
        switch(strtolower($record['level_name'])) {
            case 'debug':
                $this->logger->debug($record['message']);
                break;
            case 'notice':
                $this->logger->notice($record['message']);
                break;
        }
    }

}