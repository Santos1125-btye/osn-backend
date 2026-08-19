<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use App\Models\Country;
use App\Models\State;
use App\Models\City;

class ImportLocations extends Command
{
    protected $signature = 'locations:import {--country=}';

    protected $description = 'Import Countries, States and Cities';

    private function importCountries(?string $countryCode = null)
    {
        $countries = json_decode(
            file_get_contents(database_path('data/countries.json')),
            true
        );

        $rows = [];

        foreach ($countries as $country) {

            if ($countryCode && $country['iso2'] !== $countryCode) {
                continue;
            }

            $rows[] = [

                'id' => $country['id'],

                'name' => $country['name'],

                'iso2' => $country['iso2'],

                'iso3' => $country['iso3'],

                'phone_code' => $country['phonecode'],

                'currency' => $country['currency'],

                'currency_symbol' => $country['currency_symbol'],

                'emoji' => $country['emoji'],

                'status' => true,

                'created_at' => now(),

                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 1000) as $chunk) {

            Country::upsert(
                $chunk,
                ['id'],
                [
                    'name',
                    'iso2',
                    'iso3',
                    'phone_code',
                    'currency',
                    'currency_symbol',
                    'emoji',
                    'status',
                    'updated_at'
                ]
            );
        }

        $this->info('Countries imported.');
    }

    private function importStates()
    {
        $states = json_decode(
            file_get_contents(database_path('data/states.json')),
            true
        );

        foreach ($states as $state) {

            State::updateOrCreate(

                ['id' => $state['id']],

                [

                    'country_id' => $state['country_id'],

                    'name' => $state['name'],

                    'code' => $state['state_code'],

                    'status' => true,
                ]
            );
        }

        $this->info('States Imported');
    }

    private function importCities()
    {
        $cities = json_decode(
            file_get_contents(database_path('data/cities.json')),
            true
        );

        foreach ($cities as $city) {

            City::updateOrCreate(

                ['id' => $city['id']],

                [

                    'state_id' => $city['state_id'],

                    'name' => $city['name'],

                    'status' => true,
                ]
            );
        }

        $this->info('Cities Imported');
    }
}