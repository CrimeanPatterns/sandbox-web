<?php

namespace AwardWallet\Engine\testprovider\Transfer;

class Transfer extends \TAccountChecker
{
    public function InitBrowser()
    {
        $this->UseCurlBrowser();
    }

    public function LoadLoginForm()
    {
        $this->http->Log('Load Login Form');

        return true;
    }

    public function Login()
    {
        $this->http->Log('Login');

        return true;
    }

    public function transferMiles($targetProviderCode, $targetAccountNumber, $numberOfMiles, $fields = [])
    {
        $this->http->Log('transfer ' . var_export([$targetProviderCode, $targetAccountNumber, $numberOfMiles], true));
        $this->ErrorMessage = sprintf('Test transfer: transferred %d miles from account %s to %s (%s)', $numberOfMiles, $this->AccountFields['Login'], $targetAccountNumber, $targetProviderCode);

        if (0 === strpos($this->AccountFields['Login'], '$')) {
            $args = explode(' ', preg_replace('/\s+/', ' ', trim(substr($this->AccountFields['Login'], 1))));

            while (0 < count($args)) {
                $option = array_shift($args);

                switch ($option) {
                    case '-s':
                        $arg = array_shift($args);

                        if (null === $arg || !is_numeric($arg) || $arg < 1 || $arg > 60) {
                            throw new \UserInputError('Invalid sleep time');
                        }
                        sleep($arg);

                        break;

                    case '-e':
                        return false;

                        break;

                    case '-ce':
                        throw new \CheckException('Test transfer failed');

                        break;

                    case '-cep':
                        throw new \CheckException('Test transfer: invalid credentials', ACCOUNT_INVALID_PASSWORD);

                        break;

                    default:
                        throw new \CheckException('Invalid login format');

                        break;
                }
            }
        }

        return true;
    }

    public function getTransferFields()
    {
        return [
            "FirstName" => [
                "Type"     => "string",
                "Required" => true,
                "Caption"  => "Last Name",
            ],
            "LastName" => [
                "Type"     => "string",
                "Required" => true,
                "Caption"  => "First Name",
            ],
        ];
    }
}
