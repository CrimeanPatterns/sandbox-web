<?php

namespace AwardWallet\Engine\testprovider\Checker;

use AwardWallet\Engine\testprovider\Success;
use AwardWallet\Engine\testprovider\TestHelper;

class Requests extends Success
{
    use TestHelper;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->ArchiveLogs = true;
        $this->http->LogHeaders = true;
        $this->http->RetryCount = 0;
        $this->UseCurlBrowser();
    }

    public function Parse()
    {
        $this->http->GetURL('http://localhost/1');
        $this->http->GetURL('http://localhost/2');
        $this->http->GetURL('http://localhost/3');
        $this->SetBalance(1);
    }
}
