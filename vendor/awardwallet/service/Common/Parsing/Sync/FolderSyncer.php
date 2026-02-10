<?php

namespace AwardWallet\Common\Parsing\Sync;

use Psr\Log\LoggerInterface;

class FolderSyncer
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return null|string hash of files, null on failed sync
     * @throws \Exception
     */
    public function sync(string $fromFolder, string $toFolder, string $knownHash = null, bool $recursive = true) : ?string
    {
        $startTime = microtime(true);
        $stateFile = $toFolder . "/.sync-hash";

        if (!file_exists($toFolder) && !mkdir($toFolder) && !is_dir($toFolder)) {
            throw new \Exception("failed to create $toFolder");
        }

        do {
            clearstatcache();
            $files = $this->getFiles($fromFolder, $recursive);
            if (count($files) === 0){
                $this->logger->warning("sync failed, no files in $fromFolder");
                return null;
            }

            $hash = $this->calcHash($files);
            if ($knownHash === $hash || $hash === @file_get_contents($stateFile)) {
                return $hash;
            }

            $lockFile = fopen($toFolder . "/.sync-lock", "w");
            try {
                if (flock($lockFile, LOCK_EX | LOCK_NB)) {
                    try {
                        $this->logger->info("syncing files from $fromFolder to $toFolder");
                        $this->syncFiles($fromFolder, $toFolder, $files, $recursive);
                        if (!file_put_contents($stateFile, $hash)) {
                            throw new \Exception("failed to write $stateFile");
                        }
                    } finally {
                        flock($lockFile, LOCK_UN);
                    }
                    $this->logger->info("synced $toFolder, files: " . count($files) . ", hash: $hash");
                    return $hash;
                }
            }
            finally {
                fclose($lockFile);
            }

            $this->logger->info("waiting for lock on $toFolder");
            sleep(1);
        } while ( (microtime(true) - $startTime) < 10);

        return null;
    }

    /**
     * @param string $fromFolder
     * @param string $toFolder
     * @param array $files ["fileName" => modTimestamp, ... ]
     */
    private function syncFiles(string $fromFolder, string $toFolder, array $files, bool $recursive = true)
    {
        $folders = [];

        foreach ($files as $file => $modTime) {
            $target = $toFolder . "/" . $file;
            if (file_exists($target) && is_dir($target)) {
                $this->removeDirectory($target);
            }
            $folder = dirname($target);
            if (!in_array($folder, $folders)){
                $folders[] = $folder;
                if (!file_exists($folder)) {
                    if (!mkdir($folder, 0777, true) && !is_dir($folder)) {
                        throw new \Exception("failed to create $folder");
                    }
                }
            }
            if (!copy($fromFolder . "/" . $file, $target)) {
                throw new \Exception("failed to copy $file from $fromFolder to $toFolder");
            }
        }

        foreach (array_keys($this->getFiles($toFolder, $recursive)) as $file) {
            if (!isset($files[$file])) {
                if (!unlink($toFolder . "/" . $file)) {
                    throw new \Exception("failed to delete $file from $toFolder");
                }
            }
        }
    }

    /**
     * @param array $files ["fileName" => modTimestamp, ... ]
     */
    private function calcHash(array $files)
    {
        return sha1(http_build_query($files));
    }

    /**
     * @param string $folder
     * @return array ["fileName" => modTimestamp, ... ]
     */
    private function getFiles(string $folder, bool $recursive): array
    {
        if (!file_exists($folder)) {
            return [];
        }

        $flags = \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS;
        if ($recursive) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder, $flags));
        } else {
            $it = new \FilesystemIterator($folder, $flags);
        }
        $it->rewind();
        $files = [];
        foreach ($it as $file) {
            /** @var \SplFileInfo $file */
            if (!$recursive && $file->isDir()) {
                continue;
            }
            if ($file->getFilename() === 'sync_marker') {
                continue;
            }
            $path = substr($file->getPathname(), strlen($folder) + 1);
            if(substr(basename($path), 0, 1) === ".") {
                continue;
            }
            $files[$path] = $file->getMTime();
        }
        ksort($files);
        return $files;
    }

    private function removeDirectory(string $path)
    {
        $files = array_diff(scandir($path), array('.', '..'));
        foreach ($files as $file) {
            if (is_dir("$path/$file")) {
                $this->removeDirectory("$path/$file");
            } else {
                if (!unlink("$path/$file")) {
                    throw new \Exception("failed to remove file $path/$file");
                }
            }
        }
        if (!rmdir($path)){
            throw new \Exception("failed to remove directory $path");
        }
    }

}