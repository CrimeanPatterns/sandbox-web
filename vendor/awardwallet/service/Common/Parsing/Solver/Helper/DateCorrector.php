<?php


namespace AwardWallet\Common\Parsing\Solver\Helper;


use AwardWallet\Common\DateTimeUtils;

class DateCorrector
{

    private $changedYear = false;

    public function fixDateNextSegment($prevDate, $nextDate)
    {
        if (!$prevDate || !$nextDate)
            return $nextDate;
        if (($diff = abs($prevDate - $nextDate)) && $prevDate > $nextDate && $diff > DateTimeUtils::SECONDS_PER_DAY * 250 && $diff < DateTimeUtils::SECONDS_PER_DAY * ($this->changedYear ? 366 : 365)) {
            $this->changedYear = true;
            $nextDate = strtotime('+1 year', $nextDate);
        }
        if (!$prevDate || !$nextDate || abs($prevDate - $nextDate) > 5 * DateTimeUtils::SECONDS_PER_DAY)
            return $nextDate;
        while($nextDate < $prevDate)
            $nextDate += DateTimeUtils::SECONDS_PER_DAY;
        return $nextDate;
    }

    public function fixDateOvernightSegment($depDate, $depOffset, $arrDate, $arrOffset)
    {
        if (!$depDate || !$arrDate)
            return $arrDate;
        if (!isset($depOffset) || !isset($arrOffset)) {
            $depOffset = DateTimeUtils::SECONDS_PER_DAY / 2;
            $arrOffset = 0;
        }
        $d = $depDate - $depOffset;
        $a = $arrDate - $arrOffset;
        if ($d > $a && ($diff = $d - $a) && $diff > DateTimeUtils::SECONDS_PER_DAY * 360 && $diff < DateTimeUtils::SECONDS_PER_DAY * ($this->changedYear ? 366 : 365)
            && ($this->changedYear || date('m', $depDate) === '12' && date('m', $arrDate) === '01')) {
            $arrDate = strtotime('+1 year', $arrDate);
            $this->changedYear = true;
        }
        if (abs($depDate - $arrDate) > 5 * DateTimeUtils::SECONDS_PER_DAY)
            return $arrDate;
        while($depDate - $depOffset > $arrDate - $arrOffset)
            $arrDate += DateTimeUtils::SECONDS_PER_DAY;
        return $arrDate;
    }

}