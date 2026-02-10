<?php

require_once __DIR__ . '/autoload.php';

function getSymfonyContainer() : \Symfony\Component\DependencyInjection\ContainerInterface
{
    $kernel = getSymfonyKernel();
    return $kernel->getContainer();
}

function getSymfonyKernel() : AppKernel
{
    static $kernel;

    if ($kernel !== null) {
        return $kernel;
    }

    $kernel = new AppKernel('dev', true);
    $kernel->boot();
    return $kernel;
}

