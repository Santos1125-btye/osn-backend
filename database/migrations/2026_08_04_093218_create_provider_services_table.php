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
        Schema::create('provider_services', function (Blueprint $table) {

            $table->id();

            $table->foreignId('provider_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            // Provider-defined
            $table->string('sub_category');
            $table->string('service_name');

            $table->text('description')->nullable();

            $table->text('cover_image')->nullable();

            // Pricing
            $table->enum('pricing_method', [
                'fixed',
                'range',
                'consultation'
            ]);

            $table->decimal('price', 12, 2)->nullable();

            $table->decimal('min_price', 12, 2)->nullable();

            $table->decimal('max_price', 12, 2)->nullable();

            // Duration
            $table->enum('duration_method', [
                'fixed',
                'range',
                'consultation'
            ]);

            $table->string('duration')->nullable();

            $table->string('min_duration')->nullable();

            $table->string('max_duration')->nullable();

            // Consultation
            $table->enum('consultation_type', [
                'phone',
                'video',
                'physical'
            ])->nullable();

            // Ordering
            $table->unsignedInteger('display_order')->default(0);

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->unique(
                ['provider_id', 'category_id', 'sub_category', 'service_name'],
                'ps_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_services');
    }
};
