<?php
if (file_exists($file = "vendor/autoload.php")) /** @var \Composer\Autoload\ClassLoader $loader */ {
    $loader = require_once $file;
} else {
    echo "Run this test from project root directory\n";
    exit(1);
}

const TRIP_CODE_UNKNOWN = 'UnknownCode';
const CONFNO_UNKNOWN = 'UnknownNumber';
const MISSING_DATE = -1;
const FLIGHT_NUMBER_UNKNOWN = 'UnknownFlightNumber';
const AIRLINE_UNKNOWN = 'UnknownAirlineName';

const EVENT_RESTAURANT = 1;

const TRIP_CATEGORY_BUS = 2;
const TRIP_CATEGORY_TRAIN = 3;
const TRIP_CATEGORY_CRUISE = 4;
const TRIP_CATEGORY_FERRY = 5;
const TRIP_CATEGORY_TRANSFER = 6;

$logger = new \Symfony\Component\Console\Logger\ConsoleLogger(new \Symfony\Component\Console\Output\ConsoleOutput(256));
$err = $ok = 0;
$reWrite = isset($argv[1]) && $argv[1] === '-w';
try {
    // region email Itinerary
    $it = [];
    $it['Flight'] = [
        'Kind' => 'T',
        'RecordLocator' => 'ABCDEF',
        'ConfirmationNumbers' => '1232323',
        'Passengers' => ['John', 'Jane Doe'],
        'AccountNumbers' => ['123456', 'XXXX4321'],
        'TicketNumbers' => ['123-45678', '123-XXXXX'],
        'SpentAwards' => '2000 points',
        'EarnedAwards' => '25000 miles',
        'Status' => 'confirmed',
        'TotalCharge' => '1234',
        'Currency' => 'USD',
        'Tax' => '23.45',
        'BaseFare' => '1200',
        'NoItineraries' => false,
        'ReservationDate' => strtotime('2018-01-17'),
        'Fees' => [
            [
                'Name' => 'seat selection',
                'Charge' => '12'
            ],
            [
                'Name' => 'seat selection',
                'Charge' => '12'
            ],
            [
                'Name' => 'additional baggage',
                'Charge' => '10'
            ],
        ],
        'Discount' => '100',
        'TripSegments' => [
            [
                'FlightNumber' => FLIGHT_NUMBER_UNKNOWN,
                'AirlineName' => 'BA',
                'DepCode' => 'BUF',
                'DepName' => 'buffalo',
                'DepDate' => strtotime('2018-01-17 13:30'),
                'DepartureTerminal' => '1',
                'ArrCode' => TRIP_CODE_UNKNOWN,
                'ArrName' => 'Los Angeles',
                'ArrDate' => strtotime('2018-01-17 15:40'),
                'Status' => 'pending',
                'Seats' => ['1C', '1B'],
                'Stops' => 0,
                'Smoking' => false,
                'Aircraft' => 'AirBus 420',
                'TraveledMiles' => '770km',
                'Cabin' => 'Economy',
                'BookingClass' => 'CL',
                'Duration' => '7 years',
                'Meal' => 'Brownies'
            ],
            [
                'FlightNumber' => 123,
                'AirlineName' => AIRLINE_UNKNOWN,
                'DepCode' => 'LAS',
                'DepName' => 'Los Angeles',
                'DepDate' => MISSING_DATE,
                'ArrivalTerminal' => '1',
                'ArrCode' => TRIP_CODE_UNKNOWN,
                'ArrName' => 'Los Angeles',
                'ArrDate' => strtotime('2018-01-17 15:40'),
                'Status' => 'pending',
                'Seats' => ['1C', '1B'],
                'Operator' => 'SkyWest'
            ],
        ]
    ];
    $it['Hotel'] = [
        'Kind' => 'R',
        'ConfirmationNumber' => '6677889900',
        'TripNumber' => '810954276 ',
        'ConfirmationNumbers' => '6677889900/12123123123',
        'Status' => 'Cancelled',
        'Cancelled' => true,
        'HotelName' => 'Grand Hotel',
        'Phone' => '3344-5566',
        'Fax' => '556677-00',
        'Address' => 'Emerald City, Yellow brick road 1',
        'CancellationPolicy' => 'is non refundable',
        'AccountNumbers' => ['4444', '55555'],
        'GuestNames' => ['Jane', 'Joe'],
        'DetailedAddress' => [
            'AddressLine' => 'ajjajas',
            'CityName' => 'cccc',
            'PostalCode' => '12123123',
            'StateProv' => 'stststs',
            'Country' => 'oiopopo',
        ],
        'CheckInDate' => strtotime('2018-01-17 10:00 AM'),
        'CheckOutDate' => strtotime('2018-01-19 13:00'),
        'Guests' => 1,
        'Kids' => 2,
        'Rooms' => 2,
        'RoomType' => 'room type|awesome',
        'RoomTypeDescription' => 'room description|everything u can wish for',
        'Rate' => 'room rate|100 $ / night',
        'RateType' => 'room rate type|double',
        'Total' => '11.1',
        'Currency' => '$'
    ];
    $it['Rental'] = [
        'Kind' => 'L',
        'RenterName' => 'Rob Bran',
        'AccountNumbers' => ['4445'],
        'Number' => CONFNO_UNKNOWN,
        'TripNumber' => '7676678585',
        'EarnedAwards' => '3 pts',
        'PickupDatetime' => strtotime('2018-01-17 7:20 AM'),
        'PickupLocation' => 'somewhere in the desert',
        'DropoffDatetime' => strtotime('2018-01-19 9:20 AM'),
        'DropoffLocation' => 'somewhere in the desert',
        'PickupPhone' => '123-13-23',
        'PickupFax' => '+1 123-13-23',
        'PickupHours' => '5:00-23:00',
        'RentalCompany' => 'Alamo',
        'CarType' => 'sedan',
        'CarModel' => 'honda',
        'CarImageUrl' => 'http://ayy.lmao',
        'Discounts' => [
            [
                'Code' => 'AAA SOUTHERN NEW ENGLAND',
                'Name' => 'Your Rate has been discounted based on the Hertz CDP provided'
            ]
        ]
    ];
    $it['Cruise'] = [
        'RecordLocator' => '12123123',
        'TripCategory' => TRIP_CATEGORY_CRUISE,
        'Deck' => '13.5',
        'Passengers' => ['John Doe'],
        'CruiseName' => 'nice cruise',
        'RoomNumber' => '666',
        'ShipName' => 'titanic',
        'ShipCode' => 'TTNC',
        'RoomClass' => 'first',
        'Status' => 'Confirmed',
        'VoyageNumber' => 'ABC-123',
        'TripSegments' => [
            [
                'DepName' => 'some port',
                'DepDate' => strtotime('2018-01-17 13:30'),
                'ArrName' => 'another port',
                'ArrDate' => strtotime('2018-01-17 16:30'),
            ],
        ]
    ];
    $it['Event'] = [
        'Kind' => 'E',
        'ConfNo' => '5566hjhj',
        'EventType' => EVENT_RESTAURANT,
        'Name' => 'moe\'s',
        'Address' => 'springfield',
        'Phone' => '1122323',
        'DinerName' => 'Mary Doe',
        'Guests' => '1',
        'StartDate' => strtotime('2018-01-17'),
        'EndDate' => MISSING_DATE
    ];
    $it['Transfer'] = [
        'RecordLocator' => CONFNO_UNKNOWN,
        'TripCategory' => TRIP_CATEGORY_TRANSFER,
        'TripSegments' => [
            [
                'DepCode' => 'LAX',
                'DepDate' => strtotime('2018-01-17'),
                'ArrName' => 'some hotel',
                'ArrDate' => MISSING_DATE,
                'Type' => 'regular car',
                'Vehicle' => 'mercedes',
                'TraveledMiles' => '1km',
                'Duration' => '2h'
            ]
        ]
    ];
    $it['Train'] = [
        'RecordLocator' => CONFNO_UNKNOWN,
        'TripCategory' => TRIP_CATEGORY_TRAIN,
        'TicketNumbers' => ['3343434'],
        'TripSegments' => [
            [
                'FlightNumber' => '223',
                'AirlineName' => 'ACELLA EXPRESS',
                'DepCode' => 'CDD',
                'DepName' => 'some name',
                'DepDate' => MISSING_DATE,
                'ArrName' => 'another name',
                'ArrDate' => strtotime('2018-01-17'),
                'Type' => 'vip train',
                'Vehicle' => 'choo choo',
                'Seats' => ['4-13'],
                'Duration' => '2h'
            ]
        ]
    ];
    $it['Bus'] = [
        'RecordLocator' => 'AHAHAHAH',
        'TripCategory' => TRIP_CATEGORY_BUS,
        'TicketNumbers' => ['334'],
        'TripSegments' => [
            [
                'FlightNumber' => FLIGHT_NUMBER_UNKNOWN,
                'DepName' => 'some name',
                'DepDate' => MISSING_DATE,
                'ArrCode' => 'CDD',
                'ArrName' => 'station name',
                'ArrDate' => strtotime('2018-01-17 13:20')
            ]
        ]
    ];
    $it['Ferry'] = [
        'RecordLocator' => 'CONF1',
        'TripCategory' => TRIP_CATEGORY_FERRY,
        'TicketNumbers' => ['112233'],
        'ReservationDate' => strtotime('2030-01-01'),
        'Passengers' => ['John Doe', 'Jane Doe'],
        'RoomNumber' => 'topside',
        'ShipName' => 'VS-1',
        'Status' => 'Confirmed',
        'TripSegments' => [
            [
                'DepName' => 'some port',
                'DepDate' => strtotime('2018-01-17 13:30'),
                'ArrName' => 'another port',
                'ArrDate' => strtotime('2018-01-17 16:30'),
                'Cabin' => 'flexi'
            ],
        ]
    ];
    runCheck($it, true, $logger, $err, $ok, $reWrite);
    //endregion

    // region master NoItineraries + BP + Statement
    $it = [];
    foreach (array('T', 'R', 'L', 'E') as $kind) {
        $it[strtoupper($kind)] = array(
            'Kind' => strtoupper($kind),
            'NoItineraries' => true,
        );
    }
    $its['Itineraries'] = $it;
    $it = [];
    $it['BoardingPass'] = [
        [
            'DepCode' => 'ABC',
            'DepDate' => strtotime('2018-01-01 13:30'),
            'Passengers' => ['richard'],
            'AttachmentFileName' => 'bp.pdf',
            'BoardingPassURL' => 'http://some.url',
            'FlightNumber' => '123',
            'RecordLocator' => 'SDF43D'
        ]
    ];
    $it['Properties'] = [
        'Miles' => 123,
        'Points' => 'ABC',
        'Balance' => 1234.23,
        'AccountExpirationDate' => strtotime('2018-09-09')
    ];
    $it['Activity'] = [
        ['Field11' => 'Value11', 'Field12' => 'Value12'],
        ['Field21' => 'Value21', 'Field22' => 'Value22'],
        ['Field31' => 'Value31', 'Field32' => 'Value32']
    ];

    $its = array_merge($its, $it);
    runCheck($its, false, $logger, $err, $ok, $reWrite);
    //endregion
} catch (\AwardWallet\Schema\Parser\Component\InvalidDataException $e) {
    $logger->error($e->getMessage());
    $err++;
}
$logger->log($err > 0 ? \Psr\Log\LogLevel::ERROR : \Psr\Log\LogLevel::INFO,
    sprintf('%d success, %d errors', $ok, $err));

