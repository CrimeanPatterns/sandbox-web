<?php

class CheckerLogger implements \Psr\Log\LoggerInterface
{

    /** @var HttpBrowser $http */
    protected $http;
    /**
     * @var \Monolog\Formatter\LineFormatter
     */
    protected $formatter;

    public function __construct($http)
    {
        $this->http = $http;
        $this->formatter = new \Monolog\Formatter\LineFormatter("%message% %context% %extra%\n");
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function emergency($message, array $context = array())
    {
        $this->correctHtmlClass(__FUNCTION__, $context);
        $this->log(LOG_LEVEL_EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function alert($message, array $context = array())
    {
        $this->correctHtmlClass(__FUNCTION__, $context);
        $this->log(LOG_LEVEL_ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function critical($message, array $context = array())
    {
        $this->correctHtmlClass(__FUNCTION__, $context);
        $this->log(LOG_LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function error($message, array $context = array())
    {
        $this->correctHtmlClass(__FUNCTION__, $context);
        $this->log(LOG_LEVEL_ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function warning($message, array $context = array())
    {
        $this->correctHtmlClass(__FUNCTION__, $context);
        $this->log(LOG_LEVEL_WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function notice($message, array $context = array())
    {
        $this->correctHtmlClass(__FUNCTION__, $context);
        $this->log(LOG_LEVEL_NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function info($message, array $context = array())
    {
        $this->correctHtmlClass(__FUNCTION__, $context);
        $this->log(LOG_LEVEL_INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function debug($message, array $context = array())
    {
        $this->correctHtmlClass(__FUNCTION__, $context);
        $this->log(LOG_LEVEL_DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level - could be int - one of LOG_LEVEL_ constants or string - monolog logger level name
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        global $arLogLevelText;
        if (
            stripos($message, 'Warning: DOMDocument::loadHTML()') !== false
        ) {
            return;
        }
        $htmlEncode = isset($context['HtmlEncode']) ? $context['HtmlEncode'] : false;
        $htmlClass = isset($context['HtmlClass']) ? $context['HtmlClass'] : null;
        $pre = isset($context['pre']) ? $context['pre'] : false;
        $htmlAttributes = $htmlClass ? ' class="' . $htmlClass . '"' : '';
        $logContext = $context;
        unset($logContext['HtmlEncode'], $logContext['HtmlClass'], $logContext['pre'], $logContext['Header']);
        if (!empty($logContext)) {
            if (is_numeric($level)) {
                $levelName = $arLogLevelText[$level] ?? \Monolog\Logger::getLevelName($level);
            }
            else {
                $levelName = $level;
            }
            $message = $this->formatter->format([
                'message' => $message,
                'context' => $logContext,
                'extra' => [],
                'datetime' => date("Y-m-d H:i:s"),
                "channel" => "app",
                "level_name" => $levelName
            ]);
        }
        if ($pre) {
            $message = '<pre>' . $message . '</pre>';
        }
        if (isset($context['Header'])) {
            $headerLevel = $context['Header'];
            $htmlEncode = false;
            $message = "<h{$headerLevel}{$htmlAttributes}>$message</h{$headerLevel}>";
        } elseif (!$htmlEncode) {
            $message = "<span{$htmlAttributes}>$message</span>";
        }
        if (isset($this->http)) {
            $this->http->Log($message, $level, $htmlEncode);
        }
    }

    protected function correctHtmlClass($callerFunctionName, &$context)
    {
        $logClassName = 'awlog-' . $callerFunctionName;
        if (isset($context['HtmlClass'])) {
            $context['HtmlClass'] .= $context['HtmlClass'] . ' ' . $logClassName;
        } else {
            $context['HtmlClass'] = $logClassName;
        }
    }

}
