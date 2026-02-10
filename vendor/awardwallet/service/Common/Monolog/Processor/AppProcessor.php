<?php

namespace AwardWallet\Common\Monolog\Processor;

use AwardWallet\Common\Strings;
use Monolog\Logger;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class AppProcessor {

    /**
     * @var int
     */
    private $userid;

    /**
     * @var string
     */
    private $requestId;
    private $appName;
    /**
     * @var int
     */
    private $pid;
    /**
     * @var bool
     */
    private $debug;
    /**
     * @var array
     */
    private $context = [];
    /**
     * @var string
     */
    private $cluster;

    public function __construct($appName, bool $debug = false)
    {
        $this->appName = $appName;
        $this->pid = getmypid();
        $this->setNewRequestId();
        $this->debug = $debug;
    }

    /**
     * @param int $userid
     */
    public function setUserid($userid)
    {
        $this->userid = (int)$userid;
    }

	public function processRecord(array $record){
        if (!$this->debug) {
            $record['extra']['app'] = $this->appName;
            $record['extra']['pid'] = $this->pid;
            if (isset($this->requestId)) {
                $record['RequestID'] = $this->requestId;
            }
        }

        if (isset($this->userid)) {
            $record['UserID'] = $this->userid;
        }
        
        if ($this->cluster !== null) {
            $record['extra']['cluster'] = $this->cluster;
        }

        $record['message'] = Strings::cutInMiddle($record['message'], 8192);

        if (
            $record['level'] == Logger::WARNING
            && TraceProcessor::isSupressedMessage($record['message'])
        ) {
            // this error will be handled by MysqlComeBack bundle, suppress
            $record['level'] = Logger::DEBUG;
            $record['level_name'] = Logger::getLevelName($record['level']);
            unset($record['context']['scope_vars']);
            unset($record['context']['stack']);
            return $record;
        }

        if (TraceProcessor::isDeprecatedMessage($record['message'])) {
            $record['level'] = Logger::DEBUG;
            $record['level_name'] = Logger::getLevelName($record['level']);
            unset($record['context']['scope_vars']);
            unset($record['context']['stack']);
        }

        $record['context'] = array_merge($this->context, $record['context']);

		return $record;
	}

	public function getRequestId()
    {
        return $this->requestId;
    }

	public function getUserId()
    {
        return $this->userid;
    }

    public function setMyPid(int $pid)
    {
        $this->pid = $pid;
    }

    public function setNewRequestId()
    {
        $this->requestId = bin2hex(openssl_random_pseudo_bytes(4));
    }

    public function setContext(array $context) : void
    {
        $this->context = array_merge($this->context, $context);
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $response->headers->set('X-RequestId', $this->requestId);

    }
    
    public function setCluster(string $cluster) : void
    {
        $this->cluster = $cluster;
    }

}