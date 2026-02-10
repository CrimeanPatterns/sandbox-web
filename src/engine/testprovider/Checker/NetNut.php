<?php

namespace AwardWallet\Engine\testprovider\Checker;

use AwardWallet\Engine\ProxyList;
use AwardWallet\Engine\testprovider\Success;

class NetNut extends Success
{
    use ProxyList;

    public function Parse()
    {
        $this->ArchiveLogs = true;
        $this->setProxyNetNut();
        $this->http->userAgent = 'CURL';
        $this->http->GetURL("http://ipinfo.io");

        $this->SetBalance(1);
    }
}
