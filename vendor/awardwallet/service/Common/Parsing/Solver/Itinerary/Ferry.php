<?php

namespace AwardWallet\Common\Parsing\Solver\Itinerary;

use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Common\Parsing\Solver\Helper\DataHelper;
use AwardWallet\Common\Parsing\Solver\Helper\ExtraHelper;
use AwardWallet\Common\Parsing\Solver\Helper\SegmentHelper;
use AwardWallet\Schema\Parser\Common\Itinerary;

class Ferry extends ItinerarySolver
{
    /**
     * @var SegmentHelper
     */
    protected $sh;

    public function __construct(ExtraHelper $eh, DataHelper $dh, SegmentHelper $sh)
    {
        parent::__construct($eh, $dh);
        $this->sh = $sh;
    }

    public function solveItinerary(Itinerary $it, Extra $extra)
    {
        /** @var \AwardWallet\Schema\Parser\Common\Ferry $it */
        foreach ($it->getSegments() as $s) {
            $this->sh->parseSegmentLocation($s, $extra, false, false);
        }
    }

}