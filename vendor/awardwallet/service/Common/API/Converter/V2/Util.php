<?php


namespace AwardWallet\Common\API\Converter\V2;


use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Common\Parsing\Solver\Extra\GeoData;
use AwardWallet\Schema\Itineraries\Address;
use AwardWallet\Schema\Itineraries\ConfNo;
use AwardWallet\Schema\Itineraries\Fee;
use AwardWallet\Schema\Itineraries\NumberedSeat;
use AwardWallet\Schema\Itineraries\ParsedNumber;
use AwardWallet\Schema\Itineraries\Person;
use AwardWallet\Schema\Itineraries\PhoneNumber;
use AwardWallet\Schema\Itineraries\PricingInfo;
use AwardWallet\Schema\Itineraries\ProviderInfo;
use AwardWallet\Schema\Parser\Common\Price;
use AwardWallet\Schema\Parser\Common\TravelAgency;

class Util {

	const DATE_FORMAT = 'Y-m-d\TH:i:s';

	public static function date($time) {
		if (!$time)
			return null;
		return date(self::DATE_FORMAT, $time);
	}

	/**
	 * @param $names array ['name', 'isfull']
	 * @param $isFull boolean are all full
	 * @return Person[]|null
	 */
	public static function names($names, $isFull, $type) {
		if (!is_array($names) || count($names) === 0)
			return null;
		$r = [];
		foreach($names as $pair) {
			$p = new Person();
			$p->name = $pair[0];
			$p->full = $pair[1] ?? $isFull;
			$p->type = $type;
			$r[] = $p;
		}
		return $r;
	}

    /**
     * @param Person[] $travellers
     * @param array $accountNumbers
     * @param array $ticketNumbers
     * @return void
     */
    public static function setNumbers(array $travellers, array $accountNumbers, array $ticketNumbers)
    {
        foreach($travellers as $traveller) {
            foreach($accountNumbers as $number) {
                if (!empty($number[2]) && strcasecmp($traveller->name, $number[2]) === 0) {
                    $pn = new ParsedNumber();
                    $pn->number = $number[0];
                    $pn->masked = $number[1];
                    $traveller->accountNumbers[] = $pn;
                }
            }
            foreach($ticketNumbers as $number) {
                if (!empty($number[2]) && strcasecmp($traveller->name, $number[2]) === 0) {
                    $pn = new ParsedNumber();
                    $pn->number = $number[0];
                    $pn->masked = $number[1];
                    $traveller->ticketNumbers[] = $pn;
                }
            }
        }
    }

    /**
     * @param Person[] $travellers
     * @param array $seats
     * @param int $idx
     * @return void
     */
    public static function setSeats(array $travellers, array $seats, int $idx)
    {
        foreach($travellers as $traveller) {
            foreach($seats as $seat) {
                if (!empty($seat[1]) && strcasecmp($seat[1], $traveller->name) === 0) {
                    $ns = new NumberedSeat();
                    $ns->seatNumber = $seat[0];
                    $ns->segmentNumber = $idx;
                    $traveller->seats[] = $ns;
                }
            }
        }
    }


	public static function emptyAddress(array $lines)
    {
	    $a = new Address();
	    $lines = array_filter($lines);
	    if (count($lines) > 0) {
            $a->text = array_shift($lines);
            return $a;
        }
        return null;
    }

	public static function address($text, GeoData $geo, ?string $date) : Address {
		$address = new Address();
		$address->text = $text;
		$address->addressLine = $geo->address;
		$address->city = $geo->city;
		$address->stateName = $geo->state;
		$address->countryName = $geo->country;
		$address->countryCode = $geo->countryCode;
		$address->postalCode = $geo->zip;
		if ($date && $geo->tzName)
		    $address->timezone = (new \DateTime($date, new \DateTimeZone($geo->tzName)))->getOffset();
        else
		    $address->timezone = $geo->tz;
		$address->timezoneId = $geo->tzName;
		$address->lat = $geo->lat;
		$address->lng = $geo->lng;
		return $address;
	}

