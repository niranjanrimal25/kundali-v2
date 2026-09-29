<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        Mail::fake();

        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'Str0ng!Pass')
            ->set('password_confirmation', 'Str0ng!Pass');

        $component->call('register');

        // Registration now ends at email verification, not the dashboard.
        $component->assertRedirect(route('verification.otp'));

        $this->assertGuest();
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }
}
