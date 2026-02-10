<?php


namespace AwardWallet\Common\Parsing\Solver\Itinerary;

use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Common\Parsing\Solver\Helper\AirlineAirCodeHelper;
use AwardWallet\Common\Parsing\Solver\Helper\DataHelper;
use AwardWallet\Common\Parsing\Solver\Helper\DateCorrector;
use AwardWallet\Common\Parsing\Solver\Helper\ExtraHelper;
use AwardWallet\Common\Parsing\Solver\Helper\FlightHelper;
use AwardWallet\Common\Parsing\Solver\Helper\FlightSegmentData;
use AwardWallet\Common\Parsing\Solver\Helper\FSHelper;
use AwardWallet\Common\Parsing\Solver\Helper\SegmentHelper;
use AwardWallet\Schema\Parser\Common\FlightSegment;
use AwardWallet\Schema\Parser\Common\Itinerary;
use Psr\Log\LoggerInterface;

class Flight extends ItinerarySolver {

	/**
	 * @var FlightHelper
	 */
	private $fh;
	/**
	 * @var SegmentHelper
	 */
	private $sh;
    /**
     * @var FSHelper
     */
	private $fsh;

	public function __construct(FlightHelper $fh, DataHelper $dh, ExtraHelper $eh, SegmentHelper $sh, FSHelper $fsh, LoggerInterface $logger)
    {
		parent::__construct($eh, $dh);
		$this->fh = $fh;
		$this->sh = $sh;
		$this->fsh = $fsh;
		$this->logger = $logger;
	}

