<?php

namespace AwardWallet\ExtensionWorker;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ParserLogger
{

    private string $logDir;
    private FileLogger $fileLogger;
    private Logger $logger;
    private WarningLogger $warningLogger;

    public function __construct(Logger $logger)
    {
        $this->logDir = sys_get_temp_dir() . "/parser-log-" . bin2hex(random_bytes(8));
        mkdir($this->logDir, 0777, true);
        $logger->info("writing logs to {$this->logDir}");
        $this->fileLogger = new FileLogger($logger, $this->logDir);
        $this->logger = $logger;
        $handler = new StreamHandler($this->logDir . "/log.html", Logger::DEBUG);
        $handler->setFormatter(new ParserLogFileFormatter());
        $logger->pushHandler($handler);
        $this->warningLogger = new WarningLogger($logger);
    }

    public function getFileLogger(): FileLogger
    {
        return $this->fileLogger;
    }

    public function getWarningLogger() : WarningLogger
    {
        return $this->warningLogger;
    }

    public function cleanup() : void
    {
        $this->logger->popHandler();
        $this->rrmdir($this->logDir);
    }

    private function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") {
                        $this->rrmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    public function getLogDir() : string
    {
        return $this->logDir;
    }

}