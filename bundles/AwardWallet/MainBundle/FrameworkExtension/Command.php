<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Command extends \Symfony\Component\Console\Command\Command  implements ContainerAwareInterface
{

    /**
     * @return ContainerInterface
     *
     * @throws \LogicException
     */
    protected function getContainer()
    {
        return getSymfonyContainer();
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
    }

}