<?php

use AwardWallet\Common\Parsing\Html;

class TAccountCheckerAeromexico extends TAccountChecker
{
    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg["CookieURL"] = "https://member.clubpremier.com/login/auth";

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
    }

    public function IsLoggedIn()
    {
        if ($this->attempt > 0) {
            return false;
        }

        $this->http->RetryCount = 0;
        $this->http->GetURL("https://member.clubpremier.com/", [], 20);
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful() && !strstr($this->http->currentUrl(), 'login/auth')) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL("https://member.clubpremier.com/login/auth?lang=en");

        if (!$this->http->ParseForm("loginForm")) {
            return $this->checkErrors();
        }
        $this->http->SetInputValue("j_username", $this->AccountFields['Login']);
        $this->http->SetInputValue("j_password", $this->AccountFields['Pass']);

        return true;
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);
        // Web server is returning an unknown error
        if ($message = $this->http->FindSingleNode("//h2[contains(text(), 'Web server is returning an unknown error')]")) {
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }
        // Maintenance
        if ($message = $this->http->FindSingleNode("//img[@src = '45521alerta_co.jpg']/@src")) {
            throw new CheckException("Estamos trabajando en el sitio con el fin de ofrecerte mejor una experiencia. A partir del 11 de mayo a las 19:00 hrs quedará restablecido el servicio", ACCOUNT_PROVIDER_ERROR);
        }

        return false;
    }

    public function Login()
    {
        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }

        if ($this->loginSuccessful()) {
            return true;
        }
        // Password is not correct
        if ($message = $this->http->FindSingleNode("//p[contains(text(), 'The account number and/or password are incorrect.')]")) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }
        // Tu cuenta fue bloqueada porque alcanzó el número máximo de solicitudes de login, por favor intenta de nuevo en 15 min.
        if ($message = $this->http->FindSingleNode("//p[contains(text(), 'Tu cuenta fue bloqueada porque alcanzó el número máximo de solicitudes de login')]")) {
            throw new CheckException($message, ACCOUNT_LOCKOUT);
        }
        // Status pending
        if ($message = $this->http->FindPreg("/(We require additional information with regard to your account as it has been temporarly suspended\.)/ims")) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        // switch to English version
        $language = Html::cleanXMLValue($this->http->FindSingleNode("//div[contains(@class, 'select-lang')]/a/span"));
        $this->logger->notice(">>> Language: " . $language);

        if (!in_array($language, ['Inglés', 'English']) && !$this->http->FindSingleNode("//p[contains(text(), 'Balance:')]")) {
            $this->logger->notice(">>> Switch to English version");
            $this->http->GetURL("https://member.clubpremier.com/?lang=en");
        }

        // Balance - My balance is ... Premier Points
        $this->SetBalance($this->http->FindSingleNode("//p[contains(text(), 'Balance:')]", null, true, "/([\d\.\,]+)/ims"));

        $this->http->PostURL('https://member.clubpremier.com/homeServices/loadIndex', []);
        // Name
        $this->SetProperty("Name", beautifulName($this->http->FindSingleNode("//div[@class = 'name']", null, true, "/>?(.+)/ims")));
        // Membership Number
        $this->SetProperty("Number", $this->http->FindSingleNode("//div[@class = 'account']/text()/following-sibling::span"));

        // Level - in english version, level not showing
        $levelEnglish = $this->http->FindSingleNode("//div[@class = 'card-user']//img[@class = 'img-fluid']/@src");

        $this->http->GetURL("https://member.clubpremier.com/individual/movimientos-por-fecha");

        $level = $levelEnglish;

        if ($levelEnglish) {
            $level = basename($level);
            $this->logger->debug(">>> Level " . $level);

            switch ($level) {
                case 'bannerVisa.jpg':
                    // may be Clasico
                case 'AeromexicoVisaSignature.png':
                case 'AeromexicoVisaCard.png':
                case 'cp-card-test.png':
                case 'cp-one.png':
                    $this->SetProperty("Level", "Clasico");

                    break;

                case 'AeromexicoGold.png':
                case 'cp-card-test-oro.png':
                    $this->SetProperty("Level", "Gold");

                    break;

                case 'AeromexicoPlatino.png':
                case 'cp-card-test-platino.png':
                    $this->SetProperty("Level", "Platinum");

                    break;

                case 'AeromexicoTitanio.png':
                case 'cp-card-test-titanio.png':
                    $this->SetProperty("Level", "Titanium");

                    break;

                default:
                    $this->sendNotification("aeromexico: newStatus: $level");
            }// switch ($status)
        }// if ($level = $this->http->FindSingleNode("//img[@class = 'img-card']/@src"))

        // Expiration Date  // refs #12900
        $this->logger->info('Expiration date', ['Header' => 3]);

        if ($exp = $this->http->FindSingleNode("//strong[contains(text(), 'they will expire on') or contains(text(), 'éstos vencerán el')]/span")) {
            $exp = $this->ModifyDateFormat($exp, "/", true);

            if ($exp = strtotime($exp)) {
                $this->SetExpirationDate($exp);
            }
            $this->parseLastActivity();
        }// if ($exp = $this->http->FindSingleNode("//strong[contains(text(), 'they will expire on') or contains(text(), 'éstos vencerán el')]/span"))
        else {
            if (!$this->http->ParseForm(null, 1, true, "//form[contains(@action, 'generateAccountStatement')]")) {
                return;
            }
            $this->http->SetInputValue("movementsFrom_year", date("Y") - 1);
            $this->http->FormURL = 'https://member.clubpremier.com/accountStatement/searchDataAccount';
            $this->http->PostForm();
            $this->parseLastActivity(true);
        }
    }

    public function parseLastActivity($findExpDate = false)
    {
        $this->logger->notice(__METHOD__);
        $nodes = $this->http->XPath->query("//table[@id = 'transactionPersonal']//tr[td]");
        $this->logger->debug("Total {$nodes->length} history nodes were found");

        foreach ($nodes as $node) {
            $premierPoints = $this->http->FindSingleNode("td[8]", $node);
            $activityDate = $this->http->FindSingleNode("td[1]", $node);

            if ($premierPoints > 0) {
                $this->SetProperty("LastActivity", $activityDate);
                $activityDate = $this->ModifyDateFormat($activityDate, "/", true);

                if ($findExpDate && ($exp = strtotime($activityDate))) {
                    $this->SetExpirationDate(strtotime("+2 year", $exp));
                }

                break;
            }// if ($premierPoints > 0)
        }// foreach ($nodes as $node)
    }

    private function loginSuccessful()
    {
        // Access is allowed
        if ($this->http->FindNodes("//a[contains(@href, '/salir')]/@href")) {
            return true;
        }

        return false;
    }
}
