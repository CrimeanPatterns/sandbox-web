<?php


namespace AwardWallet\Engine\tapportugal\RewardAvailability\Helpers;


class FormFieldsInformation
{
    public static $title = [
        'Mr' => 'Mr.', 'Mrs' => 'Mrs.',
    ];

    private static $states = [
        "AK" => "Alaska", "AL" => "Alabama", "AR" => "Arkansas", "AZ" => "Arizona", "CA" => "California", "CO" => "Colorado", "CT" => "Connecticut", "DE" => "Delaware", "DC" => "District of Columbia", "FL" => "Florida", "GA" => "Georgia", "HI" => "Hawaii", "IA" => "Iowa", "ID" => "Idaho", "IL" => "Illinois", "IN" => "Indiana", "KS" => "Kansas", "KY" => "Kentucky", "LA" => "Louisiana", "MA" => "Massachusetts", "MD" => "Maryland", "ME" => "Maine", "MI" => "Michigan", "MN" => "Minnesota", "MO" => "Missouri", "MS" => "Mississippi", "MT" => "Montana", "NC" => "North Carolina", "ND" => "North Dakota", "NE" => "Nebraska", "NH" => "New Hampshire", "NJ" => "New Jersey", "NM" => "New Mexico", "NV" => "Nevada", "NY" => "New York", "OH" => "Ohio", "OK" => "Oklahoma", "OR" => "Oregon", "PA" => "Pennsylvania", "RI" => "Rhode Island", "SC" => "South Carolina", "SD" => "South Dakota", "TN" => "Tennessee", "TX" => "Texas", "UT" => "Utah", "VA" => "Virginia", "VT" => "Vermont", "WA" => "Washington", "WI" => "Wisconsin", "WV" => "West Virginia", "WY" => "Wyoming",
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
                'Caption'  => 'First Name (Only characters, no special characters or spaces or number)',
                'Required' => true,
            ],
            'LastName' => [
                'Type'     => 'string',
                'Caption'  => 'Last Name (Only characters, no special characters or spaces or number)',
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
            'PhoneNumber' => [
                'Type'     => 'string',
                'Caption'  => 'Phone Number (10 numbers length)',
                'Required' => true,
            ],
            'State' => [
                'Type'     => 'string',
                'Caption'  => 'State Of Residence',
                'Required' => true,
                'Options'  => static::$states,
            ],
            'City' => [
                'Type'     => 'string',
                'Caption'  => 'City (Only characters)',
                'Required' => true,
            ],
        ];
    }
}