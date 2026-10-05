<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('readings', function (Blueprint $table) {
            // Identifies the rule corpus this reading was generated from.
            // When the corpus or the active rule sources change, the
            // fingerprint stops matching and the reading regenerates
            // itself, instead of silently serving stale text.
            $table->string('corpus_fingerprint', 64)->nullable()->after('sections');
        });
    }

    public function down(): void
    {
        Schema::table('readings', function (Blueprint $table) {
            $table->dropColumn('corpus_fingerprint');
        });
    }
};
