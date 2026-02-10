<?php


namespace AwardWallet\Common\Monolog\Handler;


use AwardWallet\Common\Monolog\Formatter\StubFormatter;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class ArrayHandler extends AbstractProcessingHandler
{
    /**
     * @var array
     */
    private $records;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @return array
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     *
     * @return void
     */
    protected function write(array $record)
    {
        if ($this->enabled) {
            $this->records[] = $record;
        }
    }

    public function __construct($level = Logger::DEBUG, $bubble = true, ?bool $enabled = null)
    {
        parent::__construct($level, $bubble);

        $this->setFormatter(new StubFormatter);

        $this->enabled = $enabled || ($enabled === null && 'cli' !== php_sapi_name());
    }

}
