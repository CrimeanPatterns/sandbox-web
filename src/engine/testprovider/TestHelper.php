<?php

namespace AwardWallet\Engine\testprovider;

trait TestHelper
{
    public function getAwUrl()
    {
        if (ConfigValue(CONFIG_TRAVEL_PLANS)) {
            return getSymfonyContainer()->getParameter('requires_channel') . '://' . getSymfonyContainer()->getParameter('host');
        } else {
            return parse_url(DEBUG_SERVICE_LOCATION, PHP_URL_SCHEME) . '://' . parse_url(DEBUG_SERVICE_LOCATION, PHP_URL_HOST);
        }
    }
}
