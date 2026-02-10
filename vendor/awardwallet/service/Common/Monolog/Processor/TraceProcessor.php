<?php

namespace AwardWallet\Common\Monolog\Processor;

use Monolog\Logger;

class TraceProcessor {


    public function __construct($level = Logger::DEBUG)
    {
        $this->level = Logger::toMonologLevel($level);
    }

    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record)
    {
    	if ($record['level'] < $this->level)
    		return $record;

        if (
            $record['level'] == Logger::CRITICAL
            && TraceProcessor::isSupressedMessage($record['message'])
        ) {
            // this error will be handled by MysqlComeBack bundle, suppress
            $record['level'] = Logger::INFO;
            $record['level_name'] = Logger::getLevelName($record['level']);
        }

        unset($record['context']['scope_vars']);
        if(isset($record['context']['stack'])) {
            $trace = $record['context']['stack'];
            unset($record['context']['stack']);
        }
        else {
            $trace = debug_backtrace();
            array_shift($trace);
            array_shift($trace);
            array_shift($trace);
            array_shift($trace);
        }

        // we should have the call source now
        $record['extra'] = array_merge(
            $record['extra'],
            array(
                'trace' => self::filterBackTrace($trace),
            )
        );

        return $record;
    }

    public static function isSupressedMessage($message)
    {
        foreach ([
            'MySQL server has gone away',
            'Error while sending QUERY packet',
            'Error reading result set\'s header'
         ] as $needle) {
            if (strpos($message, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function isDeprecatedMessage($message)
    {
        foreach ([
            'Deprecated: Function mcrypt_',
            'User Deprecated:'
         ] as $needle) {
            if (strpos($message, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function filterBackTrace(array $trace)
    {
        return array_map(function(array $frame){
            $result = \array_intersect_key($frame, ['file' => null, 'line' => null, 'function' => null, 'type' => null, 'class' => null, '_filtered_' => null]);
            if(isset($frame['args'])) {
                if (!($frame['_filtered_'] ?? false)) {
                    $result['args'] = self::filterArguments($frame['args'],  1);
                    $result['_filtered_'] = true;
                } else {
                    $result['args'] = $frame['args'];
                }
            } else
                $result['args'] = [];
            return $result;
        }, $trace);
    }

    public static function filterArguments($arguments, int $maxLevel = 0, int $level = 0, array $visited = [], bool $splHash = true, int $keepValuesMaxLevel = -1)
    {
        $REQUEST_CLASS = 'Symfony\\Component\\HttpFoundation\\Request';

        if (!\is_object($arguments) && !\is_array($arguments)) {
            return $arguments;
        }

        $result = [];

        if ($isObject = \is_object($arguments)) {
            $object = $arguments;
            $reflClass = new \ReflectionClass($arguments);
            $properties = [];

            foreach ($reflClass->getProperties(\ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
                try {
                    if ($reflectionProperty->isDefault()) {
                        if (!($isAccessible = $reflectionProperty->isPublic())) {
                            $reflectionProperty->setAccessible(true);
                        }

                        $properties[$reflectionProperty->getName()] = $reflectionProperty->getValue($arguments);

                        if (!$isAccessible) {
                            $reflectionProperty->setAccessible(false);
                        }
                    }
                } catch (\Throwable $exception) {}
            }

            $arguments = \array_merge($properties, \get_object_vars($arguments));

            if ($arguments) {
                $result['_class_'] = \get_class($object) . ($splHash ? ':' . (\spl_object_hash($object)) : '');
            } else {
                return '<' . \get_class($object) . ':' . \spl_object_hash($object) . '>';
            }
        }

        foreach ($arguments as $key => $value) {
            if (($valueIsObject = \is_object($value)) || \is_array($value)) {
                if (
                    ($value instanceof $REQUEST_CLASS) &&
                    ($value->attributes) &&
                    (($splValueHash = \spl_object_hash($value)) === $value->attributes->get('trace_processor_visited', false))
                ) {
                    $value = '<' . \get_class($value) . ':' . $splValueHash . '>';
                } elseif ($value instanceof \DateTimeInterface) {
                    $value = '<' . \get_class($value) . ':' . $value->format(\DateTime::RFC3339_EXTENDED) . '>';
                } elseif ($level < $maxLevel) {
                    $visitedValue = self::removeReferences($value);

                    if (!\in_array($visitedValue, $visited)) {
                        $visited[] = $visitedValue;

                        if ($value instanceof $REQUEST_CLASS && ($value->attributes)) {
                            $value->attributes->set('trace_processor_visited', $splValueHash);
                        }

                        $value = self::filterArguments($value, $maxLevel, $level + 1, $visited, $splHash, $keepValuesMaxLevel);
                    } else {
                        $value = $valueIsObject ?
                            '<ref:' . \get_class($value) . ($splHash ? (':' . \spl_object_hash($value)) : '') . '>' :
                            '<ref:array>';
                    }
                } elseif (
                    $valueIsObject &&
                    (null !== ($entityId = self::getEntityId($value)))
                ) {
                    $value = '<' . \get_class($value) . ':id:' . $entityId . '>';
                } else {
                    $value = "<" . self::getTypeName($value) . ">";
                }
            } elseif (
                ($level > $keepValuesMaxLevel) &&
                !(
                    \is_string($value) &&
                    (
                        (\trim($value) === '') ||
                        (
                            self::isSafeKey($key) &&
                            self::isSafeString($value)
                        )
                    )
                ) &&
                !\is_int($value) &&
                !\is_float($value) &&
                !\is_bool($value) &&
                !\is_null($value)
            ) {
                $value = "<" . self::getTypeName($value) . ">";
            }

            $result[$key] = $value;
        }

        return $result;
    }

    protected static function getEntityId($value)
    {
        static $reflPropertyMap = [];
        
        $valueClassName = \get_class($value);

        if (
            !(\strpos($valueClassName, 'AwardWallet\\MainBundle\\Entity') === 0) &&
            !(\strpos($valueClassName, 'Proxies\\__CG__\\AwardWallet\\MainBundle\\Entity') === 0) &&
            !(\strpos($valueClassName, 'AwardWallet\\EmailBundle\\Entity')) &&
            !(\strpos($valueClassName, 'Proxies\\__CG__\\AwardWallet\\EmailBundle\\Entity') === 0)
        ) {
            return null;
        }

        $classParts = \explode('\\', $valueClassName);
        $classShort = \end($classParts);

        if (\array_key_exists($classShort, $reflPropertyMap)) {
            $reflProp = $reflPropertyMap[$classShort];
        } else {
            if ('Usr' === $classShort) {
                $idProperty = 'userid';
            } else {
                $idProperty = \strtolower($classShort) . 'id';
            }

            $reflProp = null;

            try {
                $reflClass = new \ReflectionClass($valueClassName);

                foreach ($reflClass->getProperties(
                    \ReflectionProperty::IS_PRIVATE |
                    \ReflectionProperty::IS_PROTECTED |
                    \ReflectionProperty::IS_PUBLIC)
                     as $reflectionProperty
                ) {
                    if (\strtolower($reflectionProperty->getName()) === $idProperty) {
                        $reflProp = $reflectionProperty;

                        break;
                    }
                }

                if (!$reflProp) {
                    $reflProp = $reflClass->getProperty('id');
                }

            } catch (\ReflectionException $e) {}

            $reflPropertyMap[$classShort] = $reflProp;
        }

        if (!$reflProp) {
            return null;
        }

        try {
            $isAccessible = $reflProp->isPublic();

            if (!$isAccessible) {
                $reflProp->setAccessible(true);
            }

            $id = $reflProp->getValue($value);

            if (!$isAccessible) {
                $reflProp->setAccessible(false);
            }
        } catch (\Throwable $e) {
            return null;
        }

        if (\is_numeric($id)) {
            return $id;
        }

        return null;
    }

    protected static function isSafeString(string $value) : bool
    {
        return
            (\is_numeric($value)) ||
            (false !== \filter_var($value, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4 | \FILTER_FLAG_IPV6)) ||
            (false !== \filter_var($value, \FILTER_VALIDATE_EMAIL));
    }

    protected static function isSafeKey($key) : bool
    {
        return \is_numeric($key) || !\preg_match('/(pass|secre|key|sign|phrase|url|uri|add?ress?|auth|credential|secur|pwd|token|sid|sess|crypt|hid|uid)/ims', $key);
    }

    public static function filterMessage(\Throwable $exception)
    {
        $message = $exception->getMessage();
        $driverExceptionClass = 'Doctrine\\DBAL\\DBALException';

        if (
            ($exception instanceof $driverExceptionClass) &&
            (\strpos($message, "An exception occurred while executing '") === 0)
        ) {
            $previousException = $exception->getPrevious();

            if ($previousException) {
                $previousExceptionMessage = $previousException->getMessage();

                if (\mb_strlen($previousExceptionMessage) > 1024) {
                    $previousMessagePart =
                        ":\n\n"  .
                        \preg_quote(\mb_substr($previousExceptionMessage, 0, 512), '/') .
                        '.+' .
                        \preg_quote(\mb_substr($previousExceptionMessage, -512), '/');
                } else {
                    $previousMessagePart =
                        ":\n\n" .
                        \preg_quote($previousExceptionMessage, '/');
                }
            } else {
                $previousMessagePart = '';
            }

        if (preg_match("/^An exception occurred while executing '(.+)' with params (.+){$previousMessagePart}$/ims", $message, $matches)) {
                $params = @json_decode($matches[2], true);

                if (!is_array($params)) {
                    $params = sprintf('[error occurred while filtering params, see %s::filterMessage method]', self::class);
                } else {
                    $params = self::formatParameters(TraceProcessor::filterArguments($params,  1));
                }

                $message = "An exception occurred while executing '{$matches[1]}' with params {$params}" . ($previousException ? (":\n\n" . $previousException->getMessage()) : '');
            }
        }

        return $message;
    }

    /**
     * @see \Doctrine\DBAL\DBALException::formatParameters
     *
     * Returns a human-readable representation of an array of parameters.
     * This properly handles binary data by returning a hex representation.
     *
     * @param array $params
     *
     * @return string
     */
    private static function formatParameters(array $params)
    {
        return '[' . \implode(', ', array_map(function ($param) {
            $json = @\json_encode($param);

            if (! \is_string($json) || $json == 'null' && \is_string($param)) {
                // JSON encoding failed, this is not a UTF-8 string.
                return '"\x' . \implode('\x', \str_split(\bin2hex($param), 2)) . '"';
            }

            return $json;
        }, $params)) . ']';
    }


    private static function removeReferences($value, $level = 0)
    {
        if(\is_object($value))
            return \spl_object_hash($value);
        else
            if(\is_array($value)) {
                if($level < 30)
                    return \array_map(function ($arrItem) use ($level) {
                        return self::removeReferences($arrItem, $level + 1);
                    }, $value);
                else
                    return [];
            }
            else
                return $value;
    }

    private static function getTypeName($value){
        if(\is_object($value) && !empty($class = \get_class($value)))
            return $class;
        return \gettype($value);
    }

}