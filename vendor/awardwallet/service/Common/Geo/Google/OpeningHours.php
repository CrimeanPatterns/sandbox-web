<?php


namespace AwardWallet\Common\Geo\Google;

use JMS\Serializer\Annotation as JMS;

class OpeningHours
{
    /**
     * Boolean value indicating if the place is open at the current time.
     *
     * @JMS\SerializedName("open_now")
     * @JMS\Type("boolean")
     *
     * @var boolean|null
     */
    private $openNow;

    /**
     * is an array of opening periods covering seven days, starting from Sunday, in chronological order.
     * Each period contains:
     *      open: contains a pair of day and time objects describing when the place opens:
     *          day: a number from 0–6, corresponding to the days of the week, starting on Sunday. For example, 2 means Tuesday.
     *          time: may contain a time of day in 24-hour hhmm format. Values are in the range 0000–2359. The time will be reported in the place’s time zone.
     *      close: may contain a pair of day and time objects describing when the place closes.
     *      Note: If a place is always open, the close section will be missing from the response.
     *      Clients can rely on always-open being represented as an open period containing day with value 0 and time with value 0000, and no close.
     *
     * @JMS\Type("array")
     *
     * @var array|null
     */
    private $periods;

    /**
     * is an array of seven strings representing the formatted opening hours for each day of the week.
     * If a language parameter was specified in the Place Details request, the Places Service will format and localize the opening hours appropriately for that language.
     * The ordering of the elements in this array depends on the language parameter. Some languages start the week on Monday while others start on Sunday.
     *
     * @JMS\SerializedName("weekday_text")
     * @JMS\Type("array<string>")
     *
     * @var string[]|null
     */
    private $weekdayText;

    /**
     * @return bool|null
     */
    public function getOpenNow()
    {
        return $this->openNow;
    }

    /**
     * @return array|null
     */
    public function getPeriods()
    {
        return $this->periods;
    }

    /**
     * @return null|\string[]
     */
    public function getWeekdayText()
    {
        return $this->weekdayText;
    }
}