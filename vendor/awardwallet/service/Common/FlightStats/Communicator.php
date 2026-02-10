<?php

namespace AwardWallet\Common\FlightStats;

use HttpDriverInterface;
use HttpDriverRequest;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Created by PhpStorm.
 * User: puzakov
 * Date: 16/03/2017
 * Time: 16:52
 */
class Communicator
{
    const BASE_URL = 'https://api.flightstats.com/flex/';
    const CACHE_PREFIX = 'flight_stats_communicator_';
    const CACHE_TTL = 60 * 60 * 24 * 7;

    const REQUEST_THROTTLER_KEY = 'flight_stats_throttler_';
    const REQUEST_LIMIT = 100;
    const REQUEST_LIMIT_SECONDS = 10*60;

    /** @var HttpDriverInterface */
    private $httpDriver;
    /** @var LoggerInterface */
    private $logger;
    /** @var SerializerInterface */
    private $serializer;
    /** @var \Memcached */
    private $memcached;
    /** @var array */
    private $appCredentials;
    /** @var EventDispatcherInterface */
    private $eventDispatcher;
    /** @var Cache */
    private $cache;
    /** @var boolean */
    private $wasLastCallFromCache;
    private $defaultContext = [
        "components" => "FlightStatsCommunicator"
    ];

    /**
     * Communicator constructor.
     * @param HttpDriverInterface $httpDriver
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     * @param \Memcached $memcached
     * @param EventDispatcherInterface $eventDispatcher
     * @param Cache $cache
     * @param string $applicationId - appId or array ['default' => ['appId' => 'xxidxx', 'appKey' => 'xxxkeyxxx'], 'parnter1' => ['appId' => 'xxid2xx', 'appKey' => xxappKey2xx']]
     * @param string $applicationKey - appKey or null if array of credentials was passed to $applicationId
     */
    public function __construct(HttpDriverInterface $httpDriver, LoggerInterface $logger, SerializerInterface $serializer, \Memcached $memcached, EventDispatcherInterface $eventDispatcher, Cache $cache, $applicationId, $applicationKey )
    {
        $this->httpDriver = $httpDriver;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->memcached = $memcached;
        $this->eventDispatcher = $eventDispatcher;
        $this->cache = $cache;
        if (is_array($applicationId))
            $this->appCredentials = $applicationId;
        else
            $this->appCredentials = ['default' => ['appId' => $applicationId, 'appKey' => $applicationKey]];
    }

    /**
     * @param string $from
     * @param string $to
     * @param string $date
     * @param Context|null $context
     * @return Schedule|null
     */
    public function getScheduleByRouteAndDate($from, $to, $date, Context $context = null)
    {
        try {
            if (empty($json = $this->cache->getSchedule($from, $to, new \DateTime(date("Y/m/d", strtotime($date))), null, null, null, true))) {
                $this->verifyParams(get_defined_vars());
                if(strlen($from) != 3 || strlen($to) != 3)
                    throw new CommunicatorCallException("invalid airport codes", ["from" => $from, "to" => $to]);

                $path = sprintf("schedules/rest/v1/json/from/%s/to/%s/departing/%s", urlencode($from), urlencode($to), date("Y/m/d", strtotime($date)));
                $json = $this->call($path, [], $context ?? Context::getDefault(Context::METHOD_SCH_BY_ROUTE));
                $this->cache->addSchedule($from, $to, new \DateTime(date("Y/m/d", strtotime($date))), null, null, null, $json);
            }
            else {
                $this->wasLastCallFromCache = true;
            }
            return $this->deserializeSchedule($json);
        }
        catch(CommunicatorCallException $e){
            $this->logException($e);
            return null;
        }
    }

	/**
	 * @param string $from
	 * @param string $to
	 * @param string $date
     * @param Context|null $context
	 * @return Schedule|null
	 */
	public function getScheduleByRouteAndArrivalDate($from, $to, $date, Context $context = null)
	{
		try {
            if (empty($json = $this->cache->getSchedule($from, $to, null, new \DateTime(date("Y/m/d", strtotime($date))),null, null, true))) {
                $this->verifyParams(get_defined_vars());
                if(strlen($from) != 3 || strlen($to) != 3)
                    throw new CommunicatorCallException("invalid airport codes", ["from" => $from, "to" => $to]);

                $path = sprintf("schedules/rest/v1/json/from/%s/to/%s/arriving/%s", urlencode($from), urlencode($to), date("Y/m/d", strtotime($date)));
                $json = $this->call($path, [], $context ?? Context::getDefault(Context::METHOD_SCH_BY_ROUTE));
                $this->cache->addSchedule($from, $to, null, new \DateTime(date("Y/m/d", strtotime($date))),null, null, $json);
            }
            else {
                $this->wasLastCallFromCache = true;
            }
            return $this->deserializeSchedule($json);

        }
		catch(CommunicatorCallException $e){
			$this->logException($e);
			return null;
		}
	}

