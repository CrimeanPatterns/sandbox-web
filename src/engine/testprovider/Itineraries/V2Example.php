<?php

namespace AwardWallet\Engine\testprovider\Itineraries;

use AwardWallet\Engine\testprovider\Success;
use AwardWallet\Schema\Parser\Common\Event;

class V2Example extends Success
{
    public function ParseItineraries()
    {
        $this->flight();
        $this->hotel();
        $this->rental();
        $this->bus();
        $this->train();
        $this->transfer();
        $this->cruise();
        $this->parking();
        $this->event();

        return parent::ParseItineraries();
    }

    protected function event()
    {
        $r = $this->itinerariesMaster->add()->event();
        $r->program()
            ->code('opentable')
            ->accounts(['AM3398'], false)
            ->earnedAwards('50 points')
            ->phone('+1-33-4456', 'Customer support');
        $r->price()
            ->cost(193.75)
            ->total(251.41)
            ->tax(34.56)
            ->fee('Insurance', 23.10)
            ->discount(40)
            ->currency('USD')
            ->spentAwards('10000 points');
        $r->general()
            ->confirmation('A04-33984-12', 'Confirmation #', true)
            ->confirmation('887756', 'Transaction number', false)
            ->status('Confirmed')
            ->date2('2000-01-01')
            ->travellers(['John Doe', 'Jane Doe'], true);
        $r->place()
            ->address('132 West 58th Street New York, NY 10019')
            ->name('Loi Estiatorio')
            ->type(Event::TYPE_RESTAURANT)
            ->phone('+1-23-44556')
            ->fax('+1-99-33434');
        $r->booked()
            ->start2('2030-01-01 18:00')
            ->end2('2030-01-01 23:00')
            ->guests(2)
            ->seat('table 13');
    }

    protected function parking()
    {
        $r = $this->itinerariesMaster->add()->parking();
        $r->program()
            ->code('parkingspot')
            ->accounts(['AM3398'], false)
            ->earnedAwards('50 points')
            ->phone('+1-33-4456', 'Customer support');
        $r->price()
            ->cost(193.75)
            ->total(251.41)
            ->tax(34.56)
            ->fee('Insurance', 23.10)
            ->discount(40)
            ->currency('USD')
            ->spentAwards('10000 points');
        $r->general()
            ->confirmation('A04-33984-12', 'Confirmation #', true)
            ->confirmation('887756', 'Transaction number', false)
            ->status('Confirmed')
            ->date2('2000-01-01')
            ->travellers(['John Doe', 'Jane Doe'], true);
        $r->place()
            ->address('132 West 58th Street New York, NY 10019')
            ->phone('+1-23-44556')
            ->location('Parking Zone 123');
        $r->booked()
            ->start2('2030-01-01 18:00')
            ->end2('2030-01-01 23:00')
            ->car('white ford focus')
            ->rate('REGULAR')
            ->spot('13')
            ->plate('AB234');
    }

    protected function cruise()
    {
        $r = $this->itinerariesMaster->add()->cruise();
        $r->program()
            ->code('disneycruise')
            ->accounts(['AM3398'], false)
            ->earnedAwards('50 points')
            ->phone('+1-33-4456', 'Customer support');
        $r->price()
            ->cost(193.75)
            ->total(251.41)
            ->tax(34.56)
            ->fee('Insurance', 23.10)
            ->discount(40)
            ->currency('USD')
            ->spentAwards('10000 points');
        $r->general()
            ->confirmation('A04-33984-12', 'Confirmation #', true)
            ->confirmation('887756', 'Transaction number', false)
            ->status('Confirmed')
            ->date2('2000-01-01')
            ->travellers(['John Doe', 'Jane Doe'], true);
        $r->details()
            ->deck('3')
            ->shipCode('SHCD')
            ->ship('Disney Dream')
            ->description('Long cruise')
            ->room('342')
            ->roomClass('Regular');
        $r->addSegment()->setName('PORT CANAVERAL')
            ->parseAboard('2030-01-01 13:30');
        $r->addSegment()->setName('NASSAU')
            ->parseAshore('2030-01-02 08:00')
            ->parseAboard('2030-01-02 12:00');
        $r->addSegment()->setName('PORT CANAVERAL')
            ->parseAshore('2030-01-03 14:00');
    }

