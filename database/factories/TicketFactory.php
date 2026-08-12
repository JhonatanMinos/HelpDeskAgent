<?php

namespace Database\Factories;

use App\Enums\TeamRole;
use App\Models\Ticket;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        $creator = User::factory()->create();
        $team = Team::factory()->create();

        // ensure creator is a member of team
        $team->members()->attach($creator, ['role' => TeamRole::Owner->value]);

        return [
            'team_id' => $team->id,
            'created_by' => $creator->id,
            'assigned_to' => null,
            'store_id' => null,
            'project_id' => null,
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(),
            'category' => 'red',
            'priority' => 'media',
            'status' => 'abierto',
            'due_date' => null,
            'resolved_at' => null,
        ];
    }
}
