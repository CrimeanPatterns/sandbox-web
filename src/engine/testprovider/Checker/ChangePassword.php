<?php
/**
 * Created by PhpStorm.
 * User: puzakov
 * Date: 28/11/2017
 * Time: 16:52.
 */

namespace AwardWallet\Engine\testprovider\Checker;

use AwardWallet\Engine\testprovider\Success;

class ChangePassword extends Success
{
    public $KeepState = true;

    protected function changePasswordInternal(string $newPassword)
    {
        switch ($this->AccountFields['Login2']) {
            case '-s':
                return true;

            case '-f':
                return false;

            case '-e':
                parent::changePasswordInternal($newPassword);
        }

        return false;
    }
}
