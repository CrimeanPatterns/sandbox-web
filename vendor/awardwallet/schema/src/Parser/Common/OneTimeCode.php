<?php


namespace AwardWallet\Schema\Parser\Common;


use AwardWallet\Schema\Parser\Component\Base;

class OneTimeCode extends Base
{

    /**
     * @parsed Field
     * @attr type=onetimecode
     * @attr maxlength=15
     */
    protected $code;

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param $code
     * @throws \AwardWallet\Schema\Parser\Component\InvalidDataException
     */
    public function setCode($code)
    {
        $this->setProperty($code, 'code', false, false);
    }

    public function setCodeAttr(string $regexp, int $length)
    {
        $this->_fields['code']['attr']['regexp'] = $regexp;
        $this->_fields['code']['attr']['maxlength'] = $length;
    }

    public function validate()
    {
        if( empty($this->code) )
            $this->invalid('missing code');
        return $this->valid;
    }

    public function getChildren()
    {
        return [];
    }

}