    /**
     * @param string $carrier
     * @param string $flightNumber
     * @param string $departureDate
     * @param Context|null $context
     * @return Schedule|null
     */
    public function getScheduleByCarrierFNAndDepartureDate($carrier, $flightNumber, $departureDate, Context $context = null)
    {
        try {
            if (empty($json = $this->cache->getSchedule(null, null, new \DateTime(date('Y/m/d', strtotime($departureDate))), null, $flightNumber, $carrier, true))) {
                $this->verifyParams(get_defined_vars());
                if(!$this->validateAirlineCode($carrier))
                    throw new CommunicatorCallException("Invalid carrier $carrier");
                if(!$this->validateFlightNumber($flightNumber))
                    throw new CommunicatorCallException("Non-numeric flight number $flightNumber");

                //Обрежем ведущие нули
                $flightNumber = (string) (integer) $flightNumber;
                $path = sprintf("schedules/rest/v1/json/flight/%s/%s/departing/%s", $carrier, $flightNumber, date('Y/m/d', strtotime($departureDate)));
                $json = $this->call($path, [], $context ?? Context::getDefault(Context::METHOD_SCH_BY_FLIGHT));
                $this->cache->addSchedule(null, null, new \DateTime(date('Y/m/d', strtotime($departureDate))), null, $flightNumber, $carrier, $json);
            }
            else {
                $this->wasLastCallFromCache = true;
            }
            return $this->deserializeSchedule($json);
        }
        catch(CommunicatorCallException $e){
            $this->logException($e);
            return null;
        }
    }

	/**
	 * @param string $carrier
	 * @param string $flightNumber
	 * @param string $arrivalDate
     * @param Context|null $context
	 * @return Schedule|null
	 */
	public function getScheduleByCarrierFNAndArrivalDate($carrier, $flightNumber, $arrivalDate, Context $context = null)
	{
		try {
            if (empty($json = $this->cache->getSchedule(null, null, null, new \DateTime(date('Y/m/d', strtotime($arrivalDate))), $flightNumber, $carrier, true))) {
                $this->verifyParams(get_defined_vars());
                if(!$this->validateAirlineCode($carrier))
                    throw new CommunicatorCallException("Invalid carrier $carrier");
                if(!$this->validateFlightNumber($flightNumber))
                    throw new CommunicatorCallException("Non-numeric flight number $flightNumber");

                $flightNumber = (string) (integer) $flightNumber;
                $path = sprintf("schedules/rest/v1/json/flight/%s/%s/arriving/%s", $carrier, $flightNumber, date('Y/m/d', strtotime($arrivalDate)));
                $json = $this->call($path, [], $context ?? Context::getDefault(Context::METHOD_SCH_BY_FLIGHT));
                $this->cache->addSchedule(null, null, null, new \DateTime(date('Y/m/d', strtotime($arrivalDate))), $flightNumber, $carrier, $json);
            }
            else {
                $this->wasLastCallFromCache = true;
            }
			return $this->deserializeSchedule($json);
		}
		catch(CommunicatorCallException $e){
			$this->logException($e);
			return null;
		}
	}

	/**
	 * @param string $departureCode
	 * @param string $departureDate
     * @param Context|null $context
	 * @return Schedule|null
	 */
    public function getScheduleByDeparture($departureCode, $departureDate, Context $context = null) {
    	try {
            if (empty($json = $this->cache->getSchedule($departureCode, null, new \DateTime(date('Y/m/d H:00', strtotime($departureDate))), null, null, null, true))) {
                $this->verifyParams(get_defined_vars());
                if (strlen($departureCode) !== 3)
                    throw new CommunicatorCallException('invalid airport code', ['from' => $departureCode]);
                $path = sprintf('schedules/rest/v1/json/from/%s/departing/%s', $departureCode, date('Y/m/d/H', strtotime($departureDate)));
                $json = $this->call($path, [], $context ?? Context::getDefault(Context::METHOD_SCH_BY_AIRPORT));
                $this->cache->addSchedule($departureCode, null, new \DateTime(date('Y/m/d H:00', strtotime($departureDate))), null, null, null, $json);
            }
            else {
                $this->wasLastCallFromCache = true;
            }
    		return $this->deserializeSchedule($json);
		}
		catch(CommunicatorCallException $e){
			$this->logException($e);
			return null;
		}
	}

