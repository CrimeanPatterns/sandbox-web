<?php


namespace AwardWallet\Common\Parsing\Solver\Itinerary;

use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Common\Parsing\Solver\Helper\AddressHelper;
use AwardWallet\Common\Parsing\Solver\Helper\DataHelper;
use AwardWallet\Common\Parsing\Solver\Helper\ExtraHelper;
use AwardWallet\Common\Parsing\Solver\Helper\SegmentHelper;
use AwardWallet\Schema\Parser\Common\Itinerary;

class Bus extends ItinerarySolver {

	/**
	 * @var SegmentHelper
	 */
	protected $sh;

    /**
     * @var AddressHelper
     */
    protected $ah;
	public function __construct(ExtraHelper $eh, DataHelper $dh, SegmentHelper $sh, AddressHelper $ah) {
		parent::__construct($eh, $dh);
		$this->sh = $sh;
		$this->ah = $ah;
	}

	public function solveItinerary(Itinerary $it, Extra $extra) {
		/** @var \AwardWallet\Schema\Parser\Common\Bus $it */
		foreach($it->getSegments() as $s) {
            $this->sh->parseSegmentLocation($s, $extra, true, true);
            $this->sh->checkValidRoute($s, $extra, 'bus');

            if ((!empty($s->getArrDate()) && ($s->getNoDepDate() === true))
                || (!empty($s->getDepDate()) && ($s->getNoArrDate() === true))
            ) {
                if (!empty($s->getDepCode()) && $extra->data->existsGeo($s->getDepCode())
                    && ($dep = $extra->data->getGeo($s->getDepCode()))
                    && !empty($dep->lat) && !empty($dep->lng)
                ) {
                    $sPoint = $dep->lat . ',' . $dep->lng;
                } elseif (!empty($s->getDepAddress())) {
                    $sPoint = $s->getDepAddress();
                } elseif (!empty($s->getDepName())) {
                    $sPoint = $s->getDepName();
                }
                if (!empty($s->getArrCode()) && $extra->data->existsGeo($s->getArrCode())
                    && ($arr = $extra->data->getGeo($s->getArrCode()))
                    && !empty($arr->lat) && !empty($arr->lng)
                ) {
                    $ePoint = $arr->lat . ',' . $arr->lng;
                } elseif (!empty($s->getArrAddress())) {
                    $ePoint = $s->getArrAddress();
                } elseif (!empty($s->getArrName())) {
                    $ePoint = $s->getArrName();
                }
                if (!empty($sPoint) && !empty($ePoint)) {
                    $duration = $this->ah->parseDuration($sPoint, $ePoint, 'transit', 'bus');
                    if (!empty($duration)) {
                        $minutes = round($duration / 60);
                        $delta = min(round(0.15 * $minutes), 30);
                        $minutes += $delta;
                        if ($s->getNoArrDate() === true) {
                            $s->setArrDate(strtotime("+{$minutes} minutes", $s->getDepDate()));
                        } else {
                            $s->setDepDate(strtotime("-{$minutes} minutes", $s->getArrDate()));
                        }
                    }
                }
            }
        }
	}

}