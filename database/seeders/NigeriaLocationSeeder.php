<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NigeriaLocationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            /*
             * =========================================================
             * COUNTRY
             * =========================================================
             */

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

            /*
             * =========================================================
             * LGA DATASET
             * =========================================================
             */

            $path = database_path('data/nigeria-lgas.json');

            if (! file_exists($path)) {
                throw new \RuntimeException(
                    "Nigeria LGA dataset not found: {$path}"
                );
            }

            $data = json_decode(
                file_get_contents($path),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if (! is_array($data)) {
                throw new \RuntimeException(
                    'Invalid Nigeria LGA dataset.'
                );
            }

            if (count($data) !== 774) {
                throw new \RuntimeException(
                    'Expected 774 LGAs, found ' . count($data) . '.'
                );
            }

            /*
             * =========================================================
             * STATES + LGAs
             * =========================================================
             */

            $lgaCount = 0;

            foreach ($data as $lga) {
                $stateName = trim($lga['state_name'] ?? '');
                $lgaName = trim($lga['name'] ?? '');

                if ($stateName === '' || $lgaName === '') {
                    continue;
                }

                /*
                 * Dataset uses:
                 * Federal Capital Territory
                 *
                 * Our database currently uses:
                 * FCT
                 */
                if ($stateName === 'Federal Capital Territory') {
                    $stateName = 'FCT';
                }

                /*
                 * Find the existing state.
                 *
                 * We do NOT create duplicate states.
                 */
                $state = State::where('country_id', $country->id)
                    ->where('name', $stateName)
                    ->first();

                if (! $state) {
                    throw new \RuntimeException(
                        "State not found in database: {$stateName}"
                    );
                }

                /*
                 * Store the LGA in the existing cities table.
                 */
                City::updateOrCreate(
                    [
                        'state_id' => $state->id,
                        'name' => $lgaName,
                    ],
                    [
                        'status' => true,
                    ]
                );

                $lgaCount++;
            }

            /*
             * =========================================================
             * REMOVE OLD PLACEHOLDER CITIES
             * =========================================================
             *
             * The original seeder created:
             *
             * Delta → Delta
             * Lagos → Lagos
             * Rivers → Rivers
             *
             * Those are not LGAs, so remove them.
             */



            /*
             * =========================================================
             * FINAL VALIDATION
             * =========================================================
             */



            $this->command?->info(
                "Nigeria location seeding completed successfully."
            );

            $this->command?->info(
                "Countries: " . Country::count()
            );

            $this->command?->info(
                "States: " . State::where(
                    'country_id',
                    $country->id
                )->count()
            );

 
        });
    }
}