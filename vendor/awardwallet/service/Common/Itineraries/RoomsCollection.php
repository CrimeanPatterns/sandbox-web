<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 27.05.16
 * Time: 15:01
 */

namespace AwardWallet\Common\Itineraries;

class RoomsCollection extends AbstractCollection
{
    /**
     * @return Room
     */
    public function add(){
        $result = new Room($this->logger);
        $this->collection[] = $result;
        return $result;
    }
}