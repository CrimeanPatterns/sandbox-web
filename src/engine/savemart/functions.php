<?php
use AwardWallet\Engine\ProxyList;

class TAccountCheckerSavemart extends TAccountChecker
{
    use ProxyList;

    private const REWARDS_PAGE_URL = 'https://savemart.com/accounts';
    private string $swiftlyUserId = 'fabdeae8-72db-4356-b38c-ef94bad662e7';
    private string $chainId;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        //$this->http->SetProxy($this->proxyReCaptcha()); // If graphql requests is not response
    }

    public function IsLoggedIn()
    {
        if (!isset($this->State['access_token'])) {
            return false;
        }

        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL(self::REWARDS_PAGE_URL);
        $this->chainId = $this->http->FindPreg("/\"chainId\"\s*:\s*\"([^\"]+)/");

        if ($this->http->Response['code'] !== 200 || !$this->chainId) {
            return $this->checkErrors();
        }

        $data = [
            'email'     => $this->AccountFields['Login'],
            'password'  => $this->AccountFields['Pass'],
        ];
        $headers = [
            'Accept'       => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
        ];
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://savemart.com/api/pingCloudLogin', json_encode($data), $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $response = $this->http->JsonLog();

        if (isset($response->token->access_token)) {
            $this->http->setCookie('authToken', $response->token->access_token, '.savemart.com');
            $this->State['access_token'] = $response->token->access_token;

            return $this->loginSuccessful();
        }

        if ($message = $response->message ?? null) {
            $this->logger->error("[Error]: {$message}");

            if ($message == "Invalid Credentials") {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            $this->DebugInfo = $message;

            return false;
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $userData = $this->http->JsonLog(null, 0);
        $fullName = $userData->message->FirstName . " " . $userData->message->LastName;
        // Name
        $this->SetProperty('Name', beautifulName($fullName));
        // Loyalty ID
        $this->SetProperty('Number', $userData->message->InitialCardID);

        if (empty($userData->message->HomeStore)) {
            return;
        }

        $postData = [
            "query" => 'query storeByNumber($storeNumber: Int!) { storeByNumber(storeNumber: $storeNumber) { storeId }}',
            "variables" => ["storeNumber" => $userData->message->HomeStore],
        ];
        $headers = [
            "Accept"        => "*/*",
            'authorization' => "Bearer {$this->State['access_token']}",
            "Origin"        => "https://savemart.com",
            "Content-Type"  => "application/json",
        ];

        $this->http->RetryCount = 0;
        $this->http->PostURL("https://sm.swiftlyapi.net/graphql", json_encode($postData), $headers);
        $response = $this->http->JsonLog();


        if (!$storeId = $response->data->storeByNumber->storeId ?? null) {
            return;
        };

        $postData = [
            "query" => 'query Rewards($storeId: UUID) { availableLoyaltyRewards(storeId: $storeId) { description displayName images { backgroundColor imageDensity purpose url } pointCost rewardId termsAndConditions } loyaltySummary { availablePoints summaryPoints { expiresOn points } issuedRewards { reward { description displayName images { backgroundColor imageDensity purpose url } pointCost rewardId termsAndConditions } expiryDateTime } } loyaltyCard { loyaltyId }} ',
            "variables" => ["storeId" => $storeId],
        ];

        $this->http->RetryCount = 0;
        $this->http->PostURL("https://sm.swiftlyapi.net/graphql", json_encode($postData), $headers);
        $rewards = $this->http->JsonLog();

        $this->http->GetURL("https://rebates.swiftlyapi.net/rebates/wallet/{$this->chainId}/{$this->swiftlyUserId}", $headers);
        $this->http->RetryCount = 2;
        $wallet = $this->http->JsonLog();

        // Available Cashback
        $this->AddSubAccount([
            'Code'           => 'savemart' . $userData->message->InitialCardID,
            'DisplayName'    => 'Available Cashback',
            'Balance'        => $wallet->cashbackDisplay,
        ]);
        // Available points
        $this->SetBalance($rewards->data->loyaltySummary->availablePoints);

        // Expires Soon Balance
        $this->SetProperty("ExpiringBalance", $wallet->expiringSoonDisplay);

        if (!empty($wallet->expiringSoon)) {
            $this->sendNotification("refs #17975 - need to check exp date");
        }
        if (!empty($rewards->data->loyaltySummary->issuedRewards)) {
            $this->sendNotification("refs #23078 -  Reward detected");
        }
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $headers = [
            'Authorization' => $this->State['access_token'],
        ];
        $this->http->GetURL("https://savemart.com/api/getShimmedProfile?swiftlyUserId={$this->swiftlyUserId}&cardId=44209737721&tsmcToken=null", $headers);
        $response = $this->http->JsonLog();
        $email = $response->data->email ?? null;
        $this->logger->debug("[Email]: {$email}");

        if ($email && strtolower($email) == strtolower($this->AccountFields['Login'])) {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        return true;
    }

}
