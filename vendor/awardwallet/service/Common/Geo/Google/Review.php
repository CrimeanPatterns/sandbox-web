<?php


namespace AwardWallet\Common\Geo\Google;

use JMS\Serializer\Annotation as JMS;

class Review
{
    /**
     * @JMS\Type("array<AwardWallet\Common\Geo\Google\AspectRating>")
     *
     * @var AspectRating[]|null
     */
    private $aspects;

    /**
     * Name of the user who submitted the review. Anonymous reviews are attributed to "A Google user".
     *
     * @JMS\SerializedName("author_name")
     * @JMS\Type("string")
     *
     * @var string|null
     */
    private $authorName;

    /**
     * URL to the users Google+ profile, if available.
     *
     * @JMS\SerializedName("author_url")
     * @JMS\Type("string")
     *
     * @var string|null
     */
    private $authorUrl;

    /**
     * IETF language code indicating the language used in the user's review.
     * This field contains the main language tag only, and not the secondary tag indicating country or region.
     * For example, all the English reviews are tagged as 'en', and not 'en-AU' or 'en-UK' and so on.
     *
     * @JMS\Type("string")
     *
     * @var string|null
     */
    private $language;

    /**
     * The user's overall rating for this place. This is a whole number, ranging from 1 to 5.
     *
     * @JMS\Type("integer")
     *
     * @var int|null
     */
    private $rating;

    /**
     * the user's review. When reviewing a location with Google Places, text reviews are considered optional.
     * Therefore, this field may by empty. Note that this field may include simple HTML markup. For example, the entity reference &amp; may represent an ampersand character.
     *
     * @JMS\Type("string")
     *
     * @var string|null
     */
    private $text;

    /**
     * The time that the review was submitted, measured in the number of seconds since since midnight, January 1, 1970 UTC.
     *
     * @JMS\Type("integer")
     *
     * @var int|null
     */
    private $time;

    /**
     * @return AspectRating[]|null
     */
    public function getAspects()
    {
        return $this->aspects;
    }

    /**
     * @return null|string
     */
    public function getAuthorName()
    {
        return $this->authorName;
    }

    /**
     * @return null|string
     */
    public function getAuthorUrl()
    {
        return $this->authorUrl;
    }

    /**
     * @return null|string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return int|null
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @return null|string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return int|null
     */
    public function getTime()
    {
        return $this->time;
    }
}