	/**
	 * @param string $arrivalCode
	 * @param string $arrivalDate
     * @param Context|null $context
	 * @return Schedule|null
	 */
	public function getScheduleByArrival($arrivalCode, $arrivalDate, Context $context = null) {
		try {
            if (empty($json = $this->cache->getSchedule(null, $arrivalCode, null, new \DateTime(date('Y/m/d H:00', strtotime($arrivalDate))),null, null, true))) {
                $this->verifyParams(get_defined_vars());
                if (strlen($arrivalCode) !== 3)
                    throw new CommunicatorCallException('invalid airport code', ['to' => $arrivalCode]);
                $path = sprintf('schedules/rest/v1/json/to/%s/arriving/%s', $arrivalCode, date('Y/m/d/H', strtotime($arrivalDate)));
                $json = $this->call($path, [], $context ?? Context::getDefault(Context::METHOD_SCH_BY_AIRPORT));
                $this->cache->addSchedule(null, $arrivalCode, null, new \DateTime(date('Y/m/d H:00', strtotime($arrivalDate))),null, null, $json);
            }
            else {
                $this->wasLastCallFromCache = true;
            }
			return $this->deserializeSchedule($json);
		}
		catch(CommunicatorCallException $e){
			$this->logException($e);
			return null;
		}
	}

    /**
     * @param $carrier
     * @param $flightNumber
     * @param $departureCode
     * @param $departureDate
     * @param Context|null $context
     * @return HistoricalFlightStatus|null
     */
	public function getStatusByCarrierFNAndDepartureDate($carrier, $flightNumber, $departureCode, $departureDate, Context $context = null): ?HistoricalFlightStatus
    {
        try {
            if (empty($json = $this->cache->getHistorical($departureCode, null, new \DateTime(date('Y/m/d', strtotime($departureDate))), null, $flightNumber, $carrier, true))) {
                $this->validateParams([
                    'carrier' => $carrier,
                    'flightNumber' => $flightNumber,
                    'depDate' => $departureDate,
                ]);
                $path = sprintf('flightstatus/historical/rest/v3/json/flight/status/%s/%s/dep/%s', $carrier, $flightNumber, date('Y/m/d', strtotime($departureDate)));
                $query = ['utc' => 'false', 'codeType' => 'IATA'];
                if (!empty($departureCode)) {
                    $this->validateParams(['depCode' => $departureCode]);
                    $query['airport'] = $departureCode;
                }
                $json = $this->call($path, $query, $context ?? Context::getDefault(Context::METHOD_HISTORICAL_BY_FLIGHT));
                $this->cache->addHistorical($departureCode, null, new \DateTime(date('Y/m/d', strtotime($departureDate))), null, $flightNumber, $carrier, $json);
            }
            else {
                $this->wasLastCallFromCache = true;
            }
            return $this->deserializeHistorical($json);
        }
        catch(CommunicatorCallException $e) {
            $this->logException($e);
            return null;
        }
    }

    /**
     * @param $carrier
     * @param $flightNumber
     * @param $arrivalCode
     * @param $arrivalDate
     * @param Context|null $context
     * @return HistoricalFlightStatus|null
     */
    public function getStatusByCarrierFNAndArrivalDate($carrier, $flightNumber, $arrivalCode, $arrivalDate, Context $context = null): ?HistoricalFlightStatus
    {
        try {
            if (empty($json = $this->cache->getHistorical(null, $arrivalCode, null, new \DateTime(date('Y/m/d', strtotime($arrivalDate))), $flightNumber, $carrier, true))) {
                $this->validateParams([
                    'carrier' => $carrier,
                    'flightNumber' => $flightNumber,
                    'arrDate' => $arrivalDate,
                ]);
                $path = sprintf('flightstatus/historical/rest/v3/json/flight/status/%s/%s/arr/%s', $carrier, $flightNumber, date('Y/m/d', strtotime($arrivalDate)));
                $query = ['utc' => 'false', 'codeType' => 'IATA'];
                if (!empty($arrivalCode)) {
                    $this->validateParams(['arrCode' => $arrivalCode]);
                    $query['airport'] = $arrivalCode;
                }
                $json = $this->call($path, $query, $context ?? Context::getDefault(Context::METHOD_HISTORICAL_BY_FLIGHT));
                $this->cache->addHistorical(null, $arrivalCode, null, new \DateTime(date('Y/m/d', strtotime($arrivalDate))), $flightNumber, $carrier, $json);
            }
            else {
                $this->wasLastCallFromCache = true;
            }
            return $this->deserializeHistorical($json);
        }
        catch(CommunicatorCallException $e) {
            $this->logException($e);
            return null;
        }
    }

