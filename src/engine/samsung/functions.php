<?php

class TAccountCheckerSamsung extends TAccountChecker
{
    use SeleniumCheckerHelper;

    public const WAIT_TIMEOUT = 7;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->UseSelenium();
        $this->useChromium();
        $this->disableImages();
        $this->useCache();
        $this->http->saveScreenshots = true;
    }

    public function IsLoggedIn()
    {
        $this->http->GetURL('https://www.samsungrewards.com/rewards/#/main');

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.samsungrewards.com/rewards/#/main');
        $loginFormlink = $this->waitForElement(WebDriverBy::xpath('//a[contains(text(), "Log In/Sign up")]'), self::WAIT_TIMEOUT);
        $this->saveResponse();

        if (!$loginFormlink) {
            $this->logger->error("something went wrong");

            return false;
        }
        $loginFormlink->click();

        // visible-invisible
        $this->waitForElement(WebDriverBy::id('iptLgnPlnID'), self::WAIT_TIMEOUT, false);
        $this->driver->executeScript("document.getElementById(\"iptLgnPlnID\").parentElement.classList = ['focus'];");

        $login = $this->waitForElement(WebDriverBy::id('iptLgnPlnID'), self::WAIT_TIMEOUT);
        $pass = $this->waitForElement(WebDriverBy::id('iptLgnPlnPD'), 0);

        if (!$login || !$pass) {
            $this->logger->error("something went wrong");

            return false;
        }

        $rememberMe = $this->waitForElement(WebDriverBy::id('remIdChkYN'), 0);
        $this->saveResponse();

        if ($rememberMe) {
            $rememberMe->click();
        }

        $login->sendKeys($this->AccountFields['Login']);
        $pass->sendKeys($this->AccountFields['Pass']);
        $pass->sendKeys(WebDriverKeys::ENTER);

        return true;
    }

    public function Login()
    {
        $this->waitForElement(WebDriverBy::xpath('
                //div[@class="user-rewards-points"]//h2[normalize-space(text()) != ""]
                | //div[@class="error-msg"]/div[@class=""]
                | //a[@*="btnNotNow();"]
                | //p[contains(text(), "The Terms and conditions have been updated. Read and accept the new terms and conditions to continue using Samsung services.")]
                | //h2[contains(text(), "Two-step verification")]
        '), self::WAIT_TIMEOUT);
        $this->saveResponse();

        if ($message = $this->http->FindSingleNode('//div[@class="error-msg"]/div[@class=""]')) {
            $this->logger->error($message);

            if (strstr($message, 'Incorrect ID or password')) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            return false;
        }

        // Updated Terms and Conditions
        if ($link = $this->waitForElement(WebDriverBy::xpath('//p[contains(text(), "The Terms and conditions have been updated. Read and accept the new terms and conditions to continue using Samsung services.")]'), 0)) {
            $this->throwAcceptTermsMessageException();
        }

        // Click to "Not now" link
        if ($link = $this->waitForElement(WebDriverBy::xpath('//a[@*="btnNotNow();"]'), 0)) {
            $link->click();
            $this->waitForElement(WebDriverBy::xpath('//div[@class="user-rewards-points"]//h2[normalize-space(text()) != ""]'), self::WAIT_TIMEOUT);
        }
        // Two-step verification
        if ($link = $this->waitForElement(WebDriverBy::xpath('//h2[contains(text(), "Two-step verification")]'), 0)) {
            return $this->processTwoStepVerification();
        }

        if ($this->loginSuccessful()) {
            return true;
        }

        // very strange account^ may be provider bug, AccountID: 4965884
        if ($this->AccountFields['Login'] == 'gouin.yannick@gmail.com'
            && $this->http->FindSingleNode('//body[@id = "error"]/h1/img[@src = "/rewards/public/COMPILED/images/SS_Reward_Horz_Logo_BLK.a1a994e9c159e0cb0d49fc831f05031e.png"]/@src')) {
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        return false;
    }

    public function ProcessStep($step)
    {
        $this->logger->notice(__METHOD__);

        if ($this->isNewSession()) {
            return $this->LoadLoginForm() && $this->Login();
        }

        if ($step == "Question") {
            return $this->processTwoStepVerification();
        }

        return false;
    }

    public function Parse()
    {
        // Balance - Samsung Rewards Points
        $this->SetBalance($this->http->FindSingleNode('//div[@class="user-rewards-points"]//h2[normalize-space(text()) != ""]') ?? null);
        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//h2[@class="greeting"]', null, false, '%Welcome,\s*(.*?)\.%ims')));
        // Member level
        $this->SetProperty('Level', $this->http->FindSingleNode('//div[@class="user-tier-status"][1]//h2') ?? null);

        if ($this->ErrorCode == ACCOUNT_ENGINE_ERROR) {
            if ($this->http->FindSingleNode('//h2[contains(text(), "Sign up for rewards")]')) {
                throw new CheckException(self::NOT_MEMBER_MSG, ACCOUNT_PROVIDER_ERROR);
            }
        }
    }

    protected function processTwoStepVerification()
    {
        $this->logger->notice(__METHOD__);
        $this->logger->info('Security Question', ['Header' => 3]);
        $questionObject = $this->waitForElement(WebDriverBy::xpath("//p[contains(text(), 'Please enter the verification code to complete the sign-in process.')]"), 0);
        $phone = $this->http->FindPreg('/To verify it\'s you, we\'ll send a verification code to ([^\.]+)\./');
        $this->saveResponse();

        if (!isset($questionObject) && !$phone) {
            $this->logger->error("something went wrong");

            return false;
        }

        if (!isset($questionObject) && $phone) {
            $this->logger->debug("Send Verification Code to phone: {$phone}");
            $question = "Please enter Verification Code which was sent to the following phone number: $phone. Please note that you must provide the latest code that was just sent to you. Previous codes will not work.";
        } else {
            $question = trim($questionObject->getText());
        }

        $this->logger->debug("Question -> {$question}");

        if (empty($this->Answers[$question]) || !is_numeric($this->Answers[$question])) {
            $this->holdSession();
            $this->AskQuestion($question);

            return false;
        }
        $this->logger->debug("Entering answer on question -> {$question}...");
        $answerInput = $this->driver->findElement(WebDriverBy::id("smsNumber"), 0);
        $btn = $this->waitForElement(WebDriverBy::xpath("//button[contains(text(),'Verify')]"), 0);

        if (!empty($question) && $answerInput && $btn) {
            // Don't ask me again on this device
            $this->driver->executeScript('if (checkBox = document.getElementById("isRegisterTrustedDeviceCB")) { checkBox.click(); checkBox.checked = true; }');
            $this->saveResponse();
            $this->driver->executeScript('$(\'#isRegisterTrustedDeviceCB\').removeAttr(\'disabled\');');
            $this->saveResponse();
            $answer = $this->Answers[$question];
            $answerInput->sendKeys($answer);
            $this->saveResponse();
            unset($this->Answers[$question]);
            $this->logger->debug("click 'Submit'...");
            $btn = $this->waitForElement(WebDriverBy::xpath("//button[contains(text(),'Verify')]"), 0);

            if (!$btn) {
                return false;
            }
            $btn->click();
            $this->logger->debug("find errors...");

            $error = $this->waitForElement(WebDriverBy::xpath("//div[contains(text(), 'Sorry. That code is incorrect.') or contains(text(), 'Incorrect code.')]"), 5);
            $this->saveResponse();

            if ($error) {
                $this->holdSession();
                $this->AskQuestion($question, $error->getText(), "Question");
                $this->logger->error("answer was wrong");

                return false;
            }

            $this->logger->debug("done");
            $this->saveResponse();

            return true;
        }

        return false;
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $logout = $this->waitForElement(WebDriverBy::xpath('//a[contains(text(), "Log Out")]'), 5);
        $this->saveResponse();

        if ($logout) {
            return true;
        }

        return false;
    }
}
