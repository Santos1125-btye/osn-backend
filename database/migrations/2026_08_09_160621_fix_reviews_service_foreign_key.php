<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->foreign('service_id')
                ->references('id')
                ->on('provider_services')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->foreign('service_id')
                ->references('id')
                ->on('provider_services')
                ->cascadeOnDelete();
        });
    }
};