<?php

namespace AwardWallet\ExtensionWorker;

class ParseAllOptions
{

    private Credentials $credentials;
    private ?ParseItinerariesOptions $parseItinerariesOptions;
    private ?ParseHistoryOptions $parseHistoryOptions;

    public function __construct(Credentials $credentials, ?ParseItinerariesOptions $parseItinerariesOptions, ?ParseHistoryOptions $parseHistoryOptions)
    {

        $this->credentials = $credentials;
        $this->parseItinerariesOptions = $parseItinerariesOptions;
        $this->parseHistoryOptions = $parseHistoryOptions;
    }

    public function getCredentials(): Credentials
    {
        return $this->credentials;
    }

    public function getParseItinerariesOptions(): ?ParseItinerariesOptions
    {
        return $this->parseItinerariesOptions;
    }

    public function getParseHistoryOptions(): ?ParseHistoryOptions
    {
        return $this->parseHistoryOptions;
    }

}