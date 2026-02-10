<?php


namespace AwardWallet\Engine\singaporeair\RewardAvailability\Helpers;


class FormFieldsInformation
{
    private static $title = [
        "Mr" => "Mr", "Mrs" => "Mrs",
    ];

    private static $states = [
        "AK" => "Alaska",
        "AL" => "Alabama",
        "AR" => "Arkansas",
        "AZ" => "Arizona",
        "CA" => "California",
        "CO" => "Colorado",
        "CT" => "Connecticut",
        "DE" => "Delaware",
        "DC" => "District of Columbia",
        "FL" => "Florida",
        "GA" => "Georgia",
        "HI" => "Hawaii",
        "IA" => "Iowa",
        "ID" => "Idaho",
        "IL" => "Illinois",
        "IN" => "Indiana",
        "KS" => "Kansas",
        "KY" => "Kentucky",
        "LA" => "Louisiana",
        "MA" => "Massachusetts",
        "MD" => "Maryland",
        "ME" => "Maine",
        "MI" => "Michigan",
        "MN" => "Minnesota",
        "MO" => "Missouri",
        "MS" => "Mississippi",
        "MT" => "Montana",
        "NC" => "North Carolina",
        "ND" => "North Dakota",
        "NE" => "Nebraska",
        "NH" => "New Hampshire",
        "NJ" => "New Jersey",
        "NM" => "New Mexico",
        "NV" => "Nevada",
        "NY" => "New York",
        "OH" => "Ohio",
        "OK" => "Oklahoma",
        "OR" => "Oregon",
        "PA" => "Pennsylvania",
        "RI" => "Rhode Island",
        "SC" => "South Carolina",
        "SD" => "South Dakota",
        "TN" => "Tennessee",
        "TX" => "Texas",
        "UT" => "Utah",
        "VA" => "Virginia",
        "VT" => "Vermont",
        "WA" => "Washington",
        "WI" => "Wisconsin",
        "WV" => "West Virginia",
        "WY" => "Wyoming",
    ];

    public static function getRegisterFields()
    {
        return [
            'Title' => [
                'Type'     => 'string',
                'Caption'  => 'Title',
                'Required' => true,
                'Options'  => static::$title,
            ],
            'FirstName' => [
                'Type'     => 'string',
                'Caption'  => 'First Name (2-25 length, can use characters and symbols /\'@()”-,)',
                'Required' => true,
            ],
            'LastName' => [
                'Type'     => 'string',
                'Caption'  => 'Last Name (2-26 length, can use characters and symbols /\'@()”-,)',
                'Required' => true,
            ],
            'BirthdayDate' => [
                'Type'     => 'date',
                'Caption'  => 'Your date of birth, older than 18 (MM/DD/YYYY)',
                'Required' => true,
            ],
            'Email' => [
                'Type'     => 'string',
                'Caption'  => 'Email',
                'Required' => true,
            ],
            'Password' => [
                'Type'     => 'string',
                'Caption'  => 'Password (Must be 10-16 characters and include at least 1 lowercase letter, 1 uppercase letter and 1 number)',
                'Required' => true,
            ],
            'MobileAreaCode' => [
                'Type'     => 'string',
                'Caption'  => 'Mobile Area Code for US (4 max-length)',
                'Required' => true,
            ],
            'PhoneNumber' => [
                'Type'     => 'string',
                'Caption'  => 'Phone Number (8 max-length)',
                'Required' => true,
            ],
            'State' => [
                'Type'     => 'string',
                'Caption'  => 'State (US only)',
                'Required' => true,
                'Options'  => static::$states,
            ],
        ];
    }
}