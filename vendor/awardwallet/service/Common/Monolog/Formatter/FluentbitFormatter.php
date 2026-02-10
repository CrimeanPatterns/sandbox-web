<?php

namespace AwardWallet\Common\Monolog\Formatter;

use AwardWallet\Common\Strings;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Logger;

class FluentbitFormatter extends NormalizerFormatter
{

    /**
     * @var string 
     */
    private $facility;
    
    public function __construct(string $facility)
    {
        parent::__construct();
        
        $this->facility = $facility;
    }

    public function format(array $record)
    {
        $data = array();
        $data['@timestamp'] = $record['datetime']->format("c");
        $data['level'] = Logger::getLevelName($record['level']);
        $data['level_int'] = $record['level'];
        $data['facility'] = $this->facility;
        $data['message'] = $record['message'];
        $data['channel'] = $record['channel'];
        
        unset($data['context']['scope_vars']);

        if (!empty($record['context'])) {
            $data['context'] = $record['context'];
        }

        if (!empty($record['extra'])) {
            $data['extra'] = $record['extra'];
        }

        foreach ($record['context'] as $fieldName => &$fieldValue) {
            if (
                is_numeric($fieldValue) &&
                ('userid' === strtolower(preg_replace('/[^a-z]/i', '', $fieldName)))
            ) {
                $data['UserID'] = (int)$fieldValue;

                break;
            }
        }

        $data = $this->trim($data, 0);

        return json_encode($data);
    }

    public function formatBatch(array $records)
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }

        return $message;
    }

    private function trim($data, int $level)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
            if (empty($data)) {
                return new \StdClass();
            }
        }
        return array_map(function ($value) use ($level) {
            if (is_array($value) || is_object($value)) {
                if ($level > 9) {
                    return "too deep";
                }
                return $this->trim($value, $level + 1);
            }

            if (is_string($value)) {
                return Strings::cutInMiddle($value, 1024);
            }

            return $value;
        }, $data);
    }
    
}