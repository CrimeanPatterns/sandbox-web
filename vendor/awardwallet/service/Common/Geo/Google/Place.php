<?php


namespace AwardWallet\Common\Geo\Google;

use JMS\Serializer\Annotation as JMS;

class Place
{
    /**
     * Human-readable address of this place.
     * Often this address is equivalent to the "postal address", which sometimes differs from country to country.
     *
     * @JMS\SerializedName("formatted_address")
     * @JMS\Type("string")
     *
     * @var string|null
     */
    protected $formattedAddress;

    /**
     * Contains Location and Viewport
     *
     * @JMS\Type("AwardWallet\Common\Geo\Google\Geometry")
     *
     * @var Geometry
     */
    protected $geometry;

    /**
     * Contains the URL of a suggested icon which may be displayed to the user when indicating this result on a map.
     *
     * @JMS\Type("string")
     *
     * @var string|null
     */
    protected $icon;

    /**
     * Contains the human-readable name for the returned result.
     * For establishment results, this is usually the canonicalized business name.
     *
     * @JMS\Type("string")
     *
     * @var string
     */
    protected $name;

    /**
     * @JMS\SerializedName("opening_hours")
     * @JMS\Type("AwardWallet\Common\Geo\Google\OpeningHours")
     *
     * @var OpeningHours|null
     */
    protected $openingHours;

    /**
     * Boolean flag indicating whether the place has permanently shut down (value true). If the place is not permanently closed, the flag is absent from the response.
     *
     * @JMS\SerializedName("permanently_closed")
     * @JMS\Type("boolean")
     *
     * @var boolean|null
     */
    protected $permanentlyClosed;

    /**
     * An array of photo objects, each containing a reference to an image. A Place Details request may return up to ten photos.
     * More information about place photos and how you can use the images in your application can be found in the Place Photos documentation.
     * @link https://developers.google.com/places/web-service/photos
     *
     * @JMS\Type("array<AwardWallet\Common\Geo\Google\Photo>")
     *
     * @var Photo[]
     */
    protected $photos;

    /**
     * A textual identifier that uniquely identifies a place. To retrieve information about the place, pass this identifier in the placeId field of a Places API request.
     * For more information about place IDs, @link https://developers.google.com/places/web-service/place-id
     *
     * @JMS\SerializedName("place_id")
     * @JMS\Type("string")
     *
     * @var string
     */
    protected $placeId;

    /**
     * Indicates the scope of the place_id. The possible values are:
     *      APP: The place ID is recognised by your application only. This is because your application added the place, and the place has not yet passed the moderation process.
     *      GOOGLE: The place ID is available to other applications and on Google Maps.
     *
     * @JMS\Type("string")
     *
     * @var string|null
     */
    protected $scope;

    /**
     * An array of zero, one or more alternative place IDs for the place, with a scope related to each alternative ID.
     *
     * @JMS\SerializedName("alt_ids")
     * @JMS\Type("array")
     *
     * @var array|null
     */
    protected $altIds;

    /**
     * The price level of the place, on a scale of 0 to 4. The exact amount indicated by a specific value will vary from region to region.
     * Price levels are interpreted as follows:
     *      0 — Free
     *      1 — Inexpensive
     *      2 — Moderate
     *      3 — Expensive
     *      4 — Very Expensive
     *
     * @JMS\SerializedName("price_level")
     * @JMS\Type("integer")
     *
     * @var int|null
     */
    protected $priceLevel;

    /**
     * Place's rating, from 1.0 to 5.0, based on aggregated user reviews.
     *
     * @JMS\Type("float")
     *
     * @var float
     */
    protected $rating;

    /**
     * An array of feature types describing the given result. See the list of supported types.
     * @link https://developers.google.com/places/web-service/supported_types#table2
     *
     * @JMS\Type("array<string>")
     *
     * @var string[]
     */
    protected $types = [];

    /**
     * Lists a simplified address for the place, including the street name, street number, and locality, but not the province/state, postal code, or country.
     * For example, Google's Sydney, Australia office has a vicinity value of 48 Pirrama Road, Pyrmont.
     *
     * @JMS\Type("string")
     *
     * @var string|null
     */
    protected $vicinity;

    /**
     * @return null|string
     */
    public function getFormattedAddress()
    {
        return $this->formattedAddress;
    }

    /**
     * @return Geometry
     */
    public function getGeometry()
    {
        return $this->geometry;
    }

    /**
     * @return null|string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return OpeningHours|null
     */
    public function getOpeningHours()
    {
        return $this->openingHours;
    }

    /**
     * @return bool|null
     */
    public function getPermanentlyClosed()
    {
        return $this->permanentlyClosed;
    }

    /**
     * @return Photo[]
     */
    public function getPhotos()
    {
        return $this->photos;
    }

    /**
     * @return string
     */
    public function getPlaceId()
    {
        return $this->placeId;
    }

    /**
     * @return null|string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return null|\string[]
     */
    public function getAltIds()
    {
        return $this->altIds;
    }

    /**
     * @return int|null
     */
    public function getPriceLevel()
    {
        return $this->priceLevel;
    }

    /**
     * @return float
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @return \string[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @return null|string
     */
    public function getVicinity()
    {
        return $this->vicinity;
    }

    /**
     * @return float
     */
    public function getLatitude()
    {
        return $this->geometry->getLocation()->getLat();
    }

    /**
     * @return float
     */
    public function getLongitude()
    {
        return $this->geometry->getLocation()->getLng();
    }
}