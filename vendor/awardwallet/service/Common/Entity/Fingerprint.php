<?php

namespace AwardWallet\Common\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="Fingerprint")
 * @ORM\Entity()
 */
class Fingerprint
{

    /**
     * @var int
     * @ORM\Column(name="FingerprintID", type="integer", nullable=false)
     * @ORM\Id
     */
    private $id;
    /**
     * @var string
     * @ORM\Column(name="Hash", type="string", length=80, nullable=false)
     */
    private $hash;
    /**
     * @var ?string
     * @ORM\Column(name="BrowserFamily", type="string", length=80)
     */
    private $browserFamily;
    /**
     * @var ?int
     * @ORM\Column(name="BrowserVersion", type="integer")
     */
    private $browserVersion;
    /**
     * @var string
     * @ORM\Column(name="Platform", type="string", length=80, nullable=false)
     */
    private $platform;
    /**
     * @var bool
     * @ORM\Column(name="IsMobile", type="boolean", nullable=false)
     */
    private $isMobile;
    /**
     * @var array
     * @ORM\Column(name="Fingerprint", type="json", nullable=false)
     */
    private $fingerprint;

    public function getId(): int
    {
        return $this->id;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getBrowserFamily(): ?string
    {
        return $this->browserFamily;
    }

    public function getBrowserVersion(): ?int
    {
        return $this->browserVersion;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function isMobile(): bool
    {
        return $this->isMobile;
    }

    public function getFingerprint(): array
    {
        return $this->fingerprint;
    }

    public function getUseragent() : string
    {
        return $this->fingerprint['fp2']['userAgent'];
    }

    public function getScreenWidth() : int
    {
        return $this->fingerprint['fp2']['screen']['width'];
    }

    public function getScreenHeight() : int
    {
        return $this->fingerprint['fp2']['screen']['height'];
    }

}
