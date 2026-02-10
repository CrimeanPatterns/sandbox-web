<?php
if (file_exists($file  = "vendor/autoload.php"))
    /** @var \Composer\Autoload\ClassLoader $loader */
    $loader = require_once $file;
else {
    echo "Run this test from project root directory\n";
    exit(1);
}

$console = new \Symfony\Component\Console\Logger\ConsoleLogger(new \Symfony\Component\Console\Output\ConsoleOutput(256));

$tests = [
    ['MASTER', null, null],
    // empty junk
    ['noItineraries', '{"noItineraries": true}', null],
    // not empty junk
    ['noItinerariesWithData', '{"itineraries":[{"segments":[{"depName":"depname","depDate":1893456000,"arrName":"arrname","arrDate":1893542400}],"confirmationNumbers":[["ABCD",null]],"type":"bus"}],"noItineraries":true}', 'data with itineraries cannot have noItineraries=true'],

];

$ok = $err = 0;
foreach($tests as list($name, $json, $error)) {
    if (is_null($json)) {
        $console->info('-----'.$name.'------');
        continue;
    }
    try {
        instance()->fromJson($json)->validate();
        if (!is_null($error)) {
            $console->error(sprintf('%s: expecting error `%s`, got none', $name, $error));
            $err++;
        }
        else {
            $console->info(sprintf('%s: OK', $name));
            $ok++;
        }
    }
    catch(\AwardWallet\Schema\Parser\Component\InvalidDataException $e) {
        if (is_null($error)) {
            $console->error(sprintf('%s: unexpected error `%s`', $name, $e->getMessage()));
            $err++;
        }
        elseif (strpos(preg_replace('/^[^:]+:\s*/', '', $e->getMessage()), $error) !== 0) {
            $console->error(sprintf('%s: expecting error `%s`, got `%s`', $name, $error, $e->getMessage()));
            $err++;
        }
        else {
            $console->info(sprintf('%s: OK', $name));
            $ok++;
        }
    }
}
$console->log($err > 0 ? \Psr\Log\LogLevel::ERROR : \Psr\Log\LogLevel::INFO, sprintf('%d success, %d errors', $ok, $err));

function instance(): \AwardWallet\Schema\Parser\Component\Master
{
    $options = new \AwardWallet\Schema\Parser\Component\Options();
    $options->logDebug = false;
    $options->throwOnInvalid = true;
    $e = new \AwardWallet\Schema\Parser\Component\Master('e', $options);
    return $e;
}