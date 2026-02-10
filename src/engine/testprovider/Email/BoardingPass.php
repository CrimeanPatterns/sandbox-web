<?php

namespace AwardWallet\Engine\testprovider\Email;

class BoardingPass extends \TAccountChecker
{
    public const FLIGHT = 'AWTESTPROVIDERBOARDINGPASS_FLIGHT';
    public const PASS = 'AWTESTPROVIDERBOARDINGPASS_PASS';

    public function ParsePlanEmail(\PlancakeEmailParser $parser)
    {
        $result = [];
        $this->http->SetBody($parser->getPlainBody());

        if (stripos($this->http->Response['body'], self::FLIGHT) !== false) {
            $result['Itineraries'] = [[
                'Kind'          => 'T',
                'RecordLocator' => CONFNO_UNKNOWN,
                'TripSegments'  => [
                    [
                        'DepCode'      => TRIP_CODE_UNKNOWN,
                        'DepDate'      => strtotime($this->http->FindPreg('/DepDate ([^\n]+)/')),
                        'DepName'      => $this->http->FindPreg('/DepName ([^\n]+)/'),
                        'ArrCode'      => TRIP_CODE_UNKNOWN,
                        'ArrDate'      => strtotime($this->http->FindPreg('/ArrDate ([^\n]+)/')),
                        'ArrName'      => $this->http->FindPreg('/ArrName ([^\n]+)/'),
                        'FlightNumber' => $this->http->FindPreg('/FlightNumber ([^\n]+)/'),
                        'AirlineName'  => $this->http->FindPreg('/AirlineName ([^\n]+)/'),
                    ],
                ],
            ]];
        }

        if (stripos($this->http->Response['body'], self::PASS) !== false) {
            $bp = [
                'DepCode'      => $this->http->FindPreg('/DepCode ([^\n]+)/'),
                'DepDate'      => strtotime($this->http->FindPreg('/DepDate ([^\n]+)/')),
                'FlightNumber' => $this->http->FindPreg('/FlightNumber ([^\n]+)/'),
                'AirlineName'  => $this->http->FindPreg('/AirlineName ([^\n]+)/'),
            ];
            $search = $parser->searchAttachmentByName('.+\.(pdf|png)');

            if (count($search) === 1) {
                $name = $parser->getAttachmentHeader($search[0], 'Content-Type');

                if ($name && preg_match('/name="(?<n>.+\.(pdf|png))"/', $name, $m) > 0) {
                    $bp['AttachmentFileName'] = $m['n'];
                }
            }

            if (!isset($bp['AttachmentFileName'])) {
                $bp['BoardingPassURL'] = $this->http->FindPreg('/URL ([^\n]+)/');
            }

            $result['BoardingPass'] = [[$bp]];
        }

        return [
            'parsedData' => $result,
            'emailType'  => 'boardingPass',
        ];
    }

    public function detectEmailByBody(\PlancakeEmailParser $parser)
    {
        $body = $parser->getPlainBody();

        return stripos($body, self::FLIGHT) !== false
            || stripos($body, self::PASS) !== false;
    }

    public function detectEmailByHeaders(array $headers)
    {
        return false;
    }

    public function detectEmailFromProvider($from)
    {
        return false;
    }
}
