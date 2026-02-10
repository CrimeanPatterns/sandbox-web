<?php


namespace AwardWallet\Common\OneTimeCode;


trait OtcHelper
{

    private $waitForOtc = false;


    /**
     * @return bool
     */
    public function getWaitForOtc()
    {
        return $this->waitForOtc;
    }

    /**
     * @param bool $waitForOtc
     */
    public function setWaitForOtc(bool $waitForOtc)
    {
        $this->waitForOtc = $waitForOtc;
    }

}
