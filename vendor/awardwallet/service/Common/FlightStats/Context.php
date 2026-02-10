<?php


namespace AwardWallet\Common\FlightStats;


class Context
{

    const METHOD_SCH_BY_FLIGHT = 'ScheduleByFlight';
    const METHOD_SCH_BY_ROUTE = 'ScheduleByRoute';
    const METHOD_SCH_BY_AIRPORT = 'ScheduleByAirport';
    const METHOD_AIRLINES = 'Airlines';
    const METHOD_HISTORICAL_BY_FLIGHT = 'HistoricalByFlight';
    const METHOD_HISTORICAL_BY_ROUTE = 'HistoricalByRoute';
    const METHOD_HISTORICAL_BY_AIRPORT = 'HistoricalByAirport';

    private $reason;

    private $partnerLogin;

    private $eligible;

    private $method;

    /** @var bool */
    private $callWasMade;

    /**
     * @param string[] $reasons
     * @param string $method
     * @param string $partnerLogin
     * @param bool $eligible
     */
    public function __construct(array $reasons, string $method, string $partnerLogin, bool $eligible)
    {
        $this->reason = $reasons;
        $this->partnerLogin = $partnerLogin;
        $this->eligible = $eligible;
        $this->method = $method;
        $this->callWasMade = false;
    }

    public function getReason()
    {
        return $this->reason;
    }

    public function getPartnerLogin()
    {
        return $this->partnerLogin;
    }

    public function getEligible(): bool
    {
        return $this->eligible;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public static function getDefault(?string $method = null): Context
    {
        return new self([], $method, '', true);
    }

    /**
     * @return bool
     */
    public function wasCallMade(): bool
    {
        return $this->callWasMade;
    }

    /**
     * @param bool $callWasMade
     */
    public function setCallWasMade(bool $callWasMade): void
    {
        $this->callWasMade = $callWasMade;
    }

}