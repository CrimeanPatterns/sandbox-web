<?
/**
 * delay some process, using memcached for IPC
 */
class Throttler implements ThrottlerInterface {

    const PREFIX = 'thr_';
	
	// throttling. prevent ddos crawled site
	// all parameters must be set
	// APC extension required
	// how many seconds in one period
	public $PeriodSeconds;
	// how many periods to keep
	public $PeriodCount;
	// how many request allowed within $PeriodCount * $PeriodSeconds seconds
	// if there are too much requests - process will be suspended
	public $RequestCount;
	/**
	 * @var Memcached memcached instance, required for throttling
	 */
	public $Cache;

	public function __construct(Memcached $cache, $periodSeconds, $periodCount, $requestCount){
		$this->Cache = $cache;
		$this->PeriodSeconds = $periodSeconds;
		$this->PeriodCount = $periodCount;
		$this->RequestCount = $requestCount;
	}

	/**
	 * delay request if throttling enabled
	 * @returns int - how many seconds to throttle
	 */
	public function getDelay(string $key, bool $readOnly = false, int $increment = 1) : int {
		if(!isset($this->PeriodSeconds) || !isset($this->PeriodCount) || !isset($this->RequestCount))
			return 0;

		if($this->getThrottledRequestsCount($key) >= $this->RequestCount)
            $result = random_int($this->PeriodSeconds, $this->PeriodSeconds + floor($this->PeriodSeconds / 2));
        else
			$result = 0;

		if (!$readOnly) {
		    $this->increment($key, $increment);
		}

		return $result;
	}

	public function increment(string $key, int $increment = 1) : void
    {
        $ttl = $this->PeriodSeconds * $this->PeriodCount;
        $period = floor(time() / $this->PeriodSeconds) % $this->PeriodCount;
        $cacheKey = self::PREFIX . "{$key}_{$period}";
        if ($increment > 0) {
            $this->Cache->increment($cacheKey, $increment, $increment, $ttl);
        } else {
            $this->Cache->decrement($cacheKey, abs($increment), $increment, $ttl);
        }
    }

	/**
	 * how many request was made to this hosts within $PeriodSeconds * $PeriodCount
	 */
	public function getThrottledRequestsCount(string $key) : int {
		$result = 0;

        $values = $this->Cache->getMulti(array_map(function($n) use ($key) { return self::PREFIX . "{$key}_{$n}"; }, range(0, $this->PeriodCount - 1)));
        if (is_array($values)) {
            $result = (int)array_sum($values);
        }

		return $result;
	}

	public function clear(string $key) : void {
		for($n = 0; $n < $this->PeriodCount; $n++){
			$cacheKey = self::PREFIX . "{$key}_{$n}";
			$this->Cache->delete($cacheKey);
		}
	}

}
