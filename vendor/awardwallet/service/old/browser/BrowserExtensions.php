<?php


class BrowserExtensions
{

    private const REQUEST_RECORDER_FILES = [
        __DIR__ . '/extensions/request-recorder/manifest.json',
        __DIR__ . '/extensions/request-recorder/background.js',
        __DIR__ . '/extensions/request-recorder/content.js',
    ];

    public static function createHideSeleniumExtension(string $browser, int $version, string $userAgent = null, ?array $fingerprint = null, ?FingerprintParams $fingerprintParams): string
    {
        $file = sys_get_temp_dir() . "/hide-selenium-" . bin2hex(random_bytes(10)) . '.zip';
        $zip = new ZipArchive();
        $res = $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($res !== true) {
            throw new \Exception("Can't create chrome extension at $file");
        }
        $zip->addFile(__DIR__ . '/extensions/chrome-hide-selenium/manifest.json', 'manifest.json');
        $zip->addFile(__DIR__ . '/extensions/chrome-hide-selenium/background.js', 'background.js');
        $zip->addFromString('injected-javascript.js', self::replaceFingerprintParams(
            file_get_contents(__DIR__ . '/extensions/chrome-hide-selenium/injected-javascript.js'),
            $fingerprintParams ?? new FingerprintParams($browser, $version, $userAgent, $fingerprint)
        ));
        $zip->close();

        return $file;
    }

    public static function createPuppeteerStealthExtension() : string
    {
        $file = sys_get_temp_dir() . "/pup-stealth-selenium-" . bin2hex(random_bytes(10)) . '.zip';
        $zip = new ZipArchive();
        $res = $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($res !== true) {
            throw new \Exception("Can't create chrome extension at $file");
        }
        $zip->addFile(__DIR__ . '/extensions/puppeteer-stealth/manifest.json', 'manifest.json');
        $zip->addFile(__DIR__ . '/extensions/puppeteer-stealth/background.js', 'background.js');
        $zip->addFile(__DIR__ . '/extensions/puppeteer-stealth/dist/content.js', 'dist/content.js');
        $zip->close();

        return $file;
    }

