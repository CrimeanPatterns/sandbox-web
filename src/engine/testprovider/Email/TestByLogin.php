<?php

namespace AwardWallet\Engine\testprovider\Email;

class TestByLogin extends \TAccountChecker
{
    public const TEST_BY_LOGIN_FROM = 'testByLogin@test.awardwallet.com';

    public function detectEmailByHeaders(array $headers)
    {
        return !empty($headers['from']) && $headers['from'] == self::TEST_BY_LOGIN_FROM;
    }

    public function detectEmailFromProvider($from)
    {
        return false;
    }

    public function ParsePlanEmail(\PlancakeEmailParser $parser)
    {
        if ($parser->getHeader('from') == self::TEST_BY_LOGIN_FROM) {
            $login = $parser->getBody();
            $checker = GetAccountChecker('testprovider', true, ["Login" => $login]);
            $checker->InitBrowser();
            $checker->AccountFields = [
                'Login' => $login,
            ];
            $checker->Parse();

            return [
                "parsedData" => [
                    "Properties"  => $checker->Properties,
                    "Itineraries" => $checker->ParseItineraries(),
                ],
                "emailType" => "test" . $login,
            ];
        }

        return [];
    }
}
