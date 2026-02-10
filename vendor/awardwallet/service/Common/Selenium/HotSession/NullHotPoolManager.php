<?php

namespace AwardWallet\Common\Selenium\HotSession;

use AwardWallet\Common\Document\HotSession;
use Psr\Log\LoggerInterface;

class NullHotPoolManager implements HotPoolManagerInterface
{
    public function getConnection(string $prefix, string $provider, ?string $accountKey): ?\SeleniumConnection
    {
        return null;
    }

    public function saveConnection(\SeleniumConnection $connection, string $prefix, string $provider, ?string $accountKey): void
    {
    }

    public function deleteConnection(\SeleniumConnection $connection): void
    {
    }

    /**
     * @internal
     */
    public function getHotConnection(HotSession $hotSession): ?\SeleniumConnection
    {
        return null;
    }

}