	protected function solveItinerary(Itinerary $it, Extra $extra)
    {
		/** @var \AwardWallet\Schema\Parser\Common\Flight $it */
		$ticket = count($it->getTicketNumbers()) > 0 ? $it->getTicketNumbers()[0][0] : null;
		if ($ticket && $data = $this->fh->parseTicketPrefix($ticket)) {
		    $it->setIssuingAirlineName($data->name);
		    $extra->data->addAirline($data->name, $data);
        }
        /* refs 20264
		if (!$it->getIssuingAirlineName() && $extra->provider->iata)
		    $it->setIssuingAirlineCode($extra->provider->code);
        */
		if ($data = $this->fh->parseTicketingInfo($it->getIssuingAirlineName(), $it->getIssuingAirlineCode(), $extra)) {
		    if (!$it->getIssuingAirlineName())
			    $it->setIssuingAirlineName($data->name);
		    $extra->data->addAirline($it->getIssuingAirlineName(), $data);
		}
		$airlineLocator = null;
        $backupLocator = null;
		foreach($it->getConfirmationNumbers() as $number) {
            if (is_null($backupLocator)) {
                $backupLocator = $number[0];
            }
            if (is_null($airlineLocator) && $this->isLocatorClassic($number[0])) {
                $airlineLocator = $number[0];
            }
            if ($it->isConfirmationNumberPrimary($number[0])) {
                $airlineLocator = $number[0];
                break;
            }
        }
        if (empty($airlineLocator) && !empty($backupLocator)) {
            $airlineLocator = $backupLocator;
        }
		$previous = null;

        $checkAir = null;
        $sharedAlert = false;
        $emptyLocator = true;

        $corrector = new DateCorrector();
		foreach($it->getSegments() as $s) {
		    $checkAirlineRecloc = !empty($airlineLocator) && empty($s->getConfirmation());

			// DB aliases lookup

            if ($s->getDepCode())
                switch($code = $this->fh->checkAirCode($s->getDepCode(), $extra)) {
                    case null:
                        $s->clearDepCode();
                        break;
                    case $s->getDepCode():
                        break;
                    default:
                        $s->setDepCode($code);
                        break;
                }
			if (!$s->getDepCode() && $code = $this->fh->solveAirCode($s->getDepName(), $extra))
				$s->setDepCode($code);

            if ($s->getArrCode())
                switch($code = $this->fh->checkAirCode($s->getArrCode(), $extra)) {
                    case null:
                        $s->clearArrCode();
                        break;
                    case $s->getArrCode():
                        break;
                    default:
                        $s->setArrCode($code);
                        break;
                }
			if (!$s->getArrCode() && $code = $this->fh->solveAirCode($s->getArrName(), $extra))
				$s->setArrCode($code);

			if ($s->getAirlineName() && !$extra->data->existsAirline($s->getAirlineName()) && $data = $this->eh->solveAirline($s->getAirlineName(), $extra))
				$extra->data->addAirline($s->getAirlineName(), $data);

			if ($s->getAirlineName() && ($data = $extra->data->getAirline($s->getAirlineName())) && !empty($data->iata)) {
			    if (!$s->getDepCode() && $s->getDepName() && $code = AirlineAirCodeHelper::lookup($s->getDepName(), $data->iata)) {
			        $s->setDepCode($code);
                }
                if (!$s->getArrCode() && $s->getArrName() && $code = AirlineAirCodeHelper::lookup($s->getArrName(), $data->iata)) {
                    $s->setArrCode($code);
                }
            }

			 $this->matchAirlineRecLoc($s, $extra, $airlineLocator);

			if (!$s->getAirlineName() && $airline = $this->checkSameAirline($s->getConfirmation(), $it->getIssuingConfirmation(), $it->getIssuingAirlineName()))
				$s->setAirlineName($airline);

			if (!$s->getConfirmation() && $locator = $this->checkSameAirline($s->getAirlineName(), $it->getIssuingAirlineName(), $it->getIssuingConfirmation()))
				$s->setConfirmation($locator);

			if ($s->getCarrierAirlineName() && !$extra->data->getAirline($s->getCarrierAirlineName()) && $data = $this->eh->solveAirline($s->getCarrierAirlineName(), $extra))
				$extra->data->addAirline($s->getCarrierAirlineName(), $data);

			if ($s->getOperatedBy() && $data = $this->eh->solveAirline($s->getOperatedBy(), $extra)) {
			    if (!$extra->data->getAirline($s->getOperatedBy()))
			        $extra->data->addAirline($s->getOperatedBy(), $data);
                if ($s->getIsWetlease() === null) {
                    if ($this->fh->isWetlease($data->iata))
                        $s->setIsWetlease(true);
                    elseif (!$s->getCarrierAirlineName())
                        $s->setCarrierAirlineName($data->name);
                }
            }

			if (!$s->getCarrierAirlineName() && $airline = $this->checkSameAirline($s->getCarrierConfirmation(), $it->getIssuingConfirmation(), $it->getIssuingAirlineName()))
				$s->setCarrierAirlineName($airline);

			if (!$s->getCarrierConfirmation()
                && $s->getCarrierAirlineName()
                && $it->getIssuingAirlineName()
                && ($d1 = $extra->data->getAirline($s->getCarrierAirlineName()))
                && ($d2 = $extra->data->getAirline($it->getIssuingAirlineName()))
                && ($locator = $this->checkSameAirline($d1->name, $d2->name, $it->getIssuingConfirmation())))

				$s->setCarrierConfirmation($locator);

			$this->fh->solveAircraft($s->getAircraft(), $extra);
			// simple condition - next dep is always later than previous arr
			if ($extra->settings->correctDatesBetweenSegments && !$s->getDatesStrict() && !empty($previous) && $date = $corrector->fixDateNextSegment($previous, $s->getDepDate()))
				$s->setDepDate($date);
			// flight stats
            if ($this->fsh->isFsEnabled($extra) && (!$it->getCancelled() || in_array($extra->context->partnerLogin, ['traxo', 'awardwallet', 'testemail']))) {
                if ($s->getAirlineName() && $extra->data->getAirline($s->getAirlineName()))
                    $carrier = $extra->data->getAirline($s->getAirlineName())->iata;
                else
                    $carrier = null;
                $context = $this->fsh->getContext(
                    $carrier, $s->getFlightNumber(),
                    $s->getDepCode(), $s->getArrCode(),
                    $s->getDepDate(), $s->getArrDate(), $extra->context->partnerLogin,
                    $s->getDepDay(), $s->getArrDay(), $s->getCarrierAirlineName(), $s->getCarrierFlightNumber());
                $sData = null;
                if ($context->getEligible()) {
                    $sData = $this->fsh->process(
                        $context,
                        $carrier, $s->getFlightNumber(),
                        $s->getDepCode(), $s->getArrCode(),
                        $s->getDepDate(), $s->getArrDate(),
                        $s->getDepDay(), $s->getArrDay(),
                        $extra, $s->getId());
                    if ($context->wasCallMade())
                        $extra->solverData->addFsCall($s->getId(), $context->getMethod());
                }
                if (isset($sData)) {
                    $this->processSegmentData($s, $sData, $extra);
                    $this->matchAirlineRecLoc($s, $extra, $airlineLocator);
                }
                if (!$context->wasCallMade() && (empty($s->getCarrierAirlineName()) || empty($s->getCarrierFlightNumber())) && $context->getPartnerLogin() === 'awardwallet') {
                    if (!empty($s->getAirlineName()) && !empty($a = $extra->data->getAirline($s->getAirlineName())) && !empty($s->getFlightNumber()) && !empty($s->getDepDate()))
                        $this->logger->info('retrieve operating airline info',
                            ['fs_key' => sprintf('%s_%s_%s',
                                $a->iata,
                                ltrim($s->getFlightNumber(), ' 0'),
                                date('Ymd', $s->getDepDate()))]);
                }
            }
			if (!$it->getIssuingConfirmation()) {
			    $d1 = $it->getIssuingAirlineName() ? $extra->data->getAirline($it->getIssuingAirlineName()) : null;
                $d2 = $s->getAirlineName() ? $extra->data->getAirline($s->getAirlineName()) : null;
                $d3 = $s->getCarrierAirlineName() ? $extra->data->getAirline($s->getCarrierAirlineName()) : null;
                if ($d1 && $d2 && ($locator = $this->checkSameAirline($d1->name, $d2->name, $s->getConfirmation()))
                    || $d1 && $d3 && ($locator = $this->checkSameAirline($d1->name, $d3->name, $s->getCarrierConfirmation()))
                    || $d1 && $extra->provider->iata && $d1->iata === $extra->provider->iata && ($locator = $airlineLocator))
                    $it->setIssuingConfirmation($locator);
            }

			if (!$it->getIssuingAirlineName() && (
					($airline = $this->checkSameAirline($it->getIssuingConfirmation(), $s->getConfirmation(), $s->getAirlineName()))
					||
					($airline = $this->checkSameAirline($it->getIssuingConfirmation(), $s->getCarrierConfirmation(), $s->getCarrierAirlineName()))
				))
				$it->setIssuingAirlineName($airline);
			if (!empty($s->getConfirmation()))
			    $emptyLocator = false;
			// calculating tz offset between airports and adjusting dates
            if (!$s->getDatesStrict() && ($date = $corrector->fixDateOvernightSegment($s->getDepDate(), $this->fh->getAirportOffset($s->getDepCode()), $s->getArrDate(), $this->fh->getAirportOffset($s->getArrCode()))))
				$s->setArrDate($date);
			$previous = $s->getArrDate();
			// parsing and storing details
			$this->parseDetails($s, $extra);

			if ($checkAirlineRecloc && !$sharedAlert && $s->getAirlineName()) {
			    $cur = ($air = $extra->data->getAirline($s->getAirlineName())) ? $air->iata : $s->getAirlineName();
			    if (!isset($checkAir))
			        $checkAir = $cur;
			    elseif ($checkAir !== $cur)
			        $sharedAlert = true;
            }
		}
		if (empty($it->getIssuingConfirmation()) && $emptyLocator && !empty($airlineLocator)) {
            foreach ($it->getSegments() as $s)
                $s->setConfirmation($airlineLocator);
            $it->setIssuingConfirmation($airlineLocator);
        }
		// solving airline names from phone array and pulling missing phones from db
		foreach($it->getAirlinePhones() as $airline => $arr)
			if (!$extra->data->existsAirline($airline) && ($data = $this->eh->solveAirline($airline, $extra)))
			    $extra->data->addAirline($airline, $data);
			/*
		foreach($extra->data->getAirlineCodes() as $code)
			if (!array_key_exists($code, $it->getAirlinePhones()) && ($data = $extra->data->getAirline($code)) && $phone = $this->eh->getProviderPhone(null, $data->iata))
				$it->addAirlinePhone($code, $phone, null, true, true);
			*/


        $airRecLoc = $reclocAir = [];
        $airRecLocAlert = $reclocAirAlert = false;
        $codesMissing = false;
        foreach($it->getSegments() as $s) {
            if (($airName = $s->getAirlineName()) && ($locator = $s->getConfirmation())) {
                if ($air = $extra->data->getAirline($airName))
                    $airName = $air->iata;
                if (!isset($airRecLoc[$airName]))
                    $airRecLoc[$airName] = $locator;
                elseif ($airRecLoc[$airName] !== $locator)
                    $airRecLocAlert = true;
                if (!isset($reclocAir[$locator]))
                    $reclocAir[$locator] = $airName;
                elseif ($reclocAir[$locator] !== $airName)
                    $reclocAirAlert = true;
            }
            if (!$s->getDepCode() || !$s->getArrCode()) {
                $codesMissing = true;
            }
        }
        if ($airRecLocAlert || $reclocAirAlert || $sharedAlert)
            $this->logger->notice('recloc matching notice', ['AirRecLoc' => $airRecLocAlert, 'RecLocAir' => $reclocAirAlert, 'SharedRecLoc' => $sharedAlert]);
        $this->logger->info('flight aircode stat', ['missing' => $codesMissing, 'component' => 'FlightSolver']);
	}

