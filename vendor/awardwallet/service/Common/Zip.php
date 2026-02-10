<?php

namespace AwardWallet\Common;

class Zip
{

    public static function zipDir(string $sourcePath, array $excludePaths) : string
    {
        $z = new \ZipArchive();
        $outZipPath = tempnam(sys_get_temp_dir(), "zip") . ".zip";
        $z->open($outZipPath, \ZIPARCHIVE::CREATE);
        self::folderToZip($sourcePath, $z, strlen("$sourcePath/"), $excludePaths);
        $z->close();
        return $outZipPath;
    }

    private static function folderToZip(string $folder, \ZipArchive $zipFile, int $exclusiveLength, array $exludePaths)
    {
        $handle = opendir($folder);
        while (false !== $f = readdir($handle)) {
            if ($f !== '.' && $f !== '..') {
                $filePath = "$folder/$f";
                // Remove prefix from file path before add to zip.
                $localPath = substr($filePath, $exclusiveLength);
                foreach ($exludePaths as $excluded) {
                    if (strpos($localPath, $excluded) === 0) {
                        continue 2;
                    }
                }
                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    $zipFile->addEmptyDir($localPath);
                    self::folderToZip($filePath, $zipFile, $exclusiveLength, $exludePaths);
                }
            }
        }
        closedir($handle);
    }

}