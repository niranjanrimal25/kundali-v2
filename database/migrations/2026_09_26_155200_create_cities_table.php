<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offline place database sourced from GeoNames (Creative Commons).
 * Supplies latitude, longitude AND the IANA timezone for a birthplace,
 * removing any dependence on a paid geocoding API.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('geoname_id')->nullable()->unique();
            $table->string('name', 200)->index();
            $table->string('ascii_name', 200)->index();
            $table->char('country_code', 2)->index();
            $table->string('admin1', 20)->nullable();
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);
            $table->string('timezone', 64);
            $table->unsignedBigInteger('population')->default(0);
            $table->timestamps();

            // Autocomplete orders by population within a name prefix match.
            $table->index(['ascii_name', 'population']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
