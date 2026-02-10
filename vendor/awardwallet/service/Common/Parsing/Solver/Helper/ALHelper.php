<?php


namespace AwardWallet\Common\Parsing\Solver\Helper;


use AwardWallet\Common\AirLabs\Communicator;
use AwardWallet\Common\AirLabs\Context;
use AwardWallet\Common\AirLabs\FlightSegmentData;
use AwardWallet\Common\AirLabs\Route;
use AwardWallet\Common\AirLabs\RoutesRequest;
use AwardWallet\Common\AirLabs\RoutesResponse;
use Psr\Log\LoggerInterface;

class ALHelper
{

    /** @var Communicator $communicator */
    private $communicator;
    /** @var LoggerInterface */
    private $logger;

    private $restrictedPartners;

    public function __construct(Communicator $communicator, LoggerInterface $logger)
    {
        $this->communicator = $communicator;
        $this->logger = $logger;
    }

    /**
     * @param null|string $carrier
     * @param null|string $fn
     * @param null|string $depCode
     * @param null|string $arrCode
     * @param int|null $depDate
     * @param int|null $arrDate
     * @param int|null $depDay
     * @param int|null $arrDay
     * @param string|null $extra
     * @param $dataKey
     * @return FlightSegmentData|null
     */
    public function process(
        Context $context,
        ?string $carrier,
        ?string $fn,
        ?string $depCode,
        ?string $arrCode,
        ?int $depDate,
        ?int $arrDate,
        ?string $fnIata
    ): ?FlightSegmentData {
        $dateMaxOffset = 60 * 60;

        if (!empty($carrier) && !empty($fn) && !empty($depCode) && !empty($arrCode) && !empty($depDate) && !empty($arrDate)) {
            $request = new RoutesRequest($depCode, $arrCode, $carrier, $fn);
            $routes = $this->communicator->getRoutes($request);
            if (!$this->communicator->getWasLastCallFromCache()) {
                $context->setCallWasMade(true);
            }
            if (isset($routes) && !empty($routes->getRoutes())) {
                $routes = $routes->getRoutes() ?? [];
                $aircrafts = [];
                $hasEmpty = false;
                $filteredRoutes = [];
                $matchTimeRoutes = [];
                $matchTimeAircrafts = [];
//                $memRoute = null;
                foreach ($routes as $route) {
                    if (!$route->getAircraftIcao()) {
                        $hasEmpty = true;
                        continue;
                    }
                    $aircrafts[] = $route->getAircraftIcao();
                    $filteredRoutes[] = $route;
                    // complete match
                    $weekDay = strtolower(date('D', $depDate));
                    if ($route->getDepTime() === date("H:i", $depDate)) {
                        if (in_array($weekDay, $route->getDays())){
                            $this->logger->info('finded from routes (complete match)', ['component' => 'ALHelper']);
                            $context->setTypeGuessing(0);
                            return FlightSegmentData::fromRoute($route, $depDate, $arrDate);
                        }
                        if (empty($route->getDays())) {
                            $matchTimeRoutes[] = $route;
                            $matchTimeAircrafts[] = $route->getAircraftIcao();
                        }
                    }
//                    $memRoute = $route;
                }
                if (!empty($matchTimeAircrafts)) {
                    $matchTimeAircrafts = array_unique($matchTimeAircrafts);
                    if (count($matchTimeAircrafts) === 1) {
                        $this->logger->info('finded from routes (time match)', ['component' => 'ALHelper', 'foundCnt' => count($matchTimeRoutes)]);
                        $context->setTypeGuessing(1);
                        return FlightSegmentData::fromRoute($matchTimeRoutes[0], $depDate, $arrDate);
                    }
                }

                // partially match
                /*$sameTimeRoutes = [];
                $sameTimeAircrafts = [];
                foreach ($filteredRoutes as $route) {
                    $weekDay = strtolower(date('D', $depDate));
                    if ($route->getDepTime() === date("H:i", $depDate)) {
                        $sameTimeRoutes[] = $route;
                        $sameTimeAircrafts[]=$route->getAircraftIcao();
                    }
                }
                if (!empty($sameTimeRoutes)) {
                    $sameTimeAircrafts = array_values(array_unique($sameTimeAircrafts));
                    if (count($sameTimeAircrafts) === 1) {
                        $this->logger->info('finded from routes (partially match)', ['component' => 'ALHelper']);
                        $context->setTypeGuessing(1);
                        return FlightSegmentData::fromRoute($sameTimeRoutes[0], $depDate, $arrDate);
                    }
                }
                // always one aircraft
                $aircrafts = array_unique($aircrafts);
                if (!$hasEmpty && count($aircrafts) === 1 && isset($memRoute)) {
                    $this->logger->info('finded from routes (always one aircraft)', ['component' => 'ALHelper']);
                    $context->setTypeGuessing(2);
                    return FlightSegmentData::fromRoute($memRoute, $depDate, $arrDate);
                }*/
            }
        }
        if (!empty($fnIata)) {
            $flightInfo = $this->communicator->getFlight($fnIata);
            if (isset($flightInfo) && !empty($flightInfo->getFlightInfo())) {
                $this->logger->info('finded from flight', ['component' => 'ALHelper', 'depDate' => $flightInfo->getFlightInfo()->getDepTime()]);
                return FlightSegmentData::fromFlight($flightInfo->getFlightInfo());
            }
        }
        return null;
    }

}