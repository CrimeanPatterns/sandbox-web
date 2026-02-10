<?php

use AwardWallet\Engine\ProxyList;

class TAccountCheckerMoosejaw extends TAccountChecker
{
    use ProxyList;

    private const REWARDS_PAGE_URL = 'https://www.moosejaw.com/';

    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->RetryCount = 0;
        $this->http->SetProxy($this->proxyReCaptcha());
    }

    public function IsLoggedIn()
    {
        $this->http->RetryCount = 0;
        $this->http->GetURL(self::REWARDS_PAGE_URL, [], 20);
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.moosejaw.com/content/AccountSummary?catalogId=10000001&myAcctMain=1&langId=-1&storeId=10208');

        if (!$this->http->ParseForm('Logon')) {
            return $this->checkErrors();
        }

        $this->http->SetInputValue('logonId', $this->AccountFields['Login']);
        $this->http->SetInputValue('logonPassword', $this->AccountFields['Pass']);
        $this->http->SetInputValue('rememberMe', "on");

        return true;
    }

    public function Login()
    {
        if ($this->loginSuccessful()) {
            $this->captchaReporting($this->recognizer, true);

            return true;
        }

        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }

        //verify for captcha
        if (
            $this->http->FindSingleNode('//h2[contains(text(), "so sorry, but our Fancy Site Protection System (FSPS)")]')
            && !$this->parseReCaptcha()
        ) {
            return $this->checkErrors();
        }

        if ($message = $this->http->FindSingleNode('//img[@src="/moosejaw/Moosejaw/images/static/MJ-Throttle-Block-Page.jpg"]')) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $this->logger->debug($this->http->JsonLog());

        $this->http->GetURL('https://www.moosejaw.com');

        //Find link & to to order-status page
        $this->http->GetURL($this->http->FindSingleNode('//div[contains(@class, "account-scene customer-registered")]/a[contains(., "Order Status")]/@href'));

        // Name
        $this->SetProperty('Name', $this->http->FindSingleNode('//div[contains(@class, "row")]/div[contains(@class, "right-side-border-box wishlist")]'));
        //div[contains(@class, "right-side-border-box wishlist")]/p[contains(@class, "account-descr")]/b

        // Balance
        $this->SetBalance($this->http->FindSingleNode('//p[contains(.,"You have")]/b/text()'));
    }

    public function ParseItineraries()
    {
        $result = [];
        $this->http->GetURL('http://www.moosejaw.com/');
        $nodes = $this->http->XPath->query("//its");

        foreach ($nodes as $node) {
            $this->http->GetURL($node->nodeValue);
            $it = $this->parseItinerary();
            $this->logger->debug('Parsed itinerary:');
            $this->logger->debug(var_export($it, true), ['pre' => true]);
            $result[] = $it;
        }

        return $result;
    }

    public function GetHistoryColumns()
    {
        return [
            "Date"        => "PostingDate",
            "Description" => "Description",
            "Bonus"       => "Bonus",
            "Points"      => "Miles",
        ];
    }

    public function parseReCaptcha()
    {
        $key = '6Ldg4BgaAAAAAACxPHxTI7VHH-DKc6wyY8jf8Unf'; //$this->http->FindSingleNode('//div[contains(@class, "g-recaptcha")]/@data-sitekey');
        $this->logger->debug("data-sitekey: {$key}");

        if (!$key) {
            return false;
        }
        $this->recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_RUCAPTCHA);
        $this->recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl" => $this->http->currentUrl(),
            "proxy"   => $this->http->GetProxy(),
        ];

        return $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindNodes('//div[contains(@id, "AccountContainer")]/div[contains(@class, "account-scene customer-registered")]/div[contains(@class, "top-line")]/span[contains(@id, "DesktopHeaderCustomerName") and not(contains(., "Max"))]')) {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    private function parseItinerary()
    {
        $result = [];
        $bookNumber = '';
        $this->logger->info("Parse itinerary #{$bookNumber}", ['Header' => 3]);

        return $result;
    }
}
