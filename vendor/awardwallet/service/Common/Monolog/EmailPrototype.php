<?php

namespace AwardWallet\Common\Monolog;

use Monolog\Logger;

class EmailPrototype
{

    /**
     * @var \Swift_Mailer
     */
    private $mailer;
    /**
     * @var string
     */
    private $appTitle;
    /**
     * @var string|null
     */
    private $devAddress;

    public function __construct(\Swift_Mailer $mailer, string $appTitle, ?string $devAddress = null)
    {
        $this->mailer = $mailer;
        $this->appTitle = $appTitle;
        $this->devAddress = $devAddress;
    }

    public function buildMessage(string $content, array $records) : \Swift_Message
    {
        /** @var \Swift_Message $result */
        $result = $this->mailer->createMessage();
        $result
            ->setFrom('error@awardwallet.com')
            ->setTo('error@awardwallet.com')
            ->setSubject($this->getSubject($records))
            ->setContentType('text/html')
        ;

        // при миграции на symfony4 у метода Swift_Message::setDate() изменен интерфейс
        if (interface_exists(\Swift_Mime_Message::class) && $result instanceof \Swift_Mime_Message) {
            $result->setDate(time());
        } else {
            $result->setDate(new \DateTime());
        }
            
        if ($this->devAddress !== null && stripos($result->getSubject(), '[Dev Notification]') !== false) {
            $result->setTo($this->devAddress);
        }

        return $result;
    }

    private function getSubject(array $records){
        $maxLevel = 0;
        $defaultTitle = $this->appTitle . ": An Error Occurred!";
        $result = $defaultTitle;

        foreach ($records as $record) {
            // Sending Dev Notifications
            if (isset($record['context']['DevNotification']) && $maxLevel <= Logger::WARNING && $record['level'] > $maxLevel) {
                $title = substr(isset($record['context']['Title']) ? $record['context']['Title'] : '', 0, 250);
                $result = sprintf($this->appTitle . ' [Dev Notification]: %s', $title);
                $maxLevel = Logger::WARNING;
                continue;
            }

            // replace subject with critical error for better email grouping
            if (isset($record['level'], $record['message']) && $record['level'] >= Logger::WARNING && $record['level'] > $maxLevel) {
                $result = $defaultTitle . ': ' . substr($record['message'], 0, 250);
                $maxLevel = $record['level'];
            }
        }

        return $result;
    }
}