    protected function transfer()
    {
        $r = $this->itinerariesMaster->add()->transfer();
        $r->program()
            ->code('uber')
            ->accounts(['AM3398'], false)
            ->earnedAwards('50 points')
            ->phone('+1-33-4456', 'Customer support');
        $r->price()
            ->cost(193.75)
            ->total(251.41)
            ->tax(34.56)
            ->fee('Insurance', 23.10)
            ->discount(40)
            ->currency('USD')
            ->spentAwards('10000 points');
        $r->general()
            ->confirmation('A04-33984-12', 'Confirmation #', true)
            ->confirmation('887756', 'Transaction number', false)
            ->status('Confirmed')
            ->date2('2000-01-01')
            ->travellers(['John Doe', 'Jane Doe'], true);
        $s = $r->addSegment();
        $s->departure()
            ->code('SFO')
            ->date2('2030-01-01 13:30');
        $s->arrival()
            ->address('315 Walnut Ave, South San Francisco, CA 94080, USA')
            ->date2('2030-01-01 14:34');
        $s->extra()
            ->type('Regular')
            ->model('Ford Focus')
            ->image('http://car.image/url')
            ->miles('4.3mi')
            ->duration('7h')
            ->adults(1)
            ->kids(0);
    }

    protected function train()
    {
        $r = $this->itinerariesMaster->add()->train();
        $r->program()
            ->code('amtrak')
            ->accounts(['AM3398'], false)
            ->earnedAwards('50 points')
            ->phone('+1-33-4456', 'Customer support');
        $r->price()
            ->cost(193.75)
            ->total(251.41)
            ->tax(34.56)
            ->fee('Insurance', 23.10)
            ->discount(40)
            ->currency('USD')
            ->spentAwards('10000 points');
        $r->general()
            ->confirmation('A04-33984-12', 'Confirmation #', true)
            ->confirmation('887756', 'Transaction number', false)
            ->status('Confirmed')
            ->date2('2000-01-01')
            ->travellers(['John Doe', 'Jane Doe'], true);
        $r->setTicketNumbers(['345667', '345668'], false);
        $s = $r->addSegment();
        $s->departure()
            ->code('BBSS')
            ->name('Boston South Station - Gate 9 NYC-Gate 10 NWK\/PHL')
            ->date2('2030-01-01 13:30');
        $s->arrival()
            ->code('NNYW')
            ->name('New York W 33rd St & 11-12th Ave (DC,BAL,BOS,PHL)')
            ->date2('2030-01-01 20:34');
        $s->extra()
            ->service('Amtrak Express')
            ->number('2023')
            ->seats(['11', '12'])
            ->type('Regular')
            ->miles('43mi')
            ->duration('7h')
            ->car('4')
            ->cabin('coach');
    }

    protected function bus()
    {
        $r = $this->itinerariesMaster->add()->bus();
        $r->program()
            ->code('boltbus')
            ->accounts(['BB3398'], false)
            ->earnedAwards('50 points')
            ->phone('+1-33-4456', 'Customer support');
        $r->price()
            ->cost(193.75)
            ->total(251.41)
            ->tax(34.56)
            ->fee('Insurance', 23.10)
            ->discount(40)
            ->currency('USD')
            ->spentAwards('10000 points');
        $r->general()
            ->confirmation('A04-33984-12', 'Confirmation #', true)
            ->confirmation('887756', 'Transaction number', false)
            ->status('Confirmed')
            ->date2('2000-01-01')
            ->travellers(['John Doe', 'Jane Doe'], true);
        $r->setTicketNumbers(['345667', '345668'], false);
        $s = $r->addSegment();
        $s->departure()
            ->name('Boston South Station - Gate 9 NYC-Gate 10 NWK\/PHL')
            ->date2('2030-01-01 13:30');
        $s->arrival()
            ->name('New York W 33rd St & 11-12th Ave (DC,BAL,BOS,PHL)')
            ->date2('2030-01-01 20:34');
        $s->extra()
            ->number('2023')
            ->seats(['11', '12'])
            ->type('Regular')
            ->model('Mercedes')
            ->miles('43mi')
            ->duration('7h');
    }

