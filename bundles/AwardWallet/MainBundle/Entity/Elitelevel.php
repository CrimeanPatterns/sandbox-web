<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Elitelevel
 *
 * @ORM\Table(name="EliteLevel")
 * @ORM\Entity()
 */
class Elitelevel
{
    /**
     * @var integer
     *
     * @ORM\Column(name="EliteLevelID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $elitelevelid;

    /**
     * @var integer
     *
     * @ORM\Column(name="Rank", type="integer", nullable=false)
     */
    protected $rank;

    /**
     * @var string
     *
     * @ORM\Column(name="CustomerSupportPhone", type="string", length=70, nullable=true)
     */
    protected $customersupportphone;

    /**
     * @var boolean
     *
     * @ORM\Column(name="NoElitePhone", type="boolean", nullable=true)
     */
    protected $noelitephone;

    /**
     * @var string
     *
     * @ORM\Column(name="Name", type="string", length=50, nullable=true)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="Description", type="string", length=2000, nullable=true)
     */
    protected $description;

    /**
     * @var Provider
     *
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * @var Allianceelitelevel
     *
     * @ORM\ManyToOne(targetEntity="Allianceelitelevel", inversedBy="EliteLevels")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AllianceEliteLevelID", referencedColumnName="AllianceEliteLevelID")
     * })
     */
    protected $allianceelitelevelid;



    /**
     * Get elitelevelid
     *
     * @return integer 
     */
    public function getElitelevelid()
    {
        return $this->elitelevelid;
    }

    /**
     * Set rank
     *
     * @param integer $rank
     * @return Elitelevel
     */
    public function setRank($rank)
    {
        $this->rank = $rank;
    
        return $this;
    }

    /**
     * Get rank
     *
     * @return integer 
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Set customersupportphone
     *
     * @param string $customersupportphone
     * @return Elitelevel
     */
    public function setCustomersupportphone($customersupportphone)
    {
        $this->customersupportphone = $customersupportphone;
    
        return $this;
    }

    /**
     * Get customersupportphone
     *
     * @return string 
     */
    public function getCustomersupportphone()
    {
        return $this->customersupportphone;
    }

    /**
     * Set noelitephone
     *
     * @param boolean $noelitephone
     * @return Elitelevel
     */
    public function setNoelitephone($noelitephone)
    {
        $this->noelitephone = $noelitephone;
    
        return $this;
    }

    /**
     * Get noelitephone
     *
     * @return boolean 
     */
    public function getNoelitephone()
    {
        return $this->noelitephone;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Elitelevel
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
     * Set description
     *
     * @param string $description
     * @return Elitelevel
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set providerid
     *
     * @param \AwardWallet\MainBundle\Entity\Provider $providerid
     * @return Elitelevel
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

    /**
     * Set allianceelitelevelid
     *
     * @param \AwardWallet\MainBundle\Entity\Allianceelitelevel $allianceelitelevelid
     * @return Elitelevel
     */
    public function setAllianceelitelevelid(\AwardWallet\MainBundle\Entity\Allianceelitelevel $allianceelitelevelid = null)
    {
        $this->allianceelitelevelid = $allianceelitelevelid;
    
        return $this;
    }

    /**
     * Get allianceelitelevelid
     *
     * @return \AwardWallet\MainBundle\Entity\Allianceelitelevel 
     */
    public function getAllianceelitelevelid()
    {
        return $this->allianceelitelevelid;
    }
}