<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bar_stock_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_bar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_store_id')->constrained('stores')->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number', 60)->unique();
            $table->uuid('idempotency_key');
            $table->string('status', 40)->default('requested');
            $table->boolean('initial_allocation')->default(false);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid_total', 15, 2)->default(0);
            $table->decimal('amount_due', 15, 2)->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'idempotency_key']);
            $table->index(['event_bar_id', 'status', 'created_at']);
            $table->index(['event_id', 'amount_due']);
        });

        Schema::create('bar_stock_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bar_stock_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_counter_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_requested', 15, 3);
            $table->decimal('quantity_approved', 15, 3)->default(0);
            $table->decimal('quantity_dispatched', 15, 3)->default(0);
            $table->decimal('quantity_received', 15, 3)->default(0);
            $table->decimal('reserve_quantity', 15, 3)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('difference_reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['bar_stock_order_id', 'event_counter_id', 'product_id'], 'bar_order_counter_product_unique');
        });

        Schema::create('bar_stock_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bar_stock_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('paid_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('method', 30);
            $table->string('reference', 150)->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('paid_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['bar_stock_order_id', 'status']);
        });

        Schema::create('counter_stock_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_counter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('reserve_quantity', 15, 3)->default(0);
            $table->decimal('reorder_level', 15, 3)->default(0);
            $table->timestamps();

            $table->unique(['event_counter_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counter_stock_policies');
        Schema::dropIfExists('bar_stock_settlements');
        Schema::dropIfExists('bar_stock_order_items');
        Schema::dropIfExists('bar_stock_orders');
    }
};