    protected function rental()
    {
        $r = $this->itinerariesMaster->add()->rental();
        $r->program()
            ->code('avis')
            ->accounts(['AVS454545'], false)
            ->earnedAwards('50 points')
            ->phone('+1-33-4456', 'Customer support');
        $r->price()
            ->cost(193.75)
            ->total(251.41)
            ->tax(34.56)
            ->fee('Insurance', 23.10)
            ->discount(40)
            ->currency('USD')
            ->spentAwards('10000 points');
        $r->general()
            ->confirmation('1122334455', 'Confirmation number', true)
            ->confirmation('887756', 'Reference', false)
            ->status('Confirmed')
            ->date2('2000-01-01')
            ->travellers(['John Doe'], true);
        $r->pickup()
            ->location('Palm Beach Intl Airport,PBI, 2500 Turnage Boulevard, West Palm Beach, FL 33406 US')
            ->date2('2030-01-01 13:30');
        $r->setPickUpPhone('+1-13-PICKUP')
            ->setPickUpFax('+1-14-FAX')
            ->setPickUpHours('Sun - Sat open 24 hrs');
        $r->dropoff()->same()->date2('2030-01-05 13:30');
        $r->setDropOffPhone('+1-13-DROPOFF')
            ->setDropOffFax('+1-14-FAX')
            ->setDropOffHours('Sun - Sat open 24 hrs');
        $r->car()
            ->model('Ford Edge or similar')
            ->type('Regular')
            ->image('http://car.image/url');
    }

    protected function hotel()
    {
        $h = $this->itinerariesMaster->add()->hotel();
        $h->ota()
            ->confirmation('CONFNO1');
        $h->program()
            ->code('spg')
            ->accounts(['xxxxxx345'], true)
            ->earnedAwards('4 nights')
            ->phone('+1-33-4456', 'Customer support');
        $h->price()
            ->cost(200)
            ->total(300)
            ->tax(100)
            ->discount(40)
            ->currency('USD')
            ->spentAwards('10000 points');
        $h->general()
            ->confirmation('1122334455', 'Confirmation number', true)
            ->confirmation('887756', 'Reference', false)
            ->status('Confirmed')
            ->date2('2000-01-01')
            ->travellers(['John D.', 'Jane D.'], false);
        $h->hotel()
            ->name('Sheraton Philadelphia Downtown Hotel')
            ->address('201 North 17th Street, Philadelphia, Pennsylvania 19103 United States')
            ->phone('+1-22-3333')
            ->fax('+1-66-77899');
        $h->booked()
            ->checkIn2('2030-01-01 13:30')
            ->checkOut2('2030-01-05 12:00')
            ->guests(2)
            ->kids(3)
            ->rooms(1)
            ->cancellation('Cancellation is free prior to check-in');
        $h->addRoom()
            ->setType('King bed')
            ->setDescription('Traditional, TV, free wi-fi')
            ->setRate('30$/night')
            ->setRateType('King bed');
    }

    protected function flight()
    {
        $f = $this->itinerariesMaster->add()->flight();
        $f->issued()
            ->tickets(['006 123321', '006 456654'], false)
            ->confirmation('ISSD12');
        $f->program()
            ->code('delta')
            ->accounts(['1234****', '4321****'], true)
            ->earnedAwards('300 award miles')
            ->phone('+1-2345-67890', 'Customer support');
        $f->price()
            ->cost(100)
            ->total(150)
            ->tax(30)
            ->fee('Seat selection', 5.5)
            ->fee('Baggage fee', 14.5)
            ->discount(28.34)
            ->currency('USD')
            ->spentAwards('3 segments');
        $f->general()
            ->confirmation('MRTG67')
            ->status('Confirmed')
            ->date2('2000-01-01')
            ->travellers(['John Doe', 'Jane Doe'], true);
        $s = $f->addSegment();
        $s->departure()
            ->code('LAX')
            ->date2('2030-01-01 13:30')
            ->terminal('A');
        $s->arrival()
            ->code('SFO')
            ->date2('2030-01-01 15:00')
            ->terminal('2');
        $s->airline()
            ->name('DL')
            ->operator('Sky Express')
            ->wetlease()
            ->number('0013')
            ->carrierName('BA')
            ->carrierConfirmation('CARR23')
            ->carrierNumber('5566');
        $s->extra()
            ->aircraft('7M7')
            ->stops(0)
            ->smoking(false)
            ->meal('Snacks')
            ->duration('1h30min')
            ->cabin('Coach')
            ->bookingCode('CL')
            ->miles('300mi')
            ->seats(['3E', '3F']);
        $s = $f->addSegment();
        $s->departure()
            ->code('SFO')
            ->date2('2030-01-05 06:00')
            ->terminal('2');
        $s->arrival()
            ->code('LAX')
            ->date2('2030-01-05 07:30')
            ->terminal('A');
        $s->airline()
            ->name('DL')
            ->number('0014')
            ->carrierName('BA')
            ->carrierConfirmation('CARR23')
            ->carrierNumber('9009');
        $s->extra()
            ->aircraft('7M7')
            ->meal('Snacks')
            ->duration('1h30min')
            ->cabin('First class')
            ->bookingCode('I')
            ->miles('300mi')
            ->seats(['1B', '1C']);
    }
}