    /**
     * @param $departureCode
     * @param $departureDate
     * @param Context|null $context
     * @return HistoricalFlightStatus|null
     */
    public function getStatusByDeparture($departureCode, $departureDate, Context $context = null): ?HistoricalFlightStatus
    {
        try {
            if (empty($json = $this->cache->getHistorical($departureCode, null, new \DateTime(date('Y/m/d H:00', strtotime($departureDate))), null, null, null, true))) {
                $this->validateParams([
                    'depCode' => $departureCode,
                    'depDate' => $departureDate,
                ]);
                $path = sprintf('flightstatus/historical/rest/v3/json/airport/status/%s/dep/%s', $departureCode, date('Y/m/d/H', strtotime($departureDate)));
                $query = ['utc' => 'false', 'codeType' => 'IATA'];
                $json = $this->call($path, $query, $context ?? Context::getDefault(Context::METHOD_HISTORICAL_BY_AIRPORT));
                $this->cache->addHistorical($departureCode, null, new \DateTime(date('Y/m/d H:00', strtotime($departureDate))), null, null, null, $json);
            }
            else {
                $this->wasLastCallFromCache = true;
            }
            return $this->deserializeHistorical($json);
        }
        catch(CommunicatorCallException $e) {
            $this->logException($e);
            return null;
        }
    }

    /**
     * @param $arrivalCode
     * @param $arrivalDate
     * @param Context|null $context
     * @return HistoricalFlightStatus|null
     */
    public function getStatusByArrival($arrivalCode, $arrivalDate, Context $context = null): ?HistoricalFlightStatus
    {
        try {
            if (empty($json = $this->cache->getHistorical(null, $arrivalCode, null, new \DateTime(date('Y/m/d H:00', strtotime($arrivalDate))), null, null, true))) {
                $this->validateParams([
                    'arrCode' => $arrivalCode,
                    'arrDate' => $arrivalDate,
                ]);
                $path = sprintf('flightstatus/historical/rest/v3/json/airport/status/%s/arr/%s', $arrivalCode, date('Y/m/d/H', strtotime($arrivalDate)));
                $query = ['utc' => 'false', 'codeType' => 'IATA'];
                $json = $this->call($path, $query, $context ?? Context::getDefault(Context::METHOD_HISTORICAL_BY_AIRPORT));
                $this->cache->addHistorical(null, $arrivalCode, null, new \DateTime(date('Y/m/d H:00', strtotime($arrivalDate))), null, null, $json);
            }
            else {
                $this->wasLastCallFromCache = true;
            }
            return $this->deserializeHistorical($json);
        }
        catch(CommunicatorCallException $e) {
            $this->logException($e);
            return null;
        }
    }

    /**
     * @param $departureCode
     * @param $arrivalCode
     * @param $departureDate
     * @param Context|null $context
     * @return HistoricalFlightStatus|null
     */
    public function getStatusByRouteAndDepartureDate($departureCode, $arrivalCode, $departureDate, Context $context = null): ?HistoricalFlightStatus
    {
        try {
            if (empty($json = $this->cache->getHistorical($departureCode, $arrivalCode, new \DateTime(date('Y/m/d', strtotime($departureDate))), null, null, null, true))) {
                $this->validateParams([
                    'depCode' => $departureCode,
                    'arrCode' => $arrivalCode,
                    'depDate' => $departureDate,
                ]);
                $path = sprintf('flightstatus/historical/rest/v3/json/route/status/%s/%s/dep/%s', $departureCode, $arrivalCode, date('Y/m/d', strtotime($departureDate)));
                $query = ['utc' => 'false', 'codeType' => 'IATA'];
                $json = $this->call($path, $query, $context ?? Context::getDefault(Context::METHOD_HISTORICAL_BY_ROUTE));
                $this->cache->addHistorical($departureCode, $arrivalCode, new \DateTime(date('Y/m/d', strtotime($departureDate))), null, null, null, $json);
            }
            else {
                $this->wasLastCallFromCache = true;
            }
            return $this->deserializeHistorical($json);
        }
        catch(CommunicatorCallException $e) {
            $this->logException($e);
            return null;
        }
    }

