<?php

use AwardWallet\Common\Parsing\Html;

class TAccountCheckerKenyaair extends TAccountChecker
{
    use AwardWallet\Engine\ProxyList;

    private const REWARDS_PAGE_URL = 'https://asante.kenya-airways.com/';

    public function GetRedirectParams($targetURL = null)
    {
        $arg = parent::GetRedirectParams($targetURL);
        $arg['CookieURL'] = 'https://asante.kenya-airways.com/';

        return $arg;
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->http->setHttp2(true);
        $this->http->SetProxy($this->proxyReCaptcha());
        $this->KeepState = true;
    }

    public function IsLoggedIn()
    {
        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL(self::REWARDS_PAGE_URL);

        if (
             !$this->http->FindSingleNode('//div[@class="cmp-rectangle-bounce"]')
             || $this->http->Response['code'] != 200
         ) {
            return $this->checkErrors();
        }

        return true;
    }

    public function Login()
    {
        $postData = [
            "channel"      => "W",
            'username'     => $this->AccountFields['Login'],
            'password'     => $this->AccountFields['Pass'],
            "program_code" => "ASANTE_PROGRAM",
        ];

        $this->http->PostURL("https://asante.kenya-airways.com/b2c/login", $postData, [
            'Accept'             => 'application/json, text/plain, */*',
            'Content-Type'       => 'application/x-www-form-urlencoded',
            'X-Clm-Program-Code' => 'ASANTE_PROGRAM',
        ]);

        $data = $this->http->JsonLog(null, 2, true);
        $access_token = $data['access_token'] ?? null;

        if ($this->http->Response['code'] != 200 || !$access_token) {
            $message = $data['message'] ?? null;

            if ($message == 'Wrong login or password') {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            return false;
        }

        $this->http->setCookie('Bearer', $access_token, 'asante.kenya-airways.com');
        $this->http->setCookie('cmptoken', json_encode(['access_token' => $access_token, 'expires_in' => $data['expires_in'], 'scope' => $data['scope'], 'jti' => $data['jti']]), 'asante.kenya-airways.com');

        if ($this->loginSuccessful()) {
            return true;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $data = $this->getData('https://asante.kenya-airways.com/b2c/me', 3, true);
        // Balance -
        $this->SetBalance($data['mainPointsBalance'] ?? null);
        // Name
        $this->SetProperty('Name', beautifulName(Html::cleanXMLValue($data['firstName'] ?? null . ' ' . $data['lastName'] ?? null)));
        // Number
        $this->SetProperty('Number', $data['mainIdentifier'] ?? null);

        $data = $this->getData('https://asante.kenya-airways.com/b2c/me/recognition-schemas/KQ_Asante/tiers', 3, true);

        // Tier
        $this->SetProperty('Tier', $data[0]['tier']['name'] ?? null);

        $data = $this->getData('https://asante.kenya-airways.com/b2c/me/progress-trackers', 3, true);

        if ($data[0] ?? null) {
            $flightCounterMax = $data[0]['maxValue'] ?? null;
            $flightCounterCur = $data[0]['currentValue'] ?? null;

            if (is_numeric($flightCounterMax) && is_numeric($flightCounterCur)) {
                // Flight Counter
                $this->SetProperty('FlightCounter', (float) $flightCounterMax - (float) $flightCounterCur);
            }
        }

        if ($data[1] ?? null) {
            $tierCounterMax = $data[1]['maxValue'] ?? null;
            $tierCounterCur = $data[1]['currentValue'] ?? null;

            if (is_numeric($tierCounterMax) && is_numeric($tierCounterCur)) {
                // Tier Counter
                $this->SetProperty('TierCounter', (float) $tierCounterMax - (float) $tierCounterCur);
            }
        }
    }

    public function GetHistoryColumns()
    {
        return [
            // general info
            "Account id"     => "Info.Int",
            "Channel"        => "Info",
            "Channel name"   => "Info",
            "Currency code"  => "Info",
            "Customer id"    => "Info.Int",
            "Date"           => "PostingDate",
            "Partner"        => "Info",
            "Partner name"   => "Info",
            "Points"         => "Info.Int",
            "Status"         => "Info",
            "Status name"    => "Info",
            "Total value"    => "Info.Int",
            "Transaction id" => "Info.Int",
            "TrnNo"          => "Info",
            "Type"           => "Info",
            "Type name"      => "Info",

            // balances info
            "Billing partner"      => "Info",
            "Billing partner name" => "Info",
            "Expiration date"      => "Info.Date",
            "Main balance"         => "Info",
            "Point type"           => "Info",
            "Point type name"      => "Info",
            "Points remaining"     => "Info.Int",
            "Points to expire"     => "Info.Int",
            "Spent points"         => "Info.Int",
        ];
    }

    public function GetHiddenHistoryColumns()
    {
        return [
            // general info
            "Account id",
            "Channel",
            "Customer id",
            "Partner",
            "Status",
            "Transaction id",
            "TrnNo",
            "Type",

            // balances info
            "Billing partner",
            "Main balance",
            "Point type",
        ];
    }

    public function ParseHistory($startDate = null)
    {
        $this->logger->debug('[History start date: ' . ((isset($startDate)) ? date('Y/m/d H:i:s', $startDate) : 'all') . ']');
        $history = $this->getData('https://asante.kenya-airways.com/b2c/me/account/customers/9253/transactions?orderField=date%3Adesc&maxResults=10&firstResult=0' . ((isset($startDate)) ? '&dateFrom=' . date('Y/m/d H:i:s', $startDate) : '') . '&withQCnt=false', 3, true);
        $result = [];

        foreach ($history as $histItem) {
            $item = [
                "Account id"     => $histItem['accountId'] ?? null,
                "Channel"        => $histItem['channel'] ?? null,
                'Channel name'   => $histItem['channelName'] ?? null,
                'Currency code'  => $histItem['currencyCode'] ?? null,
                "Customer id"    => $histItem['customerId'] ?? null,
                "Date"           => strtotime($histItem['date'] ?? null),
                "Partner"        => $histItem['partner'] ?? null,
                'Partner name'   => $histItem['partnerName'] ?? null,
                'Points'         => $histItem['points'] ?? null,
                "Status"         => $histItem['status'] ?? null,
                'Status name'    => $histItem['statusName'] ?? null,
                "Total value"    => $histItem['totalValue'] ?? null,
                "Transaction id" => $histItem['transactionId'] ?? null,
                "TrnNo"          => $histItem['trnNo'] ?? null,
                "Type"           => $histItem['type'] ?? null,
                'Type name'      => $histItem['typeName'] ?? null,
            ];

            $pointBalances = $histItem['pointsBalances'][0] ?? null;

            if ($pointBalances) {
                $item["Billing partner"] = $pointBalances['billingPartner'] ?? null;
                $item["Billing partner name"] = $pointBalances['billingPartnerName'] ?? null;
                $item["Expiration date"] = $pointBalances['expirationDate'] ? strtotime($pointBalances['expirationDate']) : null;
                $item["Main balance"] = $pointBalances['mainBalance'] ?? null;
                $item["Point type"] = $pointBalances['pointType'] ?? null;
                $item["Point type name"] = $pointBalances['pointTypeName'] ?? null;
                $item["Points remaining"] = $pointBalances['pointsRemaining'] ?? null;
                $item["Points to expire"] = $pointBalances['pointsToExpire'] ?? null;
                $item["Spent points"] = $pointBalances['spentPoints'] ?? null;
            }
            array_push($result, $item);
        }

        return $result;
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $this->http->RetryCount = 0;
        $data = $this->getData('https://asante.kenya-airways.com/b2c/me', 3, true);
        $email = $data['address']['email'] ?? null;

        if ($email != null && strtolower($email) === strtolower($this->AccountFields['Login'])) {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return false;
    }

    private function getData(string $url, $logs = 0, $convertToArray = false)
    {
        $this->logger->notice(__METHOD__);
        $this->http->GetURL($url, [
            'Accept'        => 'application/json, text/plain, */*',
            'Authorization' => 'Bearer ' . $this->http->getCookieByName('Bearer', 'asante.kenya-airways.com'),
        ]);

        return $this->http->JsonLog(null, $logs, $convertToArray);
    }

    private function postData(string $url, $params = [], $logs = 0, $convertToArray = false)
    {
        $this->logger->notice(__METHOD__);
        $this->http->PostURL($url, $params, [
            'Accept'        => 'application/json, text/plain, */*',
            'Authorization' => 'Bearer ' . $this->http->getCookieByName('Bearer', 'asante.kenya-airways.com'),
            'Content-Type'  => 'application/json',
        ]);

        return $this->http->JsonLog(null, $logs, $convertToArray);
    }
}
