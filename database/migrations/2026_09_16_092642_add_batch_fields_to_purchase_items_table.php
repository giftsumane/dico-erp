<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'purchase_items',
            function (Blueprint $table) {

                $table
                    ->foreignId('stock_batch_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('stock_batches')
                    ->nullOnDelete();

                $table
                    ->string('batch_code', 100)
                    ->nullable()
                    ->after('stock_batch_id');

                $table
                    ->string('batch_name', 200)
                    ->nullable()
                    ->after('batch_code');

                $table
                    ->date('batch_received_at')
                    ->nullable()
                    ->after('batch_name');

                $table
                    ->date('batch_expiry_date')
                    ->nullable()
                    ->after('batch_received_at');

                $table->index(
                    'stock_batch_id',
                    'purchase_items_stock_batch_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'purchase_items',
            function (Blueprint $table) {

                $table->dropIndex(
                    'purchase_items_stock_batch_index'
                );

                $table->dropForeign([
                    'stock_batch_id',
                ]);

                $table->dropColumn([
                    'stock_batch_id',
                    'batch_code',
                    'batch_name',
                    'batch_received_at',
                    'batch_expiry_date',
                ]);
            }
        );
    }
};