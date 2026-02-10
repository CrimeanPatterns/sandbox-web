<?php

namespace AwardWallet\Engine\testprovider\Email;

class Itinerary1 extends \TAccountChecker
{
    public const TIMEOUT_MARKER = 'awardwallet test timeout';

    public const USEREMAIL_MARKER = 'awardwallet_unbelievable_email_address@awardwallet.com';

    public const SPENT_AWARDS_MARKER = 'awardwallet test spent awards itinerary';

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        if (strpos($parser->emailRawContent, self::TIMEOUT_MARKER) !== false) {
            while (true);
        } // loop

        if (stripos($parser->emailRawContent, self::USEREMAIL_MARKER) !== false) {
            return true;
        }

        if (stripos($parser->emailRawContent, self::SPENT_AWARDS_MARKER) !== false) {
            return true;
        }

        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return false;
    }

    public function GetStatementCriteria()
    {
        return [
            'BODY "' . self::SPENT_AWARDS_MARKER . '"',
        ];
    }

    public function ParsePlanEmail(\PlancakeEmailParser $parser)
    {
        if (stripos($parser->emailRawContent, self::USEREMAIL_MARKER) !== false) {
            return [
                "parsedData" => ["Properties" => ["Balance" => 1], "userEmail" => self::USEREMAIL_MARKER],
                "emailType"  => "123",
            ];
        }

        if (stripos($parser->emailRawContent, self::SPENT_AWARDS_MARKER) !== false) {
            include_once __DIR__ . '/../functions.php';
            $testProvider = new \TAccountCheckerTestprovider();
            $testProvider->AccountFields['Login'] = 'trip.today';
            $result = $testProvider->ParseItineraries();

            return [
                'parsedData' => [
                    'Itineraries' => [
                        array_shift($result),
                    ],
                ],
                'emailType' => '123',
            ];
        }

        return [];
    }
}
