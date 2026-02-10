<?php

use AwardWallet\Engine\ProxyList;

class TAccountCheckerMeliuz extends TAccountChecker
{
    private const REWARDS_PAGE_URL = 'https://customer.meliuz.com.br/me?include=indication_count,has_online_transaction,has_retail_transaction,has_online_transaction_only_purchase';
    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
    }

    public function IsLoggedIn()
    {
        if (!isset($this->State['headers'])) {
            return false;
        }

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.meliuz.com.br/entrar');
        // it's Login page? verify...
        if (strpos($this->http->Response['body'], '<title>Méliuz - Entrar</title>') !== false) {
            return false;
        }

        $captcha = $this->parseReCaptcha();

        if ($captcha == false) {
            return false;
        }

        $data = [
            'client_id'          => 'meliuz-client-site-production',
            'client_secret'      => 'cWk5Z4GyAK1OgX1NDaBarLGFDmL9wk',
            'grant_data'         => $this->AccountFields['Pass'],
            'grant_type'         => 'password',
            'identifier_type'    => 'email',
            'identifier_value'   => $this->AccountFields['Login'],
            'recaptcha_response' => $captcha,
        ];
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://customer.meliuz.com.br/v2/oauth/token', $data);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $response = $this->http->JsonLog();

        if (isset($response->data->accessToken)) {
            $this->captchaReporting($this->recognizer);
            $this->State['headers'] = [
                'authorization' => 'Bearer ' . $response->data->accessToken,
                'refreshToken'  => $response->data->refreshToken,
            ];

            return $this->loginSuccessful();
        }

        $message = $response->error->message ?? null;

        if ($message) {
            $this->logger->error("[Error]: {$message}");

            if ($message === "oauth.grant.customer.not_found") {
                throw new CheckException("Usuário ou senha incorretos. Por favor, tente novamente.", ACCOUNT_INVALID_PASSWORD);
            }

            return false;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $json = $this->http->JsonLog(null, 0);
        // Balance - R$...
        $this->SetBalance($json->data->confirmed_balance ?? null);
    }

    public function parseReCaptcha()
    {
        $this->http->RetryCount = 0;
        /*
         * secret_key  - it's "global secret key" for this site
         * Get from java-script on https://staticz.com.br/.next/production/67e451c702d2f6aa4a7c6b549707176052aa8975-42154/_next/static/chunks/3ad6cca2ee21cee566a33a852cf14bef8e30cd53.90302b6cf8b618710744.js
         * static,= cWk5Z4GyAK1OgX1NDaBarLGFDmL9wk
         */
        $key = '6Lfh9JgUAAAAAFKjdZEc33SmBBfqqc8hwfFC0X-y';

        if (!$key) {
            return false;
        }

        $this->recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_RUCAPTCHA);
        $this->recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl"   => $this->http->currentUrl(),
            "proxy"     => $this->http->GetProxy(),
            'invisible' => 1,
        ];

        return $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $this->http->RetryCount = 0;
        $this->http->GetURL(self::REWARDS_PAGE_URL, $this->State['headers'], 20);
        $this->http->RetryCount = 2;

        $json = $this->http->JsonLog(null, 3, true);
        $email = $json['data']['email'] ?? null;
        $this->logger->debug("[Email]: {$email}");

        if (strtolower($email) == strtolower($this->AccountFields['Login'])) {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }
}
