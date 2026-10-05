<?php

namespace Tests\Feature;

use App\Models\User;
use App\Rules\StrongPassword;
use Database\Seeders\NiranjanUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NiranjanUserSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_the_account_ready_to_sign_in(): void
    {
        $this->seed(NiranjanUserSeeder::class);

        $user = User::where('email', 'niranjanjyotish@yopmail.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Niranjan Jyotish', $user->name);

        // Seeded accounts have no inbox, so they must not need an OTP.
        $this->assertTrue($user->hasVerifiedEmail());

        $this->assertTrue(Auth::validate([
            'email' => 'niranjanjyotish@yopmail.com',
            'password' => 'Shark150@',
        ]));
    }

    #[Test]
    public function the_seeded_password_satisfies_the_applications_own_policy(): void
    {
        // A seeded password the app itself would reject on registration
        // would be an embarrassing inconsistency.
        $validator = Validator::make(
            ['password' => 'Shark150@'],
            ['password' => new StrongPassword]
        );

        $this->assertFalse($validator->fails());
        $this->assertNotContains(false, StrongPassword::check('Shark150@'));
    }

    #[Test]
    public function the_password_is_hashed_not_stored_in_plain_text(): void
    {
        $this->seed(NiranjanUserSeeder::class);

        $user = User::where('email', 'niranjanjyotish@yopmail.com')->first();

        $this->assertNotSame('Shark150@', $user->password);
        $this->assertStringStartsWith('$2y$', $user->password);
    }

    #[Test]
    public function reseeding_updates_rather_than_duplicating(): void
    {
        $this->seed(NiranjanUserSeeder::class);
        $this->seed(NiranjanUserSeeder::class);

        $this->assertSame(1, User::where('email', 'niranjanjyotish@yopmail.com')->count());
    }

    #[Test]
    public function credentials_can_be_overridden_from_the_environment(): void
    {
        putenv('NIRANJAN_EMAIL=other@example.com');
        putenv('NIRANJAN_NAME=Someone Else');

        $this->seed(NiranjanUserSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'other@example.com', 'name' => 'Someone Else']);

        putenv('NIRANJAN_EMAIL');
        putenv('NIRANJAN_NAME');
    }
}
