<?php


namespace AwardWallet\Common\Geo\Google;


class AspectRating
{
    /**
     * Name of the aspect that is being rated.
     * The following types are supported: appeal, atmosphere, decor, facilities, food, overall, quality and service.
     *
     * @var string|null
     */
    private $type;

    /**
     * User's rating for this particular aspect, from 0 to 3.
     *
     * @var int|null
     */
    private $rating;
}