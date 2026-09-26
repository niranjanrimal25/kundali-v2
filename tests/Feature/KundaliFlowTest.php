<?php

namespace Tests\Feature;

use App\Livewire\Kundali\CreateKundali;
use App\Livewire\Kundali\KundaliIndex;
use App\Livewire\Kundali\ShowKundali;
use App\Models\City;
use App\Models\Kundali;
use App\Models\User;
use App\Services\Astrology\KundaliService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KundaliFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        City::create([
            'geoname_id' => 1282898,
            'name' => 'Pokhara',
            'ascii_name' => 'Pokhara',
            'country_code' => 'NP',
            'admin1' => '4',
            'latitude' => 28.26689,
            'longitude' => 83.96851,
            'timezone' => 'Asia/Kathmandu',
            'population' => 600051,
        ]);
    }

    #[Test]
    public function guests_cannot_reach_the_app(): void
    {
        $this->get(route('kundalis.index'))->assertRedirect(route('login'));
        $this->get(route('kundalis.create'))->assertRedirect(route('login'));
    }

    #[Test]
    public function the_place_autocomplete_returns_matches(): void
    {
        Livewire::actingAs($this->user)
            ->test(CreateKundali::class)
            ->set('place_query', 'Pokh')
            ->assertSee('Pokhara')
            ->assertSee('Asia/Kathmandu');
    }

    #[Test]
    public function selecting_a_place_fills_coordinates_and_timezone(): void
    {
        $city = City::first();

        Livewire::actingAs($this->user)
            ->test(CreateKundali::class)
            ->call('selectPlace', $city->id)
            ->assertSet('latitude', 28.26689)
            ->assertSet('longitude', 83.96851)
            ->assertSet('timezone', 'Asia/Kathmandu')
            ->assertSet('place_selected', true);
    }

    #[Test]
    public function the_offset_preview_warns_about_pre_1986_nepali_births(): void
    {
        $city = City::first();

        Livewire::actingAs($this->user)
            ->test(CreateKundali::class)
            ->call('selectPlace', $city->id)
            ->set('birth_date', '1980-07-20')
            ->set('birth_time', '10:30')
            ->assertSee('+05:30')
            ->assertSee('Historical offset applied');
    }

    #[Test]
    public function it_validates_required_fields(): void
    {
        Livewire::actingAs($this->user)
            ->test(CreateKundali::class)
            ->call('save')
            ->assertHasErrors(['name', 'birth_date', 'birth_time', 'birth_place']);
    }

    #[Test]
    public function it_creates_a_kundali_and_redirects_to_the_chart(): void
    {
        $city = City::first();

        Livewire::actingAs($this->user)
            ->test(CreateKundali::class)
            ->set('name', 'Test Subject')
            ->set('gender', 'male')
            ->set('birth_date', '1990-05-15')
            ->set('birth_time', '10:30')
            ->call('selectPlace', $city->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('kundalis', [
            'name' => 'Test Subject',
            'user_id' => $this->user->id,
            'timezone' => 'Asia/Kathmandu',
        ]);
    }

    #[Test]
    public function the_chart_page_computes_and_caches_the_chart_data(): void
    {
        $kundali = $this->makeKundali();

        $this->assertDatabaseCount('chart_data', 0);

        Livewire::actingAs($this->user)
            ->test(ShowKundali::class, ['kundali' => $kundali])
            ->assertOk()
            ->assertSee('Planetary Positions')
            ->assertSee('The Twelve Bhavas')
            ->assertSee('View Full Details of this Kundali');

        // Chart facts must be persisted after first view.
        $this->assertDatabaseCount('chart_data', 1);
        $this->assertNotNull($kundali->fresh()->chartData->facts['lagna']['sign_name']);
    }

    #[Test]
    public function the_chart_renders_in_both_styles(): void
    {
        $kundali = $this->makeKundali();

        $component = Livewire::actingAs($this->user)
            ->test(ShowKundali::class, ['kundali' => $kundali]);

        // Default must be North Indian per the agreed spec.
        $component->assertSet('style', 'north');
        $north = $component->call('setStyle', 'north')->get('style');
        $this->assertSame('north', $north);

        $component->call('setStyle', 'south')
            ->assertSet('style', 'south')
            ->assertSee('Lagna', false);
    }

    #[Test]
    public function it_toggles_between_the_rashi_and_navamsa_charts(): void
    {
        $kundali = $this->makeKundali();

        Livewire::actingAs($this->user)
            ->test(ShowKundali::class, ['kundali' => $kundali])
            ->assertSet('variant', 'D1')
            ->assertSee('Rashi Chart (D1)')
            ->call('setVariant', 'D9')
            ->assertSee('Navamsa (D9)');
    }

    #[Test]
    public function a_user_cannot_view_another_users_kundali(): void
    {
        $other = User::factory()->create();
        $kundali = $this->makeKundali($other);

        Livewire::actingAs($this->user)
            ->test(ShowKundali::class, ['kundali' => $kundali])
            ->assertForbidden();
    }

    #[Test]
    public function the_index_lists_and_searches_saved_kundalis(): void
    {
        $this->makeKundali(name: 'Sita Sharma');
        $this->makeKundali(name: 'Ram Thapa');

        Livewire::actingAs($this->user)
            ->test(KundaliIndex::class)
            ->assertSee('Sita Sharma')
            ->assertSee('Ram Thapa')
            ->set('search', 'Sita')
            ->assertSee('Sita Sharma')
            ->assertDontSee('Ram Thapa');
    }

    #[Test]
    public function it_deletes_a_kundali_and_its_derived_data(): void
    {
        $kundali = $this->makeKundali();

        // Force chart computation so there is cached data to cascade.
        app(KundaliService::class)->facts($kundali);
        $this->assertDatabaseCount('chart_data', 1);

        Livewire::actingAs($this->user)
            ->test(KundaliIndex::class)
            ->call('delete', $kundali->id);

        $this->assertDatabaseCount('kundalis', 0);
        $this->assertDatabaseCount('chart_data', 0);
    }

    private function makeKundali(?User $owner = null, string $name = 'Test Subject'): Kundali
    {
        return Kundali::create([
            'user_id' => ($owner ?? $this->user)->id,
            'name' => $name,
            'gender' => 'male',
            'birth_date' => '1990-05-15',
            'birth_time' => '10:30',
            'birth_place' => 'Pokhara, NP',
            'latitude' => 28.26689,
            'longitude' => 83.96851,
            'timezone' => 'Asia/Kathmandu',
        ]);
    }
}
