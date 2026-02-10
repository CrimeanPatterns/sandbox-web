<?php
if (file_exists($file  = "vendor/autoload.php"))
    require_once $file;
$logger = new \Symfony\Component\Console\Logger\ConsoleLogger(new \Symfony\Component\Console\Output\ConsoleOutput(256));
$options = new \AwardWallet\Schema\Parser\Component\Options();
$options->logDebug = true;
$options->throwOnInvalid = true;
$m = new \AwardWallet\Schema\Parser\Component\Master('master', $options);
$m->getLogger()->pushHandler(new \Monolog\Handler\PsrHandler($logger));

// dates too far apart
$f = $m->add()->flight();
$f->general()->confirmation('ABC123');
$s = $f->addSegment();
$s->airline()->name('SU')->number('1234');
$s->departure()->code('DME')
    ->date2('2019-12-31 13:30');
$s->arrival()->code('PEE')
    ->date2('2018-12-31 17:30');

try {
    $m->checkValid();
    throw new \Exception('expected exception');
}
catch(\AwardWallet\Schema\Parser\Component\InvalidDataException $e) {}
$m->clearItineraries();

// year didn't carry over
$f = $m->add()->flight();
$f->general()->confirmation('ABC123');
$s = $f->addSegment();
$s->airline()->name('SU')->number('1234');
$s->departure()->code('DME')
    ->date2('2019-12-31 13:30');
$s->arrival()->code('PEE')
    ->date2('2019-01-01 17:30');
$m->checkValid();

$logger->info('ok');