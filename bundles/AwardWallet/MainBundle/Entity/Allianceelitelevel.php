<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Allianceelitelevel
 *
 * @ORM\Table(name="AllianceEliteLevel")
 * @ORM\Entity
 */
class Allianceelitelevel
{
    /**
     * @var integer
     *
     * @ORM\Column(name="AllianceEliteLevelID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $allianceelitelevelid;

    /**
     * @var integer
     *
     * @ORM\Column(name="Rank", type="integer", nullable=false)
     */
    protected $rank;

    /**
     * @var string
     *
     * @ORM\Column(name="Name", type="string", length=80, nullable=false)
     */
    protected $name;

    /**
     * @var Alliance
     *
     * @ORM\ManyToOne(targetEntity="Alliance", inversedBy="Levels")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AllianceID", referencedColumnName="AllianceID")
     * })
     */
    protected $allianceid;

	/**
	 * @var EliteLevel
	 *
	 * @ORM\OneToMany(targetEntity="Elitelevel", mappedBy="allianceelitelevelid")
	 */
	protected $EliteLevels;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EliteLevels = new ArrayCollection();
	}

    /**
     * Get allianceelitelevelid
     *
     * @return integer 
     */
    public function getAllianceelitelevelid()
    {
        return $this->allianceelitelevelid;
    }

    /**
     * Set rank
     *
     * @param integer $rank
     * @return Allianceelitelevel
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
     * Set name
     *
     * @param string $name
     * @return Allianceelitelevel
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
     * Set allianceid
     *
     * @param Alliance $allianceid
     * @return Allianceelitelevel
     */
    public function setAllianceid(Alliance $allianceid = null)
    {
        $this->allianceid = $allianceid;
    
        return $this;
    }

    /**
     * Get allianceid
     *
     * @return Alliance
     */
    public function getAllianceid()
    {
        return $this->allianceid;
    }

	/**
	 * @return EliteLevel
	 */
	public function getEliteLevels()
	{
		return $this->EliteLevels;
	}

	/**
	 * @param EliteLevel[] $EliteLevels
	 */
	public function setEliteLevels($EliteLevels)
	{
		$this->EliteLevels = $EliteLevels;
	}
}