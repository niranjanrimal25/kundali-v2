<?php

namespace App\Console\Commands;

use App\Services\Astrology\Interpretation\ConditionMatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Checks the rule corpus for rules that can never fire.
 *
 * A composite rule with a typo in its condition JSON fails silently —
 * it simply never matches, and the reading is quietly poorer. This
 * surfaces that at build time instead.
 */
class AuditRules extends Command
{
    protected $signature = 'jyotish:audit-rules';

    protected $description = 'Report unusable or unattributed interpretation rules';

    public function handle(ConditionMatcher $matcher): int
    {
        $rules = DB::table('interpretation_rules')->get();
        $problems = 0;

        $this->line('');
        $this->info("Auditing {$rules->count()} rules");
        $this->line('');

        foreach ($rules as $rule) {
            if ($rule->conditions === null) {
                continue;
            }

            $decoded = json_decode($rule->conditions, true);

            if (! is_array($decoded)) {
                $this->line("  <fg=red>BAD JSON</> {$rule->condition_type}|{$rule->condition_key}");
                $problems++;

                continue;
            }

            if ($decoded === []) {
                $this->line("  <fg=red>EMPTY</>    {$rule->condition_type}|{$rule->condition_key} has no conditions and can never match");
                $problems++;

                continue;
            }

            if ($unknown = $matcher->unknownKeys($decoded)) {
                $this->line("  <fg=yellow>UNKNOWN</>  {$rule->condition_type}|{$rule->condition_key} uses ".implode(', ', $unknown));
                $problems++;
            }
        }

        $this->line('');
        $this->line('  Provenance breakdown:');

        foreach (DB::table('interpretation_rules')
            ->selectRaw('provenance, count(*) as c')
            ->groupBy('provenance')
            ->orderByDesc('c')
            ->get() as $row) {
            $this->line(sprintf('    %-22s %4d', $row->provenance, $row->c));
        }

        $this->line('');

        if ($problems === 0) {
            $this->info('No unusable rules found.');

            return self::SUCCESS;
        }

        $this->error("{$problems} rule(s) need attention.");

        return self::FAILURE;
    }
}
