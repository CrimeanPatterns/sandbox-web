<?php

namespace AwardWallet\Engine\testprovider\Timeline;

use AwardWallet\Engine\testprovider\Success;

class Past extends Success
{
    public function ParseItineraries()
    {
        $result = [];

        if (!empty($this->AccountFields['Pass'])) {
            $startDate = strtotime($this->AccountFields['Pass']);
        } else {
            $startDate = strtotime("15:00");
        }

        for ($n = 0; $n < 100; $n++) {
            $result[] = [
                'Kind'               => 'R',
                'ConfirmationNumber' => 'PAST' . $n,
                'HotelName'          => 'Past Hotel',
                'CheckInDate'        => $this->clipSecondsFromTimeStamp($startDate),
                'CheckOutDate'       => $this->clipSecondsFromTimeStamp($startDate + SECONDS_PER_DAY),
                'Address'            => 'London, England',
                "AllowPastSegments"  => "Yes",
            ];
            $startDate = strtotime("-1 day", $startDate);
        }

        return $result;
    }
}
