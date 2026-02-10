<?php

namespace AwardWallet\Common\Selenium\Puppeteer;

use Symfony\Component\Process\Process;

class Installer
{

    public static function install() : void
    {
        $oldDir = getcwd();
        chdir(__DIR__);
        putenv('PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true');
        passthru("npm install", $exitCode);
        chdir($oldDir);

        if ($exitCode !== 0) {
            throw new \Exception("failed to run 'npm install', code: $exitCode");
        }
    }

}