<?php

use App\Enums\TeamRole;
use App\Http\Requests\Tickets\StoreTicketRequest;
use App\Http\Requests\Tickets\UpdateTicketRequest;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

function ticketValidationDatabaseState(): string
{
    $state = [];

    foreach (DB::select("SELECT name FROM sqlite_master WHERE type = 'table' ORDER BY name") as $table) {
        $state[$table->name] = DB::table($table->name)->get()->toArray();
    }

    return hash('sha256', serialize($state));
}

test('ticket assignments stay inside the current team', function (string $operation, bool $sameTeam) {
    $team = Team::factory()->create();
    $actor = User::factory()->create();
    $assignee = User::factory()->create();
    $team->members()->attach($actor, ['role' => TeamRole::Owner->value]);
    $actor->switchTeam($team);
    $assigneeTeam = $sameTeam ? $team : Team::factory()->create();
    $assigneeTeam->members()->attach($assignee, ['role' => TeamRole::Member->value]);
    $ticket = Ticket::factory()->create(['team_id' => $team->id, 'created_by' => $actor->id]);
    $payload = ['title' => 'Scoped assignment', 'category' => 'red', 'priority' => 'media', 'assigned_to' => $assignee->id];
    $before = ticketValidationDatabaseState();

    $this->actingAs($actor);
    $response = $operation === 'store'
        ? $this->postJson(route('tickets.store', ['current_team' => $team->slug]), $payload)
        : $this->patchJson(route('tickets.update', ['current_team' => $team->slug, 'ticket' => $ticket->id]), $payload);

    if (! $sameTeam) {
        $response->assertUnprocessable()->assertJsonValidationErrors('assigned_to');
        expect(ticketValidationDatabaseState())->toBe($before);

        return;
    }

    $response->assertRedirect();
    $saved = $operation === 'store' ? Ticket::where('title', 'Scoped assignment')->sole() : $ticket->fresh();
    expect($saved->assigned_to)->toBe($assignee->id)
        ->and($saved->team_id)->toBe($team->id);
    $this->assertDatabaseHas('ticket_activities', [
        'ticket_id' => $saved->id,
        'type' => $operation === 'store' ? 'created' : 'updated',
    ]);
})->with(['create' => 'store', 'update' => 'update'])->with(['same team' => true, 'foreign team' => false]);

test('ticket validation fails closed without a bound team model', function (string $requestClass, ?string $teamParameter) {
    $before = ticketValidationDatabaseState();
    Route::post('/__ticket-validation/{current_team?}', function () use ($requestClass) {
        app($requestClass)->validated();

        return response()->noContent();
    });

    $this->postJson('/__ticket-validation'.($teamParameter === null ? '' : '/'.$teamParameter), [
        'title' => 'Unbound team', 'category' => 'red', 'priority' => 'media',
    ])->assertNotFound();
    expect(ticketValidationDatabaseState())->toBe($before);
})->with(['create' => [StoreTicketRequest::class], 'update' => [UpdateTicketRequest::class]])->with(['missing' => null, 'unbound slug' => 'unbound-team']);

test('public bound ticket routes preserve relation validation and persistence', function () {
    $team = Team::factory()->create();
    $foreignTeam = Team::factory()->create();
    $actor = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    $team->members()->attach($actor, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $foreignTeam->members()->attach($outsider, ['role' => TeamRole::Member->value]);
    $actor->switchTeam($team);
    $makeStore = fn ($owner, $code) => $owner->stores()->create(['store_code' => $code, 'name' => $code, 'company' => 16, 'base_ip' => '192.168.1.100', 'address' => '1 Main Street', 'neighborhood' => 'Center', 'city' => 'City', 'postal_code' => '12345', 'state' => 'State']);
    $store = $makeStore($team, 'LOCAL');
    $foreignStore = $makeStore($foreignTeam, 'FOREIGN');
    $project = $team->projects()->create(['created_by' => $actor->id, 'name' => 'Local project']);
    $foreignProject = $foreignTeam->projects()->create(['created_by' => $outsider->id, 'name' => 'Foreign project']);
    $base = ['title' => 'Bound route ticket', 'category' => 'red', 'priority' => 'media', 'assigned_to' => $member->id, 'store_id' => $store->id, 'project_id' => $project->id];
    $storeUrl = route('tickets.store', ['current_team' => $team->slug]);
    $this->actingAs($actor);
    $response = $this->postJson($storeUrl, $base);
    $response->assertRedirect();
    $ticket = Ticket::where('title', 'Bound route ticket')->sole();
    $this->assertSame($member->id, $ticket->assigned_to);
    $this->assertSame($store->id, $ticket->store_id);
    $this->assertSame($project->id, $ticket->project_id);
    $updateUrl = route('tickets.update', ['current_team' => $team->slug, 'ticket' => $ticket->id]);
    foreach ([null, $member->id] as $assignee) {
        $response = $this->patchJson($updateUrl, ['assigned_to' => $assignee]);
        $response->assertRedirect();
        $this->assertSame($assignee, $ticket->fresh()->assigned_to);
    }
    $response = $this->postJson($storeUrl, [...$base, 'title' => 'Nullable assignee', 'assigned_to' => null]);
    $response->assertRedirect();
    $this->assertNull(Ticket::where('title', 'Nullable assignee')->sole()->assigned_to);
    // Nullable relation clearing is existing behavior, not a new stricter policy.
    $response = $this->patchJson($updateUrl, ['store_id' => null, 'project_id' => null]);
    $response->assertRedirect();
    $this->assertNull($ticket->fresh()->store_id);
    $this->assertNull($ticket->fresh()->project_id);
    $response = $this->patchJson($updateUrl, $base);
    $response->assertRedirect();
    $this->assertSame($store->id, $ticket->fresh()->store_id);
    $this->assertSame($project->id, $ticket->fresh()->project_id);
    foreach (['store_id' => $foreignStore->id, 'project_id' => $foreignProject->id, 'assigned_to' => $outsider->id] as $field => $value) {
        foreach (['create', 'update'] as $operation) {
            $before = ticketValidationDatabaseState();
            $payload = [...$base, $field => $value];
            $response = $operation === 'create' ? $this->postJson($storeUrl, $payload) : $this->patchJson($updateUrl, $payload);
            $response->assertUnprocessable()->assertJsonValidationErrors($field);
            $this->assertSame($before, ticketValidationDatabaseState());
        }
    }
    $this->app['auth']->forgetGuards();
    foreach (['create', 'update'] as $operation) {
        $before = ticketValidationDatabaseState();
        $response = $operation === 'create' ? $this->postJson($storeUrl, $base) : $this->patchJson($updateUrl, $base);
        $response->assertUnauthorized();
        $this->assertSame($before, ticketValidationDatabaseState());
    }
    $this->actingAs($outsider);
    foreach (['create', 'update'] as $operation) {
        $before = ticketValidationDatabaseState();
        $response = $operation === 'create' ? $this->postJson($storeUrl, $base) : $this->patchJson($updateUrl,$base);
        $response->assertForbidden();
        $this->assertSame($before,ticketValidationDatabaseState());
    }
});
