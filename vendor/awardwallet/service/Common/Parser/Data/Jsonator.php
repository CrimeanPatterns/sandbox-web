<?php


namespace AwardWallet\Common\Parser\Data;


class Jsonator
{

    private static function buildRows($data, $schema, $level, $prevKey, $prevOdd)
    {
        if ($level > 10)
            return [$level, 'too deep', 'err'];
        $result = [];
        if (is_array($data)) {
            if(count($data) > 0) {
                $odd = true;
                if (array_key_exists(0, $data)) {
                    $result[] = [$level, '[', null];
                    $cnt = count($data);
                    foreach ($data as $key => $val) {
                        $cnt--;
                        $arr = self::buildRows($val, $schema[$key] ?? null, $level + 1, $prevKey, $odd);
                        if ($cnt > 0) {
                            $last = array_pop($arr);
                            $last[1] .= ',';
                            $arr[] = $last;
                        }
                        $result = array_merge($result, $arr);
                        $odd = !$odd;
                    }
                    $result[] = [$level, ']', null];
                } else {
                    $result[] = [$level, '{', $prevKey ? sprintf('bracket bracket_%s bracket_%s', $prevKey, $prevOdd ? 'odd' : 'even') : null];
                    $cnt = count($data);
                    foreach ($data as $k => $v) {
                        $cnt--;
                        $arr = self::buildRows($v, $schema[$k] ?? null, $level + 1, $k, $odd);
                        if ($cnt > 0) {
                            $last = array_pop($arr);
                            $last[1] .= ',';
                            $arr[] = $last;
                        }
                        $first = array_shift($arr);
                        $result[] = [
                            $level + 1,
                            sprintf('"%s": %s', $k, $first[1]),
                            $first[2],
                        ];
                        $result = array_merge($result, $arr);
                        $odd = !$odd;
                    }
                    $result[] = [$level, '}', $prevKey ? sprintf('bracket bracket_%s bracket_%s', $prevKey, $prevOdd ? 'odd' : 'even') : null];
                }
            }
            else {
                $result[] = [$level, '[ ]', isset($schema) && is_string($schema) ? $schema : null];
            }
        }
        elseif (is_numeric($data) || is_bool($data) || is_null($data) || is_string($data)) {
            $result[] = [$level, json_encode($data), isset($schema) && is_string($schema) ? $schema : null];
        }
        else {
            $result[] = [$level, 'unknown', null];
        }
        return $result;
    }

    public static function html($data, $schema)
    {
        $rows = self::buildRows($data, $schema, 0, null, null);
        $html = '';
        foreach($rows as $row) {
            $html .= str_repeat('&nbsp;', $row[0] * 2) . '<span';
            if ($row[2])
                $html .= ' class="' . $row[2] . '"';
            $html .= '>' . htmlspecialchars($row[1]) . '</span><br>';
        }
        return $html;
    }
    
}