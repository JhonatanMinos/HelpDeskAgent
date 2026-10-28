<?php

use App\Enums\TeamRole;
use App\Models\Store;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function dashboardStore(Team $team, string $status): Store
{
    return Store::create([
        'team_id' => $team->id, 'store_code' => fake()->unique()->numerify('S#####'),
        'name' => 'Branch '.$status, 'company' => 'Example', 'base_ip' => '192.168.1.100',
        'status' => $status, 'address' => 'Main street', 'neighborhood' => 'Center',
        'city' => 'Example City', 'state' => 'Example State', 'postal_code' => '12345',
        'last_seen_at' => $status === 'unknown' ? null : now()->subMinutes(5),
    ]);
}

test('dashboard summarizes only the route team and preserves invitations', function () {
    $user = User::factory()->create();
    $foreignTeam = $user->currentTeam;
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    dashboardStore($team, 'online');
    $offline = dashboardStore($team, 'offline');
    $unknown = dashboardStore($team, 'unknown');
    dashboardStore($team, 'offline')->delete();
    dashboardStore($foreignTeam, 'offline');

    foreach (['abierto', 'en_proceso', 'esperando_cliente', 'resuelto', 'cerrado'] as $status) {
        $ticket = Ticket::create([
            'team_id' => $team->id, 'created_by' => $user->id,
            'title' => 'Local '.$status, 'status' => $status,
        ]);
    }
    TicketActivity::create(['ticket_id' => $ticket->id, 'user_id' => $user->id, 'type' => 'created']);
    $foreignTicket = Ticket::create([
        'team_id' => $foreignTeam->id, 'created_by' => $user->id, 'title' => 'Foreign secret',
    ]);
    TicketActivity::create(['ticket_id' => $foreignTicket->id, 'user_id' => $user->id, 'type' => 'created']);

    $this->actingAs($user)->get(route('dashboard', ['current_team' => $team->slug]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')->has('pendingInvitations')
        ->where('overview.branches', ['total' => 3, 'online' => 1, 'offline' => 1, 'unknown' => 1])
        ->has('overview.attention', 2)
        ->where('overview.attention.0.id', $offline->id)
        ->where('overview.attention.0.lastCheckedAt', $offline->last_seen_at->toISOString())
        ->where('overview.attention.1.id', $unknown->id)
        ->where('overview.attention.1.lastCheckedAt', null)
        ->where('overview.tickets', ['abierto' => 1, 'en_proceso' => 1, 'esperando_cliente' => 1, 'resuelto' => 1, 'cerrado' => 1])
        ->has('overview.activity', 1)
        ->where('overview.activity.0.ticketTitle', 'Local cerrado'));
});

test('dashboard returns honest zero and empty states', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard', ['current_team' => $user->currentTeam->slug]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('overview.branches', ['total' => 0, 'online' => 0, 'offline' => 0, 'unknown' => 0])
        ->where('overview.tickets', ['abierto' => 0, 'en_proceso' => 0, 'esperando_cliente' => 0, 'resuelto' => 0, 'cerrado' => 0])
        ->has('overview.attention', 0)->has('overview.activity', 0));
});

test('dashboard bounds attention and activity lists with deterministic ordering', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $stores = collect(range(1, 8))->map(fn () => dashboardStore($team, 'offline'));
    $ticket = Ticket::create(['team_id' => $team->id, 'created_by' => $user->id, 'title' => 'Local ticket']);
    $events = collect(range(1, 8))->map(fn () => TicketActivity::create([
        'ticket_id' => $ticket->id, 'user_id' => $user->id, 'type' => 'created',
    ]));

    $this->actingAs($user)->get(route('dashboard', ['current_team' => $team->slug]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('overview.branches.total', 8)->has('overview.attention', 6)
        ->where('overview.attention.0.id', $stores->first()->id)
        ->has('overview.activity', 6)->where('overview.activity.0.id', $events->last()->id));
});

test('dashboard still denies access to another teams overview', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $this->actingAs($user)->get(route('dashboard', ['current_team' => $team->slug]))->assertForbidden();
});

test('dashboard preserves forbidden response for a missing team', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard', ['current_team' => 'missing-team']))->assertForbidden();
});
