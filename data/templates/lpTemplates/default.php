<?php

class TAccountCheckerProviderName extends TAccountChecker
{
    private const REWARDS_PAGE_URL = 'siteURL';

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'siteURL';

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
    }

    public function IsLoggedIn()
    {
        $this->http->RetryCount = 0;
        $this->http->GetURL(self::REWARDS_PAGE_URL, [], 20);
        $this->http->RetryCount = 2;

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->sendNotification("refs #XXXX: New valid account");

        $this->http->removeCookies();
        $this->http->GetURL('loginURL');

        if (!$this->http->ParseForm('loginForm')) {
            return $this->checkErrors();
        }

        $this->http->SetInputValue('login', $this->AccountFields['Login']);
        $this->http->SetInputValue('password', $this->AccountFields['Pass']);
        $this->http->SetInputValue('rememberMe', "on");

        return true;
    }

    public function Login()
    {
        if (!$this->http->PostForm()) {
            return $this->checkErrors();
        }

        if ($this->loginSuccessful()) {
            return true;
        }

        if ($this->parseQuestion()) {
            return false;
        }

        if ($message = $this->http->FindSingleNode('//div[contains(@class, "error")]')) {
            $this->logger->error("[Error]: {$message}");

            if (strstr($message, 'Error message')) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            return false;
        }

        return $this->checkErrors();
    }

    public function ProcessStep($step)
    {
        $this->logger->notice(__METHOD__);
        $this->http->SetInputValue('answer', $this->Answers[$this->Question]);

        if (!$this->http->PostForm()) {
            return false;
        }

        $error = $this->http->FindSingleNode('//error');

        if ($error) {
            $this->AskQuestion($this->Question, $error);

            return false;
        }// if ($error)

        return true;
    }

    public function Parse()
    {
        if ($this->http->currentUrl() != self::REWARDS_PAGE_URL) {
            $this->http->GetURL(self::REWARDS_PAGE_URL);
        }
        // Balance -
        $this->SetBalance($this->http->FindSingleNode('//li[contains(@id, "balance")]'));
        // Name
        $this->SetProperty('Name', beautifulName($this->http->FindSingleNode('//li[contains(@id, "name")]')));
        // Number
        $this->SetProperty('Number', $this->http->FindSingleNode('//li[contains(@id, "number")]'));

        // Expiration Date
        $this->logger->info('Expiration Date', ['Header' => 3]);
        $exp = $this->http->FindSingleNode("//p[contains(text(), 'Expiration Date')]", null, true, "/expiring on ([^<]+)/ims");
        $expiringBalance = $this->http->FindSingleNode("//p[contains(., 'CashPoints expiring on')]", null, true, "/([\d\.\,]+) CashPoints? expiring/ims");
        // Expiring Balance
        $this->SetProperty("ExpiringBalance", $expiringBalance);

        if ($expiringBalance > 0 && strtotime($exp)) {
            $this->SetExpirationDate(strtotime($exp));
        }
    }

    public function ParseItineraries()
    {
        $result = [];
        $this->http->GetURL('siteURL');
        $itineraries = $this->http->XPath->query("//its");
        $this->logger->debug("Total {} itineraries were found");

        foreach ($itineraries as $itinerary) {
            $this->http->GetURL($itinerary->nodeValue);
            $it = $this->parseItinerary();
            $this->logger->debug('Parsed itinerary:');
            $this->logger->debug(var_export($it, true), ['pre' => true]);
            $result[] = $it;
        }

        return $result;
    }

    public function GetHistoryColumns()
    {
        return [
            "Date"        => "PostingDate",
            "Description" => "Description",
            "Bonus"       => "Bonus",
            "Points"      => "Miles",
        ];
    }

    public function ParseHistory($startDate = null)
    {
        $result = [];
        $this->logger->debug('[History start date: ' . ((isset($startDate)) ? date('Y/m/d H:i:s', $startDate) : 'all') . ']');
        $startTimer = $this->getTime();

        $this->http->GetURL('');
        $startIndex = sizeof($result);
        $result = array_merge($result, $this->ParsePageHistory($startIndex, $startDate));

        $this->getTime($startTimer);

        return $result;
    }

    public function ParsePageHistory($startIndex, $startDate)
    {
        $result = [];
        $nodes = $this->http->XPath->query("");
        $this->logger->debug("Total {$nodes->length} history items were found");

        for ($i = 0; $i < $nodes->length; $i++) {
            $dateStr = $this->http->FindSingleNode("td[1]", $nodes->item($i));
            $postDate = $this->ModifyDateFormat($dateStr);
            $postDate = strtotime($postDate);

            if (isset($startDate) && $postDate < $startDate) {
                $this->logger->notice("break at date {$dateStr} ($postDate)");

                break;
            }

            $result[$startIndex]['Date'] = $postDate;
            $result[$startIndex]['Description'] = $this->http->FindSingleNode("td[3]", $nodes->item($i));
            $miles = $this->http->FindSingleNode("td[4]", $nodes->item($i));

            $key = 'Points';

            if ($this->http->FindPreg('/Bonus/ims', false, $result[$startIndex]['Description'])) {
                $key = 'Bonus';
            }

            $result[$startIndex][$key] = $miles;

            $startIndex++;
        }

        return $result;
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//a[contains(@href, "logout")]/@href')) {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    private function parseQuestion()
    {
        $this->logger->notice(__METHOD__);
        $question = $this->http->FindSingleNode('//question');

        if (!isset($question) || !$this->http->ParseForm('formQuestion')) {
            return false;
        }

        $this->Question = $question;
        $this->ErrorCode = ACCOUNT_QUESTION;
        $this->Step = 'Question';

        return true;
    }

    private function parseItinerary()
    {
        $result = [];
        $bookNumber = '';
        $this->logger->info("Parse itinerary #{$bookNumber}", ['Header' => 3]);

        return $result;
    }
}
