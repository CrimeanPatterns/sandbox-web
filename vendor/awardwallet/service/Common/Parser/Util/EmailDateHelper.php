<?php

namespace AwardWallet\Common\Parser\Util;


class EmailDateHelper {

	const MAX_GAP = 5;
	const MAX_FROM_START = 20;
	const MIN_CONSECUTIVE = 3;
	const YEAR_LIMIT = 3;

	const FORMAT_DOT_YEAR_DATE = '%Y%.%D%';
	const FORMAT_DOT_DATE_YEAR = '%D%.%Y%';
	const FORMAT_SLASH_YEAR_DATE = '%Y%/%D%';
	const FORMAT_SLASH_DATE_YEAR = '%D%/%Y%';
	const FORMAT_SPACE_YEAR_DATE = '%Y% %D%';
	const FORMAT_SPACE_DATE_YEAR = '%D% %Y%';
	const FORMAT_DASH_YEAR_DATE = '%Y%-%D%';
	const FORMAT_DASH_DATE_YEAR = '%D%-%Y%';

	protected static $forwardedHeaders = [
		'from', 'to', 'date', 'sent', 'subject'
	];

	protected static $forwardedDateHeaders = [
		'date', 'sent'
	];

	/**
	 * @param \TAccountChecker $checker - email parser to check detectEmailFromProvider with
	 * @param \PlancakeEmailParser $parser
	 * @param bool $html - true for html, false for plain text
	 * @deprecated
	 * @return int|null
	 */
	public static function calculateOriginalDate(\TAccountChecker $checker, \PlancakeEmailParser $parser, $html = true) {
		$date = self::fromHeaders($checker, $parser);
		if ($date !== false)
			return $date;
		if ($html)
			$lines = self::getHtmlLines($parser->getHTMLBody());
		else
			$lines = self::getPlainLines($checker->http->Response['body']);
		return self::fromForwardedHeader($lines);
	}

    /**
     * @param string $date string with date without year
     * @param \TAccountChecker $checker - email parser to check detectEmailFromProvider with
     * @param \PlancakeEmailParser $parser
     * @param mixed $format
     * @return int|null
     */
	public static function calculateDateRelative($date, \TAccountChecker $checker, \PlancakeEmailParser $parser, $format = null) {
        if (!(($relDate = $parser->getDate()) && ($relDate = strtotime($relDate))))
            $relDate = time();
		return self::parseDateRelative($date, $relDate, null, $format, 9);
	}

	/**
	 * @param string $date string with date without year
	 * @param int $relative unixtime stamp of date to use as relative date
	 * @param bool $after if true - will look for date after relative; if false - before; if null - both directions
	 * @param string $format - string that contains symbols '%Y%' and '%D%'. tells how to
	 * 					merge date and year, e.g. '%D% %Y%', '%Y%/%D%', '12 december %Y% 12:34', etc.
     * @param int $afterMonths -  if $after is null, resulting date will fall into this amount of months after $relative date
     *                  otherwise the date before $relative will be returned
	 * @return int|null
	 */
	public static function parseDateRelative($date, $relative, $after = true, $format = null, $afterMonths = 9)
    {
		if (!$relative || !is_string($date) || empty($date))
			return null;
		$year = date('Y', $relative);
		if (self::isFeb29($date))
		    $year = self::getClosestLeapYear($year);
		if (null === $format) {
			$result = strtotime($date . ' ' . $year);
			if ($result === false)
				$result = strtotime($year . ' ' . $date);
		}
		else {
			$result = strtotime(str_replace(['%Y%', '%D%'], [$year, $date], $format));
		}
		if (!$result)
			return null;
		if (!self::isFeb29($date)) {
		    $more = strtotime('+1 year', $result);
		    $less = strtotime('-1 year', $result);
		    if (is_null($after)) {
		        $down = strtotime(sprintf('-%d months', 12 - $afterMonths), $relative);
                $up = strtotime(sprintf('+%d months', $afterMonths), $relative);
		        foreach([$less, $result, $more] as $date)
		            if ($date > $down && $date < $up)
		                return $date;

            } elseif ($after && $result < $relative) {
                return $more;
            } elseif (!$after && $result > $relative) {
                return $less;
            }
        }
		return $result;
	}

