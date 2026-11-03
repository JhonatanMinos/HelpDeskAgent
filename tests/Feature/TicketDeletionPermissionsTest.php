<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('ticket deletion respects membership role and creator', function (TeamRole $role, bool $ownTicket, bool $allowed) {
    $user = User::factory()->create();
    $creator = $ownTicket ? $user : User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => $role->value]);
    if (! $ownTicket) {
        $team->members()->attach($creator, ['role' => TeamRole::Member->value]);
    }

    $ticket = Ticket::factory()->create(['team_id' => $team->id, 'created_by' => $creator->id]);
    $ticket->comments()->create(['user_id' => $creator->id, 'body' => 'Keep this comment.']);
    $ticket->activities()->create(['user_id' => $creator->id, 'type' => 'created']);
    $tables = ['tickets', 'ticket_comments', 'ticket_activities'];
    $before = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->toJson()]);

    $response = $this->actingAs($user)->delete(route('tickets.destroy', [
        'current_team' => $team->slug,
        'ticket' => $ticket->id,
    ]));

    if ($allowed) {
        $response->assertRedirect(route('tickets.index', ['current_team' => $team->slug]));
        $this->assertSoftDeleted('tickets', ['id' => $ticket->id]);
        $this->assertDatabaseHas('ticket_comments', ['ticket_id' => $ticket->id, 'body' => 'Keep this comment.']);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'type' => 'created']);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $ticket->id, 'type' => 'deleted', 'user_id' => $user->id]);
        expect(Ticket::find($ticket->id))->toBeNull();
        expect($team->tickets()->whereKey($ticket->id)->exists())->toBeFalse();
        $parameters = ['current_team' => $team->slug, 'ticket' => $ticket->id];
        $this->get(route('tickets.show', $parameters))->assertNotFound();
        $this->patch(route('tickets.update', $parameters), ['title' => 'Changed'])->assertNotFound();
        $this->post(route('tickets.comments.store', $parameters), ['body' => 'New comment'])->assertNotFound();
        $this->delete(route('tickets.destroy', $parameters))->assertNotFound();
        expect($ticket->activities()->where('type', 'deleted')->count())->toBe(1);
    } else {
        $response->assertForbidden();
        foreach ($tables as $table) {
            expect(DB::table($table)->orderBy('id')->get()->toJson())->toBe($before[$table]);
        }
    }
})->with([
    'member cannot delete another creator ticket' => [TeamRole::Member, false, false],
    'member can delete own ticket' => [TeamRole::Member, true, true],
    'admin can delete another creator ticket' => [TeamRole::Admin, false, true],
    'owner can delete another creator ticket' => [TeamRole::Owner, false, true],
]);

test('ticket deletion still rejects a ticket outside the routed team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $ticket = Ticket::factory()->create();
    $before = $ticket->fresh()->getRawOriginal();

    $this->actingAs($user)->delete(route('tickets.destroy', [
        'current_team' => $team->slug,
        'ticket' => $ticket->id,
    ]))->assertNotFound();

    expect($ticket->fresh()->getRawOriginal())->toBe($before);
});
