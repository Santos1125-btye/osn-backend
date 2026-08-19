<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {

            $table->id();

            $table->foreignId('provider_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('amount', 12, 2);

            $table->string('bank_name');

            $table->string('account_name');

            $table->string('account_number');

            $table->string('reference')->unique();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'paid'
            ])->default('pending');

            $table->text('admin_note')->nullable();

            $table->timestamp('approved_at')->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};