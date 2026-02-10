<?php


namespace AwardWallet\Common\Parsing\Solver\Itinerary;

use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Common\Parsing\Solver\Helper\AddressHelper;
use AwardWallet\Common\Parsing\Solver\Helper\DataHelper;
use AwardWallet\Common\Parsing\Solver\Helper\ExtraHelper;
use AwardWallet\Common\Parsing\Solver\Helper\SegmentHelper;
use AwardWallet\Schema\Parser\Common\Itinerary;

class Transfer extends ItinerarySolver {

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
        /** @var \AwardWallet\Schema\Parser\Common\Transfer $it */
        foreach ($it->getSegments() as $s) {
            $this->sh->parseSegmentLocation($s, $extra, true, false);
            $this->sh->checkValidRoute($s, $extra, 'transfer');

            if ((!empty($s->getArrDate()) && ($s->getNoDepDate() === true))
                || (!empty($s->getDepDate()) && ($s->getNoArrDate() === true))
            ) {
                $sPoint = $this->getPointForDirections([
                    $s->getDepCode(),
                    $s->getDepAddress(),
                    $s->getDepName()], $extra);
                $ePoint = $this->getPointForDirections([
                    $s->getArrCode(),
                    $s->getArrAddress(),
                    $s->getArrName()
                ], $extra);
                if (!empty($sPoint) && !empty($ePoint)) {
                    $duration = $this->ah->parseDuration($sPoint, $ePoint);
                    if (!empty($duration)) {
                        $minutes = round($duration / 60);
                        $delta = min(round(0.15 * $minutes), 10);
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

    private function getPointForDirections(array $points, Extra $extra): ?string
    {
        foreach($points as $point) {
            if (!empty($point)
                && $extra->data->existsGeo($point)
                && ($dep = $extra->data->getGeo($point))
                && !empty($dep->lat) && !empty($dep->lng)) {
                return $dep->lat . ',' . $dep->lng;
            }
        }
        return $points[1] ?? $points[2];
    }

}