    /**
     * @param $departureCode
     * @param $arrivalCode
     * @param $arrivalDate
     * @param Context|null $context
     * @return HistoricalFlightStatus|null
     */
    public function getStatusByRouteAndArrivalDate($departureCode, $arrivalCode, $arrivalDate, Context $context = null): ?HistoricalFlightStatus
    {
        try {
            if (empty($json = $this->cache->getHistorical($departureCode, $arrivalCode, null, new \DateTime(date('Y/m/d', strtotime($arrivalDate))), null, null, true))) {
                $this->validateParams([
                    'depCode' => $departureCode,
                    'arrCode' => $arrivalCode,
                    'arrDate' => $arrivalDate,
                ]);
                $path = sprintf('flightstatus/historical/rest/v3/json/route/status/%s/%s/arr/%s', $departureCode, $arrivalCode, date('Y/m/d', strtotime($arrivalDate)));
                $query = ['utc' => 'false', 'codeType' => 'IATA'];
                $json = $this->call($path, $query, $context ?? Context::getDefault(Context::METHOD_HISTORICAL_BY_ROUTE));
                $this->cache->addHistorical($departureCode, $arrivalCode, null, new \DateTime(date('Y/m/d', strtotime($arrivalDate))), null, null, $json);
            }
            else {
                $this->wasLastCallFromCache = true;
            }
            return $this->deserializeHistorical($json);
        }
        catch(CommunicatorCallException $e) {
            $this->logException($e);
            return null;
        }
    }

    private function verifyParams(array $args)
    {
        foreach ($args as $key => $val) {
            if ('context' === $key || 'json' === $key)
                continue;
            if (empty($val) || trim($val) == '') {
                throw new CommunicatorCallException("empty param for getScheduleByRouteAndDate: $key");
            }
            if(preg_match('#date$#ims', $key)){
                if (false === strtotime($val)) {
                    throw new CommunicatorCallException("Unparsable date $val");
                }

                $dateDiff = ((new \DateTime($val))->diff(new \DateTime()));
                if ($dateDiff->invert === 0 && $dateDiff->days > 30) {
                    throw new CommunicatorCallException("Cannot query for schedules older than 30 days");
                }
            }
        };
    }

    private function validateParams($params)
    {
        foreach($params as $key => $param) {
            if (strlen($param) === 0)
                throw new CommunicatorCallException('empty parameter ' . $key);
            switch($key) {
                case 'carrier':
                    $valid = $this->validateAirlineCode($param);
                    break;
                case 'flightNumber':
                    $valid = $this->validateFlightNumber($param);
                    break;
                case 'depCode':
                case 'arrCode':
                    $valid = preg_match('/^[A-Z]{3}$/', $param) > 0;
                    break;
                case 'depDate':
                case 'arrDate':
                    $unix = strtotime($param);
                    $valid = false !== $unix && $unix > strtotime('1990-01-01');
                    break;
            }
            if (empty($valid))
                throw new CommunicatorCallException(sprintf('invalid parameter %s', $key));
        }
    }

