<?php

namespace App\Services\Astrology\Reading;

/**
 * STEP 4 — Rule evaluation core.
 *
 * Evaluates declarative rule objects against a ChartPayload. The engine
 * knows nothing about astrology; it only resolves fact paths and applies
 * operators. All domain knowledge lives in the JSON rule base.
 *
 * RULE SCHEMA (see resources/rules/*.json)
 * ----------------------------------------
 * {
 *   "id":       "SUN_LAGNESH_MARS_INJURY",   unique, stable
 *   "subject":  "Sun",                        graha the finding is about
 *   "category": "health",                     health|mind|relationships|career
 *   "priority": 90,                           higher sorts first
 *   "source":   "Owner manual, Sun section",
 *   "when": {
 *     "all": [ <condition>, ... ],            every one must hold
 *     "any": [ <condition>, ... ]             at least one must hold
 *   },
 *   "then": {
 *     "title": "Sun as Lagnesh influenced by Mars",
 *     "text":  "Precaution is needed against injury to the head ..."
 *   }
 * }
 *
 * CONDITION SCHEMA
 * ----------------
 * { "fact": "planet.Sun.house", "<operator>": <value> }
 *
 * Operators:
 *   equals            scalar equality
 *   not               scalar inequality
 *   in                value is one of a list
 *   notIn             value is not in a list
 *   includesAny       array fact shares at least one member with a list
 *   includesAll       array fact contains every member of a list
 *   includesNone      array fact shares no member with a list
 *   isTrue / isFalse  boolean fact
 *   lessThan / greaterThan   numeric comparison
 *   countAtLeast      array fact has at least N members
 */
class RuleEngine
{
    public const OPERATORS = [
        'equals', 'not', 'in', 'notIn', 'includesAny', 'includesAll',
        'includesNone', 'isTrue', 'isFalse', 'lessThan', 'greaterThan',
        'countAtLeast',
    ];

    public const CATEGORIES = ['health', 'mind', 'relationships', 'career'];

    /**
     * Run every rule against the payload.
     *
     * @param  list<array>  $rules
     * @return list<array> triggered rules, strongest first
     */
    public function evaluate(array $rules, array $payload): array
    {
        $triggered = [];

        foreach ($rules as $rule) {
            if (! $this->matches($rule['when'] ?? [], $payload)) {
                continue;
            }

            $triggered[] = [
                'id' => $rule['id'],
                'subject' => $rule['subject'] ?? null,
                'category' => $rule['category'] ?? 'health',
                'priority' => $rule['priority'] ?? 50,
                'source' => $rule['source'] ?? null,
                'derived' => $rule['derived'] ?? false,
                'title' => $rule['then']['title'] ?? null,
                'text' => $rule['then']['text'] ?? '',
            ];
        }

        usort($triggered, fn ($a, $b) => $b['priority'] <=> $a['priority']);

        return $triggered;
    }

    /** A rule's `when` block: every `all` must hold, at least one `any`. */
    public function matches(array $when, array $payload): bool
    {
        $all = $when['all'] ?? [];
        $any = $when['any'] ?? [];

        // A rule with no conditions at all - including an empty "all"
        // array - must never fire. Otherwise a half-written rule
        // silently matches every chart ever generated.
        if ($all === [] && $any === []) {
            return false;
        }

        foreach ($all as $condition) {
            if (! $this->test($condition, $payload)) {
                return false;
            }
        }

        if ($any !== []) {
            $satisfied = false;

            foreach ($any as $condition) {
                if ($this->test($condition, $payload)) {
                    $satisfied = true;
                    break;
                }
            }

            if (! $satisfied) {
                return false;
            }
        }

        return true;
    }

    /** A single condition. */
    public function test(array $condition, array $payload): bool
    {
        // Nested groups let a rule express "A and (B or C)".
        if (isset($condition['all']) || isset($condition['any'])) {
            return $this->matches($condition, $payload);
        }

        $path = $condition['fact'] ?? null;

        if ($path === null) {
            return false;
        }

        $actual = ChartPayload::get($payload, $path);

        foreach ($condition as $operator => $expected) {
            if ($operator === 'fact') {
                continue;
            }

            if (! in_array($operator, self::OPERATORS, true)) {
                // Unknown operator: fail closed rather than silently pass.
                return false;
            }

            if (! $this->apply($operator, $actual, $expected)) {
                return false;
            }
        }

        return true;
    }

    private function apply(string $operator, mixed $actual, mixed $expected): bool
    {
        $actualArray = is_array($actual) ? $actual : [];
        $expectedArray = is_array($expected) ? $expected : [$expected];

        return match ($operator) {
            'equals' => $actual === $expected,
            'not' => $actual !== $expected,
            'in' => in_array($actual, $expectedArray, true),
            'notIn' => $actual !== null && ! in_array($actual, $expectedArray, true),
            'includesAny' => array_intersect($actualArray, $expectedArray) !== [],
            'includesAll' => array_diff($expectedArray, $actualArray) === [],
            'includesNone' => array_intersect($actualArray, $expectedArray) === [],
            'isTrue' => $actual === true,
            'isFalse' => $actual === false,
            'lessThan' => is_numeric($actual) && $actual < $expected,
            'greaterThan' => is_numeric($actual) && $actual > $expected,
            'countAtLeast' => count($actualArray) >= (int) $expected,
            default => false,
        };
    }

    /**
     * Static validation of a rule document, used by the seeder and the
     * audit command so a malformed rule is rejected at load time rather
     * than silently never firing.
     *
     * @return list<string> problems found; empty means valid
     */
    public function validate(array $rule): array
    {
        $problems = [];

        foreach (['id', 'when', 'then'] as $required) {
            if (! isset($rule[$required])) {
                $problems[] = "missing '{$required}'";
            }
        }

        if (($rule['then']['text'] ?? '') === '') {
            $problems[] = 'empty output text';
        }

        if (isset($rule['category']) && ! in_array($rule['category'], self::CATEGORIES, true)) {
            $problems[] = "unknown category '{$rule['category']}'";
        }

        $when = $rule['when'] ?? [];

        if (($when['all'] ?? []) === [] && ($when['any'] ?? []) === []) {
            $problems[] = 'no conditions, so it can never fire';
        }

        foreach (array_merge($when['all'] ?? [], $when['any'] ?? []) as $condition) {
            foreach (array_keys($condition) as $key) {
                if ($key === 'fact' || $key === 'all' || $key === 'any') {
                    continue;
                }

                if (! in_array($key, self::OPERATORS, true)) {
                    $problems[] = "unknown operator '{$key}'";
                }
            }
        }

        return $problems;
    }
}
