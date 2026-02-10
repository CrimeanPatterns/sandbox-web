<?php


namespace AwardWallet\Common\Geo\Google;

use JMS\Serializer\Annotation as JMS;

class PlaceAutocompleteResponse extends GoogleResponse
{
    /**
     * @JMS\Type("array<AwardWallet\Common\Geo\Google\Prediction>")
     * @var Prediction[]
     */
    private $predictions = [];

    /**
     * @return Prediction[]
     */
    public function getPredictions(): array
    {
        return $this->predictions;
    }
}