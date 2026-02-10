<?php


namespace AwardWallet\Common\Parsing\Solver\Itinerary;

use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Common\Parsing\Solver\Helper\DataHelper;
use AwardWallet\Common\Parsing\Solver\Helper\ExtraHelper;
use AwardWallet\Schema\Parser\Common\Itinerary;
use Psr\Log\LoggerInterface;

class Event extends ItinerarySolver {

    public function __construct(ExtraHelper $eh, DataHelper $dh, LoggerInterface $logger)
    {
        parent::__construct($eh, $dh);
        $this->logger = $logger;
    }

    public function solveItinerary(Itinerary $it, Extra $extra) {
		/** @var \AwardWallet\Schema\Parser\Common\Event $it */
		if ($it->getAddress())
			$this->dh->parseAddress($it->getAddress(), $extra);
		$this->logger->info('event itinerary', ['eventType' => $it->getEventType()]);
	}

}