<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('company_id')
                ->constrained('companies')
                ->restrictOnDelete();

            $table
                ->foreignId('store_id')
                ->constrained('stores')
                ->restrictOnDelete();

            $table
                ->foreignId('sale_id')
                ->constrained('sales')
                ->restrictOnDelete();

            $table
                ->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table
                ->foreignId('cash_register_id')
                ->nullable()
                ->constrained('cash_registers')
                ->nullOnDelete();

            $table->string('number', 50)
                ->nullable()
                ->unique();

            $table->string('status', 30)
                ->default('completed');

            /*
            |--------------------------------------------------------------------------
            | Tipo
            |--------------------------------------------------------------------------
            |
            | partial = devolução parcial
            | full    = devolução total
            |
            */

            $table->string('return_type', 20);

            $table->decimal(
                'total',
                15,
                2
            )->default(0);

            /*
            |--------------------------------------------------------------------------
            | Reembolso
            |--------------------------------------------------------------------------
            |
            | none  = sem devolução monetária
            | cash  = dinheiro devolvido fisicamente
            | other = reembolso externo/electrónico
            |
            */

            $table->string(
                'refund_method',
                30
            )->default('none');

            $table->decimal(
                'refund_amount',
                15,
                2
            )->default(0);

            $table->string(
                'refund_reference',
                150
            )->nullable();

            $table->string(
                'reason',
                255
            )->nullable();

            $table->text('notes')
                ->nullable();

            $table->timestamp(
                'returned_at'
            );

            $table->timestamps();

            $table->index([
                'company_id',
                'store_id',
                'returned_at',
            ]);

            $table->index([
                'sale_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'sale_returns'
        );
    }
};