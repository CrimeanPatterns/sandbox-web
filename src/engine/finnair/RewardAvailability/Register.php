<?php

namespace AwardWallet\Engine\finnair\RewardAvailability;

use AwardWallet\Engine\ProxyList;

class Register extends \TAccountChecker
{
    use ProxyList;
    use \SeleniumCheckerHelper;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->UseSelenium();
        $this->useGoogleChrome(\SeleniumFinderRequest::CHROME_95);
        $this->setProxyNetNut();
        $this->http->saveScreenshots = true;
    }

    public function registerAccount(array $fields)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->debug(var_export($fields, true), ['pre' => true]);

        $this->checkFields($fields);

        $this->http->GetURL('https://auth.finnair.com/content/en/join/finnair-plus');

        $internal_err = $this->waitForElement(\WebDriverBy::xpath('//*[contains(text(),"request could not be processed")]'), 5);

        if ($internal_err) {
            //throw new \EngineError($internal_err->getText());
            throw new \ProviderError($internal_err->getText());
        }

        $email = $this->waitForElement(\WebDriverBy::xpath('//input[@type="email"]'), 10);
        $pass = $this->waitForElement(\WebDriverBy::xpath('//input[@type="password"]'), 0);

        $firstName = $this->waitForElement(\WebDriverBy::xpath('//input[@type="text"][@formcontrolname="firstName"]'), 0);
        $lastName = $this->waitForElement(\WebDriverBy::xpath('//input[@type="text"][@formcontrolname="lastName"]'), 0);

        if (!$email || !$pass || !$firstName || !$lastName) {
            //$this->saveResponse();
            $this->logger->error('no register form or other format 1');

            return false;
        }

        if ($fields['Gender'] === 'female') {
            $gender = $this->waitForElement(\WebDriverBy::xpath('//span[contains(text(),"Female")]'), 10);
            $fields['PickedGender'] = 'Female';
        } else {
            $gender = $this->waitForElement(\WebDriverBy::xpath('//span[contains(text(),"Male")]'), 10);
            $fields['PickedGender'] = 'Male';
        }

        $month = $this->waitForElement(\WebDriverBy::xpath('//input[@type="number"][@formcontrolname="month"]'), 0);
        $day = $this->waitForElement(\WebDriverBy::xpath('//input[@type="number"][@formcontrolname="day"]'), 0);
        $year = $this->waitForElement(\WebDriverBy::xpath('//input[@type="number"][@formcontrolname="year"]'), 0);

        $mover = new \MouseMover($this->driver);
        $mover->logger = $this->logger;

        $email->click();
        $mover->sendKeys($email, $fields['Email'], 5);
        /*
                $email_invalid = $this->waitForElement(\WebDriverBy::xpath('//input[@formcontrolname="email"]/ancestor::div[contains(@class,"form__group invalid" )]//div[@id="input-invalid"]/p'), 5);
                if ($email_invalid) {
                    throw new \UserInputError($email_invalid->getText());
                }
                $this->logger->debug("-email ok-");
        */
        $this->logger->debug("-email entered-");
        $this->saveResponse();
        //$email->sendKeys($fields['Email']);

        $pass->click();
        $mover->sendKeys($pass, $fields['Password'], 5);
        $this->saveResponse();
        //$pass->sendKeys($fields['Password']);

        $firstName->click();
        $mover->sendKeys($firstName, $fields['FirstName'], 5);
        //$firstName->sendKeys($fields['FirstName']);

        $lastName->click();
        $mover->sendKeys($lastName, $fields['LastName'], 5);
        //$lastName->sendKeys($fields['LastName']);

        $gender->click();

        $fields['BirthDay'] = \DateTime::createFromFormat("m/d/Y", $fields["BirthdayDate"])->format("j");
        $fields['BirthMonth'] = \DateTime::createFromFormat("m/d/Y", $fields["BirthdayDate"])->format("n");
        $fields['BirthYear'] = \DateTime::createFromFormat("m/d/Y", $fields["BirthdayDate"])->format("Y");

        $month->click();
        $mover->sendKeys($month, $fields['BirthMonth'], 5);
        //$month->sendKeys($fields['BirthMonth']);

        $day->click();
        $mover->sendKeys($day, $fields['BirthDay'], 5);
        //$day->sendKeys($fields['BirthDay']);

        $year->click();
        $mover->sendKeys($year, $fields['BirthYear'], 5);
        //$year->sendKeys($fields['BirthYear']);
        $this->saveResponse();

        $fields['CountryCode'] = $this->driver->executeScript('
            function doEvent( obj, event ) {
                var event = new Event( event, {target: obj, bubbles: true} );
                    return obj ? obj.dispatchEvent(event) : false;
            };
            
            let countryCode = document.querySelector(\'select[formcontrolname="countryCode"]\');
            countryCode.value = "US";
                    //document.querySelector(\'select[formcontrolname="countryCode"]\').value = "US"; 
            
            doEvent(document.querySelector(\'select[formcontrolname="countryCode"]\'), "change");
            
            return countryCode.value;
        ');

        $phone = $this->waitForElement(\WebDriverBy::xpath('//input[@formcontrolname="phone"]'), 0);
        $mover->sendKeys($phone, $fields['PhoneNumber'], 5);
        $this->saveResponse();
        /*
                $phone_invalid = $this->waitForElement(\WebDriverBy::xpath('//input[@formcontrolname="phone"]/ancestor::div[contains(@class,"form__group invalid" )]//div[@id="input-invalid"]/p'), 10);
                if ($phone_invalid) {
                    throw new \UserInputError($phone_invalid->getText());
                }
                $this->logger->debug("-phone ok-");
        */
        $this->logger->debug("-phone entered-");

        $savedFields = $this->driver->executeScript('
            let name = document.querySelector(\'input[formcontrolname="firstName"]\').value;
            let lastName =document.querySelector(\'input[formcontrolname="lastName"]\').value;
            let bDay = document.querySelector(\'input[formcontrolname="day"]\').value;
            let bMonth = document.querySelector(\'input[formcontrolname="month"]\').value;
            let bYear = document.querySelector(\'input[formcontrolname="year"]\').value; 
            let el = document.querySelector(\'input[formcontrolname="gender"]:checked\');
            let gender = el.parentNode.childNodes[2].innerText;   
            let phone = document.querySelector(\'input[formcontrolname="phone"]\').value;
            let email = document.querySelector(\'input[formcontrolname="email"]\').value;
            let password = document.querySelector(\'input[formcontrolname="password"]\').value;
            
            let user = {
                name: name,
                lastName: lastName,
                bDay: bDay,
                bMonth: bMonth,
                bYear: bYear,
                gender: gender,
                phone: phone,
                email: email,
                password: password                          
            }
            
            return JSON.stringify(user);            
        ');

        $this->driver->executeScript("document.querySelector('button.button').scrollIntoView({block: \"end\"});");
        $this->driver->executeScript('
            document.querySelector(\'input[type="checkbox"]\').click();
        ');

        $this->saveResponse();

        //$this->http->FilterHTML = false;
        //$this->http->SetBody($this->http->Response['body'] = preg_replace("#\b(ns|xmlns)\b=\"[^\"]+\"#i", '', $this->http->Response['body']));
        //$inputErrors = $this->http->FindNodes("//form//app-input-error//p");

        $inputErrors = $this->http->FindNodes("//form//p[@class='ng-star-inserted']");

        if (!empty($inputErrors)) {
            throw new \UserInputError(implode('; ', $inputErrors));
        }

        $nextBtn = $this->waitForElement(\WebDriverBy::xpath('//button[@type="submit" and not(@disabled)]'), 10);
        $this->saveResponse();

        if (!$nextBtn) {
            throw new \EngineError('no submit button placed on resource');
        }

        $this->logger->debug("-Ready to click Submit btn -");
        $nextBtn->click();ф

        $confirm = $this->waitForElement(\WebDriverBy::xpath('//span[contains(text(),"Save and continue")]/parent::button[@type="submit"]'), 10);
        $this->saveResponse();

        if (!$confirm) {
            throw new \EngineError('confirm button not found');
        }
        $this->logger->debug("-confirm button ok-");

        // на этом шаге вряд ли получим "something went wrong" но на всякий случай
        //$sorry_msg = $this->driver->findElement(\WebDriverBy::xpath('//*[contains(text(),"something went wrong")]'));
        $sorry_msg = $this->waitForElement(\WebDriverBy::xpath('//*[contains(text(),"something went wrong")]'), 5);

        if ($sorry_msg) {
            $this->logger->error($sorry_msg->getText());
            $this->logger->debug("-smth went wrong Point1 -");
            //throw new \ProviderError($sorry_msg->getText());
        }
        $this->saveResponse();
        $this->logger->debug("-Ready for click Save and Continue btn -");
        $confirm->click();
        $this->logger->debug("-Save and Continue btn clicked -");

        $this->saveResponse();

        // тут  либо получаем membership либо 'something went wrong'
        $sorry_msg2 = $this->waitForElement(\WebDriverBy::xpath('//*[contains(text(),"something went wrong")]'), 5);

        if ($sorry_msg2) {
            $this->logger->error($sorry_msg2->getText());
            $this->logger->debug("-smth went wrong Point2 -");

            $confirm = $this->waitForElement(\WebDriverBy::xpath('//span[contains(text(),"Save and continue")]/parent::button[@type="submit"]'), 10);
            $this->saveResponse();

            if (!$confirm) {
                throw new \EngineError('confirm button2 not found');
            }
            $this->logger->debug("-Save and Continue btn2 ok-");
            $confirm->click();
            $this->logger->debug("-Save and Continue btn2 clicked -");

            $res = $this->waitForElement(\WebDriverBy::xpath(' //div[@class="form-container"]/descendant::p[contains(text(),"membership number")]'), 10);
            $this->saveResponse();

            if (!$res) {
                throw new \EngineError('Registration failed (point A)');
            }

            try {
                $welcome_msg = $this->driver->findElement(\WebDriverBy::xpath('//div[@class="form-container"]/descendant::p[contains(text(),"membership number")]'));
            } catch (\NoSuchElementException | \WebDriverException $e) {
                throw new \EngineError('Membership number not found (point A)');
            }
        } else {
            $res = $this->waitForElement(\WebDriverBy::xpath(' //div[@class="form-container"]/descendant::p[contains(text(),"membership number")]'), 10);
            $this->saveResponse();

            if (!$res) {
                throw new \EngineError('Registration failed (point B)');
            }

            try {
                $welcome_msg = $this->driver->findElement(\WebDriverBy::xpath('//div[@class="form-container"]/descendant::p[contains(text(),"membership number")]'));
            } catch (\NoSuchElementException | \WebDriverException $e) {
                throw new \EngineError('Membership number not found (point B)');
            }
        }

        //$this->waitForElement(\WebDriverBy::xpath('//p[contains(text(),"something went wrong")] | //div[@class="form-container"]/descendant::p[contains(text(),"membership number")]'), 10);

        $welcome_msg_text = $welcome_msg->getText();
        $membershipNumber = $this->http->FindPreg('/^.*?(\d+)$/', false, $welcome_msg_text);

        if ($membershipNumber) {  //$welcome_msg
            $this->logger->debug("--saveRegisterFields-");

            $savedFields = json_decode($savedFields, true);

            $fullBirthdayDate = $savedFields['bDay'] . "/" . $savedFields['bMonth'] . "/" . $savedFields['bYear'];

            $this->ErrorMessage = json_encode([
                "status"          => "success",
                "message"         => "$welcome_msg_text! Membership number: {$membershipNumber}",
                "login"           => $membershipNumber,
                "login2"          => $fields['LastName'],
                "login3"          => "",
                "password"        => $fields['Password'],
                "email"           => $fields['Email'],
                "registerInfo"    => [
                    [
                        "key"      => "First Name",
                        "value"    => $savedFields['name'],
                    ],
                    [
                        "key"      => "Last Name",
                        "value"    => $savedFields['lastName'],
                    ],
                    [
                        "key"      => "Gender",
                        "value"    => $savedFields['gender'],
                    ],
                    [
                        "key"      => "PhoneNumber",
                        "value"    => $savedFields['phone'],
                    ],
                    [
                        "key"      => "BirthdayDate",
                        "value"    => $fullBirthdayDate,
                    ],
                ],

                "active"    => true,
            ], JSON_PRETTY_PRINT);

            return true;
        }

        $this->ErrorMessage = 'Something is wrong';

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
                "Note"     => "Between 8 and 32 characters. At least one uppercase. At least one lowercase. At least one number. At least one special character. Special characters like @ ? # $ % ( ) _ = * : ; ' . / + < > & ¿ , [. ! % & ¡",
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
            'Gender' => [
                'Type'     => 'string',
                'Caption'  => 'Gender',
                'Required' => true,
                'Options'  => ['male' => 'Male', 'female' => 'Femail'],
            ],
            'PhoneNumber' => [
                'Type'     => 'string',
                'Caption'  => 'Phone number length should be 5-10 symbols long. Only digits are allowed.',
                'Required' => true,
            ],
            'BirthdayDate' => [
                'Type'     => 'date',
                'Caption'  => 'Your date of birth, older than 18 (MM/DD/YYYY)',
                'Required' => true,
            ],
        ];
    }

    protected function checkFields(&$fields)
    {
        if (!filter_var($fields['Email'], FILTER_VALIDATE_EMAIL)) {
            throw new \UserInputError('Email address contains an incorrect format');
        }

        if (preg_match("/[\d*¡!?¿<>\\ºª|\/\·@#$%&.,;=?¿())_+{}\-\[\]\"\^€\$£']/", $fields['FirstName'])) {
            throw new \UserInputError('FirstName contains an incorrect symbol');
        }

        if (preg_match("/[\d*¡!?¿<>\\ºª|\/\·@#$%&.,;=?¿())_+{}\-\[\]\"\^€\$£']/", $fields['LastName'])) {
            throw new \UserInputError('LastName contains an incorrect symbol');
        }

        if ((strlen($fields['Password']) < 8 || strlen($fields['Password']) > 32) || !preg_match("/[A-Z]/", $fields['Password'])
            || !preg_match("/[a-z]/", $fields['Password']) || !preg_match("/[0-9]/", $fields['Password']) || strpos($fields['Password'], ' ') !== false
            || !preg_match("/[*?!%&¡<>\\ºª|\/\·@#$.,;=?¿())_+{}\-\[\]\"\^€\$£']/", $fields['Password'])
        ) {
            throw new \UserInputError("Between 8 and 32 characters. At least one uppercase. At least one lowercase. At least one number. At least one special character. Special characters like @ ? # $ % ( ) _ = * : ; ' . / + < > & ¿ , [. ! % & ¡");
        }

        /*
          if (!(preg_match("/^\+{0,1}1[0-9]{10}$/", $fields['PhoneNumber'])
              || preg_match("/^[0-9]{10}$/", $fields['PhoneNumber']))
          ) {
              // preg_match("/\D/", $fields['PhoneNumber']
              throw new \UserInputError("US phone number should be  like 1xxx or +1xxx or simply xxx (without leader 1 or +1). In case with leader '(+)1' symbol phone length to be 11 digits, otherwise 10 digits.  Given incorrect symbol or length is wrong. ");
          }
        */
        if (!preg_match("/^[0-9]{5,10}$/", $fields['PhoneNumber'])
        ) {
            // preg_match("/\D/", $fields['PhoneNumber']
            throw new \UserInputError("Phone number length should be 5-10 symbols long. Only digits are allowed. Given incorrect symbol or length is wrong.");
        }
    }
}
