<?php

namespace AwardWallet\Common\Monolog\Formatter;

use AwardWallet\Common\Monolog\Processor\AppProcessor;
use AwardWallet\Common\Monolog\Processor\TraceProcessor;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;

class HtmlFormatter extends LineFormatter {

    const SERVER_KEYS_ALLOWED = [
        'SCRIPT_URL', 'SCRIPT_URI', 'HTTP_HOST', 'HTTP_ACCEPT', 'HTTP_ACCEPT_ENCODING', 'HTTP_ACCEPT_LANGUAGE', 'HTTP_USER_AGENT',
        'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED_PORT', 'HTTP_X_FORWARDED_PROTO', 'HTTP_CONNECTION',
        'SERVER_NAME', 'SERVER_ADDR', 'SERVER_PORT', 'REMOTE_ADDR', 'DOCUMENT_ROOT', 'REQUEST_SCHEME',
        'SCRIPT_FILENAME', 'SERVER_PROTOCOL', 'REQUEST_METHOD', 'QUERY_STRING', 'REQUEST_URI', 'SCRIPT_NAME', 'PHP_SELF', 'REQUEST_TIME_FLOAT',
        'HTTPS', 'HTTP_PORT'
    ];
	
	protected $htmlPattern = '
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>An Error Occurred!</title>
	<style type="text/css">
	body {
		color: #3F3F3F;
		font-size: 14px;
	}
	</style>
</head>
<body>
%body%
</body>
</html>
';
	protected $colors = array(
		Logger::DEBUG 	=> "#FFF",
		Logger::INFO 	=> "#FDF8CC",
		Logger::NOTICE  => "#FFDDD7",
		Logger::WARNING	=> "#FFDDD7",
		Logger::ERROR	=> "#FFC1B7",
		Logger::CRITICAL=> "#FF7D66",
		Logger::ALERT	=> "#FF5537",
		Logger::EMERGENCY	=> "#FF5537",
	);

    /**
     * @var AppProcessor
     */
    private $appProcessor;

    public function __construct(AppProcessor $appProcessor) {
        parent::__construct();
        $this->appProcessor = $appProcessor;
        $this->format = "[%datetime%] %channel%.%level_name%: %message% %context%";
    }
	
	public function format(array $record): string
    {
        $record['datetime'] = $record['datetime']->format($this->dateFormat);

        $output = $this->format;

        $output .= $this->addExtraInformation($record);

        $loyaltyExtraValues = ['accountId', 'partner', 'provider', 'requestId', 'worker_executor'];
        foreach ($loyaltyExtraValues as $extraKey)
            if(isset($record['extra'][$extraKey]))
                $record['context'][$extraKey] = $record['extra'][$extraKey];

        unset($record['extra']);

        foreach ($record as $var => $val) {
            $output = str_replace('%'.$var.'%', htmlspecialchars($this->convertToString(TraceProcessor::filterArguments($val, 1, 0, [], true, 1))), $output);
        }

        return $this->filterSensitiveData($output);
    }

