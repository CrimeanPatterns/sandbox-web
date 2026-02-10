<?php

namespace AwardWallet\Common\Itineraries;

class PhonesCollection extends AbstractCollection
{
    /** @return Phone */
    public function add(){
        $result = new Phone($this->logger);
        $this->collection[] = $result;
        return $result;
    }
}