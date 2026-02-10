<?php


namespace AwardWallet\Common\Geo\Google;

use JMS\Serializer\Annotation as JMS;

class StructuredFormatting
{
    /**
     * Contains the main text of a prediction, usually the name of the place.
     *
     *
     * @JMS\Type("string")
     * @JMS\SerializedName("main_text")
     *
     * @var string
     */
    private $mainText;

    /**
     * Contains an array with offset value and length.
     * These describe the location of the entered term in the prediction result text, so that the term can be highlighted if desired.
     *
     * @JMS\Type("array")
     * @JMS\SerializedName("main_text_matched_substrings")
     *
     * @var array
     */
    private $mainTextMatchedSubstrings;

    /**
     * Contains the secondary text of a prediction, usually the location of the place.
     *
     * @JMS\Type("string")
     * @JMS\SerializedName("secondary_text")
     *
     * @var string
     */
    private $secondaryText;
}