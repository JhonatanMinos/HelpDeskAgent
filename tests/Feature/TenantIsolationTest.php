<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Jobs\RefreshStoreStatusJob;
use App\Models\Store;
use App\Models\Team;
use App\Models\User;
use App\Services\NetworkDiagnosticService;
use App\Services\StoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_refresh_command_preserves_feedback_and_dispatches_all_stores(): void
    {
        $firstStore = $this->createStore(Team::factory()->create(), 'First store');
        $secondStore = $this->createStore(Team::factory()->create(), 'Second store');
        Queue::fake();

        $this->artisan('branches:refresh-status')
            ->expectsOutputToContain('Refresh de status despachado para todas las tiendas.')
            ->assertExitCode(0);

        Queue::assertPushed(RefreshStoreStatusJob::class, 2);
        Queue::assertPushed(RefreshStoreStatusJob::class, fn ($job) => $job->store->is($firstStore));
        Queue::assertPushed(RefreshStoreStatusJob::class, fn ($job) => $job->store->is($secondStore));
    }

    public function test_rejected_cross_team_requests_preserve_database_state_and_dispatch_nothing(): void
    {
        [$user, $team] = $this->userAndTeam();
        $user->switchTeam($team);
        $foreignTeam = Team::factory()->create();
        $store = $this->createStore($foreignTeam, 'Private branch');
        $project = $foreignTeam->projects()->create(['created_by' => $user->id, 'name' => 'Private project']);
        Queue::fake();
        $diagnostics = Mockery::mock(NetworkDiagnosticService::class);
        $diagnostics->shouldNotReceive('pingStore');
        $this->app->instance(NetworkDiagnosticService::class, $diagnostics);
        $this->actingAs($user);

        $requests = [
            ['GET', route('dev.projects.show', ['current_team' => $team->slug, 'project' => $project->id])],
            ['GET', route('branches.show', ['current_team' => $team->slug, 'branch' => $store->id])],
            ['POST', route('branches.ping', ['current_team' => $team->slug, 'store' => $store->id])],
        ];

        foreach ($requests as [$method, $uri]) {
            $before = $this->databaseStateHash();
            $this->call($method, $uri)->assertNotFound();
            $this->assertSame($before, $this->databaseStateHash());
            Queue::assertNothingPushed();
        }
    }

    private function databaseStateHash(): string
    {
        $state = [];

        foreach (DB::select("SELECT name FROM sqlite_master WHERE type = 'table' ORDER BY name") as $table) {
            $state[$table->name] = DB::table($table->name)->get()->toArray();
        }

        return hash('sha256', serialize($state));
    }

    public function test_project_detail_is_not_available_across_teams(): void
    {
        [$user, $team] = $this->userAndTeam();
        $otherTeam = Team::factory()->create();
        $project = $otherTeam->projects()->create([
            'created_by' => $user->id,
            'name' => 'Private project',
        ]);

        $this->actingAs($user)
            ->get(route('dev.projects.show', ['current_team' => $team->slug, 'project' => $project->id]))
            ->assertNotFound();
    }

    public function test_project_detail_remains_available_to_its_team(): void
    {
        [$user, $team] = $this->userAndTeam();
        $project = $team->projects()->create([
            'created_by' => $user->id,
            'name' => 'Local project',
        ]);

        $this->actingAs($user)
            ->get(route('dev.projects.show', ['current_team' => $team->slug, 'project' => $project->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('projects/show')
                ->where('project.id', $project->id)
                ->where('project.name', 'Local project'));
    }

    public function test_store_detail_is_not_available_across_teams(): void
    {
        [$user, $team] = $this->userAndTeam();
        $store = $this->createStore(Team::factory()->create(), 'Foreign store');

        $this->actingAs($user)
            ->get(route('branches.show', ['current_team' => $team->slug, 'branch' => $store->id]))
            ->assertNotFound();
    }

    public function test_store_detail_and_ping_remain_available_to_its_team(): void
    {
        [$user, $team] = $this->userAndTeam();
        $store = $this->createStore($team, 'Local store');
        $diagnostics = Mockery::mock(NetworkDiagnosticService::class);
        $diagnostics->shouldReceive('pingStore')->once()->with(Mockery::on(
            fn (Store $actual) => $actual->is($store),
        ))->andReturn([]);
        $this->app->instance(NetworkDiagnosticService::class, $diagnostics);
        $store->update(['status' => 'online']);

        $this->actingAs($user)
            ->get(route('branches.show', ['current_team' => $team->slug, 'branch' => $store->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('branches/show')
                ->where('store.id', $store->id));

        $this->actingAs($user)
            ->post(route('branches.ping', ['current_team' => $team->slug, 'store' => $store->id]))
            ->assertRedirect()
            ->assertSessionHas('success', 'La tienda Local store está en línea.');
    }

    public function test_store_ping_and_print_diagnostics_reject_other_teams_before_side_effects(): void
    {
        [$user, $team] = $this->userAndTeam();
        $store = $this->createStore(Team::factory()->create(), 'Foreign store');
        $diagnostics = Mockery::mock(NetworkDiagnosticService::class);
        $diagnostics->shouldNotReceive('pingStore');
        $diagnostics->shouldNotReceive('checkPrintChain');
        $this->app->instance(NetworkDiagnosticService::class, $diagnostics);

        $this->actingAs($user)
            ->post(route('branches.ping', ['current_team' => $team->slug, 'store' => $store->id]))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('branches.show', ['current_team' => $team->slug, 'branch' => $store->id]))
            ->assertNotFound();
        $this->assertDatabaseCount('ping_logs', 0);
        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'status' => 'unknown',
            'last_seen_at' => null,
        ]);
    }

    public function test_store_index_and_dashboard_are_scoped_to_the_route_team(): void
    {
        [$user, $team] = $this->userAndTeam();
        $localStore = $this->createStore($team, 'Local store');
        $this->createStore(Team::factory()->create(), 'Foreign store');

        $this->actingAs($user)
            ->get(route('branches.index', ['current_team' => $team->slug]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('branches/stores')
                ->where('dashboard.total', 1)
                ->has('stores.data', 1)
                ->where('stores.data.0.id', $localStore->id));
    }

    public function test_store_search_does_not_leak_other_team_name_matches(): void
    {
        [$user, $team] = $this->userAndTeam();
        $this->createStore($team, 'Local Alpha');
        $this->createStore(Team::factory()->create(), 'Private Store');
        $this->actingAs($user);

        $this->get(route('branches.index', ['current_team' => $team->slug, 'search' => 'Private']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('branches/stores')
                ->has('stores.data', 0));
    }

    public function test_store_search_scopes_store_code_matches_and_preserves_local_search(): void
    {
        [$user, $team] = $this->userAndTeam();
        $localStore = $this->createStore($team, 'Local Alpha');
        $foreignStore = $this->createStore(Team::factory()->create(), 'Private Store');
        $foreignStore->update(['store_code' => 'FOREIGN-SECRET']);
        $this->actingAs($user);

        $this->get(route('branches.index', ['current_team' => $team->slug, 'search' => 'SECRET']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('branches/stores')
                ->has('stores.data', 0));

        $this->get(route('branches.index', ['current_team' => $team->slug, 'search' => 'Alpha']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('branches/stores')
                ->has('stores.data', 1)
                ->where('stores.data.0.id', $localStore->id));
    }

    public function test_refresh_status_dispatches_jobs_only_for_the_route_team(): void
    {
        [$user, $team] = $this->userAndTeam();
        $localStore = $this->createStore($team, 'Local store');
        $this->createStore(Team::factory()->create(), 'Foreign store');
        Queue::fake();

        $this->actingAs($user)
            ->post(route('branches.refresh-status', ['current_team' => $team->slug]))
            ->assertRedirect();

        Queue::assertPushed(RefreshStoreStatusJob::class, fn ($job) => $job->store->is($localStore));
        Queue::assertPushed(RefreshStoreStatusJob::class, 1);
    }

    public function test_global_store_refresh_without_team_still_dispatches_across_teams(): void
    {
        [$user, $team] = $this->userAndTeam();
        $this->createStore($team, 'Local store');
        $this->createStore(Team::factory()->create(), 'Foreign store');
        Queue::fake();

        app(StoreService::class)->refreshStatusNow();

        Queue::assertPushed(RefreshStoreStatusJob::class, 2);
    }

    public function test_ticket_store_options_are_limited_to_the_route_team(): void
    {
        [$user, $team] = $this->userAndTeam();
        $localStore = $this->createStore($team, 'Local store');
        $this->createStore(Team::factory()->create(), 'Foreign store');

        $this->actingAs($user)
            ->get(route('tickets.index', ['current_team' => $team->slug]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('tickets/index')
                ->has('stores', 1)
                ->where('stores.0.id', $localStore->id));
    }

    /** @return array{User, Team} */
    private function userAndTeam(): array
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        return [$user, $team];
    }

    private function createStore(Team $team, string $name): Store
    {
        return $team->stores()->create([
            'store_code' => fake()->unique()->bothify('S####'),
            'name' => $name,
            'company' => 16,
            'base_ip' => '192.168.1.100',
            'address' => '1 Main Street',
            'neighborhood' => 'Center',
            'city' => 'Example City',
            'postal_code' => '12345',
            'state' => 'Example State',
        ]);
    }
}
