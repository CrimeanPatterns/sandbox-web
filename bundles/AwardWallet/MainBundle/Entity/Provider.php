<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Provider
 *
 * @ORM\Table(name="Provider")
 * @ORM\Entity()
 */
class Provider
{

    const CHASE_ID = 87;
    const AMEX_ID = 84;
    const CITI_ID = 364;

    const OAUTH_PROVIDERS = [75, 104];

    const BIG3_PROVIDERS = [7, 145, 26, 288, 16];

    /**
     * @var integer
     *
     * @ORM\Column(name="ProviderID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $providerid;

    /**
     * @var string
     *
     * @ORM\Column(name="Name", type="string", length=200, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="Code", type="string", length=20, nullable=true)
     */
    protected $code;

    /**
     * @var integer
     *
     * @ORM\Column(name="Kind", type="integer", nullable=false)
     */
    protected $kind = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="Engine", type="integer", nullable=false)
     */
    protected $engine = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="LoginCaption", type="string", length=255, nullable=false)
     */
    protected $logincaption;

    /**
     * @var bool
     *
     * @ORM\Column(name="LoginRequired", type="boolean", nullable=false)
     */
    protected $loginRequired = true;

    /**
     * @var string
     *
     * @ORM\Column(name="DisplayName", type="string", length=100, nullable=true)
     */
    protected $displayname;

    /**
     * @var string
     *
     * @ORM\Column(name="ProgramName", type="string", length=80, nullable=false)
     */
    protected $programname;

    /**
     * @var integer
     *
     * @ORM\Column(name="LoginMinSize", type="integer", nullable=false)
     */
    protected $loginminsize = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="LoginMaxSize", type="integer", nullable=false)
     */
    protected $loginmaxsize = 255;

    /**
     * @var string
     *
     * @ORM\Column(name="PasswordCaption", type="string", length=80, nullable=true)
     */
    protected $passwordcaption;

    /**
     * @var integer
     *
     * @ORM\Column(name="PasswordMinSize", type="integer", nullable=false)
     */
    protected $passwordminsize = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="PasswordMaxSize", type="integer", nullable=false)
     */
    protected $passwordmaxsize = 80;

    /**
     * @var boolean
     *
     * @ORM\Column(name="CanRetrievePassword", type="boolean", nullable=true)
     */
    protected $canretrievepassword = false;

    /**
     * @var string
     *
     * @ORM\Column(name="Site", type="string", length=80, nullable=false)
     */
    protected $site;

    /**
     * @var string
     *
     * @ORM\Column(name="LoginURL", type="string", length=512, nullable=false)
     */
    protected $loginurl;

    /**
     * @var Providercountry[]|Collection
     *
     * @ORM\OneToMany(targetEntity="AwardWallet\MainBundle\Entity\Providercountry", mappedBy="providerId", cascade={"persist", "remove"})
     */
    protected $countries;

    /**
     * @var integer
     *
     * @ORM\Column(name="State", type="integer", nullable=false)
     */
    protected $state = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationdate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="EnableDate", type="datetime", nullable=false)
     */
    protected $enabledate;

    /**
     * @var string
     *
     * @ORM\Column(name="Login2Caption", type="string", length=80, nullable=true)
     */
    protected $login2caption;

    /**
     * @var bool
     *
     * @ORM\Column(name="Login2Required", type="boolean", nullable=false)
     */
    protected $login2Required = true;

        /**
     * @var bool
     *
     * @ORM\Column(name="Login2AsCountry", type="boolean", nullable=false)
     */
    protected $login2AsCountry = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="Login2MinSize", type="integer", nullable=false)
     */
    protected $login2minsize = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="Login2MaxSize", type="integer", nullable=false)
     */
    protected $login2maxsize = 80;

    /**
     * Max size for input
     * 
     * @var int
     */
    protected $login3MaxSize = 40;

    /**
     * @var integer
     *
     * @ORM\Column(name="AutoLogin", type="integer", nullable=false)
     */
    protected $autologin = AUTOLOGIN_DISABLED;

    /**
     * @var string
     *
     * @ORM\Column(name="OneTravelCode", type="string", length=2, nullable=true)
     */
    protected $onetravelcode = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="OneTravelName", type="string", length=80, nullable=true)
     */
    protected $onetravelname;

    /**
     * @var integer
     *
     * @ORM\Column(name="OneTravelID", type="integer", nullable=true)
     */
    protected $onetravelid;

    /**
     * @var boolean
     *
     * @ORM\Column(name="CanCheck", type="boolean", nullable=false)
     */
    protected $cancheck = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="CanCheckBalance", type="boolean", nullable=false)
     */
    protected $cancheckbalance = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="CanCheckConfirmation", type="integer", nullable=false)
     */
    protected $cancheckconfirmation = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="CanCheckItinerary", type="boolean", nullable=false)
     */
    protected $cancheckitinerary = false;

    /**
     * @var string
     *
     * @ORM\Column(name="ExpirationDateNote", type="text", nullable=true)
     */
    protected $expirationdatenote;

    /**
     * @var string
     *
     * @ORM\Column(name="RenewNote", type="text", nullable=true)
     */
    protected $renewnote;

    /**
     * @var string
     *
     * @ORM\Column(name="TradeText", type="text", nullable=true)
     */
    protected $tradetext;

    /**
     * @var integer
     *
     * @ORM\Column(name="TradeMin", type="integer", nullable=false)
     */
    protected $trademin = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="RedirectByHTTPS", type="boolean", nullable=false)
     */
    protected $redirectbyhttps = true;

    /**
     * @var string
     *
     * @ORM\Column(name="DefaultRegion", type="string", length=80, nullable=true)
     */
    protected $defaultregion;

    /**
     * @var string
     *
     * @ORM\Column(name="BalanceFormat", type="string", length=60, nullable=true)
     */
    protected $balanceformat;

    /**
     * @var boolean
     *
     * @ORM\Column(name="AllowFloat", type="boolean", nullable=false)
     */
    protected $allowfloat = false;

    /**
     * @var string
     *
     * @ORM\Column(name="ShortName", type="string", length=255, nullable=false)
     */
    protected $shortname;

    /**
     * @var integer
     *
     * @ORM\Column(name="Difficulty", type="integer", nullable=false)
     */
    protected $difficulty = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="ImageURL", type="string", length=512, nullable=true)
     */
    protected $imageurl;

    /**
     * @var string
     *
     * @ORM\Column(name="ClickURL", type="string", length=512, nullable=true)
     */
    protected $clickurl;

    /**
     * @var boolean
     *
     * @ORM\Column(name="WSDL", type="boolean", nullable=false)
     */
    protected $wsdl = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="CanCheckExpiration", type="integer", nullable=false)
     */
    protected $cancheckexpiration = false;

    /**
     * @var bool
     * @ORM\Column(name="DontSendEmailsSubaccExpDate", type="integer", nullable=false)
     */
    protected $dontSendEmailsSubaccExpDate = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="FAQ", type="integer", nullable=true)
     */
    protected $faq;

    /**
     * @var string
     *
     * @ORM\Column(name="ProviderGroup", type="string", length=20, nullable=true)
     */
    protected $providergroup;

    /**
     * @var string
     *
     * @ORM\Column(name="ExpirationUnknownNote", type="string", length=2000, nullable=true)
     */
    protected $expirationunknownnote;

    /**
     * @var boolean
     *
     * @ORM\Column(name="CustomDisplayName", type="boolean", nullable=false)
     */
    protected $customdisplayname = false;

    /**
     * @var string
     *
     * @ORM\Column(name="BarCode", type="string", length=20, nullable=true)
     */
    protected $barcode;

    /**
     * @var boolean
     *
     * @ORM\Column(name="MobileAutoLogin", type="boolean", nullable=false)
     */
    protected $mobileautologin = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Corporate", type="boolean", nullable=false)
     */
    protected $corporate = false;

    /**
     * @var Currency
     *
     * @ORM\ManyToOne(targetEntity="Currency")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="Currency", referencedColumnName="CurrencyID")
     * })
     */
    protected $currency;

    /**
     * @var Popularity
     * @ORM\OneToMany(targetEntity="Popularity", mappedBy="provider", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $popularity;

    /**
     * @var integer
     *
     * @ORM\Column(name="DeepLinking", type="integer", nullable=false)
     */
    protected $deeplinking = 2;

    /**
     * @var boolean
     *
     * @ORM\Column(name="Questions", type="boolean", nullable=false)
     */
    protected $questions = false;

    /**
     * @var string
     *
     * @ORM\Column(name="Note", type="text", nullable=true)
     */
    protected $note;

    /**
     * @var integer
     *
     * @ORM\Column(name="TimesRequested", type="integer", nullable=true)
     */
    protected $timesrequested;

    /**
     * @var boolean
     *
     * @ORM\Column(name="PasswordRequired", type="boolean", nullable=false)
     */
    protected $passwordrequired = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="Tier", type="integer", nullable=true)
     */
    protected $tier;

    /**
     * @var integer
     *
     * @ORM\Column(name="Severity", type="integer", nullable=true)
     */
    protected $severity;

    /**
     * @var integer
     *
     * @ORM\Column(name="ResponseTime", type="integer", nullable=true)
     */
    protected $responsetime;

    /**
     * @var integer
     *
     * @ORM\Column(name="EliteLevelsCount", type="integer", nullable=true)
     */
    protected $elitelevelscount;

    /**
     * @var integer
     *
     * @ORM\Column(name="RSlaEventID", type="integer", nullable=true)
     */
    protected $rslaeventid;

    /**
     * @var boolean
     *
     * @ORM\Column(name="CanCheckCancelled", type="boolean", nullable=true)
     */
    protected $cancheckcancelled = false;

    /**
     * @var float
     *
     * @ORM\Column(name="AAADiscount", type="float", nullable=true)
     */
    protected $aaadiscount;

    /**
     * @var string
     *
     * @ORM\Column(name="Login3Caption", type="string", length=80, nullable=true)
     */
    protected $login3caption;

    /**
     * @var bool
     *
     * @ORM\Column(name="Login3Required", type="boolean", nullable=false)
     */
    protected $login3Required = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="CheckInBrowser", type="integer", nullable=false)
     */
    protected $checkinbrowser = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="CheckInMobileBrowser", type="boolean", nullable=false)
     */
    protected $checkinmobilebrowser = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="Accounts", type="integer", nullable=false)
     */
    protected $accounts = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="AbAccounts", type="integer", nullable=false)
     */
    protected $abaccounts = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="KeyWords", type="string", length=2000, nullable=true)
     */
    protected $keywords;

    /**
     * @var string
     *
     * @ORM\Column(name="StopKeyWords", type="string", length=2000, nullable=true)
     */
    protected $stopKeywords;

    /**
     * @var float
     *
     * @ORM\Column(name="AvgDurationWithoutPlans", type="float", nullable=true)
     */
    protected $avgdurationwithoutplans;

    /**
     * @var float
     *
     * @ORM\Column(name="AvgDurationWithPlans", type="float", nullable=true)
     */
    protected $avgdurationwithplans;

    /**
     * @var boolean
     *
     * @ORM\Column(name="CanMarkCoupons", type="boolean", nullable=false)
     */
    protected $canmarkcoupons = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="CanParseCardImages", type="boolean", nullable=false)
     */
    protected $canParseCardImages = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="CanDetectCreditCards", type="boolean", nullable=false)
     */
    protected $canDetectCreditCards = false;

    /**
     * @var string
     *
     * @ORM\Column(name="Warning", type="string", length=250, nullable=true)
     */
    protected $warning;

    /**
     * @var boolean
     *
     * @ORM\Column(name="CanCheckHistory", type="boolean", nullable=false)
     */
    protected $cancheckhistory = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ExpirationAlwaysKnown", type="boolean", nullable=false)
     */
    protected $expirationalwaysknown = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="RequestsPerMinute", type="integer", nullable=true)
     */
    protected $requestsperminute;

    /**
     * @var integer
     *
     * @ORM\Column(name="CacheVersion", type="integer", nullable=false)
     */
    protected $cacheversion = 1;

    /**
     * @var boolean
     *
     * @ORM\Column(name="CanCheckNoItineraries", type="boolean", nullable=false)
     */
    protected $canchecknoitineraries = false;

    /**
     * @var string
     *
     * @ORM\Column(name="PlanEmail", type="string", length=120, nullable=true)
     */
    protected $planemail;

    /**
     * @var string
     *
     * @ORM\Column(name="InternalNote", type="text", nullable=true)
     */
    protected $internalnote;

    /**
     * @var boolean
     *
     * @ORM\Column(name="CalcEliteLevelExpDate", type="boolean", nullable=false)
     */
    protected $calcelitelevelexpdate = false;

    /**
     * @var int
     *
     * @ORM\Column(name="ItineraryAutologin", type="integer", nullable=true)
     */
    protected $itineraryautologin = ITINERARY_AUTOLOGIN_DISABLED;

    /**
     * @var string
     *
     * @ORM\Column(name="EliteProgramComment", type="string", length=2000, nullable=true)
     */
    protected $eliteprogramcomment;

    /**
     * @var boolean
     *
     * @ORM\Column(name="CanScanEmail", type="boolean", nullable=false)
     */
    protected $canscanemail = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="Category", type="integer", nullable=true)
     */
    protected $category = 3;

    /**
     * @var Alliance
     *
     * @ORM\ManyToOne(targetEntity="Alliance")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AllianceID", referencedColumnName="AllianceID")
     * })
     */
    protected $allianceid;

    /**
     * @var ProviderProperty[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Providerproperty", mappedBy="providerid", cascade={"persist", "remove"})
     */
    protected $properties;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $AutoLoginIE = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $AutoLoginSafari = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $AutoLoginChrome = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $AutoLoginFirefox = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="Goal", type="integer", nullable=true)
     */
    protected $Goal;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="CanSavePassword", type="boolean", nullable=true)
	 */
	protected $CanSavePassword;

	/**
     * @var integer
     *
     * @ORM\Column(name="CanReceiveEmail", type="boolean", nullable=false)
     */
    protected $CanReceiveEmail;

    /**
     * @var boolean
     *
     * @ORM\Column(name="CanCheckFiles", type="boolean", nullable=false)
     */
    protected $CanCheckFiles = false;

    /**
     * @var string
     *
     * @ORM\Column(name="IATACode", type="string", nullable=true)
     */
    protected $IATACode;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="CanTransferRewards", type="boolean", nullable=false)
	 */
	protected $canTransferRewards;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="CanRegisterAccount", type="integer", nullable=true)
	 */
	protected $canRegisterAccount;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="CanBuyMiles", type="integer", nullable=true)
	 */
	protected $canBuyMiles;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="CanCheckOneTime", type="boolean", nullable=false)
	 */
	protected $canCheckOneTime;

    /**
     * @var string
     *
     * @ORM\Column(name="CheckInReminderOffsets", type="string", length=200, nullable=false)
     */
    protected $checkInReminderOffsets = '{"mail":[24],"push":[1,4,24]}';

    /**
     * @var boolean
     *
     * @ORM\Column(name="isRetail", type="boolean", nullable=false)
     */
    protected $isRetail;

    /**
     * @var string
     *
     * @ORM\Column(name="additionalInfo", type="string", nullable=true)
     */
    protected $additionalInfo;

    /**
     * @var Providerphone[]|Collection
     *
     * @ORM\OneToMany(targetEntity="AwardWallet\MainBundle\Entity\Providerphone", mappedBy="providerid")
     */
    protected $phones;

	/**
     * you can check account of this provider only one time, when adding new account. for southwest.
	 * @return boolean
	 */
	public function getCanCheckFiles()
	{
		return $this->CanCheckFiles;
	}

	/**
	 * @param boolean $CanCheckFiles
	 */
	public function setCanCheckFiles($CanCheckFiles)
	{
		$this->CanCheckFiles = $CanCheckFiles;
	}

	public function __construct() {
		$this->creationdate = new \DateTime();
        $this->properties = new ArrayCollection();
        $this->phones = new ArrayCollection();
    }

    /**
     * Get providerid
     *
     * @return integer 
     */
    public function getProviderid()
    {
        return $this->providerid;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Provider
     */
    public function setName($name)
    {
        $this->name = null === $name ? null : htmlspecialchars($name);
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return null === $this->name ? null : htmlspecialchars_decode($this->name);
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Provider
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
     * Set kind
     *
     * @param integer $kind
     * @return Provider
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
     * Set engine
     *
     * @param integer $engine
     * @return Provider
     */
    public function setEngine($engine)
    {
        $this->engine = $engine;
    
        return $this;
    }

    /**
     * Get engine
     *
     * @return integer
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * Set logincaption
     *
     * @param string $logincaption
     * @return Provider
     */
    public function setLogincaption($logincaption)
    {
        $this->logincaption = $logincaption;
    
        return $this;
    }

    /**
     * Get logincaption
     *
     * @return string 
     */
    public function getLogincaption()
    {
        return $this->logincaption;
    }

    public function isLoginRequired() : bool
    {
        return $this->loginRequired;
    }

    public function setLoginRequired(bool $loginRequired) : Provider
    {
        $this->loginRequired = $loginRequired;

        return $this;
    }

    /**
     * Set displayname
     *
     * @param string $displayname
     * @return Provider
     */
    public function setDisplayname($displayname)
    {
        $this->displayname = null === $displayname ? null : htmlspecialchars($displayname);
    
        return $this;
    }

    /**
     * Get displayname
     *
     * @return string 
     */
    public function getDisplayname()
    {
        return null === $this->displayname ? null : htmlspecialchars_decode($this->displayname);
    }

    /**
     * Set programname
     *
     * @param string $programname
     * @return Provider
     */
    public function setProgramname($programname)
    {
        $this->programname = null === $programname ? null : htmlspecialchars($programname);
    
        return $this;
    }

    /**
     * Get programname
     *
     * @return string 
     */
    public function getProgramname()
    {
        return null === $this->programname ? null : htmlspecialchars_decode($this->programname);
    }

    /**
     * Set loginminsize
     *
     * @param integer $loginminsize
     * @return Provider
     */
    public function setLoginminsize($loginminsize)
    {
        $this->loginminsize = $loginminsize;
    
        return $this;
    }

    /**
     * Get loginminsize
     *
     * @return integer 
     */
    public function getLoginminsize()
    {
        return $this->loginminsize;
    }

    /**
     * Set loginmaxsize
     *
     * @param integer $loginmaxsize
     * @return Provider
     */
    public function setLoginmaxsize($loginmaxsize)
    {
        $this->loginmaxsize = $loginmaxsize;
    
        return $this;
    }

    /**
     * Get loginmaxsize
     *
     * @return integer 
     */
    public function getLoginmaxsize()
    {
        return $this->loginmaxsize;
    }

    /**
     * Set passwordcaption
     *
     * @param string $passwordcaption
     * @return Provider
     */
    public function setPasswordcaption($passwordcaption)
    {
        $this->passwordcaption = $passwordcaption;
    
        return $this;
    }

    /**
     * Get passwordcaption
     *
     * @return string 
     */
    public function getPasswordcaption()
    {
        return $this->passwordcaption;
    }

    /**
     * Set passwordminsize
     *
     * @param integer $passwordminsize
     * @return Provider
     */
    public function setPasswordminsize($passwordminsize)
    {
        $this->passwordminsize = $passwordminsize;
    
        return $this;
    }

    /**
     * Get passwordminsize
     *
     * @return integer 
     */
    public function getPasswordminsize()
    {
        return $this->passwordminsize;
    }

    /**
     * Set passwordmaxsize
     *
     * @param integer $passwordmaxsize
     * @return Provider
     */
    public function setPasswordmaxsize($passwordmaxsize)
    {
        $this->passwordmaxsize = $passwordmaxsize;
    
        return $this;
    }

    /**
     * Get passwordmaxsize
     *
     * @return integer 
     */
    public function getPasswordmaxsize()
    {
        return $this->passwordmaxsize;
    }

    /**
     * @return boolean
     */
    public function isCanretrievepassword()
    {
        return $this->canretrievepassword;
    }

    /**
     * @param boolean $canretrievepassword
     * @return Provider
     */
    public function setCanretrievepassword($canretrievepassword)
    {
        $this->canretrievepassword = $canretrievepassword;

        return $this;
    }

    /**
     * Set site
     *
     * @param string $site
     * @return Provider
     */
    public function setSite($site)
    {
        $this->site = $site;
    
        return $this;
    }

    /**
     * Get site
     *
     * @return string 
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * Set loginurl
     *
     * @param string $loginurl
     * @return Provider
     */
    public function setLoginurl($loginurl)
    {
        $this->loginurl = $loginurl;
    
        return $this;
    }

    /**
     * Get loginurl
     *
     * @return string 
     */
    public function getLoginurl()
    {
        return $this->loginurl;
    }

    /**
     * @return Providercountry[]|Collection
     */
    public function getCountries()
    {
        return $this->countries;
    }

    /**
     * @param Providercountry[]|Collection $countries
     *
     * @return Provider
     */
    public function setCountries($countries)
    {
        $this->countries = $countries;

        return $this;
    }

    /**
     * Set state
     *
     * @param integer $state
     * @return Provider
     */
    public function setState($state)
    {
        $this->state = $state;
    
        return $this;
    }

    /**
     * Get state
     *
     * @return integer
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set creationdate
     *
     * @param \DateTime $creationdate
     * @return Provider
     */
    public function setCreationdate($creationdate)
    {
        $this->creationdate = $creationdate;
    
        return $this;
    }

    /**
     * Get creationdate
     *
     * @return \DateTime 
     */
    public function getCreationdate()
    {
        return $this->creationdate;
    }

    /**
     * Set login2caption
     *
     * @param string $login2caption
     * @return Provider
     */
    public function setLogin2caption($login2caption)
    {
        $this->login2caption = $login2caption;
    
        return $this;
    }

    /**
     * Get login2caption
     *
     * @return string 
     */
    public function getLogin2caption()
    {
        return $this->login2caption;
    }

    public function isLogin2Required() : bool
    {
        return $this->login2Required;
    }

    public function setLogin2Required(bool $login2Required) : Provider
    {
        $this->login2Required = $login2Required;

        return $this;
    }

    public function isLogin2AsCountry(): bool
    {
        return $this->login2AsCountry;
    }

    public function setLogin2AsCountry(bool $login2AsCountry): Provider
    {
        $this->login2AsCountry = $login2AsCountry;

        return $this;
    }

    /**
     * Set login2minsize
     *
     * @param integer $login2minsize
     * @return Provider
     */
    public function setLogin2minsize($login2minsize)
    {
        $this->login2minsize = $login2minsize;
    
        return $this;
    }

    /**
     * Get login2minsize
     *
     * @return integer 
     */
    public function getLogin2minsize()
    {
        return $this->login2minsize;
    }

    /**
     * Set login2maxsize
     *
     * @param integer $login2maxsize
     * @return Provider
     */
    public function setLogin2maxsize($login2maxsize)
    {
        $this->login2maxsize = $login2maxsize;
    
        return $this;
    }

    /**
     * Get login2maxsize
     *
     * @return integer 
     */
    public function getLogin2maxsize()
    {
        return $this->login2maxsize;
    }
    
    public function getLogin3Maxsize()
    {
        return $this->login3MaxSize;
    }

    /**
     * Set autologin
     *
     * @param int $autologin
     * @return Provider
     */
    public function setAutologin($autologin)
    {
        $this->autologin = $autologin;
    
        return $this;
    }

    /**
     * Get autologin
     *
     * @return integer
     */
    public function getAutologin()
    {
        return $this->autologin;
    }

    /**
     * Set onetravelcode
     *
     * @param string $onetravelcode
     * @return Provider
     */
    public function setOnetravelcode($onetravelcode)
    {
        $this->onetravelcode = $onetravelcode;
    
        return $this;
    }

    /**
     * Get onetravelcode
     *
     * @return string 
     */
    public function getOnetravelcode()
    {
        return $this->onetravelcode;
    }

    /**
     * Set onetravelname
     *
     * @param string $onetravelname
     * @return Provider
     */
    public function setOnetravelname($onetravelname)
    {
        $this->onetravelname = $onetravelname;
    
        return $this;
    }

    /**
     * Get onetravelname
     *
     * @return string 
     */
    public function getOnetravelname()
    {
        return $this->onetravelname;
    }

    /**
     * Set onetravelid
     *
     * @param integer $onetravelid
     * @return Provider
     */
    public function setOnetravelid($onetravelid)
    {
        $this->onetravelid = $onetravelid;
    
        return $this;
    }

    /**
     * Get onetravelid
     *
     * @return integer 
     */
    public function getOnetravelid()
    {
        return $this->onetravelid;
    }

    /**
     * Set cancheck
     *
     * @param boolean $cancheck
     * @return Provider
     */
    public function setCancheck($cancheck)
    {
        $this->cancheck = $cancheck;
    
        return $this;
    }

    /**
     * Get cancheck
     *
     * @return boolean
     */
    public function getCancheck()
    {
        return $this->cancheck;
    }

    /**
     * Set cancheckbalance
     *
     * @param boolean $cancheckbalance
     * @return Provider
     */
    public function setCancheckbalance($cancheckbalance)
    {
        $this->cancheckbalance = $cancheckbalance;
    
        return $this;
    }

    /**
     * Get cancheckbalance
     *
     * @return boolean
     */
    public function getCancheckbalance()
    {
        return $this->cancheckbalance;
    }

    /**
     * Set cancheckconfirmation
     *
     * @param integer $cancheckconfirmation
     * @return Provider
     */
    public function setCancheckconfirmation($cancheckconfirmation)
    {
        $this->cancheckconfirmation = $cancheckconfirmation;
    
        return $this;
    }

    /**
     * Get cancheckconfirmation
     *
     * @return integer
     */
    public function getCancheckconfirmation()
    {
        return $this->cancheckconfirmation;
    }

    /**
     * Set cancheckitinerary
     *
     * @param boolean $cancheckitinerary
     * @return Provider
     */
    public function setCancheckitinerary($cancheckitinerary)
    {
        $this->cancheckitinerary = $cancheckitinerary;
    
        return $this;
    }

    /**
     * Get cancheckitinerary
     *
     * @return boolean
     */
    public function getCancheckitinerary()
    {
        return $this->cancheckitinerary;
    }

    /**
     * Set expirationdatenote
     *
     * @param string $expirationdatenote
     * @return Provider
     */
    public function setExpirationdatenote($expirationdatenote)
    {
        $this->expirationdatenote = $expirationdatenote;
    
        return $this;
    }

    /**
     * Get expirationdatenote
     *
     * @return string 
     */
    public function getExpirationdatenote()
    {
        return $this->expirationdatenote;
    }

    /**
     * Set renewnote
     *
     * @param string $renewnote
     * @return Provider
     */
    public function setRenewnote($renewnote)
    {
        $this->renewnote = $renewnote;
    
        return $this;
    }

    /**
     * Get renewnote
     *
     * @return string 
     */
    public function getRenewnote()
    {
        return $this->renewnote;
    }

    /**
     * Set tradetext
     *
     * @param string $tradetext
     * @return Provider
     */
    public function setTradetext($tradetext)
    {
        $this->tradetext = $tradetext;
    
        return $this;
    }

    /**
     * Get tradetext
     *
     * @return string 
     */
    public function getTradetext()
    {
        return $this->tradetext;
    }

    /**
     * Set trademin
     *
     * @param integer $trademin
     * @return Provider
     */
    public function setTrademin($trademin)
    {
        $this->trademin = $trademin;
    
        return $this;
    }

    /**
     * Get trademin
     *
     * @return integer 
     */
    public function getTrademin()
    {
        return $this->trademin;
    }

    /**
     * Set redirectbyhttps
     *
     * @param boolean $redirectbyhttps
     * @return Provider
     */
    public function setRedirectbyhttps($redirectbyhttps)
    {
        $this->redirectbyhttps = $redirectbyhttps;
    
        return $this;
    }

    /**
     * Get redirectbyhttps
     *
     * @return boolean
     */
    public function getRedirectbyhttps()
    {
        return $this->redirectbyhttps;
    }

    /**
     * Set defaultregion
     *
     * @param string $defaultregion
     * @return Provider
     */
    public function setDefaultregion($defaultregion)
    {
        $this->defaultregion = $defaultregion;
    
        return $this;
    }

    /**
     * Get defaultregion
     *
     * @return string 
     */
    public function getDefaultregion()
    {
        return $this->defaultregion;
    }

    /**
     * Set balanceformat
     *
     * @param string $balanceformat
     * @return Provider
     */
    public function setBalanceformat($balanceformat)
    {
        $this->balanceformat = $balanceformat;
    
        return $this;
    }

    /**
     * Get balanceformat
     *
     * @return string 
     */
    public function getBalanceformat()
    {
        return $this->balanceformat;
    }

    /**
     * Set allowfloat
     *
     * @param boolean $allowfloat
     * @return Provider
     */
    public function setAllowfloat($allowfloat)
    {
        $this->allowfloat = $allowfloat;
    
        return $this;
    }

    /**
     * Get allowfloat
     *
     * @return boolean
     */
    public function getAllowfloat()
    {
        return $this->allowfloat;
    }

    /**
     * Set shortname
     *
     * @param string $shortname
     * @return Provider
     */
    public function setShortname($shortname)
    {
        $this->shortname = null === $shortname ? null : htmlspecialchars($shortname);
    
        return $this;
    }

    /**
     * Get shortname
     *
     * @return string 
     */
    public function getShortname()
    {
        return null === $this->shortname ? null : htmlspecialchars_decode($this->shortname);
    }

    /**
     * Set difficulty
     *
     * @param integer $difficulty
     * @return Provider
     */
    public function setDifficulty($difficulty)
    {
        $this->difficulty = $difficulty;
    
        return $this;
    }

    /**
     * Get difficulty
     *
     * @return integer 
     */
    public function getDifficulty()
    {
        return $this->difficulty;
    }

    /**
     * Set imageurl
     *
     * @param string $imageurl
     * @return Provider
     */
    public function setImageurl($imageurl)
    {
        $this->imageurl = $imageurl;
    
        return $this;
    }

    /**
     * Get imageurl
     *
     * @return string 
     */
    public function getImageurl()
    {
        return $this->imageurl;
    }

    /**
     * Set clickurl
     *
     * @param string $clickurl
     * @return Provider
     */
    public function setClickurl($clickurl)
    {
        $this->clickurl = $clickurl;
    
        return $this;
    }

    /**
     * Get clickurl
     *
     * @return string 
     */
    public function getClickurl()
    {
        return $this->clickurl;
    }

    /**
     * Set wsdl
     *
     * @param boolean $wsdl
     * @return Provider
     */
    public function setWsdl($wsdl)
    {
        $this->wsdl = $wsdl;
    
        return $this;
    }

    /**
     * Get wsdl
     *
     * @return boolean 
     */
    public function getWsdl()
    {
        return $this->wsdl;
    }

    /**
     * Set cancheckexpiration
     *
     * @param integer $cancheckexpiration
     * @return Provider
     */
    public function setCancheckexpiration($cancheckexpiration)
    {
        $this->cancheckexpiration = $cancheckexpiration;
    
        return $this;
    }

    /**
     * Get cancheckexpiration
     *
     * @return integer
     */
    public function getCancheckexpiration()
    {
        return $this->cancheckexpiration;
    }

    /**
     * @return boolean
     */
    public function isDontSendEmailsSubaccExpDate()
    {
        return $this->dontSendEmailsSubaccExpDate;
    }

    /**
     * @param boolean $dontSendEmailsSubaccExpDate
     * @return Provider
     */
    public function setDontSendEmailsSubaccExpDate($dontSendEmailsSubaccExpDate)
    {
        $this->dontSendEmailsSubaccExpDate = $dontSendEmailsSubaccExpDate;
        return $this;
    }

    /**
     * Set faq
     *
     * @param integer $faq
     * @return Provider
     */
    public function setFaq($faq)
    {
        $this->faq = $faq;
    
        return $this;
    }

    /**
     * Get faq
     *
     * @return integer 
     */
    public function getFaq()
    {
        return $this->faq;
    }

    /**
     * Set providergroup
     *
     * @param string $providergroup
     * @return Provider
     */
    public function setProvidergroup($providergroup)
    {
        $this->providergroup = $providergroup;
    
        return $this;
    }

    /**
     * Get providergroup
     *
     * @return string 
     */
    public function getProvidergroup()
    {
        return $this->providergroup;
    }

    /**
     * Set expirationunknownnote
     *
     * @param string $expirationunknownnote
     * @return Provider
     */
    public function setExpirationunknownnote($expirationunknownnote)
    {
        $this->expirationunknownnote = $expirationunknownnote;
    
        return $this;
    }

    /**
     * Get expirationunknownnote
     *
     * @return string 
     */
    public function getExpirationunknownnote()
    {
        return $this->expirationunknownnote;
    }

    /**
     * Set customdisplayname
     *
     * @param boolean $customdisplayname
     * @return Provider
     */
    public function setCustomdisplayname($customdisplayname)
    {
        $this->customdisplayname = $customdisplayname;
    
        return $this;
    }

    /**
     * Get customdisplayname
     *
     * @return boolean 
     */
    public function getCustomdisplayname()
    {
        return $this->customdisplayname;
    }

    /**
     * Set barcode
     *
     * @param string $barcode
     * @return Provider
     */
    public function setBarcode($barcode)
    {
        $this->barcode = $barcode;
    
        return $this;
    }

    /**
     * Get barcode
     *
     * @return string 
     */
    public function getBarcode()
    {
        return $this->barcode;
    }

    /**
     * Set mobileautologin
     *
     * @param boolean $mobileautologin
     *
     * @return Provider
     */
    public function setMobileautologin($mobileautologin)
    {
        $this->mobileautologin = $mobileautologin;
    
        return $this;
    }

    /**
     * Get mobileautologin
     *
     * @return boolean 
     */
    public function getMobileautologin()
    {
        return $this->mobileautologin;
    }

    /**
     * Set corporate
     *
     * @param boolean $corporate
     * @return Provider
     */
    public function setCorporate($corporate)
    {
        $this->corporate = $corporate;
    
        return $this;
    }

    /**
     * Get corporate
     *
     * @return boolean 
     */
    public function getCorporate()
    {
        return $this->corporate;
    }

    /**
     * Set currency
     *
     * @param Currency $currency
     * @return Provider
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    
        return $this;
    }

    /**
     * Get currency
     *
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set deeplinking
     *
     * @param integer $deeplinking
     * @return Provider
     */
    public function setDeeplinking($deeplinking)
    {
        $this->deeplinking = $deeplinking;
    
        return $this;
    }

    /**
     * Get deeplinking
     *
     * @return integer
     */
    public function getDeeplinking()
    {
        return $this->deeplinking;
    }

    /**
     * Set questions
     *
     * @param boolean $questions
     * @return Provider
     */
    public function setQuestions($questions)
    {
        $this->questions = $questions;
    
        return $this;
    }

    /**
     * Get questions
     *
     * @return boolean 
     */
    public function getQuestions()
    {
        return $this->questions;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return Provider
     */
    public function setNote($note)
    {
        $this->note = $note;
    
        return $this;
    }

    /**
     * Get note
     *
     * @return string 
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set timesrequested
     *
     * @param integer $timesrequested
     * @return Provider
     */
    public function setTimesrequested($timesrequested)
    {
        $this->timesrequested = $timesrequested;
    
        return $this;
    }

    /**
     * Get timesrequested
     *
     * @return integer 
     */
    public function getTimesrequested()
    {
        return $this->timesrequested;
    }

    /**
     * Set passwordrequired
     *
     * @param boolean $passwordrequired
     * @return Provider
     */
    public function setPasswordrequired($passwordrequired)
    {
        $this->passwordrequired = $passwordrequired;
    
        return $this;
    }

    /**
     * Get passwordrequired
     *
     * @return boolean 
     */
    public function getPasswordrequired()
    {
        return $this->passwordrequired;
    }

    /**
     * Set tier
     *
     * @param integer $tier
     * @return Provider
     */
    public function setTier($tier)
    {
        $this->tier = $tier;
    
        return $this;
    }

    /**
     * Get tier
     *
     * @return integer
     */
    public function getTier()
    {
        return $this->tier;
    }

    /**
     * Set severity
     *
     * @param integer $severity
     * @return Provider
     */
    public function setSeverity($severity)
    {
        $this->severity = $severity;
    
        return $this;
    }

    /**
     * Get severity
     *
     * @return integer
     */
    public function getSeverity()
    {
        return $this->severity;
    }

    /**
     * Set responsetime
     *
     * @param integer $responsetime
     * @return Provider
     */
    public function setResponsetime($responsetime)
    {
        $this->responsetime = $responsetime;
    
        return $this;
    }

    /**
     * Get responsetime
     *
     * @return integer 
     */
    public function getResponsetime()
    {
        return $this->responsetime;
    }

    /**
     * Set elitelevelscount
     *
     * @param integer $elitelevelscount
     * @return Provider
     */
    public function setElitelevelscount($elitelevelscount)
    {
        $this->elitelevelscount = $elitelevelscount;
    
        return $this;
    }

    /**
     * Get elitelevelscount
     *
     * @return integer
     */
    public function getElitelevelscount()
    {
        return $this->elitelevelscount;
    }

    /**
     * Set rslaeventid
     *
     * @param integer $rslaeventid
     * @return Provider
     */
    public function setRslaeventid($rslaeventid)
    {
        $this->rslaeventid = $rslaeventid;
    
        return $this;
    }

    /**
     * Get rslaeventid
     *
     * @return integer 
     */
    public function getRslaeventid()
    {
        return $this->rslaeventid;
    }

    /**
     * Set cancheckcancelled
     *
     * @param boolean $cancheckcancelled
     * @return Provider
     */
    public function setCancheckcancelled($cancheckcancelled)
    {
        $this->cancheckcancelled = $cancheckcancelled;
    
        return $this;
    }

    /**
     * Get cancheckcancelled
     *
     * @return boolean
     */
    public function getCancheckcancelled()
    {
        return $this->cancheckcancelled;
    }

    /**
     * Set aaadiscount
     *
     * @param float $aaadiscount
     * @return Provider
     */
    public function setAaadiscount($aaadiscount)
    {
        $this->aaadiscount = $aaadiscount;
    
        return $this;
    }

    /**
     * Get aaadiscount
     *
     * @return float 
     */
    public function getAaadiscount()
    {
        return $this->aaadiscount;
    }

    /**
     * Set login3caption
     *
     * @param string $login3caption
     * @return Provider
     */
    public function setLogin3caption($login3caption)
    {
        $this->login3caption = $login3caption;
    
        return $this;
    }

    /**
     * Get login3caption
     *
     * @return string 
     */
    public function getLogin3caption()
    {
        return $this->login3caption;
    }

    public function isLogin3Required() : bool
    {
        return $this->login3Required;
    }

    public function setLogin3Required(bool $login3Required) : Provider
    {
        $this->login3Required = $login3Required;

        return $this;
    }

    /**
     * Set checkinbrowser
     *
     * @param integer $checkinbrowser
     * @return Provider
     */
    public function setCheckinbrowser($checkinbrowser)
    {
        $this->checkinbrowser = $checkinbrowser;
    
        return $this;
    }

    /**
     * Get checkinbrowser
     *
     * @return integer
     */
    public function getCheckinbrowser()
    {
        return $this->checkinbrowser;
    }

    /**
     * @return boolean
     */
    public function isCheckinmobilebrowser()
    {
        return $this->checkinmobilebrowser;
    }

    /**
     * @param boolean $checkInMobileBrowser
     *
     * @return Provider
     */
    public function setCheckinmobilebrowser($checkInMobileBrowser)
    {
        $this->checkinmobilebrowser = $checkInMobileBrowser;

        return $this;
    }

    /**
     * Set accounts
     *
     * @param integer $accounts
     * @return Provider
     */
    public function setAccounts($accounts)
    {
        $this->accounts = $accounts;

        return $this;
    }

    /**
     * Get accounts
     *
     * @return integer
     */
    public function getAccounts()
    {
        return $this->accounts;
    }

    /**
     * Set abaccounts
     *
     * @param integer $abaccounts
     * @return Provider
     */
    public function setAbaccounts($abaccounts)
    {
        $this->abaccounts = $abaccounts;

        return $this;
    }

    /**
     * Get abaccounts
     *
     * @return integer
     */
    public function getAbaccounts()
    {
        return $this->abaccounts;
    }

    /**
     * Set keywords
     *
     * @param string $keywords
     * @return Provider
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
    
        return $this;
    }

    /**
     * Get keywords
     *
     * @return string 
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @return string
     */
    public function getStopKeywords()
    {
        return $this->stopKeywords;
    }

    /**
     * @param string $stopKeywords
     *
     * @return Provider
     */
    public function setStopKeywords($stopKeywords) : self
    {
        $this->stopKeywords = $stopKeywords;

        return $this;
    }

    /**
     * Set avgdurationwithoutplans
     *
     * @param float $avgdurationwithoutplans
     * @return Provider
     */
    public function setAvgdurationwithoutplans($avgdurationwithoutplans)
    {
        $this->avgdurationwithoutplans = $avgdurationwithoutplans;
    
        return $this;
    }

    /**
     * Get avgdurationwithoutplans
     *
     * @return float 
     */
    public function getAvgdurationwithoutplans()
    {
        return $this->avgdurationwithoutplans;
    }

    /**
     * Set avgdurationwithplans
     *
     * @param float $avgdurationwithplans
     * @return Provider
     */
    public function setAvgdurationwithplans($avgdurationwithplans)
    {
        $this->avgdurationwithplans = $avgdurationwithplans;
    
        return $this;
    }

    /**
     * Get avgdurationwithplans
     *
     * @return float 
     */
    public function getAvgdurationwithplans()
    {
        return $this->avgdurationwithplans;
    }

    public function getAvgDuration(bool $checkItineraries) : float
    {
        if ($checkItineraries) {
            $avgDuration = $this->avgdurationwithplans ?: $this->avgdurationwithoutplans;
        } else {
            $avgDuration = $this->avgdurationwithoutplans ?: $this->avgdurationwithplans;
        }

        return (float) ($avgDuration ?: 30);
    }

    /**
     * Set canmarkcoupons
     *
     * @param boolean $canmarkcoupons
     * @return Provider
     */
    public function setCanmarkcoupons($canmarkcoupons)
    {
        $this->canmarkcoupons = $canmarkcoupons;
    
        return $this;
    }

    /**
     * Get canmarkcoupons
     *
     * @return boolean 
     */
    public function getCanmarkcoupons()
    {
        return $this->canmarkcoupons;
    }

    /**
     * @return bool
     */
    public function getCanParseCardImages() : bool
    {
        return $this->canParseCardImages;
    }

    /**
     * @param bool $canParseCardImages
     *
     * @return Provider
     */
    public function setCanParseCardImages(bool $canParseCardImages) : self
    {
        $this->canParseCardImages = $canParseCardImages;

        return $this;
    }

    /**
     * @return bool
     */
    public function getCanDetectCreditCards() : bool
    {
        return $this->canDetectCreditCards;
    }

    /**
     * @param bool $canDetectCreditCards
     *
     * @return Provider
     */
    public function setCanDetectCreditCards(bool $canDetectCreditCards) : self
    {
        $this->canDetectCreditCards = $canDetectCreditCards;

        return $this;
    }

    /**
     * Set warning
     *
     * @param string $warning
     * @return Provider
     */
    public function setWarning($warning)
    {
        $this->warning = $warning;
    
        return $this;
    }

    /**
     * Get warning
     *
     * @return string 
     */
    public function getWarning()
    {
        return $this->warning;
    }

    /**
     * Set cancheckhistory
     *
     * @param boolean $cancheckhistory
     * @return Provider
     */
    public function setCancheckhistory($cancheckhistory)
    {
        $this->cancheckhistory = $cancheckhistory;
    
        return $this;
    }

    /**
     * Get cancheckhistory
     *
     * @return boolean 
     */
    public function getCancheckhistory()
    {
        return $this->cancheckhistory;
    }

    /**
     * Set expirationalwaysknown
     *
     * @param boolean $expirationalwaysknown
     * @return Provider
     */
    public function setExpirationalwaysknown($expirationalwaysknown)
    {
        $this->expirationalwaysknown = $expirationalwaysknown;
    
        return $this;
    }

    /**
     * Get expirationalwaysknown
     *
     * @return boolean 
     */
    public function getExpirationalwaysknown()
    {
        return $this->expirationalwaysknown;
    }

    /**
     * Set requestsperminute
     *
     * @param integer $requestsperminute
     * @return Provider
     */
    public function setRequestsperminute($requestsperminute)
    {
        $this->requestsperminute = $requestsperminute;
    
        return $this;
    }

    /**
     * Get requestsperminute
     *
     * @return integer 
     */
    public function getRequestsperminute()
    {
        return $this->requestsperminute;
    }

    /**
     * Set cacheversion
     *
     * @param integer $cacheversion
     * @return Provider
     */
    public function setCacheversion($cacheversion)
    {
        $this->cacheversion = $cacheversion;
    
        return $this;
    }

    /**
     * Get cacheversion
     *
     * @return integer 
     */
    public function getCacheversion()
    {
        return $this->cacheversion;
    }

    /**
     * Set canchecknoitineraries
     *
     * @param boolean $canchecknoitineraries
     * @return Provider
     */
    public function setCanchecknoitineraries($canchecknoitineraries)
    {
        $this->canchecknoitineraries = $canchecknoitineraries;
    
        return $this;
    }

    /**
     * Get canchecknoitineraries
     *
     * @return boolean 
     */
    public function getCanchecknoitineraries()
    {
        return $this->canchecknoitineraries;
    }

    /**
     * Set planemail
     *
     * @param string $planemail
     * @return Provider
     */
    public function setPlanemail($planemail)
    {
        $this->planemail = $planemail;
    
        return $this;
    }

    /**
     * Get planemail
     *
     * @return string
     */
    public function getPlanemail()
    {
        return $this->planemail;
    }

    /**
     * Set internalnote
     *
     * @param string $internalnote
     * @return Provider
     */
    public function setInternalnote($internalnote)
    {
        $this->internalnote = $internalnote;
    
        return $this;
    }

    /**
     * Get internalnote
     *
     * @return string 
     */
    public function getInternalnote()
    {
        return $this->internalnote;
    }

    /**
     * Set calcelitelevelexpdate
     *
     * @param boolean $calcelitelevelexpdate
     * @return Provider
     */
    public function setCalcelitelevelexpdate($calcelitelevelexpdate)
    {
        $this->calcelitelevelexpdate = $calcelitelevelexpdate;
    
        return $this;
    }

    /**
     * Get calcelitelevelexpdate
     *
     * @return boolean 
     */
    public function getCalcelitelevelexpdate()
    {
        return $this->calcelitelevelexpdate;
    }

    /**
     * Set itineraryautologin
     *
     * @param int $itineraryautologin
     * @return Provider
     */
    public function setItineraryautologin($itineraryautologin)
    {
        $this->itineraryautologin = $itineraryautologin;
    
        return $this;
    }

    /**
     * Get itineraryautologin
     *
     * @return int
     */
    public function getItineraryautologin()
    {
        return $this->itineraryautologin;
    }

    /**
     * Set eliteprogramcomment
     *
     * @param string $eliteprogramcomment
     * @return Provider
     */
    public function setEliteprogramcomment($eliteprogramcomment)
    {
        $this->eliteprogramcomment = $eliteprogramcomment;
    
        return $this;
    }

    /**
     * Get eliteprogramcomment
     *
     * @return string 
     */
    public function getEliteprogramcomment()
    {
        return $this->eliteprogramcomment;
    }

    /**
     * Set canscanemail
     *
     * @param boolean $canscanemail
     * @return Provider
     */
    public function setCanscanemail($canscanemail)
    {
        $this->canscanemail = $canscanemail;
    
        return $this;
    }

    /**
     * Get canscanemail
     *
     * @return boolean 
     */
    public function getCanscanemail()
    {
        return $this->canscanemail;
    }

    /**
     * Set category
     *
     * @param integer $category
     * @return Provider
     */
    public function setCategory($category)
    {
        $this->category = $category;
    
        return $this;
    }

    /**
     * Get category
     *
     * @return integer
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set allianceid
     *
     * @param Alliance $allianceid
     * @return Provider
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
     * @return ProviderProperty[]|Collection
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param ProviderProperty[]|Collection $properties
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
    }

    /**
     * @param boolean $AutoLoginIE
     */
    public function setAutoLoginIE($AutoLoginIE)
    {
        $this->AutoLoginIE = $AutoLoginIE;
    }

    /**
     * @return boolean
     */
    public function getAutoLoginIE()
    {
        return $this->AutoLoginIE;
    }

    /**
     * @param boolean $AutoLoginSafari
     */
    public function setAutoLoginSafari($AutoLoginSafari)
    {
        $this->AutoLoginSafari = $AutoLoginSafari;
    }

    /**
     * @return boolean
     */
    public function getAutoLoginSafari()
    {
        return $this->AutoLoginSafari;
    }

    /**
     * @param boolean $AutoLoginChrome
     */
    public function setAutoLoginChrome($AutoLoginChrome)
    {
        $this->AutoLoginChrome = $AutoLoginChrome;
    }

    /**
     * @return boolean
     */
    public function getAutoLoginChrome()
    {
        return $this->AutoLoginChrome;
    }

    /**
     * @param boolean $AutoLoginFirefox
     */
    public function setAutoLoginFirefox($AutoLoginFirefox)
    {
        $this->AutoLoginFirefox = $AutoLoginFirefox;
    }

    /**
     * @return boolean
     */
    public function getAutoLoginFirefox()
    {
        return $this->AutoLoginFirefox;
    }

    /**
     * @return int
     */
    public function getGoal() {
        return $this->Goal;
    }

    /**
     * @param int $Goal
     */
    public function setGoal($Goal) {
        $this->Goal = $Goal;
    }

    /**
     * Set CanReceiveEmail
     *
     * @param boolean $canReceiveEmail
     * @return Provider
     */
    public function setCanReceiveEmail($canReceiveEmail)
    {
        $this->CanReceiveEmail = $canReceiveEmail;
    
        return $this;
    }

    /**
     * Get CanReceiveEmail
     *
     * @return boolean 
     */
    public function getCanReceiveEmail()
    {
        return $this->CanReceiveEmail;
    }

    public static function getKinds(){
        return [
            PROVIDER_KIND_CREDITCARD => 'track.group.card',
            PROVIDER_KIND_AIRLINE    => 'track.group.airline',
            PROVIDER_KIND_HOTEL      => 'track.group.hotel',
            PROVIDER_KIND_CAR_RENTAL => 'track.group.rent',
            PROVIDER_KIND_TRAIN      => 'track.group.train',
            PROVIDER_KIND_CRUISES    => 'track.group.cruise',
            PROVIDER_KIND_SHOPPING   => 'track.group.shop',
            PROVIDER_KIND_DINING     => 'track.group.dining',
            PROVIDER_KIND_SURVEY     => 'track.group.survey',
            PROVIDER_KIND_OTHER      => 'track.group.other',
            PROVIDER_KIND_DOCUMENT   => 'track.group.document',
        ];
    }

    /**
     * @return string
     */
    public function getIATACode()
    {
        return $this->IATACode;
    }

    /**
     * @param string $IATACode
     */
    public function setIATACode($IATACode)
    {
        $this->IATACode = $IATACode;
    }

	/**
	 * @return bool
	 */
	public function getCanTransferRewards()
	{
		return $this->canTransferRewards;
	}

	/**
	 * @param bool $flag
	 */
	public function setCanTransferRewards($flag)
	{
		$this->canTransferRewards = $flag;
	}

    /**
     * @return string
     */
    public function getCheckInReminderOffsets()
    {
        return $this->checkInReminderOffsets;
    }

    /**
     * @param string $checkInReminderOffsets
     *
     * @return Provider
     */
    public function setCheckInReminderOffsets($checkInReminderOffsets)
    {
        $this->checkInReminderOffsets = $checkInReminderOffsets;

        return $this;
    }

	/**
     * @return array<Message>
     */
    static function getTranslationMessages() {
        return [
            (new Message('track.group.all'))->setDesc('All'),
            (new Message('track.group.airline'))->setDesc('Airlines'),
            (new Message('track.group.hotel'))->setDesc('Hotels'),
            (new Message('track.group.card'))->setDesc('Credit Cards'),
            (new Message('track.group.shop'))->setDesc('Shopping'),
            (new Message('track.group.rent'))->setDesc('Rentals'),
            (new Message('track.group.dining'))->setDesc('Dining'),
            (new Message('track.group.train'))->setDesc('Trains'),
            (new Message('track.group.cruise'))->setDesc('Cruises'),
            (new Message('track.group.survey'))->setDesc('Surveys'),
            (new Message('track.group.other'))->setDesc('Other'),
            (new Message('track.group.document'))->setDesc('Documents'),

            (new Message('track.group.airline.items'))->setDesc('{0}miles|{1}mile|[2,Inf]miles'),
            (new Message('track.group.hotel.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.card.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.shop.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.rent.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.dining.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.train.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.cruise.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.survey.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
            (new Message('track.group.other.items'))->setDesc('{0}points|{1}point|[2,Inf]points'),
        ];
    }

	/**
	 * @return int
	 */
	public function getCanSavePassword()
	{
		return $this->CanSavePassword;
	}

	/**
	 * @param int $CanSavePassword
	 */
	public function setCanSavePassword($CanSavePassword)
	{
		$this->CanSavePassword = $CanSavePassword;
	}

    /**
     * @return \DateTime
     */
    public function getEnabledate()
    {
        return $this->enabledate;
    }

    /**
     * @param \DateTime $enabledate
     */
    public function setEnabledate($enabledate)
    {
        $this->enabledate = $enabledate;
    }

    /**
     * @return bool
     */
    public function getCanCheckOneTime()
    {
        return $this->canCheckOneTime;
    }

    public function setCanCheckOneTime($canCheckOneTime)
    {
        $this->canCheckOneTime = $canCheckOneTime;

        return $this;
    }

    /**
     * @return bool
     */
    public function isOauthProvider()
    {
        return in_array($this->providerid, self::OAUTH_PROVIDERS);
    }

    public function isBig3()
    {
        return in_array($this->providerid, self::BIG3_PROVIDERS);
    }

    /**
     * @return Popularity
     */
    public function getPopularity()
    {
        return $this->popularity;
    }

    /**
     * @param Popularity $popularity
     */
    public function setPopularity($popularity)
    {
        $this->popularity = $popularity;
    }

    /**
     * @return bool
     */
    public function isRetail()
    {
        return $this->isRetail;
    }

    /**
     * @param bool $isRetail
     *
     * @return Provider
     */
    public function setIsRetail($isRetail)
    {
        $this->isRetail = $isRetail;

        return $this;
    }

    /**
     * @return string
     */
    public function getAdditionalInfo()
    {
        return $this->additionalInfo;
    }

    /**
     * @param string $additionalInfo
     *
     * @return Provider
     */
    public function setAdditionalInfo($additionalInfo)
    {
        $this->additionalInfo = $additionalInfo;

        return $this;
    }


    function __toString() {
        return $this->getProviderid() .'-'. $this->getCode();
    }

    /**
     * @return Providerphone[]
     */
    public function getPhones(): array
    {
        return $this->phones->toArray();
    }
}