<?php

namespace AwardWallet\Common\Itineraries;

use Psr\Log\LoggerInterface;
use JMS\Serializer\Annotation\Exclude;

abstract class AbstractCollection implements \ArrayAccess, \Countable
{
    /**
     * @var array
     */
    protected $collection = [];
    /**
     * @var LoggerInterface
     * @Exclude
     */
    protected $logger;

    public function __construct(LoggerInterface $logger = null){
        $this->logger = $logger;
        $this->collection = [];
    }

    public function offsetExists($offset){
        return isset($this->collection[$offset]);
    }

    public function offsetGet($offset){
        return isset($this->collection[$offset]) ? $this->collection[$offset] : null;
    }

    public function offsetSet($offset, $value){
        if (is_null($offset))
            $this->collection[] = $value;
        else
            $this->collection[$offset] = $value;
    }

    public function offsetUnset($offset){
        unset($this->collection[$offset]);
    }

    /**
     * @return array
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @param array $collection
     * @return $this
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->collection);
    }
}