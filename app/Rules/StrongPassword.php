<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Password policy for this application.
 *
 * Kept as a single rule object rather than inline strings so registration,
 * password reset and the profile password change can never drift apart —
 * a mismatch there is how "strong password" requirements quietly stop
 * being enforced on one of the three screens.
 *
 * Each failing requirement is reported separately, so the user is told
 * everything that is wrong at once instead of fixing one rule per attempt.
 */
class StrongPassword implements ValidationRule
{
    public function __construct(
        private readonly int $minimum = 8,
    ) {}

    /** The rules, as the UI should display them. */
    public static function requirements(): array
    {
        return [
            'length' => 'At least 8 characters',
            'uppercase' => 'One uppercase letter (A-Z)',
            'lowercase' => 'One lowercase letter (a-z)',
            'number' => 'One number (0-9)',
            'special' => 'One special character (!@#$%&*...)',
        ];
    }

    /**
     * Evaluate a candidate password against each requirement.
     *
     * @return array<string, bool> keyed as requirements()
     */
    public static function check(string $password, int $minimum = 8): array
    {
        return [
            'length' => mb_strlen($password) >= $minimum,
            'uppercase' => preg_match('/[A-Z]/', $password) === 1,
            'lowercase' => preg_match('/[a-z]/', $password) === 1,
            'number' => preg_match('/[0-9]/', $password) === 1,
            // Anything that is not a letter, digit or whitespace.
            'special' => preg_match('/[^A-Za-z0-9\s]/', $password) === 1,
        ];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        $results = self::check($value, $this->minimum);

        $messages = [
            'length' => "The :attribute must be at least {$this->minimum} characters.",
            'uppercase' => 'The :attribute must contain at least one uppercase letter.',
            'lowercase' => 'The :attribute must contain at least one lowercase letter.',
            'number' => 'The :attribute must contain at least one number.',
            'special' => 'The :attribute must contain at least one special character.',
        ];

        foreach ($results as $key => $passed) {
            if (! $passed) {
                $fail($messages[$key]);
            }
        }
    }
}
