<?php
/**
 * Created by PhpStorm.
 * User: APuzakov
 * Date: 27.05.16
 * Time: 15:01
 */

namespace AwardWallet\Common\Itineraries;

class PersonsCollection extends AbstractCollection
{
    /**
     * @return Person
     */
    public function add(){
        $result = new Person($this->logger);
        $this->collection[] = $result;
        return $result;
    }
}