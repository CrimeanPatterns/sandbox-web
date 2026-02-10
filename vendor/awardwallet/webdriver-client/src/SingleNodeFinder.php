<?php

namespace AwardWallet\WebdriverClient;

class SingleNodeFinder extends NodeFinder
{
    
    private string $nodeAddress;
    
    public function __construct(string $nodeAddress)
    {
        $this->nodeAddress = $nodeAddress;
    }

    public function getNode(?string $table = null) : ?string
    {
        return $this->nodeAddress;
    }
    
}
