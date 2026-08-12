<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_comment_requires_non_empty_body()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $this->actingAs($user);

        $ticket = Ticket::factory()->create(['team_id' => $team->id, 'created_by' => $user->id]);

        $response = $this->post(route('tickets.comments.store', ['current_team' => $team->slug, 'ticket' => $ticket->id]), ['body' => '   ']);

        $response->assertSessionHasErrors('body');
    }

    public function test_store_comment_creates_activity_and_comment()
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        $this->actingAs($user);

        $ticket = Ticket::factory()->create(['team_id' => $team->id, 'created_by' => $user->id]);

        $response = $this->post(route('tickets.comments.store', ['current_team' => $team->slug, 'ticket' => $ticket->id]), ['body' => 'This is a comment.']);

        $response->assertStatus(302);
        $this->assertDatabaseHas('ticket_comments', ['ticket_id' => $ticket->id, 'body' => 'This is a comment.']);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'type' => 'comment']);
    }
}
