<?php

namespace AwardWallet\ExtensionWorker;

interface ParseAllowedInterface
{

    public function isParseAllowed(AccountOptions $options) : bool;

}