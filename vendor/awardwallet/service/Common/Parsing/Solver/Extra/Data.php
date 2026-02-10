<?php


namespace AwardWallet\Common\Parsing\Solver\Extra;


class Data {

	/** @var AircraftData[] */
	private $aircraft = [];
	/** @var ProviderData[] */
	private $provider = [];
	/** @var AirlineData[] */
	private $airline = [];
	/** @var GeoData[] */
	private $geo = [];
	/** @var array  */
	private $airCode = [];

    /**
     * @param $key
     * @param array $data
     * @return ProviderData
     */
	public function addProviderArray($key, array $data): ProviderData
    {
        $this->provider[$key] = ProviderData::fromArray($this->filter($data));
        return $this->provider[$key];
    }

    /**
     * @param $key
     * @param ProviderData $data
     * @return ProviderData
     */
	public function addProvider($key, ProviderData $data): ProviderData
    {
		$this->provider[$key] = $data;
		return $data;
	}

	/**
	 * @param $key
	 * @return ProviderData|null
	 */
	public function getProvider($key): ?ProviderData {
		return $this->provider[$key] ?? null;
	}

    /**
     * @param $key
     * @return bool
     */
	public function existsProvider($key): bool
    {
		return array_key_exists($key, $this->provider);
	}

	/**
	 * @param $key
	 * @param AirlineData $data
	 * @return AirlineData
	 */
	public function addAirline($key, AirlineData $data): AirlineData
    {
		$this->airline[$key] = $data;
		return $this->airline[$key];
	}

    /**
     * @param $key
     * @param array $data
     * @return AirlineData
     */
	public function addAirlineArray($key, array $data): AirlineData
    {
        $this->airline[$key] = AirlineData::fromArray($this->filter($data));
        return $this->airline[$key];
    }

    /**
     * @param $key
     */
    public function nullAirline($key): void
    {
        $this->airline[$key] = null;
    }

	/**
	 * @param $key
	 * @return AirlineData|null
	 */
	public function getAirline($key) {
		return $this->airline[$key] ?? null;
	}

    /**
     * @param $key
     * @return bool
     */
	public function existsAirline($key): bool
    {
		return array_key_exists($key, $this->airline);
	}

/*
    public function getAirlineCodes() {
		return array_keys($this->airline);
	}
*/
    public function addGeo(string $key, GeoData $data): void
    {
        $this->geo[$key] = $data;
    }

    /**
     * @param $key
     * @param array $data
     * @return GeoData
     */
	public function addGeoArray(string $key, array $data): GeoData
    {
        $this->geo[$key] = GeoData::fromArray($this->filter($data));
        return $this->geo[$key];
	}

    /**
     * @param $key
     */
	public function nullGeo(string $key): void
    {
		$this->geo[$key] = null;
	}

	/**
	 * @param $key
	 * @return GeoData|null
	 */
	public function getGeo(string $key): ?GeoData
    {
		return $this->geo[$key] ?? null;
	}

    /**
     * @param $key
     * @return bool
     */
	public function existsGeo(string $key): bool
    {
		return array_key_exists($key, $this->geo);
	}

    /**
     * @param $key
     * @param AircraftData $data
     * @return AircraftData
     */
	public function addAircraft($key, AircraftData $data): AircraftData
    {
		$this->aircraft[$key] = $data;
		return $this->aircraft[$key];
	}

    /**
     * @param $key
     */
	public function nullAircraft($key): void
    {
        $this->aircraft[$key] = null;
    }

    /**
     * @param $key
     * @param array $data
     * @return AircraftData
     */
	public function addAircraftArray($key, array $data): AircraftData
    {
        $this->aircraft[$key] = AircraftData::fromArray($this->filter($data));
        return $this->aircraft[$key];
    }

	/**
	 * @param $key
	 * @return AircraftData|null
	 */
	public function getAircraft($key): ?AircraftData
    {
		return $this->aircraft[$key] ?? null;
	}

    /**
     * @param $key
     * @return bool
     */
	public function existsAircraft($key): bool
    {
		return array_key_exists($key, $this->aircraft);
	}

    /**
     * @param $key
     * @param null|string $code
     * @return null|string
     */
	public function addAirCode($key, ?string $code): ?string
    {
        $this->airCode[$key] = $code;
        return $code;
    }

    /**
     * @param $key
     * @return null|string
     */
    public function getAirCode($key): ?string
    {
        return $this->airCode[$key] ?? null;
    }

    /**
     * @param $key
     * @return bool
     */
    public function existsAirCode($key): bool
    {
        return array_key_exists($key, $this->airCode);
    }

	public function filter($data) {
		$r = [];
		foreach($data as $k => $v)
			$r[$k] = (is_string($v) && strlen($v) === 0) ? null : $v;
		return $r;
	}

}