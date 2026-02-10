<?php

namespace AwardWallet\Engine\testprovider\Checker;

use AwardWallet\Engine\testprovider\Success;

class GoogleStateSet extends Success
{
    use \SeleniumCheckerHelper;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->ArchiveLogs = true;
        $this->UseSelenium();
    }

    public function Parse()
    {
        $this->http->GetURL("https://www.google.com/some404page");
        $cookie = $this->driver->manage()->getCookieNamed("NID");

        if (!empty($cookie)) {
            throw new \CheckException("NID cookie is already set");
        }

        $this->http->GetURL("https://www.google.com/ncr");
        $cookie = $this->driver->manage()->getCookieNamed("NID");

        if (empty($cookie)) {
            throw new \CheckException("failed to set NID cookie");
        }
        $this->SetProperty("Number", $cookie['value']);

        $this->SetBalance(100);
    }
}
