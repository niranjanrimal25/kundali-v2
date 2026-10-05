<?php

namespace App\Console\Commands;

use App\Services\Astrology\Reading\RuleBase;
use Illuminate\Console\Command;

/**
 * Reports how much of the rule base exists in a given locale.
 *
 * Untranslated rules fall back to English rather than disappearing, so
 * a partial translation degrades gracefully. This makes the remaining
 * work visible instead of letting mixed-language output go unnoticed.
 */
class TranslationStatus extends Command
{
    protected $signature = 'jyotish:translation-status {locale=ne} {--list : List every untranslated rule id}';

    protected $description = 'Report rule-base translation coverage for a locale';

    public function handle(RuleBase $base): int
    {
        $locale = $this->argument('locale');

        $this->line('');
        $this->info("Translation coverage for '{$locale}'");
        $this->line('');

        $totalRules = 0;
        $totalMissing = 0;

        foreach (['composite', 'trik', 'karakatwa'] as $file) {
            $rules = $base->localised($file, $locale);
            $missing = array_filter($rules, fn ($r) => ! ($r['translated'] ?? false));

            $done = count($rules) - count($missing);
            $pct = count($rules) > 0 ? round($done / count($rules) * 100) : 100;

            $colour = $pct === 100 ? 'green' : ($pct > 0 ? 'yellow' : 'red');

            $this->line(sprintf(
                '  %-12s <fg=%s>%3d%%</>  %d of %d',
                $file, $colour, $pct, $done, count($rules)
            ));

            if ($this->option('list')) {
                foreach ($missing as $rule) {
                    $this->line("      <fg=gray>{$rule['id']}</>");
                }
            }

            $totalRules += count($rules);
            $totalMissing += count($missing);
        }

        $overall = $totalRules > 0 ? round(($totalRules - $totalMissing) / $totalRules * 100) : 100;

        $this->line('');
        $this->line("  Overall: {$overall}%  (".($totalRules - $totalMissing)." of {$totalRules})");

        if ($totalMissing > 0) {
            $this->line('');
            $this->warn("{$totalMissing} rule(s) will fall back to English.");
            $this->line('  Add them to resources/rules/'.$locale.'/<file>.json, keyed by rule id.');
        }

        $this->line('');

        return self::SUCCESS;
    }
}
