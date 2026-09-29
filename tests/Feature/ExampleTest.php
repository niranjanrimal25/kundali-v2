<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_the_landing_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Cast your chart');
    }

    public function test_signed_in_users_are_sent_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_the_dashboard_renders_for_a_signed_in_user(): void
    {
        $user = User::factory()->create(['name' => 'Niranjan']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Namaste, Niranjan')
            ->assertSee('New Kundali');
    }
}
