<?php

namespace Database\Factories;

use App\Enums\TeamRole;
use App\Models\TicketComment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketComment>
 */
class TicketCommentFactory extends Factory
{
    protected $model = TicketComment::class;

    public function definition(): array
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create();

        // ensure user member of ticket team
        $ticket->team->members()->attach($user, ['role' => TeamRole::Member->value]);

        return [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => $this->faker->paragraph(),
        ];
    }
}
