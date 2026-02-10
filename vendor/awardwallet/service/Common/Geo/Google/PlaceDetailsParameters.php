<?php


namespace AwardWallet\Common\Geo\Google;


class PlaceDetailsParameters extends Parameters
{
    /**
     * A textual identifier that uniquely identifies a place, returned from a GoogleApi::placeTextSearch().
     * For more information about place IDs, see @link https://developers.google.com/places/web-service/place-id
     *
     * @var string
     */
    private $placeId;

    /**
     * The language code, indicating in which language the results should be returned, if possible.
     * Note that some fields may not be available in the requested language.
     * @link https://developers.google.com/maps/faq#languagesupport supported languages and their codes.
     * Note that google often update supported languages so this list may not be exhaustive.
     *
     * @var string|null
     */
    private $language = null;

    /**
     * @return array
     */
    protected function getAllParametersAsArray(): array
    {
        return [
            'place_id' => $this->placeId,
            'language' => $this->language
        ];
    }

    /**
     * GeoCodeParameters constructor.
     * @param string $placeId
     */
    private function __construct(string $placeId)
    {
        $this->placeId = $placeId;
    }

    /**
     * A textual identifier that uniquely identifies a place, returned from a GoogleApi::placeTextSearch().
     * For more information about place IDs, see @link https://developers.google.com/places/web-service/place-id
     *
     * @return string
     */
    public function getPlaceId(): string
    {
        return $this->placeId;
    }

    /**
     * The language code, indicating in which language the results should be returned, if possible.
     * Note that some fields may not be available in the requested language.
     * @link https://developers.google.com/maps/faq#languagesupport supported languages and their codes.
     * Note that google often update supported languages so this list may not be exhaustive.
     *
     * @return null|string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $placeId
     * @return PlaceDetailsParameters
     */
    public static function makeFromPlaceId(string $placeId)
    {
        if (empty($placeId)) {
            throw new \InvalidArgumentException("Place ID cannot be empty");
        }
        return new PlaceDetailsParameters($placeId);
    }
}