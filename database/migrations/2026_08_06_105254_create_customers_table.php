<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('nuit')
                ->nullable();

            $table->string('email')
                ->nullable();

            $table->string('phone')
                ->nullable();

            $table->string('address')
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->boolean('active')
                ->default(true);

            $table->timestamps();

            $table->index([
                'company_id',
                'name',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};