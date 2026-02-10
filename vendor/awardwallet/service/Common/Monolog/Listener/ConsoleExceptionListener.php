<?php
namespace AwardWallet\Common\Monolog\Listener;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Command\Command;


class ConsoleExceptionListener
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    private function handle(\Throwable $exception, $type, Command $command = null)
    {
        $message = sprintf(
            "%s: %s (uncaught {$type}) at %s line %s while running console command `%s`",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $command instanceof Command ? $command->getName() : ''
        );

        $this->logger->critical($message, ['traceAsString' => $exception->getTraceAsString()]);
    }

    public function onConsoleException(ConsoleExceptionEvent $event)
    {
        $this->handle($event->getException(), 'exception', $event->getCommand());
    }

    public function onConsoleError(ConsoleErrorEvent $event)
    {
        $this->handle($event->getError(), 'error', $event->getCommand());
    }

}