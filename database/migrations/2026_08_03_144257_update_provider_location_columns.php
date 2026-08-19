<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {

            $table->foreignId('country_id')
                ->nullable()
                ->after('business_address')
                ->constrained();

            $table->foreignId('state_id')
                ->nullable()
                ->after('country_id')
                ->constrained();

            $table->foreignId('city_id')
                ->nullable()
                ->after('state_id')
                ->constrained();

            $table->dropColumn([
                'state',
                'lga',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
