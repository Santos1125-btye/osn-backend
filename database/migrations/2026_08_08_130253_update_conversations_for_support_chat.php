<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('booking_id')
                ->nullable()
                ->change();

            $table->string('conversation_type')
                ->default('booking')
                ->after('id');

            $table->foreignId('support_user_id')
                ->nullable()
                ->after('provider_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['support_user_id']);
            $table->dropColumn([
                'conversation_type',
                'support_user_id',
            ]);

            $table->foreignId('booking_id')
                ->nullable(false)
                ->change();
        });
    }
};