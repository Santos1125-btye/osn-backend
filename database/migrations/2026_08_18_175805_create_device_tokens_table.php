<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
             * FCM registration token.
             *
             * A token is unique to an app installation/device
             * and may change over time.
             */
            $table->text('token');

            /*
             * customer / provider.
             *
             * This prevents accidental cross-app registration.
             */
            $table->enum('app', [
                'customer',
                'provider',
            ]);

            $table->string('platform', 30)
                ->default('android');

            $table->string('device_name')->nullable();

            $table->string('app_version')->nullable();

            $table->timestamp('last_seen_at')->nullable();

            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            /*
             * We cannot use a normal unique index on a TEXT column
             * in MySQL reliably across all configurations.
             *
             * Token lookups are indexed through a hash instead.
             */
            $table->string('token_hash', 64)->unique();

            $table->index([
                'user_id',
                'app',
            ]);

            $table->index([
                'user_id',
                'revoked_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};