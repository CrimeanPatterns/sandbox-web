<?php

namespace AwardWallet\ExtensionWorker;

class ProviderInfo
{

    private string $displayName;

    public function __construct(string $displayName)
    {

        $this->displayName = $displayName;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

}