<?php

namespace AwardWallet\Engine;

use SeleniumFinderRequest;

class Settings
{
    public const DATACENTERS_EU = ['lon1', 'fra1'];
    public const DATACENTERS_USA = ['nyc1', 'nyc2', 'nyc3', 'sfo1', 'sfo3'];
    public const DATACENTERS_NORTH_AMERICA = ['nyc1', 'nyc2', 'nyc3', 'sfo1', 'sfo3', 'tor1'];

    // for reward availability
    public const RA_ZONE_STATIC = "static";
    public const RA_ZONE_RESIDENTIAL = 'us_residential';

    private static $awUrl = 'https://awardwallet.com';

    public static function getPacFile()
    {
//        return null; // DISABLED

        return 'https://loyalty.awardwallet.com/pac';
    }

    public static function getAwUrl()
    {
        return self::$awUrl;
    }

    public static function getExcludedHosts()
    {
        return [
            'affiliate.flyuia.com',
            '*adobedtm.com',
            '*adrum-ext*',
            '*appdynamics.com',
            '*atgsvcs.com',
            '*btttag.com',
            '*criteo.net',
            '*custhelp.com',
            '*demdex.net',
            //	'*doubleclick.*',
            '*ensighten.com',
            '*facebook.*',
            '*connect.facebook.*',
            'connect.facebook.net',
            '*flashtalking.com',
            '*gigya.com',
            '*google-analytics.com',
            '*intentmedia.net',
            '*levexis.com',
            '*mathtag.com',
            '*mpsnare.iesnare.com',
            '*nexus.ensighten.com',
            '*pixel-eu.mythings.com',
            '*rightnowtech.com',
            '*tags.tiqcdn.*',
            '*twitter.*',
            '*tracking-protection.cdn.mozilla.*',
            '*uplift-platfrom.com',
            '*veinteractive.com',
            '*webtrendslive.com',
            'tiles.services.mozilla.com',
            'location.services.mozilla.com',
            'shavar.services.mozilla.com',
            '*.mozilla.org',
            '*.mozilla.net',
            'mozilla.net',
            'firefox.settings.services.mozilla.com',
            'ciscobinary.openh264.org',
            'service.maxymiser.net',
            'ocsp.digicert.com',
            'vendorweb.citibank.com', // dinersclub -> int
            '*.quantummetric.com',
            'monetate-qa-tool.surge.sh', // iberia
            'cm.everesttech.net', // basspro
            '*.googletagmanager.*', // basspro
            'web.aexp-static.com', // amexgc
            'bat.bing.com', // blooming
            'app-chatbotlfwindow-v1-prd.azureedge.net', // aviancataca
            'static.ada.support', // ???
            //            'api.travelid.austrian.com', // lufthansa
            '*.go-mpulse.net',
            'go-mpulse.net',
            'collection.decibelinsight.net',

            // refs #20114
            '*.analytics.yahoo.com',
            '*.adnxs.com',
            '*.snapchat.com',
            'teads.tv',
            '*.teads.tv',
            'pixel.rubiconproject.com',
            'pixel.tapad.com',
            'pixel.advertising.com',
            'www.googletagmanager.com',
            '*.doubleclick.net',
            'doubleclick.net',
            '*.contentsquare.net',
            '*.media6degrees.com',
            'snippets.cdn.mozilla.net',
            'push.services.mozilla.com',

            'www.googleadservices.com',
            'www.googletagservices.com',
            'adservice.google.com',
            'tpc.googlesyndication.com',

            'ct.pinterest.com',
            '*.thebrighttag.com',
            'thebrighttag.com',
            'tagcommander.com',
            '*.tagcommander.com',
            'airpr.com',
            '*.airpr.com',
            'casalemedia.com',
            '*.casalemedia.com',
            'bluekai.com',
            '*.bluekai.com',
            'yimg.com',
            '*.yimg.com',
            'fullstory.com',
            '*.fullstory.com',
            '*.powerequipmentdirect.com',

            //            'fonts.googleapis.com',// jetblue
            'p2pcontent-fd-prod.azurefd.net',
            //            '*.azurewebsites.net',// it broken msccruises

            'www.vegascamgirls.com',
            'mobile.api.coingecko.com',
            'www.vonmaur.com',
            //            'www.gstatic.com',// officedepot
            'stripe.com',
        ];
    }

    /**
     * @internal
     */
    public static function setAwUrl($host)
    {
        self::$awUrl = $host;
    }

    public static function canUseSeleniumMonitor(SeleniumFinderRequest $request)
    {
//        if (in_array($request->getProviderCode(), ['etihad']))
//            return false;

        return true;
    }
}
