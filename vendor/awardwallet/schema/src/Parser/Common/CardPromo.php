<?php


namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Schema\Parser\Component\Base;

class CardPromo extends Base
{
    /**
     * @parsed Field
     * @attr length=medium
     */
    protected $cardName;

    /**
     * @parsed Field
     * @attr length=medium
     */
    protected $cardOwner;

    /**
     * @parsed Field
     * @attr length=short
     * @attr type=number
     */
    protected $cardMemberSince;

    /**
     * @parsed Field
     * @attr length=short
     * @attr type=number
     */
    protected $lastDigits;

    /**
     * @parsed Field
     * @attr length=short
     */
    protected $multiplier;

    /**
     * @parsed DateTime
     */
    protected $offerDeadline;

    /**
     * @parsed DateTime
     */
    protected $applicationDeadline;

    /**
     * @parsed Field
     * @attr length=long
     */
    protected $applicationURL;

    /**
     * @parsed Field
     * @attr length=short
     * @attr type=number
     */
    protected $limitAmount;

    /**
     * @parsed Field
     * @attr length=short
     */
    protected $limitCurrency;

    /**
     * @parsed Arr
     * @attr item=Field
     * @attr unique=true
     *
     */
    protected $bonusCategories;

    /**
     * @return mixed
     */
    public function getCardName()
    {
        return $this->cardName;
    }

    /**
     * @param mixed $cardName
     * @return CardPromo
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setCardName($cardName): CardPromo
    {
        $this->setProperty($cardName, 'cardName', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCardOwner()
    {
        return $this->cardOwner;
    }

    /**
     * @param mixed $cardName
     * @return CardPromo
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setCardOwner($cardOwner): CardPromo
    {
        $this->setProperty($cardOwner, 'cardOwner', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCardMemberSince()
    {
        return $this->cardMemberSince;
    }

    /**
     * @param mixed $cardMemberSince
     * @return CardPromo
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setCardMemberSince($cardMemberSince): CardPromo
    {
        $this->setProperty($cardMemberSince, 'cardMemberSince', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastDigits()
    {
        return $this->lastDigits;
    }

    /**
     * @param mixed $lastDigits
     * @return CardPromo
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setLastDigits($lastDigits): CardPromo
    {
        $this->setProperty($lastDigits, 'lastDigits', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMultiplier()
    {
        return $this->multiplier;
    }

    /**
     * @param mixed $multiplier
     * @return CardPromo
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setMultiplier($multiplier): CardPromo
    {
        $this->setProperty($multiplier, 'multiplier', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOfferDeadline()
    {
        return $this->offerDeadline;
    }

    /**
     * @param mixed $offerDeadline
     * @return CardPromo
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setOfferDeadline($offerDeadline): CardPromo
    {
        $this->setProperty($offerDeadline, 'offerDeadline', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getApplicationDeadline()
    {
        return $this->applicationDeadline;
    }

    /**
     * @param mixed $offerDeadline
     * @return CardPromo
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setApplicationDeadline($applicationDeadline): CardPromo
    {
        $this->setProperty($applicationDeadline, 'applicationDeadline', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getApplicationURL()
    {
        return $this->applicationURL;
    }

    /**
     * @param mixed $applicationURL
     * @return CardPromo
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setApplicationURL($applicationURL): CardPromo
    {
        $this->setProperty($applicationURL, 'applicationURL', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLimitAmount()
    {
        return $this->limitAmount;
    }

    /**
     * @param mixed $limitAmount
     * @return CardPromo
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setLimitAmount($limitAmount): CardPromo
    {
        $this->setProperty($limitAmount, 'limitAmount', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLimitCurrency()
    {
        return $this->limitCurrency;
    }

    /**
     * @param mixed $limitCurrency
     * @return CardPromo
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setLimitCurrency($limitCurrency): CardPromo
    {
        $this->setProperty($limitCurrency, 'limitCurrency', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBonusCategories()
    {
        return $this->bonusCategories;
    }

    /**
     * @param mixed $bonusCategories
     * @return CardPromo
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setBonusCategories($bonusCategories): CardPromo
    {
        $this->setProperty($bonusCategories, 'bonusCategories', false, false);
        return $this;
    }


    public function validate()
    {
        if (empty($this->cardName))
            $this->invalid('missing card name info');
        if (empty($this->cardOwner))
            $this->invalid('missing card owner info');
        if (empty($this->cardMemberSince))
            $this->invalid('missing card member since info');
        if (empty($this->lastDigits))
            $this->invalid('missing last digits info');
        /* 07/27/23 for now offer details are not important
        if (empty($this->multiplier))
            $this->invalid('missing multiplier info');
        if (empty($this->multiplier))
            $this->invalid('missing multiplier info');
        if (empty($this->offerDeadline))
            $this->invalid('missing offer deadline info');
        if (empty($this->applicationDeadline))
            $this->invalid('missing application deadline info');
        if (empty($this->applicationURL))
            $this->invalid('missing application URL info');
        if (empty($this->limitAmount))
            $this->invalid('missing limit amount info');
        if (empty($this->limitCurrency))
            $this->invalid('missing limit currency info');
        if (empty($this->bonusCategories))
            $this->invalid('missing bonus categories info');
        */
        return $this->valid;
    }

    public function getChildren()
    {
        return [];
    }

}