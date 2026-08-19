<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_bank_accounts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('provider_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('bank_code');

            $table->string('bank_name');

            $table->string('account_name');

            $table->string('account_number');

            $table->boolean('is_default')
                ->default(true);

            $table->boolean('is_verified')
                ->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_bank_accounts');
    }
};