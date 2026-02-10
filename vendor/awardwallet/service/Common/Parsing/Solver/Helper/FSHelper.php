<?php


namespace AwardWallet\Common\Parsing\Solver\Helper;


use AwardWallet\Common\FlightStats\Communicator;
use AwardWallet\Common\FlightStats\Context;
use AwardWallet\Common\FlightStats\FlightStatus;
use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use Psr\Log\LoggerInterface;

class FSHelper
{

    /** @var Communicator $communicator */
    private $communicator;
    /** @var LoggerInterface  */
    private $logger;
    /** @var bool $enabled */
    private $enabled;

    private $restrictedPartners;

    public function __construct(Communicator $communicator, LoggerInterface $logger, array $restrictedPartners, bool $disabled)
    {
        $this->communicator = $communicator;
        $this->logger = $logger;
        $this->enabled = !$disabled;
        $this->restrictedPartners = $restrictedPartners;
    }

    public function isFsEnabled(Extra $extra): bool
    {
        return $this->enabled && !in_array($extra->context->partnerLogin, $this->restrictedPartners);
    }

    /**
     * @param null|string $carrier
     * @param null|string $fn
     * @param null|string $depCode
     * @param null|string $arrCode
     * @param int|null $depDate
     * @param int|null $arrDate
     * @param null|string $partnerLogin
     * @param int|null $depDay
     * @param int|null $arrDay
     * @param null|string $operatingCarrier
     * @param null|string $operatingFn
     * @return Context
     */
    public function getContext(?string $carrier, ?string $fn,
        ?string $depCode, ?string $arrCode,
        ?int $depDate, ?int $arrDate,
        ?string $partnerLogin,
        ?int $depDay, ?int $arrDay, ?string $operatingCarrier = null, ?string $operatingFn = null): Context
    {
        //don't work with ...Day if one or both dates are known !important
        if ($depDate || $arrDate)
            $depDay = $arrDay = null;

        $recent = null;
        foreach([$depDate, $arrDate, $depDay, $arrDay] as $val)
            if ($val) {
                $recent = $val > strtotime('-12 hours');
                break;
            }
        if (!isset($recent))
            return new Context([],'', $partnerLogin, false);
        $reasons = $matches = [];
        $method = '';
        if (($depDate || $depDay) && $carrier && $fn && (!$depCode || !$arrCode || !$arrDate || !$depDate)) {
            $params = [
                'depCode' => $depCode,
                'arrCode' => $arrCode,
                'arrDate' => $arrDate,
            ];
            if (!$depDate) {
                $params['depDate'] = $depDate;
            }
            $method = $recent ? Context::METHOD_SCH_BY_FLIGHT : Context::METHOD_HISTORICAL_BY_FLIGHT;
        }
        elseif (($arrDate || $arrDay) && $carrier && $fn && (!$depCode || !$arrCode || !$depDate || !$arrDate)) {
            $params = [
                'depCode' => $depCode,
                'depDate' => $depDate,
                'arrCode' => $arrCode,
            ];
            if (!$arrDate) {
                $params['arrDate'] = $arrDate;
            }
            $method = $recent ? Context::METHOD_SCH_BY_FLIGHT : Context::METHOD_HISTORICAL_BY_FLIGHT;
        }
        elseif ($depCode && $arrCode && ($depDate || $depDay) && ($carrier || $fn) && (!$carrier || !$fn || !$arrDate || !$depDate)) {
            $params = [
                'arrDate' => $arrDate,
                'carrier' => $carrier,
                'flightNumber' => $fn,
            ];
            if (!$depDate) {
                $params['depDate'] = $depDate;
            }
            $method = $recent ? Context::METHOD_SCH_BY_ROUTE : Context::METHOD_HISTORICAL_BY_ROUTE;
        }
        elseif ($depCode && $arrCode && ($arrDate || $arrDay) && ($carrier || $fn) && (!$carrier || !$fn || !$depDate || !$arrDate)) {
            $params = [
                'depDate' => $depDate,
                'carrier' => $carrier,
                'flightNumber' => $fn,
            ];
            if (!$arrDate) {
                $params['arrDate'] = $arrDate;
            }
            $method = $recent ? Context::METHOD_SCH_BY_ROUTE : Context::METHOD_HISTORICAL_BY_ROUTE;
        }
        elseif ($depCode && $depDate && ($carrier || $fn) && (!$carrier || !$fn || !$arrDate || !$arrCode)) {
            $params = [
                'arrCode' => $arrCode,
                'arrDate' => $arrDate,
                'carrier' => $carrier,
                'flightNumber' => $fn,
            ];
            $method = $recent ? Context::METHOD_SCH_BY_AIRPORT : Context::METHOD_HISTORICAL_BY_AIRPORT;
        }
        elseif ($arrCode && $arrDate && ($carrier || $fn) && (!$carrier || !$fn || !$depDate || !$depCode)) {
            $params = [
                'depCode' => $depCode,
                'depDate' => $depDate,
                'carrier' => $carrier,
                'flightNumber' => $fn,
            ];
            $method = $recent ? Context::METHOD_SCH_BY_AIRPORT : Context::METHOD_HISTORICAL_BY_AIRPORT;
        }
        if ($this->needOperatingInfo($partnerLogin) && !$method && (!$operatingCarrier || !$operatingFn) && $carrier && $fn && $depDate && $recent) {
            $method = Context::METHOD_SCH_BY_FLIGHT;
            $params = null;
            $reasons = ['operatingCarrier'];
        }
        if (isset($params)) {
            $reasons = array_keys(array_filter($params, function($i){return empty($i);}));
        }
        $eligible = !empty($reasons) && !empty($method);
        return new Context($reasons, $method, $partnerLogin, $eligible);
    }

