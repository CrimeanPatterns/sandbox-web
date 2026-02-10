<?php

namespace AwardWallet\Common\Monolog\Listener;

use AwardWallet\Common\Monolog\Processor\AppProcessor;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class DebugHeadersListener
{

    /**
     * @var AppProcessor
     */
    private $appProcessor;

    public function __construct(AppProcessor $appProcessor)
    {
        $this->appProcessor = $appProcessor;
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();

        $response->headers->set('X-RequestId', $this->appProcessor->getRequestId());
    }

}