<?php

namespace AwardWallet\Common\Parsing\Filter\FlightStats;


use AwardWallet\Common\Itineraries\FlightSegment;

interface TripSegmentFilterInterface
{
    /**
     * фильтрует данные полученные от парсера, дополняет их, возвращает в том же формате что и получает.
     * providerCode - код провайдера по нашей базе
     * @param FlightSegment $flightSegment
     * @param string $providerCode
     * @return void
     */
    public function filterTripSegment($providerCode, FlightSegment $flightSegment);
}