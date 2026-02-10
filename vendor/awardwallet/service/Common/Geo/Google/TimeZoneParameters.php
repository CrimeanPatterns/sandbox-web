<?php


namespace AwardWallet\Common\Geo\Google;


class TimeZoneParameters extends Parameters
{
    /**
     * @var LatLng
     */
    private $latLng;

    /**
     * The Google Maps Time Zone API uses the date to determine whether or not Daylight Savings should be applied, based on the time zone of the location.
     * Note that the API does not take historical time zones into account.
     * That is, if you specify a past date, the API does not take into account the possibility that the location was previously in a different time zone.
     *
     * @var \DateTime
     */
    private $dateTime;

    /**
     * The language in which to return results.
     * See the list of supported domain languages @link https://developers.google.com/maps/faq#languagesupport
     * Note that google often update supported languages so this list may not be exhaustive. Defaults to en.
     *
     * @var string|null
     */
    private $language;

    private function __construct(LatLng $latLng)
    {
        $this->latLng = $latLng;
        $this->dateTime = new \DateTime();
    }

    /**
     * The Google Maps Time Zone API uses the date to determine whether or not Daylight Savings should be applied, based on the time zone of the location.
     * Note that the API does not take historical time zones into account.
     * That is, if you specify a past date, the API does not take into account the possibility that the location was previously in a different time zone.
     *
     * @return \DateTime
     */
    public function getDateTime(): \DateTime
    {
        return $this->dateTime;
    }

    /**
     * The Google Maps Time Zone API uses the date to determine whether or not Daylight Savings should be applied, based on the time zone of the location.
     * Note that the API does not take historical time zones into account.
     * That is, if you specify a past date, the API does not take into account the possibility that the location was previously in a different time zone.
     *
     * @param \DateTime $dateTime
     */
    public function setDateTime(\DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * The language in which to return results.
     * See the list of supported domain languages @link https://developers.google.com/maps/faq#languagesupport
     * Note that google often update supported languages so this list may not be exhaustive. Defaults to en.
     *
     * @return null|string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * The language in which to return results.
     * See the list of supported domain languages @link https://developers.google.com/maps/faq#languagesupport
     * Note that google often update supported languages so this list may not be exhaustive. Defaults to en.
     *
     * @param string $language
     */
    public function setLanguage(string $language)
    {
        $this->language = $language;
    }


    /**
     * @return string[]
     */
    protected function getAllParametersAsArray(): array
    {
        return [
            'location'  => $this->latLng,
            'timestamp' => $this->dateTime->getTimestamp(),
            'language'  => $this->language
        ];
    }

    /**
     * @param LatLng $latLng
     * @return TimeZoneParameters
     */
    public static function makeFromLatLng(LatLng $latLng)
    {
        return new TimeZoneParameters($latLng);
    }
}