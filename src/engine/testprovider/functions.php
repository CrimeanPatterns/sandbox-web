<?php

/**
 * This provider is only for testing v3.
 */
class TAccountCheckerTestprovider extends TAccountChecker
{
    public const QUESTION = "What is your mother's middle name (answer is Petrovna)?";

    public $fakeRewardsTransfers;

    protected $fb;
    protected $fb_login = 'bvtest@rambler.ru';
    protected $fb_password = '123123123qa';

    public static function GetCheckStrategy($fields)
    {
        if (stripos($fields['Login'], 'phantom.') === 0) {
            return CommonCheckAccountFactory::STRATEGY_CHECK_PHANTOM;
        }
    }

    public static function GetAccountChecker($accountInfo)
    {
        if ($accountInfo['Login'] == 'selenium.question') {
            return new \AwardWallet\Engine\testprovider\TAccountCheckerTestproviderSelenium();
        }

        if (stristr($accountInfo['Login'], 'selenium.versions')) {
            return new \AwardWallet\Engine\testprovider\TSeleniumVersions();
        }

        if (preg_match('#^[a-z\d\-]+(\.[a-z\d\-]+)*$#ims', $accountInfo['Login'])) {
            $parts = explode(".", $accountInfo['Login']);
            $file = __DIR__ . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . '.php';

            if (file_exists($file)) {
                $class = 'AwardWallet\\Engine\\testprovider\\' . implode('\\', $parts);

                return new $class();
            }
        }

        return new TAccountCheckerTestprovider();
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->ArchiveLogs = true;
    }

    public static function FormatBalance($fields, $properties)
    {
        //# currency: $
        if (isset($properties['SubAccountCode'], $properties['SubAccBalance']) && strstr($properties['SubAccountCode'], 'chase')) {
            return $properties['SubAccBalance'];
        }

        if (isset($properties['Currency']) && $properties['Currency'] == '$') {
            return formatFullBalance($fields['Balance'], $fields['ProviderCode'], "$%0.2f");
        } elseif (isset($properties['Currency']) && $properties['Currency'] == 'RUB') {
            return formatFullBalance($fields['Balance'], $fields['ProviderCode'], "%0.2f rub.");
        } else {
            return parent::FormatBalance($fields, $properties);
        }
    }

    public function TuneFormFields(&$arFields, $values = null)
    {
        parent::TuneFormFields($arFields);
        $arFields['Login']['Options'] = $this->getLoginOptions();

        if (false) {
            $arFields["Pass"] = [
                "Caption"         => "Password",
                "Note"            => 'CHANGE AT YOUR OWN RISK!!!',
                "Type"            => "string",
                "InputType"       => "password",
                "InputAttributes" => " autocomplete=\"off\" style='width: 280px;'",
                "MinSize"         => 1,
                "Size"            => 80,
                "Cols"            => 82,
                "Database"        => false,
                "HTML"            => true,
                "Required"        => false,
            ];
            global $SAVE_PASSWORD;

            $arFields["SavePassword"] = [
                "Caption"         => "Save password",
                "Note"            => 'CHANGE AT YOUR OWN RISK!!!',
                "Type"            => "integer",
                "InputType"       => "select",
                "Options"         => $SAVE_PASSWORD,
                "Required"        => false,
                "InputAttributes" => " style='width: 288px;'",
                "Value"           => '1',
            ];
        }

        foreach ($arFields['Login']['Options'] as $key => &$value) {
            if (strcasecmp($key, $value) != 0) {
                $value = $key . ': ' . $value;
            }
        }
        ksort($arFields['Login']['Options']);
    }

    public function LoadLoginForm()
    {
        global $sPath;

        switch ($this->AccountFields['Login']) {
            case "facebook.groupon":
                $this->http->removeCookies();
                $this->fb = new FacebookConnect();

                try {
                    $obj = $this;
                    $this->fb->setAppId('7829106395')
                        ->setRedirectURI('https://www.groupon.com/login')
                        ->setChecker($this)
                        ->setCredentials($this->fb_login, $this->fb_password)
                        ->setBaseDomain('.groupon.com')
                        ->AllowAccess()
                        ->setCallbackFunction(function ($session, $fc, $checker) {
                            $data = json_encode([
                                'access_token' 	 => $session['access_token'],
                                'signed_request'	=> $session['signed_request'],
                            ]);
                            $checker->http->setDefaultHeader('Content-Type', 'application/json; charset=utf-8');
                            $checker->http->setDefaultHeader('X-Requested-With', 'XMLHttpRequest');
                            $checker->http->PostURL('https://www.groupon.com/facebook_session', $data);
                            $checker->http->GetURL('https://www.groupon.com/login');
                        })
                        ->PrepareLoginForm();
                } catch (FacebookException $e) {
                    return false;
                }

                break;

            case "facebook.groupon.uk":
                $this->http->removeCookies();
                $this->fb = new FacebookConnect();

                try {
                    $obj = $this;
                    $this->fb->setAppId('128218413912317')
                        ->setRedirectURI('https://www.groupon.co.uk/login')
                        ->setChecker($this)
                        ->setCredentials($this->fb_login, $this->fb_password)
                        ->AllowAccess()
                        ->setBaseDomain('.groupon.co.uk')
                        ->setCallbackFunction(function ($session, $fc, $checker) {
                            if (isset($fc->userInfo)) {
                                $url = "https://www.groupon.co.uk/Registration.action?facebookLogin=&" .
                                "registerView.facebookId={$fc->userInfo->id}&" .
                                "registerView.email={$fc->userInfo->email}&" .
                                "registerView.userAddress.firstName={$fc->userInfo->first_name}&" .
                                "registerView.userAddress.lastName={$fc->userInfo->last_name}&" .
                                "incentiveRewardToken=&initialEmailForIncentive=&dotdId=&" .
                                "returnJson=true&" .
                                "facebookSecurityToken={$session['access_token']}";
                                $checker->http->GetURL($url);
                            }
                            $checker->http->GetURL('https://www.groupon.co.uk/');
                        })
                        ->PrepareLoginForm();
                } catch (FacebookException $e) {
                    return false;
                }

                break;
        }

        return true;
    }

    public function Login()
    {
        switch ($this->AccountFields['Login']) {
            case "question":
                $this->AskQuestion(self::QUESTION);

                return false;

                break;

            case "facebook.groupon":
                try {
                    if ($this->fb->Login()->isLogIn("//a[contains(@href,'/logout')]")) {
                        return true;
                    }
                } catch (FacebookException $e) {
                    switch ($e->getCode()) {
                        case FacebookConnect::CODE_HTTP_ERROR:
                            return false;

                            break;

                        case FacebookConnect::CODE_INVALID_PASSWORD:
                            throw new CheckException($e->GetMessage(), ACCOUNT_INVALID_PASSWORD);

                            break;

                        case FacebookConnect::CODE_USER_INTERVENTION_REQUIRED:
                            $permissions = $this->fb->getPermissions();

                            if (!sizeof($permissions)) {
                                $message = "Action Required. Please login to Groupon and respond to a message that you will see after your login.";
                            } else {
                                $message = 'Need to allow access to the following information: ' . implode(', ', $permissions);
                            }

                            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);

                            break;

                        case FacebookConnect::CODE_NOT_FOUND_SESSION:
                            throw new CheckException('Authorization Failed', ACCOUNT_PROVIDER_ERROR);

                            break;

                        case FacebookConnect::CODE_LOCK_ACCOUNT:
                            throw new CheckException($e->GetMessage(), ACCOUNT_PROVIDER_ERROR);

                            break;
                    }
                }

                break;

            case "facebook.groupon.uk":
                try {
                    if ($this->fb->Login()->isLogIn("//a[@id='logoutLink']/@href")) {
                        return true;
                    }
                } catch (FacebookException $e) {
                    switch ($e->getCode()) {
                        case FacebookConnect::CODE_HTTP_ERROR:
                            return false;

                            break;

                        case FacebookConnect::CODE_INVALID_PASSWORD:
                            throw new CheckException($e->GetMessage(), ACCOUNT_INVALID_PASSWORD);

                            break;

                        case FacebookConnect::CODE_USER_INTERVENTION_REQUIRED:
                            $permissions = $this->fb->getPermissions();

                            if (!sizeof($permissions)) {
                                $message = "Action Required. Please login to Groupon and respond to a message that you will see after your login.";
                            } else {
                                $message = 'Need to allow access to the following information: ' . implode(', ', $permissions);
                            }

                            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);

                            break;

                        case FacebookConnect::CODE_NOT_FOUND_SESSION:
                            throw new CheckException('Authorization Failed', ACCOUNT_PROVIDER_ERROR);

                            break;

                        case FacebookConnect::CODE_LOCK_ACCOUNT:
                            throw new CheckException($e->GetMessage(), ACCOUNT_PROVIDER_ERROR);

                            break;
                    }
                }

                break;

            case "invalid.logon":
                throw new CheckException("Invalid logon", ACCOUNT_INVALID_PASSWORD);

            case "lockout":
                throw new CheckException("Invalid logon", ACCOUNT_LOCKOUT);

            case "provider.error":
                throw new CheckException("Provider error", ACCOUNT_PROVIDER_ERROR);