    /**
     * @param string $path путь до сущности (schedules/rest/v1/json/from/PHX/to/LAS/departing/2017/3/11?appId=&appKey=)
     * @param array|null $query
     * @param Context $context
     * @return string
     * @throws CommunicatorCallException
     */
    private function call($path, $query, Context $context)
    {
        $this->wasLastCallFromCache = null;
        $cachedResponse = $this->memcached->get(self::CACHE_PREFIX . $path);
        if(!empty($cachedResponse)) {
            $this->logger->info("Returned from cache response for $path", $this->defaultContext);
            $this->wasLastCallFromCache = true;
            return $cachedResponse;
        }
        if (!is_array($query))
            $query = [];
        if (array_key_exists($context->getPartnerLogin(), $this->appCredentials) && !in_array($context->getMethod(), [
                Context::METHOD_HISTORICAL_BY_AIRPORT,
                Context::METHOD_HISTORICAL_BY_ROUTE,
                Context::METHOD_HISTORICAL_BY_FLIGHT,
            ]))
            $appInfo = $this->appCredentials[$context->getPartnerLogin()];
        else
            $appInfo = $this->appCredentials['default'];
        $query = array_merge($appInfo, $query);
        // By default, if a REST request to a valid resource results in an error,
        // the server will produce an HTTP 200 status with the error reported in the error element of the response.
        // This approach is friendlier to most javascript frameworks.
        $query['extendedOptions'] = 'useHTTPErrors';
        $request = new HttpDriverRequest(self::BASE_URL . $path . '?' . http_build_query($query));

        if(!$this->canRequest()){
            $this->logger->notice("request limit on " . self::BASE_URL . $path, $this->defaultContext);
            throw new CommunicatorCallException("FlightStat requests limit");
        }

        $this->logger->notice("Calling " . self::BASE_URL . $path, $this->defaultContext);
        $this->wasLastCallFromCache = false;
        $response = $this->httpDriver->request($request);
        $this->eventDispatcher->dispatch(CallEvent::NAME, CallEvent::fromContext($context, substr($appInfo['appId'], -4)));
        if($response->errorCode > 0) {
            $message = "Response error code $response->errorCode: $response->errorMessage";
            $this->logger->notice($message, $this->defaultContext);
            throw new CommunicatorCallException($message, 500);
        }
        if($response->httpCode >= 400) {
            $message = "Response HTTP code = $response->httpCode";
            $this->logger->notice($message, $this->defaultContext);
            throw new CommunicatorCallException("Response HTTP code = $response->httpCode", $response->httpCode);
        }
        $this->logger->info("Call to " . self::BASE_URL . $path . " was successful", $this->defaultContext);
        $this->memcached->set(self::CACHE_PREFIX . $path, $response->body, self::CACHE_TTL);
        return $response->body;
    }

    private function canRequest()
    {
        return true;
        /*
        $period = floor(time() / self::REQUEST_LIMIT_SECONDS);
        $cacheKey = self::REQUEST_THROTTLER_KEY . $period;

        $count = intval($this->memcached->get($cacheKey));
        if(self::REQUEST_LIMIT <= $count)
            return false;

        $this->memcached->set($cacheKey, intval($this->memcached->get($cacheKey)) + 1, self::REQUEST_LIMIT_SECONDS);
        return true;
        */
    }

    /**
     * @param string $json
     * @return Schedule|null
     * @throws CommunicatorCallException
     */
    private function deserializeSchedule($json)
    {
        /** @var Schedule $schedule */
        $schedule = $this->serializer->deserialize($json, Schedule::class, 'json');
        if($schedule->hasError()) {
            $this->logger->notice($schedule->getError()->getErrorMessage(), $this->defaultContext);
            throw new CommunicatorCallException($schedule->getError()->getErrorMessage(), $schedule->getError()->getHttpStatusCode());
        }
        return $schedule;
    }

    /**
     * @param $json
     * @return HistoricalFlightStatus
     */
    private function deserializeHistorical($json): HistoricalFlightStatus
    {
        /** @var HistoricalFlightStatus $status */
        $status = $this->serializer->deserialize($json, HistoricalFlightStatus::class, 'json');
        if($status->hasError()) {
            $this->logger->notice($status->getError()->getErrorMessage(), $this->defaultContext);
            throw new CommunicatorCallException($status->getError()->getErrorMessage(), $status->getError()->getHttpStatusCode());
        }
        return $status;
    }

    /**
     * @param $airlineCode
     * @return bool
     */
    private function validateAirlineCode($airlineCode)
    {
        if(is_string($airlineCode) && strlen($airlineCode) === 2) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $flightNumber
     * @return bool
     */
    private function validateFlightNumber($flightNumber)
    {
        if(preg_match('/^\d+$/', $flightNumber)) {
            return true;
        } else {
            return false;
        }
    }

	/**
	 * @return bool
	 */
    public function getWasLastCallFromCache() {
    	return $this->wasLastCallFromCache;
	}

	private function logException(\Exception $e){
        $this->logger->notice("communicator exception", array_merge($this->defaultContext, ["class" => get_class($e), "exception" => $e->getMessage(), "file" => $e->getFile(), "line" => $e->getLine()]));
    }

    /**
     * @param Context|null $context
     * @return Airline[]
     */
    public function getAllAirlines(Context $context = null)
    {
        try {
            $response = $this->call('airlines/rest/v1/json/all', [], $context ?? Context::getDefault(Context::METHOD_AIRLINES));
        } catch (CommunicatorCallException $e) {
            $this->logException($e);
            return null;
        }
        /** @var AirlinesResponse $airlinesResponse */
        $airlinesResponse = $this->serializer->deserialize($response, AirlinesResponse::class, 'json');
        return $airlinesResponse->getAirlines();
    }

}