<?php

namespace AwardWallet\Common\Parsing\Sync;

use Psr\Log\LoggerInterface;

class EngineRefresher
{

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var string
     */
    private $engineFolder;
    /**
     * @var string
     */
    private $loadedHash;
    /**
     * @var string
     */
    private $syncMarkerFile;
    /**
     * @var bool
     */
    private $syncOn;
    /**
     * @var string
     */
    private $sharedFolder;
    /**
     * @var EngineSyncer
     */
    private $syncer;
    /**
     * @var FolderSyncer
     */
    private $folderSyncer;

    public function __construct(LoggerInterface $logger, string $engineFolder, string $sharedFolder, EngineSyncer $syncer, FolderSyncer $folderSyncer)
    {
        $this->logger = $logger;
        $this->engineFolder = realpath($engineFolder);
        $this->syncMarkerFile = $this->engineFolder . '/sync_marker';
        $this->syncOn = file_exists($this->syncMarkerFile); // do not sync on local copies
        $this->logger->info("engine sync from {$sharedFolder} {$this->engineFolder} is " . ($this->syncOn ? "on" : "off"));
        $this->sharedFolder = $sharedFolder;
        $this->syncer = $syncer;
        $this->folderSyncer = $folderSyncer;
    }

    public function isFresh() : bool
    {
        if (!$this->syncOn){
            return true;
        }

        $hash = $this->syncer->syncAll($this->sharedFolder, $this->engineFolder, false);

        if ($this->loadedHash !== null && $this->loadedHash !== $hash) {
            $this->logger->info("engine changed");
            return false;
        }

        if ($this->loadedHash === null) {
            $this->logger->info("loaded engine");
            $this->loadedHash = $hash;
        }

        return true;
    }

    public function syncRoot()
    {
        if (!$this->syncOn){
            return;
        }

        $rootHash = $this->folderSyncer->sync($this->sharedFolder, $this->engineFolder, null, false);
        if ($rootHash === null) {
            throw new \Exception("failed to sync root folder");
        }
    }

}