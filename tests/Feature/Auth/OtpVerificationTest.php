<?php

namespace Tests\Feature\Auth;

use App\Mail\EmailVerificationCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Services\Auth\EmailOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function registering_does_not_log_the_user_in(): void
    {
        Mail::fake();

        Volt::test('pages.auth.register')
            ->set('name', 'Niranjan')
            ->set('email', 'new@example.com')
            ->set('password', 'Str0ng!Pass')
            ->set('password_confirmation', 'Str0ng!Pass')
            ->call('register')
            ->assertRedirect(route('verification.otp'));

        $this->assertGuest();

        $user = User::where('email', 'new@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at, 'Must start unverified');

        Mail::assertSent(EmailVerificationCodeMail::class);
        $this->assertDatabaseCount('email_verification_codes', 1);
    }

    #[Test]
    public function the_code_is_hashed_at_rest(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();

        $code = app(EmailOtpService::class)->issue($user);

        $record = EmailVerificationCode::first();

        $this->assertNotSame($code, $record->code_hash);
        $this->assertTrue(Hash::check($code, $record->code_hash));
    }

    #[Test]
    public function a_correct_code_verifies_and_logs_in(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        $code = app(EmailOtpService::class)->issue($user);

        session()->put(EmailOtpService::sessionKey(), $user->id);

        Volt::test('pages.auth.verify-otp')
            ->set('code', $code)
            ->call('verify')
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function a_wrong_code_does_not_authenticate(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        app(EmailOtpService::class)->issue($user);

        session()->put(EmailOtpService::sessionKey(), $user->id);

        Volt::test('pages.auth.verify-otp')
            ->set('code', '000000')
            ->call('verify')
            ->assertSet('error', fn ($e) => $e !== null);

        $this->assertGuest();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function a_code_cannot_be_brute_forced(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        $code = app(EmailOtpService::class)->issue($user);

        $service = app(EmailOtpService::class);

        for ($i = 0; $i < EmailOtpService::MAX_ATTEMPTS; $i++) {
            $service->verify($user, '111111');
        }

        // Even the CORRECT code must now fail; the record is burnt.
        $result = $service->verify($user, $code);

        $this->assertFalse($result['ok']);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function an_expired_code_is_rejected(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        $code = app(EmailOtpService::class)->issue($user);

        EmailVerificationCode::first()->update([
            'expires_at' => now()->subMinute(),
        ]);

        $result = app(EmailOtpService::class)->verify($user, $code);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('expired', $result['error']);
    }

    #[Test]
    public function issuing_a_new_code_invalidates_the_previous_one(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();

        $service = app(EmailOtpService::class);
        $first = $service->issue($user);
        $second = $service->issue($user);

        $this->assertFalse($service->verify($user, $first)['ok'], 'Old code must stop working');

        $this->assertTrue($service->verify($user, $second)['ok']);
    }

    #[Test]
    public function an_unverified_user_cannot_log_in_and_is_sent_to_verification(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'password' => Hash::make('Str0ng!Pass'),
        ]);

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'Str0ng!Pass')
            ->call('login')
            ->assertRedirect(route('verification.otp'));

        $this->assertGuest();
        Mail::assertSent(EmailVerificationCodeMail::class);
    }

    #[Test]
    public function a_verified_user_logs_in_normally(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Str0ng!Pass'),
        ]);

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'Str0ng!Pass')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function the_verification_page_is_unreachable_without_a_pending_registration(): void
    {
        Volt::test('pages.auth.verify-otp')
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function resending_is_rate_limited(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        app(EmailOtpService::class)->issue($user);

        session()->put(EmailOtpService::sessionKey(), $user->id);

        Volt::test('pages.auth.verify-otp')
            ->call('resend')
            ->assertSet('error', fn ($e) => str_contains((string) $e, 'wait'));
    }
}
