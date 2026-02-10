<?php

namespace AwardWallet\Engine\aviancataca\RewardAvailability;

use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;

class Register extends \TAccountChecker
{
    use ProxyList;
    use \SeleniumCheckerHelper;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->UseSelenium();
//        $this->useGoogleChrome(\SeleniumFinderRequest::CHROME_84);
        switch (random_int(0, 6)) {
            case 5:
                $this->useGoogleChrome(\SeleniumFinderRequest::CHROME_84);
                $request = FingerprintRequest::chrome();

                break;

            case 0:
                $this->useGoogleChrome(\SeleniumFinderRequest::CHROME_99);
                $request = FingerprintRequest::chrome();

                break;

            case 1:
                $this->useGoogleChrome(\SeleniumFinderRequest::CHROME_95);
                $request = FingerprintRequest::chrome();

                break;

            case 2:
                $this->useFirefox(\SeleniumFinderRequest::FIREFOX_84);
                $request = FingerprintRequest::firefox();

                break;

            case 3:
                $this->useFirefox(\SeleniumFinderRequest::FIREFOX_59);
                $request = FingerprintRequest::firefox();

                break;

            case 4:
                $this->useFirefoxPlaywright();
                $request = FingerprintRequest::firefox();

                break;

            default:
                $this->useChromePuppeteer(\SeleniumFinderRequest::CHROME_PUPPETEER_103);
                $request = FingerprintRequest::chrome();
        }
        $this->disableImages();
        $this->seleniumOptions->showImages = false;

        $resolutions = [
            [1152, 864],
            [1280, 720],
            [1280, 768],
            [1280, 800],
            [1360, 768],
            [1366, 768],
        ];
        $chosenResolution = $resolutions[array_rand($resolutions)];
        $this->setScreenResolution($chosenResolution);

