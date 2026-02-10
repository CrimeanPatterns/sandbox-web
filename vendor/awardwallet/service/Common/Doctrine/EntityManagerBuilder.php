<?php

namespace AwardWallet\Common\Doctrine;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;

class EntityManagerBuilder
{

    public static function create(Connection $connection, Configuration $config) : EntityManager
    {
        AnnotationRegistry::registerLoader('class_exists');
        return EntityManager::create($connection, $config);
    }

}