<?php

namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Schema\Parser\Component\Base;
use AwardWallet\Schema\Parser\Component\Field\Validator;
use AwardWallet\Schema\Parser\Component\InvalidDataException;

class Statement extends Base {

    const PROPERTY_KIND_NUMBER = 1;
    const PROPERTY_KIND_NAME = 12;
    const PROPERTY_KIND_STATUS = 3;
    const PROPERTY_KIND_EXPIRING_BALANCE = 6;

	const EXPIRATION_KEY = 'AccountExpirationDate';
	const BALANCE_KEY = 'Balance';
	const BALANCE_DATE_KEY = 'BalanceDate';

    private $_propertySchema;
    private $_providerCode;
    private $_lastSetField;

    private $_propertyLength = [
        'ParsedAddress' => 250,
    ];
    
    protected $properties = [];
    protected $detectedCards = [];
    protected $subaccounts = [];
    protected $activity;

	/**
	 * @parsed Field
	 * @attr type=balance
	 */
	protected $balance;
    /**
     * @parsed DateTime
     */
	protected $balanceDate;
    /**
     * @parsed Boolean
     */
	protected $noBalance;
    /**
     * @parsed Field
     */
	protected $login;
    /**
     * @parsed Field
     */
	protected $login2;
    /**
     * @parsed Field
     * @attr enum=['left','right','center']
     */
	protected $loginMask;
    /**
     * @parsed Field
     */
	protected $number;
    /**
     * @parsed Field
     * @attr enum=['left','right','center']
     */
	protected $numberMask;
    /**
     * @parsed DateTime
     * @attr seconds=true
     */
	protected $expirationDate;
    /**
     * @parsed Boolean
     */
	protected $membership;

	/**
	 * @return mixed
	 */
	public function getActivity() {
		return $this->activity;
	}

	/**
	 * @param array $activity
	 * @return $this
	 */
	public function setActivityArray(array $activity) {
		$this->activity = $activity;
		return $this;
	}

