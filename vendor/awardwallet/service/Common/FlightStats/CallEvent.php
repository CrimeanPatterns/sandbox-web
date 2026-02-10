<?php


namespace AwardWallet\Common\FlightStats;


use Symfony\Component\EventDispatcher\Event;

class CallEvent extends Event
{

    const NAME = 'aw_flightstats_call';

    private $reason;

    private $partnerLogin;

    private $method;

    private $appId;

    public function __construct($reason, $method, $partnerLogin, $appId)
    {
        $this->reason = $reason;
        $this->partnerLogin = $partnerLogin;
        $this->method = $method;
        $this->appId = $appId;
    }

    public static function fromContext(Context $context, string $appId)
    {
        return new self($context->getReason(), $context->getMethod(), $context->getPartnerLogin(), $appId);
    }

    public function getReason()
    {
        return $this->reason;
    }

    public function getPartnerLogin()
    {
        return $this->partnerLogin;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getAppId()
    {
        return $this->appId;
    }

}