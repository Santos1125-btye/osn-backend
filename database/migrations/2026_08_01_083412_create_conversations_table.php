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
        Schema::create('conversations', function (Blueprint $table) {

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

            $table->enum('status', [
                'active',
                'completed',
                'cancelled',
            ])->default('active');

            // Timestamp of the latest message for sorting conversations
            $table->timestamp('last_message_at')
                ->nullable();

            $table->timestamps();

            // One booking = One conversation
            $table->unique('booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};