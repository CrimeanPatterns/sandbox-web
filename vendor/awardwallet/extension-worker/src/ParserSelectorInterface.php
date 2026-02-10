<?php

namespace AwardWallet\ExtensionWorker;

use Psr\Log\LoggerInterface;

interface ParserSelectorInterface
{

    public function selectParser(SelectParserRequest $request, LoggerInterface $logger) : string;

}