    public static function createExtensionV3() : string
    {
        $file = sys_get_temp_dir() . "/extension-v3-" . bin2hex(random_bytes(10)) . '.zip';
        $zip = new ZipArchive();
        $res = $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        
        if ($res !== true) {
            throw new \Exception("Can't create chrome extension at $file");
        }

        $manifest = json_decode(file_get_contents(__DIR__ . '/../../../../../node_modules/@awardwallet/extension-v3/dist/manifest.json'), true);
        unset($manifest['icons']);
        $manifest['externally_connectable']['matches'][] = 'http://localhost/*';
        $manifest['key'] = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA075r54AZKctjOcEA4zUDdpIRY0BuUcbOcEOMCS+avApS28TvbGsjjW2BVRKgGsHqIAEvBgineILWh0l5ACQvl/OW0CRzFT7MSY0eDio1rytZgUOoDo4mcj0nmLkE1BsEAdrxgL9PaqCP+4Aaz/VJL7WB5+qz+oEf3kYcKRlorwZQaEB4GjOZzXXrLZFFkEIUupCjKohOwNXQXEbcb4yKmKCSN8L3onPe7OvZ5W2OpJzvLhDYamaDusEWPUOONAwkQ/S+b6MNesGVUpyx9cfrlTJssYY9yvL8vZjvje84/5iJPfQxJyuYTJMvZvxIjXxxHwthSRQhOVDBVEnZrADjjQIDAQAB";

        if (!$zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT))) {
            throw new \Exception("failed to add manifest: " . $zip->getStatusString());
        }
        
        if (!$zip->addFile(realpath(__DIR__ . '/../../../../../node_modules/@awardwallet/extension-v3/dist/background.js'), 'background.js')) {
            throw new \Exception("failed to add background: " . $zip->getStatusString());
        }
        
        $zip->close();

        return $file;
    }

    public static function createBridgeExtension(): array
    {
        $file = sys_get_temp_dir() . "/bridge-" . bin2hex(random_bytes(10)) . '.zip';
        $zip = new ZipArchive();
        $res = $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($res !== true) {
            throw new \Exception("Can't create chrome extension at $file");
        }
        $zip->addFile(__DIR__ . '/extensions/bridge/manifest.json', 'manifest.json');
        $zip->addFile(__DIR__ . '/extensions/bridge/background.js', 'background.js');
        $requestElementId = bin2hex(random_bytes(random_int(4, 10)));
        $responseElementId = bin2hex(random_bytes(random_int(4, 10)));
        $tags = ['div', 'a', 'span', 'pre'];
        $tag = $tags[array_rand($tags)];
        $zip->addFromString('content.js', str_ireplace(
            ['%request-element-id%', '%response-element-id%', '%tag%'],
            [$requestElementId, $responseElementId, $tag],
            file_get_contents(__DIR__ . '/extensions/bridge/content.js')
        ));
        $zip->close();

        return [$file, $requestElementId, $responseElementId];
    }

    public static function createRequestRecorderExtension(string $dir) : void
    {
        mkdir($dir, 0777, true);
        foreach (self::REQUEST_RECORDER_FILES as $file) {
            copy($file, $dir . '/' . basename($file));
        }
    }

    public static function createRequestRecorderExtensionZip() : string
    {
        $file = sys_get_temp_dir() . "/request-recorder-" . bin2hex(random_bytes(10)) . '.zip';
        $zip = new ZipArchive();
        $res = $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($res !== true) {
            throw new \Exception("Can't create extension at $file");
        }

        foreach (self::REQUEST_RECORDER_FILES as $aFile) {
            $zip->addFile($aFile, basename($aFile));
        }

        $zip->close();

        return $file;
    }

    public static function createAntiCaptchaExtension(string $destinationDir, ?array $proxyParams) : void
    {
        mkdir($destinationDir, 0755, true);
        foreach (
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(__DIR__ . '/extensions/anticaptcha-plugin', \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST) as $item
        ) {
            if ($item->isDir()) {
                mkdir($destinationDir . DIRECTORY_SEPARATOR . $iterator->getSubPathname());
            } else {
                copy($item, $destinationDir . DIRECTORY_SEPARATOR . $iterator->getSubPathname());
            }
        }        
        
        $configFile = $destinationDir . '/js/config_ac_api_key.js';
        $config = file_get_contents($configFile);
        $config = str_replace('%ANTI_CAPTCHA_API_KEY%', ANTIGATE_KEY, $config);
        $config = str_replace('%ANTI_CAPTCHA_PROXY_SERVER%', $proxyParams['proxyAddress'] ?? '', $config);
        $config = str_replace('%ANTI_CAPTCHA_PROXY_PORT%', $proxyParams['proxyPort'] ?? '', $config);
        $config = str_replace('%ANTI_CAPTCHA_PROXY_LOGIN%', $proxyParams['proxyLogin'] ?? '', $config);
        $config = str_replace('%ANTI_CAPTCHA_PROXY_PASSWORD%', $proxyParams['proxyPassword'] ?? '', $config);
        file_put_contents($configFile, $config);
    }

    public static function createAntiCaptchaExtensionZip(?array $proxyParams) : string
    {
        $dir = sys_get_temp_dir() . "/anti-captcha-" . bin2hex(random_bytes(10));
        self::createAntiCaptchaExtension($dir, $proxyParams);
        $file = sys_get_temp_dir() . "/anti-captcha-" . bin2hex(random_bytes(10)) . '.zip';
        $zip = new ZipArchive();
        $res = $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST) as $item
        ) {
            if ($item->isFile()) {
                $zip->addFile($item, substr($item, strlen($dir) + 1));
            }
        }

        $zip->close();
        \AwardWallet\Common\FileSystem::deleteDirectory($dir);

        return $file;
    }

//    public static function createStealthSeleniumExtension(string $browser, int $version, string $userAgent = null): string
//    {
//        $file = sys_get_temp_dir() . "/chrome-stealth-" . bin2hex(random_bytes(10)) . '.zip';
//        $zip = new ZipArchive();
//        $res = $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
//        if ($res !== true) {
//            throw new \Exception("Can't create chrome extension at $file");
//        }
//
//        $zip->addFile(__DIR__ . '/extensions/chrome-stealth/manifest.json', 'manifest.json');
//
//        $pageScript = file_get_contents(__DIR__ . '/extensions/chrome-stealth/compiled-page-script.js');
//        $pageScript = self::replaceFingerprintParams($pageScript, new FingerprintParams($browser, $version, $userAgent));
//
//        $contentScript = file_get_contents(__DIR__ . '/extensions/chrome-stealth/content-script.js');
//        $contentScript = str_replace('PAGE_SCRIPT', json_encode($pageScript), $contentScript);
//        $zip->addFromString('content-script.js', $contentScript);
//
//        $zip->close();
//
//        return $file;
//    }

    public static function replaceFingerprintParams($content, FingerprintParams $params): string
    {
        return str_replace('const fingerprintParams = {}', 'const fingerprintParams = ' . json_encode($params),
            $content);
    }

}
