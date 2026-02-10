<?php

namespace AwardWallet\Common\Selenium;

use Psr\Log\LoggerInterface;

class DownloadManager
{

    /**
     * @var \HttpDriverInterface
     */
    private $httpDriver;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(\HttpDriverInterface $httpDriver, LoggerInterface $logger)
    {
        $this->httpDriver = $httpDriver;
        $this->logger = $logger;
    }

    public function getLastDownloadedFile(\SeleniumConnection $connection) : ?DownloadedFile
    {
        $this->logger->info("reading files from " . $connection->getShare());
        $zippedDownloads = $this->request(new \HttpDriverRequest($connection->getAwEndpoint() . "/zip" . $connection->getShare()));
        if ($zippedDownloads === null) {
            $this->logger->notice("failed to get files");
            return null;
        }

        $zipFile = tempnam(sys_get_temp_dir(), "firefox-profile") . ".zip";
        if (file_put_contents($zipFile, $zippedDownloads) === false) {
            throw new \Exception("failed to save downloads to $zipFile");
        }
        try {
            $zip = new \ZipArchive();
            $result = $zip->open($zipFile);
            if ($result !== true) {
                $this->logger->notice("error while opening downloads zip: " . $result);
            }
            try {
                if ($zip->count() === 0) {
                    return null;
                }
                $files = [];
                for ($n = 0; $n < $zip->count(); $n++) {
                    $files[] = $zip->statIndex($n);
                }
                usort($files, function(array $a, array $b) {
                    return $a['mtime'] <=> $b['mtime'];
                });
                $last = end($files);
                $this->logger->info("last file: {$last['name']}, {$last['size']} bytes");
                return new DownloadedFile($last['name'], $last['size'], $zip->getFromIndex($last['index']));
            } finally {
                $zip->close();
            }
        } finally {
            unlink($zipFile);
        }

        return $result;
    }

    public function clearDownloads(\SeleniumConnection $connection) : void
    {
        $this->request(new \HttpDriverRequest($connection->getAwEndpoint() . "/rm/" . $connection->getShare() . '?contentsOnly'));
    }

    /**
     * @internal
     */
    public function cleanup(\SeleniumConnection $connection) : void
    {
        $this->request(new \HttpDriverRequest($connection->getAwEndpoint() . "/rm/" . $connection->getShare()));
    }

    private function request(\HttpDriverRequest $request) : ?string
    {
        $response = $this->httpDriver->request($request);
        if ($response->httpCode < 200 || $response->httpCode > 299) {
            $this->logger->notice("error while removing downloads: " . $response->toString());
            return null;
        }
        return $response->body;
    }

}
