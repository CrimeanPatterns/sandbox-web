<?php

namespace AwardWallet\Engine\testprovider\Checker;

/**
 * in this test
 * we will set cookie for two different domains: awardwallet.dev and m.awardwallet.dev
 * and make sure this cookies will be kept in state.
 */
class SeleniumState extends \TAccountChecker
{
    use \SeleniumCheckerHelper;
    use TestHelper;

    private $loginUsed;
    private $testPage;
    private $testPage2;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->InitSeleniumBrowser();
        $this->ArchiveLogs = true;
        $this->testPage = $this->getAwUrl() . '/admin/test/cookies.php';
        $this->testPage2 = str_replace("://", "://m.", $this->testPage);

        if (empty($this->Answers['Cookie1']) || empty($this->Answers['Cookie2'])) {
            throw new \CheckException("Please specify Cookie1 and Cookie2 in Answers", ACCOUNT_PROVIDER_ERROR);
        }
    }

    public function IsLoggedIn()
    {
        return
            $this->http->GetURL($this->testPage) && !empty($this->http->FindPreg("/Cookie1/ims"))
            && $this->http->GetURL($this->testPage2) && !empty($this->http->FindPreg("/Cookie2/ims"));
    }

    public function LoadLoginForm()
    {
        return true;
    }

    public function Login()
    {
        $this->loginUsed = true;

        $this->http->GetURL($this->testPage . '?Cookie1=' . $this->Answers['Cookie1']);

        if (empty($this->http->FindPreg("/" . $this->Answers['Cookie1'] . "/ims"))) {
            throw new \CheckException("Failed to set cookie 1", ACCOUNT_INVALID_PASSWORD);
        }

        $this->http->GetURL($this->testPage2 . '?Cookie2=' . $this->Answers['Cookie2']);

        if (empty($this->http->FindPreg("/" . $this->Answers['Cookie2'] . "/ims"))) {
            throw new \CheckException("Failed to set cookie 2", ACCOUNT_INVALID_PASSWORD);
        }

        return true;
    }

    public function Parse()
    {
        if ($this->IsLoggedIn()) {
            if ($this->loginUsed) {
                $this->SetBalance(1);
            } else {
                $this->SetBalance(2);
            }
        } else {
            $this->SetBalance(3);
        }
    }
}
