<?php


namespace AwardWallet\Common\Parsing\Solver\Helper;


use AwardWallet\Common\Geo\Constants;
use AwardWallet\Common\Geo\GoogleGeo;

class AddressHelper {

	/** @var GoogleGeo $geo */
	protected $geo;

	public function __construct(GoogleGeo $geo) {
		$this->geo = $geo;
	}

    public function parseAddress($address, $hotel = false, $tip = null) {
        if ($hotel) {
            $details = $this->geo->FindHotel($address);
        } else {
            $details = $this->geo->FindGeoTag($address, null, 0, false, true, $this->buildBias($tip));
        }
        if (!empty($details['Lat']) && !empty($details['Lng']) && !empty($details['Country']) && empty($details['City'])) {
            $reverse = $this->geo->FindReverseGeoTag($details['Lat'], $details['Lng']);
            if (!empty($reverse)) {
                foreach(['City', 'State', 'AddressLine', 'PostalCode'] as $key)
                    if (empty($details[$key]) && !empty($reverse[$key]))
                        $details[$key] = $reverse[$key];
            }
        }
        // PS returned 3letter
        if (isset($details['CountryCode']) && 'USA' === $details['CountryCode']) {
            $details['CountryCode'] = 'US';
        }
        return $details;
    }

	public function parseAirport($code) {
		return $this->geo->FindGeoTag($code, null, Constants::GEOTAG_TYPE_AIRPORT_WITH_ADDRESS);
	}

    public function parseDuration($startPoint, $endPoint, ?string $mode = 'driving', ?string $transit_mode = null) {
        return $this->geo->FindDuration($startPoint, $endPoint, $mode, $transit_mode);
    }

    private function buildBias($tip)
    {
        if (!empty($tip)) {
            $tip = strtolower($tip);
            if (in_array($tip, ['eu', 'europe'])) {
                // neLat, neLng, swLat, swLng
                return ['box' => '74.32 45.84 35.26 -11.20'];
            }
            if ('asia' == $tip) {
                return ['box' => '58.04 151.25 -11.41 56.33'];
            }
            if ('am' == $tip) {
                return ['box' => '74.80 -31.90 -58.66 -163.74'];
            }
            if (preg_match('/^[a-z]{2}$/', $tip) > 0) {
                return ['country' => $tip];
            }
        }
        return [];
    }

}