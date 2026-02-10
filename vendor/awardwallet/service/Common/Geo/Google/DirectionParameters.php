<?php


namespace AwardWallet\Common\Geo\Google;


class DirectionParameters extends Parameters
{
    /**
     * The address, textual latitude/longitude value, or place ID from which you wish to calculate directions.
     * If you pass an address, the Directions service geocodes the string and converts it to a latitude/longitude coordinate to calculate directions. 
     * This coordinate may be different from that returned by the Geocoding API, for example a building entrance rather than its center.
     *      origin=24+Sussex+Drive+Ottawa+ON
     *
     * If you pass coordinates, they are used unchanged to calculate directions. Ensure that no space exists between the latitude and longitude values.
     *      origin=41.43206,-81.38992
     *
     * Place IDs must be prefixed with place_id:. The place ID may only be specified if the request includes an API key or a Google Maps APIs Premium Plan client ID.
     * You can retrieve place IDs from the Geocoding API and the Places SDK (including Place Autocomplete). For an example using place IDs from Place Autocomplete, see Place Autocomplete and Directions. For more about place IDs, see the place ID overview.
     *      origin=place_id:ChIJ3S-JXmauEmsRUcIaWtf4MzE
     *
     * @var string
     */
    private $origin;

    /**
     * The address, textual latitude/longitude value, or place ID to which you wish to calculate directions.
     * The options for the destination parameter are the same as for the origin parameter, described above
     *
     * @var string
     */
    private $destination;

    /**
     * (defaults to driving) — Specifies the mode of transport to use when calculating directions.
     *
     * When you calculate directions, you may specify the transportation mode to use. By default, directions are calculated as driving directions. The following travel modes are supported:
     *     - driving (default) indicates standard driving directions using the road network.
     *     - walking requests walking directions via pedestrian paths & sidewalks (where available).
     *     - bicycling requests bicycling directions via bicycle paths & preferred streets (where available).
     *     - transit requests directions via public transit routes (where available). If you set the mode to transit, you can optionally specify either a departure_time or an arrival_time.
     *     If neither time is specified, the departure_time defaults to now (that is, the departure time defaults to the current time). You can also optionally include a transit_mode and/or a transit_routing_preference.
    */
    private $mode;

    /**
     *Specifies one or more preferred modes of transit. This parameter may only be specified for transit directions, and only if the request includes an API key or a Google Maps APIs Premium Plan client ID.
     * The parameter supports the following arguments:
     *     - bus indicates that the calculated route should prefer travel by bus.
     *     - subway indicates that the calculated route should prefer travel by subway.
     *     - train indicates that the calculated route should prefer travel by train.
     *     - tram indicates that the calculated route should prefer travel by tram and light rail.
     *     - rail indicates that the calculated route should prefer travel by train, tram, light rail, and subway. This is equivalent to transit_mode=train|tram|subway.
    */
    private $transit_mode;

    /**
     * Specifies the desired time of arrival for transit directions, in seconds since midnight, January 1, 1970 UTC. You can specify either departure_time or arrival_time, but not both. Note that arrival_time must be specified as an integer
    */
//    private $arrival_time;

    /**
     * Specifies the desired time of departure. You can specify the time as an integer in seconds since midnight, January 1, 1970 UTC.
     * Alternatively, you can specify a value of now, which sets the departure time to the current time (correct to the nearest second). The departure time may be specified in two cases:
     *     For requests where the travel mode is transit: You can optionally specify one of departure_time or arrival_time.
     *          If neither time is specified, the departure_time defaults to now (that is, the departure time defaults to the current time).
     *     For requests where the travel mode is driving: You can specify the departure_time to receive a route and trip duration (response field: duration_in_traffic) that take traffic conditions into account.
     *          This option is only available if the request contains a valid API key, or a valid Google Maps APIs Premium Plan client ID and signature.
     *          The departure_time must be set to the current time or some time in the future. It cannot be in the past.
    */
//    private $departure_time;


    //TODO: for more options: https://developers.google.com/maps/documentation/directions/intro#required-parameters
    protected function getAllParametersAsArray(): array
    {
        $result = [
            'origin' => $this->origin,
            'destination' => $this->destination,
        ];
        if ($this->mode === 'transit') {
            $result['mode'] = 'transit';
            if (isset($this->transit_mode))
                $result['mode'] = $this->transit_mode;
//            if ($this->departure_time) {
//                //make future date (day and month is metter for bus|train... because of schedule)
//                while ($this->departure_time < strtoupper("+1 minutes"))
//                    $this->departure_time = strtotime("+1 year",$this->departure_time);
//                $result['departure_time'] = $this->departure_time;
//            } elseif ($this->arrival_time){
//                //make future date (day and month is metter for bus|train... because of schedule)
//                while ($this->arrival_time < strtoupper("+1 minutes"))
//                    $this->arrival_time = strtotime("+1 year",$this->arrival_time);
//                $result['arrival_time'] = $this->arrival_time;
//            }
        }

        return $result;
    }

//    private function __construct(string $origin, string $destination, ?string $mode = 'driving', ?string $transit_mode = null, ?int $departure_time = null, ?int $arrival_time = null)
    private function __construct(string $origin, string $destination, ?string $mode = 'driving', ?string $transit_mode = null)
    {
        $this->origin = $origin;
        $this->destination = $destination;
        $this->mode = $mode;
        $this->transit_mode = $transit_mode;
//        $this->departure_time = $departure_time;
//        $this->arrival_time = $arrival_time;
    }

//    public static function makeFromDerection(string $origin, string $destination, ?string $mode = 'driving', ?string $transit_mode = null, ?int $departure_time = null, ?int $arrival_time = null)
    public static function makeFromDerection(string $origin, string $destination, ?string $mode = 'driving', ?string $transit_mode = null) : DirectionParameters
    {
//        return new self($origin, $destination, $mode, $transit_mode, $departure_time, $arrival_time);
        return new self($origin, $destination, $mode, $transit_mode);
    }

}