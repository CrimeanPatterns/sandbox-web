<?php

namespace AwardWallet\Engine\testprovider;

use AwardWallet\Engine\ProxyList;

class TSeleniumVersions extends \TAccountChecker
{
    use \SeleniumCheckerHelper;
    use ProxyList;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->InitSeleniumBrowser();
//        $this->http->SetProxy($this->proxyStaticIpDOP());

        if (isset($this->AccountFields['Login'])) {
            switch ($this->AccountFields['Login']) {
            case "selenium.versions.45":
                $this->useFirefox45();

                break;

            case "selenium.versions.53":
                $this->useFirefox53();

                break;

            case "selenium.versions.Chromium":
                $this->useChrome();

                break;

            case "selenium.versions.logs":
                $this->http->saveScreenshots = true;
                $this->disableImages();
                $this->useChrome();

                break;

            default:// selenium.versions.40
                break;
        }
        }

        $this->keepCookies(false);
        $this->ArchiveLogs = true;
    }

    public function LoadLoginForm()
    {
        $this->http->GetURL("https://useragentapi.com");
        sleep(3);

        $this->http->GetURL("http://ipinfo.io");
        sleep(3);

        if ($this->AccountFields['Login'] == "selenium.versions.logs") {
            $this->http->GetURL("https://yandex.ru");
            sleep(3);
            $this->http->GetURL("https://www.yahoo.com");
            sleep(3);
            $this->http->GetURL("https://yandex.ru");
            sleep(3);
            $this->http->GetURL("https://www.yahoo.com");
            sleep(3);
        }

        return true;
    }

    public function Login()
    {
        return true;
    }

    public function Parse()
    {
        $this->SetBalance(1000);
    }
}
