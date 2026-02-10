<?php


namespace AwardWallet\Common\Geo\Google;

use JMS\Serializer\Annotation as JMS;

class AddressComponent
{
    /**
     * An array indicating the type of the address component.
     *
     * @JMS\Type("array<string>")
     *
     * @var string[]|null
     */
    private $types;
    /**
     * Full text description or name of the address component.
     *
     * @JMS\SerializedName("long_name")
     * @JMS\Type("string")
     *
     * @var string|null
     */
    private $longName;
    /**
     * An abbreviated textual name for the address component.
     * For example, an address component for the state of Alaska may have a long_name of "Alaska" and a short_name of "AK" using the 2-letter postal abbreviation.
     *
     * @JMS\SerializedName("short_name")
     * @JMS\Type("string")
     *
     * @var string|null
     */
    private $shortName;

    /**
     * @return null|\string[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @return null|string
     */
    public function getLongName()
    {
        return $this->longName;
    }

    /**
     * @return null|string
     */
    public function getShortName()
    {
        return $this->shortName;
    }
}