<?php

namespace AwardWallet\Engine\testprovider\Itineraries;

use AwardWallet\Engine\testprovider\Success;

class MultipleAccountNumber extends Success
{
    public function Parse()
    {
        $this->SetBalance(10);
        $this->SetProperty("Number", $this->AccountFields['Login2']);
    }

    public function ParseItineraries()
    {
        return [
            [
                'Kind'                => 'R',
                'ConfirmationNumber'  => '1252463788',
                'Address'             => '123, Leinin st., Fort Lauderdale, FL / Miami, FL AREA',
                'HotelName'           => 'Sheraton Philadelphia Downtown Hotel',
                'CheckInDate'         => strtotime('+1 week') + 3 * 3600,
                'CheckOutDate'        => strtotime('+2 week'),
                'Phone'               => '123-745-856',
                'Guests'              => 3,
                'Kids'                => 0,
                'Rooms'               => 2,
                'Rate'                => '9600 starpoints and USD 180.00',
                'RateType'            => 'Spg Cash & Points Only To Be Booked With A Spg Award Stay. Guest Must Be A Spg.member. Must Redeem Starpoints For Cash And Points Award.',
                'CancellationPolicy'  => '',
                'RoomType'            => 'Superior Romantic Glimmering Room - Earlybird',
                'RoomTypeDescription' => 'Non-Smoking Room Confirmed',
                'Cost'                => 280.60,
                'Taxes'               => 35.10,
                'Total'               => 315.70,
                'Currency'            => 'USD',
                'AccountNumbers'      => $this->AccountFields['Login3'],
                'GuestNames'          => 'Ms. Dinissa Duvanova',
            ],
        ];
    }
}
