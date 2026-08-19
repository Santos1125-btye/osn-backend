<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('payment_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('booking_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('provider_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('transaction_reference')->unique();

            $table->enum('type', [
                'payment',
                'refund',
                'withdrawal',
                'commission',
            ]);

            $table->decimal('amount', 10, 2);

            $table->string('currency')->default('NGN');

            $table->enum('status', [
                'pending',
                'successful',
                'failed',
                'cancelled',
            ])->default('pending');

            $table->string('gateway')->nullable();

            $table->string('gateway_transaction_id')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamp('transaction_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};