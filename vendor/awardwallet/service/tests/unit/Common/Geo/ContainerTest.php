<?php

namespace tests\unit\Common\Geo;

use AwardWallet\Common\Geo\GoogleGeo;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ContainerTest extends TestCase
{

    public function testContainer()
    {
        require_once __DIR__ . '/../../../../old/constants.php';

        $containerBuilder = new ContainerBuilder();
        $loader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__));
        $loader->load('config.yml');
        $containerBuilder->compile();

        $geo = $containerBuilder->get('google.geo.public');
        $this->assertArrayHasKey("GeoTagID", $geo->FindGeoTag("test", null, 0, true));

        $geo = $containerBuilder->get("aw.geo.cheap_geocoder.public");
        $this->assertArrayHasKey("GeoTagID", $geo->FindGeoTag("test", null, 0, true));
    }

}