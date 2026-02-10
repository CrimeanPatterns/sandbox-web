<?php

namespace AwardWallet\ExtensionWorker;

class ParseItinerariesOptions
{

    private bool $parsePastItineraries;

    public function __construct(bool $parsePastItineraries)
    {
        $this->parsePastItineraries = $parsePastItineraries;
    }

    public function isParsePastItineraries(): bool
    {
        return $this->parsePastItineraries;
    }

}