function runCheck(
    array $data,
    bool $isEmail = false,
    \Symfony\Component\Console\Logger\ConsoleLogger &$logger,
    int &$err,
    int &$ok,
    bool $reWrite
) {
    foreach ($data as $key => $value) {
        $prefix = '';
        if (!$isEmail && $key === 'Itineraries') {
            $prefix = 'no';
        }
        $logger->notice('---' . $prefix . $key . '---');
        $options = new \AwardWallet\Schema\Parser\Component\Options();
        $options->logDebug = false;
        $options->throwOnInvalid = true;
        if ($isEmail) {
            $result = [
                'emailType' => $key,
                'parsedData' => [
                    'Itineraries' => [$value],
                    'TotalCharge' => [
                        'Amount' => 15678.56,
                        'Currency' => 'USD'
                    ]
                ],
                'providerCode' => 'testprovider'
            ];
            $e = new \AwardWallet\Schema\Parser\Email\Email('e', $options);
        } else {
            $result = [
                $key => $value
            ];
            $e = new \AwardWallet\Schema\Parser\Component\Master('e', $options);
        }
        $e->getLogger()->pushHandler(new \Monolog\Handler\PsrHandler($logger));
        try {
            if ($isEmail) {
                \AwardWallet\Schema\Parser\Util\ArrayConverter::convertEmail($result, $e);
            } else {
                \AwardWallet\Schema\Parser\Util\ArrayConverter::convertMaster($result, $e);
            }
            $logger->info('convert ' . $prefix . $key . ' SUCCESS');
            $ok++;
        } catch (\Exception $e) {
            $logger->error("Uncaught ErrorException: " . $e->getMessage() . "\n" . "Stack trace:" . $e->getTraceAsString());
            $err++;
            continue;
        }
        if ($reWrite) {
            $logger->debug('writing json to ' . $prefix . $key . '.json');
            file_put_contents(__DIR__ . '/data/' . $prefix . $key . '.json', json_encode($e->toArray()));
        };
        $expect = file_get_contents(__DIR__ . '/data/' . $prefix . $key . '.json');
        $json = json_encode($e->toArray());
        if (strcmp($expect, $json) !== 0) {
            $logger->error('JSON NOT MATCHING');
            $pos = strspn($expect ^ $json, "\0");
            $logger->notice('expecting ' . substr($expect, max(0, $pos - 50), 100));
            $logger->notice('     got ' . substr($json, max(0, $pos - 50), 100));
            $err++;
        } else {
            $logger->info('OK');
            $ok++;
        }
    }
}