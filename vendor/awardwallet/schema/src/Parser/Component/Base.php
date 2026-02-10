<?php

namespace AwardWallet\Schema\Parser\Component;


use AwardWallet\Schema\Parser\Component\Field\Arr;
use AwardWallet\Schema\Parser\Component\Field\Field;
use AwardWallet\Schema\Parser\Component\Field\KeyValue;
use AwardWallet\Schema\Parser\Component\Field\Validator;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

abstract class Base {

	/** @var bool $valid */
	protected $valid;

	/** @var Logger $logger */
	protected $logger;

	/** @var string $name */
	protected $_name;

	/** @var Options $_options */
	protected $_options;

	protected $_fields;

	/**
	 * @return Base[]
	 */
	public function unfold() {
		$result = [$this];
		for($i=0;isset($result[$i]);$i++)
			$result = array_merge($result, $result[$i]->getChildren());
		return $result;
	}

	/**
	 * returns personal valid flag
	 * @return bool
	 */
	public function getValid() {
		return $this->valid;
	}

    protected function validateArrays()
    {
        foreach($this->_fields as $name => $field) {
            $unique = $field['attr']['unique'] ?? false;
            if ($unique === false || !is_array($this->$name) || empty($this->$name))
                continue;
            $filtered = $dups = [];
            if ($field['type'] === 'Arr') {
                foreach($this->$name as $val)
                    if (!in_array($val, $filtered))
                        $filtered[] = $val;
                    else
                        $dups[] = $val;
            }
            if ($field['type'] === 'KeyValue') {
                $f = [];
                foreach($this->$name as $val)
                    if (!in_array($val[0], $f)) {
                        $f[] = $val[0];
                        $filtered[] = $val;
                    }
                    else
                        $dups[] = $val[0];
            }
            if (count($dups) > 0) {
                $dups = array_unique($dups);
                if ($unique === 'strict')
                    $this->invalid(sprintf('duplicate elements in `%s`: `%s`', $name, $this->str($dups)));
                if ($unique === true) {
                    $this->logNotice(sprintf('%s: duplicate elements in `%s`: `%s` filtered out', $this->_name, $name, $this->str($dups)));
                    $this->$name = $filtered;
                }
            }
        }
    }

	public function getId() {
		return $this->_name;
	}

	/**
	 * @return Base[]
	 */
	abstract protected function getChildren();

	public function __construct($name, LoggerInterface $logger, Options $options = null) {
		$this->valid = true;
		$this->logger = $logger;
		$this->_name = $name;
		if (isset($options))
			$this->_options = $options;
		else
			$this->_options = new Options();
		$this->parseFields();
	}

	public function getLogger(): Logger
    {
        return $this->logger;
    }

	protected function parseFields() {
		$this->_fields = [];
		$class = new \ReflectionClass($this);
		foreach($class->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
			if ($property->class !== get_class($this))
				continue;
			$name = $property->name;
			$lines = explode("\n", $property->getDocComment());
			foreach($lines as $line) {
				$line = trim($line, " \r\n\t*");
				if (strpos($line, '@') !== 0)
					continue;
				list($annot, $value) = explode(' ', $line, 2);
				if ($annot === '@parsed') {
					$this->_fields[$name] = ['type' => $value, 'attr' => []];
					if (in_array($value, ['Arr', 'KeyValue']))
						$this->$name = [];
				}
				elseif($annot === '@attr') {
					list($attr, $val) = explode('=', $value, 2);
					if (is_numeric($val))
						$val = intval($val);
					elseif($val === 'true')
						$val = true;
					elseif($val === 'false')
						$val = false;
					elseif($val[0] === '[' && $val[strlen($val) - 1] === ']')
                        $val = json_decode($val, true);
					if (strpos($attr, '_') !== false) {
						list($sub, $a) = explode('_', $attr, 2);
						$this->_fields[$name]['attr'][$sub.'_'][$a] = $val;
					}
					else
						$this->_fields[$name]['attr'][$attr] = $val;
				}
			}
		}
	}

	/**
	 * @return Options
	 */
	public function options() {
		return $this->_options;
	}

	/**
	 * @param $value
	 * @param string $propertyName
	 * @param boolean $allowEmpty
	 * @param boolean $allowNull
	 * @throws InvalidDataException
	 */
	protected function setProperty($value, $propertyName, $allowEmpty, $allowNull) {
		$old = $value;
		if (array_key_exists($propertyName, $this->_fields)) {
			$this->setLog($propertyName, $value);
			$this->$propertyName = null;
			$error = Validator::validateField($value, $this->_fields[$propertyName]['type'], $this->$propertyName, $this->_fields[$propertyName]['attr'], $allowEmpty, $allowNull);
			if (!empty($error)) {
				$this->invalid(sprintf('could not set property `%s` => `%s`: %s', $propertyName, $this->str($old), $error));
			}
			else {
				$this->$propertyName = $value;
			}
		}
	}

