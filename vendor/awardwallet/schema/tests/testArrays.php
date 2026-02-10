<?php

if (file_exists($file  = "vendor/autoload.php"))
    /** @var \Composer\Autoload\ClassLoader $loader */
    $loader = require_once $file;
else {
    echo "Run this test from home directory\n";
    exit(1);
}

$f = getFlight()->getSegments()[0];
$f->addSeat('1B')
    ->addSeat('1C')
    ->addSeat('1B');
try {
    $f->validateBasic();
    if (count($f->getSeats()) === 2 && in_array('1B', $f->getSeats()) && in_array('1C', $f->getSeats()))
        echo "SUCCESS: FlightSegment duplicate seats\n";
    else
        echo sprintf("ERROR: FlightSegment duplicate seats - unexpected seats value `%s`\n", json_encode($f->getSeats()));
}
catch(\Exception $e) {
    echo sprintf("ERROR: FlightSegment duplicate seats - unexpected exception %s: %s\n", get_class($e), $e->getMessage());
}

$f = getFlight()->getSegments()[0];
$f->addSeat('1B')
    ->addSeat('1C')
    ->addSeat('1D');
try {
    $f->validateBasic();
    if (count($f->getSeats()) === 3 && in_array('1B', $f->getSeats()) && in_array('1C', $f->getSeats()) && in_array('1D', $f->getSeats()))
        echo "SUCCESS: FlightSegment multiple seats\n";
    else
        echo sprintf("ERROR: FlightSegment multiple seats - unexpected seats value `%s`\n", json_encode($f->getSeats()));
}
catch(\Exception $e) {
    echo sprintf("ERROR: FlightSegment multiple seats - unexpected exception %s: %s\n", get_class($e), $e->getMessage());
}

$f = getFlight();
$f->setNoConfirmationNumber(false);
$f->addConfirmationNumber('ABCD')
    ->addConfirmationNumber('ABCD');
try {
    $f->validate(false);
    echo "ERROR: Flight duplicated confirmation numbers - expecting an exception\n";
}
catch(\Exception $e) {
    if ($e instanceof \AwardWallet\Schema\Parser\Component\InvalidDataException && strpos($e->getMessage(), 'duplicate') !== false)
        echo "SUCCESS: Flight duplicated confirmation numbers\n";
    else
        echo sprintf("ERROR: Flight duplicated confirmation numbers - unexpected exception %s\n", $e->getMessage());
}

$f = getFlight();
$f->setNoConfirmationNumber(false);
$f->addConfirmationNumber('ABCD')
    ->addConfirmationNumber('WASD');
try {
    $f->validate(false);
    if (count($f->getConfirmationNumbers()) === 2 && $f->getConfirmationNumbers()[0][0] === 'ABCD' && $f->getConfirmationNumbers()[1][0] === 'WASD')
        echo "SUCCESS: Flight multiple confirmation numbers\n";
    else
        echo sprintf("ERROR: Flight multiple confirmation numbers - unexpected confno value `%s`\n", json_encode($f->getConfirmationNumbers()));
}
catch(\Exception $e) {
    echo sprintf("ERROR: Flight multiple confirmation numbers - unexpected exception %s: %s\n", get_class($e), $e->getMessage());
}

$f = getFlight();
$f->addTicketNumber('1234567', false)
    ->addTicketNumber('1234567', false)
    ->addTicketNumber('7654321', false);
try {
    $f->validate(false);
    if (count($f->getTicketNumbers()) === 2 && $f->getTicketNumbers()[0][0] === '1234567' && $f->getTicketNumbers()[1][0] === '7654321')
        echo "SUCCESS: Flight duplicated ticket numbers\n";
    else
        echo sprintf("ERROR: Flight duplicated ticket numbers - unexpected tickets value `%s`\n", json_encode($f->getTicketNumbers()));
}
catch(\Exception $e) {
    echo sprintf("ERROR: Flight duplicated ticket numbers - unexpected exception %s: %s\n", get_class($e), $e->getMessage());
}

$f = getFlight();
$f->addTicketNumber('11111', false)
    ->addTicketNumber('22222', false)
    ->addTicketNumber('33333', false);
