<?php


namespace AwardWallet\Common\Geo\Google;

use AwardWallet\Common\Geo\Google\Traits\HasAddressComponents;
use JMS\Serializer\Annotation as JMS;

class PlaceDetails extends Place
{
    use HasAddressComponents;

    /**
     * Contains the place's phone number in its local format @link https://en.wikipedia.org/wiki/National_conventions_for_writing_telephone_numbers.
     * For example, the formatted_phone_number for Google's Sydney, Australia office is (02) 9374 4000.
     *
     * @JMS\SerializedName("formatted_phone_number")
     * @JMS\Type("string")
     *
     * @var string|null
     */
    private $formattedPhoneNumber;

    /**
     * Representation of the place's address in the adr microformat. @link http://microformats.org/wiki/adr
     *
     * @JMS\SerializedName("adr_address")
     * @JMS\Type("string")
     *
     * @var string|null
     */
    private $adrAddress;

    /**
     * contains the place's phone number in international format.
     * International format includes the country code, and is prefixed with the plus (+) sign.
     * For example, the international_phone_number for Google's Sydney, Australia office is +61 2 9374 4000.
     *
     * @JMS\SerializedName("international_phone_number")
     * @JMS\Type("string")
     *
     * @var string|null
     */
    private $internationalPhoneNumber;

    /**
     * An array of up to five reviews.
     * If a language parameter was specified in the Place Details request, the Places Service will bias the results to prefer reviews written in that language.
     *
     * @JMS\Type("array<AwardWallet\Common\Geo\Google\Review>")
     *
     * @var Review[]|null
     */
    private $reviews;

    /**
     * Contains the URL of the official Google page for this place.
     * This will be the Google-owned page that contains the best available information about the place.
     * Applications must link to or embed this page on any screen that shows detailed results about the place to the user.
     *
     * @JMS\Type("string")
     *
     * @var string|null
     */
    private $url;

    /**
     * Contains the number of minutes this place’s current timezone is offset from UTC.
     * For example, for places in Sydney, Australia during daylight saving time this would be 660 (+11 hours from UTC),
     * and for places in California outside of daylight saving time this would be -480 (-8 hours from UTC).
     *
     * @JMS\SerializedName("utc_offset")
     * @JMS\Type("integer")
     *
     * @var int|null
     */
    private $utcOffset;

    /**
     * lists the authoritative website for this place, such as a business' homepage.
     *
     * @JMS\Type("string")
     *
     * @var string|null
     */
    private $website;

    /**
     * @return null|string
     */
    public function getFormattedPhoneNumber()
    {
        return $this->formattedPhoneNumber;
    }

    /**
     * @return null|string
     */
    public function getAdrAddress()
    {
        return $this->adrAddress;
    }

    /**
     * @return null|string
     */
    public function getInternationalPhoneNumber()
    {
        return $this->internationalPhoneNumber;
    }

    /**
     * @return Review[]|null
     */
    public function getReviews()
    {
        return $this->reviews;
    }

    /**
     * @return null|string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return int|null
     */
    public function getUtcOffset()
    {
        return $this->utcOffset;
    }

    /**
     * @return null|string
     */
    public function getWebsite()
    {
        return $this->website;
    }
}