	/**
	 * @param array $row
	 * @return $this
	 */
	public function addActivityRow(array $row) {
		if (!isset($this->activity))
			$this->activity = [];
		$this->activity[] = $row;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * @param $name
	 * @param $value
	 * @return $this
	 * @throws InvalidDataException
	 */
	public function addProperty($name, $value) {
		$this->logDebug(sprintf('%s: setting property `%s` = `%s`', $this->_name, $this->str($name), $this->str($value)));
		$valid = true;
		$error = null;
		if (!is_string($name) || !is_scalar($value) && !is_null($value)) {
			$valid = false;
			$error = sprintf('invalid property name or value: `%s` => `%s`', $this->str($name), $this->str($value));
		}
		else {
			$this->setPropertyInternal($name, $value);
		}
		if (!$valid)
			$this->invalid($error);
		return $this;
	}

	public function addSubAccount(array $subacc)
    {
        $set = true;
        foreach($subacc as $code => $value)
            $set = $set && $this->validateBasicProperty($code, 'subacc.', $value, 250);
        if ($set)
            $this->subaccounts[] = $subacc;
    }

    public function getSubAccounts(): array
    {
        return $this->subaccounts;
    }

    public function addDetectedCard(array $card)
    {
        $set = true;
        foreach($card as $code => $value)
            $set = $set && $this->validateBasicProperty($code, 'card.', $value, 250);
        if ($set)
            $this->detectedCards[] = $card;
    }
	
	/**
	 * @param $date
	 * @return $this
	 * @throws InvalidDataException
	 */
	public function setExpirationDate($date)
    {
        $this->setProperty($date, 'expirationDate', false, false);
        return $this;
	}

    /**
     * @param $dateStr
     * @param null $relative
     * @param string $format
     * @param bool $after
     * @return $this
     * @throws InvalidDataException
     */
	public function parseExpirationDate($dateStr, $relative = null, $format = '%D% %Y%', $after = true)
    {
        $this->parseUnixTimeProperty($dateStr, 'expirationDate', $relative, $after, $format);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }
	
	/**
	 * @param $balance
	 * @return $this
	 * @throws InvalidDataException
	 */
	public function setBalance($balance) {
		$this->setProperty($balance, 'balance', false, false);
		return $this;
	}
	
	/**
	 * @return mixed
	 */
	public function getBalance() {
		return $this->balance;
	}

    /**
     * @return mixed
     */
    public function getBalanceDate()
    {
        return $this->balanceDate;
    }

    /**
     * @param mixed $balanceDate
     * @return Statement
     * @throws InvalidDataException
     */
    public function setBalanceDate($balanceDate): Statement
    {
        $this->setProperty($balanceDate, 'balanceDate', false, false);
        return $this;
    }

    /**
     * @param $balanceDate
     * @param $relative
     * @param string $format
     * @param bool $after
     * @return Statement
     * @throws InvalidDataException
     */
    public function parseBalanceDate($balanceDate, $relative = null, $format = '%D% %Y%', $after = true): Statement
    {
        $this->parseUnixTimeProperty($balanceDate, 'balanceDate', $relative, $after, $format);
        return $this;
    }

    /**
     * @param $noBalance
     * @return $this
     * @throws InvalidDataException
     */
	public function setNoBalance($noBalance)
    {
        $this->setProperty($noBalance, 'noBalance', false, false);
        return $this;
    }

    /**
     * @return bool
     */
    public function getNoBalance()
    {
        return $this->noBalance;
    }

    /**
     * @return mixed
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param mixed $login
     * @return Statement
     * @throws InvalidDataException
     */
    public function setLogin($login)
    {
        $this->setProperty($login, 'login', false, false);
        $this->_lastSetField = 'login';
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLogin2()
    {
        return $this->login2;
    }

    /**
     * @param mixed $login2
     * @return Statement
     * @throws InvalidDataException
     */
    public function setLogin2($login2)
    {
        $this->setProperty($login2, 'login2', false, false);
        $this->_lastSetField = 'login2';
        return $this;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param mixed $number
     * @return Statement
     * @throws InvalidDataException
     */
    public function setNumber($number)
    {
        $this->setProperty($number, 'number', false, false);
        $this->_lastSetField = 'number';
        return $this;
    }

    /**
     * @param string $mask
     * @return $this
     * @throws InvalidDataException
     */
    public function masked($mask = 'left')
    {
        switch($this->_lastSetField) {
            case 'login':
                $this->setProperty($mask, 'loginMask', false, false);
                break;
            case 'number':
                $this->setProperty($mask, 'numberMask', false, false);
                break;
        }
        return $this;
    }

    public function getLoginMask()
    {
        return $this->loginMask;
    }

    public function getNumberMask()
    {
        return $this->numberMask;
    }

    /**
     * @return mixed
     */
    public function getMembership()
    {
        return $this->membership;
    }

    /**
     * @param mixed $membership
     * @return Statement
     * @throws InvalidDataException
     */
    public function setMembership($membership)
    {
        $this->setProperty($membership, 'membership', false, false);
        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @throws InvalidDataException
     */
	protected function setPropertyInternal($name, $value) {
		if (is_string($value))
			$value = trim($value, " \r\n\t");
		$name = trim($name, " \r\n\t");
        $this->properties[$name] = $value;
	}

	private function filterPartialString($value)
    {
        if ($value[strlen($value)-1] === '$')
            return substr($value, 0, -1);
        $this->invalid(sprintf('invalid partial format `%s`', $this->str($value)));
        return null;
    }

    public function validateProperties(): bool
    {
        $valid = true;
        if ($this->_propertySchema !== null)
            foreach($this->properties as $code => $value)
                if (!$this->validateProperty($code, $value))
                    $valid = false;
        return $valid;
    }

	private function validateProperty($code, $value): bool
    {
	    if ($this->_options->checkProviderProperties && !array_key_exists($code, $this->_propertySchema)) {
	        $this->invalid(sprintf('unknown property `%s` for provider %s', $code, $this->_providerCode));
	        return false;
        }
	    if (!is_scalar($value)) {
	        $this->invalid(sprintf('non-scalar value in `%s`', $code));
	        return false;
        }
	    if (is_string($value) && strlen($value) == 0 || is_null($value))
	        return true;
	    if (isset($this->_propertySchema) && array_key_exists($code, $this->_propertySchema))
            switch($this->_propertySchema[$code]) {
                case self::PROPERTY_KIND_NUMBER:
                case self::PROPERTY_KIND_NAME:
                case self::PROPERTY_KIND_STATUS:
                    if (strlen($value) > [
                            self::PROPERTY_KIND_NUMBER => 30,
                            self::PROPERTY_KIND_NAME => 100,
                            self::PROPERTY_KIND_STATUS => 50,
                        ][$this->_propertySchema[$code]]) {
                        $this->invalid(sprintf('property `%s` is too long (%d)', $code, strlen($value)));
                        return false;
                    }
                    break;
                case self::PROPERTY_KIND_EXPIRING_BALANCE:
                    if (preg_match('/^.{0,5}\s*(\d[\d., ]*|[\d., ]*\d)\s*.{0,5}$/', $value) === 0 || strlen(preg_replace('/\D/', '', $value)) > 11) {
                        $this->invalid(sprintf('bad property `%s`: %s', $code, $this->str($value)));
                        return false;
                    }
                    break;
                default:
                    return $this->validateBasicProperty($code, '', $value, $this->_propertyLength[$code] ?? 100);
            }
        return true;
    }

    private function validateBasicProperty($code, $prefix, $value, $length): bool
    {
        if (!is_scalar($value))
            return true;
        $ignore = ['CashBack', 'CashBackNextQuarter', 'AccountExpirationWarning'];
        if (in_array($code, $ignore) || substr($code, -4) === 'Note')
            return true;
        if ($code === 'BarCode') {
            $length = 500;
        }
        if ($code === 'Code' && preg_match('/^[-#.…_*$®℠&a-zA-Z\d\(\)]+$/', $value) === 0) {
            $this->invalid(sprintf('bad property `%s%s`: %s', $prefix, $code, $this->str($value)));
            return false;
        }
        if ($code === 'ExpirationDate' && $value !== false && ($error = Validator::validateField($value, 'DateTime', null, ['seconds' => true], false, true))) {
            $this->invalid(sprintf('bad `%sExpirationDate`: %s', $prefix, $error));
            return false;
        }
        if (strlen($value) > $length) {
            $this->invalid(sprintf('property `%s%s` is too long (%d)', $prefix, $code, strlen($value)));
            return false;
        }
        return true;
    }

	/**
	 * checks data and sets valid flag
	 * @return bool
	 * @throws InvalidDataException
	 */
	public function validate() {
	    $empty = count($this->properties) === 0 && $this->login === null && $this->number === null;
	    if ($empty && $this->balance === null && empty($this->activity) && !$this->membership)
	        $this->invalid('missing data');
	    if (!$empty && !isset($this->balance) && !$this->noBalance)
	        $this->invalid('missing balance');
	    if (!isset($this->balance) && (isset($this->balanceDate) || isset($this->expirationDate)))
	        $this->invalid('balanceDate/expDate is set but balance is not');
	    if (isset($this->balanceDate) && $this->balanceDate > time()) {
	        if ($this->balanceDate === strtotime('tomorrow 00:00')) {
	            $this->logNotice('fixed balanceDate');
	            $this->parseBalanceDate('today 00:00');
            }
	        else {
                $this->invalid('balanceDate is in the future');
            }
        }
	    if ($this->loginMask === 'center' && preg_match('/^[^*]+\*\*[^*]+$/', $this->login) == 0)
	        $this->invalid('invalid center masked login format');
        if ($this->numberMask === 'center' && preg_match('/^[^*]+\*\*[^*]+$/', $this->number) == 0)
            $this->invalid('invalid center masked number format');
        if (!empty($this->activity)) {
            foreach($this->activity as $row) {
                if (!is_array($row)) {
                    $this->invalid('invalid history row');
                }
                else {
                    if (isset($row['PostingDate']) && strlen(trim($row['PostingDate'])) === 0) {
                        $this->invalid('invalid PostingDate item in history');
                    }
                }
            }
        }
		return $this->valid;
	}

    /**
     * @param string $code
     * @param array $properties ["Code" => "Kind"]
     */
	public function loadProviderProperties(string $code, array $properties)
    {
        $this->_providerCode = $code;
        $this->_propertySchema = $properties;

        //todo: remove
        if (isset($this->properties['Login'])) {
            $this->login = $this->properties['Login'];
        }
        if (isset($this->properties['PartialLogin']) && $str = $this->filterPartialString($this->properties['PartialLogin'])) {
            $this->login = $str;
            $this->loginMask = 'left';
        }
        unset($this->properties['Login'],$this->properties['PartialLogin']);
        if ($numCode = $this->getNumberPropertyCode()) {
            if (isset($this->properties[$numCode])) {
                $this->number = $this->properties[$numCode];
            }
            if (isset($this->properties['Partial'.$numCode]) && $str = $this->filterPartialString($this->properties['Partial'.$numCode])) {
                $this->number = $str;
                $this->numberMask = 'left';
            }
            unset($this->properties[$numCode],$this->properties['Partial'.$numCode]);
        }
    }

    private function getNumberPropertyCode()
    {
        if (isset($this->_propertySchema))
            foreach($this->_propertySchema as $code => $kind)
                if ($kind == self::PROPERTY_KIND_NUMBER)
                    return $code;
        return null;
    }

	protected function fromArrayChildren(array $arr)
    {
        parent::fromArrayChildren($arr);
        if (isset($arr['properties']))
            $this->properties = $arr['properties'];
        if (isset($arr['activity']))
            $this->activity = $arr['activity'];
    }

    /**
	 * @return Base[]
	 */
	protected function getChildren() {
		return [];
	}
}