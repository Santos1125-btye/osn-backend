<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_working_hours', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_id')
                ->constrained('providers')
                ->cascadeOnDelete();

            $table->string('day');

            $table->boolean('is_available')->default(true);

            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->timestamps();

            $table->unique(['provider_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_working_hours');
    }
};