<?php

namespace AwardWallet\Common\Parsing;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\InputStream;

class JsExecutor
{

    private const RESPONSE_BEGIN_MARKER = '[RESPONSE_BEGIN]';
    private const RESPONSE_END_MARKER = '[RESPONSE_END]';

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $jsCode - js Code to execute, call sendResponseToPhp to return value, example: "var a = Math.round(3.6); sendResponseToPhp(a);"
     * @param $extraScriptUrls - extra script urls, like ["https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.9-1/crypto-js.js"]
     * @throws \Exception
     */
    public function executeString(string $jsCode, int $timeout = 5, $extraScriptUrls = [])
    {

        $jsCode = $this->loadScripts($extraScriptUrls) .  /** @lang JavaScript */ "
        
        function sendResponseToPhp(response) {
            console.log('" . self::RESPONSE_BEGIN_MARKER . "');
            console.log(JSON.stringify(response));
            console.log('" . self::RESPONSE_END_MARKER . "');
            process.exit(0);
        };
        
        $jsCode
        ";  
        
        $input = new InputStream();
        $this->logger->info("starting node");
        $process = new Process(["node"], __DIR__, [
            'NODE_PATH' => __DIR__ . '/node_modules:' . __DIR__
        ], null, $timeout);
        $process->setInput($input);
        $receivingResponse = false;
        $timeout = false;

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
            $input->write($jsCode);
            $input->close();
            $process->wait();
        }
        catch (ProcessTimedOutException $exception) {
            $this->logger->warning($exception->getMessage());
            $timeout = true;
            $process->stop(2);
        }

        if ($timeout) {
            throw new \Exception("Timeout executing javascript, did you forget to call sendResponseToPhp() ?");
        }

        if ($process->getExitCode() !== 0) {
            throw new \Exception("failed to execute node, code {$process->getExitCode()}: " . $process->getErrorOutput());
        }

        $startPos = strpos($output, self::RESPONSE_BEGIN_MARKER);
        $endPos = strpos($output, self::RESPONSE_END_MARKER);

        if ($startPos === false || $endPos === false) {
            throw new \Exception("Could not find response markers, did you forget to call sendResponseToPhp() ?: $output");
        }

        $startPos += strlen(self::RESPONSE_BEGIN_MARKER);
        $response = substr($output, $startPos, $endPos - $startPos);

        return json_decode($response, true);

    }

    private function loadScripts(array $extraScriptUrls) : string
    {
        $result = "";

        foreach (array_values($extraScriptUrls) as $index => $url) {
            $code = file_get_contents($url);

            if (strpos($code, 'define') === false) {
                $this->logger->info("looks like script $url does not support AMD. including it as is");
                $result .= "$url\n\n" . $code . ";\n\n";
                continue;
            }

            $code = /** @lang JavaScript */ "
            // $url
            function scriptLoadFn{$index}() {
                console.log('executing {$url}');
                var exports;
                var module;
                var define;
                
                $code;

            }
            
            scriptLoadFn{$index}.call(global)
            
            ";

            $result .= $code;
        }

        return $result;
    }

}