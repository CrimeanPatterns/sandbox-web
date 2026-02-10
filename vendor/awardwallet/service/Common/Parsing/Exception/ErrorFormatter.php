<?php

namespace AwardWallet\Common\Parsing\Exception;

class ErrorFormatter
{

    private string $displayName;

    public function __construct(string $displayName)
    {
        $this->displayName = $displayName;
    }

    public function format(?string $error) : ?string
    {
        if ($error === null) {
            return $error;
        }

        return str_ireplace('%DISPLAY_NAME%', $this->displayName, $error);
    }

}