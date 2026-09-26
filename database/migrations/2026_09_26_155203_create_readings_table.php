<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The composed narrative reading, generated on demand when the user
 * clicks "View Full Details of this Kundali".
 *
 * Stored per-locale so an English and a Nepali reading can coexist for
 * the same chart once translations are added.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kundali_id')->constrained()->cascadeOnDelete();

            $table->string('locale', 8)->default('en');
            $table->json('sections');

            $table->timestamps();

            $table->unique(['kundali_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('readings');
    }
};
