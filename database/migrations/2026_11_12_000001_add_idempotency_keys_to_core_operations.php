<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->uuid('idempotency_key')->nullable()->after('user_id');
            $table->unique(['company_id', 'idempotency_key']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->uuid('idempotency_key')->nullable()->after('user_id');
            $table->unique(['sale_id', 'idempotency_key']);
        });

        Schema::table('sale_returns', function (Blueprint $table) {
            $table->uuid('idempotency_key')->nullable()->after('user_id');
            $table->unique(['sale_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::table('sale_returns', function (Blueprint $table) {
            $table->dropUnique(['sale_id', 'idempotency_key']);
            $table->dropColumn('idempotency_key');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['sale_id', 'idempotency_key']);
            $table->dropColumn('idempotency_key');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};
