<?php


namespace AwardWallet\Common\Parsing\Solver\Itinerary;

use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Common\Parsing\Solver\Helper\DataHelper;
use AwardWallet\Common\Parsing\Solver\Helper\ExtraHelper;
use AwardWallet\Schema\Parser\Common\Itinerary;
use Psr\Log\LoggerInterface;

class Hotel extends ItinerarySolver {

    public function __construct(ExtraHelper $eh, DataHelper $dh, LoggerInterface $logger)
    {
        parent::__construct($eh, $dh);
        $this->logger = $logger;
    }

    public function solveItinerary(Itinerary $it, Extra $extra) {
		/** @var \AwardWallet\Schema\Parser\Common\Hotel $it */
		if (is_null($it->getHouse()) && ($it->getProviderCode() === 'airbnb' || $extra->provider->code === 'airbnb'))
		    $it->setHouse(true);
        if (!$it->getAddress() && $it->getDetailedAddress() && $it->getDetailedAddress()->isFull()) {
            $it->setAddress($it->getDetailedAddress()->implode());
        }
        if (!empty($it->getHotelName()) && !$it->getHouse()) {
            $this->dh->lookupPlace($it->getHotelName(), $extra);
        }
        if (empty($it->getHotelName()) || !$extra->data->getGeo($it->getHotelName())) {
            if ($it->getAddress()) {
                $this->dh->parseAddress($it->getAddress(), $extra);
            }
            if ((empty($it->getAddress()) || !$extra->data->getGeo($it->getAddress())) && $it->getHotelName() && !$it->getHouse()) {
                $this->dh->parsePlace($it->getHotelName(), $extra);
            }
        }

        foreach ($it->getRooms() as $room) {
            if (!empty($room->getRate())
                && (count(explode(';', $room->getRate())) > 2
                    || count(explode('|', $room->getRate())) > 2)
            ) {
                $this->warning('perhaps string with the rate should be distributed to the array of rates');
                break;
            }
        }

        if (!empty($it->getCancellation()) && (null === $it->getNonRefundable()) && (null === $it->getDeadline()) && (preg_match('/\bno[tn][- ]?refundable\b/i', $it->getCancellation()) > 0))
		    $this->warning('nonRefundable should probably be set');
	}

}