<?php

namespace AwardWallet\MainBundle\Service;

class CheckerFactory
{

    public function __construct(ParsingConstants $parsingConstants)
    {
    }

    public function getAccountChecker(string $providerCode, bool $requireFiles = false, $accountInfo = null)
    {
        return \GetAccountChecker($providerCode, $requireFiles, $accountInfo);
    }

    public function getRewardAvailabilityChecker(string $providerCode, bool $requireFiles = false, $accountInfo = null)
    {
        return \GetRewardAvailabilityChecker($providerCode, $requireFiles, $accountInfo, 'Parser');
    }

    public function getRaHotelChecker(string $providerCode, bool $requireFiles = false, $accountInfo = null)
    {
        return \GetRewardAvailabilityChecker($providerCode, $requireFiles, $accountInfo, 'HotelParser');
    }

    public function getRewardAvailabilityRegister(string $providerCode, bool $requireFiles = false)
    {
        return \GetRewardAvailabilityRegister($providerCode, $requireFiles);
    }
}