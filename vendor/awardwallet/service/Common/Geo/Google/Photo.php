<?php


namespace AwardWallet\Common\Geo\Google;

use JMS\Serializer\Annotation as JMS;

class Photo
{
    /**
     * A string used to identify the photo when you perform a Photo request.
     *
     * @JMS\SerializedName("photo_reference")
     * @JMS\Type("string")
     *
     * @var string|null
     */
    private $photoReference;

    /**
     * The maximum height of the image.
     *
     * @JMS\Type("integer")
     *
     * @var int|null
     */
    private $height;

    /**
     * The maximum width of the image.
     *
     * @JMS\Type("integer")
     *
     * @var int|null
     */
    private $width;

    /**
     * Contains any required attributions.
     *
     * @JMS\SerializedName("html_attributions")
     * @JMS\Type("array<string>")
     *
     * @var string[]
     */
    private $htmlAttributions;

    /**
     * @return null|string
     */
    public function getPhotoReference()
    {
        return $this->photoReference;
    }

    /**
     * @return int|null
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return int|null
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return \string[]
     */
    public function getHtmlAttributions()
    {
        return $this->htmlAttributions;
    }
}