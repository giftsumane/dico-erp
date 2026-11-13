<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 30);
            $table->unsignedSmallInteger('year');
            $table->string('prefix', 20);
            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();

            $table->unique(['company_id', 'store_id', 'document_type', 'year']);
        });

        Schema::create('commercial_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_document_id')->nullable()->constrained('commercial_documents')->nullOnDelete();
            $table->uuid('idempotency_key')->nullable();
            $table->string('type', 30);
            $table->string('number')->nullable();
            $table->string('status', 30)->default('draft');
            $table->date('document_date');
            $table->date('valid_until')->nullable();
            $table->date('due_date')->nullable();
            $table->string('currency', 3)->default('MZN');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_total', 15, 2)->default(0);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid_total', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'number']);
            $table->unique(['company_id', 'idempotency_key']);
            $table->index(['company_id', 'store_id', 'type', 'status']);
        });

        Schema::create('commercial_document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commercial_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_total', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->timestamps();
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('idempotency_key')->nullable();
            $table->string('number');
            $table->date('receipt_date');
            $table->string('payment_method', 30);
            $table->string('reference')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('issued');
            $table->text('notes')->nullable();
            $table->timestamp('issued_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'number']);
            $table->unique(['company_id', 'idempotency_key']);
            $table->index(['company_id', 'store_id', 'receipt_date']);
        });

        Schema::create('receipt_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('commercial_document_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->unique(['receipt_id', 'commercial_document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_allocations');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('commercial_document_items');
        Schema::dropIfExists('commercial_documents');
        Schema::dropIfExists('document_series');
    }
};