    public function formatBatch(array $records): string
    {
        $message = '';
        $record = end($records);
        /* if DevNotification then dump only last 5 logger messages */
        if(isset($record['context']['DevNotification'])) {
            $records = array_slice($records, -2, 2);
        }

        foreach ($records as $record) {
            if(isset($record['context']['DevNotification'])){
                $message .= "<div style=\"background-color: #CAFAB4; padding: 3px; margin: 2px;\">" . $this->formatNotification($record) . "</div>";
                continue;
            }

            $message .= "<div style=\"background-color: ".$this->colors[$record['level']]."; padding: 3px; margin: 2px;\">".$this->format($record)."</div>";
        }

        $message .= $this->addExtraInformation($record);

        $message .= "<div style=\"padding: 5px; background-color: #CAFAB4;\">
        <b>RequestID: </b>" . $this->appProcessor->getRequestId() . "<br/>
        <b>UserID: </b>" . $this->appProcessor->getUserId() . "<br/>
        <b>PID: </b>" . getmypid() . "
    </div>";
        $message .= "<div style=\"padding: 5px; background-color: #CAFAB4;\"><b>SERVER</b><br /><pre>" . htmlspecialchars($this->filterSensitiveData(print_r(array_intersect_key($_SERVER,
                array_flip(self::SERVER_KEYS_ALLOWED)), true))) . "</pre></div>";
        if (!empty($_POST)) {
            $message .= "<div style=\"padding: 5px; background-color: #CAFAB4;\"><b>POST</b><br /><pre>" . htmlspecialchars($this->filterSensitiveData(print_r(TraceProcessor::filterArguments($_POST,
                    1), true))) . "</pre></div>";
        }
        if (isset($_SESSION)) {
            $message .= "<div style=\"padding: 5px; background-color: #CAFAB4;\"><b>SESSION</b><br /><pre>" . htmlspecialchars($this->filterSensitiveData(print_r(TraceProcessor::filterArguments($_SESSION,
                    1), true))) . "</pre></div>";
        }
        # Server name
        $state = '';
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $ref = 'http://' . ($_SERVER['SERVER_NAME'] ?? '') . ($_SERVER['SCRIPT_NAME'] ?? '');
            if (($_SERVER['QUERY_STRING'] ?? '') != "") {
                $ref .= "?" . $_SERVER['QUERY_STRING'];
            }
            $state = "URL: <a href=$ref>$ref</a><hr />" . $state;
        } else {
            $state = "Script: " . ($_SERVER['SCRIPT_NAME'] ?? null) . "<br />\n{$state}";
        }
        $message = "<div>$state</div>" . $message;

