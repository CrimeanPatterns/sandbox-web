<?php

class CruiseSegmentsConverter {

	function Convert($cruise) {
		if (!is_array($cruise))
			return null;

		$result = array();
		$depPort = $depDate = $arrPort = $arrDate = null;
		$lastDate = 0;
		foreach ($cruise as $cs) {
			if ((isset($cs['DepDate']) || isset($cs['ArrDate'])) && isset($cs['Port']) && strcasecmp($cs['Port'], "At Sea") != 0) {
				$arrPort = $cs['Port'];
				$arrDate = isset($cs['ArrDate']) ? $cs['ArrDate'] : null;
				if ($depPort && $depDate && $arrPort && $arrDate) {
					$segment = array();
					$segment['DepName'] = $depPort;
					$segment['DepDate'] = $depDate;
					$segment['ArrName'] = $arrPort;
					$segment['ArrDate'] = $arrDate;

					if ($lastDate > 0 && $lastDate - $segment['DepDate'] > 30 * SECONDS_PER_DAY) {
						$segment['DepDate'] = strtotime('+1 year', $segment['DepDate']);
					}
					$lastDate = $segment['DepDate'];
					if ($lastDate > 0 && $lastDate - $segment['ArrDate'] > 30 * SECONDS_PER_DAY) {
						$segment['ArrDate'] = strtotime('+1 year', $segment['ArrDate']);
					}
					$lastDate = $segment['ArrDate'];

					$result[] = $segment;
				}
				$depPort = $cs['Port'];
				$depDate = isset($cs['DepDate']) ? $cs['DepDate'] : null;
			}
		}
		return $result;
	}

}