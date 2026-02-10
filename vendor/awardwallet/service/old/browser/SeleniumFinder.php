<?php

class SeleniumFinder
{

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @return SeleniumFinderInterface
     */
    public static function createByContainer(\Symfony\Component\DependencyInjection\ContainerInterface $container)
    {
        if(!empty($container->getParameter("selenium_consul_address"))) {
            return new SeleniumConsulFinder(
                $container->getParameter("selenium_consul_address"),
                $container->get("logger"),
                $container->has('aw.curl_driver') ? $container->get('aw.curl_driver') : new CurlDriver()
            );
        }
        else
            return new SeleniumSingleServerFinder($container->getParameter("selenium_host"));
    }
}