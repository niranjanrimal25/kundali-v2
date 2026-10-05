<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Single admin account — this app is single-user by design.
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@kundali.test')],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            NiranjanUserSeeder::class,
            CitySeeder::class,
            InterpretationRuleSeeder::class,
        ]);
    }
}
