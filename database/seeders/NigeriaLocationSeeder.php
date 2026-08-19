<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\State;
use App\Models\City;

class NigeriaLocationSeeder extends Seeder
{
    public function run(): void
    {
        // Country
        $country = Country::updateOrCreate(
            ['iso2' => 'NG'],
            [
                'name' => 'Nigeria',
                'iso3' => 'NGA',
                'phone_code' => '234',
                'currency' => 'NGN',
                'currency_symbol' => '₦',
                'emoji' => '🇳🇬',
                'status' => true,
            ]
        );

        $states = [
            'Abia',
            'Adamawa',
            'Akwa Ibom',
            'Anambra',
            'Bauchi',
            'Bayelsa',
            'Benue',
            'Borno',
            'Cross River',
            'Delta',
            'Ebonyi',
            'Edo',
            'Ekiti',
            'Enugu',
            'FCT',
            'Gombe',
            'Imo',
            'Jigawa',
            'Kaduna',
            'Kano',
            'Katsina',
            'Kebbi',
            'Kogi',
            'Kwara',
            'Lagos',
            'Nasarawa',
            'Niger',
            'Ogun',
            'Ondo',
            'Osun',
            'Oyo',
            'Plateau',
            'Rivers',
            'Sokoto',
            'Taraba',
            'Yobe',
            'Zamfara',
        ];

        foreach ($states as $stateName) {

            $state = State::updateOrCreate(
                [
                    'country_id' => $country->id,
                    'name' => $stateName,
                ],
                [
                    'code' => strtoupper(substr($stateName, 0, 3)),
                    'status' => true,
                ]
            );

            // Placeholder city
            City::updateOrCreate(
                [
                    'state_id' => $state->id,
                    'name' => $stateName,
                ],
                [
                    'status' => true,
                ]
            );
        }
    }
}