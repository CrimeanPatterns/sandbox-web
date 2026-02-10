<?php

namespace AwardWallet\Common\Itineraries;

class ConfNoCollection extends AbstractCollection
{
    /** @return ConfNo */
    public function add(){
        $result = new ConfNo($this->logger);
        $this->collection[] = $result;
        return $result;
    }
}