<?php


namespace AwardWallet\Common\Parsing;


use MailSlurp\Apis\EmailControllerApi;
use MailSlurp\Apis\InboxControllerApi;
use MailSlurp\Apis\WaitForControllerApi;
use MailSlurp\Configuration;

class MailslurpApiControllersCustom
{
    /** @var InboxControllerApi $inboxControllerApi */
    private $inboxControllerApi;
    /** @var EmailControllerApi $emailControllerApi */
    private $emailControllerApi;
    /** @var WaitForControllerApi $waitForController */
    private $waitForController;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey('x-api-key', MAILSLURP_API_KEY);

        $this->inboxControllerApi = new InboxControllerApi(null, $config);
        $this->emailControllerApi = new EmailControllerApi(null, $config);
        $this->waitForController = new WaitForControllerApi(null, $config);
    }

    public function getInboxControllerApi(): InboxControllerApi
    {
        return $this->inboxControllerApi;
    }

    public function setInboxControllerApi(InboxControllerApi $inboxControllerApi): void
    {
        $this->inboxControllerApi = $inboxControllerApi;
    }

    public function getEmailControllerApi(): EmailControllerApi
    {
        return $this->emailControllerApi;
    }

    public function setEmailControllerApi(EmailControllerApi $emailControllerApi): void
    {
        $this->emailControllerApi = $emailControllerApi;
    }

    public function getWaitForControllerApi(): WaitForControllerApi
    {
        return $this->waitForController;
    }

    public function setWaitForControllerApi(WaitForControllerApi $waitForController): void
    {
        $this->waitForController = $waitForController;
    }
}