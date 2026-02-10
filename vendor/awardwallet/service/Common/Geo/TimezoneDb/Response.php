<?php

namespace AwardWallet\Common\Geo\TimezoneDb;

class Response
{
    /**
     * @var string
     */
    private $zoneName;
    /**
     * @var int
     */
    private $gmtOffset;

    public function __construct(string $zoneName, int $gmtOffset)
    {
        $this->zoneName = $zoneName;
        $this->gmtOffset = $gmtOffset;
    }

    public function getZoneName(): string
    {
        return $this->zoneName;
    }

    public function getGmtOffset(): int
    {
        return $this->gmtOffset;
    }

}