	protected function clearProperty($propertyName)
    {
        $this->logDebug(sprintf('%s: clearing property %s', $this->_name, $propertyName));
        $this->$propertyName = null;
    }

	/**
	 * @param $value
	 * @param $propertyName
	 * @param $allowEmpty
	 * @param $allowNull
	 * @throws InvalidDataException
	 */
	public function addItem($value, $propertyName, $allowEmpty, $allowNull) {
		if (array_key_exists($propertyName, $this->_fields)) {
			$this->logDebug(sprintf('%s: adding element `%s` to `%s`', $this->_name, $this->str($value, true), $propertyName));
			$error = Arr::validateItem($value, $this->$propertyName, $this->_fields[$propertyName]['attr'], $allowEmpty, $allowNull);
			if (!empty($error))
				$this->invalid(sprintf('could not add element `%s` to `%s`: %s', $this->str($value), $propertyName, $error));
			elseif (!is_null($value) && (!is_string($value) || strlen((string)$value) > 0))
				$this->$propertyName[] = $value;
		}
	}

    /**
     * @param $key
     * @param $value
     * @param $propertyName
     * @param $allowEmptyValue
     * @param $allowNullValue
     * @param $keyAttr
     * @throws InvalidDataException
     */
	public function addKeyValue($key, $value, string $propertyName, bool $allowEmptyValue, bool $allowNullValue, array $keyAttr) {
		if (array_key_exists($propertyName, $this->_fields)) {
			$this->logDebug(sprintf('%s: adding pair [`%s`, `%s`] to `%s`', $this->_name, $this->str($key, true), $this->str($value, true), $propertyName));
			$error = KeyValue::validatePair($key, $value, $this->$propertyName, $this->_fields[$propertyName]['attr'], false, false, $allowEmptyValue, $allowNullValue, $keyAttr);
			if (!empty($error))
				$this->invalid(sprintf('could not add pair [`%s`, `%s`] to `%s`: %s', $this->str($key), $this->str($value), $propertyName, $error));
			else
				$this->$propertyName[] = [$key, $value];
		}
	}

	/**
	 * @param $value
	 * @param $propertyName
	 */
	public function removeItem($value, $propertyName) {
		if (array_key_exists($propertyName, $this->_fields)) {
			$indexed = $this->_fields[$propertyName]['type'] === 'Arr';
			$this->logDebug(sprintf('%s: removing element `%s` from `%s`', $this->_name, $this->str($value), $propertyName));
			$found = null;
			foreach ($this->$propertyName as $k => $v)
				if ($indexed && $v === $value || !$indexed && $v[0] === $value) {
					$found = $k;
					break;
				}
			if ($found !== null) {
				unset($this->$propertyName[$found]);
				$this->$propertyName = array_values($this->$propertyName);
			}
		}
	}

	/**
	 * @param $date
	 * @param $propertyName
	 * @param $relative
	 * @param $after
	 * @param $format
	 * @return bool
	 * @throws InvalidDataException
	 */
	protected function parseUnixTimeProperty($date, $propertyName, $relative, $after, $format) {
		$this->logDebug(sprintf('%s: parsing date `%s` into `%s`', $this->getId(), $this->str($date), $propertyName));
        if (array_key_exists($propertyName, $this->_fields))
            $this->$propertyName = null;
		$valid = true;
		$error = null;
		$unix = false;
		if (is_string($date))
			$date = trim($date, " \n\r\t");
		if (!is_string($date) || strlen($date) === 0) {
			$valid = false;
			$error = 'invalid date';
		}
		else {
			if (isset($relative) && isset($after) && isset($format)) {
				$this->logDebug(sprintf('using relative date %s (%s), after=%s, format `%s`',
					$this->str($relative),
					is_int($relative) ? date('Y-m-d H:i', $relative) : '',
					$this->str($after),
					$this->str($format)));
				if (!is_int($relative) || $relative < strtotime('2000-01-01') || !is_bool($after) || !is_string($format) || strlen($format) === 0 || strpos($format, '%Y%') === false) {
					$valid = false;
					$error = 'invalid relative parameters';
				}
				else {
					$year = date('Y', $relative);
					$unix = strtotime(str_replace(['%Y%', '%D%'], [$year, $date], $format));
					if (!$unix) {
						$valid = false;
						$error = 'failed to parse date from format';
					}
					else {
						if ($after && $unix < $relative)
							$unix = strtotime('+1 year', $unix);
						elseif (!$after && $unix > $relative)
							$unix = strtotime('-1 year', $unix);
					}
				}
			}
			else
				$unix = strtotime($date);
		}
		if ($valid)
			if ($unix)
				$this->setProperty($unix, $propertyName, false, false);
			else {
				$valid = false;
				$error = 'failed to parse date';
			}
		if (!$valid)
			$this->invalid($error);
		return $valid;
	}

