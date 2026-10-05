<?php

namespace App\Console\Commands;

use App\Services\Astrology\Reading\RuleBase;
use App\Services\Astrology\Reading\RuleEngine;
use Illuminate\Console\Command;

/**
 * Validates the JSON rule base.
 *
 * A malformed rule fails silently at runtime - it simply never matches -
 * so the corpus is checked explicitly instead.
 */
class AuditRuleBase extends Command
{
    protected $signature = 'jyotish:audit-rules';

    protected $description = 'Validate the JSON rule base';

    public function handle(RuleBase $base, RuleEngine $engine): int
    {
        $rules = $base->rules();
        $problems = 0;
        $ids = [];
        $byCategory = [];

        $this->line('');
        $this->info('Auditing '.count($rules).' rules from '.implode(', ', $base->files()));
        $this->line('');

        foreach ($rules as $rule) {
            $id = $rule['id'] ?? '(no id)';

            foreach ($engine->validate($rule) as $problem) {
                $this->line("  <fg=red>INVALID</>  {$id}: {$problem}");
                $problems++;
            }

            if (isset($ids[$id])) {
                $this->line("  <fg=red>DUPLICATE</> {$id}");
                $problems++;
            }

            $ids[$id] = true;
            $byCategory[$rule['category'] ?? 'uncategorised'] ??= 0;
            $byCategory[$rule['category'] ?? 'uncategorised']++;
        }

        $this->line('  By category:');
        foreach ($byCategory as $category => $count) {
            $this->line(sprintf('    %-16s %4d', $category, $count));
        }

        $this->line('  Karakatwa entries: '.count($base->karakatwa()));
        $this->line('');

        if ($problems === 0) {
            $this->info('Rule base is valid.');

            return self::SUCCESS;
        }

        $this->error("{$problems} problem(s) found.");

        return self::FAILURE;
    }
}
