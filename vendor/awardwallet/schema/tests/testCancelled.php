<?php
if (file_exists($file  = "vendor/autoload.php"))
    require_once $file;
$logger = new \Symfony\Component\Console\Logger\ConsoleLogger(new \Symfony\Component\Console\Output\ConsoleOutput(256));
$options = new \AwardWallet\Schema\Parser\Component\Options();
$options->logDebug = true;
$options->throwOnInvalid = true;

$master = new \AwardWallet\Schema\Parser\Component\Master('master', $options);
$master->getLogger()->pushHandler(new \Monolog\Handler\PsrHandler($logger));
$f = $master->add()->flight();
$f->setCancelled(true);
$f->addConfirmationNumber('ADBCD');
$h = $master->add()->hotel();
$h->setHotelName('hotelname')
    ->setCheckInDate(strtotime('13:30'))
    ->setCancelled(true);

$master->checkValid();

$email = new \AwardWallet\Schema\Parser\Email\Email('e', $options);
$email->getLogger()->pushHandler(new \Monolog\Handler\PsrHandler($logger));
$email->add()->flight()->setCancelled(true);
try {
    $email->checkValid();
    throw new Exception('expected InvalidDataException');
}
catch(\AwardWallet\Schema\Parser\Component\InvalidDataException $e) {

}
$email->ota()->confirmation('112123');
$email->checkValid();