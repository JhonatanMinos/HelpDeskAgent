<?php

use App\Enums\TeamRole;
use App\Models\Store;
use App\Models\Team;
use App\Models\TelegramConversation;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Inertia\Testing\AssertableInertia as Assert;

test('telegram conversations expose their store relationship', function () {
    $relationship = (new TelegramConversation)->store();

    expect($relationship)->toBeInstanceOf(BelongsTo::class)
        ->and($relationship->getRelated())->toBeInstanceOf(Store::class)
        ->and($relationship->getForeignKeyName())->toBe('store_id');
});

test('ticket detail preserves date casts and nested relationship fields', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $ticket = Ticket::factory()->create([
        'team_id' => $team->id,
        'created_by' => $user->id,
        'assigned_to' => $user->id,
        'due_date' => '2026-10-29',
        'resolved_at' => '2026-10-28 12:30:00',
    ]);
    $ticket->comments()->create(['user_id' => $user->id, 'body' => 'Contract regression']);
    $ticket->activities()->create(['user_id' => $user->id, 'type' => 'created']);

    $this->actingAs($user)
        ->get(route('tickets.show', ['current_team' => $team->slug, 'ticket' => $ticket->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tickets/show')
            ->where('ticket.due_date', '2026-10-29')
            ->where('ticket.resolved_at', '2026-10-28 12:30:00')
            ->where('ticket.creator.id', $user->id)
            ->where('ticket.assignee.name', $user->name)
            ->where('ticket.comments.0.body', 'Contract regression')
            ->where('ticket.comments.0.user.id', $user->id)
            ->where('ticket.activities.0.type', 'created')
            ->where('ticket.activities.0.user.id', $user->id));
});
