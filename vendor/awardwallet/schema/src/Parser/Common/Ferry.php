<?php

namespace AwardWallet\Schema\Parser\Common;

use AwardWallet\Schema\Parser\Component\Options;
use Psr\Log\LoggerInterface;

class Ferry extends Itinerary
{

    /** @var FerrySegment[] $segments */
    protected $segments;
    protected $_seg_cnt;
    /**
     * @parsed KeyValue
     * @attr unique=true
     * @attr key=Field
     * @attr key_type=basic
     * @attr key_length=short
     * @attr val=Boolean
     */
    protected $ticketNumbers;
    /**
     * @parsed Boolean
     */
    protected $areTicketsMasked;
    /**
     * @parsed KeyValue
     * @attr unique=strict
     * @attr key=Field
     * @attr key_type=confno
     * @attr key_length=short
     * @attr key_minlength=3
     * @attr val=Field
     * @attr val_type=basic
     * @attr val_length=medium
     */
    protected $confirmationNumbers;
    /**
     * @parsed Boolean
     */
    protected $noConfirmationNumber;
    protected $primaryConfirmationKey;
    /**
     * @parsed DateTime
     * @attr seconds=true
     */
    protected $reservationDate;
    /**
     * @parsed Field
     * @attr type=clean
     * @attr length=medium
     */
    protected $status;
    /**
     * @parsed KeyValue
     * @attr unique=false
     * @attr key=Field
     * @attr key_type=basic
     * @attr key_length=medium
     * @attr key_minlength=2
     * @attr val=Boolean
     */
    protected $travellers;
    /**
     * @parsed Boolean
     */
    protected $areNamesFull;
    /**
     * @parsed Boolean
     */
    protected $cancelled;

    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=medium
     */
    protected $providerKeyword;
    /**
     * @parsed Field
     * @attr type=provider
     */
    protected $providerCode;
    /**
     * @parsed KeyValue
     * @attr unique=true
     * @attr key=Field
     * @attr key_type=phone
     * @attr val=Field
     * @attr val_type=basic
     * @attr val_length=medium
     */
    protected $providerPhones;
    /**
     * @parsed KeyValue
     * @attr unique=true
     * @attr key=Field
     * @attr key_type=clean
     * @attr key_length=short
     * @attr val=Boolean
     */
    protected $accountNumbers;
    /**
     * @parsed Boolean
     */
    protected $areAccountMasked;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=medium
     */
    protected $earnedAwards;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=extra
     */
    protected $notes;
    /**
     * @parsed Boolean
     */
    protected $allowTzCross;

    public function __construct($name, LoggerInterface $logger, Options $options = null)
    {
        parent::__construct($name, $logger, $options);
        $this->segments = [];
        $this->_seg_cnt = 0;
    }

    /**
     * @return FerrySegment[]
     */
    public function getSegments()
    {
        return $this->segments;
    }

    /**
     * @return FerrySegment
     */
    public function addSegment()
    {
        $segment = new FerrySegment(sprintf('%s-%d', $this->_name, $this->_seg_cnt), $this->logger, $this->_options);
        $this->_seg_cnt++;
        $this->segments[] = $segment;
        $this->logDebug(sprintf('%s: created new segment %s', $this->_name, $segment->getId()));
        return $segment;
    }

    /**
     * @param FerrySegment $segment
     * @return Ferry
     */
    public function removeSegment(FerrySegment $segment)
    {
        $idx = null;
        foreach ($this->segments as $key => $seg) {
            if (strcmp($seg->getId(), $segment->getId()) === 0) {
                $idx = $key;
                break;
            }
        }
        if (isset($idx)) {
            unset($this->segments[$idx]);
            $this->logDebug(sprintf('%s: removed segment %s', $this->_name, $segment->getId()));
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getTicketNumbers()
    {
        return $this->ticketNumbers;
    }

    /**
     * @param mixed $ticketNumbers
     * @param boolean $areMasked
     * @return Ferry
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setTicketNumbers($ticketNumbers, $areMasked)
    {
        if (!is_array($ticketNumbers)) {
            $this->invalid('setTicketNumbers expects array');
        } else {
            foreach ($ticketNumbers as $num) {
                $this->addTicketNumber($num, $areMasked);
            }
        }
        return $this;
    }

    /**
     * @param $number
     * @param boolean $isMasked
     * @return Ferry
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function addTicketNumber($number, $isMasked)
    {
        $this->addKeyValue($number, $isMasked, 'ticketNumbers', false, true, []);
        return $this;
    }

    /**
     * @param string $number
     * @return Ferry
     */
    public function removeTicketNumber($number)
    {
        $this->removeItem($number, 'ticketNumbers');
        return $this;
    }

    /**
     * @return boolean
     */
    public function getAreTicketsMasked()
    {
        return $this->areTicketsMasked;
    }

    /**
     * @param boolean $areTicketsMasked
     * @return Ferry
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setAreTicketsMasked($areTicketsMasked)
    {
        $this->setProperty($areTicketsMasked, 'areTicketsMasked', false, false);
        return $this;
    }

    /**
     * @return boolean
     */
    public function getAllowTzCross()
    {
        return $this->allowTzCross;
    }

    /**
     * @param boolean $allowTzCross
     * @return Itinerary
     */
    public function setAllowTzCross($allowTzCross)
    {
        $this->setProperty($allowTzCross, 'allowTzCross', false, false);
        return $this;
    }

    /**
     * @return array
     */
    protected function getChildren()
    {
        return array_merge(parent::getChildren(), $this->segments);
    }

    public function validate(bool $hasUpperConfNo)
    {
        $this->validateArrays();
        foreach($this->segments as $segment) {
            $this->valid = $this->valid && $segment->validateBasic($this->allowTzCross);
            if (!$this->cancelled)
                $this->valid = $this->valid && $segment->validateData();
        }
        if (!$this->cancelled) {
            if (count($this->segments) === 0)
                $this->invalid('missing segments');
            if (empty($this->confirmationNumbers) && !$this->noConfirmationNumber)
                $this->invalid('missing confirmation number');
        }
        else {
            if (!$hasUpperConfNo && !$this->hasConfNo())
                $this->invalid('missing confirmation number');
        }
        if ($this->travelAgency)
            $this->valid = $this->travelAgency->validate() && $this->valid;
        if ($this->price)
            $this->valid = $this->price->getValid() && $this->valid;
        $this->checkTravellers();
        return $this->valid;
    }

    public function sortSegments()
    {
        $this->segments = array_values($this->segments);
        usort($this->segments, function (FerrySegment $a, FerrySegment $b) {
            $date1 = $a->getDepDate() ?? $a->getArrDate() ?? 0;
            $date2 = $b->getDepDate() ?? $b->getArrDate() ?? 0;
            return $date1 - $date2;
        });
    }

}