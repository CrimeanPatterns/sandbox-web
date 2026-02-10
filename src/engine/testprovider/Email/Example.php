<?php

namespace AwardWallet\Engine\testprovider\Email;

use AwardWallet\Schema\Parser\Common\Coupon;
use AwardWallet\Schema\Parser\Common\Event;
use AwardWallet\Schema\Parser\Email\Email;
use PlancakeEmailParser;

class Example extends \TAccountChecker
{
    protected $froms = [
        'bus@test.awardwallet.com',
        'train@test.awardwallet.com',
        'transfer@test.awardwallet.com',
        'cruise@test.awardwallet.com',
        'ferry@test.awardwallet.com',
        'event@test.awardwallet.com',
        'flight@test.awardwallet.com',
        'rental@test.awardwallet.com',
        'hotel@test.awardwallet.com',
        'parking@test.awardwallet.com',
        'agency@test.awardwallet.com',
        'debug@test.awardwallet.com',
        'bpass@test.awardwallet.com',
        'coupon@test.awardwallet.com',
        'onetimecode@test.awardwallet.com',
        'junk@test.awardwallet.com',
    ];

    public function detectEmailByHeaders(array $headers)
    {
        return isset($headers['from']) && in_array($headers['from'], $this->froms);
    }

    public function detectEmailByBody(PlancakeEmailParser $parser)
    {
        foreach ($this->froms as $from) {
            if (stripos($parser->emailRawContent, $from) !== false) {
                return true;
            }
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return false;
    }

    public function ParsePlanEmailExternal(PlancakeEmailParser $parser, Email $email)
    {
        $body = $parser->getPlainBody();

        if (strpos($body, '[') === 0 && ($array = @json_decode($body)) && is_array($array)) {
            $array = array_map(function ($s) { if (is_string($s) && strpos($s, '@test.awardwallet.com') === false) { $s .= '@test.awardwallet.com'; }

return $s; }, $array);
        } else {
            $array = [$parser->getCleanFrom()];
        }

        foreach ($array as $item) {
            switch ($item) {
                case 'flight@test.awardwallet.com':
                    $this->flight($email);

                    break;

                case 'hotel@test.awardwallet.com':
                    $this->hotel($email);

                    break;

                case 'rental@test.awardwallet.com':
                    $this->rental($email);

                    break;

                case 'bus@test.awardwallet.com':
                    $this->bus($email);

                    break;

                case 'train@test.awardwallet.com':
                    $this->train($email);

                    break;

                case 'transfer@test.awardwallet.com':
                    $this->transfer($email);

                    break;

                case 'cruise@test.awardwallet.com':
                    $this->cruise($email);

                    break;

                case 'event@test.awardwallet.com':
                    $this->event($email);

                    break;

                case 'ferry@test.awardwallet.com':
                    $this->ferry($email);

                    break;

                case 'parking@test.awardwallet.com':
                    $this->parking($email);

                    break;

                case 'agency@test.awardwallet.com':
                    $this->agency($email);

                    break;

                case 'debug@test.awardwallet.com':
                    $this->debug($email);

                    break;

                case 'bpass@test.awardwallet.com':
                    $this->bpass($email);

                    break;

                case 'onetimecode@test.awardwallet.com':
                    $this->oneTimeCode($email);

                    break;

                case 'coupon@test.awardwallet.com':
                    $this->coupon($email);

                    break;

                case 'junk@test.awardwallet.com':
                    $this->junk($email);

                    break;
            }
        }

        return $email;
    }

    public function ParsePlanEmail(PlancakeEmailParser $parser)
    {
        return null;
    }

    protected function agency(Email $email)
    {
        $this->flight($email);
        $this->hotel($email);
        $email->setProviderCode('expedia');
        $email->ota()
            ->confirmation('J3HND-8776', 'Trip Locator')
            ->code('expedia')
            ->account('EXP-11298', false)
            ->earnedAwards('1 booking')
            ->phone('+1-44-EXPEDIA', 'Help Desk');

        foreach ($email->getItineraries() as $it) {
            $it->removePrice();
        }
        $email->price()
            ->cost(193.75)
            ->total(251.41)
            ->tax(34.56)
            ->fee('Insurance', 23.10)
            ->discount(40)
            ->currency('USD')
            ->spentAwards('10000 points');
    }

    protected function event(Email $email)
    {
        $email->setProviderCode('opentable');
        $r = $email->add()->event();
        $r->program()
            ->code('opentable')
            ->accounts(['AM3398'], false)
            ->earnedAwards('50 points')
            ->phone('+1-33-4456', 'Customer support');
        $r->price()
            ->cost(193.75)
            ->total(351.41)
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

    protected function parking(Email $email)
    {
        $email->setProviderCode('parkingspot');
        $r = $email->add()->parking();
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

    protected function cruise(Email $email)
    {
        $email->setProviderCode('disneycruise');
        $r = $email->add()->cruise();
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

    protected function ferry(Email $email)
    {
        $email->setProviderCode('disneycruise');
        $r = $email->add()->ferry();
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
        $r->setTicketNumbers(['111-38474', '111-38475'], false);
        $s = $r->addSegment();
        $s->departure()
            ->code('ITPIO')
            ->name('Piombino')
            ->date2('2030-01-01 14:30');
        $s->arrival()
            ->code('ITOLB')
            ->name('Olbia')
            ->date2('2030-01-01 19:00');
        $s->extra()
            ->carrier('ANEK SUPERFAST')
            ->vessel('Hellenic Spirit')
            ->status('confirmed')
            ->miles('7.4 mi')
            ->duration('2h')
            ->meal('none')
            ->smoking(false);
        $s->booked()
            ->accommodations([
                '4-Bed inside cabin, shower/WC',
                '4-Bed inside cabin, shower/WC',
            ])
            ->adults(2)
            ->pets('1 cat, 1 dog')
            ->kids(1);
        $v = $s->addVehicle();
        $v
            ->setType('HH1683B - GOLF 6 VW')
            ->setHeight('less than 1.90 meters')
            ->setLength('less than 5.00 meters')
            ->setWidth('less than 2.00 meters');
        $t = $s->addTrailer();
        $t
            ->setHeight('less than 1 meters')
            ->setLength('less than 2.00 meters')
            ->setWidth('less than 1.5 meters');
    }

    protected function transfer(Email $email)
    {
        $email->setProviderCode('uber');
        $r = $email->add()->transfer();
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

    protected function train(Email $email)
    {
        $email->setProviderCode('amtrak');
        $r = $email->add()->train();
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

    protected function bus(Email $email)
    {
        $email->setProviderCode('boltbus');
        $r = $email->add()->bus();
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

    protected function rental(Email $email)
    {
        $email->setProviderCode('avis');
        $r = $email->add()->rental();
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

    protected function hotel(Email $email)
    {
        $email->setProviderCode('spg');
        $h = $email->add()->hotel();
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
            ->travellers(['John D.', 'Jane D.'], false)
            ->cancellation('Cancel, no-show or early check out 100% charge non refundable');
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
            ->deadlineRelative('48 hours', '12:00')
            ->parseNonRefundable();
        $h->addRoom()
            ->setType('King bed')
            ->setDescription('Traditional, TV, free wi-fi')
            ->setRate('30$/night')
            ->setRateType('King bed');
    }

    protected function flight(Email $email)
    {
        $email->setProviderCode('delta');
        $f = $email->add()->flight();
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
            ->seats(['3E', '3F'])
            ->status('confirmed');
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
            ->seats(['1B', '1C'])
            ->status('confirmed');
    }

    private function debug(Email $email)
    {
        $this->flight($email);
    }

    private function bpass(Email $email)
    {
        $email->add()->bpass()
            ->setDepCode('LAX')
            ->setFlightNumber('123')
            ->setUrl('http://some.url');
    }

    private function oneTimeCode(Email $email)
    {
        $email->add()->oneTimeCode()->setCode('OT1C67');
    }

    private function junk(Email $email)
    {
        $email->setIsJunk(true);
    }

    private function coupon(Email $email)
    {
        $email->createCoupon()
            ->setOwner('John Doe')
            ->setCategory(Coupon::CAT_HOTEL)
            ->setType(Coupon::TYPE_GIFT_CARD)
            ->setNumber('AB1123')
            ->setPin('3980')
            ->setValue('30$')
            ->setExpirationDate(strtotime('+ 2 years 00:00'))
            ->setCanExpire(true);
    }
}
