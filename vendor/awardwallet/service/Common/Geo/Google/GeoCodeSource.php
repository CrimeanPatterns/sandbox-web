<?php

namespace AwardWallet\Common\Geo\Google;

use AwardWallet\Common\Geo\GeoCodeResult;
use AwardWallet\Common\Geo\GeoCodeSourceInterface;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

class GeoCodeSource implements GeoCodeSourceInterface
{

    private GoogleApi $googleApi;
    private LoggerInterface $logger;
    private SerializerInterface $jms;

    public function __construct(GoogleApi $googleApi, LoggerInterface $logger, SerializerInterface $jms)
    {
        $this->googleApi = $googleApi;
        $this->logger = $logger;
        $this->jms = $jms;
    }

    public function getSourceId() : string
    {
        return 'g';
    }

    /**
     * @return GeoCodeResult[]
     */
    public function geoCode(string $query, array $bias = []): array
    {
        try {
            $params = GeoCodeParameters::makeFromAddress($query);
            $this->addBias($params, $bias);
            $results = $this->googleApi->geoCode($params)->getResults();
            $this->logger->info('google geocode request result', ['body' => $this->jms->serialize($results, 'json')]);
            return array_map(
                function(GeoTag $tag){
                    $result = new GeoCodeResult($tag->getGeometry()->getLocation()->getLat(), $tag->getGeometry()->getLocation()->getLng());
                    $result->postalCode = $tag->getPostalCode();
                    $result->types = $tag->getTypes();
                    $result->formattedAddress = $tag->getFormattedAddress();
                    $result->detailedAddress = GeoResultConverter::decodeGoogleGeoResult($tag);
                    $result->cityUnreliable = true;
                    return $result;
                },
                $results
            );
        }
        catch(GoogleRequestFailedException $e){
            return [];
        }
    }

    private function addBias(GeoCodeParameters $params, array $bias): void
    {
        foreach($bias as $type => $value) {
            switch($type) {
                case 'country':
                    if ('gb' == $value) {
                        $value = 'uk';
                    }
                    $params->setRegion($value);
                    break;
                case 'box':
                    list($neLat, $neLng, $swLat, $swLng) = explode(' ', $value);
                    $params->setBounds(new ViewPort(new LatLng($neLat, $neLng), new LatLng($swLat, $swLng)));
                    break;
            }
        }
    }

}