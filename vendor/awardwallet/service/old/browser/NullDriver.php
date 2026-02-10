<?php

class NullDriver implements HttpDriverInterface
{

    public function start($proxy = null, $proxyLogin = null, $proxyPassword = null, $userAgent = null){}

    public function stop(){}

    public function isStarted()
    {
        return false;
    }

    public function request(HttpDriverRequest $request)
    {
        throw new \Exception('method should not be called');
    }

    public function getState()
    {
        return [];
    }

    public function setState(array $state){}

    public function setLogger(HttpLoggerInterface $logger){}
}