<?php

namespace AwardWallet\Common\Selenium;

class RecordedXHR
{

    public function __construct(array $event)
    {
        $this->request = new RecordedRequest($event['request']);
        $this->response = new RecordedResponse($event['response']);
    }

}