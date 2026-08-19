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

            $table->enum('business_type', [
                'individual',
                'registered_business',
            ])->after('business_name');

            $table->string('business_email')->nullable()->after('phone');

            $table->text('business_description')->nullable()->after('bio');

            $table->integer('years_of_experience')->nullable();

            $table->string('certificate_file')->nullable();

            $table->enum('verification_status', [
                'pending',
                'verified',
                'rejected',
            ])->default('pending');

            $table->text('rejection_reason')->nullable();

            $table->string('business_address')->nullable();

            $table->string('state')->nullable();

            $table->string('lga')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();

            $table->decimal('longitude', 10, 7)->nullable();

            $table->boolean('is_active')->default(true);
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
