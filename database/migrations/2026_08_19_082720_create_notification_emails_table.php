<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_emails', function (Blueprint $table) {
            $table->id();

            $table->foreignId('notification_id')
                ->constrained('notifications')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('email');
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0);

            $table->timestamp('sent_at')->nullable();
            $table->text('last_error')->nullable();

            $table->timestamps();

            /*
             * One email record per notification.
             * Prevents the same OSN notification from
             * creating multiple email records.
             */
            $table->unique('notification_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_emails');
    }
};