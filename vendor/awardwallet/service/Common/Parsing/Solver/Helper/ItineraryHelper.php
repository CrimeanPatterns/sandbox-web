<?php

namespace AwardWallet\Common\Parsing\Solver\Helper;


use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Schema\Parser\Common\FlightSegment;
use AwardWallet\Schema\Parser\Common\Itinerary;
use AwardWallet\Schema\Parser\Common\TransferSegment;
use AwardWallet\Schema\Parser\Component\InvalidDataException;
use AwardWallet\Schema\Parser\Component\Master;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class ItineraryHelper
{

    /**
     * @var Connection $connection
     */
    private $connection;

    /**
     * @var LoggerInterface $logger
     */
    private $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function extractFlightsNotAirCode(Master $master)
    {
        $itineraries = $master->getItineraries();
        foreach ($itineraries as $itinerary) {
            if ($itinerary->getType() === 'flight') {
                $delSegments = [];
                /** @var \AwardWallet\Schema\Parser\Common\Flight $flight */
                $flight = $itinerary;
                foreach ($flight->getSegments() as $segment) {
                    $rail = $bus = null;
                    foreach ([$segment->getDepCode(), $segment->getArrCode()] as $code) {
                        if (!empty($code)) {
                            $qAir = $this->connection->executeQuery('select 1 from AirCode where AirCode = ?', [$code],
                                [\PDO::PARAM_STR]);
                            $qStation = $this->connection->executeQuery('select StationType from StationCode where StationCode  = ?',
                                [$code], [\PDO::PARAM_STR]);
                            if ($qAir->rowCount() == 0 && $qStation->rowCount()) {
                                $type = $qStation->fetchColumn(0);
                                switch ($type) {
                                    case 'rail':
                                        $rail = true;//$segment->getId();
                                        break;
                                    case 'bus':
                                        $bus = true;//$segment->getId();
                                        break;
                                }
                            }
                        }
                    }
                    if ($rail) {
                        $this->logger->notice(sprintf('converted flight segment `%s` -> `%s` to rail', $segment->getDepCode(), $segment->getArrCode()), ['component' => 'ItineraryHelper']);
                        $this->createItineraryFromFlightSegment($master, $segment, $flight, 'rail');//$flight->getId()
                        $delSegments[] = $segment;

                    } elseif ($bus) {
                        $this->logger->notice(sprintf('converted flight segment `%s` -> `%s` to bus', $segment->getDepCode(), $segment->getArrCode()), ['component' => 'ItineraryHelper']);
                        $this->createItineraryFromFlightSegment($master, $segment, $flight, 'bus');
                        $delSegments[] = $segment;
                    }
                }
                foreach ($delSegments as $segment) {
                    $flight->removeSegment($segment);
                }
                if (count($delSegments) > 0 && count($flight->getSegments()) === 0) {
                    $master->removeItinerary($flight);
                }
            }
        }
    }

    private function createItineraryFromFlightSegment(Master $master, FlightSegment $segment, Itinerary $it, $type)
    {
        switch ($type) {
            case 'rail':
                $r = $master->add()->train();
                break;
            case 'bus':
                $r = $master->add()->bus();
                break;
            default:
                return null;
        }
        $confNo = $segment->getConfirmation();
        if (isset($confNo)) {
            $r->general()
                ->confirmation($confNo);
        } else {
            $confNo = $it->getConfirmationNumbers();
            if (count($confNo) > 0) {
                foreach ($confNo as $cn) {
                    if (preg_match("#^[\w\-\/]+$#", $cn[0])) {
                        $r->general()
                            ->confirmation($cn[0]);
                    }
                }
            } elseif ($it->getNoConfirmationNumber() === true) {
                $r->general()
                    ->noConfirmation();
            }
        }
        if ($segment->getStatus()) {
            $r->setStatus($segment->getStatus());

        } elseif ($it->getStatus()) {
            $r->setStatus($it->getStatus());
        }
        if ($segment->getCancelled() === true) {
            $r->general()->cancelled();
        } elseif ($it->getCancelled() === true) {
            $r->general()->cancelled();
        }
        if (count($it->getTravellers()) > 0) {
            foreach ($it->getTravellers() as $traveller) {
                $r->general()->traveller($traveller[0], $traveller[1]);
            }
        }
        if ($it->getProviderKeyword()){
            $r->setProviderKeyword($it->getProviderKeyword());
        }
        if ($it->getProviderCode()){
            $r->setProviderCode($it->getProviderCode());
        }
        if (count($it->getProviderPhones())>0){
            foreach ($it->getProviderPhones() as $phones) {
                $r->program()->phone($phones[0], $phones[1]);
            }
        }
        if ($it->getReservationDate()){
            $r->setReservationDate($it->getReservationDate());
        }

        $s = $r->addSegment();
        if ($segment->getDepCode()) {
            $s->setDepCode($segment->getDepCode());
        }
        if ($segment->getArrCode()) {
            $s->setArrCode($segment->getArrCode());
        }
        if ($segment->getDepName()) {
            $s->setDepName($segment->getDepName());
        }
        if ($segment->getArrName()) {
            $s->setArrName($segment->getArrName());
        }
        if ($segment->getDepAddress()) {
            $s->setDepAddress($segment->getDepAddress());
        }
        if ($segment->getArrAddress()) {
            $s->setArrAddress($segment->getArrAddress());
        }

        if ($segment->getOperatedBy()) {
            switch ($type) {
                case 'rail':
                    $s->setServiceName($segment->getOperatedBy());
                    break;
            }
        } elseif ($segment->getAirlineName())
            switch ($type) {
                case 'rail':
                    $s->setServiceName($segment->getAirlineName());
                    break;
            }

        if ($segment->getNoFlightNumber() === true) {
            $s->setNoNumber(true);
        }
        if ($segment->getFlightNumber()) {
            $s->setNumber($segment->getFlightNumber());
        }
        if ($segment->getNoDepDate() === true) {
            $s->setNoDepDate(true);
        }
        if ($segment->getDepDate()) {
            $s->setDepDate($segment->getDepDate());
        }
        if ($segment->getNoArrDate() === true) {
            $s->setNoArrDate(true);
        }
        if ($segment->getArrDate()) {
            $s->setArrDate($segment->getArrDate());
        }
        if ($segment->getDepCode()) {
            $s->setArrCode($segment->getDepCode());
        }
        if ($segment->getArrCode()) {
            $s->setArrCode($segment->getArrCode());
        }
        if ($segment->getCabin()) {
            $s->setCabin($segment->getCabin());
        }
        if ($segment->getBookingCode()) {
            $s->setBookingCode($segment->getBookingCode());
        }
        if ($segment->getMeals()) {
            $s->setMeals($segment->getMeals());
        }
        if ($segment->getMiles()) {
            $s->setMiles($segment->getMiles());
        }
        if ($segment->getSeats()) {
            $s->setSeats($segment->getSeats());
        }
        if ($segment->getSmoking()) {
            $s->setSmoking($segment->getSmoking());
        }
        if ($segment->getDuration()) {
            $s->setDuration($segment->getDuration());
        }
        if ($segment->getAircraft()) {
            switch ($type) {
                case 'rail':
                    $s->setTrainType($segment->getAircraft());
                    break;
                case 'bus':
                    $s->setBusType($segment->getAircraft());
                    break;
            }
        }

        try{
            $r->validate(false);
        } catch (InvalidDataException $e){
            $master->removeItinerary($r);
        };
        if ($r->getValid() !== true) {
            $master->removeItinerary($r);
        }
    }

    public function calculateTransferDate(TransferSegment $segment, Extra $extra): void
    {
        if (!empty($segment->getDepFlightSegment()) && empty($segment->getDepDate())) {
            $flightSegment = $segment->getDepFlightSegment();
            $type = 'dep';
        }
        elseif (!empty($segment->getArrFlightSegment()) && empty($segment->getArrDate())) {
            $flightSegment = $segment->getArrFlightSegment();
            $type = 'arr';
        }
        else {
            return;
        }
        $date = $type === 'dep' ? $flightSegment->getArrDate() : $flightSegment->getDepDate();
        if (empty($date)) {
            return;
        }
        if (!empty($segment->getFlightDateCorrection())) {
            $newDate = strtotime($segment->getFlightDateCorrection(), $date);
        }
        else {
            if ($type === 'arr') {
                $international = true;
                $geoDep = $extra->data->getGeo($flightSegment->getDepCode() ?? $flightSegment->getDepName());
                $geoArr = $extra->data->getGeo($flightSegment->getArrCode() ?? $flightSegment->getArrName());
                if ($geoDep && $geoArr) {
                    $international = $geoDep->countryCode !== $geoArr->countryCode;
                }
                $newDate = strtotime($international ? '-3 hours' : '-2 hours', $date);
            }
            else {
                $newDate = strtotime('+30 minutes', $date);
            }
        }
        if (isset($newDate) && false !== $newDate && $newDate > strtotime('2000-01-01')) {
            $this->logger->info('TransferDateCorrection transfer ' . $type . ' date corrected to ' . date('Y-m-d H:i', $newDate));
            if ($type === 'dep') {
                if (!empty($segment->getArrDate()) && $segment->getArrDate() < $newDate) {
                    $segment->setDepDate($date);
                    $this->logger->info('TransferDateCorrection error dep' . $segment->getId());
                }
                else {
                    $segment->setDepDate($newDate);
                }
            }
            else {
                if (!empty($segment->getDepDate()) && $segment->getDepDate() > $newDate) {
                    $segment->setArrDate($date);
                    $this->logger->info('TransferDateCorrection error arr' . $segment->getId());
                }
                else {
                    $segment->setArrDate($newDate);
                }
            }
        }
    }

}