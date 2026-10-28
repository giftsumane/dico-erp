<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->decimal('quantity', 15, 3);

            $table->decimal('unit_cost', 15, 2);

            $table->decimal('tax_rate', 5, 2)
                ->default(0);

            $table->decimal('subtotal', 15, 2);

            $table->decimal('tax_total', 15, 2)
                ->default(0);

            $table->decimal('total', 15, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};