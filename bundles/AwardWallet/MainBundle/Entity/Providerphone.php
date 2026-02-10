<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Providerphone
 *
 * @ORM\Table(name="ProviderPhone")
 * @ORM\Entity()
 */
class Providerphone
{
    /**
     * @var integer
     *
     * @ORM\Column(name="ProviderPhoneID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $providerphoneid;

    /**
     * @var string
     *
     * @ORM\Column(name="Phone", type="string", length=80, nullable=false)
     */
    protected $phone;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Paid", type="boolean", nullable=true)
     */
    protected $paid;

    /**
     * @var boolean
     *
     * @ORM\Column(name="PhoneFor", type="boolean", nullable=false)
     */
    protected $phonefor;

    /**
     * @var integer
     *
     * @ORM\Column(name="RegionID", type="integer", nullable=true)
     */
    protected $regionid;

    /**
     * @var string
     *
     * @ORM\Column(name="Comment", type="string", length=16000, nullable=true)
     */
    protected $comment;

    /**
     * @var boolean
     *
     * @ORM\Column(name="DefaultPhone", type="boolean", nullable=true)
     */
    protected $defaultphone;

    /**
     * @var string
     *
     * @ORM\Column(name="DisplayNote", type="string", length=50, nullable=true)
     */
    protected $displaynote;

    /**
     * @var integer
     *
     * @ORM\Column(name="CheckedBy", type="integer", nullable=true)
     */
    protected $checkedby;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="CheckedDate", type="datetime", nullable=true)
     */
    protected $checkeddate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Valid", type="boolean", nullable=true)
     */
    protected $valid;

    /**
     * @var Provider
     *
     * @ORM\ManyToOne(targetEntity="Provider", inversedBy="")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * @var \Elitelevel
     *
     * @ORM\ManyToOne(targetEntity="Elitelevel")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="EliteLevelID", referencedColumnName="EliteLevelID")
     * })
     */
    protected $elitelevelid;



    /**
     * Get providerphoneid
     *
     * @return integer 
     */
    public function getProviderphoneid()
    {
        return $this->providerphoneid;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return Providerphone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    
        return $this;
    }

    /**
     * Get phone
     *
     * @return string 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set paid
     *
     * @param boolean $paid
     * @return Providerphone
     */
    public function setPaid($paid)
    {
        $this->paid = $paid;
    
        return $this;
    }

    /**
     * Get paid
     *
     * @return boolean 
     */
    public function getPaid()
    {
        return $this->paid;
    }

    /**
     * Set phonefor
     *
     * @param boolean $phonefor
     * @return Providerphone
     */
    public function setPhonefor($phonefor)
    {
        $this->phonefor = $phonefor;
    
        return $this;
    }

    /**
     * Get phonefor
     *
     * @return boolean 
     */
    public function getPhonefor()
    {
        return $this->phonefor;
    }

    /**
     * Set regionid
     *
     * @param integer $regionid
     * @return Providerphone
     */
    public function setRegionid($regionid)
    {
        $this->regionid = $regionid;
    
        return $this;
    }

    /**
     * Get regionid
     *
     * @return integer 
     */
    public function getRegionid()
    {
        return $this->regionid;
    }

    /**
     * Set comment
     *
     * @param string $comment
     * @return Providerphone
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    
        return $this;
    }

    /**
     * Get comment
     *
     * @return string 
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set defaultphone
     *
     * @param boolean $defaultphone
     * @return Providerphone
     */
    public function setDefaultphone($defaultphone)
    {
        $this->defaultphone = $defaultphone;
    
        return $this;
    }

    /**
     * Get defaultphone
     *
     * @return boolean 
     */
    public function getDefaultphone()
    {
        return $this->defaultphone;
    }

    /**
     * Set displaynote
     *
     * @param string $displaynote
     * @return Providerphone
     */
    public function setDisplaynote($displaynote)
    {
        $this->displaynote = $displaynote;
    
        return $this;
    }

    /**
     * Get displaynote
     *
     * @return string 
     */
    public function getDisplaynote()
    {
        return $this->displaynote;
    }

    /**
     * Set checkedby
     *
     * @param integer $checkedby
     * @return Providerphone
     */
    public function setCheckedby($checkedby)
    {
        $this->checkedby = $checkedby;
    
        return $this;
    }

    /**
     * Get checkedby
     *
     * @return integer 
     */
    public function getCheckedby()
    {
        return $this->checkedby;
    }

    /**
     * Set checkeddate
     *
     * @param \DateTime $checkeddate
     * @return Providerphone
     */
    public function setCheckeddate($checkeddate)
    {
        $this->checkeddate = $checkeddate;
    
        return $this;
    }

    /**
     * Get checkeddate
     *
     * @return \DateTime 
     */
    public function getCheckeddate()
    {
        return $this->checkeddate;
    }

    /**
     * Set valid
     *
     * @param boolean $valid
     * @return Providerphone
     */
    public function setValid($valid)
    {
        $this->valid = $valid;
    
        return $this;
    }

    /**
     * Get valid
     *
     * @return boolean 
     */
    public function getValid()
    {
        return $this->valid;
    }

    /**
     * Set providerid
     *
     * @param \AwardWallet\MainBundle\Entity\Provider $providerid
     * @return Providerphone
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
     * Set elitelevelid
     *
     * @param \AwardWallet\MainBundle\Entity\Elitelevel $elitelevelid
     * @return Providerphone
     */
    public function setElitelevelid(\AwardWallet\MainBundle\Entity\Elitelevel $elitelevelid = null)
    {
        $this->elitelevelid = $elitelevelid;
    
        return $this;
    }

    /**
     * Get elitelevelid
     *
     * @return \AwardWallet\MainBundle\Entity\Elitelevel 
     */
    public function getElitelevelid()
    {
        return $this->elitelevelid;
    }
}