<?php


namespace AwardWallet\Common\Parsing\Solver\Helper;


use AwardWallet\Common\Parsing\Solver\Exception;
use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Schema\Parser\Common\BaseSegment;
use Psr\Log\LoggerInterface;

class SegmentHelper {

	/**
	 * @var DataHelper
	 */
	protected $dh;
    /**
     * @var LoggerInterface
     */
	private $logger;

	public function __construct(DataHelper $dh, LoggerInterface $logger) {
		$this->dh = $dh;
		$this->logger = $logger;
	}

	public function parseSegmentLocation(BaseSegment $s, Extra $extra, $useAirportCodes, $useStationCodes) {
        $parsedAirCode = false;
		if ($useAirportCodes && $s->getDepCode())
			$parsedAirCode = $this->dh->parseAirCode($s->getDepCode(), $extra);
		if (!$parsedAirCode) {
            $parsedStationCode = false;
            if ($useStationCodes && $s->getDepCode())
                $parsedStationCode = $this->dh->parseStationCode($s->getDepCode(), $s->getDepName(), $extra);
            if (!$parsedStationCode){
                if ($s->getDepAddress())
                    $this->dh->parseAddress($s->getDepAddress(), $extra);
                elseif ($s->getDepName()) {
                    $this->dh->parseAddress($s->getDepName(), $extra, $s->getDepGeoTip());
                }
            }
        }
        $parsedAirCode = false;
		if ($useAirportCodes && $s->getArrCode())
            $parsedAirCode = $this->dh->parseAirCode($s->getArrCode(), $extra);
        if (!$parsedAirCode) {
            $parsedStationCode = false;
            if ($useStationCodes && $s->getArrCode())
                $parsedStationCode = $this->dh->parseStationCode($s->getArrCode(), $s->getArrName(), $extra);
            if (!$parsedStationCode) {
                if ($s->getArrAddress())
                    $this->dh->parseAddress($s->getArrAddress(), $extra);
                elseif ($s->getArrName()) {
                    $this->dh->parseAddress($s->getArrName(), $extra, $s->getArrGeoTip());
                }
            }
        }
	}

	public function checkValidRoute(BaseSegment $s, Extra $extra, $type)
    {
        $countries = [['United States', 'Mexico', 'Canada'], ['Australia']];
        $limits = [
            'train' => 8000,
            'bus' => 500,
            'transfer' => 100,
        ];
        foreach([$s->getDepCode(), $s->getDepAddress(), $s->getDepName()] as $str)
            if (!empty($str) && ($dep = $extra->data->getGeo($str)))
                break;
        foreach([$s->getArrCode(), $s->getArrAddress(), $s->getArrName()] as $str)
            if (!empty($str) && ($arr = $extra->data->getGeo($str)))
                break;
        if (isset($dep) && isset($arr)) {
            if ($dep->country && $arr->country) {
                foreach($countries as $NA) {
                    $c = 0;
                    foreach ([$dep->country, $arr->country] as $str) {
                        if (in_array($str, $NA)) {
                            $c++;
                        }
                    }
                    if ($c === 1) {
                        throw Exception::impossibleRoute($dep->country, $arr->country, $s->getId());
                    }
                }
            }
            if ($dep->lat && $dep->lng && $arr->lat && $arr->lng) {
                $lat = $dep->lat - $arr->lat;
                $lng = $dep->lng - $arr->lng;
                $diff = $lat * $lat + $lng * $lng;
                if ($diff > $limits[$type]) {
                    $this->logger->info('suspicious distance detected', [
                        'component' => 'SegmentHelper',
                        'type' => $type,
                        'distance' => $diff,
                    ]);
                    throw Exception::impossibleRoute(
                        sprintf('%s, %s', $dep->country, $dep->city),
                        sprintf('%s, %s', $arr->country, $arr->city),
                        $s->getId());
                }
            }


        }
    }

    public function getPlaceTimeZoneOffset($namesArr, Extra $extra)
    {
        foreach($namesArr as $name)
            if ($name && ($geo = $extra->data->getGeo($name)) && ($tz = $geo->tz))
                return $tz;
        return null;
    }

}