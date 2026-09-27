<?php

namespace Tests\Feature;

use App\Livewire\Kundali\EditKundali;
use App\Models\Kundali;
use App\Models\User;
use App\Services\Astrology\KundaliService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EditKundaliTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function kundali(array $overrides = []): Kundali
    {
        return Kundali::create(array_merge([
            'user_id' => $this->user->id,
            'name' => 'Demo Chart',
            'birth_date' => '1990-05-15',
            'birth_time' => '10:30:00',
            'birth_place' => 'Pokhara, Nepal',
            'latitude' => 28.26689,
            'longitude' => 83.96851,
            'timezone' => 'Asia/Kathmandu',
        ], $overrides));
    }

    #[Test]
    public function the_edit_form_loads_the_existing_values(): void
    {
        $kundali = $this->kundali();

        Livewire::actingAs($this->user)
            ->test(EditKundali::class, ['kundali' => $kundali])
            ->assertSet('name', 'Demo Chart')
            ->assertSet('birth_date', '1990-05-15')
            ->assertSet('birth_time', '10:30')
            ->assertSet('timezone', 'Asia/Kathmandu')
            ->assertSet('birth_place', 'Pokhara, Nepal');
    }

    #[Test]
    public function a_user_cannot_edit_another_users_kundali(): void
    {
        $kundali = $this->kundali(['user_id' => User::factory()->create()->id]);

        $this->actingAs($this->user)
            ->get(route('kundalis.edit', $kundali))
            ->assertForbidden();
    }

    #[Test]
    public function guests_cannot_reach_the_edit_page(): void
    {
        $this->get(route('kundalis.edit', $this->kundali()))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function renaming_does_not_discard_the_cached_chart(): void
    {
        $kundali = $this->kundali();

        // Force the chart to be computed and stored.
        app(KundaliService::class)->facts($kundali, true);
        $this->assertDatabaseCount('chart_data', 1);

        Livewire::actingAs($this->user)
            ->test(EditKundali::class, ['kundali' => $kundali])
            ->set('name', 'Renamed Only')
            ->call('save');

        $this->assertSame('Renamed Only', $kundali->fresh()->name);

        // A name change cannot alter the astronomy, so recomputing would
        // be pure waste.
        $this->assertDatabaseCount('chart_data', 1);
    }

    #[Test]
    public function changing_the_birth_time_invalidates_the_cached_chart_and_reading(): void
    {
        $kundali = $this->kundali();

        $before = app(KundaliService::class)->facts($kundali, true);
        $this->assertDatabaseCount('chart_data', 1);

        Livewire::actingAs($this->user)
            ->test(EditKundali::class, ['kundali' => $kundali])
            ->set('birth_time', '18:45')
            ->call('save');

        // The stale chart must be gone, not silently served.
        $this->assertDatabaseCount('chart_data', 0);
        $this->assertDatabaseCount('readings', 0);

        $after = app(KundaliService::class)->facts($kundali->fresh(), true);

        $this->assertNotEqualsWithDelta(
            $before['lagna']['longitude'],
            $after['lagna']['longitude'],
            0.001,
            'An eight-hour shift must move the Lagna'
        );
    }

    #[Test]
    public function the_form_warns_before_saving_when_the_chart_will_change(): void
    {
        $kundali = $this->kundali();

        Livewire::actingAs($this->user)
            ->test(EditKundali::class, ['kundali' => $kundali])
            ->assertSet('name', 'Demo Chart')
            ->call('$refresh')
            ->assertDontSee('This edit changes the chart')
            ->set('birth_time', '04:15')
            ->assertSee('This edit changes the chart');
    }

    #[Test]
    public function it_validates_on_edit(): void
    {
        Livewire::actingAs($this->user)
            ->test(EditKundali::class, ['kundali' => $this->kundali()])
            ->set('name', '')
            ->set('birth_time', 'not-a-time')
            ->call('save')
            ->assertHasErrors(['name', 'birth_time']);
    }

    #[Test]
    public function it_deletes_the_kundali_and_its_derived_data(): void
    {
        $kundali = $this->kundali();
        app(KundaliService::class)->facts($kundali, true);

        Livewire::actingAs($this->user)
            ->test(EditKundali::class, ['kundali' => $kundali])
            ->call('delete')
            ->assertRedirect(route('kundalis.index'));

        $this->assertDatabaseCount('kundalis', 0);
        $this->assertDatabaseCount('chart_data', 0);
    }

    #[Test]
    public function the_chart_page_links_to_the_edit_page(): void
    {
        $kundali = $this->kundali();

        $this->actingAs($this->user)
            ->get(route('kundalis.show', $kundali))
            ->assertOk()
            ->assertSee('Edit details')
            ->assertSee(route('kundalis.edit', $kundali), false);
    }
}
