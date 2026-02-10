<?php

namespace AwardWallet\Engine\testprovider\Checker;

use AwardWallet\Engine\ProxyList;
use AwardWallet\Engine\testprovider\Success;

class GoogleStateGet extends Success
{
    use \SeleniumCheckerHelper;
    use ProxyList;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->ArchiveLogs = true;
        $this->UseSelenium();
        $this->useSeleniumServer("10.154.26.253");
        $this->useChrome();
    }

    public function Parse()
    {
        $this->http->GetURL("https://www.google.com/some404page");
        $cookie = $this->driver->manage()->getCookieNamed("NID");

        if (empty($cookie)) {
            throw new \CheckException("NID cookie is not set");
        }

        $this->SetProperty("Number", $cookie['value']);
        $this->SetBalance(100);
    }
}
