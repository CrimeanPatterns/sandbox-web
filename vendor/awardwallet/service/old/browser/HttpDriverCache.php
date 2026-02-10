<?php

class HttpDriverCache implements HttpDriverInterface
{

    public const ATTR_CACHE_DATE = 'hdc_cache_date';
    public const ATTR_FROM_CACHE = 'hdc_from_cache';
    public const ATTR_TTL = 'hdc_ttl';
    public const ATTR_CAN_CACHE_CALLBACK = 'hdc_can_cache';

    public const DEFAULT_TTL = 180;

    /**
     * @var HttpDriverInterface
     */
    private $delegate;
    /**
     * @var Memcached
     */
    private $memcached;

    public function __construct(\HttpDriverInterface $delegate, \Memcached $memcached)
    {
        $this->delegate = $delegate;
        $this->memcached = $memcached;
    }

    public function start($proxy = null, $proxyLogin = null, $proxyPassword = null, $userAgent = null)
    {
        return $this->delegate->start($proxy, $proxyLogin, $proxyPassword, $userAgent);
    }

    public function stop()
    {
        return $this->delegate->stop();
    }

    public function isStarted()
    {
        return $this->delegate->isStarted();
    }

    public function request(HttpDriverRequest $request)
    {
        if (isset($request->attributes[self::ATTR_CAN_CACHE_CALLBACK]) || $request->method === 'GET') {
            $cacheKey = "hdc_" . sha1($request->url);

            $cacheData = $this->memcached->get($cacheKey);
            if ($cacheData !== false) {
                /** @var HttpDriverResponse $response */
                $response = unserialize($cacheData, ['allowed_classes' => ['HttpDriverResponse', 'HttpDriverRequest']]);
            }

            if (!empty($response)) {
                $response->attributes[self::ATTR_FROM_CACHE] = true;
                $response->request = $request;
                return $response;
            }

            $response = $this->delegate->request($request);

            $response->attributes[self::ATTR_FROM_CACHE] = false;

            if (isset($request->attributes[self::ATTR_CAN_CACHE_CALLBACK])) {
                $writeCache = call_user_func($request->attributes[self::ATTR_CAN_CACHE_CALLBACK], $response);
            } else {
                $writeCache = $response->httpCode == 200;
            }

            if (!$writeCache) {
                return $response;
            }

            $response->attributes[self::ATTR_CACHE_DATE] = time();
            $this->memcached->set($cacheKey, serialize($response), $request->attributes[self::ATTR_TTL] ?? self::DEFAULT_TTL);

            return $response;
        }

        $response = $this->delegate->request($request);
        $response->attributes[self::ATTR_FROM_CACHE] = false;
        return $response;
    }

    public function getState()
    {
        return $this->delegate->getState();
    }

    public function setState(array $state)
    {
        return $this->delegate->setState($state);
    }

    public function setLogger(HttpLoggerInterface $logger)
    {
        return $this->delegate->setLogger($logger);
    }
}