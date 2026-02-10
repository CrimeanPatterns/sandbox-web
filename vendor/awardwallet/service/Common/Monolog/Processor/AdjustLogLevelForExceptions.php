<?php

namespace AwardWallet\Common\Monolog\Processor;

use Monolog\Logger;
use Throwable;

class AdjustLogLevelForExceptions
{
    /**
     * @var array
     */
    private $exceptionsClassMap;

    public function __construct(array $exceptionsMap)
    {
        $this->exceptionsClassMap = $exceptionsMap;
    }

    public function __invoke(array $record): array
    {
        if (!isset($record['context']['exception'])) {
            /**
             * Symfony's ErrorListener will provide a Throwable through the log message's context.
             * If the context has no "exception" key, we don't have to further process this log record.
             *
             * @see ErrorListener::logException()
             */
            return $record;
        }

        $throwable = $record['context']['exception'];
        if (!$throwable instanceof Throwable) {
            // For some reason the provided value is not an actual exception, so we can't do anything with it
            return $record;
        }

        $currentLevel = $record['level'];

        if (!\is_int($currentLevel)) {
            return $record;
        }

        $modifiedLogLevel = $this->determineLogLevel($throwable, $currentLevel);

        if ($modifiedLogLevel === $currentLevel) {
            return $record;
        }

        $record['level'] = $modifiedLogLevel;
        $record['level_name'] = Logger::getLevelName($modifiedLogLevel);

        return $record;
    }

    private function determineLogLevel(Throwable $throwable, int $currentLevel): int
    {
        foreach ($this->exceptionsClassMap as $exceptionClass => $newLevel) {
            if ($throwable instanceof $exceptionClass) {
                return $newLevel;
            }
        }

        return $currentLevel;
    }
}
