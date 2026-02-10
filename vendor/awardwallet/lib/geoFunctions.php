<?

function GoogleGeoTagLimitOk(&$value = null) {
	if(
		function_exists('getSymfonyContainer')
		&& getSymfonyContainer()->hasParameter('google_api_key')
		&& strlen(getSymfonyContainer()->getParameter('google_api_key')) > 15
	)
		return true;
	$key = 'googlegeotag_'.gethostname();

	$value = Cache::getInstance()->get($key);

	if (empty($value)) {
		$q = new TQuery("
			SELECT COUNT(*) AS c
			FROM GeoTag
			WHERE UpdateDate > (NOW() - INTERVAL 1 DAY)
				AND (HostName IS NULL OR HostName = '".gethostname()."')
		");
		if (!$q->EOF) {
			$value = intval($q->Fields['c']);
			Cache::getInstance()->set($key, $value, 60*5);
		}
	}
	return $value < 2500*0.75;
}

/*function GoogleGeoTagLimitInc() {
	$key = 'googlegeotag';
	apc_inc($key);
}*/

function SendGoogleGeoRequest($url){
	$objXML = curlXmlRequest($url, 2);
	if(isset($objXML->status) && ($objXML->status == 'OK') && isset($objXML->result)
	&& isset($objXML->result[0]->geometry->location->lat))
		return $objXML->result[0];
	else
		return null;
}

function GoogleGeoTag($sAddress, &$nLat, &$nLng, &$arDetailedAddress = null, $placeName = null, $expectedType = 0){
    global $onGoogleGeoXmlRequest;
	if($sAddress == "")
		DieTrace("Empty address");
	if(preg_match("/[<>]/ims", $sAddress))
		DieTrace("invalid characters in address");
	// temporary hardcore, this entire function will be removed in favor of new geo classes
	if(
	    function_exists('getSymfonyContainer')
        && getSymfonyContainer()->hasParameter('google_api_key')
        && strlen(getSymfonyContainer()->getParameter('google_api_key')) > 15
    ) {
        $baseUrl = "https://maps.googleapis.com/maps/api/geocode/xml?key=" . urlencode(getSymfonyContainer()->getParameter('google_api_key')) . "&";
        getSymfonyContainer()->get("aw.stat_logger")->info("GoogleGeoTag request", ["Address" => $sAddress, "BackTrace" => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)]);
    }
	else
	    $baseUrl = "http://maps.googleapis.com/maps/api/geocode/xml?";
	$nLat = $nLng = $arDetailedAddress = null;
	//GoogleGeoTagLimitInc();
    if(isset($onGoogleGeoXmlRequest))
        $result = $onGoogleGeoXmlRequest("address=" . urlencode($sAddress));
    else
	    $result = SendGoogleGeoRequest($baseUrl . "address=" . urlencode($sAddress));
	$tryPlace = !empty($placeName);
	$matchQuality = PHP_INT_MAX;
	if($result) {
        $type = array_map('strval', $result->xpath('./type'));
        if ((GEOTAG_TYPE_AIRPORT === $expectedType) && !in_array('airport', $type, true)) {
            return false;
        }

		$nLat = (float)$result->geometry->location->lat;
		$nLng = (float)$result->geometry->location->lng;
		EchoDebug("GeoTag", "GoogleGeoTag: found $sAddress [{$nLat}, {$nLng}]");
		if(!empty($result->formatted_address))
			$matchQuality = levenshtein($sAddress, $result->formatted_address);
		$arDetailedAddress = DecodeGoogleGeoResult($result, $sAddress);
		$address = ParseAddress($sAddress);
		// for addresses like 2424 E 38th Street, Dallas, TX 75261 US, which resolved to partial match
		if(!empty($address['PostalCode'])) {
			if (!empty($arDetailedAddress['PostalCode']) && $address['PostalCode'] == $arDetailedAddress['PostalCode'])
				$tryPlace = false;
			else
				$arDetailedAddress = $address;
		}
	}
	if($tryPlace){
		$place = GooglePlace($sAddress, $placeName);
		if(!empty($place)){
            if(isset($onGoogleGeoXmlRequest))
                $result = $onGoogleGeoXmlRequest("latlng=" . urlencode($place->geometry->location->lat . ',' . $place->geometry->location->lng));
            else
    			$result = SendGoogleGeoRequest($baseUrl . "latlng=" . urlencode($place->geometry->location->lat . ',' . $place->geometry->location->lng));
			if($result) {
				$placeAddress = DecodeGoogleGeoResult($result, $sAddress);
				if(!empty($placeAddress)) {
					if (!empty($address['PostalCode']) && !empty($placeAddress['PostalCode']) && $address['PostalCode'] == $placeAddress['PostalCode'])
						$arDetailedAddress = $placeAddress;
					if(levenshtein($sAddress, $result->formatted_address) < $matchQuality){
						$nLat = $place->geometry->location->lat;
						$nLng = $place->geometry->location->lng;
					}
				}
			}
		}
	}
	return !empty($nLat);
}

