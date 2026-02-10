<?php


namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Schema\Parser\Component\Base;

class AwardRedemption extends Base
{
    /**
     * @parsed DateTime
     */
    protected $dateIssued;

    /**
     * @parsed Field
     * @attr length=short
     * @attr type=number
     */
    protected $milesRedeemed;

    /**
     * @parsed Field
     * @attr length=medium
     */
    protected $recipient;

    /**
     * @parsed Field
     * @attr length=long
     */
    protected $description;

    /**
     * @parsed Field
     * @attr length=short
     */
    protected $accountNumber;


    /**
     * @return mixed
     */
    public function getDateIssued()
    {
        return $this->dateIssued;
    }

    /**
     * @param mixed $dateIssued
     * @return AwardRedemption
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setDateIssued($dateIssued)
    {
        $this->setProperty($dateIssued, 'dateIssued', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMilesRedeemed()
    {
        return $this->milesRedeemed;
    }

    /**
     * @param mixed $milesRedeemed
     * @return AwardRedemption
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setMilesRedeemed($milesRedeemed)
    {
        $this->setProperty($milesRedeemed, 'milesRedeemed', false, false);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @param mixed $recipient
     * @return AwardRedemption
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setRecipient($recipient, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($recipient, 'recipient', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     * @return AwardRedemption
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setDescription($description, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($description, 'description', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAccountNumber()
    {
        return $this->accountNumber;
    }

    /**
     * @param mixed $accountNumber
     * @return AwardRedemption
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setAccountNumber($accountNumber, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($accountNumber, 'accountNumber', $allowEmpty, $allowNull);
        return $this;
    }

    public function validate()
    {
        if (empty($this->dateIssued))
            $this->invalid('missing date issued info');
        if (empty($this->milesRedeemed))
            $this->invalid('missing miles redeemed info');
        return $this->valid;
    }

    public function getChildren()
    {
        return [];
    }

}