    /**
     * @param string $date - date string
     * @param int $dayNumber - week day number (1(mon) - 7(sun))
     * @param int $yearLimit - max number of years to search each way
     * @return int|null
     */
    public static function parseDateUsingWeekDay($date, $dayNumber, $yearLimit = self::YEAR_LIMIT) {
        $y = $m = $d = $step = null;
        if (is_string($date)) {
            if (self::isFeb29($date)) {
                $y = self::getClosestLeapYear(date('Y', strtotime($date)));
                $m = 2;
                $d = 29;
                $step = 4;
                $yearLimit = max($yearLimit, 4);
            }
            $date = strtotime($date);
        }
        if ($date === false || $date < strtotime('01/01/2005'))
            return null;
        if (!isset($y)) {
            $y = date('Y', $date);
            $m = date('m', $date);
            $d = date('d', $date);
            $step = 1;
        }
        $H = date('H', $date);
        $i = date('i', $date);
        $s = date('s', $date);
        for($j=0;$j<=$yearLimit;$j+=$step) {
            $try = mktime($H, $i, $s, $m, $d, $y + $j);
            if ((int)date('N', $try) === $dayNumber)
                return $try;
            $try = mktime($H, $i, $s, $m, $d, $y - $j);
            if ((int)date('N', $try) === $dayNumber)
                return $try;
        }
        return null;
    }

	private static function isFeb29($date)
    {
        $has29 = preg_match('/\b29\b/', $date) > 0;
        $date = strtotime($date);
        return $has29 && in_array(date('md', $date), ['0229', '0301']);

    }

    private static function getClosestLeapYear($start)
    {
        for($i=0; $i < 10; $i++) {
            if (self::isYearLeap($y = $start + $i))
                return $y;
            if (self::isYearLeap($y = $start - $i))
                return $y;
        }
        return null;
    }

    private static function isYearLeap($year)
    {
        return ((($year % 4) == 0) && ((($year % 100) != 0) || (($year % 400) == 0)));
    }

	protected static function fromHeaders(\TAccountChecker $checker, \PlancakeEmailParser $parser) {
		if (($from = $parser->getCleanFrom()) && $checker->detectEmailFromProvider($from)) {
			if (($date = $parser->getDate()) && ($date = strtotime($date)) && ($date > strtotime('1/1/2000')))
				return $date;
			else
				return null;
		}
		else
			return false;
	}

	protected static function fromForwardedHeader($lines) {
		$lastDate = null;
		$gap = $consecutive = 0;
		$isGap = $header = false;
		foreach($lines as $line) {
			if (preg_match('/^([a-z ]+):(.+)$/i', $line, $m) > 0 && in_array(strtolower($m[1]), self::$forwardedHeaders)) {
				if ($isGap)
					$consecutive = 0;
				$header = true;
				$isGap = false;
				$gap = 0;
				$consecutive++;
				if (in_array(strtolower($m[1]), self::$forwardedDateHeaders))
					$lastDate = trim($m[2]);
			}
			else {
				$isGap = true;
				$gap++;
			}
			if ($header && $gap >= self::MAX_GAP || !$header && $gap >= self::MAX_FROM_START)
				break;
		}
		if (isset($lastDate) && $consecutive >= self::MIN_CONSECUTIVE) {
			$lastDate = str_replace(' at ', ' ', $lastDate);
			if (($date = strtotime($lastDate)) && $date > strtotime('01/01/2010'))
				return $date;
		}
		return null;
	}

	protected static function getHtmlLines($html) {
		$html = strip_tags(str_replace(['<br>', '<br '], ["\n", "\n<br "], $html));
		return self::getPlainLines($html);
	}

	protected static function getPlainLines($text) {
		return array_filter(array_map('trim', explode("\n", $text)));
	}


}