    private function needOperatingInfo(string $partnerLogin): bool
    {
        return in_array($partnerLogin, [
            'awardwallet',
            'benjacobson',
        ]);
    }

    /**
     * @param Context $context
     * @param null|string $carrier
     * @param null|string $fn
     * @param null|string $depCode
     * @param null|string $arrCode
     * @param int|null $depDate
     * @param int|null $arrDate
     * @param int|null $depDay
     * @param int|null $arrDay
     * @param Extra $extra
     * @param $dataKey
     * @return FlightSegmentData|null
     */
    public function process(
        Context $context,
        ?string $carrier,
        ?string $fn,
        ?string $depCode,
        ?string $arrCode,
        ?int $depDate, ?int $arrDate,
        ?int $depDay, ?int $arrDay,
        Extra $extra, $dataKey): ?FlightSegmentData
    {
        if (!$context->getEligible())
            throw new \LogicException('called process on context with eligible=false');
        //don't work with ...Day if one or both dates are known !important
        if ($depDate || $arrDate)
            $depDay = $arrDay = null;
        $replacedDates = false;
        if (!$depDate && !$arrDate && ($depDay || $arrDay)){
            $depDate = $depDay;
            $arrDate = $arrDay;
            $replacedDates = true;
        }
        if (!empty($fn))
            $fn = ltrim($fn, '0');
        switch($context->getMethod()) {
            case Context::METHOD_SCH_BY_FLIGHT:
                if ($depDate)
                    $schList = $this->communicator->getScheduleByCarrierFNAndDepartureDate($carrier, $fn, date('Y-m-d H:i:s', $depDate), $context);
                elseif ($arrDate)
                    $schList = $this->communicator->getScheduleByCarrierFNAndArrivalDate($carrier, $fn, date('Y-m-d H:i:s', $arrDate), $context);
                break;
            case Context::METHOD_SCH_BY_ROUTE:
                if ($depDate)
                    $schList = $this->communicator->getScheduleByRouteAndDate($depCode, $arrCode, date('Y-m-d H:i:s', $depDate), $context);
                elseif ($arrDate)
                    $schList = $this->communicator->getScheduleByRouteAndArrivalDate($depCode, $arrCode, date('Y-m-d H:i:s', $arrDate), $context);
                break;
            case Context::METHOD_SCH_BY_AIRPORT:
                if ($depDate)
                    $schList = $this->communicator->getScheduleByDeparture($depCode, date('Y-m-d H:i:s', $depDate), $context);
                elseif ($arrDate)
                    $schList = $this->communicator->getScheduleByArrival($arrCode, date('Y-m-d H:i:s', $arrDate), $context);
                break;
            case Context::METHOD_HISTORICAL_BY_FLIGHT:
                if ($depDate)
                    $statusList = $this->communicator->getStatusByCarrierFNAndDepartureDate($carrier, $fn, $depCode, date('Y-m-d H:i:s', $depDate), $context);
                elseif ($arrDate)
                    $statusList = $this->communicator->getStatusByCarrierFNAndArrivalDate($carrier, $fn, $arrCode, date('Y-m-d H:i:s', $arrDate), $context);
                break;
            case Context::METHOD_HISTORICAL_BY_ROUTE:
                if ($depDate)
                    $statusList = $this->communicator->getStatusByRouteAndDepartureDate($depCode, $arrCode, date('Y-m-d H:i:s', $depDate), $context);
                elseif ($arrDate)
                    $statusList = $this->communicator->getStatusByRouteAndArrivalDate($depCode, $arrCode, date('Y-m-d H:i:s', $arrDate), $context);
                break;
            case Context::METHOD_HISTORICAL_BY_AIRPORT:
                if ($depDate)
                    $statusList = $this->communicator->getStatusByDeparture($depCode, date('Y-m-d H:i:s', $depDate), $context);
                elseif ($arrDate)
                    $statusList = $this->communicator->getStatusByArrival($arrCode, date('Y-m-d H:i:s', $arrDate), $context);
                break;
            default:
                $ignore = true;
        }
        if (!isset($ignore) && !$this->communicator->getWasLastCallFromCache())
            $context->setCallWasMade(true);
        //get back values if they was replaced
        if ($replacedDates)
            $depDate = $arrDate = null;
        $dateMaxOffset = 60 * 60;
        $params = [
            'carrier' => $carrier,
            'fn' => $fn,
            'depCode' => $depCode,
            'arrCode' => $arrCode,
            'depDate' => $depDate,
            'arrDate' => $arrDate,
            'depDay' => $depDay,
            'arrDay' => $arrDay,
        ];
        if (isset($schList)) {
            foreach($schList->getScheduledFlights() as $item) {
                if ((empty($params['carrier']) || strcasecmp($params['carrier'], $item->getCarrier()->getIata()) === 0)
                    && (empty($params['fn']) || strcasecmp($params['fn'], ltrim($item->getFlightNumber(), '0')) === 0)
                    && (empty($params['depCode']) || !empty($item->getDepartureAirport()) && strcasecmp($params['depCode'], $item->getDepartureAirport()->getIata()) === 0)
                    && (empty($params['arrCode']) || !empty($item->getArrivalAirport()) && strcasecmp($params['arrCode'], $item->getArrivalAirport()->getIata()) === 0)
                    && $this->matchDates($params['depDate'], $params['arrDate'], strtotime($item->getDepartureTime()), strtotime($item->getArrivalTime()), $dateMaxOffset)
                    && (empty($params['depDay']) || $this->checkDatesSameDay($params['depDay'], strtotime($item->getDepartureTime())))
                    && (empty($params['arrDay']) || $this->checkDatesSameDay($params['arrDay'], strtotime($item->getArrivalTime())))) {
                    if (!isset($sch))
                        $sch = $item;
                    else {
                        $this->logger->info('multiple matching flights in schedules', ['component' => 'FSHelper']);
                        return null;
                    }
                }
            }
            if (isset($sch)) {
                $this->logger->info('found matching flight in schedules', ['component' => 'FSHelper']);
                $extra->solverData->addSchedule($dataKey, $sch);
                return FlightSegmentData::fromSchedule($sch);
            }
            else
                $this->logger->info('no matching flight in schedules', ['component' => 'FSHelper']);
        }
        if (isset($statusList)) {
            foreach($statusList->getFlightStatuses() as $item) {
                if ($this->matchCarrierInStatus($item, $params)
                    && (empty($params['depCode']) || strcasecmp($params['depCode'], $item->getDepartureAirport()->getIata()) === 0)
                    && (empty($params['arrCode']) || strcasecmp($params['arrCode'], $item->getArrivalAirport()->getIata()) === 0)
                    && $this->matchDates($params['depDate'], $params['arrDate'], strtotime($item->getDepartureDate()->getDateLocal()), strtotime($item->getArrivalDate()->getDateLocal()), $dateMaxOffset)
                    && (empty($params['depDay']) || $this->checkDatesSameDay($params['depDay'], strtotime($item->getDepartureDate()->getDateLocal())))
                    && (empty($params['arrDay']) || $this->checkDatesSameDay($params['arrDay'], strtotime($item->getArrivalDate()->getDateLocal())))) {
                    if (!isset($status))
                        $status = $item;
                    else {
                        $this->logger->info('multiple matching flights in statuses', ['component' => 'FSHelper']);
                        return null;
                    }
                }
            }
            if (isset($status)) {
                $this->logger->info('found matching flight in statuses', ['component' => 'FSHelper']);
                return FlightSegmentData::fromStatus($status, $carrier, $fn);
            }
            else
                $this->logger->info('no matching flight in statuses', ['component' => 'FSHelper']);
        }
        return null;
    }