        $message = str_replace("%body%", $message, $this->htmlPattern);
        return $message;
    }

    /**
     * for sending notification emails ($logger->alert(...))
     * @param array $record
     * @return string
     */
    protected function formatNotification(array $record)
    {
        $record['context'] = array_filter($record['context'], function($value) : bool {
            return $value !== null;
        });

        $extra = $record['context']['extra'] ?? [];
        unset($record['context']['extra']);

        $record['context'] = array_map(function($value) {
            return htmlspecialchars($this->convertToString($value));
        }, $record['context']);

        foreach ($extra as $key => $links) {
            if (isset($links["value"])) {
                if (isset($record['context'][$key]))
                    $record['context'][$key] = "<a href='{$links["value"]}' target='_blank'>{$record['context'][$key]}</a>";
                unset($links["value"]);
            }
            foreach ($links as $title => $link) {
                $record['context'][$key] .= ", <a href='{$link}' target='_blank'>{$title}</a>";
            }
        }

        unset($record['context']['DevNotification']);

        $result = "";
        foreach ($record['context'] as $key => $value) {
            $result .= "$key: " . $value . "<br/>\n";
        }

        return $result;
    }

    /**
    * @param string $string
    * @return string
    */
    private function filterSensitiveData($data)
    {
        // hide passwords from global variables
        $globalFields = ['Pass', 'Credential', 'GoogleAuthSecret', 'GoogleAuthRecoveryCode', 'BrowserKey', 'XSRF-TOKEN', 'ItineraryCalendarCode', '_csrf\/\w*', 'FormToken'];
        $globalFieldsGroup = '(?:' . implode('|', $globalFields) . ')';
        $data = preg_replace('/^(\s*)\[(' . $globalFieldsGroup .'[^]]*)\] => ([^\n]+)(\n)(?=\s*(\)|\[))/ims', '$1[$2] => xxx_$2_exists_and_hidden_xxx$4', $data);

        // hide bcrypt hashes in serialized tokens
        $data = preg_replace('/\$2y\$.{56}/', '\$2y\$13xxxxxxxxxxxxxxx_bcrypt_hash_is_hidden_xxxxxxxxxxxxxxxx', $data);

        // hide md5 hashes in serialized tokens
        $data = preg_replace('/s:32:"[a-f0-9]{32}"/i', 's:32:"xxxxxx_md5_hash_is_hidden_xxxxxx"', $data);

        // hide cookies
        $cookies = ['PwdHash', 'PHPSESSID', 'phpbb3_h543j_sid', 'BrowserKey', 'XSRF\-TOKEN', 'APv2\-\d+', 'CC']; //:WARNING: [refs #15657] cookie CC will be remove
        $cookiesGroup = implode('|', $cookies);
        $data = preg_replace('/('. $cookiesGroup .')=[^;\n]+?([^;\n]{4})(;|$|\n)/ims', '$1[-4:]=$2$3', $data);

        return $data;
    }

    private function formatTrace($trace, $traceTitle = null, $message = null, $class = null, $file = null, $line = null) {
    	$out = array();

        if (isset($traceTitle)) {
            $out[] = "<b>{$traceTitle}:</b>";
        }

        if (isset($file) && isset($line)) {
            $out[] = "<span style=\"color: #747474;\">({$file}:{$line})</span>";
        }

        if (isset($message)) {
            $out[] = "<b>Message:</b> {$message}";
        }

        if (isset($class)) {
            $out[] = "<b>Class:</b> {$class}";
        }

    	foreach (TraceProcessor::filterBackTrace($trace) as $num => $item) {
    		$str = "<b>";
    		$str .= (isset($item['class']) && $item['class'] != '') ? $item['class'].(isset($item['type']) ? $item['type'] : "::") : "";
    		$str .= (isset($item['function']) ? $item['function'] : '') . "</b>";

    		$argsDelimeter = ', ';
    		$argsBoundary = '';

            foreach ($item['args'] as $arg) {
                if ((\is_array($arg) && \count($arg) >= 5) || \is_object($arg)) {
                    $argsDelimeter = ', <br /><br />';
                    $argsBoundary = '<br />';

                    break;
                }
            }

            $str .= "({$argsBoundary}".implode($argsDelimeter, array_map(function($arg){ return htmlspecialchars(json_encode($arg, JSON_UNESCAPED_UNICODE)); }, $item['args']))."{$argsBoundary})";
	   		if (isset($item['file']) && $item['file'] != '' && isset($item['line']) && $item['line'] != '')
    			$str .= "<br /><span style=\"color: #747474;\">({$item['file']}:{$item['line']})</span>";
    		$out[] = $str;
    	}

    	return "
            <div style=\"background-color: #FFDDD7; padding: 3px; margin: 2px;\">
                ".implode("<br />", $out).
            "</div>";
    }

    private function addExtraInformation(array $record) : string
    {
        $output = '';

        if(isset($record['context']['exception']) && $record['context']['exception'] instanceof \Exception){
            /** @var \Exception $exception */
            $exception = $record['context']['exception'];
            while ($exception !== null) {
                $output .= $this->formatTrace(
                    $exception->getTrace(),
                    TraceProcessor::filterMessage($exception),
                    null,
                    null,
                    $exception->getFile(),
                    $exception->getLine()
                );
                $exception = $exception->getPrevious();
            }
            unset($record['context']['exception']);
        }

        if (isset($record['context']['traces'])) {
            foreach ($record['context']['traces'] as $i => $exception) {
                $output .= $this->formatTrace($exception['trace'], "Exception trace #{$i}", $exception['message'], $exception['class']);
            }
            unset($record['context']['traces']);
        }

        if(isset($record['extra']['file']))
            $output .= "<div style=\"background-color: #FFDDD7; padding: 3px; margin: 2px;\">{$record['extra']['file']}:{$record['extra']['line']}<br/>
            {$record['extra']['class']}::{$record['extra']['function']}()
            </div>";

		if(isset($record['extra']['trace'])){
			$output .= $this->formatTrace($record['extra']['trace']);

		} elseif (isset($record['context']['trace'])) {
            $output .= $this->formatTrace($record['context']['trace']);
            unset($record['context']['trace']);
        }

		return $output;
    }

}