	/**
	 * @param null $message
	 * @throws InvalidDataException
	 */
	protected function invalid($message = null) {
		$this->valid = false;
		if (isset($message)) {
			$message = sprintf('%s: %s', $this->_name, $message);
			$this->logNotice($message);
		}
		if (isset($this->_options) && $this->_options->throwOnInvalid)
			throw new InvalidDataException($message);
	}

	protected function setLog($propertyName, $value) {
		if (array_key_exists($propertyName, $this->_fields) && $this->_fields[$propertyName]['type'] === 'DateTime' && is_int($value))
			$value = sprintf('`%s`(%s)', $value, date('Y-m-d H:i:s', $value));
		else
			$value = $this->str($value, true);
		$this->logDebug(sprintf('%s: setting property `%s` to `%s`', $this->_name, $propertyName, $value));
	}

	protected function logDebug($m) {
		if ($this->_options->logDebug)
			$this->logger->debug($m, $this->_options->logContext);
	}

	protected function logNotice($m) {
		$this->logger->notice($m, $this->_options->logContext);
	}

	protected function str($var, $long = false) {
		if (is_array($var))
			$var = 'array ' . json_encode(array_map([$this, 'strItem'], $var));
		else
			$var = $this->strItem($var, $long);
		return $var;
	}

	protected function strItem($var, $long = false) {
		if (is_bool($var))
			return $var ? 'true' : 'false';
		elseif (is_array($var))
			return 'array';
		elseif(is_null($var))
			return 'null';
		elseif(!is_scalar($var))
			return 'non scalar value';
		elseif(is_string($var) && strlen($var) > Field::MEDIUM && !$long)
			return substr($var, 0, Field::MEDIUM) . '...';
		else
			return (string)$var;
	}

	public function toArray($showEmpty = false) {
		$r = [];
		try {
			foreach ((new \ReflectionClass($this))->getProperties() as $property) {
				if (strpos($property->name, '_') !== 0 && $property->class !== get_class()) {
					$name = $property->name;
					$value = $this->$name;
					if (is_array($value)) {
					    $v = null;
					    if (count($value) > 0) {
					        foreach($value as $k => $vv)
					            $v[$k] = $this->itemToArray($vv, $showEmpty);
                        }
                    }
					else
						$v = $this->itemToArray($value, $showEmpty);
					if (isset($v) || $showEmpty)
						$r[$name] = $v;
				}
			}
		}
		catch(\ReflectionException $e) {
			$this->logger->error(sprintf('%s: %s in %s', get_class($e), $e->getMessage(), $this->_name));
		}
		return $r;
	}

	protected function itemToArray($item, $showEmpty) {
		if (is_object($item)) {
		    if ($item instanceof Base) {
                return $item->toArray($showEmpty);
            }
		    else {
		        return 'object';
            }
        }
		else
			return $item;
	}

    /**
     * @param array $arr
     * @return $this
     */
    public function fromArray(array $arr)
    {
        foreach($this->_fields as $name => $field) {
            if (isset($arr[$name])) {
                switch ($field['type']) {
                    case 'Field':
                    case 'Boolean':
                    case 'DateTime':
                    case 'Arr':
                        $this->$name = $arr[$name];
                        break;
                    case 'KeyValue':
                        foreach ($arr[$name] as $v) {
                            $this->$name[] = [$v[0], $v[1]];
                        }
                        break;
                }
            }
        }
        $this->fromArrayChildren($arr);
        return $this;
    }

    /**
     * @param $json
     * @return $this
     */
    public function fromJson($json)
    {
        return $this->fromArray(json_decode($json, true));
    }

    protected function fromArrayChildren(array $arr){
        foreach($arr as $k => $v) {
            if (method_exists($this, $method = 'obtain'.ucfirst($k)) && is_array($v)) {
                /** @var Base $child */
                $child = $this->$method();
                $child->fromArray($v);
            }
        }
    }

}