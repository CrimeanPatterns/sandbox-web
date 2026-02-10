<?php

namespace AwardWallet\Common;

use AwardWallet\Common\Geo\TimezoneResolver;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Statement;
use Symfony\Component\Console\Command\Command;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FixTimeZoneLocationCommand extends Command
{
    protected static $defaultName = 'aw:fix-timezone-locations';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var TimezoneResolver
     */
    private $timezoneResolver;

    private const UNSUPPORTED_TIMEZONES = [
        'Africa/Asmera',
        'Africa/Timbuktu',
        'America/Argentina/ComodRivadavia',
        'America/Atka',
        'America/Buenos_Aires',
        'America/Catamarca',
        'America/Coral_Harbour',
        'America/Cordoba',
        'America/Ensenada',
        'America/Fort_Wayne',
        'America/Godthab',
        'America/Indianapolis',
        'America/Jujuy',
        'America/Knox_IN',
        'America/Louisville',
        'America/Mendoza',
        'America/Montreal',
        'America/Porto_Acre',
        'America/Rosario',
        'America/Santa_Isabel',
        'America/Shiprock',
        'America/Virgin',
        'Antarctica/South_Pole',
        'Asia/Ashkhabad',
        'Asia/Calcutta',
        'Asia/Chongqing',
        'Asia/Chungking',
        'Asia/Dacca',
        'Asia/Harbin',
        'Asia/Istanbul',
        'Asia/Kashgar',
        'Asia/Katmandu',
        'Asia/Macao',
        'Asia/Rangoon',
        'Asia/Saigon',
        'Asia/Tel_Aviv',
        'Asia/Thimbu',
        'Asia/Ujung_Pandang',
        'Asia/Ulan_Bator',
        'Atlantic/Faeroe',
        'Atlantic/Jan_Mayen',
        'Australia/ACT',
        'Australia/Canberra',
        'Australia/LHI',
        'Australia/North',
        'Australia/NSW',
        'Australia/Queensland',
        'Australia/South',
        'Australia/Tasmania',
        'Australia/Victoria',
        'Australia/West',
        'Australia/Yancowinna',
        'Brazil/Acre',
        'Brazil/DeNoronha',
        'Brazil/East',
        'Brazil/West',
        'Canada/Atlantic',
        'Canada/Central',
        'Canada/Eastern',
        'Canada/Mountain',
        'Canada/Newfoundland',
        'Canada/Pacific',
        'Canada/Saskatchewan',
        'Canada/Yukon',
        'CET',
        'Chile/Continental',
        'Chile/EasterIsland',
        'CST6CDT',
        'Cuba',
        'EET',
        'Egypt',
        'Eire',
        'EST',
        'EST5EDT',
        'Etc/GMT',
        'Etc/GMT+0',
        'Etc/GMT+1',
        'Etc/GMT+10',
        'Etc/GMT+11',
        'Etc/GMT+12',
        'Etc/GMT+2',
        'Etc/GMT+3',
        'Etc/GMT+4',
        'Etc/GMT+5',
        'Etc/GMT+6',
        'Etc/GMT+7',
        'Etc/GMT+8',
        'Etc/GMT+9',
        'Etc/GMT-0',
        'Etc/GMT-1',
        'Etc/GMT-10',
        'Etc/GMT-11',
        'Etc/GMT-12',
        'Etc/GMT-13',
        'Etc/GMT-14',
        'Etc/GMT-2',
        'Etc/GMT-3',
        'Etc/GMT-4',
        'Etc/GMT-5',
        'Etc/GMT-6',
        'Etc/GMT-7',
        'Etc/GMT-8',
        'Etc/GMT-9',
        'Etc/GMT0',
        'Etc/Greenwich',
        'Etc/UCT',
        'Etc/Universal',
        'Etc/UTC',
        'Etc/Zulu',
        'Europe/Belfast',
        'Europe/Nicosia',
        'Europe/Tiraspol',
        'Factory',
        'GB',
        'GB-Eire',
        'GMT',
        'GMT+0',
        'GMT-0',
        'GMT0',
        'Greenwich',
        'Hongkong',
        'HST',
        'Iceland',
        'Iran',
        'Israel',
        'Jamaica',
        'Japan',
        'Kwajalein',
        'Libya',
        'MET',
        'Mexico/BajaNorte',
        'Mexico/BajaSur',
        'Mexico/General',
        'MST',
        'MST7MDT',
        'Navajo',
        'NZ',
        'NZ-CHAT',
        'Pacific/Johnston',
        'Pacific/Ponape',
        'Pacific/Samoa',
        'Pacific/Truk',
        'Pacific/Yap',
        'Poland',
        'Portugal',
        'PRC',
        'PST8PDT',
        'ROC',
        'ROK',
        'Singapore',
        'Turkey',
        'UCT',
        'Universal',
        'US/Alaska',
        'US/Aleutian',
        'US/Arizona',
        'US/Central',
        'US/East-Indiana',
        'US/Eastern',
        'US/Hawaii',
        'US/Indiana-Starke',
        'US/Michigan',
        'US/Mountain',
        'US/Pacific',
        'US/Samoa',
        'UTC',
        'W-SU',
        'WET',
        'Zulu',
    ];

    public function __construct(LoggerInterface $logger, Connection $connection, TimezoneResolver $timezoneResolver)
    {
        parent::__construct();

        $this->logger = $logger;
        $this->connection = $connection;
        $this->timezoneResolver = $timezoneResolver;
    }

    protected function configure()
    {
        $this
            ->setName("aw:fix-timezone-locations")
            ->setDescription('Fill TimeZoneLocation fields in GeoTag, AirCode, StationCode tables')
            ->addOption('tables', 't', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, "tables", ['GeoTag', 'AirCode', 'StationCode']);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $tables = $input->getOption('tables');

        if (in_array('GeoTag', $tables)) {
            $this->processGeoTag();
        }
        if (in_array('AirCode', $tables)) {
            $this->processAirCode();
        }
        if (in_array('StationCode', $tables)) {
            $this->processStationCode();
        }

        $output->writeln('done.');
    }

    private function processGeoTag()
    {
        $this->logger->info(sprintf('processing GeoTag table'));

        $affected = $this->connection->executeUpdate($sql = "
            UPDATE
                GeoTag g
                JOIN TimeZone tz ON tz.TimeZoneID = g.TimeZoneID
            SET
                g.TimeZoneLocation = tz.Location
            WHERE
                tz.Location NOT IN (?)
                AND g.TimeZoneLocation = 'UTC'
        ", [self::UNSUPPORTED_TIMEZONES], [Connection::PARAM_STR_ARRAY]);
        $this->logger->info(sprintf('%s, affected: %d', $sql, $affected));

        $stmtUpdate = $this->connection->prepare('UPDATE GeoTag SET TimeZoneLocation = ? WHERE GeoTagID = ?');
        $q = $this->connection->executeQuery("
            SELECT
                g.GeoTagID,
                g.Address,
                g.Lat,
                g.Lng
            FROM
                GeoTag g
                LEFT JOIN TimeZone tz ON tz.TimeZoneID = g.TimeZoneID
            WHERE
               (g.TimeZoneID IS NULL OR tz.Location IN (?))
               AND g.TimeZoneLocation = 'UTC'
        ", [self::UNSUPPORTED_TIMEZONES], [Connection::PARAM_STR_ARRAY]);

        [$processed, $notProcessedIds] = $this->detectTZByCoords($q, $stmtUpdate, 'GeoTagID', 'Address');
        $this->logger->info(sprintf('update geotag tz by coordinates done, affected: %d, not processed: %d (%s)', $processed, count($notProcessedIds), implode(', ', $notProcessedIds)));
    }

    private function processAirCode()
    {
        $this->logger->info(sprintf('processing AirCode table'));

        $affected = $this->connection->executeUpdate($sql = "
            UPDATE
                AirCode a
                JOIN TimeZone tz ON tz.TimeZoneID = a.TimeZoneID
            SET
                a.TimeZoneLocation = tz.Location
            WHERE
                tz.Location NOT IN (?)
                AND a.TimeZoneLocation = 'UTC'
        ", [self::UNSUPPORTED_TIMEZONES], [Connection::PARAM_STR_ARRAY]);
        $this->logger->info(sprintf('%s, affected: %d', $sql, $affected));

        $stmtUpdate = $this->connection->prepare('UPDATE AirCode SET TimeZoneLocation = ? WHERE AirCodeID = ?');
        $q = $this->connection->executeQuery("
            SELECT
                a.AirCodeID,
                a.AirCode,
                a.Lat,
                a.Lng
            FROM
                AirCode a
                LEFT JOIN TimeZone tz ON tz.TimeZoneID = a.TimeZoneID
            WHERE
               (a.TimeZoneID IS NULL OR tz.Location IN (?))
               AND a.TimeZoneLocation = 'UTC'
        ", [self::UNSUPPORTED_TIMEZONES], [Connection::PARAM_STR_ARRAY]);
        [$processed, $notProcessedIds] = $this->detectTZByCoords($q, $stmtUpdate, 'AirCodeID', 'AirCode');
        $this->logger->info(sprintf('update aircode tz by coordinates done, affected: %d, not processed: %d (%s)', $processed, count($notProcessedIds), implode(', ', $notProcessedIds)));
    }

    private function processStationcode()
    {
        $this->logger->info(sprintf('processing StationCode table'));

        $affected = $this->connection->executeUpdate($sql = "
            UPDATE
                StationCode a
                JOIN TimeZone tz ON tz.TimeZoneID = a.TimeZoneID
            SET
                a.TimeZoneLocation = tz.Location
            WHERE
                tz.Location NOT IN (?)
                AND (a.TimeZoneLocation IS NULL OR a.TimeZoneLocation = 'UTC')
        ", [self::UNSUPPORTED_TIMEZONES], [Connection::PARAM_STR_ARRAY]);
        $this->logger->info(sprintf('%s, affected: %d', $sql, $affected));

        $stmtUpdate = $this->connection->prepare('UPDATE StationCode SET TimeZoneLocation = ? WHERE StationCodeID = ?');
        $q = $this->connection->executeQuery("
            SELECT
                a.StationCodeID,
                a.StationCode,
                a.Lat,
                a.Lng
            FROM
                StationCode a
                LEFT JOIN TimeZone tz ON tz.TimeZoneID = a.TimeZoneID
            WHERE
               (a.TimeZoneID IS NULL OR tz.Location IN (?))
               AND (a.TimeZoneLocation IS NULL OR a.TimeZoneLocation = 'UTC')
        ", [self::UNSUPPORTED_TIMEZONES], [Connection::PARAM_STR_ARRAY]);
        [$processed, $notProcessedIds] = $this->detectTZByCoords($q, $stmtUpdate, 'StationCodeID', 'StationCode');
        $this->logger->info(sprintf('update stationcode tz by coordinates done, affected: %d, not processed: %d (%s)', $processed, count($notProcessedIds), implode(', ', $notProcessedIds)));
    }

    private function detectTZByCoords(ResultStatement $selectStmt, $updateStmt, string $idField, string $adrField)
    {
        $processed = 0;
        $notProcessedIds = [];
        while ($row = $selectStmt->fetch(\PDO::FETCH_ASSOC)) {
            $id = $row[$idField];
            try {
                if (empty($row['Lat']) || empty($row['Lng'])) {
                    throw new \Exception('empty coords');
                }

                $timezoneId = null;
                $offset = null;
                $this->timezoneResolver->getTimeZoneByCoordinates($row['Lat'], $row['Lng'], $offset, $timezoneId);

                if (empty($timezoneId)) {
                    throw new \Exception('empty timezone id');
                }

                $this->logger->info(sprintf('getTZByCoordinates, ID: %d, adr: %s, %d, %d, tz: %s, offset: %d', $id, $row[$adrField], $row['Lat'], $row['Lng'], $timezoneId, $offset));
                $updateStmt->execute([$timezoneId, $id]);
                $processed++;
            } catch(\Exception $e) {
                $this->logger->info(sprintf('#%d, detect tz exception: %s', $id, $e->getMessage()));
                $notProcessedIds[] = $id;
            }
        }

        return [$processed, $notProcessedIds];
    }
}
