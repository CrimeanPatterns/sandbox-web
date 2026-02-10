<?php

namespace AwardWallet\Common\Monolog\Handler;

use Monolog\Logger;
use Symfony\Bridge\Monolog\Handler\SwiftMailerHandler;

class MailHandler extends SwiftMailerHandler {

    protected function send($content, array $records) {
        $message = $this->buildMessage($content, $records);
        $message->setBody($content, 'text/html', 'utf-8');

        $app = explode(":", $message->getSubject())[0];

        $subject = $this->getSubject($records);

        if(!empty($subject)){
            $subject = strpos($subject, '[Dev Notification]') === false ? $message->getSubject() . ': ' . $subject : $subject;
            $message->setSubject($subject);
        }

        $message->setFrom("error@awardwallet.com", $app . " at " . gethostname());

        try {
            $this->mailer->send($message);
        }
        catch(\Swift_SwiftException $e){
            // pass error to next error handler
        }
    }

    private function getSubject(array $records){
        $maxLevel = 0;
        $result = false;

        foreach ($records as $record) {
            // Sending Dev Notifications
            // TODO: unused? remove? see EmailPrototype
            if (isset($record['context']['DevNotification']) && $maxLevel <= Logger::WARNING && $record['level'] > $maxLevel) {
                $title = substr(isset($record['context']['Title']) ? $record['context']['Title'] : '', 0, 250);
                $result = sprintf('[Dev Notification]: %s', $title);
                $maxLevel = Logger::WARNING;
                continue;
            }

            // replace subject with critical error for better email grouping
            if (isset($record['level'], $record['message']) && $record['level'] >= Logger::WARNING && $record['level'] > $maxLevel) {
                $result = substr($record['message'], 0, 250);
                $maxLevel = $record['level'];
            }
        }

        return $result;
    }
}