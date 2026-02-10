<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 28.05.16
 * Time: 01:30
 */

namespace AwardWallet\Common\Itineraries;

class CarRentalDiscountsCollection extends AbstractCollection
{
    /**
     * @return CarRentalDiscount
     */
    public function add(){
        $result = new CarRentalDiscount($this->logger);
        $this->collection[] = $result;
        return $result;
    }
}