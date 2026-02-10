<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Country
 *
 * @ORM\Table(name="Country")
 * @ORM\Entity()
 */
class Country
{
    const UNITED_STATES = 230;
    const UK = 229;
    const RUSSIA = 179;

    /**
     * @var integer
     *
     * @ORM\Column(name="CountryID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $countryid;

    /**
     * @var string
     *
     * @ORM\Column(name="Name", type="string", length=80, nullable=false)
     */
    protected $name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="HaveStates", type="boolean", nullable=false)
     */
    protected $havestates;

    /**
     * @var string
     *
     * @ORM\Column(name="Code", type="string", length=2, nullable=true)
     */
    protected $code;



    /**
     * Get countryid
     *
     * @return integer 
     */
    public function getCountryid()
    {
        return $this->countryid;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Country
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
     * Set havestates
     *
     * @param boolean $havestates
     * @return Country
     */
    public function setHavestates($havestates)
    {
        $this->havestates = $havestates;
    
        return $this;
    }

    /**
     * Get havestates
     *
     * @return boolean 
     */
    public function getHavestates()
    {
        return $this->havestates;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Country
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

    function __toString()
    {
        return $this->getName();
    }


}