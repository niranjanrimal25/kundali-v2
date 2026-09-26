<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cached output of the calculation layers (astronomy + Vedic math).
 *
 * Deterministic for a given birth record, so it is computed once and
 * invalidated only when the underlying birth data changes. The
 * `engine_version` column lets us force recomputation after an
 * ephemeris or algorithm upgrade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kundali_id')->constrained()->cascadeOnDelete();

            $table->json('facts');

            $table->decimal('julian_day', 16, 8)->nullable();
            $table->decimal('ayanamsa', 10, 6)->nullable();
            $table->unsignedTinyInteger('lagna_sign')->nullable();
            $table->unsignedTinyInteger('moon_sign')->nullable();
            $table->unsignedTinyInteger('moon_nakshatra')->nullable();

            $table->string('engine_version', 32)->default('1.0');
            $table->timestamps();

            $table->unique('kundali_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_data');
    }
};
