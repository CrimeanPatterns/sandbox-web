<?php


namespace AwardWallet\Common\API\Filter;


use AwardWallet\Schema\Itineraries\Flight;
use AwardWallet\Schema\Itineraries\Itinerary;

class Filter
{

    /** @var BaseField[] */
    private $fields = [];

    public function __construct()
    {
        foreach(glob(__DIR__.'/Field/*.php') as $file) {
            if (class_exists($class = sprintf('\AwardWallet\Common\API\Filter\Field\%s', substr(basename($file), 0, -4))))
                $this->fields[] = new $class();
        }
    }

    /**
     * @param Itinerary[] $itineraries
     * @return Itinerary[]
     */
    public function filter(array $itineraries): array
    {
        $result = [];
        foreach($itineraries as $it) {
            $valid = true;
            foreach($this->fields as $filter) {
                if ($it->cancelled && !$filter->filterCancelled())
                    continue;
                foreach ($filter->getRequiredFieldsForClass(get_class($it)) as $field)
                    if (!$this->checkRequiredField($it, $field))
                        $valid = false;
            }
            if ($it instanceof Flight) {
                foreach($it->segments as $segment) {
                    if (   $segment->departure && $segment->departure->localDateTime
                        && $segment->departure->address && null !== $segment->departure->address->timezone
                        && $segment->arrival && $segment->arrival->localDateTime
                        && $segment->arrival->address && null !== $segment->arrival->address->timezone
                        && strtotime($segment->departure->localDateTime) - $segment->departure->address->timezone === strtotime($segment->arrival->localDateTime) - $segment->arrival->address->timezone) {
                        $valid = false;
                    }
                }
            }
            if ($valid)
                $result[] = $it;
        }
        return $result;
    }

    /**
     * @param $obj
     * @param $field
     * @return bool
     */
    private function checkRequiredField($obj, $field): bool
    {
        if (!is_object($obj))
            return false;
        list($property, $next) = explode('.', $field, 2) + [null, null];
        if (empty($next))
            if (isset($obj->$property) && !(is_array($obj->$property) && empty($obj->$property)))
                return true;
            else
                return false;
        $array = false;
        if (strpos($property, '[]') === strlen($property) - 2) {
            $property = substr($property, 0, -2);
            $array = true;
        }
        if (!isset($obj->$property))
            return false;
        if (!$array)
            return $this->checkRequiredField($obj->$property, $next);
        else {
            foreach ($obj->$property as $sub) {
                if (!$this->checkRequiredField($sub, $next))
                    return false;
            }
            return true;
        }
    }

}