/*
{
 "formatted_address" : "2424 E 38th St, Dallas, TX 75261, United States",
 "geometry" : {
	"location" : {
	   "lat" : 32.857416,
	   "lng" : -97.035245
	}
 },
 "icon" : "http://maps.gstatic.com/mapfiles/place_api/icons/generic_business-71.png",
 "id" : "bf0127f31ce6372716f3e7f6f81d2063faaae550",
 "name" : "Advantage Rent A Car",
 "opening_hours" : {
	"open_now" : false,
	"weekday_text" : []
 },
 "photos" : [
	{
	   "height" : 250,
	   "html_attributions" : [],
	   "photo_reference" : "CpQBjQAAABKr8l2RMchlSMWSWAO42oEqh2InMz5snZOwoRFYk2gILRHNZTOzwtPKQJNO6b1acS6a5RjG_kew3ZkO6n3c8la6U0hq48qL8c7bEx_muK1ur8RwwKu1hxJ0gcQDzJolDIJ7Dm_s_S0ejAvJi37Fw6EzsGuYughEVr1rZNlfAug18f77jEJ51HuQl0RDK03dBBIQsJNqEieOboIh_-7Cw1LbOhoUwlIiK3O0mt8ATlYW-gACk7xcp58",
	   "width" : 250
	}
 ]
}
 */
function GooglePlace($addressText, $placeName){
	$detailedAddress = null;
	if(empty($placeName))
		return false;
	$address = ParseAddress($addressText);
	if(empty($address))
		return false;
	$response = @json_decode(curlRequest("https://maps.googleapis.com/maps/api/place/textsearch/json?query=".urlencode($placeName . ' ' . $addressText)."&key=".GOOGLE_PLACES_KEY));
	if(empty($response->results))
		return false;

	$bestMatch = null;
	foreach($response->results as $result){
		$foundAddress = ParseAddress($result->formatted_address);
		if(!empty($foundAddress['PostalCode']) && $foundAddress['PostalCode'] == $address['PostalCode'] && !empty($result->geometry->location->lat)) {
			$distance = levenshtein($result->name . ' ' . $result->formatted_address, $placeName . ' ' . $addressText);
			if (!isset($minDistance) || $distance < $minDistance) {
				$minDistance = $distance;
				$bestMatch = $result;
			}
		}
	}
	return $bestMatch;
}

