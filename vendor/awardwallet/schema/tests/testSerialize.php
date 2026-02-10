<?php
if (file_exists($file  = "vendor/autoload.php"))
    /** @var \Composer\Autoload\ClassLoader $loader */
    $loader = require_once $file;
else {
    echo "Run this test from home directory\n";
    exit(1);
}
const ACCOUNT_ENGINE_ERROR = 1;
$logger = new \Symfony\Component\Console\Logger\ConsoleLogger(new \Symfony\Component\Console\Output\ConsoleOutput(256));
$options = new \AwardWallet\Schema\Parser\Component\Options();
$options->logDebug = false;
$options->throwOnInvalid = true;


$parser = new \AwardWallet\Engine\testprovider\Email\Example();
$template = "From: %s\n\nbody";
foreach($parser->froms as $from) {
    $plancake = new PlancakeEmailParser(sprintf($template, $from));
    $email = new \AwardWallet\Schema\Parser\Email\Email('e', $options);
    $parser->ParsePlanEmailExternal($plancake, $email);
    $s1 = var_export($email->toArray(), true);
    $s2 = var_export((new \AwardWallet\Schema\Parser\Email\Email('cmp', $options))->fromArray($email->toArray())->toArray(), true);
    if (strcmp($s1, $s2) !== 0) {
        $logger->error('serialization failed with email ' . $from);
        if (in_array('-v', $argv)) {
            echo "$s1\n-----\n$s2\n";
        }
    }
    else
        $logger->info($from . ' OK');
}