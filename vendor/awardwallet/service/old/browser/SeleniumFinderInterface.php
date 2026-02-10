<?php

interface SeleniumFinderInterface
{

    /**
     * @return SeleniumServer[]
     */
    public function getServers(SeleniumFinderRequest $request) : array;

}