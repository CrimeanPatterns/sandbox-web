<?php

namespace AwardWallet\ExtensionWorker;

interface LoginWithConfNoInterface
{

    /**
     * @param string[] $confNoFields - provider-specific fields, like: ["RecordLocator" => "ABC123", "LastName" => "Doe"]
     */
    public function getLoginWithConfNoStartingUrl(array $confNoFields) : string;

    /**
     * @return bool - whether login was successful
     */
    public function loginWithConfNo(Tab $tab, array $confNoFields) : bool;

}