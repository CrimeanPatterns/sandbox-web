<?php


namespace AwardWallet\Common\Parsing\Solver;


class Exception extends \Exception {

    private $source;

    public function __construct(string $message = "", ?string $source = null)
    {
        parent::__construct($message);
        $this->source = $source;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public static function unknownAirCode($airCode)
    {
		return new self(sprintf('Unknown AirCode `%s`', $airCode));
	}

    public static function unknownStationCode($stationCode)
    {
        return new self(sprintf('Unknown StationCode `%s`', $stationCode));
    }

    public static function unknownAirlineCode($airlineCode)
    {
		return new self(sprintf('Unknown AirlineCode `%s`', $airlineCode));
	}

	public static function unknownProviderCode($providerCode)
    {
		return new self(sprintf('Unknown ProviderCode `%s`', $providerCode));
	}

	public static function unknownProviderKeyWord($word)
    {
		return new self(sprintf('Unknown Provider KeyWord `%s`', $word));
	}

	public static function suspiciousValue($name, $value)
    {
	    return new self(sprintf('Suspicious value `%s` in `%s`', $value, $name));
    }

    public static function impossibleRoute($dep, $arr, $source = null)
    {
        return new self(sprintf('%s: Impossible route between `%s` <--> `%s`', $source, $dep, $arr), $source);
    }

}