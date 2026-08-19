<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Address;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where(
            'role',
            'customer'
        )->get();

        foreach ($customers as $customer) {

            Address::firstOrCreate(

                [
                    'user_id' => $customer->id,
                    'is_default' => true,
                ],

                [
                    'label' => 'Home',

                    'address_line' => fake()->streetAddress(),

                    'city' => 'Warri',

                    'state' => 'Delta',

                    'country' => 'Nigeria',

                    'latitude' => fake()->latitude(),

                    'longitude' => fake()->longitude(),

                ]

            );

        }
    }
}