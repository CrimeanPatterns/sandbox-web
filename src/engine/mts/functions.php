<?php

class TAccountCheckerMts extends TAccountChecker
{
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
        $this->http->GetURL('https://bonus.ssl.mts.ru/api/user/part/Status', [], 20);
        $this->http->RetryCount = 2;
        $response = $this->http->JsonLog();

        if (isset($response->status) && $response->status == 'registered') {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->RetryCount = 0;
        $this->http->GetURL("https://login.mts.ru/amserver/UI/Login?goto=https%3a%2f%2fbonus.ssl.mts.ru%2f%23!%2fdashboard&service=bonus");

        $this->handlerRedirect();

        if (!$this->http->ParseForm("Login")) {
            return $this->checkErrors();
        }
        $this->AccountFields['Login'] = str_replace('+7', '', $this->AccountFields['Login']);
        $this->http->SetInputValue('IDToken1', $this->AccountFields['Login']);
        $this->http->SetInputValue('IDToken2', $this->AccountFields['Pass']);
        $this->http->SetInputValue('IDButton', 'Submit');
        unset($this->http->Form['noscript']);

        if ($key = $this->http->FindSingleNode("//button[contains(@class, 'g-recaptcha')]/@data-sitekey")) {
            $captcha = $this->parseReCaptcha($key);

            if ($captcha === false) {
                return false;
            }
            $this->http->SetInputValue("IDToken3", $captcha);
        }

        $this->http->setCookie("BonusGeoCookie7" . $this->AccountFields['Login'], "138020", "bonus.ssl.mts.ru");

        return true;
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode("//h1[contains(text(), 'Service Unavailable')]")
            || $this->http->FindSingleNode("//h1[contains(text(), '502 Bad Gateway')]")
        ) {
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        return false;
    }

