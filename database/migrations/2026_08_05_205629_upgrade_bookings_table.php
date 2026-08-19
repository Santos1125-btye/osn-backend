<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {

            $table->foreignId('address_id')
                ->nullable()
                ->after('service_id')
                ->constrained()
                ->nullOnDelete();

            $table->enum('service_delivery', [
                'customer_location',
                'provider_location',
            ])->after('address_id');

            $table->decimal('discount_amount', 10, 2)
                ->default(0)
                ->after('amount');

            $table->decimal('home_service_fee', 10, 2)
                ->default(0)
                ->after('discount_amount');

            $table->decimal('total_amount', 10, 2)
                ->after('home_service_fee');

            $table->string('promo_code')
                ->nullable()
                ->after('total_amount');

            $table->string('estimated_duration')
                ->nullable()
                ->after('promo_code');

            $table->timestamp('accepted_at')->nullable();

            $table->timestamp('rejected_at')->nullable();

            $table->timestamp('started_at')->nullable();

            $table->timestamp('provider_completed_at')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamp('cancelled_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {

            $table->dropConstrainedForeignId('address_id');

            $table->dropColumn([
                'service_delivery',
                'discount_amount',
                'home_service_fee',
                'total_amount',
                'promo_code',
                'estimated_duration',
                'accepted_at',
                'rejected_at',
                'started_at',
                'provider_completed_at',
                'completed_at',
                'cancelled_at',
            ]);
        });
    }
};