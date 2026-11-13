<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('bank_name', 150);
            $table->string('account_number', 100)->nullable();
            $table->string('nib', 100)->nullable();
            $table->string('iban', 100)->nullable();
            $table->string('swift_code', 50)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_bank_accounts');
    }
};
