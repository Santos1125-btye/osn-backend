<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_unavailable_dates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_id')
                ->constrained('providers')
                ->cascadeOnDelete();

            $table->date('date');
            $table->string('reason')->nullable();

            $table->timestamps();

            $table->unique(['provider_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_unavailable_dates');
    }
};