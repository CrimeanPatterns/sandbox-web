<?php
if (file_exists($file  = "vendor/autoload.php"))
    /** @var \Composer\Autoload\ClassLoader $loader */
    $loader = require_once $file;
else {
    echo "Run this test from project root directory\n";
    exit(1);
}
$logger = new \Symfony\Component\Console\Logger\ConsoleLogger(new \Symfony\Component\Console\Output\ConsoleOutput(256));
$options = new \AwardWallet\Schema\Parser\Component\Options();
$options->logDebug = true;
$options->throwOnInvalid = true;
$options->clearNamePrefix = true;
$master = new \AwardWallet\Schema\Parser\Component\Master('master', $options);
$master->getLogger()->pushHandler(new \Monolog\Handler\PsrHandler($logger));
try {
	$f = $master->add()->flight();
	$f->program()
		->account('123456', true)
		->account('654321', false)
		->account('777777', false)
		->code('delta')
		->keyword('Delta Airlines')
		->phone('1-234-5678', 'desk')
		->phone('8-765-4321', 'fax')
		->earnedAwards('1300 miles');
	$f->general()
		->confirmation('ABCDEF', 'recloc', true)
		->confirmation('1232323', 'purchase number')
		->status('confirmed')
		->traveller('John', false)
		->traveller('DR Jane Doe', true)
		->traveller('mr. Bob')
        ->infant('Alice')
		->date(strtotime('2018-01-17'))
        ->notes('direction notes')
        ->cancellation('non refundable');
	$f->price()
		->cost(123.45)
		->tax('23.45')
		->total('11223')
		->fee('seat selection', '123')
		->fee('seat selection', '123')
		->fee('additional baggage', 32)
		->discount('34.56')
		->currency('USD')
		->spentAwards('5 miles');
	$f->issued()
		->name('BA')
		->provider('british')
		->confirmation('ABJJHH')
		->ticket('123-45678', false)
		->ticket('123-XXXXX', true)
		->ticket('123-09090', false);
	$f->addAirlinePhone('BA', '+1-44-55567', 'desk');
	$s1 = $f->addSegment();
	$s1->departure()
		->code('BUF')
		->name('buffalo')
		->date(strtotime('2018-01-17 13:30'))
        ->strict()
		->terminal('3');
	$s1->arrival()
		->noCode()
		->name('Los Angeles')
		->date2('2018-01-17 15:40');
	$s1->airline()
		->name('airline name')
		->noNumber();
	$s1->extra()
		->status('pending')
		->seat('1C')
		->seat('1B')
		->stops(0)
		->smoking(false)
		->aircraft('AirBus 420')
        ->regNum('ABC123')
		->miles('770km')
		->cabin('Economy')
		->bookingCode('CL')
		->duration('7 years')
		->meal('Brownies')
        ->transit();
	$s2 = $f->addSegment();
	$s2->departure()
		->code('LAS')
		->name('Los Angeles')
		->noDate();
	$s2->arrival()
		->noCode()
		->noDate();
	$s2->airline()
		->name('DL')
		->confirmation('UUKKLL')
		->number('123')
		->carrierName('Uganda airlines')
		->carrierNumber('556')
		->carrierConfirmation('UGANDA')
		->operator('SkyWest')
		->wetlease();
	$s2->extra()
		->seats(['1T', '2U']);
	$s2->setTransit(false);
    $f = $master->add()->flight();
    $f->addConfirmationNumber('RECLOC');
    $s = $f->addSegment();
    $s->departure()
        ->code('COD')
        ->date2('2030-01-01 13:30');
    $s->arrival()
        ->code('MMM')
        ->date2('2030-01-01 13:30');
    $s->airline()
        ->name('SU')
        ->number('11');
    $s = $f->addSegment();
    $s->departure()
        ->code('HDQ')
        ->date2('2030-01-01 13:30');
    $s->arrival()
        ->code('MMM')
        ->date2('2030-01-01 13:30');
    $s->airline()
        ->name('SU')
        ->number('12');

	$r = $master->add()->hotel();
	$r->program()->accounts(['4444', '55555'], true);
	$r->general()
		->confirmation('6677889900')
		->status('cancelled')
		->cancelled()
        ->cancellation('is non refundable')
        ->cancellationNumber('11223344')
        ->notes('direction notes')
		->travellers(['Jane', 'Joe'], false);
	$r->hotel()
		->name('Grand Hotel')
		->phone('3344-5566')
		->fax('556677-00')
		->address('Emerald City, Yellow brick road 1');
	$r->setHouse(false)
        ->setHost(false);
	$r->hotel()->detailed()
		->address('ajjajas')
		->city('cccc')
		->state('stststs')
		->country('oiopopo')
		->zip('12123123');
	$r->booked()
		->checkIn(strtotime('2018-01-17 10:00 AM'))
		->checkOut2('01/19 7:00 PM', strtotime('2018-01-01'), '%Y%-01-19 7:00 PM')
		->guests(1)
		->kids(2)
		->rooms(2)
        ->freeNights(1)
        ->parseNonRefundable()
        ->deadlineRelative('48 hours', '18:00');
	$r->price()
        ->total(11.1)
        ->currency('$');
	$r1 = $r->addRoom();
	$r1->setType('room type')
		->setDescription('room description')
		->setRate('room rate')
		->setRateType('room rate type')
		->setConfirmation('12123123123')
		->setConfirmationDescription('some room');
	$r2 = $r->addRoom();
	$r2->setType('awesome')
		->setDescription('everything u can wish for')
		->setRateType('double')
		->addRate('100 $')
        ->addRate('110 $');

	$c = $master->add()->rental();
	$c->general()
		->travellers(['MS.Rob', 'Ms Bran'])
		->noConfirmation()
        ->notes('direction notes')
        ->cancellation('can cancel 24 hours prior');
	$c->setAreNamesFull(false);
	$c->program()
		->accounts(['3344', '55566'], false);
	$c->ota()
		->confirmation('7676678585', 'trip number', false)
		->accounts(['4445', '443030'], true)
		->code('code')
		->keyword('keyword')
		->phone('5-679-3434', 'desk desk')
		->earnedAwards('3 points');
	$c->setAreAccountMasked(false);
	$c->pickup()
		->date(strtotime('2018-01-17 7:20 AM'))
		->location('somewhere in the desert')
        ->openingHours('24 hours')
		->detailed()
		->address('rental address 1')
		->city('rental city 1')
		->state('rental state 1')
		->country('rental country 1')
		->zip('123-444 /567');
	$c->dropoff()
		->date2('2018-01-17 7:30 PM')
        ->openingHoursFullList(['Mon: off', 'Tue-Fri: 9.00-22.00'])
        ->openingHours('Sat-Sun: 6.00-23.00')
		->noLocation();
	$c->car()
		->type('sedan')
		->model('honda')
		->image('http://ayy.lmao');
	$c->extra()
        ->host()
		->discount('code', 'name')
		->company('avis')
		->equip('wheel', 13.45);

	$p = $master->add()->parking();
    $p->program()
        ->code('parkingspot')
        ->accounts(['AM3398'], false)
        ->earnedAwards('50 points')
        ->phone('+1-33-4456', 'Customer support');
    $p->price()
        ->cost(193.75)
        ->total(251.41)
        ->tax(34.56)
        ->fee('Insurance', 23.10)
        ->discount(40)
        ->currency('USD')
        ->spentAwards('10000 points');
    $p->general()
        ->confirmation('A04-33984-12', 'Confirmation #', true)
        ->confirmation('887756', 'Transaction number', false)
        ->status('Confirmed')
        ->date2('2000-01-01')
        ->travellers(['John Doe', 'Jane Doe'], true);
    $p->place()
        ->address('132 West 58th Street New York, NY 10019')
        ->phone('+1-23-44556')
        ->openingHours('24 hours')
        ->location('Parking Zone 123');
    $p->booked()
        ->start2('2030-01-01 18:00')
        ->end2('2030-01-01 23:00')
        ->car('white ford focus')
        ->rate('REGULAR')
        ->spot('13')
        ->plate('AB234');

	$cr = $master->add()->cruise();
	$cr->general()
        ->confirmation('12123123', 'main confno')
        ->notes('direction notes')
        ->cancellation('cancel with penalties');
	$cr->details()
		->deck('13.5')
		->description('nice cruise')
		->room('666')
		->roomClass('frist')
		->ship('titanic')
		->shipCode('TTNC')
        ->number('ABC-123');
	$s1 = $cr->addSegment();
	$s1->setName('some port')
		->setAshore(strtotime('2018-01-17 13:30'))
		->parseAboard('2018-01-17 14:40');
	$cr->addSegment()
		->setName('another port')
		->parseAshore('2018-01-17 15:50')
		->setAboard(strtotime('2018-01-17 16:00'));

	$e = $master->add()->event();
	$e->general()
        ->confirmation('5566hjhj')
        ->notes('direction notes')
        ->cancellation('cannot refund');
	$e->place()
		->name('moe\'s')
		->address('springfield')
		->phone('1122323')
		->fax('122322323');
    $e->type()->restaurant();
	$e->booked()
        ->host()
		->guests(1)
        ->kids(2)
		->start(strtotime('2018-01-17'))
		->noEnd()
		->seats(['101', '102'])
		->seat('103');

	$tf = $master->add()->transfer();
	$tf->general()
        ->noConfirmation()
        ->notes('direction notes');
	$tf->setAllowTzCross(true);
    $tf->setHost(true);
	$s1 = $tf->addSegment();
	$s1->departure()
		->code('LAX')
		->date2('2018-01-17');
	$s1->arrival()
		->name('some hotel')
		->noDate();
	$s1->extra()
		->type('regular car')
		->image('http://hello.world')
		->model('mercedes')
		->adults(1)
		->kids(0)
		->duration('2h')
		->miles('1km');

	$tr = $master->add()->train();
	$tr->general()
        ->notes('direction notes')
        ->noConfirmation();
	$tr->addTicketNumber('3343434', false);
	$s1 = $tr->addSegment();
	$s1->departure()
		->code('CDD')
		->name('some name')
        ->geoTip('europe')
		->noDate();
	$s1->arrival()
		->name('another name')
        ->geoTip('europe')
		->date(strtotime('2018-01-17'));
	$s1->extra()
		->model('choo choo')
		->type('vip train')
		->service('ACELLA EXPRESS')
		->number('223')
		->seats(['13'])
		->car(4)
        ->link('http://some.link', 'John Doe');

	$b = $master->add()->bus();
	$b->general()
        ->notes('direction notes')
        ->confirmation('AHAHAHAH');
	$b->addTicketNumber('334', false);
	$s1 = $b->addSegment();
	$s1->departure()
		->name('nnnn')
        ->geoTip(', US')
		->noDate();
	$s1->arrival()
		->code('CDD')
		->name('station name')
        ->geoTip(', US')
		->date2('2018-01-17 13:30');
	$s1->extra()->duration('3:20')
		->noNumber();

	$f = $master->add()->ferry();
	$f->general()
        ->notes('direction notes');
	$f->addTicketNumber('112233', false);
	$f->addConfirmationNumber('223344', 'number');
    $f->addConfirmationNumber('CONF1', 'locator', true);
	$f->parseReservationDate('2030-01-01');
	$f->setStatus('confirmed');
	$f->addTraveller('Mrname Doe', true);
	$f->addTraveller('Jane Doe', true);
	$f->setAllowTzCross(true);
	$s = $f->addSegment();
	$s->setCarrier('carrier');
	$s->setVessel('VS-1');
	$s->addAccommodation('topside');
	$s->setAdults(1);
	$s->setKids(0);
	$s->addVehicle()
        ->setType('normal vehicle')
        ->setLength('3m')
        ->setWidth('1.5m')
        ->setHeight('0.7m')
        ->setModel('A678');
	$s->addTrailer()
        ->setType('rare trailer')
        ->setLength('6ft')
        ->setWidth('3ft')
        ->setHeight('2ft')
        ->setModel('RAR-990');
	$s->setDepName('port 1')
        ->setDepAddress('City 1, street ABC')
        ->parseDepDate('2030-01-01 13:30');
	$s->setArrName('second port')
        ->setNoArrDate(true);
	$s->setDuration('2h')
        ->setMiles('30km');
	$s->extra()
        ->cabin('Economy')
        ->meal('snack');

	$bp = $master->add()->bpass();
	$bp->setDepCode('ABC')
		->setDepDate(strtotime('2018-01-01 13:30'))
		->setTraveller('richard')
		->setAttachmentName('bp.pdf')
		->setUrl('http://some.url')
		->setFlightNumber('123')
		->setRecordLocator('BHJN74');

	$st = $master->add()->statement();
	$st->addProperty('Miles', '123')
		->addProperty('Points', 'ABC')
		->setExpirationDate(strtotime('2018-09-09'))
		->setBalance(123.45)
        ->setLogin('login')
        ->setLogin2('region')
		->addActivityRow(['Field11' => 'Value11', 'Field12' => 'Value12'])
		->addActivityRow(['Field21' => 'Value21', 'Field22' => 'Value22'])
		->addActivityRow(['Field31' => 'Value31', 'Field32' => 'Value32']);

	$oneTimeCode = $master->add()->oneTimeCode();
    $oneTimeCode->setCode('123456');

    $coupon = $master->addCoupon();
    $coupon->setType(\AwardWallet\Schema\Parser\Common\Coupon::TYPE_COUPON)
        ->setNumber('Kdo3Reife')
        ->setCanExpire(false)
        ->setCategory(\AwardWallet\Schema\Parser\Common\Coupon::CAT_AIRLINE)
        ->setPin('PKDOAE903')
        ->setOwner('Some owner name')
        ->setValue('21,3$')
        ->setExpirationDate(strtotime('2018-01-01 13:30'))
        ->setAccountNumber('****4455')
        ->setAccountMask();

    $awardRedemption = $master->add()->awardRedemption();
    $awardRedemption->setDateIssued(1514813400)
        ->setMilesRedeemed(80000)
        ->setRecipient('Some recipient name')
        ->setDescription('Flight award')
        ->setAccountNumber('1L211P4');

    $cardPromo = $master->add()->cardPromo();
    $cardPromo->setCardName('Citi Prestige Card')
        ->setCardOwner('Erik Paquet')
        ->setCardMemberSince(2016)
        ->setLastDigits(1933)
        ->setMultiplier('5x')
        ->setOfferDeadline(1680202800)
        ->setApplicationDeadline(1676401200)
        ->setApplicationURL('test_url')
        ->setLimitAmount(2500)
        ->setLimitCurrency('points')
        ->setBonusCategories([
            'Gas Stations',
            'Grocery Stores',
            'Drugstores',
            'Mass Transit & Commuter Transportation Vendors'
        ]);

	$master->checkValid();
}
catch (\AwardWallet\Schema\Parser\Component\InvalidDataException $e) {
	$logger->error($e->getMessage());
}
if (isset($argv[1]) && $argv[1] === '-w') {
	$logger->info('writing json to valid.json');
	file_put_contents(__DIR__.'/data/valid.json', json_encode($master->toArray()));
}
$expect = file_get_contents(__DIR__.'/data/valid.json');
$json = json_encode($master->toArray());
if (strcmp($expect, $json) !== 0) {
	$logger->error('JSON NOT MATCHING');
	$pos = strspn($expect ^ $json, "\0");
	$logger->info('expecting ' . substr($expect, max(0, $pos - 50), 100));
	$logger->error('     got ' . substr($json, max(0, $pos - 50), 100));
}
else
	$logger->notice('OK');
