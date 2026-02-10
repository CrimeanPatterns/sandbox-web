<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Providerproperty
 *
 * @ORM\Table(name="ProviderProperty")
 * @ORM\Entity()
 */
class Providerproperty
{
    /**
     * @var integer
     *
     * @ORM\Column(name="ProviderPropertyID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $providerpropertyid;

    /**
     * @var string
     *
     * @ORM\Column(name="Name", type="string", length=80, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="Code", type="string", length=40, nullable=false)
     */
    protected $code;

    /**
     * @var integer
     *
     * @ORM\Column(name="SortIndex", type="integer", nullable=false)
     */
    protected $sortindex;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Required", type="boolean", nullable=false)
     */
    protected $required = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="Kind", type="integer", nullable=true)
     */
    protected $kind;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Visible", type="boolean", nullable=false)
     */
    protected $visible = true;

    /**
     * @var Provider
     *
     * @ORM\ManyToOne(targetEntity="Provider", inversedBy="properties")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;



    /**
     * Get providerpropertyid
     *
     * @return integer 
     */
    public function getProviderpropertyid()
    {
        return $this->providerpropertyid;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Providerproperty
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Providerproperty
     */
    public function setCode($code)
    {
        $this->code = $code;
    
        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set sortindex
     *
     * @param integer $sortindex
     * @return Providerproperty
     */
    public function setSortindex($sortindex)
    {
        $this->sortindex = $sortindex;
    
        return $this;
    }

    /**
     * Get sortindex
     *
     * @return integer 
     */
    public function getSortindex()
    {
        return $this->sortindex;
    }

    /**
     * Set required
     *
     * @param boolean $required
     * @return Providerproperty
     */
    public function setRequired($required)
    {
        $this->required = $required;
    
        return $this;
    }

    /**
     * Get required
     *
     * @return boolean 
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * Set kind
     *
     * @param integer $kind
     * @return Providerproperty
     */
    public function setKind($kind)
    {
        $this->kind = $kind;
    
        return $this;
    }

    /**
     * Get kind
     *
     * @return integer
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * Set visible
     *
     * @param boolean $visible
     * @return Providerproperty
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;
    
        return $this;
    }

    /**
     * Get visible
     *
     * @return boolean 
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set providerid
     *
     * @param \AwardWallet\MainBundle\Entity\Provider $providerid
     * @return Providerproperty
     */
    public function setProviderid(\AwardWallet\MainBundle\Entity\Provider $providerid = null)
    {
        $this->providerid = $providerid;
    
        return $this;
    }

    /**
     * Get providerid
     *
     * @return \AwardWallet\MainBundle\Entity\Provider 
     */
    public function getProviderid()
    {
        return $this->providerid;
    }
}