        $this->http->saveScreenshots = true;
        $this->setProxyNetNut();

//        $request = FingerprintRequest::chrome();
        $request->browserVersionMin = 100;
        $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);

        if (isset($fingerprint)) {
            $this->http->setUserAgent($fingerprint->getUseragent());
            $this->seleniumOptions->userAgent = $fingerprint->getUseragent();
            $this->seleniumOptions->fingerprintOptions = $fingerprint->getFingerprint();
        } else {
            $this->http->setRandomUserAgent(null, false, true, false, true, false);
        }
        $this->KeepState = false;
    }

    public function registerAccount(array $fields)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->debug(var_export($fields, true), ['pre' => true]);

        $this->checkFields($fields);

        $this->http->GetURL('https://www.lifemiles.com/enrollment/step/1');

        $email = $this->waitForElement(\WebDriverBy::xpath('//input[@id="email"]'), 60);
        $pass = $this->waitForElement(\WebDriverBy::xpath('//input[@id="password"]'), 0);
        $confPass = $this->waitForElement(\WebDriverBy::xpath('//input[@id="confirmPassword"]'), 0);

        if (!$email || !$pass || !$confPass) {
            $this->saveResponse();
            $this->logger->error('no register form or other format');

            return false;
        }

        if ($cookie = $this->waitForElement(\WebDriverBy::xpath('//button[@class="CookiesBrowserAlert_acceptButtonNO"]'), 10)) {
            $cookie->click();
        }

        $email->sendKeys($fields['Email']);
        $this->someSleep();
        $pass->sendKeys($fields['Password']);
        $this->someSleep();
        $confPass->sendKeys($fields['Password']);
        $this->someSleep();
        $email->click();

        $nextBtn = $this->waitForElement(\WebDriverBy::xpath('//button[contains(@class,"Enrollment_nextButton")]'), 30);

        if (!$nextBtn) {
            $this->saveResponse();
            $this->logger->error('no register form or other format');

            return false;
        }
        $this->saveResponse();
        $nextBtn->click();
        $this->saveResponse();

        $firstName = $this->waitForElement(\WebDriverBy::xpath('//input[@id="firstname"]'), 30);
        $lastName = $this->waitForElement(\WebDriverBy::xpath('//input[@id="lastname"]'), 0);
        $month = $this->waitForElement(\WebDriverBy::xpath('//div[contains(@class,"DateSelectGroup_month")]//select'), 0);
        $day = $this->waitForElement(\WebDriverBy::xpath('//div[contains(@class,"DateSelectGroup_day")]//select'), 0);
        $year = $this->waitForElement(\WebDriverBy::xpath('//div[contains(@class,"DateSelectGroup_year")]//select'), 0);

        sleep(5);

        if (!$firstName || !$lastName || !$month || !$day || !$year) {
            $this->saveResponse();
            $this->logger->error('no register form or other format');

            return false;
        }

        $firstName->sendKeys($fields['FirstName']);
        $this->someSleep();

        $lastName->sendKeys($fields['LastName']);
        $this->someSleep();

        $month->click();
        $this->someSleep();
        $monthOption = $this->waitForElement(\WebDriverBy::xpath(
            '//div[contains(@class,"DateSelectGroup_month")]//option[@value="' . random_int(0, 11) . '"]'), 10);

        if (!$monthOption) {
            $this->saveResponse();
            $this->logger->error('no register form or other format');

            return false;
        }
        $monthOption->click();
        $this->someSleep();

        $day->click();
        $this->someSleep();
        $dayOption = $this->waitForElement(\WebDriverBy::xpath(
            '//div[contains(@class,"DateSelectGroup_day")]//option[@value="' . random_int(0, 26) . '"]'), 10);

        if (!$dayOption) {
            $this->saveResponse();
            $this->logger->error('no register form or other format');

            return false;
        }
        $dayOption->click();
        $this->someSleep();

        $year->click();
        $this->someSleep();
        $yearOption = $this->waitForElement(\WebDriverBy::xpath(
            '//div[contains(@class,"DateSelectGroup_year")]//option[@value="' . random_int(20, 44) . '"]'), 10);

        if (!$yearOption) {
            $this->saveResponse();
            $this->logger->error('no register form or other format');

            return false;
        }
        $yearOption->click();
        $this->someSleep();
        $this->saveResponse();

        $documentNumber = $this->waitForElement(\WebDriverBy::xpath('//input[@id="documentNumber"]'), 0);
        $country = $this->waitForElement(\WebDriverBy::xpath('//div[contains(@class,"idNumberInputWrapper")]//select[@id="country"]'), 0);
        $checkbox1 = $this->waitForElement(\WebDriverBy::xpath('//input[@id="normal_term0"]'), 5, false);
        $checkbox2 = $this->waitForElement(\WebDriverBy::xpath('//input[@id="normal_term1"]'), 5, false);
        $confirm = $this->waitForElement(\WebDriverBy::xpath('//button[@id="Enroll-confirm"]'), 0);

        if (!$documentNumber || !$country || !$checkbox1 || !$checkbox2 || !$confirm) {
            $this->saveResponse();
            $this->logger->error('no register form or other format');

            return false;
        }

        $country->click();
        $this->someSleep();
        $countries = [
            156, // United Kingdom (190 eng) - Reino Unido
            56, // United States (191 eng) - Estados Unidos
            //            40, // Colombia (38 eng) - Colombia
        ];

        $country = $countries[array_rand($countries)];
        $countryOption = $this->waitForElement(\WebDriverBy::xpath(
            '//div[contains(@class,"idNumberInputWrapper")]//select[@id="country"]//option[@value="' . $country . '"]'), 10);

        if (!$countryOption) {
            $this->saveResponse();
            $this->logger->error('no register form or other format');

            return false;
        }

        $countryOption->click();
        $this->someSleep();

        if ($checkbox1 && $checkbox2 && $documentNumber) {
            $this->driver->executeScript('
                document.querySelector(\'label[for="normal_term0"]\').click();
                document.querySelector(\'label[for="normal_term1"]\').click();
            ');
        }

        // 8 digits based on email, + random last one
        $national_id = substr(hexdec(substr(md5($fields['Email']), 0, 10)), 0, 8) . rand(0, 9);

        $documentNumber->click();
        $documentNumber->sendKeys(\WebDriverKeys::DELETE);
        $documentNumber->sendKeys($national_id);
        $this->someSleep();

        $lastName->click();
        $this->saveResponse();

        $confirm->click();

        if ($res = $this->waitForElement(\WebDriverBy::xpath('
                //p[contains(text(),"This email is already associated")]
                | //div[contains(text(),"The email you entered is invalid. Please verify that the email is correct.")]
                | //p[contains(text(),"nico se encuentra asociado")]
                | //p[contains(text(),"We\'re sorry, we couldn\'t complete the operation at this time. For assistance, please visit our")]
                | //li[contains(text(),"que ingresaste ya pertenece a")]
            '), 10)) {
            $this->saveResponse();

            $msg = $res->getText();

            if (strpos($msg, "complete the operation at this time. For assistance") !== false) {
                throw new \EngineError($msg);
            }

            if (strpos($msg, "The email you entered is invalid") !== false) {
                throw new \UserInputError($msg);
            }

            if (strpos($msg, 'que ingresaste ya pertenece a') !== false) {
                throw new \UserInputError('National ID is already associated to a LifeMiles account. Try again.');
            }

            if (strpos($msg, 'se encuentra asociado a otra cuenta LifeMiles.') !== false
                || strpos($msg, 'This email is already associated to a LifeMiles account') !== false) {
                throw new \UserInputError('This email is already associated to a LifeMiles account. Try with a different email.');
            }

            throw new \EngineError($msg);
        }

        if ($username = $this->waitForElement(\WebDriverBy::xpath('//input[@id="username"]'), 90)) {
            $this->saveResponse();
            $this->ErrorMessage = json_encode([
                "status"  => "success",
                "message" => "Registration is successful! Saved with email as login.",
                "active"  => true,
                "login"   => $username->getAttribute('value'),
            ], JSON_PRETTY_PRINT);

            return true;
        }

        $this->ErrorMessage = 'Something is wrong';
        $this->saveResponse();

        return false;
    }

    public function getRegisterFields()
    {
        return [
            "Email"     => [
                "Type"     => "string",
                "Caption"  => "Email address",
                "Required" => true,
            ],
            "Password"  => [
                "Type"     => "string",
                "Caption"  => "Password",
                "Note"  => "Between 8 and 15 characters. At least one uppercase. At least one lowercase. At least one number. Special characters like @ ? # $ % ( ) _ = * : ; ' . / + < > & ¿ , [.",
                "Required" => true,
            ],
            "FirstName" => [
                "Type"     => "string",
                "Caption"  => "First Name",
                "Required" => true,
            ],
            "LastName"  => [
                "Type"     => "string",
                "Caption"  => "LastName",
                "Required" => true,
            ],
        ];
    }

    protected function checkFields(&$fields)
    {
        if (!preg_match("/[A-Z0-9a-z._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}/", $fields['Email'])) {
            throw new \UserInputError('Email address contains an incorrect format');
        }

        if (preg_match("/[\d*¡!?¿<>\\ºª|\/\·@#$%&.,;=?¿())_+{}\-\[\]\"\^€\$£']/", $fields['FirstName'])) {
            throw new \UserInputError('FirstName contains an incorrect symbol');
        }

        if (preg_match("/[\d*¡!?¿<>\\ºª|\/\·@#$%&.,;=?¿())_+{}\-\[\]\"\^€\$£']/", $fields['LastName'])) {
            throw new \UserInputError('LastName contains an incorrect symbol');
        }

        if ((strlen($fields['Password']) < 8 || strlen($fields['Password']) > 15) || !preg_match("/[A-Z]/", $fields['Password'])
            || !preg_match("/[a-z]/", $fields['Password']) || !preg_match("/[0-9]/", $fields['Password']) || strpos($fields['Password'], ' ') !== false
            || !preg_match("/[*?<>\\ºª|\/\·@#$.,;=?¿())_+{}\-\[\]\"\^€\$£']/", $fields['Password']) || preg_match("/[!%&¡¿¨]/", $fields['Password'])
        ) {
            throw new \UserInputError("Between 8 and 15 characters. At least one uppercase. At least one lowercase. At least one number. Special characters like @ ? # $ % ( ) _ = * : ; ' . / + < > & ¿ , [.");
        }
    }

    private function someSleep()
    {
        usleep(random_int(12, 25) * 100000);
    }
}
