<?php


namespace AwardWallet\Common\Parsing\Solver;


use AwardWallet\Common\Parsing\Solver\Extra\Extra;
use AwardWallet\Schema\Parser\Common\Statement;
use Doctrine\DBAL\Connection;

class Loyalty
{

    /**
     * @var Connection $connection
     */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function solve(Statement $st, Extra $extra) {
        /** @var \TAccountChecker $checker */
        $class = sprintf('\TAccountChecker%s', ucfirst($extra->provider->code));
        if (class_exists($class)) {
            $checker = new $class();
            $extra->provider->historyFields = $checker->GetHistoryColumns();
        }
        $properties = $this->connection->executeQuery(
            'select Code, Kind, Name from ProviderProperty where ProviderID in (select ProviderID from Provider where Code = ?) or ProviderID is null',
                [$extra->provider->code])->fetchAll(\PDO::FETCH_ASSOC);
        $extra->provider->properties = [];
        $schema = [];
        foreach($properties as $row) {
            $extra->provider->properties[$row['Code']] = $row;
            $schema[$row['Code']] = $row['Kind'];
        }
        $st->loadProviderProperties($extra->provider->code, $schema);
        $st->validateProperties();
    }

}