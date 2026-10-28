<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table
                ->foreignId('stock_batch_id')
                ->nullable()
                ->after('product_id')
                ->constrained('stock_batches')
                ->nullOnDelete();

            $table->index(
                'stock_batch_id',
                'stock_movements_batch_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(
                'stock_movements_batch_index'
            );

            $table->dropConstrainedForeignId(
                'stock_batch_id'
            );
        });
    }
};
