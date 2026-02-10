<?php

trait StringTools {

	function glue($str, $with = ", ") {
		$source = is_array($str) ? $str : explode("\n", $str);
		return implode($with, $source);
	}

	function nicify($text, $glue = false) {
		if (is_array($text)) {
			$result = [];
			foreach ($text as $key => $value)
				$result[$key] = $this->nicify($value, $glue);
			return $result;
		}

		if (!is_string($text))
			return $text;

		$text = $glue ? $this->glue($text, $glue):$text;

		$text = text($text);
		$text = preg_replace("#\s+#ums", ' ', $text);
		$text = preg_replace("#,+#ums", ',', $text);
		$text = preg_replace("#(?:\s*,\s*)+#ums", ',', $text);
		$text = preg_replace("#\s+,\s+#ums", ', ', $text);
		$text = preg_replace("#(\S),(\S)#ums", '\1, \2', $text);
		$text = preg_replace_callback("#([\w\d]),([\w\d])#ums", function($m){return $m[1].', '.$m[2];}, $text);
		$text = preg_replace("#[,\s]+$#ums", '', $text);
		$text = preg_replace("#^[,.\s]+#ums", '', $text);
		return $text;
	}

	function htmlToPlainText($html) {
		$result = $html;
		$result = preg_replace("#<head[^>]*?>.*?</head[^>]*?>#ims", '', $result);
		$result = preg_replace("#<style[^>]*?>.*?</style[^>]*?>#ims", '', $result);
		#$html = preg_replace("#<script.+?/>.*?</script>#ims", '', $html);

		$result = str_ireplace('<o:p></o:p>', '', $result);
		$result = preg_replace("#<o:p>(.*?)</o:p>#ims", "\\1<br>", $result);
		$result = str_ireplace(['<pre>','</pre>'], ["","<BR>"], $result);

		$u = ($result!=null && preg_replace("#&mdash;#uims", "—", $result) != null)?'u':''; // sometimes "u" usage returns null

		$result = html_entity_decode($result);

		// filter symbols
		$result = preg_replace("#[^\w\d,. \t\r\n_/+:;\"'<>?~`!@\#$%^&*\[\]=\(\)\-{}£¥₣₤₧€\$\|]#ims$u", ' ', $result);
		$result = str_ireplace(['&#160;','&#43;','&#58;','&#39;'], [' ','+',':','\''], $result);

		// simplify carets
		$result = str_ireplace("\r\n", "\n", $result);

		// process cells & paragraphs
		$result = preg_replace("#<(?:td|th)(?:\s+|\s+[^>]+|)>#ims$u", "\t", $result);
		$result = preg_replace("#<(?:p|tr)(?:\s+|\s+[^>]+|)>#ims$u", "\n", $result);
		$result = preg_replace("#</(?:p|tr|pre)>#ims$u", "\n", $result);

		// process newline
		$result = preg_replace("#[ \t]{20,}#ims$u", " ", $result, -1, $count); // replace very long spaces to work next regexp faster
		$count = 0;
		do {
			$result = preg_replace("#[ \t]*<br\s*/*>[ \t]*\n#ims$u", "\n", $result, -1, $count);
		} while ($result && $count > 0);

		$result = preg_replace("#<br\s*/*>#ims$u", "\n", $result, -1, $count);

		// remove tags
		$result = preg_replace("#<[^>]+>#ims$u", ' ', $result);

		// trim lines
		$result = preg_replace("#[ \t]+\n#ims$u", "\n", $result);

		// replace too long newlines
		$result = preg_replace("#\n{4,}#ims$u", "\n\n\n", $result);

		return trim($result);
	}

    function htmlToTextLines($html, $arCleanerOther = array()) {
        $replaced = 1; 
        $arCleaner = array(
            array("#<html\b.+?<\/head>#si", ""),
            array("#<style(?>[^>]*)>.*?</style\s*>#si", ""),
            array("#(?>&nbsp;|&\#160;|\s\s)+#u", " "),
            array("#<\w+(?>[^>]+)?\/>#", "\n"),
            array("#<(\w+)(?>[^>]+)?>([^<]*)<\/\g{1}>#", "\n\\2"),
            array("#\n\s*l\s*?\n#", "\n")
        );
        $arCleaner = array_merge($arCleaner, $arCleanerOther);
        
        foreach($arCleaner as $cleanStaff) {
            $replaced = 1;
            while($replaced) $html = preg_replace($cleanStaff[0], $cleanStaff[1], $html, -1, $replaced);
        }
        return $html;
    }
}