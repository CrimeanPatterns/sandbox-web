<?php

namespace AwardWallet\Common\Doctrine;

use Doctrine\DBAL\Connection;

class BatchUpdater
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var array
     */
    private $preparedQueries = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param array $rows - array of params, like [[0 => 'a', 1 => 'b'], [0 => 'c', 1 => 'd']] or [["Param1" => "a", "Param2" => "b"], ["Param1" => "c", "Param2" => "d"]]
     * @param string $sql - update sql for 1 row, like "update SomeTable set SomeField = ? where OtherField = ?" or "update SomeTable set SomeField = :Param1 where OtherField = :Param2"
     * @param int $packageSize - max rows to process. zero for all
     * @return int number of updated rows
     */
    public function batchUpdate(array &$rows, string $sql, int $packageSize) : int
    {
        $result = 0;

        if (count($rows) > 0 && (count($rows) >= $packageSize || $packageSize === 0)) {
            $isNamedParams = is_string(array_keys(reset($rows))[0]);

            if ($isNamedParams) {
                $index = 0;
                $params = [];
                $paramNames = [];

                foreach ($rows as $row) {
                    foreach ($row as $name => $value) {
                        $params[$name . $index] = $value;

                        if (!in_array($name, $paramNames)) {
                            $paramNames[] = $name;
                        }
                    }
                    $index++;
                }
            } else {
                $params = array_merge(...$rows);
            }

            /** @var Driver\Ste $q */
            $q = $this->preparedQueries[$sql . count($rows)] ?? null;

            if ($q === null) {
                if ($isNamedParams) {
                    $index = 0;
                    $batchSql = implode(";", array_map(function (string $sql) use (&$index, $paramNames) {
                        foreach ($paramNames as $name) {
                            $sql = str_replace(':' . $name, ':' . $name . $index, $sql);
                        }
                        $index++;

                        return $sql;
                    }, array_fill(0, count($rows), $sql)));
                } else {
                    $batchSql = str_repeat($sql . ';', count($rows));
                }

                $q = $this->connection->prepare($batchSql);
                $this->preparedQueries[$sql . count($rows)] = $q;
            }

            $result += $q->execute($params);
            $q->closeCursor();
            $rows = [];
        }

        return $result;
    }
}
