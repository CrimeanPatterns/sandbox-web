<?php

namespace AwardWallet\ExtensionWorker\Commands;

class CompleteRequest
{

    public ?string $error;

    public function __construct(?string $error)
    {
        $this->error = $error;
    }

}