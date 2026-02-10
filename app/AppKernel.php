<?php

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{

    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new AwardWallet\MainBundle\AwardWalletMainBundle(),
            new \Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle(),
            new OldSound\RabbitMqBundle\OldSoundRabbitMqBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
		);

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config/parameters.yml');
        $loader->load(__DIR__ . '/config/config.yml');
        $loader->load(__DIR__ . '/config/services.yml');
        $loader->load(__DIR__ . '/../vendor/awardwallet/service/Common/Geo/services.yml');
        $loader->load(__DIR__ . '/../vendor/awardwallet/service/Common/Parsing/Solver/services.yml');
        $loader->load(__DIR__ . '/../vendor/awardwallet/service/Common/Parsing/Filter/FlightStats/services.yml');
        $loader->load(__DIR__ . '/../vendor/awardwallet/service/Common/Parsing/Web/services.yml');
        $loader->load(__DIR__ . '/../vendor/awardwallet/service/Common/AirLabs/services.yml');
        $loader->load(__DIR__ . '/../vendor/awardwallet/service/Common/Selenium/services.yml');
        $loader->load(__DIR__ . '/../vendor/awardwallet/service/Common/Selenium/HotSession/services.yml');
        $loader->load(__DIR__ . '/../vendor/awardwallet/extension-worker/src/services.yml');
        $loader->load(__DIR__ . '/config/mocks.yml');
    }

}
