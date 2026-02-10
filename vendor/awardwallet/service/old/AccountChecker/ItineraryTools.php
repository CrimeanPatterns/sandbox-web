<?php

trait ItineraryTools {

	// remove Passengers, Currency, TotalCharge, Total, Tax, Taxes, TotalTaxAmount, GuestNames, RenterName
	function correctItinerary($it, $uniteSegments = false, $uniteFlights = false)
	{
		if ($uniteSegments){
			$it = $this->uniteAirSegments($it);
		}

		// check if mixed
		$mixed = count($it)>1?true:false;
		if ($mixed){

			// check number of travelers
			$names = [];
			foreach($it as $i => &$cur){
				array_walk($cur, function($value, $key) use(&$names){
					if (in_array($key, ['Passengers', 'GuestNames', 'RenterName'])){
						if (is_array($value)){
							foreach($value as $name){
								$names[niceName(nice($name))] = 1;
							}
						} else {
							$names[niceName(nice($value))] = 1;
						}
					}
				});
			}

			$numTravelers = count(array_keys($names));

			foreach($it as $i => &$cur)
			{
				array_walk($it, function(&$cur, $key) use($numTravelers) {
					if ($numTravelers > 1){
						unset($cur['Passengers']);
						unset($cur['GuestNames']);
						unset($cur['RenterName']);
					}

					unset($cur['Currency']);
					unset($cur['BaseFare']);
					unset($cur['TotalCharge']);
					unset($cur['Total']);
					unset($cur['TotalTaxAmount']);
					unset($cur['Taxes']);
					unset($cur['Tax']);
				}, $cur);
			}
		}

		return $uniteFlights ? $this->uniteFlights($it) : $it;
	}

	function uniteAirSegments($it)
	{
		// index by locators
		$index = [];
		$other = [];

		foreach($it as $i => &$cur)
		{
			// only trip with locators
			if (!isset($cur['Kind']) || $cur['Kind'] != 'T' || !isset($cur['RecordLocator'])){
				$other[] = $cur;
				continue;
			}

			// should be exist anyway
			if (!isset($cur['TripSegments']))
				$cur['TripSegments'] = [];

			$locator = $cur['RecordLocator'].(isset($cur['TripCategory'])?$cur['TripCategory']:'');

			if (!isset($index[$locator])){
				$index[$locator] = $cur; // begin
			} else {
				foreach($cur['TripSegments'] as $seg){
					$index[$locator]['TripSegments'][] = $seg;
				}
			}
		}

		// recount
		$res = $other;
		foreach($index as $locator => $it){
			$res[] = $it;
		}

		foreach ($res as &$i) {
			if (isset($i['Status']) and $i['Status'])
				continue;
			$statuses = [];
			if (isset($i['TripSegments']))
				foreach ($i['TripSegments'] as $ts)
					$statuses[] = isset($ts['Status']) ? $ts['Status'] : null;
			$uniqueStatuses = array_values(array_unique($statuses));
			if (count($uniqueStatuses) == 1 and $uniqueStatuses[0])
				$i['Status'] = $uniqueStatuses[0];
		}

		return $res;
	}

	function uniteFlights($it)
	{
		// index by locators
		$index = [];
		$other = [];

		foreach($it as $i => &$cur)
		{
			// only trip with locators
			if (!isset($cur['Kind']) || $cur['Kind'] != 'T' || !isset($cur['RecordLocator'])){
				$other[] = $cur;
				continue;
			}

			if (isset($cur['TripSegments']) && is_array($cur['TripSegments'])){
				$rebuild = [];

				foreach($cur['TripSegments'] as $seg){
					$air = $seg['AirlineName'].' '.$seg['FlightNumber'];
					if (!isset($rebuild[$air]))
						$rebuild[$air] = $seg;
					else {
						if (isset($seg['Seats']) && $seg['Seats']){
							if (!isset($rebuild[$air]['Seats'])){
								$rebuild[$air]['Seats'] = "";
							}
							$rebuild[$air]['Seats'] = trim($rebuild[$air]['Seats'].','.$seg['Seats'], ',.');
							$seats = [];
							foreach(explode(",",$rebuild[$air]['Seats']) as $seat){
								$seats[trim($seat)] = 1;
							}
							$rebuild[$air]['Seats'] = implode(",", array_keys($seats));
						}
					}
				}

				$cur['TripSegments'] = [];
				foreach($rebuild as $flight){
					$cur['TripSegments'][] = $flight;
				}
			}
		}

		return $it;
	}

}