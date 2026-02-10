<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ProviderCountry")
 * @ORM\Entity()
 */
class Providercountry
{
    /**
     * @ORM\Column(name="ProviderCountryID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $providerCountryId;

    /**
     * @var Provider
     *
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerId;

    /**
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CountryID", referencedColumnName="CountryID")
     * })
     */
    protected $countryId;

    /**
     * @var string
     * 
     * @ORM\Column(name="Site", type="string", nullable=false)
     */
    protected $site;

    /**
     * @var string
     * 
     * @ORM\Column(name="LoginURL", type="string", nullable=false)
     */
    protected $loginUrl;

    /**
     * @var string
     * 
     * @ORM\Column(name="LoginCaption", type="string", nullable=false)
     */
    protected $loginCaption;

    /**
     * @return mixed
     */
    public function getProviderCountryId()
    {
        return $this->providerCountryId;
    }

    /**
     * @param mixed $providerCountryId
     *
     * @return Providercountry
     */
    public function setProviderCountryId($providerCountryId)
    {
        $this->providerCountryId = $providerCountryId;
        return $this;
    }

    /**
     * @return Provider
     */
    public function getProviderId()
    {
        return $this->providerId;
    }

    /**
     * @param Provider $providerId
     *
     * @return Providercountry
     */
    public function setProviderId($providerId)
    {
        $this->providerId = $providerId;
        return $this;
    }

    /**
     * @return Country
     */
    public function getCountryId()
    {
        return $this->countryId;
    }

    /**
     * @param Country $countryId
     *
     * @return Providercountry
     */
    public function setCountryId($countryId)
    {
        $this->countryId = $countryId;
        return $this;
    }

    /**
     * @return string
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * @param string $site
     *
     * @return Providercountry
     */
    public function setSite($site)
    {
        $this->site = $site;
        return $this;
    }

    /**
     * @return string
     */
    public function getLoginUrl()
    {
        return $this->loginUrl;
    }

    /**
     * @param string $loginUrl
     *
     * @return Providercountry
     */
    public function setLoginUrl($loginUrl)
    {
        $this->loginUrl = $loginUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getLoginCaption()
    {
        return $this->loginCaption;
    }

    /**
     * @param string $loginCaption
     *
     * @return Providercountry
     */
    public function setLoginCaption($loginCaption)
    {
        $this->loginCaption = $loginCaption;
        return $this;
    }
}