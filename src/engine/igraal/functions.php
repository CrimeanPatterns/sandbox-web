<?php

class TAccountCheckerIgraal extends TAccountChecker
{
    use AwardWallet\Engine\ProxyList;

    private $region;

    private $recognizer;

    public function TuneFormFields(&$arFields, $values = null)
    {
        parent::TuneFormFields($arFields);
        $arFields["Login2"]["Options"] = [
            "FR" => "France",
            "DE" => "Germany",
        ];
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->SetProxy($this->proxyReCaptcha());
        $this->http->setHttp2(true);
        $this->http->setDefaultHeader('Accept-Encoding', 'gzip, deflate, br');
        $this->getRegionSettings();
    }

    public function getRegionSettings()
    {
        $this->logger->notice(__METHOD__);

        if (empty($this->AccountFields['Login2'])) {
            $this->AccountFields['Login2'] = 'FR';
        }
        $this->logger->debug('Region => ' . $this->AccountFields['Login2']);

        $this->region = 'fr';

        if ($this->AccountFields['Login2'] == 'DE') {
            $this->region = 'de';
        }
    }

    public function IsLoggedIn()
    {
        if ($this->attempt > 0) {
            return false;
        }

        $this->http->RetryCount = 0;
        $this->http->GetURL('https://' . $this->region . '.igraal.com/', [], 20);

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();

        $this->http->GetURL('https://' . $this->region . '.igraal.com/security/csrf/' . rand(10000, 99999));
        $csrfToken = $this->http->JsonLog(null, false);

        $this->http->GetURL('https://' . $this->region . '.igraal.com/');

        if (!$this->http->ParseForm('form_login')) {
            return false;
        }
        $this->http->SetInputValue('email', $this->AccountFields['Login']);
        $this->http->SetInputValue('password', $this->AccountFields['Pass']);

        if ($recaptchaKey = $this->http->FindSingleNode('//div[@id="recaptcha_login"]/@data-recaptcha-key')) {
            $captcha = $this->parseReCaptcha($recaptchaKey);

            if ($captcha === false) {
                return false;
            }
            $this->http->SetInputValue('g-recaptcha-response', $captcha);
        } else {
            return false;
        }

        $this->http->SetInputValue('_csrf_token', $csrfToken);

        return true;
    }

    public function Login()
    {
        if (!$this->http->PostForm()) {
            return false;
        }

        if ($message = $this->http->FindSingleNode('//div[contains(@data-ig-connect-form-type, "error")
													and (contains(text(), "email ou mot de passe incorrect")
														 or contains(text(), "Benutzername oder Passwort ist falsch"))]')
        ) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        if ($this->loginSuccessful()) {
            $this->recognizer->reportGoodCaptcha();

            return true;
        }

        $result = $this->http->JsonLog(null, false);

        if (isset($result->valid_captcha) && $result->valid_captcha == false) {
            $this->recognizer->reportIncorrectlySolvedCAPTCHA();

            throw new CheckRetryNeededException(3, 1, self::CAPTCHA_ERROR_MSG);
        }

        return false;
    }

    public function Parse()
    {
        $this->http->GetURL('https://' . $this->region . '.igraal.com/ws/token');
        $result = $this->http->JsonLog(null, false);

        if (empty($result->token)) {
            return false;
        }
        $token = $result->token;

        $headers = [
            'Accept'        => '*/*',
            'Authorization' => $token,
        ];
        $this->http->GetURL('https://public-api-' . $this->region . '.igraal.com/v1/user/me', $headers);
        $result = $this->http->JsonLog();

        // Balance - VALIDÉS / Kontostand
        $this->SetBalance($result->balanceTotal ?? null);
        // Name
        $name = $result->civility->firstname ?? '' . ' ' . $result->civility->lastname ?? '';
        $this->SetProperty('Name', beautifulName(trim($name)));
        // Pending Cashback - EN ATTENTE / Vorgemerkt
        $this->SetProperty('PendingCashback', $result->balancePending ?? null);
        // Status
        $this->SetProperty('Status', $result->status ?? null);

        // Notifications
        if ($result->balanceSponsorship || $result->balanceReviews || $result->balanceSubscriptionCashback || $result->balanceSubscriptionCorrection
            || $result->nbGodchildren || $result->nbActiveGodchildren || $result->premiumSponsoBonus || $result->premiumWelcomeBonus
            || $result->nbActions || $result->nbReviews || $result->nbValidatedReviews || $result->nbWaitingReviews
            || $result->nbFavorites || $result->nbWishes
        ) {
            $this->sendNotification('igraal, refs #6759. Need to check properties // IV');
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//a[contains(@href, "/logout")]/@href')) {
            return true;
        }

        return false;
    }

    private function parseReCaptcha($recaptchaKey)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->debug("data-sitekey: {$recaptchaKey}");

        $this->recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_RUCAPTCHA);
        $this->recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl" => $this->http->currentUrl(),
            "proxy"   => $this->http->GetProxy(),
        ];
        $captcha = $this->recognizeByRuCaptcha($this->recognizer, $recaptchaKey, $parameters);
        $this->logger->debug("captcha: {$captcha}");

        return $captcha;
    }
}
