<?php

namespace AwardWallet\Common\Monolog\Handler;

use AwardWallet\Common\Strings;
use Fluent\Logger\Entity;
use Fluent\Logger\FluentLogger;
use Fluent\Logger\PackerInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Class FluentHandler
 */
class FluentHandler extends AbstractProcessingHandler
{

    private const DEFAULT_RECURSION_LEVEL = 5;
    public const MAX_RECURSION_LEVEL_KEY = 'maxLogRecursionLevel';

    protected static $logIndex = 0;
	/**
	 * @var FluentLogger
	 */
	protected $logger;
	private $milliSecsSupported = false;

	/**
	 * Initialize Handler
	 *
	 * @param FluentLogger $logger
	 * @param bool|string $host
	 * @param int $port
	 * @param int $level
	 * @param bool $bubble
	 */
	public function __construct(
		$logger = null,
		$host = FluentLogger::DEFAULT_ADDRESS,
		$port = FluentLogger::DEFAULT_LISTEN_PORT,
		$level = Logger::INFO,
		$bubble = true,
        $msgPack = false
	)
	{
		parent::__construct($level, $bubble);

		$this->milliSecsSupported = $msgPack && class_exists('AwardWallet\Common\Monolog\Handler\FluentMilliSecsMsgPacker');

		if (is_null($logger)) {
		    $packer = null;

		    if ($msgPack) {
		        $packer = $this->createMsgPackPacker();
            }

			$logger = new FluentLogger($host, $port, [], $packer);
			$logger->registerErrorHandler(function(FluentLogger $logger, Entity $entity, $error){
                error_log($entity->getData()["message"] . ', fluent error: ' . $error);
            });
		}

		$this->logger = $logger;
	}

	/**
	 * {@inheritDoc}
	 */
	public function write(array $record): void
	{
		$data = array();
        $data['idx'] = self::$logIndex++;
		$data['level'] = Logger::getLevelName($record['level']);
		$data['facility'] = 'php';
		$data['message'] = $record['message'];
		$data['context'] = $record['context'];
		$data['channel'] = $record['channel'];
        unset($data['context']['scope_vars']);

        if (isset($record['extra'])) {
            $data['extra']   = $record['extra'];
        }

        if (isset($record['RequestID'])) {
            $data['RequestID'] = (string)$record['RequestID'];
        }

        if (isset($record['UserID'])) {
            $data['UserID'] = (int)$record['UserID'];
        }

        foreach ($record['context'] as $fieldName => &$fieldValue) {
            if (
                is_numeric($fieldValue) &&
                ('userid' === strtolower(preg_replace('/[^a-z]/i', '', $fieldName)))
            ) {
                $data['UserID'] = (int)$fieldValue;

                break;
            }
        }

        $maxRecursionLevel = (int) ($data['context'][self::MAX_RECURSION_LEVEL_KEY] ?? self::DEFAULT_RECURSION_LEVEL);
        unset($data['context'][self::MAX_RECURSION_LEVEL_KEY]);

        if ($maxRecursionLevel < 0 || $maxRecursionLevel > 100) {
            $maxRecursionLevel = self::DEFAULT_RECURSION_LEVEL;
        }

        $data = $this->trim($data, 0, $maxRecursionLevel);

        if ($this->milliSecsSupported) {
            $this->logger->post2(new MilleSecsEntity('php', $data, $record['datetime']));
        }
        else {
            $this->logger->post('php', $data);
        }
	}

    private function trim($data, int $level, int $maxRecursionLevel)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
            if (empty($data)) {
                return [];
            }
        }

        $result = array_map(function ($value) use ($level, $maxRecursionLevel) {
            if (is_array($value) || is_object($value)) {
                if ($level > $maxRecursionLevel) {
                    return "too deep";
                }
                return $this->trim($value, $level + 1, $maxRecursionLevel);
            }

            if (is_string($value)) {
                return Strings::cutInMiddle($value, 16384);
            }

            if (is_resource($value)) {
                return "resource";
            }

            return $value;
        }, $data);

        $result = array_filter($result, function($v) {
            return $v !== [];
        });

        return $result;
    }

    private function createMsgPackPacker() : PackerInterface
    {
        if ($this->milliSecsSupported)
            return new FluentMilliSecsMsgPacker();

        return new \Fluent\Logger\MsgpackPacker();

    }

}
