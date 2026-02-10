<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 28.05.16
 * Time: 01:30
 */

namespace AwardWallet\Common\Itineraries;

class FeesCollection extends AbstractCollection
{
    /**
     * @return Fee
     */
    public function add(){
        $result = new Fee($this->logger);
        $this->collection[] = $result;
        return $result;
    }
}