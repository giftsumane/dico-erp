<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('store_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('number')
                ->nullable();

            $table->string('supplier_reference')
                ->nullable();

            $table->date('purchase_date');

            $table->enum('status', [
                'draft',
                'confirmed',
                'cancelled',
            ])->default('draft');

            $table->decimal('subtotal', 15, 2)
                ->default(0);

            $table->decimal('tax_total', 15, 2)
                ->default(0);

            $table->decimal('total', 15, 2)
                ->default(0);

            $table->text('notes')
                ->nullable();

            $table->timestamp('confirmed_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'company_id',
                'store_id',
                'purchase_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};