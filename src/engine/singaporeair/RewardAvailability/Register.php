<?php

namespace AwardWallet\Engine\singaporeair\RewardAvailability;

use AwardWallet\Common\Parsing\MailslurpApiControllersCustom;
use AwardWallet\Engine\singaporeair\RewardAvailability\Helpers\FormFieldsInformation;
use MailSlurp\Models\MatchOption;
use MailSlurp\Models\MatchOptions;

class Register extends \TAccountChecker
{
    use \SeleniumCheckerHelper;
    use \AwardWallet\Engine\ProxyList;

    private $timeout = 30;
    private $fields;
    /** @var MailslurpApiControllersCustom */
    private $mailslurpApiComtrollers;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;

        $this->mailslurpApiComtrollers = $this->services->get(MailslurpApiControllersCustom::class);

        $this->UseSelenium();
        $this->useChromium(\SeleniumFinderRequest::CHROMIUM_80);
        $this->setProxyNetNut(null, 'ca');
        $this->disableImages();
        $this->http->saveScreenshots = true;

        $resolutions = [
            [1152, 864],
            [1280, 720],
            [1280, 768],
            [1280, 800],
            [1360, 768],
            [1366, 768],
        ];

        $this->setScreenResolution($resolutions[array_rand($resolutions)]);
        $this->useCache();
        $this->http->setRandomUserAgent(null, false, true);
    }

    public function registerAccount(array $fields)
    {
        $this->logger->notice(__METHOD__);
        $this->fields = $fields;

        if (strstr($this->fields['Email'], 'mailslurp')
            || strstr($this->fields['Email'], 'vmversion')) {
            $this->fields['inboxId'] = $this->mailslurpApiComtrollers
                ->getInboxControllerApi()
                ->getInboxByEmailAddress($fields['Email'])
                ->getInboxId();
        }

        $this->logger->info(var_export($this->fields, true), ['pre' => true]);

        $this->modifyFields($this->fields);
        $this->checkFields($this->fields);

        $this->register();

        $this->saveResponse();

        $question = $this->waitForElement(\WebDriverBy::xpath("//p[contains(text(),'A verification email has been sent to you.')]"), 10);

        if ($question) {
            if (strstr($this->fields['Email'], 'mailslurp')
                || strstr($this->fields['Email'], 'vmversion')) {
                return $this->mailslurpActivation();
            }

            return $this->checkSendEmail();
        }

        $error = $this->waitForElement(\WebDriverBy::xpath('
            //p[contains(text(),"already registered as a KrisFlyer member")]
            | //p[contains(text(),"Please enter a valid email address")]
            | //p[contains(text(),"process your request")]'), 10);

        if ($error) {
            $this->ErrorMessage = $error->getText();

            throw new \UserInputError($error->getText());
        }

        $error = $this->waitForElement(\WebDriverBy::xpath('//p[contains(@class,"text-error")]/span'), 0);

        if (!empty($error)) {
            $this->ErrorMessage = $error->getText();

            throw new \UserInputError($error->getText());
        }

        throw new \EngineError('Unknown scenario, check it out');
    }

    public function ProcessStep($step)
    {
        $this->logger->notice(__METHOD__);

        if (!$this->Question) {
            $this->logger->error("something went wrong");

            return false;
        }

        if (isset($this->Answers[$this->Question])) {
            $this->logger->info('Got verification link.');
            $verificationLink = $this->Answers[$this->Question];
            unset($this->Answers[$this->Question]);

            return $this->useVerificationLink($verificationLink);
        }

        $this->logger->info('Answer is empty.');
        $this->sendNotification("Go to email {$this->State['email']} and confirm it.");
        $this->ErrorMessage = json_encode([
            "status"  => "success",
            "message" => "Registration is successful! Go to email and confirm it.",
            "login"   => $this->State['email'],
            "active"  => false,
        ], JSON_PRETTY_PRINT);

        return true;
    }

    public function getRegisterFields()
    {
        $this->logger->notice(__METHOD__);

        return FormFieldsInformation::getRegisterFields();
    }

    protected function checkFields(&$fields)
    {
        $this->logger->notice(__METHOD__);

        if (preg_match("/[\d*¡!?¿<>ºª|\·#$%&.,;=?¿_+{}\-\[\]\^€\$£]/", $fields['FirstName']) || strpos($fields['FirstName'], ' ') !== false
            || strlen($fields['FirstName']) < 2 || strlen($fields['FirstName']) > 25) {
            throw new \UserInputError('FirstName contains an incorrect symbol');
        }

        if (preg_match("/[\d*¡!?¿<>ºª|\·#$%&.,;=?¿_+{}\-\[\]\^€\$£]/", $fields['LastName']) || strpos($fields['LastName'], ' ') !== false
            || strlen($fields['LastName']) < 2 || strlen($fields['LastName']) > 26) {
            throw new \UserInputError('LastName contains an incorrect symbol');
        }

        if (!preg_match("/[A-Z0-9a-z._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}/", $fields['Email'])) {
            throw new \UserInputError('Email address contains an incorrect format');
        }

        if (strlen($fields['Password']) < 8 || strlen($fields['Password']) > 16 || !preg_match("/[A-Z]/", $fields['Password'])
            || !preg_match("/[a-z]/", $fields['Password']) || !preg_match("/[0-9]/", $fields['Password']) || strpos($fields['Password'], ' ') !== false
        ) {
            throw new \UserInputError("Your password must be 10-16 characters and include at least 1 lowercase letter, 1 uppercase letter and 1 number");
        }

        if (strlen($fields['MobileAreaCode']) > 4 || preg_match("/[a-zA-Z*¡!?¿<>ºª|\·@#$%&;=?¿())_+{}\-\[\]\"\^€\$£']/", $fields['MobileAreaCode'])) {
            throw new \UserInputError('Area Code contains an incorrect number');
        }

        if (strlen($fields['PhoneNumber']) > 10 || preg_match("/[a-zA-Z*¡!?¿<>ºª|\·@#$%&;=?¿())_+{}\-\[\]\"\^€\$£']/", $fields['PhoneNumber'])) {
            throw new \UserInputError('Phone Number contains an incorrect number');
        }

        if (!preg_match("/^(0[1-9]|1[012])\/(0[1-9]|[12][0-9]|3[01])\/(19[3-9][0-9]|20[01][0-9])$/", $fields['BirthdayDate'])) {
            throw new \UserInputError('BirthdayDate contains an incorrect number');
        }
    }

    protected function register()
    {
        $this->logger->notice(__METHOD__);

        $this->getAction('https://www.singaporeair.com/en_UK/ppsclub-krisflyer/registration-form/');

        //Register form
        $btnContinue = $this->waitForElement(\WebDriverBy::xpath("//input[@id='btnContinue']"), $this->timeout);
        $this->checkFieldExist(['btnContinue' => $btnContinue]);

        if ($cookie = $this->waitForElement(\WebDriverBy::xpath("//div[contains(text(), 'Accept all cookies')]"))) {
            $cookie->click();
        }

        $this->searchAndFillRegistrationFields($this->fields);
        $this->driver->executeScript("document.querySelector('#btnContinue').style.zIndex = '2147483647'; document.querySelector('#btnContinue').click();");

        $challenge = $this->waitForElement(\WebDriverBy::xpath("//title[contains(text(),'Challenge Validation')]"), 10, false);
        $recaptcha = $this->waitForElement(\WebDriverBy::xpath("//iframe[contains(@src,'recaptcha')]"), 0, false);

        $this->saveResponse();

        if ($challenge && $recaptcha) {
            $key = $this->http->FindSingleNode('//iframe[contains(@src,"recaptcha")]/@data-key');
            $token = $this->parseReCaptchaItinerary($key);

            if ($token) {
                $this->driver->executeScript("
                const xhr = new XMLHttpRequest();
                var res = false;
                xhr.open(\"GET\", \"https://www.singaporeair.com/_sec/cp_challenge/verify?cpt-token={$token}\");
                xhr.send();");

                sleep(3);
                $this->driver->executeScript("document.location.reload();");
                $this->saveResponse();
            }
        }
    }

    protected function checkSendEmail()
    {
        $this->logger->notice(__METHOD__);

        $question = $this->waitForElement(\WebDriverBy::xpath("//p[contains(text(),'A verification email has been sent to you.')]"), 10);

        if ($question) {
            $this->State['email'] = $this->fields['Email'];

            $question = substr($question->getText(), 0, 200);
            $this->holdSession();
            $this->AskQuestion($question, null, 'Question');

            return false;
        } else {
            $error = $this->waitForElement(\WebDriverBy::xpath('
                //p[contains(text(),"already registered as a KrisFlyer member")]
                | //p[contains(text(),"process your request")]'), 10);

            if ($error) {
                $this->ErrorMessage = $error->getText();

                throw new \UserInputError($error->getText());
            }
        }

        $this->saveResponse();

        $this->ErrorMessage = "Something going wrong";

        throw new \CheckException("Something going wrong");
    }

    protected function useVerificationLink($link)
    {
        $this->getAction($link);

        $error = $this->waitForElement(\WebDriverBy::xpath("//p[contains(text(), 'We cannot process your request right now')]"), 5);

        if (!$error) {
            $success = $this->waitForElement(\WebDriverBy::xpath("//h3[contains(text(), 'KrisFlyer membership number*')]"), $this->timeout);

            if ($success) {
                $membershipNumber = str_replace(' ', '',
                    str_replace('KrisFlyer membership number*', '', $success->getText())
                );

                $this->ErrorMessage = json_encode([
                    "status"  => "success",
                    "message" => "Registration is successful and email is confirmed! Membership number: {$membershipNumber}. ",
                    "active"  => true,
                    "login"   => $membershipNumber,
                ], JSON_PRETTY_PRINT);

                return true;
            }

            throw new \EngineError('Unknown scenario, check it out');
        }

        throw new \EngineError($error->getText());
    }

    protected function searchAndFillRegistrationFields(array $fields)
    {
        $this->logger->notice(__METHOD__);

        try {
            $titleBtn = $this->waitForElement(\WebDriverBy::xpath("//input[@id='customSelect-0-combobox']"), 0, false);
            $this->checkFieldExist(['titleBtn' => $titleBtn]);
            $this->driver->executeScript("document.querySelector('#{$titleBtn->getAttribute('id')}').click();");
            $title = $this->waitForElement(\WebDriverBy::xpath("//ul[@id='customSelect-0-listbox']/li[@data-value='{$this->fields['Title']}']"), 10, false);
            $firstName = $this->waitForElement(\WebDriverBy::xpath("//input[@id='givenName']"), 0);
            $lastName = $this->waitForElement(\WebDriverBy::xpath("//input[@id='familyName']"), 0);

            $this->checkFieldExist([
                'title'     => $title,
                'firstName' => $firstName,
                'lastName'  => $lastName,
            ]);

            $this->driver->executeScript("document.querySelector('#{$title->getAttribute('id')}').click();");
            $firstName->sendKeys($fields['FirstName']);
            $lastName->sendKeys($fields['LastName']);

            $this->driver->executeScript("document.querySelectorAll('.custom-dropdown')[0].style.display = 'block';");
            $birthDay = $this->waitForElement(\WebDriverBy::xpath("//ul[@id='customSelect-1-listbox']/li[@data-value='{$this->fields['BirthDay']}']"), 15, false);
            $this->checkFieldExist(['birthDay' => $birthDay]);
            $this->driver->executeScript("document.querySelector('#{$birthDay->getAttribute('id')}').click();");

            $this->driver->executeScript("document.querySelectorAll('.custom-dropdown')[1].style.display = 'block';");
            $birthMonth = $this->waitForElement(\WebDriverBy::xpath("//ul[@id='customSelect-2-listbox']/li[@data-value='{$this->fields['BirthMonth']}']"), 15, false);
            $this->checkFieldExist(['birthMonth' => $birthMonth]);
            $this->driver->executeScript("document.querySelector('#{$birthMonth->getAttribute('id')}').click();");

            $this->driver->executeScript("document.querySelectorAll('.custom-dropdown')[2].style.display = 'block';");
            $birthYear = $this->waitForElement(\WebDriverBy::xpath("//ul[@id='customSelect-3-listbox']/li[@data-value='{$this->fields['BirthYear']}']"), 15, false);
            $this->checkFieldExist(['birthYear' => $birthYear]);
            $this->driver->executeScript("document.querySelector('#{$birthYear->getAttribute('id')}').click();");

            $this->saveResponse();

            $this->driver->executeScript("document.querySelector('#mobileNumberCountryInput').style.zIndex = '100000';");
            $mobileNumberCountryInput = $this->waitForElement(\WebDriverBy::xpath("//input[@id='mobileNumberCountryInput']"), 0, false);
            $this->checkFieldExist(['mobileNumberCountryInput' => $mobileNumberCountryInput]);
            $mobileNumberCountryInput->click();
            $mobileNumberCountryItem = $this->waitForElement(\WebDriverBy::xpath("//ul[contains(@class,'mobileNumberCountryInput')]//li[contains(@data-value, 'Of Ameri')]/a"), 15, false);
            $this->checkFieldExist(['mobileNumberCountryItem' => $mobileNumberCountryItem]);
            $this->driver->executeScript("document.querySelector('#{$mobileNumberCountryItem->getAttribute('id')}').click();");

            $this->driver->executeScript("document.querySelector('#countryInput').style.zIndex = '100001';");
            $countryInput = $this->waitForElement(\WebDriverBy::xpath("//input[@id='countryInput']"), 0, false);
            $this->checkFieldExist(['countryInput' => $countryInput]);
            $countryInput->click();
            $country = $this->waitForElement(\WebDriverBy::xpath("//ul[contains(@class,'countryInput')]//li[@data-key=\"US\"]/a"), 15, false);
            $this->checkFieldExist(['country' => $country]);
            $this->driver->executeScript("document.querySelector('#{$country->getAttribute('id')}').click();");

            $email = $this->waitForElement(\WebDriverBy::xpath("//input[@id='email']"), 0);
            $confirmationEmail = $this->waitForElement(\WebDriverBy::xpath("//input[@id='confirmationEmail']"), 0);
            $mobileAreaCode = $this->waitForElement(\WebDriverBy::xpath("//input[@id='mobileAreaCode']"), 0);
            $mobilePhoneNumber = $this->waitForElement(\WebDriverBy::xpath("//input[@id='mobilePhoneNumber']"), 0);

            $this->checkFieldExist([
                'email'             => $email,
                'confirmationEmail' => $confirmationEmail,
                'mobileAreaCode'    => $mobileAreaCode,
                'mobilePhoneNumber' => $mobilePhoneNumber,
            ]);

            $email->sendKeys($fields['Email']);
            $confirmationEmail->sendKeys($fields['Email']);
            $mobileAreaCode->sendKeys($fields['MobileAreaCode']);
            $mobilePhoneNumber->sendKeys(substr($fields['PhoneNumber'], 3));

            $this->driver->executeScript("document.querySelector('#stateInput').style.zIndex = '100002';");
            $stateInput = $this->waitForElement(\WebDriverBy::xpath("//input[@id='stateInput' and not(@disabled)]"), 15);
            $this->checkFieldExist(['stateInput' => $stateInput]);
            $stateInput->click();
            $country = $this->waitForElement(\WebDriverBy::xpath("//ul[contains(@class,'stateInput')]//li[contains(@data-key, '{$fields['State']}')]/a"), 15, false);
            $this->checkFieldExist(['country' => $country]);
            $this->driver->executeScript("document.querySelector('#{$country->getAttribute('id')}').click();");

            $this->saveResponse();

            $password = $this->waitForElement(\WebDriverBy::xpath("//input[@id='password']"), 0);
            $confirmPassword = $this->waitForElement(\WebDriverBy::xpath("//input[@id='confirmPassword']"), 0);

            $this->checkFieldExist([
                'password'        => $password,
                'confirmPassword' => $confirmPassword,
            ]);

            $password->sendKeys($fields['Password']);
            $confirmPassword->sendKeys($fields['Password']);

            $this->saveResponse();

            $this->driver->executeScript("document.querySelector('#receiveEmailStatment').style.zIndex = '100003';");
            $checkBox1 = $this->waitForElement(\WebDriverBy::xpath("//input[@id='receiveEmailStatment']"), 0, false);
            $this->checkFieldExist(['checkBox1' => $checkBox1]);
            $checkBox1->click();

            $this->driver->executeScript("document.querySelector('#tncAggreement_otr').style.zIndex = '100004';");
            $checkBox2 = $this->waitForElement(\WebDriverBy::xpath("//input[@id='tncAggreement_otr']"), 0, false);
            $this->checkFieldExist(['checkBox2' => $checkBox2]);
            $this->driver->executeScript("document.querySelector('#tncAggreement_otr').click();");

            $recaptcha = $this->waitForElement(\WebDriverBy::xpath("//iframe[contains(@src,'recaptcha')]/../.."), 15, false);

            if ($recaptcha) { //We check this way, because sometimes it does not work
                $this->driver->executeScript("
                    document.querySelector('[title=\"recaptcha challenge expires in two minutes\"]').parentElement.parentElement.style.visibility = 'hidden';
                    document.querySelector('[title=\"recaptcha challenge expires in two minutes\"]').parentElement.parentElement.style.display = 'none';
                    ");
            }

            $this->saveResponse();
        } catch (\WebDriverCurlException $e) {
            $this->logger->error("Something going wrong, try again");
            $this->ErrorMessage = "Something going wrong, try again.";

            throw new \CheckException("Error: {$e->getMessage()}");
        }
    }

    protected function modifyFields(array &$fields)
    {
        $this->logger->notice(__METHOD__);

        foreach ($fields as $key => $value) {
            if ($key !== 'Password') {
                $value = trim($value);
            }

            if ($key == 'BirthdayDate') {
                $fields['BirthDay'] = \DateTime::createFromFormat("m/d/Y", $this->fields["BirthdayDate"])->format("j");
                $fields['BirthMonth'] = \DateTime::createFromFormat("m/d/Y", $this->fields["BirthdayDate"])->format("n");
                $fields['BirthYear'] = \DateTime::createFromFormat("m/d/Y", $this->fields["BirthdayDate"])->format("Y");
            }

            $fields[$key] = $value;
        }
    }

    protected function getAction($url)
    {
        $this->logger->notice(__METHOD__);

        try {
            $this->http->GetURL($url);
        } catch (\WebDriverCurlException $e) {
            $this->logger->error("Site timed out, please try again later");

            throw new \UserInputError("Site timed out, please try again later");
        }
    }

    protected function checkFieldExist(array $fields)
    {
        foreach ($fields as $key => $field) {
            if (!$field) {
                $this->logger->error("{$key} field is not exist");
                $this->ErrorMessage = "{$key} field is not exist";

                throw new \CheckException("{$key} field is not exist");
            }
        }
    }

    protected function parseReCaptchaItinerary($key)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->debug("data-sitekey: {$key}");

        if (!$key) {
            return false;
        }

        // watchdog workaround
        $this->increaseTimeLimit(180);

        $recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_ANTIGATE_API_V2);
        $recognizer->RecognizeTimeout = 120;
        $parameters = [
            "type"         => "RecaptchaV2TaskProxyless",
            "websiteURL"   => $this->http->currentUrl(),
            "websiteKey"   => $key,
        ];
        $captcha = $this->recognizeAntiCaptcha($recognizer, $parameters);

        return $captcha;
    }

    protected function getVerificationLink(string $inboxId, array $matches): ?string
    {
        $matchOptions = [];

        foreach ($matches as $match) {
            $matchOptions[] = new MatchOption($match);
        }

        $email = $this->mailslurpApiComtrollers
            ->getWaitForControllerApi()
            ->waitForMatchingEmails(
                $inboxId,
                1,
                (new MatchOptions())->setMatches($matchOptions),
                null,
                null,
                null,
                null,
                30000000
            )[0];

        return $this->mailslurpApiComtrollers
            ->getEmailControllerApi()
            ->getEmailHTML($email->getId());
    }

    protected function mailslurpActivation()
    {
        $html = $this->getVerificationLink(
            $this->fields['inboxId'],
            [
                [
                    'field'  => 'SUBJECT',
                    'should' => 'CONTAIN',
                    'value'  => 'Account Activation',
                ],
                [
                    'field'  => 'FROM',
                    'should' => 'CONTAIN',
                    'value'  => '@singaporeair',
                ],
            ]
        );

        $this->http->SetEmailBody($html, true);

        $link = $this->http->FindSingleNode('//td[@class="desktop"]//a[contains(text(),"click here")]/@href');

        $this->logger->error($link);

        return $this->useVerificationLink($link);
    }
}
