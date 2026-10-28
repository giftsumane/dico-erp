<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('store_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('status', [
                'open',
                'closed',
            ])->default('open');

            $table->decimal('opening_amount', 15, 2)
                ->default(0);

            $table->decimal('closing_amount', 15, 2)
                ->nullable();

            $table->decimal('expected_amount', 15, 2)
                ->nullable();

            $table->decimal('difference', 15, 2)
                ->nullable();

            $table->timestamp('opened_at')
                ->useCurrent();

            $table->timestamp('closed_at')
                ->nullable();

            $table->text('opening_notes')
                ->nullable();

            $table->text('closing_notes')
                ->nullable();

            $table->timestamps();

            $table->index([
                'company_id',
                'store_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};