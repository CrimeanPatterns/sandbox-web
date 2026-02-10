<?php


namespace AwardWallet\Common\Document;

use AwardWallet\Common\Repository\HotSessionRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass=HotSessionRepository::class)
 * @MongoDB\Indexes({
 *   @MongoDB\Index(keys={"prefix"="asc"}),
 *   @MongoDB\Index(keys={"provider"="asc"}),
 *   @MongoDB\Index(keys={"accountKey"="asc"}),
 *   @MongoDB\Index(keys={"lastUseDate"="asc"}),
 *   @MongoDB\Index(keys={"startDate"="asc"}),
 * })
 */

class HotSession
{
    /** @MongoDB\Id */
    private $id;

    /** RaAccount.id which used in session or null if without (will use for better choice of account for parsing)*/
    /** @MongoDB\Field(type="string") */
    private $accountKey;

    /** @MongoDB\Field(type="date") */
    private $startDate;

    /** @MongoDB\Field(type="date") */
    private $lastUseDate;

    /** some unique string value for a group of hot sessions to distinguish between them */
    /** @MongoDB\Field(type="string") */
    private $prefix;

    /** @MongoDB\Field(type="string") */
    private $provider;

    /** @MongoDB\Field(type="bool") */
    private $isLocked;

    /** @MongoDB\EmbedOne(targetDocument="AwardWallet\Common\Document\HotSessionInfo")
     * @var HotSessionInfo
     */
    private $sessionInfo;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getAccountKey()
    {
        return $this->accountKey;
    }

    /**
     * @param mixed $accountKey
     * @return HotSession
     */
    public function setAccountKey($accountKey)
    {
        $this->accountKey = $accountKey;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param mixed $startDate
     * @return HotSession
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastUseDate()
    {
        return $this->lastUseDate;
    }

    /**
     * @param mixed $lastUseDate
     * @return HotSession
     */
    public function setLastUseDate($lastUseDate)
    {
        $this->lastUseDate = $lastUseDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param mixed $prefix
     * @return HotSession
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param mixed $provider
     * @return HotSession
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsLocked()
    {
        return $this->isLocked;
    }

    /**
     * @param mixed $isLocked
     * @return HotSession
     */
    public function setIsLocked($isLocked)
    {
        $this->isLocked = $isLocked;
        return $this;
    }

    /**
     * @return HotSessionInfo
     */
    public function getSessionInfo()
    {
        return $this->sessionInfo;
    }

    /**
     * @param mixed $sessionInfo
     * @return HotSession
     */
    public function setSessionInfo($sessionInfo)
    {
        $this->sessionInfo = $sessionInfo;
        return $this;
    }

}