<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('unit_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name');

            $table->string('sku')->nullable();

            $table->string('barcode')->nullable();

            $table->text('description')->nullable();

            $table->decimal('purchase_price', 15, 2)
                ->default(0);

            $table->decimal('sale_price', 15, 2)
                ->default(0);

            $table->decimal('tax_rate', 5, 2)
                ->default(0);

            $table->decimal('minimum_stock', 15, 3)
                ->default(0);

            $table->boolean('track_stock')
                ->default(true);

            $table->boolean('active')
                ->default(true);

            $table->timestamps();

            $table->unique([
                'company_id',
                'sku',
            ]);

            $table->unique([
                'company_id',
                'barcode',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};