            case "testprovidergroup":
                throw new CheckException("Invalid logon", ACCOUNT_INVALID_PASSWORD);
        }

        return true;
    }

    public function ProcessStep($step)
    {
        if ($this->Question != self::QUESTION) {
            throw new CheckException("Unknown question", ACCOUNT_PROVIDER_ERROR);
        }

        if ($this->Answers[$this->Question] != 'Petrovna') {
            $this->AskQuestion(self::QUESTION, 'Wrong answer. Shoud be "Petrovna"');

            return false;
        }

        return true;
    }

    public function Parse()
    {
        global $Config;

        switch ($this->AccountFields['Login']) {
            case "antigate":
                $this->http->GetURL("https://anti-captcha.com/res.php?key=" . ANTIGATE_KEY . "&action=getbalance");
                $balance = round($this->http->Response['body'], 2);
                $this->SetBalance($balance);
                $this->SetProperty("Currency", '$');

                if ($balance < 50) {
                    $this->sendNotification("WARNING! Antigate - refill your balance ($" . $balance . ")");
                }

                return false;

                break;

            case "rucapthca"://todo support old accounts
            case "rucaptcha":
                $this->http->GetURL("https://rucaptcha.com/res.php?key=" . RUCAPTCHA_KEY . "&action=getbalance");
                $balance = round($this->http->Response['body'], 2);
                $this->SetBalance($balance);
                $this->SetProperty("Currency", 'RUB');

                if ($balance < 1000) {
                    $this->sendNotification("WARNING! ruCaptcha - refill your balance (RUB " . $balance . ")");
                }

                return false;

                break;

            case "deathbycaptcha":
                $recognizer = $this->getDeathbycaptchaRecognizer();
                $balance = round($recognizer->getBalance()) / 100;
                $this->http->Log("Balance" . $balance);
                $this->SetBalance($balance);
                $this->SetProperty("Currency", '$');

                if ($balance < 5) {
                    $this->sendNotification("WARNING! deathbycaptcha.com - refill your balance ($" . $balance . ")");
                }

                return false;

                break;

            case "question.multi":
                unset($this->Answers['Question 1']);
                unset($this->Answers['Question 2']);
                $this->SetBalance(1);

                return false;

                break;

            case "expiration.never":
                $this->SetBalance(1500);
                $this->SetExpirationDateNever();

                break;

            case "expiration.on":
                $this->SetBalance(1800);
                $this->SetExpirationDate(strtotime("+1 year +6 month"));

                break;

            case "expiration.fromsub":
                $this->SetBalanceNA();
                $this->SetProperty("SubAccounts", [
                    [
                        'Code'           => 'first',
                        'DisplayName'    => 'First subaccount',
                        'Balance'        => 1000,
                        'ExpirationDate' => strtotime("2024-01-01"),
                    ],
                ]);

                break;

            case "expiration.balance_negative_with_sub1":
                $this->SetBalance(-500);
                $this->SetProperty("SubAccounts", [
                    [
                        'Code'           => 'first',
                        'DisplayName'    => 'First subaccount',
                        'Balance'        => 1000,
                        'ExpirationDate' => strtotime("2024-01-01"),
                    ],
                ]);

                break;

            case "expiration.past":
                $this->SetBalance(2500);
                $this->SetExpirationDate(strtotime("01.01.2012"));

                break;

            case "expiration.close":
                $this->SetBalance(3000);

                if (!empty($this->AccountFields['Pass']) && strtotime($this->AccountFields['Pass']) !== false) {
                    $this->SetExpirationDate(strtotime($this->AccountFields['Pass']));
                } else {
                    $this->SetExpirationDate(strtotime("+7 day"));
                }
                $this->SetProperty("AccountExpirationWarning", "Expires soon, test AccountExpirationWarning field![NoNote]");

                break;

            case 'expiration.doNotExpireEliteStatus':
                $this->SetBalance(123);
                $this->SetExpirationDate(strtotime('+1 month'));
                $this->SetProperty('AccountExpirationWarning', 'do not expire with elite status');

                break;

            case "expiration.empty":
                $this->SetBalance(3000);

                break;

            case "expiration.clear":
                if (rand(0, 1)) {
                    $this->SetBalance(1000);
                    $this->ClearExpirationDate();
                } else {
                    $this->SetBalance(2500);
                    $this->SetExpirationDate(strtotime("+6 month"));
                }

                break;

            case "delay":
                if ($this->AccountFields['Pass'] != '') {
                    $delay = intval($this->AccountFields['Pass']);
                } else {
                    $delay = 30;
                }
                sleep($delay);
                $this->SetBalance(30);

                break;

            case "exception.browser":
                // set to production mode to prevent debug backtrace
                throw new BrowserException("Test browser exception");

                break;

            case "cancel.check":
                if ($this->isBackgroundCheck()) {
                    $this->Cancel();
                } else {
                    $this->SetBalance(222);
                }

                break;

            case "balance.random":
                $this->SetBalance(rand(1, 10000));

                break;

            case "balance.random-status":
                $this->SetBalance(rand(1, 10000));
                $this->SetProperty('EliteStatus', "Random Status" . rand(1, 10000));

                break;

            case "balance.increase":
                if (isset($this->AccountFields['Balance'])) {
                    $this->SetBalance($this->AccountFields['Balance'] + ($this->AccountFields['Login2'] ?? rand(1, 100)));
                } else {
                    $this->SetBalance(rand(1, 100));
                }

                break;

            case "balance.decrease":
                if (isset($this->AccountFields['Balance'])) {
                    $this->SetBalance($this->AccountFields['Balance'] - ($this->AccountFields['Login2'] ?? rand(1, 100)));
                } else {
                    $this->SetBalance(1000000 + rand(1, 100));
                }

                break;

            case "error":
                $arAccountErrorCode = [
                    ACCOUNT_UNCHECKED         => "Unchecked",
                    ACCOUNT_INVALID_PASSWORD  => "Invalid password",
                    ACCOUNT_LOCKOUT           => "Lockout",
                    ACCOUNT_PROVIDER_ERROR    => "Provider error",
                    ACCOUNT_PROVIDER_DISABLED => "Provider disabled",
                    ACCOUNT_ENGINE_ERROR      => "Engine error",
                    ACCOUNT_MISSING_PASSWORD  => "Missing password",
                    ACCOUNT_PREVENT_LOCKOUT   => "Prevent lockout",
                    ACCOUNT_WARNING           => "Warning",
                    ACCOUNT_TIMEOUT           => "Timeout",
                ];
                $errorCode = array_rand($arAccountErrorCode);

                throw new CheckException($arAccountErrorCode[$errorCode], $errorCode);
//                $this->SetBalance(rand(1, 10000));
                break;

            case 'warning.with.message':
                $this->ErrorCode = ACCOUNT_WARNING;
                $this->ErrorMessage = "You don't seem to be a member of Captain's Club reward program, please login to celebritycruises.com and join their program. Then try updating your account.";

                break;

            case 'warning.without.message':
                $this->ErrorCode = ACCOUNT_WARNING;

                break;

            case "lastActivity.random":
                $this->SetBalance(0);
                $this->SetProperty("Name", "Test");
                $lastActivity = ["01/01/2009", "01/01/2010", "01/01/2011", "01/01/2012", "01/01/2013"];
                $this->SetProperty("LastActivity", $lastActivity[array_rand($lastActivity)]);

                break;

            case "balance.point":
                $this->SetBalance('3,222.11');

                break;

            case "balance.comma":
                $this->SetBalance('3,222,11');

                break;

            case "1.subaccount":
                $this->SetBalance(1);
                $this->SetProperty("CombineSubAccounts", false);
                $this->SetProperty("Number", "MainNumber");
                $this->SetProperty("SubAccounts", [
                    [
                        'Code'        => 'first',
                        'Number'      => 'SubNumber',
                        'DisplayName' => 'First subaccount',
                        'Balance'     => rand(1, 10000),
                        'Currency'    => 'points',
                    ],
                ]);

                break;

            case "subaccount_expired":
                $this->SetBalance(1);
                $this->SetProperty("CombineSubAccounts", false);
                $this->SetProperty("Number", "MainNumber");
                $this->SetProperty("SubAccounts", [
                    [
                        'Code'           => 'first',
                        'Number'         => 'SubNumber',
                        'DisplayName'    => 'First subaccount',
                        'Balance'        => rand(1, 10000),
                        'ExpirationDate' => strtotime("+1 year +6 month"),
                        'Currency'       => 'points',
                    ],
                ]);

                break;

            case "subaccount_expired_combined":
                $this->SetBalanceNA();
                $this->SetProperty("CombineSubAccounts", true);
                $this->SetProperty("Number", "MainNumber");
                $this->SetProperty("SubAccounts", [
                    [
                        'Code'           => 'first',
                        'Number'         => 'SubNumber',
                        'DisplayName'    => 'First subaccount',
                        'Balance'        => rand(1, 10000),
                        'ExpirationDate' => strtotime("+1 year +6 month"),
                        'Currency'       => 'points',
                    ],
                ]);

                break;

            case "3.subaccounts":
                $this->SetBalance(1);
                $this->SetProperty("CombineSubAccounts", false);
                $this->SetProperty("Number", "MainNumber");
                $this->SetProperty("SubAccounts", [
                    [
                        'Code'        => 'first',
                        'Number'      => 'SubNumber',
                        'DisplayName' => 'First subaccount',
                        'Balance'     => 5300,
                        'Currency'    => 'points',
                    ],
                    [
                        'Code'        => 'second',
                        'DisplayName' => 'Second subaccount',
                        'Balance'     => rand(1, 10000),
                        'Currency'    => '$',
                    ],
                ]);

                break;

            case "2.subaccounts":
                $this->SetBalance(1);
                $this->SetProperty("CombineSubAccounts", false);
                $this->SetProperty("Number", "MainNumber");
                $this->SetProperty("SubAccounts", [
                    [
                        'Code'        => 'first',
                        'Number'      => 'SubNumber',
                        'DisplayName' => 'First subaccount',
                        'Balance'     => rand(1, 10000),
                        'Currency'    => 'points',
                    ],
                    [
                        'Code'        => 'second',
                        'DisplayName' => 'Second subaccount',
                        'Balance'     => rand(1, 10000),
                        'Currency'    => '$',
                    ],
                ]);
                $detectedCards = [];
                $listCards = [
                    [
                        'Code'            => 'first',
                        'DisplayName'     => 'First subaccount',
                        'CardDescription' => C_CARD_DESC_CANCELLED,
                    ],
                    [
                        'Code'            => 'second',
                        'DisplayName'     => 'Second subaccount',
                        'CardDescription' => C_CARD_DESC_DELTA,
                    ],
                    [
                        'Code'            => 'third',
                        'DisplayName'     => 'Third subaccount',
                        'CardDescription' => C_CARD_DESC_DO_NOT_EARN,
                    ],
                    [
                        'Code'            => 'fourth',
                        'DisplayName'     => 'Fourth subaccount',
                        'CardDescription' => C_CARD_DESC_CANCELLED,
                    ],
                    [
                        'Code'            => 'fifth',
                        'DisplayName'     => 'Fifth subaccount',
                        'CardDescription' => C_CARD_DESC_MARRIOTT,
                    ],
                    [
                        'Code'            => 'sixth',
                        'DisplayName'     => 'Sixth subaccount',
                        'CardDescription' => C_CARD_DESC_DELTA,
                    ],
                    [
                        'Code'            => 'seventh',
                        'DisplayName'     => 'Seventh subaccount',
                        'CardDescription' => C_CARD_DESC_ACTIVE,
                    ],
                    [
                        'Code'            => 'eighth',
                        'DisplayName'     => 'Eighth subaccount',
                        'CardDescription' => C_CARD_DESC_DO_NOT_EARN,
                    ],
                    [
                        'Code'            => 'ninth',
                        'DisplayName'     => 'Business Card ending with ...3585 (Ink / Ultimate Rewards)',
                        'CardDescription' => C_CARD_DESC_ACTIVE,
                    ],
                    [
                        'Code'            => 'tenth',
                        'DisplayName'     => 'Personal Card ending with ...9899 (Sapphire Prefered / Ultimate Rewards)',
                        'CardDescription' => C_CARD_DESC_ACTIVE,
                    ],
                ];
                $numberOfCards = rand(2, count($listCards));

                for ($i = 0; $i < $numberOfCards; $i++) {
                    $detectedCards[] = $listCards[$i];
                }
                $this->SetProperty("DetectedCards", $detectedCards);

                break;

            case "old.n-a.balance":
                $this->SetBalance(-1);

                break;

            case "new.n-a.balance":
                $this->SetBalanceNA();

                break;

            case "sub.n-a.balance":
                $this->SetBalance(0);
                $this->SetProperty("SubAccounts", [
                    [
                        'Code'        => 'first',
                        'DisplayName' => 'First subaccount with n/a',
                        'Balance'     => null,
                    ],
                    [
                        'Code'        => 'second',
                        'DisplayName' => 'Second subaccount with 100',
                        'Balance'     => 100,
                    ],
                    [
                        'Code'        => 'third',
                        'DisplayName' => 'Еhird subaccount with empty balance string',
                        'Balance'     => '',
                    ],
                    [
                        'Code'        => 'fourth',
                        'DisplayName' => 'Fourth subaccount with no balance',
                    ],
                ]);

                break;

            case "sub.chase":
                $this->SetBalance("114,877");
                $this->SetProperty("DetectedCards", [
                    [
                        'Code'            => 'chase3585',
                        'DisplayName'     => 'Business Card ending with ...3585 (Ink / Ultimate Rewards)',
                        'CardDescription' => C_CARD_DESC_ACTIVE,
                    ],
                    [
                        'Code'            => 'chase9899',
                        'DisplayName'     => 'Personal Card ending with ...9899 (Sapphire Prefered / Ultimate Rewards)',
                        'CardDescription' => C_CARD_DESC_ACTIVE,
                    ],
                ]);
                $this->SetProperty("SubAccounts", [
                    [
                        'Code'        => 'chase3585',
                        'DisplayName' => 'Business Card ending with ...3585 (Ink / Ultimate Rewards)',
                        'Balance'     => "57,094",
                    ],
                    [
                        'Code'        => 'chase9899',
                        'DisplayName' => 'Personal Card ending with ...9899 (Sapphire Prefered / Ultimate Rewards)',
                        'Balance'     => "57,783",
                    ],
                ]);

                if (isset($this->Properties['SubAccounts'])) {
                    for ($i = 0; $i < count($this->Properties['SubAccounts']); $i++) {
                        $this->Properties['SubAccounts'][$i]['SubAccBalance'] = $this->Properties['SubAccounts'][$i]['Balance'];
                        $this->Properties['SubAccounts'][$i]['Balance'] = null;
                    }
                }// if (isset($this->Properties['SubAccounts']))

                break;

            case "sub.chase.freedom":
                $this->SetBalance("114,877");
                $this->allowHtmlProperty("CashBack");
                $this->AddSubAccount(
                    [
                        'Code'        => 'chaseFreedom0236',
                        'DisplayName' => 'Freedom / Ultimate Rewards ...0236 (Personal Card)',
                        'Balance'     => '20,171',
                        'CashBack'    => 'Activated (<a target=\'_blank\' href=\'https://awardwallet.com/blog/link/ChaseFreedomCurrentQuarter\'>Jul-Sep</a>) for <br> Restaurants & Movie Theatres',
                    ]
                );

                break;

            case 'sub.coupon':
                $this->SetBalance("100");
                $this->SetProperty("SubAccounts", [
                    [
                        'Code'        => 'sub.1',
                        'Kind'        => 'C',
                        'Balance'     => 93,
                        'DisplayName' => 'Kit Kat Lounge & Supper Club',
                        'Name'        => 'Kit Kat Lounge & Supper Club',
                        'ShortName'   => 'Kit Kat Lounge & Supper Club',
                        'FinePrint'   => '
                            <p>Expires Jan 28th, 2014. Limit 1 per person. Limit 1 per reservation. Limit 1 per table. Valid only for option purchased. Reservation required. Dine-in only. Must provide 21+ ID to receive alcoholic beverages. Not valid on 2/13-2/16, 10/31, New Year\'s Eve, or other holidays. Mandatory 18% service fee not included. Merchant is solely responsible to purchasers for the care and quality of the advertised goods and services.</p>
                        ',
                        'Details' => '
                        <article class="eight columns pitch">
                                        <p>Vodka comes from fermenting grains or potatoes, just as wine comes from criticizing grapes until they cry. Drink your fruit and veggies with this Groupon. </p>

                            <h4>Choose From Three&nbsp;Options</h4>

                            <ul>
                            <li>$39 for dinner and drinks for 2 (up to a $93 total value) </li>
                            <li>$69&nbsp;for dinner and drinks for 4 (up to a $186 total value)</li>
                            <li>$175 for dinner and drinks for 10 (up to a $465 total value)<p></p></li>
                            </ul>

                            <p>All options include the following for each person: </p>

                            <ul>
                            <li>Kit Kat or Caesar salad (a $10 value each) </li>
                            <li>Entree chosen from the Sauteed Salmon, Chicken and Waffles,&nbsp;Pasta Primavera&nbsp;&nbsp;or the&nbsp;Kit Kat Burger featuring Allen Brothers Prime Beef&nbsp;(up to a $23 value each) </li>
                            <li>Cocktail chosen from a Kit Kat Martini, Lady Gaga Martini, Pomegranate Martini, Cherry Tree Martini, Tropical Diva Martini, or Chocolate Martini (a $13.50 value each) <p></p></li>
                            </ul>

                            <p>See the complete <a href="https://groupon.s3.amazonaws.com/sponsorship-imgs/chicago/KkGrouponPrixFixe_Oct2013.pdf">prix fixe menu</a>. Performances begin every 30 minutes. Check out this <a href="http://vimeo.com/42316909">video preview</a> of the venue, food, and diva performances. </p><p></p>

                                          <div class="merchant-profile">
                                      <h4>
                                        Kit Kat Lounge &amp; Supper Club
                                      </h4>
                                      <p> </p><p>At the <a href="http://gr.pn/YAzSOl">Kit Kat Lounge &amp; Supper Club</a> in the heart of Boystown, <a href="http://www.kitkatchicago.com/divas/sunny.php">Sunny Dee-Lite</a>  leads the troupe of divas enhance the upscale <a href="http://gr.pn/YApTIS">dining</a> experience with a sexy, sassy female impersonation show featuring such pseudo-stars as Tina Turner, Lady Gaga, and Dolly Parton. Every 20 minutes or so, the restaurant becomes a stage for the likes of <a href="http://gr.pn/YABc3M">Madam X</a>, known for her big, elaborate costumes, and <a href="http://gr.pn/UeXVti">Mokha Montrese</a>, winner of the title Miss Continental in 2010. The dinner menu is nearly as dazzling, filled with entrees bearing the names of Golden Age film stars such as Paul Newman, Mae West, and Marlon Brando. Despite their star-studded titles, most dishes feature classic American comfort food such as chicken coq au vin, pasta primavera, and beef stew with potatoes. </p> <p></p>
                                    </div>



                                          <div class="discussion row">
                                      <div class="text-right twelve columns end" data-bhw="AskQuestionCTA" data-bhw-path="DealWriteUp|AskQuestionCTA">
                                        <a class="btn btn-small" href="/deals/kit-kat-lounge-supper-club-1/discussion">
                                          Ask a Question
                                        </a>

                                      </div>
                                    </div>

                                              <div class="dem-callout" data-bhw="DailyEngagementModule" data-bhc="DEM:groupaganda--2" data-bhc-path="DealWriteUp|DailyEngagementModule|DEM:groupaganda--2" data-bhw-path="DealWriteUp|DailyEngagementModule">
                                  <div class="dem-bubble">
                                    <div class="dem-teaser-cat" data-modal-id="dem-modal" data-bhc="DEMcat:groupaganda--2" data-bhc-path="DealWriteUp|DailyEngagementModule|DEM:groupaganda--2|DEMcat:groupaganda--2"></div>
                                    <a href="/groupon_says/groupaganda--2" class="dem-teaser-content" data-modal-id="dem-modal" data-bhc="DEMteaser:groupaganda--2" data-bhc-path="DealWriteUp|DailyEngagementModule|DEM:groupaganda--2|DEMteaser:groupaganda--2">
                                      <p>Propaganda or pride? You be the judge of our motivational posters.</p>
                                    </a>
                                  </div>
                                </div>
                        </article>
                        ',
                        'Quantity'  => 3,
                        'Locations' => [
                            [
                                'Url' => 'https://maps.google.com/maps?f=d&daddr=3700%20N%20Halsted%20St.+Chicago+Illinois+60613+US',
                            ],
                        ],
                        'Currency'       => 'USD',
                        'Price'          => 20,
                        'Value'          => 93,
                        'Save'           => 78,
                        'ExpirationDate' => strtotime('+ 1 month'),
                        'UnableMark'     => 0,
                        'UnablePrint'    => 1,
                        'Certificates'   => [
                            'coupon1' => [
                                'Id'          => 'coupon1',
                                'File'        => 'file.pdf',
                                'Caption'     => '#coupon1',
                                'Used'        => false,
                                'ExpiresAt'   => 9999999999,
                                'PurchasedAt' => null,
                                'Status'      => '',
                            ],
                            'coupon2' => [
                                'Id'          => 'coupon2',
                                'File'        => 'file2.pdf',
                                'Caption'     => '#coupon2',
                                'Used'        => true,
                                'ExpiresAt'   => strtotime('+ 1 month'),
                                'PurchasedAt' => null,
                                'Status'      => '',
                            ],
                            'coupon3' => [
                                'Id'          => 'coupon3',
                                'File'        => 'file3.pdf',
                                'Caption'     => '#coupon3',
                                'Used'        => false,
                                'ExpiresAt'   => strtotime('+ 1 month'),
                                'PurchasedAt' => null,
                                'Status'      => '',
                            ],
                        ],
                    ],
                    [
                        'Code'        => 'sub.2',
                        'Kind'        => 'C',
                        'Balance'     => 145,
                        'DisplayName' => 'The Burger Philosophy',
                        'Name'        => 'The Burger Philosophy',
                        'ShortName'   => 'The Burger Philosophy',
                        'FinePrint'   => '
                            <p>Expires Sep 2nd, 2013. Limit 1 per person, may buy multiple as gifts. Limit 1 per visit. Online reservation required; subject to availability. Credit card required before rental. Must be 18 or older with valid ID and credit/debit card. Subject to weather. Additional $10 fee for extra rider. Merchant is solely responsible to purchasers for the care and quality of the advertised goods and services.</p>
                        ',
                        'Details' => '
                            <p>Details</p>
                        ',
                        'Quantity'  => 2,
                        'Locations' => [
                            [
                                'Url' => 'https://maps.google.com/maps?f=d&daddr=200%20Montrose%20Drive+Chicago+Illinois+60611+US',
                            ],
                            [
                                'Url' => 'https://maps.google.com/maps?f=d&daddr=200%20Montrose%20Drive+Chicago+Illinois+60611+US',
                            ],
                        ],
                        'Currency'       => 'EUR',
                        'Price'          => 35,
                        'Value'          => 145,
                        'Save'           => 76,
                        'ExpirationDate' => strtotime('+1 month'),
                        'UnableMark'     => 1,
                        'UnablePrint'    => 0,
                        'Certificates'   => [
                            'coupon1' => [
                                'Id'          => 'coupon1',
                                'File'        => 'file.pdf',
                                'Caption'     => '#coupon1',
                                'Used'        => false,
                                'ExpiresAt'   => strtotime('- 1 month'),
                                'PurchasedAt' => null,
                                'Status'      => '',
                            ],
                            'coupon2' => [
                                'Id'          => 'coupon2',
                                'File'        => 'file2.pdf',
                                'Caption'     => '#coupon2',
                                'Used'        => false,
                                'ExpiresAt'   => strtotime('+ 1 month'),
                                'PurchasedAt' => null,
                                'Status'      => '',
                            ],
                        ],
                    ],
                    [
                        'Code'        => 'sub.3',
                        'Kind'        => 'C',
                        'Balance'     => 55,
                        'DisplayName' => 'Cesar\'s Restaurant',
                        'Name'        => 'Cesar\'s Restaurant',
                        'ShortName'   => 'Cesar\'s Restaurant',
                        'FinePrint'   => '
                            <p>Expires 60 days after purchase. Limit 1 per person. Limit 1 per table. Valid only for option purchased. Dine-in only. Valid only at Broadway location. Not valid 6/30/13. Merchant is solely responsible for all sales and delivery of alcohol. Must provide 21+ ID to receive alcoholic beverages. Tax & gratuity not included. Merchant is solely responsible to purchasers for the care and quality of the advertised goods and services.</p>
                        ',
                        'Details' => '
                            <p>Details 12345</p>
                        ',
                        'Quantity'       => 0,
                        'Locations'      => [],
                        'Currency'       => 'USD',
                        'Price'          => 10,
                        'Value'          => 55,
                        'Save'           => 82,
                        'ExpirationDate' => strtotime('- 1 month'),
                        'UnableMark'     => 0,
                        'UnablePrint'    => 0,
                        'Certificates'   => [
                            'coupon1' => [
                                'Id'          => 'coupon1',
                                'File'        => 'file.pdf',
                                'Caption'     => '#coupon1',
                                'Used'        => false,
                                'ExpiresAt'   => strtotime('- 1 month'),
                                'PurchasedAt' => null,
                                'Status'      => '',
                            ],
                        ],
                    ],
                ]);

                break;

            case 'sub.expired':
                $this->SetBalanceNA();
                $this->SetProperty("SubAccounts", [
                    [
                        'Code'           => 'SubAcc1',
                        'DisplayName'    => 'SubAccount 1',
                        'Balance'        => "57,094",
                        'ExpirationDate' => strtotime("-1 month"),
                    ],
                    [
                        'Code'           => 'SubAcc2',
                        'DisplayName'    => 'SubAccount 2',
                        'Balance'        => "57,783",
                        'ExpirationDate' => strtotime("-2 month"),
                    ],
                    [
                        'Code'           => 'SubAcc3',
                        'DisplayName'    => 'SubAccount 3',
                        'Balance'        => "31,500",
                        'ExpirationDate' => strtotime("+1 month"),
                    ],
                    [
                        'Code'        => 'SubAcc4',
                        'DisplayName' => 'SubAccount 4',
                        'Balance'     => "11,201",
                    ],
                ]);

                break;

            case 'sub.cvs':
                $this->SetBalanceNA();
                $this->SetProperty("SubAccounts", [
                    [
                        'Code'           => 'SubAcc1',
                        'DisplayName'    => 'SubAccount 1',
                        'Balance'        => "57,094",
                        'ExpirationDate' => strtotime(date("Y-m-d", strtotime("+3 day"))),
                    ],
                    [
                        'Code'           => 'SubAcc2',
                        'DisplayName'    => 'SubAccount 2',
                        'Balance'        => "57,783",
                        'ExpirationDate' => strtotime(date("Y-m-d", strtotime("+7 day"))),
                    ],
                    [
                        'Code'           => 'SubAcc3',
                        'DisplayName'    => 'SubAccount 3',
                        'Balance'        => "31,500",
                        'ExpirationDate' => strtotime(date("Y-m-d", strtotime("+1 month"))),
                    ],
                    [
                        'Code'           => 'SubAcc4',
                        'DisplayName'    => 'SubAccount 4',
                        'Balance'        => "11,201",
                        'ExpirationDate' => strtotime(date("Y-m-d", strtotime("07/31/2014"))),
                    ],
                    [
                        'Code'           => 'SubAcc5',
                        'DisplayName'    => 'SubAccount 5',
                        'Balance'        => "11,201",
                        'ExpirationDate' => strtotime(date("Y-m-d", strtotime("+3 month"))),
                    ],
                ]);

                break;

            case 'sub.delta':
                $this->SetBalanceNA();
                $this->SetProperty("SubAccounts", [
                    [
                        'Code'        => 'amex1M85405097',
                        'DisplayName' => 'Blue from American Express (Membership Rewards®)',
                        'Balance'     => "6,914",
                        'Number'      => "1M85405097",
                    ],
                    [
                        'Code'                  => 'amex9406358912',
                        'DisplayName'           => 'Business (JEROME A JOAQUIN) Gold Delta SkyMiles® Busn (Delta SkyMiles®)',
                        'Balance'               => "126,819",
                        'Number'                => "9406358912",
                        'ProviderCode'          => 'delta',
                        'ProviderAccountNumber' => '9406358912',
                    ],
                ]);
                $this->SetProperty("DetectedCards", [
                    [
                        'Code'            => 'amex42293649647',
                        'DisplayName'     => 'Starwood Preferred Guest (Starpoints&reg;)',
                        'CardDescription' => C_CARD_DESC_MARRIOTT,
                    ],
                    [
                        'Code'            => 'amex1M85405097',
                        'DisplayName'     => 'Blue from American Express (Membership Rewards®)',
                        'CardDescription' => C_CARD_DESC_ACTIVE,
                    ],
                    [
                        'Code'            => 'amex9406358912',
                        'DisplayName'     => 'Business (JEROME A JOAQUIN) Gold Delta SkyMiles® Busn (Delta SkyMiles®)',
                        'CardDescription' => C_CARD_DESC_DELTA,
                    ],
                ]);

                // no break
            case "throttle":
                throw new ThrottledException(3);

                break;

            case "pass2name":
                $this->SetBalanceNA();
                $this->SetProperty("Name", $this->AccountFields['Pass']);

                break;

            case "elite.complex":
                $this->SetBalance(rand(1, 10000));
                $this->SetProperty('EliteStatus', 'Level0');
                $this->SetProperty('Property1', 2);
                $this->SetProperty('Property2', 150);
                $this->SetProperty('Property3', 500);
                $this->SetProperty('Property4', 5000);
                $this->SetProperty('Property5', 50000);

                break;

            case "elite.complex1":
                $this->SetBalance(rand(1, 10000));
                $this->SetProperty('EliteStatus', 'Level1');
                $this->SetProperty('Property1', 7);
                $this->SetProperty('Property2', 160);
                $this->SetProperty('Property3', 530);
                $this->SetProperty('Property4', 5200);
                $this->SetProperty('Property5', 58000);

                break;

            case "elite.complex2":
                $this->SetBalance(rand(1, 10000));
                $this->SetProperty('EliteStatus', 'Level2');
                $this->SetProperty('Property1', 5);
                $this->SetProperty('Property2', 190);
                $this->SetProperty('Property3', 430);
                $this->SetProperty('Property4', 7200);
                $this->SetProperty('Property5', 98000);

                break;

            case "elite.complex3":
                $this->SetBalance(rand(1, 10000));
                $this->SetProperty('EliteStatus', 'Level3');
                $this->SetProperty('Property1', 1);
                $this->SetProperty('Property2', 290);
                $this->SetProperty('Property3', 730);
                $this->SetProperty('Property4', 4800);
                $this->SetProperty('Property5', 82000);

                break;

            case "elite.complex4":
                $this->SetBalance(rand(1, 10000));
                $this->SetProperty('EliteStatus', 'Level4');
                $this->SetProperty('Property1', 2);
                $this->SetProperty('Property2', 130);
                $this->SetProperty('Property3', 430);
                $this->SetProperty('Property4', 4200);
                $this->SetProperty('Property5', 98000);

                break;

            case "elite.complex5":
                $this->SetBalance(rand(1, 10000));
                $this->SetProperty('EliteStatus', 'Level5');
                $this->SetProperty('Property1', 2);
                $this->SetProperty('Property2', 190);
                $this->SetProperty('Property3', 430);
                $this->SetProperty('Property4', 7200);
                $this->SetProperty('Property5', 98000);

                break;

            case "subaccount.to.delta":
                $this->SetBalanceNA();
                $this->SetProperty("SubAccounts", [
                    [
                        'Code'        => 'first',
                        'DisplayName' => 'First subaccount with n/a',
                        'Balance'     => null,
                    ],
                    [
                        'Code'                  => 'second',
                        'DisplayName'           => 'Second subaccount with 100',
                        "ProviderCode"          => "delta",
                        "ProviderAccountNumber" => "fromSubAccount",
                        "Balance"               => rand(200, 500),
                    ],
                ]);

                break;

            case "unknown.error":
                break;

            case "security.question":
                $questions = ["What is your mother's middle name?", "Where were you born?",
                    "In what city were you born?", "How old are you?", ];
                $key = array_rand($questions);
                $question = $questions[$key];

                if (empty($this->Answers[$question]) || $question == "How old are you?") {
                    $this->AskQuestion($question);
                } else {
                    $this->SetBalance($key);
                }

                break;

            case "notifications":
                $this->SetBalanceNA();
                $this->http->Log("Notification sent");
                $title = "Test notification";
                $this->sendNotification($title);

//                $this->sendNotification("{$title} - failed to retrieve itinerary by conf #", 'all', true,
//                    "Conf #: <a target='_blank' href='https://awardwallet.com/manager/loyalty/logs?ConfNo=224IZB'>224IZB</a><br/>Name: Karlin");

//                $this->sendNotification("{$title} - failed to retrieve itinerary by conf #", 'all', true,
//                    "Conf #: <a target='_blank' href='https://awardwallet.com/manager/loyalty/logs?ConfNo=224IZB'>224IZB</a>");

//                $this->http->Log("Notification sent: {$title}");
//                /* Email title gets from message "Title: {$title}<br/>" block be regexp "/Title:\s*([^\<]+)\<br\/\>/" */
//                $accountID = ArrayVal($this->AccountFields, 'RequestAccountID', $this->AccountFields['AccountID']);
//                $body = "AccountID: <a target='_blank' href='https://awardwallet.com/manager/loyalty/logs?AccountID={$accountID}'>"
//                    . $accountID ."</a>"
//                    . "<br/>UserID: " . $this->AccountFields['UserID']
//                    . "<br/>Partner: " . $this->AccountFields['Partner']
//                    . "<br/>Login: " . preg_replace('#^\d{12}(\d{4})$#ims', '...$1', $this->AccountFields['Login']);
//
//                $message = "Title: {$title}<br/>{$body}";
//
//                try {
//                    $this->http->Log($message, LOG_LEVEL_ALERT);
//                }
//                catch (Swift_TransportException $e) {
//                    $this->logger->error("Swift_TransportException: " . $e->getMessage());
//                }
                break;

            case "facebook.groupon":
            case "facebook.groupon.uk":
                $this->SetBalance(666);

                break;

//            case "trip.badcodes":
//                throw new CheckException("Itineraries were commented out", ACCOUNT_WARNING);
//				break;
//
            case "memory":
                $this->SetBalanceNA();
                $this->http->Log("gc cycles: " . gc_collect_cycles());

                for ($n = 0; $n < 50; $n++) {
                    $this->http->GetURL("http://awardwallet.local/admin/test/memory.php");
                    $this->http->Log("memory, step $n: " . round(memory_get_usage(true) / 1024 / 1024, 3));
                }

                break;

            case "account.number.random":
                $this->SetBalance(100);
                $this->SetProperty('Number', RandomStr(ord('a'), ord('z'), 10));

                break;

            default:
                $arFields = [];
                $this->TuneFormFields($arFields);

                if (!isset($arFields['Login']['Options'][$this->AccountFields['Login']]) && strpos($this->AccountFields['Login'], 'itmaster') === false) {
                    throw new Exception("unknown login");
                } else {
                    $this->SetBalanceNA();
                }
//				break;
        }
    }

    public function ParseItineraries()
    {
        $lastTime = time();
        $lastTime = $lastTime - $lastTime % 60;

        if (strpos($this->AccountFields['Login'], 'itmaster.') !== false) {
            if (strpos($this->AccountFields['Login'], 'itmaster.no') !== false) {
                $result = [];

                foreach (['t', 'r', 'l', 'e'] as $kind) {
                    if (strpos($this->AccountFields['Login'], $kind, strlen('itmaster.no.')) !== false) {
                        $result[] = [
                            'Kind'          => strtoupper($kind),
                            'NoItineraries' => true,
                        ];
                    }
                }

                if (empty($result)) {
                    return $this->noItinerariesArr();
                }

                return $result;
            } elseif (preg_match('/
                                    (?:(?<TA>\d)(?<TAc>\d)ta)? # air trip, example: 21ta, first number 2 stands for 2 itineraries, 1 - one cancelled
                                    (?:(?<TC>\d)(?<TCc>\d)tc)? # cruise
                                    (?:(?<TT>\d)(?<TTc>\d)tt)? # train
                                    (?:(?<TB>\d)(?<TBc>\d)tb)? # bus
                                    (?:(?<TF>\d)(?<TFc>\d)tf)? # ferry
                                    (?:(?<R>\d)(?<Rc>\d)r)?    # hotel reservation
                                    (?:(?<L>\d)(?<Lc>\d)l)?    # rental car
                                    (?:(?<E>\d)(?<Ec>\d)e)?    # events, restaurants
                                /x', substr($this->AccountFields['Login'], strlen('itmaster.')), $itcount)
            ) {
                $confs = [
                    'TA' => 'TA%05d',
                    'TC' => 'TC%05d',
                    'TT' => 'TT%05d',
                    'TB' => 'TB%05d',
                    'TF' => 'TF%05d',
                    'R'  => 'R%05d',
                    'L'  => 'L%05d',
                    'E'  => 'E%05d',
                ];
                $key = [
                    'TA' => 'RecordLocator',
                    'TC' => 'RecordLocator',
                    'TT' => 'RecordLocator',
                    'TB' => 'RecordLocator',
                    'TF' => 'RecordLocator',
                    'R'  => 'ConfirmationNumber',
                    'L'  => 'Number',
                    'E'  => 'ConfNo',
                ];
                $templates = [
                    'TA' => [
                        'Kind'         => 'T',
                        'TripCategory' => TRIP_CATEGORY_AIR,
                        //'RecordLocator' => NULL,
                        'Passengers' => [
                            'John Johnson',
                            'Molly Johnson',
                        ],
                        'AccountNumbers'  => 'T82399832, T0392820392',
                        'TotalCharge'     => '2344.22',
                        'BaseFare'        => '2000.22',
                        'Currency'        => 'USD',
                        'Tax'             => '200.2',
                        'SpentAwards'     => '1000 miles',
                        'EarnedAwards'    => '500 miles',
                        'Status'          => 'confirmed',
                        'ReservationDate' => time() - SECONDS_PER_DAY * 32,
                        'TripSegments'    => [
                            [
                                'DepDate'       => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_DAY * 14),
                                'DepCode'       => 'JFK',
                                'DepName'       => 'JF Kennedy Airport',
                                'ArrDate'       => $this->clipSecondsFromTimeStamp($lastTime += 235 * 60),
                                'ArrCode'       => 'LAX',
                                'ArrName'       => 'Los Angeles International Airport',
                                'FlightNumber'  => 'TE223',
                                'AirlineName'   => 'Test Airlines',
                                'Aircraft'      => 'Test Aircraft 203',
                                'TraveledMiles' => 500,
                                'Cabin'         => 'Economy',
                                'BookingClass'  => 'T',
                                'Seats'         => '2G, 14F',
                                'Duration'      => '5h',
                                'Meal'          => 'vegan',
                                'Stops'         => 0,
                            ],
                            [
                                'DepDate'       => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 2),
                                'DepCode'       => 'JFK',
                                'DepName'       => 'JF Kennedy Airport',
                                'ArrDate'       => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR),
                                'ArrCode'       => 'LAX',
                                'ArrName'       => 'Los Angeles International Airport',
                                'FlightNumber'  => 'TE2245',
                                'AirlineName'   => 'Test Airlines',
                                'Aircraft'      => 'Test Aircraft 203',
                                'TraveledMiles' => 500,
                                'Cabin'         => 'Economy',
                                'BookingClass'  => 'T',
                                'Seats'         => '12G, 2F',
                                'Duration'      => '3h',
                                'Meal'          => 'vegan',
                                'Stops'         => 1,
                            ],
                        ],
                    ],
                    'TC' => [
                        'Kind'         => 'T',
                        'TripCategory' => TRIP_CATEGORY_CRUISE,
                        //'RecordLocator' => NULL,
                        'Passengers' => [
                            'John Johnson',
                            'Molly Johnson',
                        ],
                        'AccountNumbers'  => 'T829832, T03920392',
                        'SpentAwards'     => '100 miles',
                        'EarnedAwards'    => '50 miles',
                        'Status'          => 'confirmed',
                        'ShipName'        => 'Varyag',
                        'ShipCode'        => 'VG',
                        'Deck'            => 'Main Deck',
                        'RoomNumber'      => '100',
                        'RoomClass'       => 'First',
                        'ReservationDate' => time() - SECONDS_PER_DAY * 19,
                        'TripSegments'    => [
                            [
                                'DepDate' => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 3),
                                'ArrDate' => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 7),
                                //'FlightNumber' => 'Sea1234',
                                //'Port' => 'New York',
                                'ArrName' => 'New York',
                                'DepName' => 'London',
                            ],
                            [
                                'DepDate' => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 8),
                                'ArrDate' => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 10),
                                //'FlightNumber' => 'Sea12345',
                                //'Port' => 'London',
                                'ArrName' => 'London',
                                'DepName' => 'Tower',
                            ],
                        ],
                    ],
                    'TT' => [
                        'Kind'         => 'T',
                        'TripCategory' => TRIP_CATEGORY_TRAIN,
                        //'RecordLocator' => NULL,
                        'Passengers' => [
                            'John Johnson',
                            'Molly Johnson',
                        ],
                        'AccountNumbers'  => 'T8239832, T03928203',
                        'SpentAwards'     => '100 miles',
                        'EarnedAwards'    => '50 miles',
                        'Status'          => 'confirmed',
                        'ReservationDate' => time() - SECONDS_PER_DAY * 11,
                        'TripSegments'    => [
                            [
                                'DepDate' => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 3),
                                'DepCode' => 'KGX',
                                'DepName' => 'Kings Cross',
                                'Seats'   => '22, 44',

                                'Type'         => 'Train',
                                'FlightNumber' => 'Train123',
                                'Cabin'        => 'Economy',

                                'Duration' => '1h23m',
                                'Meal'     => 'meat',

                                'ArrDate' => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 2),
                                'ArrCode' => 'MCO',
                                'ArrName' => 'Manchester Oxford Road',
                            ],
                            [
                                'DepDate' => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 1),
                                'DepCode' => 'MCO',
                                'DepName' => 'Manchester Oxford Road',
                                'Type'    => 'Train',

                                'FlightNumber' => 'Train12345',

                                'Seats'    => '99, 90',
                                'Cabin'    => 'Platzcart!',
                                'Duration' => '2h24m',
                                'Meal'     => 'veg',

                                'ArrCode' => 'OXF',
                                'ArrDate' => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 2),
                                'ArrName' => 'Oxford',
                            ],
                        ],
                    ],
                    'TB' => [
                        'Kind'         => 'T',
                        'TripCategory' => TRIP_CATEGORY_BUS,
                        //'RecordLocator' => NULL,
                        'TripSegments' => [
                            [
                                'DepDate' => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 2),
                                'DepCode' => 'OXF',
                                'DepName' => 'Oxford',

                                'Type'         => 'Bus',
                                'Seats'        => '12, 13',
                                'FlightNumber' => 'Bus1234',

                                'ArrDate' => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 3),
                                'ArrCode' => 'HTW',
                                'ArrName' => 'Hartwood',
                            ],
                            [
                                'DepDate' => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 2),
                                'DepCode' => 'HTW',
                                'DepName' => 'Hartwood',

                                'FlightNumber' => 'Bus12345',

                                'Type'  => 'Bus',
                                'Seats' => '16, 17',

                                'ArrDate' => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 3),
                                'ArrCode' => 'HWM',
                                'ArrName' => 'Harlow Mill',
                            ],
                        ],
                    ],

                    'TF' => [
                        'Kind'         => 'T',
                        'TripCategory' => TRIP_CATEGORY_FERRY,
                        //'RecordLocator' => NULL,
                        'TripSegments' => [
                            [
                                'DepDate'      => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 1),
                                'FlightNumber' => 'Ferry123',
                                'DepName'      => 'Harlow Mill',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 1),
                                'ArrName'      => 'Ohansk',
                            ],
                        ],
                    ],
                    'R' => [
                        'Kind' => 'R',
                        //'ConfirmationNumber' => NULL,
                        'HotelName'      => 'Sheraton Philadelphia Downtown Hotel',
                        'CheckInDate'    => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_DAY * 7),
                        'CheckOutDate'   => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_DAY * 8),
                        'Address'        => '123, Street',
                        'Guests'         => '2',
                        'RoomType'       => '2 QUEEN',
                        'GuestNames'     => ['John Smith', 'Jane Smith'],
                        'AccountNumbers' => 'R92033333',
                    ],
                    'L' => [
                        'Kind' => 'L',
                        //'Number' => NULL,
                        'CarModel'        => 'Ford Focus',
                        'CarType'         => 'M',
                        'PickupLocation'  => 'Sheraton Philadelphia Downtown Hotel',
                        'PickupDatetime'  => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 2),
                        'DropoffLocation' => 'Sheraton Philadelphia Downtown Hotel',
                        'DropoffDatetime' => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 4),
                        'AccountNumbers'  => 'L230923092',
                    ],
                    'E' => [
                        'Kind'      => 'E',
                        'Name'      => 'McDonalds',
                        'StartDate' => $this->clipSecondsFromTimeStamp($lastTime += SECONDS_PER_HOUR * 1),
                        //'ConfNo' => NULL,
                        'Address'        => '123, Street',
                        'Phone'          => '1234567890',
                        'Guests'         => '1',
                        'DinnerName'     => 'John Smith',
                        'AccountNumbers' => 'OT39390',
                    ],
                ];
                $result = [];

                foreach (array_keys($confs) as $kind) {
                    if (!isset($itcount[$kind])) {
                        $itcount[$kind] = 0;
                    }

                    for ($i = 0; $i < $itcount[$kind]; $i++) {
                        $it = $templates[$kind];
                        $it[$key[$kind]] = sprintf($confs[$kind], $i);
                        $result[] = $it;
                    }

                    if (!isset($itcount[$kind . 'c'])) {
                        $itcount[$kind . 'c'] = 0;
                    }

                    for ($i = 0; $i < $itcount[$kind . 'c']; $i++) {
                        $it = [
                            'Kind'      => $kind[0],
                            $key[$kind] => sprintf($confs[$kind], $i + $itcount[$kind]),
                            'Cancelled' => true,
                        ];
                        $result[] = $it;
                    }
                }

                return $result;
            }
        } else {
            switch ($this->AccountFields['Login']) {
            case 'past.future.trip':
                return [
                    [
                        'Kind'            => 'T',
                        'RecordLocator'   => 'TESTCN',
                        'Passengers'      => 'John Smith',
                        'TotalCharge'     => 100,
                        'Tax'             => 7,
                        'Currency'        => 'USD',
                        'ReservationDate' => time() - SECONDS_PER_DAY * 3 - 12 * 3634,
                        'TripSegments'    => [
                            0 =>
                            [
                                'AirlineName'  => 'Test Airlines',
                                'Duration'     => '5:15',
                                'DepDate'      => $this->clipSecondsFromTimeStamp(time() - SECONDS_PER_DAY * 3 - 6 * 3634),
                                'DepCode'      => 'JFK',
                                'DepName'      => 'JF Kennedy Airport',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(time() - SECONDS_PER_DAY * 3),
                                'ArrCode'      => 'LAX',
                                'ArrName'      => 'Los Angeles International Airport',
                                'FlightNumber' => 'TE223',
                                'Seats'        => '11',
                            ],
                            1 =>
                            [
                                'AirlineName'  => 'Test Airlines',
                                'Duration'     => '5:30',
                                'DepDate'      => $this->clipSecondsFromTimeStamp(time() - SECONDS_PER_DAY * 2 - 6 * 3521),
                                'DepCode'      => 'LAX',
                                'DepName'      => 'Los Angeles International Airport',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(time() - SECONDS_PER_DAY * 2),
                                'ArrCode'      => 'MIA',
                                'ArrName'      => 'Miami',
                                'FlightNumber' => 'TE224',
                                'Seats'        => '14',
                            ],
                            2 =>
                            [
                                'AirlineName'  => 'Test Airline',
                                'Duration'     => '3:00',
                                'DepDate'      => $this->clipSecondsFromTimeStamp(time() + SECONDS_PER_DAY + 3600),
                                'DepCode'      => 'MIA',
                                'DepName'      => 'Miami',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(time() + SECONDS_PER_DAY + 3600 * 4),
                                'ArrCode'      => 'SUE',
                                'ArrName'      => 'Door County Cherryland',
                                'FlightNumber' => 'TE225',
                                'Seats'        => '16',
                            ],
                            3 =>
                            ['AirlineName'     => 'Test Airlinesdsf',
                                'Duration'     => '2:00',
                                'DepDate'      => $this->clipSecondsFromTimeStamp(time() + SECONDS_PER_DAY + 3600 * 5),
                                'DepCode'      => 'SUE',
                                'DepName'      => 'Door County Cherryland',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(time() + SECONDS_PER_DAY + 3600 * 7),
                                'ArrCode'      => 'JFK',
                                'ArrName'      => 'JF Kennedy Airport',
                                'FlightNumber' => 'TE226',
                                'Seats'        => '18',
                            ],
                        ],
                    ],
                    /*					array(
                        'Kind' => 'L',
                        'Number' => '123456789',
                        'ConfirmationNumber' => '123456789',
                        'RecordLocator' => '123456789',
                        'PickupDatetime' => $this->clipSecondsFromTimeStamp(time() - SECONDS_PER_DAY * 2 + 3600),
                        'PickupLocation' => 'Miami',
                        'DropoffDatetime' => $this->clipSecondsFromTimeStamp(time() - SECONDS_PER_DAY * 2 + 3600 * 2),
                        'DropoffLocation' => '123, Street, Miami',
                        'PickupPhone' => '122-236-785',
                        'PickupHours' => '11 a.m.',
                        'DropoffHours' => '3 p.m.',
                        'CarModel' => 'Opel',
                        'RenterName' => 'Den Matson',
                        'TotalCharge' => 158.30,
                        'Currency' => 'USD',
                        'TotalTaxAmount' => 21.70,
                    ),*/
                    [
                        'Kind'                => 'R',
                        'ConfirmationNumber'  => '1252463788',
                        'HotelName'           => 'National',
                        'CheckInDate'         => $this->clipSecondsFromTimeStamp(time() - SECONDS_PER_DAY * 2 + 3600 * 2),
                        'CheckOutDate'        => $this->clipSecondsFromTimeStamp(time() + SECONDS_PER_DAY - 3600 * 2),
                        'Address'             => '123, Street, Miami',
                        'Phone'               => '123-745-856',
                        'Guests'              => 3,
                        'Kids'                => 0,
                        'Rooms'               => 2,
                        'Rate'                => '9600 starpoints and USD 180.00',
                        'RateType'            => 'Spg Cash & Points Only To Be Booked With A Spg Award Stay. Guest Must Be A Spg.member. Must Redeem Starpoints For Cash And Points Award.',
                        'CancellationPolicy'  => '',
                        'RoomType'            => 'Superior Romantic Glimmering Room - Earlybird',
                        'RoomTypeDescription' => 'Non-Smoking Room Confirmed',
                        'Cost'                => 280.60,
                        'Taxes'               => 35.10,
                        'Total'               => 315.70,
                        'Currency'            => 'USD',
                    ],
                    /*					array(
                        'Kind' => 'L',
                        'Number' => '123456789',
                        'ConfirmationNumber' => '123456789',
                        'RecordLocator' => '123456789',
                        'PickupDatetime' => $this->clipSecondsFromTimeStamp(time() + SECONDS_PER_DAY - 3600),
                        'PickupLocation' => '123, Street, Miami',
                        'DropoffDatetime' => $this->clipSecondsFromTimeStamp(time() + SECONDS_PER_DAY),
                        'DropoffLocation' => 'MIA',
                        'PickupPhone' => '122-236-785',
                        'PickupHours' => '11 a.m.',
                        'DropoffHours' => '3 p.m.',
                        'CarModel' => 'Lada Kalina',
                        'RenterName' => 'Den Mihelson',
                        'TotalCharge' => 158.30,
                        'Currency' => 'USD',
                        'TotalTaxAmount' => 21.70,
                    ),*/
                ];

            case 'future.trip':
                return [
                    [
                        'Kind'            => 'T',
                        'RecordLocator'   => 'TESTCN',
                        'TripNumber'      => '43545-67778-3424',
                        'Passengers'      => 'John Smith, Katy Smith',
                        'TotalCharge'     => 100,
                        'Tax'             => 7,
                        'Currency'        => 'USD',
                        'SpentAwards'     => '100500 miles',
                        'EarnedAwards'    => '500 miles',
                        'ReservationDate' => strtotime('2014-01-01 9:00'),
                        'TripSegments'    => [
                            [
                                'AirlineName'      => 'DL',
                                'Duration'         => '3:55',
                                'DepDate'          => $this->clipSecondsFromTimeStamp(strtotime('2030-01-01 10:00')),
                                'DepCode'          => 'JFK',
                                'DepName'          => 'JF Kennedy Airport',
                                'ArrDate'          => $this->clipSecondsFromTimeStamp(strtotime('2030-01-01 13:55')),
                                'ArrCode'          => 'LAX',
                                'ArrName'          => 'Los Angeles International Airport',
                                'FlightNumber'     => 'TE223',
                                'Seats'            => '23',
                                'BookingClass'     => 'C',
                                'PendingUpgradeTo' => 'N',
                                'Stops'            => 0,
                            ],
                        ],
                        'ConfirmationNumbers' => '12345, 56789',
                    ],
                ];

            case 'future.trip.random.seats':
                if ($this->AccountFields['Pass'] == 'random.dates') {
                    $dateOffset = rand(0, 60 * 24) * 60;
                } else {
                    $dateOffset = 0;
                }

                return [
                    [
                        'Kind'            => 'T',
                        'RecordLocator'   => 'TESTCN',
                        'Passengers'      => 'John Smith, Katy Smith',
                        'TotalCharge'     => 100,
                        'Tax'             => 7,
                        'Currency'        => 'USD',
                        'ReservationDate' => strtotime('2014-01-01 9:00'),
                        'TripSegments'    => [
                            [
                                'AirlineName'  => 'DL',
                                'Duration'     => '3:55',
                                'DepDate'      => $this->clipSecondsFromTimeStamp(strtotime('+1 year 1/1 10:00') + $dateOffset + rand(1, 1000)),
                                'DepCode'      => 'JFK',
                                'DepName'      => 'JF Kennedy Airport',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime('+1 year 1/1 13:55') + $dateOffset + rand(1, 1000)),
                                'ArrCode'      => 'LAX',
                                'ArrName'      => 'Los Angeles International Airport',
                                'FlightNumber' => 'TE223',
                                'Seats'        => rand(101, 200) . ', ' . rand(1, 99),
                                'Gate'         => rand(1, 19),
                                'BaggageClaim' => rand(1, 19),
                            ],
                        ],
                        'ConfirmationNumbers' => '12345, 56789',
                    ],
                ];

            case 'future.trip.random.props':
                return [
                    [
                        'Kind'            => 'T',
                        'RecordLocator'   => 'TESTRANDT',
                        'Passengers'      => 'John Smith, Katy Smith',
                        'TotalCharge'     => rand(100, 50000),
                        'Tax'             => rand(1, 500),
                        'Currency'        => 'USD',
                        'ReservationDate' => strtotime('+1 year'),
                        'TripSegments'    => [
                            [
                                'AirlineName'  => 'DL',
                                'Duration'     => rand(1, 12) . "h",
                                'DepDate'      => $this->clipSecondsFromTimeStamp(strtotime('+1 year') + rand(1, 1000)),
                                'DepCode'      => 'JFK',
                                'DepName'      => 'JF Kennedy Airport',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime('+1 year 1 hour') + rand(1, 1000)),
                                'ArrCode'      => 'LAX',
                                'ArrName'      => 'Los Angeles International Airport',
                                'FlightNumber' => 'TE001',
                                'Seats'        => rand(101, 200) . ', ' . rand(1, 99),
                                'Gate'         => rand(1, 200),
                                'BaggageClaim' => rand(1, 200),
                            ],
                            [
                                'AirlineName'  => 'DL2',
                                'Duration'     => rand(1, 12) . "h",
                                'DepDate'      => $this->clipSecondsFromTimeStamp(strtotime('+1 year 1 day') + rand(1, 1000)),
                                'DepCode'      => 'AUH',
                                'DepName'      => 'Abu Dhabi International',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime('+1 year 2 day') + rand(1, 1000)),
                                'ArrCode'      => 'AAN',
                                'ArrName'      => 'Al Ain International',
                                'FlightNumber' => 'TE002',
                                'Seats'        => rand(101, 200) . ', ' . rand(1, 99),
                                'Gate'         => rand(1, 200),
                                'BaggageClaim' => rand(1, 200),
                            ],
                            [
                                'AirlineName'  => 'DL3',
                                'Duration'     => rand(1, 12) . "h",
                                'DepDate'      => $this->clipSecondsFromTimeStamp(strtotime('+1 year 3 day') + rand(1, 1000)),
                                'DepCode'      => 'NHD',
                                'DepName'      => 'Al Minhad Air Base',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime('+1 year 4 day') + rand(1, 1000)),
                                'ArrCode'      => 'SHJ',
                                'ArrName'      => 'Sharjah International',
                                'FlightNumber' => 'TE003',
                                'Seats'        => rand(101, 200) . ', ' . rand(1, 99),
                                'Gate'         => rand(1, 200),
                                'BaggageClaim' => rand(1, 200),
                            ],
                        ],
                        'ConfirmationNumbers' => '123456',
                    ],
                ];

            case "future.trip.random.props.not_white_list":
                return
                [
                    [
                        'Kind'            => 'T',
                        'RecordLocator'   => 'TESTXYZ',
                        'Passengers'      => 'John Smith, Katy Smith',
                        'TotalCharge'     => 120,
                        'Tax'             => 11,
                        'Currency'        => 'USD',
                        'ReservationDate' => strtotime('+1 day') + rand(1, 100000),
                        'TripSegments'    => [
                            [
                                'AirlineName'  => 'DL',
                                'Duration'     => rand(1, 120000) . 'h',
                                'DepDate'      => $this->clipSecondsFromTimeStamp(strtotime('2017-01-01 10:10:10')),
                                'DepCode'      => 'JFK',
                                'DepName'      => 'JF Kennedy Airport',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime('2017-01-01 12:10:10')),
                                'ArrCode'      => 'LAX',
                                'ArrName'      => 'Los Angeles International Airport',
                                'FlightNumber' => 'YES009',
                                'Seats'        => '12, 15',
                                'Gate'         => 2,
                                'BaggageClaim' => 3,
                            ],
                        ],
                        'ConfirmationNumbers' => '12345, 56789',
                    ],
                ];

            case 'trip.overnight':
                return [
                    [
                        'Kind'            => 'T',
                        'RecordLocator'   => 'TESTCN',
                        'Passengers'      => 'John Smith, Katy Smith',
                        'TotalCharge'     => 100,
                        'Tax'             => 7,
                        'Currency'        => 'USD',
                        'ReservationDate' => strtotime('2014-01-01 9:00'),
                        'TripSegments'    => [
                            [
                                'AirlineName'  => 'DL',
                                'Duration'     => '3:55',
                                'DepDate'      => strtotime('2030-01-01 10:00'), // 2030-01-01 15:00 UTC
                                'DepCode'      => 'JFK', // timezone -18000 (-5)
                                'DepName'      => 'John F. Kennedy International Airport',
                                'ArrDate'      => strtotime('2030-01-01 13:55'), // 2030-01-01 21:55 UTC
                                'ArrCode'      => 'LAX', // timezone -28800 (-8)
                                'ArrName'      => 'Los Angeles International Airport',
                                'FlightNumber' => 'TE223',
                                'Seats'        => '23',
                            ],
                            [
                                'AirlineName'  => 'DL',
                                'Duration'     => '1:55',
                                'DepDate'      => strtotime('2030-01-01 09:00'), // 2030-01-01 17:00 UTC, should be corrected to next day because of previous segment, not implemented yet
                                'DepCode'      => 'LAX', // timezone -28800 (-8) // because there could be reservations with multiple not connected segments, may be should take flight number into account
                                'DepName'      => 'Los Angeles International Airport',
                                'ArrDate'      => strtotime('2030-01-01 10:55'), // 2030-01-01 18:55 UTC
                                'ArrCode'      => 'SFO', // timezone -28800 (-8)
                                'ArrName'      => 'San Francisco International Airport',
                                'FlightNumber' => 'TE224',
                                'Seats'        => '23',
                            ],
                            [
                                'AirlineName'  => 'DL',
                                'Duration'     => '1:55',
                                'DepDate'      => strtotime('2030-01-02 09:00'), // 2030-01-02 17:00 UTC
                                'DepCode'      => 'SFO', // timezone -28800 (-8)
                                'DepName'      => 'San Francisco International Airport',
                                'ArrDate'      => strtotime('2030-01-02 10:55'), // 2030-01-02 5:55 UTC, should be corrected to next day
                                'ArrCode'      => 'PEE', // timezone 18000 (+5)
                                'ArrName'      => 'Perm International Airport',
                                'FlightNumber' => 'TE225',
                                'Seats'        => '23',
                            ],
                        ],
                    ],
                ];

            case 'trip.nocodes':
                return [
                    [
                        'Kind'            => 'T',
                        'RecordLocator'   => 'TESTCN',
                        'ReservationDate' => strtotime('2014-01-01 10:00'),
                        'TripSegments'    => [
                            [
                                'DepDate'      => $this->clipSecondsFromTimeStamp(strtotime('2030-01-01 10:00')),
                                'DepCode'      => TRIP_CODE_UNKNOWN,
                                'DepName'      => 'JF Kennedy Airport',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime('2030-01-01 13:55')),
                                'ArrCode'      => TRIP_CODE_UNKNOWN,
                                'ArrName'      => 'Los Angeles International Airport',
                                'FlightNumber' => 'DL223',
                            ],
                        ],
                    ],
                ];

            case 'trip.emptycodes':
                return [
                    [
                        'Kind'            => 'T',
                        'RecordLocator'   => 'TESTCN',
                        'ReservationDate' => time(),
                        'TripSegments'    => [
                            [
                                'AirlineName'  => 'Test Airlines',
                                'FlightNumber' => 'TE223',
                                'DepDate'      => $this->clipSecondsFromTimeStamp(time() + SECONDS_PER_DAY * 7),
                                'DepCode'      => null,
                                'DepName'      => 'JF Kennedy Airport',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(time() + SECONDS_PER_DAY * 7 + 235 * 60),
                                'ArrCode'      => '',
                                'ArrName'      => 'Los Angeles International Airport',
                            ],
                        ],
                    ],
                ];

            case 'trip.badcodes':
                return [
                    [
                        'Kind'            => 'T',
                        'RecordLocator'   => 'TESTCN',
                        'ReservationDate' => time(),
                        'TripSegments'    => [
                            [
                                'AirlineName'  => 'Test Airlines',
                                'FlightNumber' => 'TE223',
                                'DepDate'      => $this->clipSecondsFromTimeStamp(time() + SECONDS_PER_DAY * 7),
                                'DepCode'      => 'BadLongCode',
                                'DepName'      => 'JF Kennedy Airport',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(time() + SECONDS_PER_DAY * 7 + 235 * 60),
                                'ArrCode'      => 'TooLong',
                                'ArrName'      => 'Los Angeles International Airport',
                            ],
                        ],
                    ],
                ];

            case 'future.trip.round':
                return [
                    [
                        'Kind'           => 'T',
                        'RecordLocator'  => 'X8383S',
                        'Passengers'     => 'Mr STEPHANE CHARBONNEAU',
                        'AccountNumbers' => '2024663055',
                        'Currency'       => null,
                        'TotalCharge'    => null,
                        'TripSegments'   =>
                        [
                            0 =>
                            [
                                'DepCode'      => 'TXL',
                                'DepName'      => 'Tegel Airport',
                                'ArrCode'      => 'CDG',
                                'ArrName'      => 'Charles De Gaulle Airport',
                                'DepDate'      => $this->clipSecondsFromTimeStamp(strtotime('2030-01-01 10:00')),
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime('2030-01-01 11:50')),
                                'Duration'     => null,
                                'Cabin'        => 'Economy',
                                'Meal'         => 'Snack, sandwich and/or meal',
                                'FlightNumber' => 'TE223',
                                'Seats'        => 'A4, A5',
                            ],
                            1 =>
                            [
                                'DepCode'      => 'TLS',
                                'DepName'      => 'Blagnac',
                                'ArrCode'      => 'TXL',
                                'ArrName'      => 'Tegel Airport',
                                'DepDate'      => $this->clipSecondsFromTimeStamp(strtotime('2030-01-01 12:20')),
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime('2030-01-01 14:20')),
                                'Duration'     => '2h15m',
                                'Cabin'        => 'Economy',
                                'Meal'         => 'Snack, sandwich and/or meal',
                                'FlightNumber' => 'TE223',
                                'Seats'        => 'C12, C15',
                            ],
                        ],
                    ],
                ];

            case "future.rental":
                return [
                    [
                        'Kind'            => 'L',
                        'Number'          => '123456789',
                        'PickupDatetime'  => $this->clipSecondsFromTimeStamp(strtotime('2030-01-01 12:20')),
                        'PickupLocation'  => 'PHL',
                        'DropoffDatetime' => $this->clipSecondsFromTimeStamp(strtotime('2030-01-02 12:20')),
                        'DropoffLocation' => 'PHL',
                        'PickupPhone'     => '122-236-785',
                        'PickupFax'       => '+122-236-785',
                        'PickupHours'     => '11 a.m.',
                        'DropoffHours'    => '3 p.m.',
                        'CarType'         => 'Mid-Size Economy',
                        'CarModel'        => 'Opel',
                        'RenterName'      => 'Den Matson',
                        'TotalCharge'     => 158.30,
                        'Currency'        => 'USD',
                        'TotalTaxAmount'  => 21.70,
                        'RentalCompany'   => 'Hertz',
                        'Discount'        => 0.08,
                        'Discounts'       => [
                            ["Code" => "AAA SOUTHERN NEW ENGLAND", "Name" => "Your Rate has been discounted based on the Hertz CDP provided"],
                            ["Code" => "Discount code 2", "Name" => "Discount name 2"],
                        ],
                    ],
                ];

            case "future.rental.new":
                $rental = new \AwardWallet\MainBundle\Service\Itinerary\CarRental($this->logger);
                $rental->setConfirmationNumber('123456789');
                $rental->setProviderName('Hertz');
                $rental->setPickupLocalDateTime(strtotime('2030-01-01 12:20'));
                $rental->setPickupAddress('PHL');
                $rental->setPickupPhone('122-236-785');
                $rental->setPickupFax('+122-236-785');
                $rental->setPickupOpeningHours('11 a.m.');
                $rental->setDropoffLocalDateTime(strtotime('2030-01-02 12:20'));
                $rental->setDropoffAddress('PHL');
                $rental->setDropoffOpeningHours('3 p.m.');
                $rental->setCarType('Mid-Size Economy');
                $rental->setCarModel('Opel');
                $rental->setDriverName('Den Matson');
                $rental->setTotal(158.30);
                $rental->setCurrencyCode('USD');
                $rental->setTax(21.70);

                return [$rental->convertToOldArrayFormat()];

            case "future.rental.badphoneandfax":
                return [
                    [
                        'Kind'            => 'L',
                        'Number'          => '123456789',
                        'PickupDatetime'  => $this->clipSecondsFromTimeStamp(floor(1402714082 / 60) * 60),
                        'PickupLocation'  => 'PHL',
                        'DropoffDatetime' => $this->clipSecondsFromTimeStamp(floor(1402886882 / 60) * 60),
                        'DropoffLocation' => 'PHL',
                        'PickupPhone'     => 'abcd',  // this bad phone should be filtered out in fixItineraries
                        'PickupFax'       => '12:40am',  // this bad phone should be filtered out in fixItineraries
                        'DropoffPhone'    => '12.10.2014',  // this bad phone should be filtered out in fixItineraries
                        'DropoffFax'      => 'efgh klmno',  // this bad phone should be filtered out in fixItineraries
                        'PickupHours'     => '11 a.m.',
                        'DropoffHours'    => '3 p.m.',
                        'CarType'         => 'Mid-Size Economy',
                        'CarModel'        => 'Opel',
                        'RenterName'      => 'Den Matson',
                        'TotalCharge'     => 158.30,
                        'Currency'        => 'USD',
                        'TotalTaxAmount'  => 21.70,
                        'RentalCompany'   => 'Hertz',
                    ],
                ];

            case "future.restaurant.new":
                $event = new AwardWallet\MainBundle\Service\Itinerary\Event($this->logger);
                $event->setConfirmationNumber('652342431');
                $event->setEventName('Kentucky Fried Chicken');
                $event->setStartDateTime(strtotime('12 may 2030, 12:00'));
                $event->setEndDateTime(strtotime('12 may 2030, 14:00'));
                $event->setAddressText('123 Street, Paris');
                $event->setPhone('122-236-785');
                $event->setGuests(['Den Matson']);
                $event->setGuestCount(2);
                $event->setTotal(158.30);
                $event->setCurrencyCode('USD');
                $event->setTax(21.70);

                return [$event->convertToOldArrayFormat()];

            case "future.restaurant":
                return [
                    [
                        'Kind'        => 'E',
                        'ConfNo'      => '123456789',
                        'Name'        => 'Kentucky Fried Chicken',
                        'StartDate'   => $this->clipSecondsFromTimeStamp(strtotime('12 may 2030, 12:00')),
                        'EndDate'     => $this->clipSecondsFromTimeStamp(strtotime('12 may 2030, 14:00')),
                        'Address'     => '123, Street',
                        'Phone'       => '122-236-785',
                        'DinerName'   => 'Den Matson',
                        'Guests'      => 2,
                        'TotalCharge' => 158.30,
                        'Currency'    => 'USD',
                        'Tax'         => 21.70,
                        'EventType'   => EVENT_RESTAURANT,
                    ],
                ];

            case "future.reservation.new":
                $hotelReservation = new AwardWallet\MainBundle\Service\Itinerary\HotelReservation($this->logger);
                $hotelReservation->setConfirmationNumber('42134242342');
                $hotelReservation->setHotelName('Sheraton Philadelphia Downtown Hotel');
                $hotelReservation->setCheckInDate(strtotime('2030-01-01 12:20'));
                $hotelReservation->setCheckOutDate(strtotime('2030-01-02 12:20'));
                $hotelReservation->setAddressText('123, Street, New York, USA');
                $hotelReservation->setPhone('123-745-856');
                $hotelReservation->setGuestCount(3);
                $hotelReservation->setGuests(['John', 'Marta', 'Stacey']);
                $hotelReservation->setKidsCount(0);
                $hotelReservation->setRoomsCount(2);
                // ??? 'Rate' => '9600 starpoints and USD 180.00'
                // ??? 'RateType' => 'Spg Cash & Points Only To Be Booked With A Spg Award Stay. Guest Must Be A Spg.member. Must Redeem Starpoints For Cash And Points Award.'
                $hotelReservation->setCancellationPolicy('No cancellation');
                // ??? 'RoomTypeDescription' => 'Non-Smoking Room Confirmed'
                $hotelReservation->setCost(280.60);
                $hotelReservation->setTax(35.10);
                $hotelReservation->setTotal(315.70);
                $hotelReservation->setCurrencyCode('USD');
                // ??? 'ExtProperties' => ["Property1" => "Value1", "Property2" => "Value2"]
                return [$hotelReservation->convertToOldArrayFormat()];

            case "future.reservation":
                return [
                    [
                        'Kind'                => 'R',
                        'ConfirmationNumber'  => '1252463788',
                        'HotelName'           => 'Sheraton Philadelphia Downtown Hotel',
                        'CheckInDate'         => $this->clipSecondsFromTimeStamp(strtotime('2030-01-01 12:20')),
                        'CheckOutDate'        => $this->clipSecondsFromTimeStamp(strtotime('2030-01-02 12:20')),
                        'Address'             => '123, Street',
                        'Phone'               => '123-745-856',
                        'Guests'              => 3,
                        'GuestNames'          => ['John', 'Marta', 'Stacey'],
                        'Kids'                => 0,
                        'Rooms'               => 2,
                        'Rate'                => '9600 starpoints and USD 180.00',
                        'RateType'            => 'Spg Cash & Points Only To Be Booked With A Spg Award Stay. Guest Must Be A Spg.member. Must Redeem Starpoints For Cash And Points Award.',
                        'CancellationPolicy'  => '',
                        'RoomType'            => 'Superior Romantic Glimmering Room - Earlybird',
                        'RoomTypeDescription' => 'Non-Smoking Room Confirmed',
                        'Cost'                => 280.60,
                        'Taxes'               => 35.10,
                        'Total'               => 315.70,
                        'Currency'            => 'USD',
                        'ExtProperties'       => ["Property1" => "Value1", "Property2" => "Value2"],
                    ],
                ];

            case "future.reservation.badphoneandfax":
                return [
                    [
                        'Kind'                => 'R',
                        'ConfirmationNumber'  => '1252463788',
                        'HotelName'           => 'Sheraton Philadelphia Downtown Hotel',
                        'CheckInDate'         => $this->clipSecondsFromTimeStamp(strtotime(date("Y-m-d", 1402886882))),
                        'CheckOutDate'        => $this->clipSecondsFromTimeStamp(strtotime(date("Y-m-d", 1403232482))),
                        'Address'             => '123, Street',
                        'Phone'               => 'abcd',
                        'Fax'                 => 'efgh',
                        'Guests'              => 3,
                        'GuestNames'          => ['John', 'Marta', 'Stacey'],
                        'Kids'                => 0,
                        'Rooms'               => 2,
                        'Rate'                => '9600 starpoints and USD 180.00',
                        'RateType'            => 'Spg Cash & Points Only To Be Booked With A Spg Award Stay. Guest Must Be A Spg.member. Must Redeem Starpoints For Cash And Points Award.',
                        'CancellationPolicy'  => '',
                        'RoomType'            => 'Superior Romantic Glimmering Room - Earlybird',
                        'RoomTypeDescription' => 'Non-Smoking Room Confirmed',
                        'Cost'                => 280.60,
                        'Taxes'               => 35.10,
                        'Total'               => 315.70,
                        'Currency'            => 'USD',
                    ],
                ];

            case "future.trip.and.reservation":
                return [
                    [
                        'Kind'            => 'T',
                        'RecordLocator'   => 'TEST001',
                        'Passengers'      => 'John Smith',
                        'TotalCharge'     => 100,
                        'Tax'             => 7,
                        'Currency'        => 'USD',
                        'ReservationDate' => strtotime("2013-08-01"),
                        'TripSegments'    => [
                            [
                                'AirlineName'  => 'Beta Alpha Test Airlines',
                                'Duration'     => '2:00',
                                'DepDate'      => $this->clipSecondsFromTimeStamp(strtotime("2037-08-01 7:00")),
                                'DepCode'      => 'JFK',
                                'DepName'      => 'JF Kennedy Airport',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime("2037-08-01 11:00")),
                                'ArrCode'      => 'LAX',
                                'ArrName'      => 'Los Angeles International Airport',
                                'FlightNumber' => 'TE223',
                                'Seats'        => '23',
                            ],
                        ],
                    ],
                    [
                        'Kind'                => 'R',
                        'ConfirmationNumber'  => '1252463788',
                        'HotelName'           => 'Test Hotel',
                        'CheckInDate'         => $this->clipSecondsFromTimeStamp(strtotime("2037-08-01 17:00")),
                        'CheckOutDate'        => $this->clipSecondsFromTimeStamp(strtotime("2037-08-03 10:00")),
                        'Address'             => 'Los Angeles',
                        'Phone'               => '123-745-856',
                        'Guests'              => 3,
                        'Kids'                => 0,
                        'Rooms'               => 2,
                        'Rate'                => '9600 starpoints and USD 180.00',
                        'RateType'            => 'Spg Cash & Points Only To Be Booked With A Spg Award Stay. Guest Must Be A Spg.member. Must Redeem Starpoints For Cash And Points Award.',
                        'CancellationPolicy'  => '',
                        'RoomType'            => 'Superior Romantic Glimmering Room - Earlybird',
                        'RoomTypeDescription' => 'Non-Smoking Room Confirmed',
                        'Cost'                => 280.60,
                        'Taxes'               => 35.10,
                        'Total'               => 315.70,
                        'Currency'            => 'USD',
                    ],
                ];

                break;

            case 'future.trip.and.train.one.confNo':
                return [
                    [
                        'Kind'           => 'T',
                        'RecordLocator'  => 'WEQQFN',
                        'AccountNumbers' =>
                            [
                                '992003449797582',
                            ],
                        'TotalCharge'    => 562.13,
                        'Currency'       => 'EUR',
                        'TicketNumbers'  =>
                            [
                                '220-1409599139',
                            ],
                        'Passengers'     =>
                            [
                                'Mr John Smith',
                            ],
                        'TripSegments'   =>
                            [
                                [
                                    'AirlineName'       => 'LH',
                                    'Aircraft'          => 'Boeing 747-400',
                                    'ArrCode'           => 'BOS',
                                    'ArrDate'           => $this->clipSecondsFromTimeStamp(strtotime('19 Mar ' . (date('Y') + 1) . ', 14:20')),
                                    'ArrivalTerminal'   => 'E',
                                    'Cabin'             => 'Economy',
                                    'DepartureTerminal' => '1',
                                    'DepCode'           => 'FRA',
                                    'DepDate'           => $this->clipSecondsFromTimeStamp(strtotime('19 Mar ' . (date('Y') + 1) . ', 10:55')),
                                    'Duration'          => '8 h 25 min',
                                    'FlightNumber'      => '422',
                                    'Stops'             => 0,
                                    'Seats'             => null,
                                ],
                                [
                                    'AirlineName'       => 'LH',
                                    'Aircraft'          => 'Airbus Industrie A330-300',
                                    'ArrCode'           => 'FRA',
                                    'ArrDate'           => $this->clipSecondsFromTimeStamp(strtotime('19 Sep ' . (date('Y') + 1) . ', 11:15')),
                                    'ArrivalTerminal'   => '1',
                                    'Cabin'             => 'Economy',
                                    'DepartureTerminal' => 'E',
                                    'DepCode'           => 'BOS',
                                    'DepDate'           => $this->clipSecondsFromTimeStamp(strtotime('19 Sep ' . (date('Y') + 1) . ', 22:05')),
                                    'Duration'          => '7 h 10 min',
                                    'FlightNumber'      => '421',
                                    'Stops'             => 0,
                                    'Seats'             => null,
                                ],
                            ],
                    ],
                    [
                        'Kind'           => 'T',
                        'TripCategory'   => TRIP_CATEGORY_TRAIN,
                        'RecordLocator'  => 'WEQQFN',
                        'AccountNumbers' =>
                            [
                                '992003449797582',
                            ],
                        'TotalCharge'    => 562.13,
                        'Currency'       => 'EUR',
                        'TicketNumbers'  =>
                            [
                                '220-1409599139',
                            ],
                        'Passengers'     =>
                            [
                                'Mr John Smith',
                            ],
                        'TripSegments'  =>
                            [
                                [
                                    'AirlineName'       => 'LH',
                                    'ArrCode'           => 'FRA',
                                    'ArrDate'           => $this->clipSecondsFromTimeStamp(strtotime('19 Mar ' . (date('Y') + 1) . ', 08:33')),
                                    'ArrivalTerminal'   => 'TN',
                                    'Cabin'             => 'Economy',
                                    'DepartureTerminal' => null,
                                    'DepCode'           => 'QDU',
                                    'DepDate'           => $this->clipSecondsFromTimeStamp(strtotime('19 Mar ' . (date('Y') + 1) . ', 07:21')),
                                    'Duration'          => '1 h 12 min',
                                    'FlightNumber'      => '3503',
                                    'Stops'             => 0,
                                    'Seats'             => null,
                                ],
                                [
                                    'AirlineName'       => 'LH',
                                    'ArrCode'           => 'QDU',
                                    'ArrDate'           => $this->clipSecondsFromTimeStamp(strtotime('19 Sep ' . (date('Y') + 1) . ', 14:36')),
                                    'ArrivalTerminal'   => null,
                                    'Cabin'             => 'Economy',
                                    'DepartureTerminal' => 'TN',
                                    'DepCode'           => 'FRA',
                                    'DepDate'           => $this->clipSecondsFromTimeStamp(strtotime('19 Sep ' . (date('Y') + 1) . ', 13:25')),
                                    'Duration'          => '1 h 11 min',
                                    'FlightNumber'      => '3512',
                                    'Stops'             => 0,
                                    'Seats'             => null,
                                ],
                            ],
                    ],
                ];

                break;

            case 'trip.noconf.allowed':
            case 'trip.noconf':
                $result = [
                    [
                        'Kind'            => 'T',
                        'TotalCharge'     => 100,
                        'Tax'             => 7,
                        'Currency'        => 'USD',
                        'ReservationDate' => time(),
                        'TripSegments'    => [
                            [
                                'AirlineName'  => 'Test Airlines',
                                'Duration'     => '3:55',
                                'DepDate'      => $this->clipSecondsFromTimeStamp(strtotime("today") + intval($this->AccountFields['Pass']) * 60),
                                'DepCode'      => 'JFK',
                                'DepName'      => 'JF Kennedy Airport',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime("today") + 3600 + intval($this->AccountFields['Pass']) * 60),
                                'ArrCode'      => 'LAX',
                                'ArrName'      => 'Los Angeles International Airport',
                                'FlightNumber' => 'TE223',
                                'Seats'        => '23',
                            ],
                        ],
                    ],
                ];

                if ($this->AccountFields['Login'] == 'trip.noconf.allowed') {
                    $result[0]['RecordLocator'] = CONFNO_UNKNOWN;
                }

                return $result;

            case 'trip.today':
                return [
                    [
                        'Kind'            => 'T',
                        'RecordLocator'   => 'TEST001',
                        'TotalCharge'     => 100,
                        'Tax'             => 7,
                        'Currency'        => 'USD',
                        'SpentAwards'     => '100500 miles',
                        'EarnedAwards'    => '500 miles',
                        'ReservationDate' => time(),
                        'TripSegments'    => [
                            [
                                'AirlineName'  => 'Test Airlines',
                                'Duration'     => '3:55',
                                'DepDate'      => $this->clipSecondsFromTimeStamp(strtotime("today") + intval($this->AccountFields['Pass']) * 60),
                                'DepCode'      => 'JFK',
                                'DepName'      => 'JF Kennedy Airport',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime("today") + 3600 + intval($this->AccountFields['Pass']) * 60),
                                'ArrCode'      => 'LAX',
                                'ArrName'      => 'Los Angeles International Airport',
                                'FlightNumber' => 'TE223',
                                'Seats'        => '23',
                            ],
                        ],
                    ],
                ];

            case 'trip.long.time':
                return [
                    [
                        'Kind'            => 'T',
                        'RecordLocator'   => 'TESTLT',
                        'Passengers'      => 'John Smith',
                        'TotalCharge'     => 100,
                        'Tax'             => 7,
                        'Currency'        => 'USD',
                        'ReservationDate' => time() - 3600 * 300,
                        'TripSegments'    => [
                            0 =>
                            [
                                'AirlineName'  => 'Test Airlines',
                                'Duration'     => '2:15',
                                'DepDate'      => $this->clipSecondsFromTimeStamp(time() + SECONDS_PER_DAY * 3),
                                'DepCode'      => 'JFK',
                                'DepName'      => 'JF Kennedy Airport',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(time() + SECONDS_PER_DAY * 3 + 3600),
                                'ArrCode'      => 'LAX',
                                'ArrName'      => 'Los Angeles International Airport',
                                'FlightNumber' => 'TE223',
                                'Seats'        => '11',
                            ],
                            1 =>
                            [
                                'AirlineName'  => 'Test Airlines',
                                'Duration'     => '5:30',
                                'DepDate'      => $this->clipSecondsFromTimeStamp(time() + SECONDS_PER_DAY * 354),
                                'DepCode'      => 'LAX',
                                'DepName'      => 'Los Angeles International Airport',
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(time() - SECONDS_PER_DAY * 354 + 3600),
                                'ArrCode'      => 'MIA',
                                'ArrName'      => 'Miami',
                                'FlightNumber' => 'TE224',
                                'Seats'        => '14',
                            ],
                        ],
                    ],
                ];

            case 'trip.passengers':
                return [
                    [
                        'Kind'            => 'T',
                        'RecordLocator'   => 'AD79QH',
                        'ReservationDate' => strtotime('+1 day'),
                        'Status'          => 'Confirmed',
                        'Passengers'      => 'Ms. Dinissa Duvanova, Mr. Alexi Vereschaga, Ms. Paulina Vereschaga, Ms. Alexandra Vereschaga',
                        'AccountNumbers'  => '176606032, 116306385, 181182514, 181182061',
                        'TripSegments'    =>
                        [
                            0 =>
                            [
                                'AirlineName'  => 'American Airlines',
                                'FlightNumber' => '647',
                                'Duration'     => '2 h 55 m',
                                'DepCode'      => 'IAG',
                                'ArrCode'      => 'FLL',
                                'DepName'      => 'Niagara Falls, NY / Toronto, Canada AREA',
                                'ArrName'      => 'Fort Lauderdale, FL / Miami, FL AREA',
                                'DepDate'      => $this->clipSecondsFromTimeStamp($this->clipSecondsFromTimeStamp(strtotime('+1 week') - 10 * 3600)),
                                'ArrDate'      => $this->clipSecondsFromTimeStamp(strtotime('+1 week') - 1 * 3600),
                            ],
                        ],
                        'TotalCharge' => '387.56',
                        'BaseFare'    => '356.00',
                        'Tax'         => '46.12',
                    ],
                    [
                        'Kind'            => 'L',
                        'Number'          => '123456789',
                        'RentalCompany'   => 'Hertz',
                        'PickupDatetime'  => $this->clipSecondsFromTimeStamp(strtotime('+1 week')),
                        'PickupLocation'  => 'PHL',
                        'DropoffDatetime' => $this->clipSecondsFromTimeStamp(strtotime('+1 week') + SECONDS_PER_DAY),
                        'DropoffLocation' => 'PHL',
                        'PickupPhone'     => '122-236-785',
                        'PickupHours'     => '11 a.m.',
                        'DropoffHours'    => '3 p.m.',
                        'CarModel'        => 'Opel',
                        'RenterName'      => 'Den Matson',
                        'TotalCharge'     => 158.30,
                        'Currency'        => 'USD',
                        'TotalTaxAmount'  => 21.70,
                        'Passengers'      => 'Ms. Dinissa Duvanova, Mr. Alexi Vereschaga, Ms. Paulina Vereschaga, Ms. Alexandra Vereschaga',
                    ],
                    [
                        'Kind'                => 'R',
                        'ConfirmationNumber'  => '1252463788',
                        'Address'             => '123, Leinin st., Fort Lauderdale, FL / Miami, FL AREA',
                        'HotelName'           => 'Sheraton Philadelphia Downtown Hotel',
                        'CheckInDate'         => $this->clipSecondsFromTimeStamp(strtotime('+1 week') + 3 * 3600),
                        'CheckOutDate'        => $this->clipSecondsFromTimeStamp(strtotime('+2 week')),
                        'Phone'               => '123-745-856',
                        'Guests'              => 3,
                        'Kids'                => 0,
                        'Rooms'               => 2,
                        'Rate'                => '9600 starpoints and USD 180.00',
                        'RateType'            => 'Spg Cash & Points Only To Be Booked With A Spg Award Stay. Guest Must Be A Spg.member. Must Redeem Starpoints For Cash And Points Award.',
                        'CancellationPolicy'  => '',
                        'RoomType'            => 'Superior Romantic Glimmering Room - Earlybird',
                        'RoomTypeDescription' => 'Non-Smoking Room Confirmed',
                        'Cost'                => 280.60,
                        'Taxes'               => 35.10,
                        'Total'               => 315.70,
                        'Currency'            => 'USD',
                        'GuestNames'          => 'Ms. Dinissa Duvanova, Mr. Alexi Vereschaga, Ms. Paulina Vereschaga, Ms. Alexandra Vereschaga',
                    ],
                ];

            case 'reservation.noconf':
                return [
                    [
                        "Kind"               => "R",
                        "HotelName"          => "Redmond Marriott Town Center",
                        "ConfirmationNumber" => CONFNO_UNKNOWN,
                        "Address"            => "7401 164th Avenue NE, Redmond, WA 98052",
                        "Phone"              => "+1 425 498 4000",
                        'CheckInDate'        => $this->clipSecondsFromTimeStamp(strtotime('+1 week')),
                        'CheckOutDate'       => $this->clipSecondsFromTimeStamp(strtotime('+1 week') + SECONDS_PER_DAY),
                        "GuestNames"         => "",
                        "Guests"             => 0,
                    ],
                    [
                        "Kind"               => "R",
                        "HotelName"          => "PHOENIX-GLENDALE Holiday Inn",
                        "ConfirmationNumber" => CONFNO_UNKNOWN,
                        "Address"            => "9310 W CABELA DRIVE GLENDALE AZ 85305",
                        "Phone"              => "+1 425 498 4000",
                        'CheckInDate'        => $this->clipSecondsFromTimeStamp(strtotime('+1 week')),
                        'CheckOutDate'       => $this->clipSecondsFromTimeStamp(strtotime('+1 week') + SECONDS_PER_DAY),
                        "GuestNames"         => "Russell Sharp",
                        "Guests"             => 1,
                    ],
                ];

            case 'rental.noconf':
                return [
                    [
                        "Kind"            => "L",
                        "RentalCompany"   => "NATIONAL CAR RENTAL",
                        'Number'          => CONFNO_UNKNOWN,
                        "PickupLocation"  => "SANTA BARBARA",
                        "PickupPhone"     => "+1 425 498 4000",
                        'PickupDatetime'  => $this->clipSecondsFromTimeStamp(strtotime('+8 day')),
                        'DropoffDatetime' => $this->clipSecondsFromTimeStamp(strtotime('+8 day') + SECONDS_PER_DAY),
                        "DropoffLocation" => "LOS ANGELES",
                        "DropoffPhone"    => "",
                        "RenterName"      => "Russell Sharp",
                        "CarModel"        => "FULL SZ AUTO AC",
                        "TotalCharge"     => "84.17",
                        "Currency"        => "UNL",
                    ],
                    [
                        "Kind"            => "L",
                        "RentalCompany"   => "Hertz",
                        "Number"          => CONFNO_UNKNOWN,
                        "PickupLocation"  => "TORONTO ON",
                        "PickupPhone"     => "+1 425 498 4000",
                        'PickupDatetime'  => $this->clipSecondsFromTimeStamp(strtotime('+1 week')),
                        'DropoffDatetime' => $this->clipSecondsFromTimeStamp(strtotime('+1 week') + SECONDS_PER_DAY),
                        "DropoffLocation" => "TORONTO ON",
                        "DropoffPhone"    => "",
                        "RenterName"      => "Russell Sharp",
                        "CarModel"        => "FULL SZ AUTO AC",
                    ],
                ];

            case 'restaurant.noconf':
                return [
                    [
                        "Kind"      => "E",
                        "Name"      => "Table for two",
                        "ConfNo"    => CONFNO_UNKNOWN,
                        "Address"   => "7401 164th Avenue NE, Redmond, WA 98052",
                        "Phone"     => "+1 025 123 5600",
                        'StartDate' => $this->clipSecondsFromTimeStamp(strtotime('+1 week')),
                        'EndDate'   => $this->clipSecondsFromTimeStamp(strtotime('+1 week') + SECONDS_PER_DAY),
                        "DinerName" => "Lisa Sharp",
                        "Guests"    => 5,
                    ],
                    [
                        "Kind"        => "E",
                        "Name"        => "Table for three",
                        "ConfNo"      => CONFNO_UNKNOWN,
                        "Address"     => "9310 W CABELA DRIVE GLENDALE AZ 85305",
                        "Phone"       => "+1 925 321 0099",
                        'StartDate'   => $this->clipSecondsFromTimeStamp(strtotime('+1 week')),
                        'EndDate'     => $this->clipSecondsFromTimeStamp(strtotime('+1 week') + SECONDS_PER_DAY),
                        "DinerName"   => "John Sharp",
                        "Guests"      => 2,
                        "Currency"    => "USD",
                        "TotalCharge" => "95.44",
                    ],
                ];
        }
        }

        return [];
    }

    public function GetConfirmationFields()
    {
        return [
            "ConfNo" => [
                "Caption"         => "Confirmation #",
                "Type"            => "string",
                "Size"            => 40,
                "Required"        => true,
                "Options"         => $this->getLoginOptions(),
                "InputAttributes" => ' style="width:144px;" ',
            ],
            "LastName" => [
                "Type"            => "string",
                "Size"            => 40,
                "Value"           => $this->GetUserField('LastName'),
                "InputAttributes" => ' style="width:144px;" ',
                "Required"        => true,
            ],
        ];
    }

    public function GetHistoryColumns()
    {
        return [
            "No."           => "Info",
            "Activity Date" => "PostingDate",
            "Activity"      => "Info",
            "Description"   => "Description",
            "Award Miles"   => "Miles",
        ];
    }

    public function ParseHistory($startDate = null)
    {
        $result = [];

        if ($this->AccountFields['Login'] == 'history' || $this->AccountFields['Login'] == 'trip.long.time') {
            for ($n = 0; $n < 10; $n++) {
                $date = strtotime("2012-01-01") + SECONDS_PER_DAY * $n;

                if (!isset($startDate) || $date >= $startDate) {
                    $result[] = [
                        "No."           => $n,
                        "Activity Date" => $date,
                        "Activity"      => "Activity $n",
                        "Description"   => "Description $n",
                        "Award Miles"   => $n * 100,
                    ];
                }
            }
        }

        return $result;
    }

    public function CheckConfirmationNumberInternal($arFields, &$it)
    {
        $this->AccountFields['Login'] = $arFields['ConfNo'];

        if ($arFields['LastName'] == 'invalid') {
            return 'Invalid Last Name';
        }
        $it = $this->ParseItineraries();

        return null;
    }

    public function ParseFiles($filesStartDate)
    {
        $file = tempnam(sys_get_temp_dir(), 'pdf');
        copy(__DIR__ . '/test.pdf', $file);

        return [
            [
                "FileDate"      => time(),
                "Name"          => 'Some test file',
                "Extension"     => "pdf",
                "AccountNumber" => '111222',
                "AccountName"   => 'Test Statement',
                "AccountType"   => 'credit',
                "Contents"      => $file,
            ],
        ];
    }

    public function getRewardsTransferRates()
    {
        return $this->fakeRewardsTransfers;
    }

    public function MarkCoupon(array $ids)
    {
        $return = [];

        foreach ($ids as $k => $v) {
            $return[$k] = true;
        }

        return $return;
    }

    protected function getLoginOptions()
    {
        $options = [
            "antigate"                                => "Antigate Balance",
            "rucaptcha"                               => "ruCaptcha Balance",
            "deathbycaptcha"                          => "Deathbycaptcha Balance",
            "expiration.never"                        => "Never Expire (Balance: 1500)",
            "expiration.on"                           => "Expire on " . (date("m/d/Y", strtotime("+1 year +6 month"))) . " (Balance: 1800)",
            "expiration.past"                         => "Expire on 01/01/2012 (Balance: 2500)",
            "expiration.close"                        => "Expire on " . (date("m/d/Y", strtotime("+7 day"))) . " (Balance: 3000)",
            'expiration.doNotExpireEliteStatus'       => 'AccountExpirationWarning=do not expire with elite status; Expire on' . date('m/d/Y', strtotime('+1 month')),
            "expiration.empty"                        => "No expiration",
            "expiration.fromsub"                      => "Expiration from subaccount",
            "expiration.balance_negative_with_sub1"   => "Negative main balance with subaccount(1000, +expiration)",
            "expiration.clear"                        => "Clear Expiration",
            "question"                                => "Ask security question",
            "question.multi"                          => "Mark two security questions invalid out of three",
            "delay"                                   => "Delay 30 or Password seconds",
            "exception.browser"                       => "Throw browser exception",
            "balance.random"                          => "Random balance",
            "balance.random-status"                   => "Random balance and status",
            "balance.increase"                        => "Increasing balance. From rand(1, 100) by rand(1, 100) each time",
            "balance.decrease"                        => "Decreasing balance. From 1 000 000 + rand(1, 100) by rand(1, 100) each time",
            "lastActivity.random"                     => "Random Last Activity",
            "balance.point"                           => "Random balance with a point",
            "balance.comma"                           => "Random balance with a comma",
            "error"                                   => "Random error",
            "unknown.error"                           => "Unknown error",
            "invalid.logon"                           => "Invalid logon",
            "provider.error"                          => "Provider error",
            "lockout"                                 => "Lockout",
            "warning.with.message"                    => 'State ACCOUNT_WARNING with message',
            "warning.without.message"                 => "State ACCOUNT_WARNING without message",
            "past.future.trip"                        => "Trip with past, current and future segments",
            "future.trip"                             => "Future trip and PendingUpgradeTo / TripNumber",
            "future.trip.round"                       => "Future round trips",
            'future.trip.random.seats'                => 'Future trip, random seats(TravelPlanDiff)',
            'future.trip.random.props'                => 'Future trip, random properties',
            'future.trip.random.props.not_white_list' => 'Future trip, random properties (not whitelisted)',
            "future.rental"                           => "Future rental",
            "future.rental.new"                       => "Future rental (new format)",
            "future.rental.badphoneandfax"            => "Future rental with bad phone and fax",
            "future.reservation"                      => "Future reservation",
            "future.reservation.new"                  => "Future reservation (new format)",
            "future.reservation.badphoneandfax"       => "Future reservation with bad phone and fax",
            "future.restaurant"                       => "Future restaurant",
            "future.restaurant.new"                   => "Future restaurant (new format)",
            "future.trip.and.reservation"             => "Future trip and reservation",
            "future.trip.and.train.one.confNo"        => "Future trip and train with one Conf #",
            "subaccount_expired"                      => "1 subaccount with expiration date",
            "subaccount_expired_combined"             => "1 subaccount with  expiration date combined with main account",
            "1.subaccount"                            => "1 subaccount",
            "2.subaccounts"                           => "2 subaccounts (changing the subaccount balance, random number of detected cards)",
            "3.subaccounts"                           => "2 subaccounts (one const)",
            "old.n-a.balance"                         => "Balance -1 (old n/a)",
            "new.n-a.balance"                         => "Balance null (new n/a)",
            "sub.n-a.balance"                         => "SubAccount with Balance null",
            "sub.chase"                               => "Chase SubAccount",
            "sub.chase.freedom"                       => "Chase SubAccount Freedom",
            "sub.coupon"                              => "Coupon subaccounts",
            "sub.expired"                             => "Expired SubAccounts",
            "sub.delta"                               => "SubAccount with Delta card",
            "sub.cvs"                                 => "SubAccount with Exp dates",
            "security.question"                       => "Security questions",
            "itmaster.20ta20c20r20l20e"               => "Retrieve 2 itineraries",
            "itmaster.11ta11c11r11l11e"               => "Retrieve 1 and 1 itineraries",
            'itmaster.10ta10tc10tt10tb10tf10r10l10e'  => "1 itinerary of each type and kind",
            "itmaster.no.trle"                        => "Retrieve noItineraries all kinds",
            "history"                                 => "Return history, 10 rows",
            "throttle"                                => "Throttle. Make 10 requests to wsdl.awardwallet.local",
            "pass2name"                               => "Copy password to Name. for debug proxy tests",
            "trip.noconf"                             => "Trip without ConfNo",
            "trip.noconf.allowed"                     => "Trip without ConfNo, allowed",
            "trip.today"                              => "Trip starting today 00:00",
            "trip.long.time"                          => "Trip with long pauses between segments should raise exception",
            "trip.nocodes"                            => "Trip without airport codes",
            "trip.emptycodes"                         => "Trip with empty airport codes",
            "trip.badcodes"                           => "Trip with too long airport codes",
            "trip.overnight"                          => "Trip overnight - incorretly parsed overnight flight",
            "reservation.noconf"                      => "Reservation without ConfNo, allowed",
            "rental.noconf"                           => "Rental without ConfNo, allowed",
            "restaurant.noconf"                       => "Restaurant without ConfNo, allowed",
            "phantom.sleep"                           => "Phantom, sleep(60)",
            "elite.complex"                           => "Elite, complex",
            "elite.complex1"                          => "Elite, complex 1",
            "elite.complex2"                          => "Elite, complex 2",
            "elite.complex3"                          => "Elite, complex 3",
            "elite.complex4"                          => "Elite, complex 4",
            "elite.complex5"                          => "Elite, complex 5",
            "subaccount.to.delta"                     => "transfer balance from subaccount to delta",
            "cancel.check"                            => "cancel check (wsdl)",
            "trip.passengers"                         => "Trip with multiple passengers",
            "facebook.groupon"                        => "Facebook (Groupon)",
            "facebook.groupon.uk"                     => "Facebook (Groupon UK)",
            "notifications"                           => "sendNotification",
            "memory"                                  => "Test memory usage",
            "testprovidergroup"                       => "Check group of providers",
            "selenium.question"                       => "Selenium. Ask security question",
            "selenium.versions.40"                    => "Selenium. Firefox 40",
            "selenium.versions.45"                    => "Selenium. Firefox 45",
            "selenium.versions.53"                    => "Selenium. Firefox 53",
            "selenium.versions.Chromium"              => "Selenium. Chromium",
            "selenium.versions.logs"                  => "Selenium. New account check logs concept - refs #11928",
        ];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_SELF));

        foreach ($iterator as $file) {
            /** @var $file RecursiveDirectoryIterator */
            if (empty($file->getSubPath())) {
                continue;
            }

            if (strtolower($file->getExtension()) == "php") {
                $key = str_replace(DIRECTORY_SEPARATOR, '.', str_replace(".php", "", $file->getSubPathname()));
                $options[$key] = $key;
            }
        }
        ksort($options, SORT_FLAG_CASE | SORT_STRING); // not working, why?

        return $options;
    }

    private function clipSecondsFromTimeStamp(int $timeStamp): int
    {
        return $timeStamp - $timeStamp % 60;
    }
}
