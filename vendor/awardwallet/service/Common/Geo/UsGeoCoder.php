<?php


namespace AwardWallet\Common\Geo;


use AwardWallet\Common\Geo\PositionStack\SolverClient;
use Doctrine\DBAL\Connection;
use PDO;
use PDOException;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Exception;

class UsGeoCoder implements GeoCodeSourceInterface
{

    /** @var SolverClient  */
    private $client;

    /** @var LoggerInterface  */
    private $logger;

    private $stateRegexp = false;

    public function __construct(SolverClient $client, LoggerInterface $logger, Connection $connection)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->connection = $connection;
    }

    public function getSourceId(): string
    {
        return 'psu';
    }

    public function geoCode(string $query, array $bias = []): array
    {
        if (strlen($query) < 3) {
            $this->logger->info("request is too short");
            return [];
        }
        $valid = $this->isUs($query);
        $this->logger->info('geo code request', ['source' => 'psus', 'isus' => $valid]);
        if (!$valid) {
            return [];
        }
        return $this->client->geoCode($query);
    }

    private function isUs(string $query): bool
    {
        if (preg_match('/\b(US|USA|United States)\b/', $query) > 0) {
            return true;
        }

        if ($this->stateRegexp === false) {
            try {
                $states = [];
                $q = $this->connection->executeQuery('select Code, Name from State where CountryID = 230');
                while($row = $q->fetch(PDO::FETCH_ASSOC)) {
                    $states[] = $row['Code'];
                    $states[] = $row['Name'];
                }
                $this->stateRegexp = '/\b(' . implode('|', $states) . ')\b/';
            }
            catch(PDOException | Exception $e) {
                $this->stateRegexp = null;
            }
        }

        if (null !== $this->stateRegexp && preg_match($this->stateRegexp, $query) > 0) {
            return true;
        }

        return false;
    }
}
