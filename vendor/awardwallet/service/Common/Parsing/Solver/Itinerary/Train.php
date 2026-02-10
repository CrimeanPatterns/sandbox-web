<?php


namespace AwardWallet\Common\Parsing\Solver\Itinerary;

use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Common\Parsing\Solver\Helper\DataHelper;
use AwardWallet\Common\Parsing\Solver\Helper\DateCorrector;
use AwardWallet\Common\Parsing\Solver\Helper\ExtraHelper;
use AwardWallet\Common\Parsing\Solver\Helper\SegmentHelper;
use AwardWallet\Schema\Parser\Common\Itinerary;
use Psr\Log\LoggerInterface;

class Train extends ItinerarySolver {

	/**
	 * @var SegmentHelper
	 */
	protected $sh;

	public function __construct(ExtraHelper $eh, DataHelper $dh, SegmentHelper $sh, LoggerInterface $logger) {
		parent::__construct($eh, $dh);
		$this->sh = $sh;
		$this->logger = $logger;
	}

	public function solveItinerary(Itinerary $it, Extra $extra) {
		/** @var \AwardWallet\Schema\Parser\Common\Train $it */
		$corrector = new DateCorrector();
		$prev = null;
		foreach($it->getSegments() as $s) {
            if ($extra->settings->correctDatesBetweenSegments && !$s->getDatesStrict() && !empty($prev) && $date = $corrector->fixDateNextSegment($prev, $s->getDepDate()))
                $s->setDepDate($date);
            $this->sh->parseSegmentLocation($s, $extra, $extra->provider->kind === 1, true);
            $depTz = $this->sh->getPlaceTimeZoneOffset([$s->getDepCode(), $s->getDepAddress(), $s->getDepName()], $extra);
            $arrTz = $this->sh->getPlaceTimeZoneOffset([$s->getArrCode(), $s->getArrAddress(), $s->getArrName()], $extra);
            if (!isset($depTz) || !isset($arrTz))
                $depTz = $arrTz = null;
            if (!$s->getDatesStrict() && ($date = $corrector->fixDateOvernightSegment($s->getDepDate(), $depTz, $s->getArrDate(), $arrTz)))
                $s->setArrDate($date);
            $this->sh->checkValidRoute($s, $extra, 'train');
        }

	}

}