function DecodeGoogleGeoResult($result){
	EchoDebug("GeoTag", "Detailed address is found");

	$arDetailedAddress = array(
		'Formatted' => (string)$result->formatted_address,
	);
	$street = '';
	$number = '';
	$jnumber = '';
	$establishment = '';

	$data = array();

	if (isset($result->formatted_address) && isset($result->address_component)) {
		foreach ($result->address_component as $component) {
			$name = null;
			if (isset($component->long_name))
				$name = (string)$component->long_name;
			elseif (isset($component->short_name))
				$name = (string)$component->short_name;

			if (isset($name) && isset($component->type)) {
				for ($i = 0; $i < $component->type->count(); $i++) {
					if ((string)$component->type[$i] != 'political')
						$data[(string)$component->type[$i]] = $name;

					switch ((string)$component->type[$i]) {
						case 'political':
							break;
						case 'country':
							$arDetailedAddress['Country'] = $name;
							if(!empty($component->short_name) && $component->short_name != $name)
								$arDetailedAddress['CountryCode'] = $component->short_name;
							break;
						case 'postal_code':
							$arDetailedAddress['PostalCode'] = $name;
							break;
						case 'administrative_area_level_1':
							$arDetailedAddress['State'] = $name;
							if(!empty($component->short_name) && $component->short_name != $name)
								$arDetailedAddress['StateCode'] = $component->short_name;
							break;
						case 'locality':
							$arDetailedAddress['City'] = $name;
							break;
						case 'sublocality':
							if (empty($arDetailedAddress['City']))
								$arDetailedAddress['City'] = $name;
							break;
						case 'sublocality_level_4':
						case 'sublocality_level_3':
						case 'sublocality_level_2':
							$jnumber = "$name-$jnumber";
							break;
						case 'sublocality_level_1':
							if (empty($street))
								$street = $name;
							break;
						case 'administrative_area_level_3':
						case 'administrative_area_level_2':
							if (empty($arDetailedAddress['City']))
								$arDetailedAddress['City'] = $name;
							if (empty($arDetailedAddress['State']))
								$arDetailedAddress['State'] = $name;
							break;
						case 'street_number':
							$number = $name;
							break;
						case 'route':
							$street = $name;
							break;
						case 'establishment':
							$establishment = $name;
							break;
						default:
							EchoDebug("GeoTag", "Unused address data: " . (string)$component->type[$i] . " => $name");
					}
				}
			}
		}
		EchoDebug("GeoTag", "<pre style='margin: 0; display: inline-block;'>" . trim(var_export($data, true)) . "</pre>");
		if (empty($number))
			$number = trim($jnumber, '-');
		$street = trim($number . ' ' . $street);
		if (empty($street))
			$street = $establishment;
		if (!empty($street))
			$arDetailedAddress['AddressLine'] = $street;
	}

	return $arDetailedAddress;
}

function ParseAddress($address){
	if(preg_match('#^([^\,]+)\,([^\,]+)\,\s*([A-Z]{2}|[a-z\s]{5,})\s*(\d{5})\s*\,?\s*(US|USA|United States)$#ims', $address, $matches) && preg_match('#^\d{5}$#ims', $matches[4])) {
		if(strlen($matches[3]) == 2) {
			$q = new TQuery("SELECT Name FROM State WHERE Code = '" . addslashes(trim($matches[3])) . "' AND CountryID = 230");
			if ($q->EOF)
				return null;
			$stateName = $q->Fields['Name'];
		}
		else
			$stateName = $matches[3];

		return [
			'AddressLine' => trim($matches[1]),
			'City' => trim($matches[2]),
			'State' => $stateName,
			'PostalCode' => $matches[4],
			'Country' => 'United States',
		];
	}
	else
		return null;
}

function Distance($nSrcLat, $nSrcLng, $nDstLat, $nDstLng){
	if($nSrcLat == $nDstLat && $nSrcLng == $nDstLng)
		return 0;
	$R = 3950;
	$nSrcLat = deg2rad($nSrcLat);
	$nSrcLng = deg2rad($nSrcLng);
	$nDstLat = deg2rad($nDstLat);
	$nDstLng = deg2rad($nDstLng);
	$nDistance = acos(sin($nSrcLat) * sin($nDstLat) + cos($nSrcLat) * cos($nDstLat) * cos($nDstLng - $nSrcLng)) * $R;
	EchoDebug("Distance", "Distance from @{$nSrcLat},{$nSrcLng} to {$nDstLat},{$nDstLng} is $nDistance miles");
	return $nDistance;
}

