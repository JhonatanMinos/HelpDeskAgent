<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class KanbanRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_creation_is_denied_for_a_project_outside_the_route_team(): void
    {
        [$user, $team] = $this->teamWithOwner();
        $foreignTeam = Team::factory()->create();
        $foreignProject = $this->projectFor($foreignTeam, $user);
        $column = $this->columnFor($foreignProject);

        $response = $this->actingAs($user)->postJson(
            route('dev.tasks.store', ['current_team' => $team->slug, 'project' => $foreignProject->id]),
            $this->taskPayload($column),
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('tasks', ['title' => 'Protected task']);
    }

    public function test_task_creation_rejects_a_column_from_another_project(): void
    {
        [$user, $team] = $this->teamWithOwner();
        $project = $this->projectFor($team, $user);
        $otherProject = $this->projectFor($team, $user);
        $foreignColumn = $this->columnFor($otherProject);

        $response = $this->actingAs($user)->postJson(
            route('dev.tasks.store', ['current_team' => $team->slug, 'project' => $project->id]),
            $this->taskPayload($foreignColumn),
        );

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('tasks', ['title' => 'Protected task']);
    }

    public function test_task_creation_still_works_for_a_column_in_the_project(): void
    {
        [$user, $team] = $this->teamWithOwner();
        $project = $this->projectFor($team, $user);
        $column = $this->columnFor($project);

        $response = $this->actingAs($user)->postJson(
            route('dev.tasks.store', ['current_team' => $team->slug, 'project' => $project->id]),
            $this->taskPayload($column),
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'boards_column_id' => $column->id,
            'title' => 'Protected task',
        ]);
    }

    public function test_task_creation_persists_the_optional_fields_sent_by_the_board(): void
    {
        [$user, $team] = $this->teamWithOwner();
        $project = $this->projectFor($team, $user);
        $column = $this->columnFor($project);

        $response = $this->actingAs($user)->postJson(
            route('dev.tasks.store', ['current_team' => $team->slug, 'project' => $project->id]),
            [
                ...$this->taskPayload($column),
                'description' => 'Detalle de la tarea',
                'due_date' => '2026-12-31',
                'assigned_to' => $user->id,
            ],
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'description' => 'Detalle de la tarea',
            'assigned_to' => $user->id,
        ]);

        $task = Task::where('project_id', $project->id)->sole();
        $this->assertSame('2026-12-31', $task->limit_date?->toDateString());
    }

    public function test_task_creation_rejects_an_assignee_outside_the_route_team(): void
    {
        [$user, $team] = $this->teamWithOwner();
        $project = $this->projectFor($team, $user);
        $column = $this->columnFor($project);
        $foreignUser = User::factory()->create();

        $response = $this->actingAs($user)->postJson(
            route('dev.tasks.store', ['current_team' => $team->slug, 'project' => $project->id]),
            [...$this->taskPayload($column), 'assigned_to' => $foreignUser->id],
        );

        $response->assertUnprocessable();
        $this->assertDatabaseMissing('tasks', ['title' => 'Protected task']);
    }

    public function test_column_creation_is_denied_for_a_project_outside_the_route_team(): void
    {
        [$user, $team] = $this->teamWithOwner();
        $foreignTeam = Team::factory()->create();
        $foreignProject = $this->projectFor($foreignTeam, $user);

        $response = $this->actingAs($user)->postJson(
            route('dev.columns.store', ['current_team' => $team->slug, 'project' => $foreignProject->id]),
            ['name' => 'Unauthorized column'],
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('boards_columns', ['name' => 'Unauthorized column']);
    }

    public function test_column_creation_still_works_for_a_project_in_the_route_team(): void
    {
        [$user, $team] = $this->teamWithOwner();
        $project = $this->projectFor($team, $user);

        $response = $this->actingAs($user)->postJson(
            route('dev.columns.store', ['current_team' => $team->slug, 'project' => $project->id]),
            ['name' => 'Authorized column'],
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('boards_columns', [
            'project_id' => $project->id,
            'name' => 'Authorized column',
        ]);
    }

    public function test_task_move_is_denied_for_a_task_outside_the_route_team(): void
    {
        [$user, $team] = $this->teamWithOwner();
        $foreignTeam = Team::factory()->create();
        $foreignProject = $this->projectFor($foreignTeam, $user);
        $column = $this->columnFor($foreignProject);
        $task = $this->taskFor($foreignProject, $column, 'Foreign task');

        $response = $this->actingAs($user)->patchJson(
            route('dev.tasks.move', ['current_team' => $team->slug, 'task' => $task->id]),
            ['boards_column_id' => $column->id, 'ordered_task_ids' => [$task->id]],
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'boards_column_id' => $column->id,
            'order' => 0,
        ]);
    }

    public function test_task_move_rejects_a_destination_column_from_another_project(): void
    {
        [$user, $team] = $this->teamWithOwner();
        $project = $this->projectFor($team, $user);
        $column = $this->columnFor($project);
        $task = $this->taskFor($project, $column, 'Protected task');
        $otherProject = $this->projectFor($team, $user);
        $foreignColumn = $this->columnFor($otherProject);

        $response = $this->actingAs($user)->patchJson(
            route('dev.tasks.move', ['current_team' => $team->slug, 'task' => $task->id]),
            ['boards_column_id' => $foreignColumn->id, 'ordered_task_ids' => [$task->id]],
        );

        $response->assertUnprocessable();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'boards_column_id' => $column->id,
            'order' => 0,
        ]);
    }

    public function test_task_move_rejects_reordering_a_task_from_another_project(): void
    {
        [$user, $team] = $this->teamWithOwner();
        $project = $this->projectFor($team, $user);
        $column = $this->columnFor($project);
        $task = $this->taskFor($project, $column, 'Protected task');
        $otherProject = $this->projectFor($team, $user);
        $otherColumn = $this->columnFor($otherProject);
        $foreignTask = $this->taskFor($otherProject, $otherColumn, 'Other project task');

        $response = $this->actingAs($user)->patchJson(
            route('dev.tasks.move', ['current_team' => $team->slug, 'task' => $task->id]),
            ['boards_column_id' => $column->id, 'ordered_task_ids' => [$task->id, $foreignTask->id]],
        );

        $response->assertUnprocessable();
        $this->assertDatabaseHas('tasks', [
            'id' => $foreignTask->id,
            'boards_column_id' => $otherColumn->id,
            'order' => 0,
        ]);
    }

    public function test_task_move_reorders_tasks_within_the_destination_column(): void
    {
        [$user, $team] = $this->teamWithOwner();
        $project = $this->projectFor($team, $user);
        $sourceColumn = $this->columnFor($project, 'Backlog');
        $destinationColumn = $this->columnFor($project, 'In progress');
        $task = $this->taskFor($project, $sourceColumn, 'Moved task');
        $sibling = $this->taskFor($project, $destinationColumn, 'Destination sibling');

        $response = $this->actingAs($user)->patchJson(
            route('dev.tasks.move', ['current_team' => $team->slug, 'task' => $task->id]),
            ['boards_column_id' => $destinationColumn->id, 'ordered_task_ids' => [$task->id, $sibling->id]],
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'boards_column_id' => $destinationColumn->id, 'order' => 0]);
        $this->assertDatabaseHas('tasks', ['id' => $sibling->id, 'boards_column_id' => $destinationColumn->id, 'order' => 1]);
    }

    public function test_new_task_is_visible_on_its_board_before_the_archive_migration(): void
    {
        $migration = require database_path('migrations/2026_10_29_000001_add_archived_at_to_tasks_table.php');
        $migration->down();
        try {
            $this->assertCreatedTaskAppearsOnBoard(false);
        } finally {
            $migration->up();
        }
    }

    public function test_new_task_is_visible_on_its_board_after_the_archive_migration(): void
    {
        $this->assertCreatedTaskAppearsOnBoard(true);
    }

    private function assertCreatedTaskAppearsOnBoard(bool $archivingAvailable): void
    {
        [$user, $team] = $this->teamWithOwner();
        $project = $this->projectFor($team, $user);
        $column = $this->columnFor($project);
        $board = route('dev.projects.show', ['current_team' => $team->slug, 'project' => $project->id]);
        $this->actingAs($user)->from($board)->post(
            route('dev.tasks.store', ['current_team' => $team->slug, 'project' => $project->id]),
            [...$this->taskPayload($column), 'description' => '', 'due_date' => '', 'assigned_to' => ''],
        )->assertRedirect($board);
        $this->assertDatabaseHas('tasks', ['title' => 'Protected task', 'boards_column_id' => $column->id]);
        $this->get($board)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('projects/show')->has('columns.0.tasks', 1)
            ->where('columns.0.tasks.0.title', 'Protected task')->has('archivedTasks', 0)
            ->where('taskArchivingAvailable', $archivingAvailable));

        $task = Task::where('project_id', $project->id)->firstOrFail();
        $destination = $this->columnFor($project, 'Doing');
        $this->patchJson(route('dev.tasks.move', ['current_team' => $team->slug, 'task' => $task->id]), [
            'boards_column_id' => $destination->id, 'ordered_task_ids' => [$task->id],
        ])->assertRedirect();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'boards_column_id' => $destination->id]);
        if (! $archivingAvailable) {
            foreach (['archive', 'restore'] as $action) {
                $before = $task->fresh()->getAttributes();
                $this->patchJson(route('dev.tasks.'.$action, ['current_team' => $team->slug, 'task' => $task->id]))
                    ->assertUnprocessable()->assertJsonValidationErrors('task');
                $this->assertSame($before, $task->fresh()->getAttributes());
            }
        }
    }

    /** @return array{User, Team} */
    private function teamWithOwner(): array
    {
        $user = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($user, ['role' => TeamRole::Owner->value]);

        return [$user, $team];
    }

    private function projectFor(Team $team, User $creator): Project
    {
        return Project::create([
            'team_id' => $team->id,
            'created_by' => $creator->id,
            'name' => 'Project '.uniqid(),
        ]);
    }

    private function columnFor(Project $project, string $name = 'Backlog'): BoardColumn
    {
        return BoardColumn::create([
            'project_id' => $project->id,
            'name' => $name,
            'order' => 0,
        ]);
    }

    private function taskFor(Project $project, BoardColumn $column, string $title): Task
    {
        return Task::create([
            'project_id' => $project->id,
            'boards_column_id' => $column->id,
            'title' => $title,
            'priority' => 'media',
            'order' => 0,
        ]);
    }

    private function taskPayload(BoardColumn $column): array
    {
        return [
            'boards_column_id' => $column->id,
            'title' => 'Protected task',
            'priority' => 'media',
        ];
    }
}
