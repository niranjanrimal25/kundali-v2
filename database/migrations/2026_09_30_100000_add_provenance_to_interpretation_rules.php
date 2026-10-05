<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interpretation_rules', function (Blueprint $table) {
            // Where a rule came from, so any sentence in a reading can be
            // traced to its origin rather than taken on trust.
            //   classical             - from a named source text
            //   traditional-consensus - widely taught, no single source
            //   modern-synthesis      - written by this project
            $table->string('provenance', 32)->default('modern-synthesis')->after('text');

            // Free-text citation, e.g. "BPHS 81.3" or "supplied by owner".
            $table->string('source', 120)->nullable()->after('provenance');

            $table->index('provenance');
        });
    }

    public function down(): void
    {
        Schema::table('interpretation_rules', function (Blueprint $table) {
            $table->dropIndex(['provenance']);
            $table->dropColumn(['provenance', 'source']);
        });
    }
};
