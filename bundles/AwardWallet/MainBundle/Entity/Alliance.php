<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Alliance
 *
 * @ORM\Table(name="Alliance")
 * @ORM\Entity
 */
class Alliance
{
    /**
     * @var integer
     *
     * @ORM\Column(name="AllianceID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $allianceid;

    /**
     * @var string
     *
     * @ORM\Column(name="Name", type="string", length=250, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="Alias", type="string", length=20, nullable=false)
     */
    protected $alias;

	/**
	 * @var Allianceelitelevel[]|ArrayCollection
	 *
	 * @ORM\OneToMany(targetEntity="Allianceelitelevel", mappedBy="allianceid", cascade={"persist", "remove"})
	 */
	protected $Levels;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->Levels = new ArrayCollection();
	}

	/**
     * Get allianceid
     *
     * @return integer 
     */
    public function getAllianceid()
    {
        return $this->allianceid;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Alliance
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
     * Set alias
     *
     * @param string $alias
     * @return Alliance
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    
        return $this;
    }

    /**
     * Get alias
     *
     * @return string 
     */
    public function getAlias()
    {
        return $this->alias;
    }

	/**
	 * @return Allianceelitelevel[]
	 */
	public function getLevels()
	{
		return $this->Levels;
	}

	/**
	 * @param Allianceelitelevel[] $levels
	 */
	public function setLevels($levels)
	{
		$this->Levels = $levels;
	}

	/**
	 * @param Allianceelitelevel $level
	 */
	public function addLevel(Allianceelitelevel $level)
	{
		$this->Levels[] = $level;
	}

	/**
	 * @param Allianceelitelevel $level
	 */
	public function removeLevel(Allianceelitelevel $level)
	{
		$this->Levels->removeElement($level);
	}

}