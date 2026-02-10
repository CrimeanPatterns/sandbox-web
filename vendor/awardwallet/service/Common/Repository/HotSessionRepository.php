<?php


namespace AwardWallet\Common\Repository;


use AwardWallet\Common\Document\HotSession;
use AwardWallet\Common\Document\HotSessionInfo;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\MongoDBException;

class HotSessionRepository extends DocumentRepository
{

    public function getHotSession(string $prefix, string $provider, ?string $accountKey): ?HotSession
    {
        $builder = $this->createQueryBuilder()
            ->sort('lastUseDate', 'desc')
            ->findAndUpdate()
            ->returnNew()
            ->field('prefix')->equals($prefix)
            ->field('provider')->equals($provider)
            ->field('isLocked')->equals(false);
        if (!empty($accountKey)) {
            $builder->field('accountKey')->equals($accountKey);
        }

        $row = $builder
            // update
            ->field('isLocked')->set(true)
            ->field('lastUseDate')->set(new \DateTime())
            ->getQuery()
            ->execute();
        if (!$row) {
            return null;
        }
        return $row;
    }

    public function unlockHotSession(string $id): void
    {
        $this->createQueryBuilder()
            ->findAndUpdate()
            ->field('_id')->equals($id)
            // update
            ->field('isLocked')->set(false)
            ->field('lastUseDate')->set(new \DateTime())
            ->getQuery()
            ->execute();
    }

    /**
     * @throws MongoDBException
     */
    public function findBySessionData(string $host, int $port, string $sessionId)
    {
        $row = $this->createQueryBuilder()
            ->select('_id')
            ->field('sessionInfo.host')->equals($host)
            ->field('sessionInfo.port')->equals($port)
            ->field('sessionInfo.sessionId')->equals($sessionId)
            ->getQuery()
            ->execute()
            ->toArray();
        if (empty($row)) {
            return null;
        }
        $result = array_keys($row);
        if (count($result) > 1) {
            throw new MongoDBException(sprintf('HotSession has browsers with not unique combination host+port+sessionId: %s:%d sessionId %s',
                $host, $port, $sessionId));
        }
        return $result[0] ?? null;
    }

    public function deleteHotSession(string $id): void
    {
        $this->createQueryBuilder()->remove()->field('_id')->equals($id)->getQuery()->execute();
    }

    public function createNewRow(string $prefix, string $provider, ?string $accountKey, HotSessionInfo $sessionInfo): string
    {
        $data = new HotSession();
        $this->dm->persist($data);
        if (!empty($accountKey)) {
            $data->setAccountKey($accountKey);
        }
        $data
            ->setLastUseDate(new \DateTime())
            ->setStartDate(new \DateTime())
            ->setPrefix($prefix)
            ->setProvider($provider)
            ->setSessionInfo($sessionInfo)
            ->setIsLocked(false);
        $this->dm->flush($data);

        return $data->getId();
    }

    public function unlockOld(string $prefix, string $provider)
    {
        $builder = $this->createQueryBuilder();
        $lastDate = (new \DateTime())->setTimestamp(strtotime("-5 minutes"));
        $builder
            // Find the hotSession
            ->field('prefix')->equals($prefix)
            ->field('provider')->equals($provider)
            ->field('isLocked')->equals(true)
            ->field('lastUseDate')->lte($lastDate)
            // update all
            ->updateMany()
            ->field('isLocked')->set(false)
            ->getQuery()
            ->execute();
    }

}