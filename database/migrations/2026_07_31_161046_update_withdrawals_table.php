<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {

            $table->foreignId('provider_bank_account_id')
                ->after('provider_id')
                ->constrained('provider_bank_accounts')
                ->cascadeOnDelete();

            $table->dropColumn([
                'bank_name',
                'account_name',
                'account_number',
            ]);

        });
    }

    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {

            $table->dropConstrainedForeignId('provider_bank_account_id');

            $table->string('bank_name');

            $table->string('account_name');

            $table->string('account_number');

        });
    }
};