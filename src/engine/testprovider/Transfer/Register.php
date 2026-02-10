<?php

namespace AwardWallet\Engine\testprovider\Transfer;

class Register extends \TAccountCheckerTestprovider
{
    /**
     * Login format
     * '$ [options]'
     * Options:
     * -m 			- use motto as success/error message
     * -n <field> 	- check if field is an empty string
     * -nn <field>	- check if field is not an empty string
     * -t <field> 	- check if field is true
     * -f <field> 	- check if field is false
     * -s <t> 		- sleep t seconds, 1 <= t <= 60
     * -e 			- return false
     * -ce			- throw \CheckException with ACCOUNT_PROVIDER_ERROR code
     * -cep			- throw \CheckException with ACCOUNT_INVALID_PASSWORD code
     * -x			- test http and xpath.
     */
    public const INVALID_LOGIN_MESSAGE = 'Invalid login format';
    public const INVALID_AGE_MESSAGE = 'Invalid age';
    public const DEFAULT_SUCCESS_MESSAGE = 'Test registration success';
    public const DEFAULT_ERROR_MESSAGE = 'Test registration failed';
    public const INVALID_PASSWORD_MESSAGE = 'Invalid password';
    public const CHECK_FAILED_MESSAGE = 'Fields check failed';
    public const XPATH_FAILED_MESSAGE = 'XPath failed';

    public function InitBrowser()
    {
        $this->UseCurlBrowser();
    }

    public function registerAccount(array $fields)
    {
        if (13 > $fields['Age'] || 80 < $fields['Age']) {
            throw new \CheckException(self::INVALID_AGE_MESSAGE);
        }
        $message = self::DEFAULT_SUCCESS_MESSAGE;
        $error = self::DEFAULT_ERROR_MESSAGE;

        if (0 === strpos($fields['Login'], '$')) {
            $args = explode(' ', preg_replace('/\s+/', ' ', trim(substr($fields['Login'], 1))));

            while (0 < count($args)) {
                $option = array_shift($args);

                switch ($option) {
                    case '-n':
                    case '-nn':
                    case '-t':
                    case '-f':
                        $arg = array_shift($args);

                        if (null === $arg || !isset($fields[$arg])) {
                            throw new \CheckException(self::INVALID_LOGIN_MESSAGE);
                        }

                        break;

                    case '-s':
                        $arg = array_shift($args);

                        if (null === $arg || !is_numeric($arg) || $arg < 1 || $arg > 60) {
                            throw new \CheckException(self::INVALID_LOGIN_MESSAGE);
                        }

                        break;
                }

                switch ($option) {
                    case '-m':
                        $message = $error = $fields['Motto'];

                        break;

                    case '-n':
                        if ('' !== $fields[$arg]) {
                            throw new \CheckException(self::CHECK_FAILED_MESSAGE);
                        }

                        break;

                    case '-nn':
                        if ('' === $fields[$arg]) {
                            throw new \CheckException(self::CHECK_FAILED_MESSAGE);
                        }

                        break;

                    case '-t':
                        if (true !== $fields[$arg]) {
                            throw new \CheckException(self::CHECK_FAILED_MESSAGE);
                        }

                        break;

                    case '-f':
                        if (false !== $fields[$arg]) {
                            throw new \CheckException(self::CHECK_FAILED_MESSAGE);
                        }

                        break;

                    case '-s':
                        sleep($arg);

                        break;

                    case '-e':
                        return false;

                        break;

                    case '-ce':
                        throw new \CheckException($error);

                        break;

                    case '-cep':
                        throw new \CheckException(self::INVALID_PASSWORD_MESSAGE, ACCOUNT_INVALID_PASSWORD);

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
        $this->ErrorMessage = $message;

        return true;
    }

    public function getRegisterFields()
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
            'Age' => [
                'Type'     => 'integer',
                'Required' => true,
                'Caption'  => 'Age, 13-80',
            ],
            'Offers' => [
                'Type'     => 'boolean',
                'Required' => true,
                'Caption'  => 'Subscribe to offers',
            ],
            'Promo' => [
                'Type'     => 'boolean',
                'Required' => true,
                'Caption'  => 'Subscribe to promo',
            ],
            'Kids' => [
                'Type'     => 'integer',
                'Required' => false,
                'Caption'  => 'Number of kids',
            ],
            'Motto' => [
                'Type'     => 'string',
                'Required' => true,
                'Caption'  => 'Motto',
            ],
            'Alias' => [
                'Type'     => 'string',
                'Required' => false,
                'Caption'  => 'Alias',
            ],
        ];
    }
}
