<?php

namespace AwardWallet\Common\Itineraries;

use JMS\Serializer\Annotation\Exclude;
use Psr\Log\LoggerInterface;

class LoggerEntity
{
    /**
     * @var LoggerInterface
     * @Exclude
     */
    protected $logger;

    public function __construct(LoggerInterface $logger = null){
        $this->logger = $logger;
    }

    public function __set($key, $val){
        $this->$key = $val;

        if(!is_object($val) && !is_array($val) && !empty($this->logger))
            $this->logger->debug(get_class($this). " Setting $key = $val");
    }

    public function __get($key){
        return $this->$key;
    }

    public function __isset($key)
    {
        return isset($this->$key);
    }

    /**
     * Itinerary properties setter.
     * Example: If property $recordLocator is defined in child $obj, just call $obj->setRecordLocator($value)
     */
    public function __call($method, $args){
        $prefix = substr($method, 0, 3);

        $postfix = substr($method, -6);
        if($prefix === 'get' && $postfix === 'ForJMS'){
            $method = str_replace('ForJMS', '', $method);
            $prop = lcfirst(substr($method, 3));
            return ($this->$prop instanceof AbstractCollection) ? $this->$prop->getCollection() : $this->$prop;
        }

        $prop = lcfirst(substr($method, 3));
        if($prefix !== 'set' || !property_exists(get_class($this), $prop))
            throw new \RuntimeException('Call to undefined method');

        if(!isset($args[0]))
            throw new \RuntimeException('Undefined argumet for method '.$method);

        $this->$prop = $args[0];
        return $this;
    }

}