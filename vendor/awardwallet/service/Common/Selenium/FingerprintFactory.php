<?php

namespace AwardWallet\Common\Selenium;

use AwardWallet\Common\Entity\Fingerprint;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Psr\Log\LoggerInterface;

class FingerprintFactory
{

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var \Memcached
     */
    private $memcached;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, \Memcached $memcached)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->memcached = $memcached;
    }

    /**
     * @param FingerprintRequest[] $requests
     * @return - a row from Fingerprint table
     */
    public function getOne(array $requests) : ?Fingerprint
    {
        $cacheKey = "fp_set_" . sha1(json_encode($requests));
        $fpSet = $this->memcached->get($cacheKey);
        if ($fpSet === false) {
            $fpSet = $this->getFingerprintSet($requests);
            $this->memcached->set($cacheKey, $fpSet, 3600);
        }

        if (count($fpSet) === 0) {
            $this->logger->info("no fingerprints found");
            return null;
        }

        $fp = null;
        $try = 0;
        while ($fp === null && $try < 3) {
            $fpId = $fpSet[array_rand($fpSet)];
            $fp = $this->entityManager->find(Fingerprint::class, $fpId);
            $try++;
        }

        if ($fp === null) {
            $this->logger->info("no fingerprint found");
            return null;
        }

        $this->logger->info("found fingerprint: {$fp->getId()} {$fp->getBrowserFamily()}:{$fp->getBrowserVersion()} {$fp->getPlatform()} {$fp->getUseragent()}");

        return $fp;
    }

    /**
     * @param int $fpId
     * @return - a row from Fingerprint table
     */
    public function getOneById(int $fpId) : ?Fingerprint
    {
        $fp = $this->entityManager->find(Fingerprint::class, $fpId);

        if ($fp === null) {
            $this->logger->info("no fingerprint found");
            return null;
        }

        $this->logger->info("found fingerprint: {$fp->getId()} {$fp->getBrowserFamily()}:{$fp->getBrowserVersion()} {$fp->getPlatform()} {$fp->getUseragent()}");

        return $fp;
    }

    public function getFingerprintSet(array $requests, int $limit = 10000) : array
    {
        $builder = $this->entityManager->getConnection()->createQueryBuilder();
        $builder
            ->select("fp.FingerPrintID")
            ->from("Fingerprint", "fp")
            ->setMaxResults($limit)
        ;

        if (count($requests) > 0) {
            $builder->where($this->buildWhere($builder, $requests));
        }

        $result = $this->entityManager->getConnection()->fetchFirstColumn($builder->getSQL(), $builder->getParameters());
        $this->logger->info("loaded " . count($result) . " fingerprints");

        return $result;
    }

    private function buildWhere(QueryBuilder $builder, array $requests) : CompositeExpression
    {
        $or = $builder->expr()->orX();
        foreach ($requests as $n => $request) {
            $and = $builder->expr()->andX();
            if ($request->browserFamily !== null) {
                $and->add($builder->expr()->eq("fp.browserFamily", ":family{$n}"));
                $builder->setParameter("family{$n}", $request->browserFamily);
            }
            if ($request->browserVersionMin !== null) {
                $and->add($builder->expr()->gte("fp.browserVersion", ":minVersion{$n}"));
                $builder->setParameter("minVersion{$n}", $request->browserVersionMin);
            }
            if ($request->browserVersionMax !== null) {
                $and->add($builder->expr()->lte("fp.browserVersion", ":maxVersion{$n}"));
                $builder->setParameter("maxVersion{$n}", $request->browserVersionMax);
            }
            if ($request->isMobile !== null) {
                $and->add($builder->expr()->eq("fp.isMobile", ":mobile{$n}"));
                $builder->setParameter("mobile{$n}", $request->isMobile);
            }
            if ($request->platform !== null) {
                $and->add($builder->expr()->eq("fp.platform", ":platform{$n}"));
                $builder->setParameter("platform{$n}", $request->platform);
            }
            $or->add($and);
        }
        return $or;
    }

}
