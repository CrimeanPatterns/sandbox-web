<?php


namespace AwardWallet\Common\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\EmbeddedDocument
 * @MongoDB\Indexes({
 *   @MongoDB\Index(keys={"sessionId"="asc"}),
 *   @MongoDB\UniqueIndex(keys={"host"="asc", "port"="asc", "sessionId"="asc"})
 * })
 */
class   HotSessionInfo
{

    /** @MongoDB\Field(type="string") */
    private $host;

    /** @MongoDB\Field(type="string") */
    private $sessionId;

    /** @MongoDB\Field(type="int") */
    private $port;

    /** @MongoDB\Field(type="string") */
    private $share;

    /** @MongoDB\Field(type="hash") */
    private $context;

    /** @MongoDB\Field(type="string") */
    private $browserFamily;

    /** @MongoDB\Field(type="int") */
    private $browserVersion;

    /** @MongoDB\Field(type="string") */
    private $path;

    /** @MongoDB\Field(type="int") */
    private $startTime;

    public function __construct(
        string $host,
        int $port,
        string $sessionId,
        string $share,
        string $browserFamily,
        string $browserVersion,
        string $path,
        ?int $startTime = null,
        ?array $context = []
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->sessionId = $sessionId;
        $this->share = $share;
        $this->browserFamily = $browserFamily;
        $this->browserVersion = $browserVersion;
        $this->path = $path;
        if ($this->startTime === null) {
            $this->startTime = time();
        }
        $this->context = $context;
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return mixed
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return mixed
     */
    public function getShare()
    {
        return $this->share;
    }

    /**
     * @return array|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * @return mixed
     */
    public function getBrowserFamily()
    {
        return $this->browserFamily;
    }

    /**
     * @return mixed
     */
    public function getBrowserVersion()
    {
        return $this->browserVersion;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

}