	private function checkSameAirline($cmp1, $cmp2, $cmpValue)
    {
		if ($cmpValue && $cmp1 && ($cmp1 === $cmp2))
			return $cmpValue;
		return null;
	}

	private function matchAirlineRecLoc(FlightSegment $s, Extra $extra, $recloc): void
    {
        if (!$s->getConfirmation() && $recloc
            && (($name = $s->getAirlineName()) && ($air = $extra->data->getAirline($name)) && $extra->provider->iata && ($air->iata === $extra->provider->iata)
                || empty($extra->provider->iata)))
            $s->setConfirmation($recloc);
    }

	private function processSegmentData(FlightSegment $s, FlightSegmentData $data, Extra $extra): void
    {
        if (!$s->getDepCode() && $data->depAir && $data->depAir->getIata()) {
            $s->setDepCode($data->depAir->getIata());
            $this->dh->parseAirCode($data->depAir->getIata(), $extra);
            if (!$extra->data->existsGeo($data->depAir->getIata()))
                $extra->data->addGeoArray($data->depAir->getIata(), [
                    'Name' => $data->depAir->getName(),
                    'AddressLine' => null,
                    'City' => $data->depAir->getCity(),
                    'State' => null,
                    'Country' => $data->depAir->getCountryName(),
                    'PostalCode' => null,
                    'Lat' => $data->depAir->getLatitude(),
                    'Lng' => $data->depAir->getLongitude(),
                    'TimeZoneLocation' => $data->depAir->getTimeZoneRegionName(),
                ]);
        }
        if (!$s->getArrCode() && $data->arrAir && $data->arrAir->getIata()) {
            $s->setArrCode($data->arrAir->getIata());
            $this->dh->parseAirCode($data->arrAir->getIata(), $extra);
            if (!$extra->data->existsGeo($data->arrAir->getIata()))
                $extra->data->addGeoArray($data->arrAir->getIata(), [
                    'Name' => $data->arrAir->getName(),
                    'AddressLine' => null,
                    'City' => $data->arrAir->getCity(),
                    'State' => null,
                    'Country' => $data->arrAir->getCountryName(),
                    'PostalCode' => null,
                    'Lat' => $data->arrAir->getLatitude(),
                    'Lng' => $data->arrAir->getLongitude(),
                    'TimeZoneLocation' => $data->arrAir->getTimeZoneRegionName(),
                ]);
        }
        if (!$s->getDepTerminal() && $data->depTerminal)
            $s->setDepTerminal($data->depTerminal);
        if (!$s->getArrTerminal() && $data->arrTerminal)
            $s->setArrTerminal($data->arrTerminal);
        if (!$s->getDepDate() && $data->depDate)
            $s->setDepDate($data->depDate);
        if (!$s->getArrDate() && $data->arrDate)
            $s->setArrDate($data->arrDate);
        if (!$s->getFlightNumber() && $data->fn)
            $s->setFlightNumber($data->fn);
        if ($data->carrier) {
            if (!$s->getAirlineName())
                $s->setAirlineName($data->carrier->getName());
            if (!$extra->data->getAirline($s->getAirlineName()))
                $extra->data->addAirlineArray($s->getAirlineName(), [
                    'Code' => $data->carrier->getIata(),
                    'ICAO' => $data->carrier->getIcao(),
                    'Name' => $data->carrier->getName()]);
        }
        if (!$s->getCarrierFlightNumber() && $data->operatorFn)
            $s->setCarrierFlightNumber($data->operatorFn);
        if ($data->operatorCarrier) {
            if (!$s->getCarrierAirlineName())
                $s->setCarrierAirlineName($data->operatorCarrier->getName());
            if (!$extra->data->getAirline($data->operatorCarrier->getName()))
                $extra->data->addAirlineArray($data->operatorCarrier->getName(), [
                    'Code' => $data->operatorCarrier->getIata(),
                    'ICAO' => $data->operatorCarrier->getIcao(),
                    'Name' => $data->operatorCarrier->getName()]);
        }
        if ((!$s->getAircraft() || !$extra->data->getAircraft($s->getAircraft())) && $data->aircraftIata) {
            $s->setAircraft($data->aircraftIata);
            $this->fh->solveAircraft($data->aircraftIata, $extra);
        }
    }

	private function parseDetails(FlightSegment $s, Extra $extra) {
		$this->sh->parseSegmentLocation($s, $extra, true, false);
	}

    private function isLocatorClassic($locator): bool
    {
        return !empty($locator) && preg_match('/^[A-Z\d]{5,7}$/', $locator) > 0;
    }

}
