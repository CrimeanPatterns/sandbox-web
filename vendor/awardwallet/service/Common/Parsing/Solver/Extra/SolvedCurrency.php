<?php


namespace AwardWallet\Common\Parsing\Solver\Extra;


class SolvedCurrency
{

    public $code;

    public $unique;

    public function __construct(?string $code = null, ?bool $unique = null)
    {
        $this->code = $code;
        $this->unique = $unique;
    }

}