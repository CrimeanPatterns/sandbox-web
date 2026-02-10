<?php


namespace AwardWallet\Common\Geo\Google;


abstract class Parameters
{
    /**
     * Should only contain string representation of set parameters and nulls otherwise.
     *
     * @return array
     */
    abstract protected function getAllParametersAsArray(): array;

    /**
     * @return string[]
     */
    public function toArray(): array
    {
        $filteredArray = array_filter($this->getAllParametersAsArray(), function ($parameterValue) {
            return null !== $parameterValue;
        });
        array_walk($filteredArray, function (&$value) {
            $value = (string) $value;
        });
        return $filteredArray;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return md5(serialize($this->toArray()));
    }
}