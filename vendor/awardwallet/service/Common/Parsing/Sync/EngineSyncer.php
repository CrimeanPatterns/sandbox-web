<?php

namespace AwardWallet\Common\Parsing\Sync;

use Psr\Log\LoggerInterface;

class EngineSyncer
{

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var FolderSyncer
     */
    private $syncer;

    public function __construct(LoggerInterface $logger, FolderSyncer $syncer)
    {
        $this->logger = $logger;
        $this->syncer = $syncer;
    }

    /**
     * @return string - hash
     */
    public function syncAll(string $fromFolder, string $toFolder, bool $checkSyntax) : string
    {
        $targetCacheFile = $toFolder . "/.sync-cache";
        $sourceCacheFile = $fromFolder . "/.sync-cache";

        $lockFile = $toFolder . "/.sync-engine";
        $startTime = microtime(true);
        do {
            $lockHandle = fopen($lockFile, "w");
            if ($lockHandle === false) {
                $this->logger->info("waiting to open $lockFile");
                sleep(1);
            }
        } while ($lockHandle === false && (microtime(true) - $startTime) < 60);
        if ($lockHandle === false) {
            throw new \Exception("failed to open $lockFile");
        }
        try {
            flock($lockHandle, LOCK_EX);
            try {
                if (file_exists($targetCacheFile)) {
                    $targetCache = file_get_contents($targetCacheFile);
                    if (file_exists($sourceCacheFile)) {
                        $sourceCache = file_get_contents($sourceCacheFile);
                        if ($targetCache === $sourceCache) {
                            return $targetCache;
                        }
                    }
                }

                $this->logger->info("syncing from $fromFolder to $toFolder");
                $providerFolders = glob($fromFolder . '/*', GLOB_ONLYDIR);

                if ($checkSyntax) {
                    $files = $this->checkSyntax($providerFolders, $fromFolder);
                } else {
                    $files = 0;
                }

                if (isset($targetCache)) {
                    $targetHashes = json_decode($targetCache, true);
                } else {
                    $targetHashes = [];
                }

                if (isset($sourceCache)) {
                    $sourceHashes = json_decode($sourceCache, true);
                } else {
                    $sourceHashes = [];
                }

                if (!isset($targetHashes[""]) || !isset($sourceHashes[""]) || $targetHashes[""] !== $sourceHashes[""]) {
                    $hash = $this->syncer->sync($fromFolder, $toFolder, $targetHashes[""] ?? null, false);
                    if ($hash === null) {
                        throw new \Exception("failed to sync root");
                    }
                    $targetHashes[""] = $hash;
                }

                foreach ($providerFolders as $folder) {
                    $provider = substr($folder, strlen($fromFolder) + 1);
                    if (isset($sourceHashes[$provider]) && isset($targetHashes[$provider]) && $sourceHashes[$provider] === $targetHashes[$provider]) {
                        $this->logger->debug("skipping $provider, same hashes");
                        continue;
                    }
                    $this->logger->info("syncing $provider");
                    $hash = $this->syncer->sync($folder, $toFolder . "/" . $provider, $targetHashes[$provider] ?? null);
                    if ($hash === null) {
                        throw new \Exception("failed to sync $folder");
                    }
                    $targetHashes[$provider] = $hash;
                }
                ksort($targetHashes);

                if (isset($sourceCache)) {
                    $this->logger->info("copying source cache to target");
                    $newCache = $sourceCache;
                } else {
                    $newCache = json_encode($targetHashes, JSON_PRETTY_PRINT);
                    $this->logger->info("created new cache");
                }

                if (file_put_contents($targetCacheFile, $newCache) === false) {
                    throw new \Exception("failed to write $targetCacheFile");
                }
                $this->logger->info("put " . strlen($newCache) . " bytes to $targetCacheFile");

                foreach ($providerFolders as $folder) {
                    $provider = substr($folder, strlen($fromFolder) + 1);
                    if (!file_exists($fromFolder . "/" . $provider)) {
                        $this->logger->info("deleting $toFolder/$provider");
                    }
                }

                $this->logger->info("synced, processed " . count($providerFolders) . " folders, required $files files");
            } finally {
                flock($lockHandle, LOCK_UN);
            }
        }
        finally{
            fclose($lockHandle);
        }

        return $newCache;
    }

    /**
     * @param array $providerFolders
     * @param string $fromFolder
     * @return int - number of files
     */
    private function checkSyntax(array $providerFolders, string $fromFolder) : int
    {
        $nameSpace = 'AwardWallet\\Engine';
        $patterns = [
            'functions.php',
            'Email/*.php',
            'Email/Statement/*.php',
            'QuestionAnalyzer.php',
            'RewardAvailability/*.php',
        ];

        spl_autoload_register(function($className) use ($fromFolder, $nameSpace) {
        	if(strpos($className, $nameSpace) === 0){
                require_once $fromFolder . str_replace('\\', '/', substr($className, strlen($nameSpace))) . '.php';
        	}
        });

        $files = 0;
        $this->logger->info("checking syntax");

        foreach($providerFolders as $d) {
            foreach ($patterns as $pattern) {
                foreach (glob($d . '/' . $pattern) as $file) {
                    $files++;
                    require_once $file;
                }
            }
        }

        ob_start();
        foreach (glob("$fromFolder/*.php") as $file) {
            $files++;
            require_once $file;
        }
        ob_clean();

        $this->logger->info('syntax checked');

        return $files;
    }

}
