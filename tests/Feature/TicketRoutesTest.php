<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_ticket_routes()
    {
        $team = Team::factory()->create();

        $this->get(route('tickets.index', ['current_team' => $team->slug]))
            ->assertRedirect('/login');
    }

    public function test_index_shows_tickets_for_team()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $this->actingAs($user);

        Ticket::factory()->create(['team_id' => $team->id]);

        $response = $this->get(route('tickets.index', ['current_team' => $team->slug]));

        $response->assertStatus(200);
        // Inertia renders the component name in the page payload (may be HTML-escaped)
        // The component name appears escaped in the JSON payload (tickets\/index)
        $response->assertSee('tickets\\/index');
    }

    public function test_store_creates_ticket_and_activity()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $this->actingAs($user);

        $payload = [
            'title' => 'Test ticket',
            'description' => 'Testing',
            'category' => 'red',
            'priority' => 'media',
        ];

        $response = $this->postJson(route('tickets.store', ['current_team' => $team->slug]), $payload);

        $response->assertStatus(302); // redirect to show

        $this->assertDatabaseHas('tickets', ['title' => 'Test ticket', 'team_id' => $team->id]);
        $this->assertDatabaseHas('ticket_activities', ['type' => 'created']);
    }

    public function test_show_returns_ticket_detail()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $this->actingAs($user);

        $ticket = Ticket::factory()->create(['team_id' => $team->id, 'created_by' => $user->id]);

        $response = $this->get(route('tickets.show', ['current_team' => $team->slug, 'ticket' => $ticket->id]));

        $response->assertStatus(200);
        $response->assertSeeText($ticket->title);
    }

    public function test_update_changes_ticket_and_logs_activity()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $this->actingAs($user);

        $ticket = Ticket::factory()->create(['team_id' => $team->id, 'created_by' => $user->id]);

        $payload = ['status' => 'resuelto'];

        $response = $this->patch(route('tickets.update', ['current_team' => $team->slug, 'ticket' => $ticket->id]), $payload);

        $response->assertStatus(302);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'resuelto']);
        $this->assertDatabaseHas('ticket_activities', ['type' => 'updated', 'field' => 'status']);
    }

    public function test_destroy_deletes_ticket()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $this->actingAs($user);

        $ticket = Ticket::factory()->create(['team_id' => $team->id, 'created_by' => $user->id]);

        $response = $this->delete(route('tickets.destroy', ['current_team' => $team->slug, 'ticket' => $ticket->id]));

        $response->assertStatus(302);
        $this->assertSoftDeleted('tickets', ['id' => $ticket->id]);
    }
}
