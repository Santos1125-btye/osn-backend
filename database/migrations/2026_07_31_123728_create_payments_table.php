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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('provider_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('payment_reference')->unique();

            $table->string('gateway_reference')->nullable();

            $table->decimal('amount', 10, 2);

            $table->decimal('platform_fee', 10, 2)->default(0);

            $table->decimal('provider_amount', 10, 2)->default(0);

            $table->string('currency')->default('NGN');

            $table->string('gateway')->default('paystack');

            $table->string('payment_method')->nullable();

            $table->enum('status', [
                'pending',
                'successful',
                'failed',
                'refunded'
            ])->default('pending');

            $table->timestamp('paid_at')->nullable();

            $table->text('gateway_response')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
