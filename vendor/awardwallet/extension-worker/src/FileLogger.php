<?php

namespace AwardWallet\ExtensionWorker;

use Psr\Log\LoggerInterface;

class FileLogger
{

    private LoggerInterface $logger;
    private string $dir;
    private int $step = 1;

    public function __construct(LoggerInterface $logger, string $dir)
    {
        $this->logger = $logger;
        $this->dir = $dir;
    }

    public function logHtml(string $html, ?string $fileName = null) : void
    {
        if ($fileName === null) {
            $fileName = ".html";
        }

        $this->logFile($html, $fileName);
    }

    /**
     * @param string $content - file contents
     * @param string $fileNameOrExtension - you could specify full file name without path, like "loginForm.html"
     * or only extension starting with dot, like ".html" - in this case file name will be generated like "step01.html"
     * @return void
     */
    public function logFile(string $content, string $fileNameOrExtension) : void
    {
        if (substr($fileNameOrExtension, 0, 1) === "." && strpos($fileNameOrExtension, ".", 1) === false) {
            $fileNameOrExtension = "step" . sprintf("%02d", $this->step) . $fileNameOrExtension;
            $this->step++;
        }

        $this->logger->info("saved $fileNameOrExtension<!-- url:unknown -->");
        file_put_contents($this->dir . "/" . $fileNameOrExtension, $content);
    }

}