<?php


namespace AwardWallet\Common\API\Filter;


abstract class BaseField
{

    public abstract function filterCancelled(): bool;

    public abstract function getRequiredFields();

    public function getRequiredFieldsForClass($class)
    {
        return $this->getRequiredFields()[$class] ?? [];
    }

}