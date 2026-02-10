<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 27.05.16
 * Time: 15:01
 */

namespace AwardWallet\Common\Itineraries;

class SegmentsCollection extends AbstractCollection
{
    /**
     * @return FlightSegment
     */
    public function add(){
        $result = new FlightSegment($this->logger);
        $this->collection[] = $result;
        return $result;
    }
}