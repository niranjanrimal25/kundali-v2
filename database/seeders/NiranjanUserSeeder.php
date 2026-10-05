<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the primary account for this installation.
 *
 * The email is marked verified at creation, so this account bypasses the
 * OTP flow. That is deliberate: a seeded account has no inbox to receive
 * a code, and locking the owner out of their own install at setup time
 * would be a poor trade.
 *
 * Credentials default to the values below but can be overridden from
 * .env, which is the right place for them on a real deployment:
 *
 *   NIRANJAN_NAME, NIRANJAN_EMAIL, NIRANJAN_PASSWORD
 *
 * Re-running is safe: an existing account is updated rather than
 * duplicated, so this doubles as a password reset.
 */
class NiranjanUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('NIRANJAN_EMAIL', 'niranjanjyotish@yopmail.com');
        $name = env('NIRANJAN_NAME', 'Niranjan Jyotish');
        $password = env('NIRANJAN_PASSWORD', 'Shark150@');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        $this->command?->info("User ready: {$user->email} ({$user->name})");
        $this->command?->line('  Email is pre-verified, so no OTP is required to sign in.');
    }
}
