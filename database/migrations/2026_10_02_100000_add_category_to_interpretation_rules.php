<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interpretation_rules', function (Blueprint $table) {
            // Life area a rule speaks to, used to group the summary in the
            // simple analysis view: health, mind, relationships, career.
            $table->string('category', 24)->nullable()->after('section');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('interpretation_rules', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};
