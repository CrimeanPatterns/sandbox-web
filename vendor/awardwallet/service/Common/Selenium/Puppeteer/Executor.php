<?php

namespace AwardWallet\Common\Selenium\Puppeteer;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class Executor
{
    
    private const RESPONSE_BEGIN_MARKER = '[RESPONSE_BEGIN]';
    private const RESPONSE_END_MARKER = '[RESPONSE_END]';
    
    /** @var LoggerInterface */
    private $logger;
    /** @var string */
    private $webSocketAddress;
    
    public function __construct(LoggerInterface $logger, string $webSocketAddress) {
        $this->logger = $logger;
        $this->webSocketAddress = $webSocketAddress;
    }

    public function execute(string $scriptFileName, int $timeout = 10)
    {
        $this->logger->info("starting node with $scriptFileName, socket: {$this->webSocketAddress}");
        $process = new Process(["node", $scriptFileName, $this->webSocketAddress], __DIR__, [
            'NODE_PATH' => __DIR__ . '/node_modules:' . __DIR__
        ], null, $timeout);
        $receivingResponse = false;
        $process->start(function($type, $buffer) use (&$output, &$receivingResponse) {
            $lines = explode("\n", $buffer);

            foreach ($lines as $line) {
                if ($receivingResponse)
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                if (!$receivingResponse && $line === self::RESPONSE_BEGIN_MARKER) {
                    $receivingResponse = true;
                }

                if ($receivingResponse) {
                    continue;
                }

                if ($receivingResponse && $line === self::RESPONSE_END_MARKER) {
                    $receivingResponse = false;
                }

                $this->logger->log(Process::OUT === $type ? Logger::INFO : Logger::WARNING, $line);
            }

            if (Process::OUT === $type) {
                $output .= $buffer;
            }
        });
        try {
            $process->wait();
        }
        catch (ProcessTimedOutException $exception) {
            $this->logger->warning($exception->getMessage());
            $process->stop(2);
        }
        if ($process->getExitCode() !== 0) {
            throw new \Exception("failed to execute node, code {$process->getExitCode()}: " . $process->getErrorOutput());
        }
        
        $startPos = strpos($output, self::RESPONSE_BEGIN_MARKER);
        $endPos = strpos($output, self::RESPONSE_END_MARKER);
        
        if ($startPos === false || $endPos === false) {
            throw new \Exception("Could not find response markers");
        }
        
        $startPos += strlen(self::RESPONSE_BEGIN_MARKER);
        $response = substr($output, $startPos, $endPos - $startPos);
        
        $response = json_decode($response, true);
        
        return $response;
    }

}
