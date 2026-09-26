<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The interpretation corpus — the knowledge base that turns computed
 * chart facts into astrologer-style prose.
 *
 * Each row is one assertion. The composer selects matching rows, resolves
 * contradictions by weight and polarity, then stitches them into flowing
 * paragraphs rather than printing them as a bullet list.
 *
 * condition_type examples:
 *   lagna_sign          key: "0"              (Aries rising)
 *   house_sign          key: "1:0"            (1st house in Aries)
 *   planet_house        key: "Mars:8"         (Mars in 8th)
 *   planet_sign         key: "Saturn:6"       (Saturn in Libra)
 *   lord_in_house       key: "1:8"            (1st lord in 8th)
 *   conjunction         key: "Sun+Mercury"
 *   aspect              key: "Jupiter>1"      (Jupiter aspects 1st)
 *   dignity             key: "Mars:exalted"
 *   digbala             key: "Jupiter:strong"
 *   nakshatra_moon      key: "7"              (Moon in Pushya)
 *   yoga                key: "gajakesari"
 *   dosha               key: "mangal"
 *   dasha               key: "Saturn"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interpretation_rules', function (Blueprint $table) {
            $table->id();

            $table->string('condition_type', 40);
            $table->string('condition_key', 60);

            // Which report section this fragment belongs to.
            $table->string('section', 40)->default('general');

            $table->string('locale', 8)->default('en');

            // -2 very negative … 0 neutral … +2 very positive.
            // Drives concessive phrasing when fragments conflict.
            $table->tinyInteger('polarity')->default(0);

            // Higher weight wins when two rules contradict.
            $table->unsignedTinyInteger('weight')->default(10);

            $table->text('text');

            // Optional extra gate, e.g. {"requires_dignity":"debilitated"}
            $table->json('conditions')->nullable();

            $table->timestamps();

            $table->index(['condition_type', 'condition_key', 'locale'], 'rules_lookup_index');
            $table->index(['section', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interpretation_rules');
    }
};
