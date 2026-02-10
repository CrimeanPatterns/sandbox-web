<?php

namespace AwardWallet\Common\Geo;

class Geo
{

    /**
     * miles
     */
    private const EARTH_RADIUS_MILES = 3950.0;
    private const KM_IN_MILE = 1.609344;

    public static function km2Mi(float $km): float
    {
        return $km / self::KM_IN_MILE;
    }

    /**
	 * returns distance between two locations in miles
	 *
	 * @param $srcLat
	 * @param $srcLng
	 * @param $dstLat
	 * @param $dstLng
	 * @return float|int
	 */
	public static function distance($srcLat, $srcLng, $dstLat, $dstLng){
		if($srcLat == $dstLat && $srcLng == $dstLng)
			return 0;

		$srcLat = deg2rad($srcLat);
		$srcLng = deg2rad($srcLng);
		$dstLat = deg2rad($dstLat);
		$dstLng = deg2rad($dstLng);
		$distance = acos(sin($srcLat) * sin($dstLat) + cos($srcLat) * cos($dstLat) * cos($dstLng - $srcLng)) * self::EARTH_RADIUS_MILES;

		return $distance;
	}

    /**
     * Calculates the great-circle distance between two points, with
     * the Vincenty formula.
     * @param float $latitudeFrom Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo Latitude of target point in [deg decimal]
     * @param float $longitudeTo Longitude of target point in [deg decimal]
     * @param float $earthRadius Mean earth radius in [m]
     * @return float Distance between points in [m] (same as earthRadius)
     */
    public static function vincentyDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);
        return $angle * $earthRadius;
    }

    /**
     * @param float $lat
     * @param float $lng
     * @param string $latField
     * @param string $lngField
     * @param bool $needParams
     * @param float $squareSide
     *
     * @return array|string
     */
    public static function getSquareGeofenceSQLCondition($lat, $lng, $latField, $lngField, $needParams = true, $squareSide = 4.0)
    {
        if ($squareSide > self::EARTH_RADIUS_MILES) {
            $squareSide = self::EARTH_RADIUS_MILES;
        }

        $degDiff = rad2deg($squareSide / Geo::EARTH_RADIUS_MILES) * 0.5;

        $latNorthBoundary = $lat + $degDiff;

        if ($latNorthBoundary > 90) {
            $latNorthBoundary = 90 - ($latNorthBoundary - 90);
        }

        $latSouthBoundary = $lat - $degDiff;

        if ($latSouthBoundary < -90) {
            $latSouthBoundary = -90 + (-$latSouthBoundary - 90);
        }

        $lngEastBoundary = $lng + $degDiff;

        if ($lngEastBoundary > 180) {
            $lngEastBoundary = -180 + ($lngEastBoundary - 180);
        }

        $lngWestBoundary = $lng - $degDiff;

        if ($lngWestBoundary < -180) {
            $lngWestBoundary = 180 + ($lngWestBoundary + 180);
        }

        if ($needParams) {
            $conditions = /** @lang MySQL */
                "
                (
                    ({$latField} <= :latNorth) AND
                    ({$latField} >= :latSouth)
                )
            ";
            $paramsList = [
                [':latNorth', $latNorthBoundary, \PDO::PARAM_STR],
                [':latSouth', $latSouthBoundary, \PDO::PARAM_STR],
            ];
        } else {
            $conditions = /** @lang MySQL */
                "
                (
                    ({$latField} <= $latNorthBoundary) AND
                    ({$latField} >= $latSouthBoundary)
                )
            ";
            $paramsList = null;
        }

        if ($lngEastBoundary >= $lngWestBoundary) {
            if ($needParams) {
                $conditions   = /** @lang MySQL */
                    "
                    {$conditions} AND 
                    ({$lngField} >= :lngWest) AND
                    ({$lngField} <= :lngEast)
                ";
                $paramsList[] = [':lngWest', $lngWestBoundary, \PDO::PARAM_STR];
                $paramsList[] = [':lngEast', $lngEastBoundary, \PDO::PARAM_STR];
            } else {
                $conditions = /** @lang MySQL */
                    "
                    {$conditions} AND 
                    ({$lngField} >= {$lngWestBoundary}) AND
                    ({$lngField} <= {$lngEastBoundary})
                ";
            }
        } else {
            if ($needParams) {
                $conditions   = /** @lang MySQL */
                    "
                    {$conditions} AND 
                    ({$lngField} >= :lngWest)
                ";
                $paramsList[] = [':lngWest', $lngWestBoundary, \PDO::PARAM_STR];
            } else {
                $conditions = /** @lang MySQL */
                    "
                    {$conditions} AND 
                    ({$lngField} >= {$lngWestBoundary})
                ";
            }
        }

        if ($needParams) {
            return [$conditions, $paramsList];
        } else {
            return $conditions;
        }
    }

    /**
     * @param string $srcLat circle center
     * @param string $srcLng circle center
     * @param string $dstLat
     * @param string $dstLng
     *
     * @return string
     */
    public static function getDistanceSQL($srcLat, $srcLng, $dstLat, $dstLng)
    {
        $srcLat = "radians({$srcLat})";
        $srcLng = "radians({$srcLng})";
        $dstLat = "radians({$dstLat})";
        $dstLng = "radians({$dstLng})";

        return "(
            acos(
                sin({$srcLat}) * sin({$dstLat}) + 
                cos({$srcLat}) * cos({$dstLat}) * cos({$dstLng} - {$srcLng})
            ) * " . self::EARTH_RADIUS_MILES .
        ")";
    }

}
