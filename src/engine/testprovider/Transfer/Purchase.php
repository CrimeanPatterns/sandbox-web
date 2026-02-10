<?php

namespace AwardWallet\Engine\testprovider\Transfer;

class Purchase extends \TAccountCheckerTestprovider
{
    /**
     * Login format
     * '$ [options]'
     * Options:
     * -s <t> 		- sleep t seconds, 1 <= t <= 60
     * -llfe		- fail LoadLoginForm
     * -le			- fail Login
     * -e			- fail purchaseMiles
     * -ce			- throw \CheckException with ACCOUNT_PROVIDER_ERROR code
     * -cep			- throw \CheckException with ACCOUNT_INVALID_PASSWORD code
     * -x			- test http and xpath
     * -cc			- use last 4 digits of cc as success message.
     */
    public const INVALID_LOGIN_MESSAGE = 'Invalid login format';
    public const DEFAULT_SUCCESS_MESSAGE = 'Test purchase success';
    public const DEFAULT_ERROR_MESSAGE = 'Test purchase failed';
    public const INVALID_PASSWORD_MESSAGE = 'Invalid password';
    public const XPATH_FAILED_MESSAGE = 'XPath failed';

    protected $loadLoginForm = true;
    protected $login = true;
    protected $purchase = true;

    public function InitBrowser()
    {
        $this->UseCurlBrowser();
        $this->KeepState = true;
    }

    public function LoadLoginForm()
    {
        if (array_key_exists($this->TransferFields['Login'], parent::getLoginOptions())) {
            return parent::LoadLoginForm();
        }

        $fields = $this->TransferFields;

        if (0 === strpos($fields['Login'], '$')) {
            $args = explode(' ', preg_replace('/\s+/', ' ', trim(substr($fields['Login'], 1))));

            while (0 < count($args)) {
                $option = array_shift($args);

                switch ($option) {
                    case '-s':
                        $arg = array_shift($args);

                        if (null === $arg || !is_numeric($arg) || $arg < 1 || $arg > 60) {
                            throw new \CheckException(self::INVALID_LOGIN_MESSAGE);
                        }
                        sleep($arg);

                        break;

                    case '-llfe':
                        $this->loadLoginForm = false;

                        break;

                    case '-le':
                        $this->login = false;

                        break;

                    case '-e':
                        $this->purchase = false;

                        break;

                    case '-ce':
                        throw new \CheckException(self::DEFAULT_ERROR_MESSAGE);

                        break;

                    case '-cep':
                        throw new \CheckException(self::INVALID_PASSWORD_MESSAGE, ACCOUNT_INVALID_PASSWORD);

                        break;

                    case '-cc':
                        $cc = $this->TransferFields['ccFull']['CardNumber'];
                        $this->ErrorMessage = substr($cc, strlen($cc) - 4);

                        break;

                    case '-x':
                        $this->http->GetURL('http://bash.org');

                        if (10 !== count($this->http->FindNodes('//td[@class="toplinks"]/a'))) {
                            throw new \CheckException(self::XPATH_FAILED_MESSAGE);
                        }

                        break;

                    default:
                        throw new \CheckException(self::INVALID_LOGIN_MESSAGE);

                        break;
                }
            }
        }

        return $this->loadLoginForm;
    }

    public function Login()
    {
        if (array_key_exists($this->TransferFields['Login'], parent::getLoginOptions())) {
            return parent::Login();
        }

        return $this->login;
    }

    public function purchaseMiles(array $fields, $numberOfMiles, $creditCard)
    {
        $this->ErrorMessage = self::DEFAULT_SUCCESS_MESSAGE;

        return $this->purchase;
    }

    public function getPurchaseMilesFields()
    {
        return [
            'Login' => [
                'Type'     => 'string',
                'Required' => true,
                'Caption'  => 'Login',
            ],
            'Password' => [
                'Type'     => 'string',
                'Required' => true,
                'Caption'  => 'Password',
            ],
            'Email' => [
                'Type'     => 'string',
                'Required' => true,
                'Caption'  => 'Email',
            ],
        ];
    }
}
