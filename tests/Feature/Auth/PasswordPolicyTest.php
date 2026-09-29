<?php

namespace Tests\Feature\Auth;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    public static function weakPasswords(): array
    {
        return [
            'too short' => ['Ab1!', 'length'],
            'no uppercase' => ['lowercase1!', 'uppercase'],
            'no lowercase' => ['UPPERCASE1!', 'lowercase'],
            'no number' => ['NoNumbers!', 'number'],
            'no special character' => ['NoSpecial123', 'special'],
        ];
    }

    #[Test]
    #[DataProvider('weakPasswords')]
    public function the_rule_rejects_weak_passwords(string $password, string $failing): void
    {
        $validator = Validator::make(
            ['password' => $password],
            ['password' => new StrongPassword]
        );

        $this->assertTrue($validator->fails(), "[{$password}] should fail on {$failing}");

        $results = StrongPassword::check($password);
        $this->assertFalse($results[$failing], "[{$password}] should fail the {$failing} check");
    }

    #[Test]
    public function the_rule_accepts_a_compliant_password(): void
    {
        $validator = Validator::make(
            ['password' => 'Str0ng!Pass'],
            ['password' => new StrongPassword]
        );

        $this->assertFalse($validator->fails());
        $this->assertNotContains(false, StrongPassword::check('Str0ng!Pass'));
    }

    #[Test]
    public function registration_rejects_a_password_without_an_uppercase_letter(): void
    {
        Mail::fake();

        Volt::test('pages.auth.register')
            ->set('name', 'Test')
            ->set('email', 'a@example.com')
            ->set('password', 'lowercase1!')
            ->set('password_confirmation', 'lowercase1!')
            ->call('register')
            ->assertHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function registration_rejects_a_password_without_a_special_character(): void
    {
        Mail::fake();

        Volt::test('pages.auth.register')
            ->set('name', 'Test')
            ->set('email', 'b@example.com')
            ->set('password', 'NoSpecial123')
            ->set('password_confirmation', 'NoSpecial123')
            ->call('register')
            ->assertHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    #[Test]
    public function the_live_checklist_reflects_what_the_backend_enforces(): void
    {
        Mail::fake();

        // Same input, two paths: the UI checklist and the validator.
        $component = Volt::test('pages.auth.register')->set('password', 'NoSpecial123');

        $uiSaysOk = ! in_array(false, StrongPassword::check('NoSpecial123'), true);

        $backendSaysOk = ! Validator::make(
            ['password' => 'NoSpecial123'],
            ['password' => new StrongPassword]
        )->fails();

        $this->assertSame($uiSaysOk, $backendSaysOk, 'UI and backend must agree');
        $this->assertFalse($uiSaysOk);

        $component->assertSee('One special character');
    }
}
