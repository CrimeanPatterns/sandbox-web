<?php


namespace AwardWallet\Common\Geo\Google;

use JMS\Serializer\Annotation as JMS;

class TimeZoneResponse extends GoogleResponse
{
    /**
     * The offset for daylight-savings time in seconds.
     * This will be zero if the time zone is not in Daylight Savings Time during the specified timestamp.
     *
     * @JMS\Type("integer")
     *
     * @var int
     */
    private $dstOffset;

    /**
     * The offset from UTC (in seconds) for the given location. This does not take into effect daylight savings.
     *
     * @JMS\Type("integer")
     *
     * @var int
     */
    private $rawOffset;

    /**
     * A string containing the ID of the time zone, such as "America/Los_Angeles" or "Australia/Sydney".
     * These IDs are defined by Unicode Common Locale Data Repository (CLDR) project, and currently available in file timezone.xml.
     * When a timezone has several IDs, the canonical one is returned. In timezone.xml, this is the first alias of each timezone.
     * For example, "Asia/Calcutta" is returned, not "Asia/Kolkata".
     *
     * @JMS\Type("string")
     *
     * @var string
     */
    private $timeZoneId;

    /**
     * A string containing the long form name of the time zone.
     * This field will be localized if the language parameter is set. eg. "Pacific Daylight Time" or "Australian Eastern Daylight Time"
     *
     * @JMS\Type("string")
     *
     * @var string
     */
    private $timeZoneName;

    /**
     * The offset for daylight-savings time in seconds.
     * This will be zero if the time zone is not in Daylight Savings Time during the specified timestamp.
     *
     * @return int
     */
    public function getDstOffset()
    {
        return $this->dstOffset;
    }

    /**
     * The offset from UTC (in seconds) for the given location. This does not take into effect daylight savings.
     *
     * @return int
     */
    public function getRawOffset()
    {
        return $this->rawOffset;
    }

    /**
     * A string containing the ID of the time zone, such as "America/Los_Angeles" or "Australia/Sydney".
     * These IDs are defined by Unicode Common Locale Data Repository (CLDR) project, and currently available in file timezone.xml.
     * When a timezone has several IDs, the canonical one is returned. In timezone.xml, this is the first alias of each timezone.
     * For example, "Asia/Calcutta" is returned, not "Asia/Kolkata".
     *
     * @return string
     */
    public function getTimeZoneId()
    {
        return $this->timeZoneId;
    }

    /**
     * A string containing the long form name of the time zone.
     * This field will be localized if the language parameter is set. eg. "Pacific Daylight Time" or "Australian Eastern Daylight Time"
     *
     * @return string
     */
    public function getTimeZoneName()
    {
        return $this->timeZoneName;
    }


}