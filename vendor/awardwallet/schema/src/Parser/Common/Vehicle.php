<?php

namespace AwardWallet\Schema\Parser\Common;

use AwardWallet\Schema\Parser\Component\Base;

class Vehicle extends Base
{
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=medium
     */
    protected $type;
    /**
     * @parsed Field
     * @attr type=have_number
     * @attr length=short
     */
    protected $length;
    /**
     * @parsed Field
     * @attr type=have_number
     * @attr length=short
     */
    protected $height;
    /**
     * @parsed Field
     * @attr type=have_number
     * @attr length=short
     */
    protected $width;
    /**
     * @parsed Field
     * @attr type=basic
     * @attr length=medium
     */
    protected $model;

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Vehicle
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setType($type, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($type, 'type', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param mixed $length
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Vehicle
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setLength($length, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($length, 'length', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param mixed $height
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Vehicle
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setHeight($height, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($height, 'height', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param mixed $width
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Vehicle
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setWidth($width, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($width, 'width', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param mixed $model
     * @param bool $allowEmpty
     * @param bool $allowNull
     * @return Vehicle
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setModel($model, $allowEmpty = false, $allowNull = false)
    {
        $this->setProperty($model, 'model', $allowEmpty, $allowNull);
        return $this;
    }

    /**
     * @return Base[]
     */
    protected function getChildren()
    {
        return [];
    }

    public function checkEmpty()
    {
        $empty = true;
        foreach([$this->type, $this->length, $this->height, $this->width, $this->model] as $item)
            $empty = $empty && empty($item);
        if ($empty)
            $this->invalid('empty vehicle');
        return $this->valid;
    }
}