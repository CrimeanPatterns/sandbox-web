<?php


namespace AwardWallet\Common\Parsing\Solver\Itinerary;

use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Common\Parsing\Solver\Helper\DataHelper;
use AwardWallet\Common\Parsing\Solver\Helper\ExtraHelper;
use AwardWallet\Common\Parsing\Solver\Helper\FlightHelper;
use AwardWallet\Schema\Parser\Common\Itinerary;
use Psr\Log\LoggerInterface;

class Rental extends ItinerarySolver {

    /** @var FlightHelper  */
    private $fh;

    public function __construct(ExtraHelper $eh, DataHelper $dh, FlightHelper $fh, LoggerInterface $logger)
    {
        parent::__construct($eh, $dh);
        $this->fh = $fh;
        $this->logger = $logger;
    }

    public function solveItinerary(Itinerary $it, Extra $extra) {
		/** @var \AwardWallet\Schema\Parser\Common\Rental $it */
        if (!$it->getPickUpLocation() && $it->getPickUpDetailedAddress() && $it->getPickUpDetailedAddress()->isFull()) {
            $it->setPickUpLocation($it->getPickUpDetailedAddress()->implode());
        }
		if ($it->getPickUpLocation()) {
            $this->solveLocation($it->getPickUpLocation(), $extra);
        }
        if (!$it->getDropOffLocation() && $it->getDropOffDetailedAddress() && $it->getDropOffDetailedAddress()->isFull()) {
            $it->setDropOffLocation($it->getDropOffDetailedAddress()->implode());
        }
		if ($it->getDropOffLocation() && !$extra->data->getGeo($it->getDropOffLocation())) {
            $this->solveLocation($it->getDropOffLocation(), $extra);
        }
		if (!empty($it->getCompany())) {
		    $solved = $extra->data->getProvider($it->getCompany());
		    if (!$solved)
                $solved = $this->eh->solveRentalCompany($it->getCompany());
            if ($solved) {
                $this->logger->info('solved rental company', ['company' => $it->getCompany(), 'solved' => $solved->code, 'component' => 'RentalSolver']);
                if ('awardwallet' === $extra->context->partnerLogin) {
                    $it->setCompany($solved->name);
                    if (!$it->getProviderCode()) {
                        $it->setProviderCode($solved->code);
                        if (!$extra->data->existsProvider($solved->code)) {
                            $this->eh->solveProvider(null, $it->getProviderCode(), $extra);
                        }
                        if (count($it->getProviderPhones()) === 0 && $phone = $this->eh->getProviderPhone($it->getProviderCode(), null)) {
                            $it->addProviderPhone($phone);
                        }
                    }
                }
            }
            else {
                $this->logger->info('failed to solve rental company', ['company' => $it->getCompany(), 'component' => 'RentalSolver']);
            }
        }
	}

    private function solveLocation(string $location, Extra $extra)
    {
        $air = $this->fh->solveAirCode($location, $extra);
        if ($air) {
            $this->dh->parseAirCode($air, $extra);
            if ($airData = $extra->data->getGeo($air)) {
                $extra->data->addGeo($location, $airData);
            }
        }
        if (!$extra->data->getGeo($location)) {
            $this->dh->parseAddress($location, $extra);
        }
    }

}
