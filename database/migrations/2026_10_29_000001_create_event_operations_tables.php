<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_store_id')->constrained('stores')->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->string('venue', 200)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('timezone', 64)->default('Africa/Maputo');
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'status', 'starts_at']);
        });

        Schema::create('event_bars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->unsignedInteger('position')->default(0);
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'code']);
        });

        Schema::create('event_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_bar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->unsignedInteger('position')->default(0);
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['event_bar_id', 'code']);
            $table->unique(['event_bar_id', 'store_id']);
        });

        Schema::create('event_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_bar_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('event_counter_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 50);
            $table->string('status', 30)->default('active');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'user_id', 'role', 'status']);
            $table->index(['event_bar_id', 'event_counter_id', 'status']);
        });

        Schema::create('event_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_bar_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('event_counter_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 50);
            $table->string('code', 80);
            $table->string('name', 150);
            $table->string('status', 30)->default('assigned');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'code']);
            $table->index(['event_counter_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_assets');
        Schema::dropIfExists('event_assignments');
        Schema::dropIfExists('event_counters');
        Schema::dropIfExists('event_bars');
        Schema::dropIfExists('events');
    }
};