    private function matchDates($depDateParam, $arrDateParam, $depDateApi, $arrDateApi, $maxOffset)
    {
        if (!empty($depDateParam)) {
            return $this->checkTimeDifference($depDateParam, $depDateApi, $maxOffset);
        }
        elseif (!empty($arrDateParam)) {
            return $this->checkTimeDifference($arrDateParam, $arrDateApi, $maxOffset);
        }
        else
            return true;
    }

    private function matchCarrierInStatus(FlightStatus $status, array $params)
    {
        if ((empty($params['fn']) || strcasecmp($params['fn'], ltrim($status->getFlightNumber(), '0')) === 0)
            && (empty($params['carrier']) || in_array($params['carrier'], [$status->getCarrier()->getIata(), $status->getPrimaryCarrier()->getIata()])))
            return true;
        foreach($status->getCodeshares() as $cs) {
            if ((empty($params['fn']) || strcasecmp($params['fn'], ltrim($cs->getFlightNumber(), '0')) === 0)
                && (empty($params['carrier']) || strcasecmp($params['carrier'], $cs->getCarrier()->getIata()) === 0))
                return true;
        }
        return false;
    }

    private function checkTimeDifference($a, $b, $offset)
    {
        return abs($a - $b) <= $offset;
    }

    private function checkDatesSameDay($a, $b)
    {
        return (date('Y-m-d', $a) === date('Y-m-d', $b));
    }


}