try {
    $f->validate(false);
    if (count($f->getTicketNumbers()) === 3 && $f->getTicketNumbers()[0][0] === '11111' && $f->getTicketNumbers()[1][0] === '22222' && $f->getTicketNumbers()[2][0] === '33333')
        echo "SUCCESS: Flight multiple ticket numbers\n";
    else
        echo sprintf("ERROR: Flight multiple ticket numbers - unexpected tickets value `%s`\n", json_encode($f->getTicketNumbers()));
}
catch(\Exception $e) {
    echo sprintf("ERROR: Flight multiple ticket numbers - unexpected exception %s: %s\n", get_class($e), $e->getMessage());
}

$f = getFlight();
$f->addAccountNumber('1234567', false)
    ->addAccountNumber('1234567', false)
    ->addAccountNumber('7654321', false);
try {
    $f->validate(false);
    if (count($f->getAccountNumbers()) === 2 && $f->getAccountNumbers()[0][0] === '1234567' && $f->getAccountNumbers()[1][0] === '7654321')
        echo "SUCCESS: Flight duplicated account numbers\n";
    else
        echo sprintf("ERROR: Flight duplicated account numbers - unexpected accounts value `%s`\n", json_encode($f->getAccountNumbers()));
}
catch(\Exception $e) {
    echo sprintf("ERROR: Flight duplicated account numbers - unexpected exception %s: %s\n", get_class($e), $e->getMessage());
}

$f = getFlight();
$f->addAccountNumber('11111', false)
    ->addAccountNumber('22222', false)
    ->addAccountNumber('33333', false);
try {
    $f->validate(false);
    if (count($f->getAccountNumbers()) === 3 && $f->getAccountNumbers()[0][0] === '11111' && $f->getAccountNumbers()[1][0] === '22222' && $f->getAccountNumbers()[2][0] === '33333')
        echo "SUCCESS: Flight multiple account numbers\n";
    else
        echo sprintf("ERROR: Flight multiple account numbers - unexpected accounts value `%s`\n", json_encode($f->getAccountNumbers()));
}
catch(\Exception $e) {
    echo sprintf("ERROR: Flight multiple account numbers - unexpected exception %s: %s\n", get_class($e), $e->getMessage());
}

$f = getFlight();
$f->addTraveller('John Doe', false)
    ->addTraveller('John Doe', false)
    ->addTraveller('Jane Doe', false);
try {
    $f->validate(false);
    if (count($f->getTravellers()) === 3 && $f->getTravellers()[0][0] === 'John Doe' && $f->getTravellers()[1][0] === 'John Doe' && $f->getTravellers()[2][0] === 'Jane Doe')
        echo "SUCCESS: Flight duplicated travellers\n";
    else
        echo sprintf("ERROR: Flight duplicated travellers - unexpected travellers value `%s`\n", json_encode($f->getTravellers()));
}
catch(\Exception $e) {
    echo sprintf("ERROR: Flight duplicated travellers - unexpected exception %s: %s\n", get_class($e), $e->getMessage());
}

$f = getFlight();
$f->addTraveller('John Doe', false)
    ->addTraveller('Jane Doe', false)
    ->addTraveller('James Doe', false);
try {
    $f->validate(false);
    if (count($f->getTravellers()) === 3 && $f->getTravellers()[0][0] === 'John Doe' && $f->getTravellers()[1][0] === 'Jane Doe' && $f->getTravellers()[2][0] === 'James Doe')
        echo "SUCCESS: Flight multiple travellers\n";
    else
        echo sprintf("ERROR: Flight multiple travellers - unexpected travellers value `%s`\n", json_encode($f->getTravellers()));
}
catch(\Exception $e) {
    echo sprintf("ERROR: Flight multiple travellers - unexpected exception %s: %s\n", get_class($e), $e->getMessage());
}

function getFlight()
{
    $f = new \AwardWallet\Schema\Parser\Common\Flight('f', new \Psr\Log\NullLogger(), null);
    $f->addSegment()
        ->setFlightNumber('1')
        ->setAirlineName('aa')
        ->setDepCode('QQQ')
        ->setArrCode('WWW')
        ->parseDepDate('13:30')
        ->parseArrDate('13:30');
    $f->setNoConfirmationNumber(true);
    return $f;
}