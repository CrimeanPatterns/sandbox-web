<?php

namespace AwardWallet\Schema\Parser\Component;


use AwardWallet\Schema\Parser\Common\AwardRedemption;
use AwardWallet\Schema\Parser\Common\BoardingPass;
use AwardWallet\Schema\Parser\Common\CardPromo;
use AwardWallet\Schema\Parser\Common\Coupon;
use AwardWallet\Schema\Parser\Common\Cruise;
use AwardWallet\Schema\Parser\Common\Event;
use AwardWallet\Schema\Parser\Common\Ferry;
use AwardWallet\Schema\Parser\Common\Flight;
use AwardWallet\Schema\Parser\Common\Hotel;
use AwardWallet\Schema\Parser\Common\Itinerary;
use AwardWallet\Schema\Parser\Common\OneTimeCode;
use AwardWallet\Schema\Parser\Common\Parking;
use AwardWallet\Schema\Parser\Common\Rental;
use AwardWallet\Schema\Parser\Common\Shortcut\ItineraryCreate;
use AwardWallet\Schema\Parser\Common\Bus;
use AwardWallet\Schema\Parser\Common\Statement;
use AwardWallet\Schema\Parser\Common\Train;
use AwardWallet\Schema\Parser\Common\Transfer;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Master extends Base {

    /** @var Itinerary[] $itineraries */
    protected $itineraries;
    /** @var BoardingPass[] $bPasses */
    protected $bPasses;
    /** @var Statement $statement */
    protected $statement;
    /** @var OneTimeCode $oneTimeCode */
    protected $oneTimeCode;
    /** @var Coupon[] */
    protected $coupons;
    /** @var AwardRedemption[] $awardRedemptions*/
    protected $awardRedemptions;
    /** @var CardPromo $cardPromo*/
    protected $cardPromo;

    /**
     * @parsed Boolean
     */
    protected $noItineraries;

    /**
     * @parsed Boolean
     */
    protected $isJunk;

    /** @var ItineraryCreate $create */
    protected $_create;

    protected $_cnt;

    public function __construct($name, Options $options = null) {
        if (!isset($options))
            $options = new Options();
        parent::__construct($name, new Logger('parser', [new PsrHandler(new NullLogger())]), $options);
        $this->itineraries = [];
        $this->bPasses = [];
        $this->coupons = [];
        $this->awardRedemptions = [];
        $this->_create = new ItineraryCreate($this);
        $this->_cnt = 0;
    }

    public function addPsrLogger(LoggerInterface $logger)
    {
        $this->logger->pushHandler(new PsrHandler($logger));
    }

    /**
     * @return ItineraryCreate
     */
    public function add() {
        return $this->_create;
    }

    /**
     * @return Flight
     */
    public function createFlight() {
        $n = new Flight('flight-' . $this->_cnt, $this->logger, $this->_options);
        $this->itineraries[] = $n;
        $this->_cnt++;
        return $n;
    }

    /**
     * @return Rental
     */
    public function createRental() {
        $n = new Rental('rental-' . $this->_cnt, $this->logger, $this->_options);
        $this->itineraries[] = $n;
        $this->_cnt++;
        return $n;
    }

    /**
     * @return Hotel
     */
    public function createHotel() {
        $n = new Hotel('hotel-' . $this->_cnt, $this->logger, $this->_options);
        $this->itineraries[] = $n;
        $this->_cnt++;
        return $n;
    }

    /**
     * @return Bus
     */
    public function createBus() {
        $n = new Bus('bus-'.$this->_cnt, $this->logger, $this->_options);
        $this->itineraries[] = $n;
        $this->_cnt++;
        return $n;
    }

    /**
     * @return Train
     */
    public function createTrain() {
        $n = new Train('train-'.$this->_cnt, $this->logger, $this->_options);
        $this->itineraries[] = $n;
        $this->_cnt++;
        return $n;
    }

    /**
     * @return Transfer
     */
    public function createTransfer() {
        $n = new Transfer('transfer-'.$this->_cnt, $this->logger, $this->_options);
        $this->itineraries[] = $n;
        $this->_cnt++;
        return $n;
    }

    /**
     * @return Ferry
     */
    public function createFerry() {
        $n = new Ferry('ferry-'.$this->_cnt, $this->logger, $this->_options);
        $this->itineraries[] = $n;
        $this->_cnt++;
        return $n;
    }

    /**
     * @return Cruise
     */
    public function createCruise() {
        $n = new Cruise('cruise-'.$this->_cnt, $this->logger, $this->_options);
        $this->itineraries[] = $n;
        $this->_cnt++;
        return $n;
    }

    /**
     * @return Event
     */
    public function createEvent() {
        $n = new Event('event-'.$this->_cnt, $this->logger, $this->_options);
        $this->itineraries[] = $n;
        $this->_cnt++;
        return $n;
    }

    /**
     * @return Parking
     */
    public function createParking() {
        $n = new Parking('parking-'.$this->_cnt, $this->logger, $this->_options);
        $this->itineraries[] = $n;
        $this->_cnt++;
        return $n;
    }

    public function removeItinerary(Itinerary $item) {
        $found = null;
        foreach($this->itineraries as $k => $it)
            if ($it->getId() === $item->getId()) {
                $found = $k;
                break;
            }
        if (null !== $found) {
            unset($this->itineraries[$found]);
        }
    }

    public function clearItineraries()
    {
        $this->logDebug('deleting all itineraries');
        $this->itineraries = [];
    }

    /**
     * @return BoardingPass
     */
    public function createBoardingPass() {
        $bp = new BoardingPass('bp-'.$this->_cnt, $this->logger, $this->_options);
        $this->bPasses[] = $bp;
        $this->_cnt++;
        return $bp;
    }

    /**
     * @return Statement
     */
    public function createStatement() {
        $this->statement = new Statement('statement', $this->logger, $this->_options);
        return $this->statement;
    }

    /**
     * @return OneTimeCode
     */
    public function createOneTimeCode() {
        $this->oneTimeCode = new OneTimeCode('oneTimeCode', $this->logger, $this->_options);
        return $this->oneTimeCode;
    }

    /**
     * @return Coupon
     */
    public function addCoupon()
    {
        $cp = new Coupon('coupon-' . $this->_cnt, $this->logger, $this->_options);
        $this->coupons[] = $cp;
        $this->_cnt++;
        return $cp;
    }

    /**
     * @return AwardRedemption
     */
    public function createAwardRedemption() {
        $ar = new AwardRedemption('awardRedemption-'.$this->_cnt, $this->logger, $this->_options);
        $this->awardRedemptions[] = $ar;
        $this->_cnt++;
        return $ar;
    }

    /**
     * @return CardPromo
     */
    public function createCardPromo() {
        $this->cardPromo = new CardPromo('cardPromo', $this->logger, $this->_options);
        return $this->cardPromo;
    }


    /**
     * @return boolean
     */
    public function getNoItineraries() {
        return $this->noItineraries;
    }

    /**
     * @param boolean $noItineraries
     * @return Master
     * @throws InvalidDataException
     */
    public function setNoItineraries($noItineraries) {
        $this->setProperty($noItineraries, 'noItineraries', false, false);
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsJunk() {
        return $this->isJunk;
    }

    /**
     * @param boolean $isJunk
     * @return Master
     * @throws InvalidDataException
     */
    public function setIsJunk($isJunk) {
        $this->setProperty($isJunk, 'isJunk', false, false);
        return $this;
    }

    /**
     * @return Itinerary[]
     */
    public function getItineraries() {
        return $this->itineraries;
    }

    /**
     * @return BoardingPass[]
     */
    public function getBPasses() {
        return $this->bPasses;
    }

    /**
     * @return Statement
     */
    public function getStatement() {
        return $this->statement;
    }

    /**
     * @return OneTimeCode
     */
    public function getOneTimeCode(){
        return $this->oneTimeCode;
    }

    /**
     * @return Coupon[]
     */
    public function getCoupons(): array
    {
        return $this->coupons;
    }

    /**
     * @return AwardRedemption[]
     */
    public function getAwardRedemption(): array
    {
        return $this->awardRedemptions;
    }

    /**
     * @return CardPromo
     */
    public function getCardPromo(): ?CardPromo
    {
        return $this->cardPromo;
    }

    /**
     * @return void
     * @throws InvalidDataException
     */
    public function validate() {
        if ($this->isJunk === true && (count($this->itineraries) > 0 || count($this->bPasses) > 0 || count($this->coupons) > 0 || isset($this->statement) || isset($this->oneTimeCode)))
            $this->invalid('email with info (itineraries/boarding pass/etc.) can\'t be junk');
        if ($this->noItineraries === true && count($this->itineraries) > 0)
            $this->invalid('data with itineraries cannot have noItineraries=true');
        if (
            count($this->itineraries) === 0 && count($this->bPasses) === 0 && count($this->coupons) === 0 && !isset($this->statement)
            && $this->noItineraries !== true && $this->isJunk !== true && !isset($this->oneTimeCode) && count($this->awardRedemptions) === 0
            && !isset($this->cardPromo)
        )
            $this->invalid('empty data');
        foreach($this->itineraries as $it)
            $this->valid = $it->validate($this->hasConfNo()) && $this->valid;
        foreach($this->bPasses as $bpass)
            $this->valid = $bpass->validate() && $this->valid;
        if ($this->statement)
            $this->valid = $this->statement->validate() && $this->valid;
        if ($this->oneTimeCode)
            $this->valid = $this->oneTimeCode->validate() && $this->valid;
        foreach($this->awardRedemptions as $ar)
            $this->valid = $ar->validate() && $this->valid;
        foreach($this->coupons as $cp) {
            $this->valid = $cp->validate() && $this->valid;
        }
        if ($this->cardPromo)
            $this->valid = $this->cardPromo->validate() && $this->valid;
    }

    public function checkValid()
    {
        $this->validate();
        return $this->valid;
    }

    protected function hasConfNo()
    {
        return false;
    }

    protected function fromArrayChildren(array $arr)
    {
        parent::fromArrayChildren($arr);
        if (isset($arr['itineraries']))
            foreach($arr['itineraries'] as $it) {
                $method = 'create'.ucfirst($it['type']);
                /** @var Itinerary $nit */
                $nit = $this->$method();
                $nit->fromArray($it);
            }
        if (isset($arr['statement']))
            $this->createStatement()->fromArray($arr['statement']);
        if (isset($arr['bPasses']))
            foreach($arr['bPasses'] as $bp)
                $this->createBoardingPass()->fromArray($bp);
        if (isset($arr['oneTimeCode']))
            $this->createOneTimeCode()->fromArray($arr['oneTimeCode']);
        if (isset($arr['coupons'])) {
            foreach($arr['coupons'] as $cp) {
                $this->addCoupon()->fromArray($cp);
            }
        }
    }

    /**
     * @return Base[]
     */
    protected function getChildren() {
        $r = array_merge($this->itineraries, $this->bPasses, $this->coupons);
        if (isset($this->statement))
            $r[] = $this->statement;
        if (isset($this->oneTimeCode))
            $r[] = $this->oneTimeCode;
        return $r;
    }


}