	/**
	 * @param $phones array of pairs[['number', 'description']]
	 * @return PhoneNumber[]|null
	 */
	public static function phones($phones) {
		if (!$phones)
			return null;
		$r = [];
		$exist = [];
		foreach($phones as $pair) {
		    if (in_array($pair[0], $exist))
		        continue;
		    $exist[] = $pair[0];
			$phone = new PhoneNumber();
			$phone->number = $pair[0];
			$phone->description = $pair[1];
			$r[] = $phone;
		}
		return $r;
	}

	/**
	 * @param $numbers
	 * @param $masked
	 * @return ParsedNumber[]|null
	 */
	public static function numbers($numbers, $masked) {
		if (!$numbers)
			return null;
		$r = [];
		foreach($numbers as $pair) {
			$new = new ParsedNumber();
			$new->number = $pair[0];
			$new->masked = $pair[1] ?? $masked;
			$r[] = $new;
		}
		return $r;
	}

	/**
	 * @param Price $price
	 * @return PricingInfo|null
	 */
	public static function price(Price $price) {
		if (!$price)
			return null;
		$new = new PricingInfo();
		$new->total = $price->getTotal();
		$new->cost = $price->getCost();
		$new->currencyCode = $price->getCurrencyCode();
		$new->discount = $price->getDiscount();
		$new->spentAwards = $price->getSpentAwards();
		if (is_array($price->getFees())) {
			$new->fees = [];
			foreach($price->getFees() as $fee) {
				$n = new Fee();
				$n->name = $fee[0];
				$n->charge = $fee[1];
				$new->fees[] = $n;
			}
		}
		return $new;
	}

	/**
	 * @param TravelAgency $ota
	 * @param Extra $extra
	 * @return \AwardWallet\Schema\Itineraries\TravelAgency|null
	 */
	public static function ota(TravelAgency $ota, Extra $extra) {
		if (!$ota)
			return null;
		$r = new \AwardWallet\Schema\Itineraries\TravelAgency();
		if (count($ota->getConfirmationNumbers()) > 0) {
			$r->confirmationNumbers = [];
			foreach ($ota->getConfirmationNumbers() as $pair) {
				$new = new ConfNo();
				$new->number = $pair[0];
				$new->description = $pair[1];
				$new->isPrimary = $ota->isConfirmationNumberPrimary($new->number);
				$r->confirmationNumbers[] = $new;
			}
		}
		$r->phoneNumbers = self::phones($ota->getProviderPhones());
		$r->providerInfo = self::provider($ota->getProviderCode(), $ota->getProviderKeyword(), $ota->getAccountNumbers(), $ota->getAreAccountMasked(), $ota->getEarnedAwards(), $extra);
		return $r;
	}

    /**
     * @param $code
     * @param $name
     * @param $numbers
     * @param $masked
     * @param $earned
     * @param Extra $extra
     * @return ProviderInfo|null
     */
	public static function provider($code, $name, $numbers, $masked, $earned, Extra $extra) {
		$r = new ProviderInfo();
		foreach([$code, $name] as $key)
		    if ($key && !isset($provider))
		        $provider = $extra->data->getProvider($key);
		if (isset($provider)) {
		    $provider = $extra->data->getProvider($code) ?? $extra->data->getProvider($name);
			$r->code = $provider->code;
			$r->name = $provider->name;
		}
		$r->accountNumbers = self::numbers($numbers, $masked);
		$r->earnedRewards = $earned;
		if (!empty($r->code) || !empty($r->accountNumbers) || !empty($r->earnedRewards))
			return $r;
		return null;
	}

	/**
	 * @param \AwardWallet\Schema\Parser\Common\Itinerary $it
	 * @return ConfNo[]|null
	 */
	public static function confirmations(\AwardWallet\Schema\Parser\Common\Itinerary $it) {
		$r = null;
		if (count($it->getConfirmationNumbers()) > 0) {
			$r = [];
			foreach($it->getConfirmationNumbers() as $pair) {
				$new = new ConfNo();
				$new->number = $pair[0];
				$new->description = $pair[1];
				$new->isPrimary = $it->isConfirmationNumberPrimary($new->number);
				$r[] = $new;
			}
		}
		return $r;
	}

}