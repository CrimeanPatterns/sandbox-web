<?php

namespace AwardWallet\Common\Parsing;

class Html
{

    public static function convertHtmlToUtf($html, $headers = null)
    {
        if (isset($headers)) {
            if (is_array($headers)) {
                $headers = implode("\n", array_map(function ($header, $value) {
                    if (is_array($value)) {
                        return implode("\n", array_map(function ($aValue) use ($header) {
                            return $header . ": " . $aValue;
                        }, $value));
                    }
                    return $header . ": " . $value;
                }, array_keys($headers), $headers));
            }
            if (preg_match("/Content\-Type\s*:\s*text\/html;\s*charset=([^\n\;]+)/ims", $headers, $matches)) {
                $encoding = strtolower($matches[1]);
            }
        }
        if (preg_match("/<meta[^>]*http\-equiv=\"?Content\-Type\"?[^>]*>/ims", $html, $matches)) {
            if (preg_match("/<meta[^>]*content=\"?text\/html;\s*charset=([^\"\;]+)\"?[^>]*>/ims", $matches[0],
                $matches)) {
                $encoding = strtolower($matches[1]);
            }
        }
        if (isset($encoding) && trim($encoding) != "" && strtolower($encoding) !== 'utf-8') {
            $encoding = self::checkEncoding($encoding);
            $_html = @iconv($encoding, 'UTF-8', $html);
            if ($_html === false) {
                $_html = @mb_convert_encoding($html, "UTF-8", $encoding);
            }
            if ($_html !== false) {
                $html = $_html;
            }
        }

        # is utf8?
        $isUtf8 = mb_check_encoding($html, 'UTF-8');
        if (!$isUtf8) {
            $currEncoding = mb_detect_encoding($html);
            if ($currEncoding !== false) {
                if (($_html = @mb_convert_encoding($html, "UTF-8", $currEncoding)) != '') {
                    $html = $_html;
                }
            }
        }

        return $html;
    }

    public static function checkEncoding($encoding)
    {
        $encoding = strtolower($encoding);
        $wrongCharsetTable = array(
            'iso8859_1' => 'iso8859-1',
            'unicode' => 'UTF-8',
        );
        $encoding = str_replace(array_keys($wrongCharsetTable), $wrongCharsetTable, $encoding);

        return $encoding;
    }

    public static function tidyDoc($html, $filter = true, $convertToUtf = true)
    {
        if ($convertToUtf)
            $html = self::convertHtmlToUtf($html);
        if ($filter) {
            $config = [
                'indent' => true,
                'output-xhtml' => true,
                'wrap' => 200,
                'doctype' => 'omit',
                'drop-empty-elements' => false,
                'tidy-mark' => false,
                'new-blocklevel-tags' => 'article aside audio bdi canvas details dialog figcaption figure footer header hgroup main menu menuitem nav section source summary template track video',
                'new-empty-tags' => 'command embed keygen source track wbr',
                'new-inline-tags' => 'audio command datalist embed keygen mark menuitem meter output progress source time video wbr',
            ];
            $tidy = new \tidy();
            $tidy->parseString($html, $config, 'utf8');
            $tidy->cleanRepair();
        }
        $doc = new \DOMDocument('1.0', 'utf-8');
        $flags = LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NOCDATA | LIBXML_HTML_NODEFDTD;
        $oldEntityLoader = libxml_disable_entity_loader();

        if (!$filter || !$doc->loadHTML('<?xml encoding="UTF-8">' . $tidy, $flags))
            $doc->loadHTML('<?xml encoding="UTF-8">' . $html, $flags);

        libxml_disable_entity_loader($oldEntityLoader);
        return $doc;
    }

    public static function cleanXMLValue($s)
    {
        if ($s instanceof \DOMNode) {
            $s = $s->nodeValue;
        }
        $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8'); // remove bugged symbols
        $s = preg_replace('/[\x{0000}-\x{0019}]+/ums', ' ', $s); // remove unicode special chars, like \u0007
        $s = preg_replace("/\p{Mc}/u", ' ', $s); // normalize spaces
        $s = trim(preg_replace("/\s+/u", " ", preg_replace("/\r|\n|\t/u", ' ', $s)));
        $s = html_entity_decode($s, ENT_COMPAT, 'UTF-8');
        return $s;
    }


}