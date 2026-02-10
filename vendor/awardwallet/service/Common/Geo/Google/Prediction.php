<?php


namespace AwardWallet\Common\Geo\Google;

use JMS\Serializer\Annotation as JMS;

class Prediction
{
    /**
     * contains the human-readable name for the returned result.
     * For establishment results, this is usually the business name.
     *
     * @JMS\Type("string")
     *
     * @var string
     */
    private $description;

    /**
     * is a textual identifier that uniquely identifies a place.
     * To retrieve information about the place, pass this identifier in the placeId field of a Google Places API Web Service request.
     * For more information about place IDs, see the place ID overview @link https://developers.google.com/places/web-service/place-id
     *
     * @JMS\Type("string")
     * @JMS\SerializedName("place_id")
     *
     * @var string
     */
    private $placeId;

    /**
     * Contains an array of terms identifying each section of the returned description (a section of the description is generally terminated with a comma).
     * Each entry in the array has a value field, containing the text of the term,
     * and an offset field, defining the start position of this term in the description, measured in Unicode characters.
     *
     * @JMS\Type("array")
     *
     * @var array
     */
    private $terms;

    /**
     * Contains an array of types that apply to this place. For example: [ "political", "locality" ] or [ "establishment", "geocode" ].
     *
     * @JMS\Type("array<string>")
     *
     * @var string[]
     */
    private $types;

    /**
     * matched_substrings contains an array with offset value and length.
     * These describe the location of the entered term in the prediction result text, so that the term can be highlighted if desired.
     *
     * @JMS\Type("array")
     * @JMS\SerializedName("matched_substrings")
     *
     * @var array
     */
    private $matchedSubstrings;

    /**
     * @JMS\Type("AwardWallet\Common\Geo\Google\StructuredFormatting")
     * @JMS\SerializedName("structured_formatting")
     *
     * @var StructuredFormatting[]
     */
    private $structuredFormatting;

    /**
     * contains the human-readable name for the returned result.
     * For establishment results, this is usually the business name.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * is a textual identifier that uniquely identifies a place.
     * To retrieve information about the place, pass this identifier in the placeId field of a Google Places API Web Service request.
     * For more information about place IDs, see the place ID overview @link https://developers.google.com/places/web-service/place-id
     *
     * @return string
     */
    public function getPlaceId(): string
    {
        return $this->placeId;
    }

    /**
     * Contains an array of terms identifying each section of the returned description (a section of the description is generally terminated with a comma).
     * Each entry in the array has a value field, containing the text of the term,
     * and an offset field, defining the start position of this term in the description, measured in Unicode characters.
     *
     * @return array
     */
    public function getTerms(): array
    {
        return $this->terms;
    }

    /**
     * Contains an array of types that apply to this place. For example: [ "political", "locality" ] or [ "establishment", "geocode" ].
     *
     * @return string[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * matched_substrings contains an array with offset value and length.
     * These describe the location of the entered term in the prediction result text, so that the term can be highlighted if desired.
     *
     * @return array
     */
    public function getMatchedSubstrings(): array
    {
        return $this->matchedSubstrings;
    }

    /**
     * @return StructuredFormatting[]
     */
    public function getStructuredFormatting(): array
    {
        return $this->structuredFormatting;
    }


}