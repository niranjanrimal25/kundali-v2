<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The birth record. This table stores ONLY raw input — never derived
 * values — so a chart can always be recomputed from source of truth.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kundalis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();

            $table->date('birth_date');
            $table->time('birth_time');

            $table->string('birth_place');
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            $table->string('timezone', 64);

            // The offset actually applied after historical resolution,
            // cached for display and for auditing older births.
            $table->decimal('utc_offset_hours', 5, 2)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kundalis');
    }
};
