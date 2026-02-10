<?php

namespace AwardWallet\Engine\tapportugal\RewardAvailability;

use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\tapportugal\RewardAvailability\Helpers\FormFieldsInformation;

class Register extends \TAccountChecker
{
    use \SeleniumCheckerHelper;
    use \AwardWallet\Engine\ProxyList;

    private $timeout = 30;
    private $fields;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->UseSelenium();
        $this->useGoogleChrome(\SeleniumFinderRequest::CHROME_95);
        $this->disableImages();
        $this->http->saveScreenshots = true;

        $countries = ['pt', 'es', 'fr', 'be', 'it', 'at', 'uk', 'us'];
        $index = array_rand($countries);
        $this->setProxyNetNut(null, $countries[$index]);

        $request = FingerprintRequest::chrome();
        $request->browserVersionMin = 100;
        $request->platform = (random_int(0, 1)) ? 'MacIntel' : 'Win32';
        $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);

        if (isset($fingerprint)) {
            $this->http->setUserAgent($fingerprint->getUseragent());
            $this->seleniumOptions->userAgent = $fingerprint->getUseragent();
        }

        $resolutions = [
            [1152, 864],
            [1280, 720],
            [1280, 768],
            [1280, 800],
            [1360, 768],
            [1366, 768],
            [800, 600],
        ];

        $this->setScreenResolution($resolutions[array_rand($resolutions)]);
    }

    public function registerAccount(array $fields)
    {
        $this->logger->notice(__METHOD__);
        $this->fields = $fields;
        $this->logger->debug(var_export($fields, true), ['pre' => true]);

        $this->checkFields($this->fields);

        return $this->register();
    }

    public function getRegisterFields()
    {
        return FormFieldsInformation::getRegisterFields();
    }

    protected function checkFields(&$fields)
    {
        if (!preg_match("/(Mr)|(Mrs)/", $fields['Title'])) {
            throw new \UserInputError('Title contains an incorrect value');
        }

        if (preg_match("/[\d*¡!?¿<>\\ºª|\/\·@#$%&.,;=?¿())_+{}\-\[\]\"\^€\$£']/", $fields['FirstName']) || strpos($fields['FirstName'], ' ') !== false) {
            throw new \UserInputError('FirstName contains an incorrect symbol');
        }

        if (preg_match("/[\d*¡!?¿<>\\ºª|\/\·@#$%&.,;=?¿())_+{}\-\[\]\"\^€\$£']/", $fields['LastName']) || strpos($fields['LastName'], ' ') !== false) {
            throw new \UserInputError('LastName contains an incorrect symbol');
        }

        if (!preg_match("/^(0[1-9]|1[012])\/(0[1-9]|[12][0-9]|3[01])\/(19[3-9][0-9]|20[01][0-9])$/", $fields['BirthdayDate'])) {
            throw new \UserInputError('BirthdayDate contains an incorrect number');
        }

        if (!preg_match("/[A-Z0-9a-z._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}/", $fields['Email'])) {
            throw new \UserInputError('Email address contains an incorrect format');
        }

        if (strlen($fields['Password']) < 10 || strlen($fields['Password']) > 16 || !preg_match("/[A-Z]/", $fields['Password'])
            || !preg_match("/[a-z]/", $fields['Password']) || !preg_match("/[0-9]/", $fields['Password']) || strpos($fields['Password'], ' ') !== false
        ) {
            throw new \UserInputError("Your password must be 10-16 characters and include at least 1 lowercase letter, 1 uppercase letter and 1 number");
        }

        if (strlen($fields['PhoneNumber']) > 10 || preg_match("/[a-zA-Z*¡!?¿<>ºª|\·@#$%&;=?¿())_+{}\-\[\]\"\^€\$£']/", $fields['PhoneNumber'])) {
            throw new \UserInputError('Phone Number contains an incorrect number');
        }

        if (preg_match("/[\d*¡!?¿<>ºª|\·@#$%&;=?¿())_+{}\-\[\]\"\^€\$£']/", $fields['City'])) {
            throw new \UserInputError('City contains an incorrect symbol');
        }
    }

    protected function register()
    {
        try {
            $this->http->GetURL('https://booking.flytap.com/booking');

            $error = $this->waitForElement(\WebDriverBy::xpath("//h1[contains(text(), 'Access Denied')]"), 10);

            if ($error) {
                throw new \EngineError("Something going wrong. Try again later.");
            }
        } catch (\WebDriverCurlException | \TimeOutException $e) {
            $this->logger->error("Site timed out, please try again later");

            throw new \UserInputError("Site timed out, please try again later");
        }
        // accept all cookies
        $accept = $this->waitForElement(\WebDriverBy::id('onetrust-accept-btn-handler'), 0);

        if ($accept) {
            $this->logger->debug("click accept");
            $accept->click();
        }
        $this->waitFor(function () {
            return !$this->waitForElement(\WebDriverBy::id('onetrust-accept-btn-handler'), 0);
        }, 20);

        //Going to login form
        $language = $this->waitForElement(\WebDriverBy::xpath("//a[@aria-label=\"Mudar para USA\"]"), $this->timeout, false);

        if ($language) {
            $this->driver->executeScript('
                document.querySelector(\'a[aria-label="Mudar para USA"]\').click();
                let cookie = document.querySelector(\'#onetrust-accept-btn-handler\')
                if(cookie) {
                    cookie.click()
                }
            ');
            sleep(2);
        }

        if (!$btn = $this->waitForElement(\WebDriverBy::xpath("//a[normalize-space()='Login' or normalize-space()='header.text.logIn']"), 0)) {
            try {
                $this->logger->debug("[run js]: document.querySelector('#pay-miles').click();");
                $this->driver->executeScript("document.querySelector('#pay-miles').click();");
            } catch (\UnexpectedJavascriptException $e) {
                throw new \EngineError("Something going wrong. Try again later.");
            }
        }

        if ($btn = $this->waitForElement(\WebDriverBy::xpath("//a[normalize-space()='Login']"), 5)) {
            $this->waitForElement(\WebDriverBy::xpath("//a[normalize-space()='Login']"), 0);
            $this->logger->debug("login click");

            try {
                $btn->click();
            } catch (\UnrecognizedExceptionException $e) {
                throw new \EngineError("Something going wrong. Try again later.");
            }
        }

        //Going to registration form
        $registrationLink = $this->waitForElement(\WebDriverBy::xpath("//a[contains(text(),'Criar Conta') or contains(text(),'Create Account')]"), 10);
        $this->checkFieldExist(['createAccount' => $registrationLink]);
        $registrationLink->click();

        //Registration form
        $registerButton = $this->waitForElement(\WebDriverBy::xpath("//div[@id='boxButtons']/button[@type='submit']"), 10);
        $this->checkFieldExist(['registerButton' => $registerButton]);

        $this->searchAndFillRegistrationFields($this->fields);

        $token = substr($this->driver->executeScript("return sessionStorage.token"), 1, -1);

        $title = FormFieldsInformation::$title[$this->fields["Title"]];

        $payload = str_replace('"', '\"', json_encode([
            "birthdate"  => \DateTime::createFromFormat("m/d/Y", $this->fields["BirthdayDate"])->format("Y-m-d"),
            "personName" => [
                "namePrefix" => $title,
                "givenName"  => $this->fields["FirstName"],
                "surname"    => $this->fields["LastName"],
            ],
            "telephone" => [
                "countryAccessCode" => "1",
                "phoneLocationType" => "10",
                "phoneNumber"       => $this->fields["PhoneNumber"],
            ],
            "parentalEmail" => null,
            "address"       => [
                "countryCode" => 'US',
                "cityName"    => $this->fields["City"],
                "stateProv"   => $this->fields["State"],
            ],
            "password"    => $this->fields["Password"],
            "nationality" => 'US',
            "socialMedia" => [
                "mediaType" => "TP",
                "email"     => $this->fields["Email"],
            ],
            "consentsList" => ["LOYTC", "TAPPP", "TAPPROF"],
        ]));

        $script = "
            let xhr = new XMLHttpRequest();
            xhr.open(\"POST\", \"https://booking.flytap.com/bfm/rest/customer/createCustomerAccount\", false);
            xhr.setRequestHeader(\"accept\",\"application/json, text/plain, */*\")
            xhr.setRequestHeader(\"content-type\",\"application/json\")
            xhr.setRequestHeader(\"authorization\",\"Bearer {$token}\")
            xhr.withCredentials = true;
            xhr.send(\"{$payload}\")
            return xhr.response
        ";
        $this->logger->debug('Registration script: ' . PHP_EOL . var_export($script, true), ['pre' => true]);

        $response = $this->driver->executeScript($script);

        $data = $this->http->JsonLog($response, 1);

        if ($data->status != 200) {
            if (strpos($data->errors[0]->desc, 'REPEATED_CUSTOMER_EMAIL') !== false) {
                throw new \UserInputError("There's already a customer with an activated digital account associated with this email");
            }

            throw new \EngineError(print_r($data->errors, true));
        }

        $membership = $data->tapUserProfileBean->userProfile->ffNumber ?? $data->data->membershipID;

        if ($membership) {
            $this->ErrorMessage = json_encode([
                "status"  => "success",
                "message" => "Registration is successful! Membership number: TP {$membership}",
                "login"   => $this->fields['Email'],
            ], JSON_PRETTY_PRINT);

            return true;
        }
        $this->logger->error("no membershipID");

        return false;
    }

    protected function searchAndFillRegistrationFields(array $fields)
    {
        try {
            $title = $this->waitForElement(\WebDriverBy::xpath("//select[@id='selectTitle']"), 10);
            $firstName = $this->waitForElement(\WebDriverBy::xpath("//input[@id='first-name']"), 0);
            $lastName = $this->waitForElement(\WebDriverBy::xpath("//input[@id='last-name']"), 0);
            $dobDay = $this->waitForElement(\WebDriverBy::xpath("//select[@id='selectDateDaydateOfBirth']"), 0);
            $dobMonth = $this->waitForElement(\WebDriverBy::xpath("//select[@id='selectdateMonthdateOfBirth']"), 0);
            $dobYear = $this->waitForElement(\WebDriverBy::xpath("//select[@id='selectdateYeardateOfBirth']"), 0);
            $nationality = $this->waitForElement(\WebDriverBy::xpath("//select[@id='selectNationality']"), 0);
            $email = $this->waitForElement(\WebDriverBy::xpath("//input[@id='eadd']"), 0);
            $password = $this->waitForElement(\WebDriverBy::xpath("//input[@id='password']"), 0);
            $confirmPassword = $this->waitForElement(\WebDriverBy::xpath("//input[@id='repassword']"), 0);
            $phoneType = $this->waitForElement(\WebDriverBy::xpath("//select[@id='selectPhoneType']"), 0);
            $countryCode = $this->waitForElement(\WebDriverBy::xpath("//select[@id='emergency-contact-phone-country']"), 0);
            $phoneNumber = $this->waitForElement(\WebDriverBy::xpath("//input[@id='emergency-contact-phone-number']"), 0);
            $city = $this->waitForElement(\WebDriverBy::xpath("//input[@id='sign-up--city']"), 0);
            $countryOfResidence = $this->waitForElement(\WebDriverBy::xpath("//select[@id='selectCountry']"), 0);
            $radio = $this->waitForElement(\WebDriverBy::xpath("//label[@for='treatment_consent-treatment_consent-yes']"), 0);
            $checkbox = $this->waitForElement(\WebDriverBy::xpath("//label[@for='formCreate']"), 0);

            $this->checkFieldExist([
                'title'              => $title,
                'firstName'          => $firstName,
                'lastName'           => $lastName,
                'dobDay'             => $dobDay,
                'dobMonth'           => $dobMonth,
                'dobYear'            => $dobYear,
                'nationality'        => $nationality,
                'email'              => $email,
                'password'           => $password,
                'confirmPassword'    => $confirmPassword,
                'phoneType'          => $phoneType,
                'countryCode'        => $countryCode,
                'phoneNumber'        => $phoneNumber,
                'city'               => $city,
                'countryOfResidence' => $countryOfResidence,
                'radio'              => $radio,
                'checkbox'           => $checkbox,
            ]);

            $this->saveResponse();

            $title->click();
            $firstName->click();
            $lastName->click();
            $dobDay->click();
            $dobMonth->click();
            $dobYear->click();
            $nationality->click();
            $email->click();
            $password->click();
            $confirmPassword->click();

            $this->saveResponse();

            $phoneType->click();
            $countryCode->click();
            $phoneNumber->click();
            $city->click();
            $countryOfResidence->click();

            $this->saveResponse();

            $radio->click();
            $checkbox->click();

            $this->saveResponse();
        } catch (\WebDriverCurlException $e) {
            $this->logger->error("Error: {$e->getMessage()}");

            throw new \EngineError("Error: {$e->getMessage()}");
        }
    }

    protected function checkFieldExist(array $fields)
    {
        foreach ($fields as $key => $field) {
            if (!$field) {
                $this->logger->error("{$key} field is not exist");

                throw new \EngineError("{$key} field is not exist");
            }
        }
    }
}
