<?php

namespace AwardWallet\Common\AirLabs;

use AwardWallet\Common\AirLabs\RoutesResponse;
use HttpDriverInterface;
use HttpDriverRequest;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

class Communicator
{
    const BASE_URL = 'https://airlabs.co/api/v9/';
    const CACHE_PREFIX = 'air_labs_communicator_';
    const CACHE_TTL = 60 * 60 * 24 * 7;

    const METHOD_ROUTES = 'routes';
    const METHOD_FLIGHT = 'flight';

    const REQUEST_THROTTLER_KEY = 'air_labs_throttler_';
    const REQUEST_LIMIT = 100;
    const REQUEST_LIMIT_SECONDS = 10 * 60;

    /** @var HttpDriverInterface */
    private $httpDriver;
    /** @var LoggerInterface */
    private $logger;
    /** @var SerializerInterface */
    private $serializer;
    /** @var \Memcached */
    private $memcached;
    /** @var array */
    private $apiKey;
    /** @var boolean */
    private $wasLastCallFromCache;
    private $defaultContext = [
        "components" => "AirLabsCommunicator"
    ];

    /**
     * Communicator constructor.
     * @param HttpDriverInterface $httpDriver
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     * @param \Memcached $memcached
     * @param string $appKey
     */
    public function __construct(
        HttpDriverInterface $httpDriver,
        LoggerInterface $logger,
        SerializerInterface $serializer,
        \Memcached $memcached,
        $apiKey
    ) {
        $this->httpDriver = $httpDriver;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->memcached = $memcached;
        $this->apiKey = $apiKey;
    }

    /**
     * @param RoutesRequest $requestParams
     * @return RoutesResponse|null
     */
    public function getRoutes(RoutesRequest $requestParams)
    {
        try {
            $params = json_decode($this->serializer->serialize($requestParams, 'json'), true);
            $this->validateParams($params);
            $json = $this->call(self::METHOD_ROUTES, $params);
            return $this->deserialize($json, RoutesResponse::class);
        } catch (CommunicatorCallException $e) {
            $this->logException($e);
            return null;
        }
    }

    /**
     * @param string $fnIata
     * @return FlightResponse|null
     */
    public function getFlight($fnIata)
    {
        try {
            $params = ['flight_iata' => $fnIata];
            $this->validateParams($params);
            $json = $this->call(self::METHOD_FLIGHT, $params);
            return $this->deserialize($json, FlightResponse::class);
        } catch (CommunicatorCallException $e) {
            $this->logException($e);
            return null;
        }
    }

    /**
     * @param string $method
     * @param array|null $query
     * @return string
     * @throws CommunicatorCallException
     */
    private function call(string $method, ?array $query)
    {
        $this->wasLastCallFromCache = null;
        if (null == $query) {
            $query = [];
        }
        $path = $method . '_' . http_build_query($query);
        $cachedResponse = $this->memcached->get(self::CACHE_PREFIX . $path);
        if ($cachedResponse !== false) {
            $this->logger->info("Returned from cache response for $path", $this->defaultContext);
            $this->wasLastCallFromCache = true;
            return $cachedResponse;
        }
        $query['api_key'] = $this->apiKey;
        $request = new HttpDriverRequest(self::BASE_URL . $method . '?' . http_build_query($query));

        $this->logger->notice("Calling " . self::BASE_URL . $method, $this->defaultContext);
        $this->wasLastCallFromCache = false;
        $response = $this->httpDriver->request($request);
        if (isset($response->error)) {
            $message = "Response error code $response->error->code: $response->error->message";
            $this->logger->notice($message, $this->defaultContext);
            throw new CommunicatorCallException($message, 500);
        }
        if ($response->httpCode < 200 || $response->httpCode >= 300) {
            $message = "Response HTTP code = $response->httpCode";
            $this->logger->notice($message, $this->defaultContext);
            throw new CommunicatorCallException("Response HTTP code = $response->httpCode", $response->httpCode);
        }
        $this->logger->info("Call to " . self::BASE_URL . $path . " was successful", $this->defaultContext);
        $this->memcached->set(self::CACHE_PREFIX . $path, $response->body, self::CACHE_TTL);
        return $response->body;
    }

    /**
     * @param string $json
     * @param string $class
     * @return RoutesResponse|FlightResponse|null
     * @throws CommunicatorCallException
     */
    private function deserialize(string $json, string $class)
    {
        /** @var RoutesResponse $response */
        $response = $this->serializer->deserialize($json, $class, 'json');
        if ($response->hasError()) {
            $this->logger->notice($response->getError()->getMessage(), $this->defaultContext);
            throw new CommunicatorCallException($response->getError()->getMessage(), 0);
        }
        return $response;
    }

    private function validateParams($params)
    {
        foreach ($params as $key => $param) {
            if (strlen($param) === 0) {
                throw new CommunicatorCallException('empty parameter ' . $key);
            }
            $valid = true;
            switch ($key) {
                case 'airline_iata':
                    $valid = $this->validateAirlineCode($param);
                    break;
                case 'flight_number':
                    $valid = $this->validateFlightNumber($param);
                    break;
                case 'flight_iata':
                    $airline = substr($param, 0, 2);
                    $flight = substr($param, 2);
                    $valid = $this->validateAirlineCode($airline) && $this->validateFlightNumber($flight);
                    break;
                case 'dep_iata':
                case 'arr_iata':
                    $valid = preg_match('/^[A-Z]{3}$/', $param) > 0;
                    break;
            }
            if (!$valid) {
                throw new CommunicatorCallException(sprintf('invalid parameter %s', $key));
            }
        }
    }

    private function validateAirlineCode($airlineCode): bool
    {
        return preg_match('/^(?:[A-Z\d][A-Z]|[A-Z][A-Z\d])$/', $airlineCode) === 1;
    }

    private function validateFlightNumber($flightNumber): bool
    {
        return preg_match('/^\d+$/', $flightNumber) === 1;
    }

    public function getWasLastCallFromCache(): bool
    {
        return $this->wasLastCallFromCache;
    }

    private function logException(\Exception $e)
    {
        $this->logger->notice("communicator exception", array_merge($this->defaultContext, [
            "class" => get_class($e),
            "exception" => $e->getMessage(),
            "file" => $e->getFile(),
            "line" => $e->getLine()
        ]));
    }
}