    public function Login()
    {
        if (!$this->http->PostForm() && $this->http->Response['code'] != 401) {
            return $this->checkErrors();
        }
        // Введите символы, которые вы видите на картинке
        if ($this->http->FindSingleNode("//div[contains(text(), 'Введите символы, которые вы видите на')]")
            && $this->http->ParseForm("Login")) {
            $this->logger->notice(">>> Для входа в Личный кабинет, пожалуйста, подтвердите, что вы не робот.");
            // parse captcha
            $captcha = $this->parseCaptcha();

            if ($captcha === false) {
                return false;
            }
            $this->http->SetInputValue("IDToken2", $captcha);
            $this->http->PostForm();
        }
        $this->http->RetryCount = 2;

        // Access is allowed
        if ($this->http->FindNodes("//a[contains(@href, 'Logout')]/@href")
            || $this->http->FindPreg("/Произошла ошибка при загрузке данных с сервера\./")) {
            return true;
        }

        if ($this->http->ParseForm("form")) {
            $this->http->PostForm();
        }

        if ($message = $this->http->FindPreg("/(?:Вы ввели неверный пароль|Неверный пароль)/ims")) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }
        // Вы ввели телефон в неверном формате. Пожалуйста, введите номер правильно.
        if ($message = $this->http->FindPreg("/Вы ввели телефон в неверном формате. Пожалуйста, введите номер правильно./ims")) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }
        // Неверный формат. Введите номер правильно
        if ($message = $this->http->FindPreg("/Неверный формат. Введите номер правильно/ims")) {
            throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }
        // Вы не являетесь участником Программы, пожалуйста,
        if ($message = $this->http->FindPreg("/Вы не являетесь участником Программы, пожалуйста,/")) {
            throw new CheckException("Вы не являетесь участником Программы, пожалуйста, зарегистрируйтесь.", ACCOUNT_PROVIDER_ERROR);
        }
        //# Account lockout
        if ($message = $this->http->FindPreg("/Учетная запись заблокирована в связи с превышением попыток ввода пароля/ims")) {
            throw new CheckException($message, ACCOUNT_LOCKOUT);
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $this->http->GetURL("https://ihelper.mts.ru/selfcare/account-status.aspx");
        // Имя
        $this->SetProperty("Name", $this->http->FindSingleNode("//div[@class = 'customer-info']/div/h3"));
        // Ваш номер
        $this->SetProperty("Phone", $this->http->FindSingleNode("//li[contains(text(), 'Номер:')]/strong"));
        // Ваш баланс
        $this->SetProperty("CashBalance", $this->http->FindSingleNode("//li[contains(text(), 'Баланс:')]/span/strong"));
        // Ваш тариф
        $this->SetProperty("Tariff", $this->http->FindSingleNode("//li[contains(text(), 'Тарифный план:')]/strong"));

        // Balance - TOTAL POINTS
        $this->http->GetURL("https://bonus.ssl.mts.ru/api/user/part/Points");
        $response = $this->http->JsonLog(null, true, true);

        if (!$this->SetBalance(ArrayVal($response, 'points'))) {
            // Сервис временно не доступен
            if (ArrayVal($response, 'status') == 'unavailable' || $this->http->FindPreg("/^\{\}$/")) {
                throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }
            // not a member
            if ($this->http->FindPreg("/^\{\"status\":\"registered\",\"hasFullBlank\":true,\"isInvalidGlobalCode\":false\}$/")) {
                $this->SetWarning(self::NOT_MEMBER_MSG);

                return;
            }
        }

        // Expiration Date
        $this->http->RetryCount = 0;
        $this->http->GetURL("https://bonus.ssl.mts.ru/api/user/combustions");
        $this->http->RetryCount = 2;
        $response = $this->http->JsonLog(null, true, true);

        if (is_array($response)) {
            foreach ($response as $row) {
                $date = preg_replace('/000$/', '', ArrayVal($row, 'date'));
                $points = ArrayVal($row, 'value');
                $this->logger->debug("date: {$date} - {$points}");

                if ((!isset($exp) || $date < $exp) && $points > 0) {
                    // Expiration date
                    $exp = $date;
                    $this->SetExpirationDate($exp);
                    // Points to Expire
                    $this->SetProperty("PointsToExpire", $points);
                }// if ($points > 0)
            }
        }// if ((!isset($exp) || $date < $exp) && $points > 0)

        if (!isset($this->Properties['PointsToExpire']) && $this->http->FindPreg("/^\[\]$/")) {
            $this->ClearExpirationDate();
        }
    }

    protected function parseCaptcha()
    {
        $this->logger->notice(__METHOD__);
        $imageData = $this->http->FindSingleNode("//img[@id = 'kaptchaImage']/@src", null, true, "/png;base64\,\s*([^<]+)/ims");
        $this->logger->debug("png;base64: {$imageData}");

        if (!empty($imageData)) {
            $this->logger->debug("decode image data and save image in file");
            // decode image data and save image in file
            $imageData = base64_decode($imageData);
            $image = imagecreatefromstring($imageData);
            $file = "/tmp/captcha-" . getmypid() . "-" . microtime(true) . ".png";
            imagejpeg($image, $file);
        }

        if (!isset($file)) {
            return false;
        }
        $this->logger->debug("file: " . $file);
        $recognizer = $this->getCaptchaRecognizer();
        $recognizer->RecognizeTimeout = 100;
        $captcha = $this->recognizeCaptcha($recognizer, $file);
        unlink($file);

        return $captcha;
    }

    private function handlerRedirect()
    {
        $this->logger->notice(__METHOD__);
        // Wait for redirect
        if ($this->http->FindPreg('/onload="document\.forms\[0\]\.submit\(\)"/')
            && $this->http->ParseForm(null, 1, true, '//form')) {
            $this->logger->debug("evaluate redirect");
            $this->http->PostForm();
        }// if ($this->http->FindSingleNode("//title[contains(text(), 'Wait for redirect')]") && $this->http->ParseForm("form"))
    }

    private function parseReCaptcha($key)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->debug("data-sitekey: {$key}");

        if (!$key) {
            return false;
        }
        $recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_RUCAPTCHA);
        $recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl" => $this->http->currentUrl(),
            "proxy"   => $this->http->GetProxy(),
        ];
        $captcha = $this->recognizeByRuCaptcha($recognizer, $key, $parameters);

        return $captcha;
    }
}
