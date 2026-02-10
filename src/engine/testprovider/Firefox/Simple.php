<?php

namespace AwardWallet\Engine\testprovider\Firefox;

use AwardWallet\Engine\testprovider\TestHelper;

class Simple extends \TAccountChecker
{
    use \SeleniumCheckerHelper;
    use TestHelper;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->InitSeleniumBrowser();
        //		$this->usePacFile();
        $this->ArchiveLogs = true;
    }

    public function LoadLoginForm()
    {
        $this->http->GetURL($this->getAwUrl());

        return $this->http->FindPreg("#keeps track of your#ims");
    }

    public function Login()
    {
        $this->waitForElement(\WebDriverBy::xpath("//a[@ui-sref = 'login']"), 5, true)->click();

        $input = $this->waitForElement(\WebDriverBy::xpath("//input[@data-ng-model = 'user.login']"), 5, true);
        $input->sendKeys('invaliduser');

        $input = $this->waitForElement(\WebDriverBy::xpath("//input[@data-ng-model = 'user.password']"), 5, true);
        $input->sendKeys('invalidpass' . rand(900, 10000));

        $this->driver->findElement(\WebDriverBy::id('login-button'))->click();

        $error = $this->waitForElement(\WebDriverBy::xpath("//span[contains(text(), 'Invalid user name or password')]"), 10, true);

        if ($error) {
            throw new \CheckException($error->getText());
        } else {
            throw new \CheckException("Unknown error");
        }
    }
}
