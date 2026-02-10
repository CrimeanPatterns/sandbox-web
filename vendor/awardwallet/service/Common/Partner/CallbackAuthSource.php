<?php

namespace AwardWallet\Common\Partner;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerInterface;

class CallbackAuthSource
{

    /**
     * @var \Doctrine\DBAL\Driver\Statement
     */
    private $query;
    /**
     * @var \Memcached
     */
    private $memcached;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection, \Memcached $memcached, LoggerInterface $logger)
    {
        $this->memcached = $memcached;
        $this->logger = $logger;
        $this->connection = $connection;
    }

    public function getByUrl(string $partner, string $url) : ?CallbackAuth
    {
        $host = parse_url($url, PHP_URL_HOST);

        // cache reasons:
        // 1. reduce db usage
        // 2. receive requests when db is offline
        $cacheKey = "cb_url:" . $partner . ":" . $host;
        $cache = $this->memcached->get($cacheKey);

        if ($cache !== false && (time() - $cache['created']) < 30) {
            return new CallbackAuth($cache['Username'], $cache['Pass']);
        }

        try {
            $this->prepareQuery();
            $this->query->execute(["partner" => $partner, "host_regexp" => $this->prepareHostRegexp($host)]);
            $row = $this->query->fetch(FetchMode::ASSOCIATIVE);

            if ($row === false) {
                $this->logger->warning("unknown callback url: $partner, $url");
                return null;
            }

            $row['created'] = time();
            $this->memcached->set($cacheKey, $row, 3600 * 12);

            return new CallbackAuth($row["Username"], $row["Pass"]);
        }
        catch (DBALException $e) {
            if ($cache !== false) {
                $this->logger->warning("fallback - callback validated through cache: " . $e->getMessage());
                return new CallbackAuth($cache['Username'], $cache['Pass']);
            }
        }

        return null;
    }

    private function prepareHostRegexp($host) : string
    {
        $result = '^(' . preg_quote($host, '#');

        $domains = explode(".", $host);
        while (count($domains) > 1) {
            array_shift($domains);
            $result .= '|' . preg_quote('*.' . implode('.', $domains), '#');
        }

        $result .= ')$';

        return $result;
    }

    private function prepareQuery() : void
    {
        if ($this->query === null) {
            $this->query = $this->connection->prepare("
            select 
                pc.Username, pc.Pass 
            from 
                PartnerCallback pc
                join Partner p on p.PartnerID = pc.PartnerID
            where 
                p.Login = :partner and pc.URL regexp :host_regexp
            order by 
                length(pc.URL)"
            );
        }
    }

}