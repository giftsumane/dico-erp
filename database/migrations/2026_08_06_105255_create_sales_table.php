<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('store_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('number')
                ->nullable();

            $table->enum('status', [
                'draft',
                'completed',
                'cancelled',
            ])->default('draft');

            $table->decimal('subtotal', 15, 2)
                ->default(0);

            $table->decimal('discount_total', 15, 2)
                ->default(0);

            $table->decimal('tax_total', 15, 2)
                ->default(0);

            $table->decimal('total', 15, 2)
                ->default(0);

            $table->decimal('paid_total', 15, 2)
                ->default(0);

            $table->decimal('change_total', 15, 2)
                ->default(0);

            $table->timestamp('completed_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'company_id',
                'store_id',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};