<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TaskActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Team $team;

    private Project $project;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->team = Team::factory()->create();
        $this->team->members()->attach($this->user, ['role' => TeamRole::Member->value]);
        $this->user->switchTeam($this->team);
        $this->project = $this->team->projects()->create(['created_by' => $this->user->id, 'name' => 'Local project']);
        $this->project->seedDefaultColumns();
        $this->task = $this->project->task()->create([
            'boards_column_id' => $this->project->columns()->first()->id,
            'title' => 'Original task', 'description' => 'Keep details', 'priority' => 'media',
            'assigned_to' => $this->user->id, 'limit_date' => '2020-01-01', 'order' => 3,
        ]);
    }

    public function test_members_can_edit_metadata_without_changing_project_column_order_or_archive_state(): void
    {
        $before = $this->task->fresh()->getAttributes();
        $this->actingAs($this->user)->patchJson($this->url(), [
            ...$this->payload(), 'title' => 'Edited task', 'description' => 'Updated details', 'priority' => 'alta',
            'project_id' => 999, 'boards_column_id' => 999, 'order' => 100, 'archived_at' => now()->toISOString(),
        ])->assertRedirect($this->boardUrl())->assertSessionHas('success', 'Tarea actualizada.');
        $after = $this->task->fresh()->getAttributes();
        $this->assertSame('Edited task', $after['title']);
        $this->assertSame('Updated details', $after['description']);
        $this->assertSame('alta', $after['priority']);
        foreach (['project_id', 'boards_column_id', 'order', 'archived_at', 'assigned_to', 'limit_date'] as $field) {
            $this->assertSame($before[$field], $after[$field]);
        }
    }

    public function test_optional_fields_can_be_cleared_and_a_teammate_can_be_assigned(): void
    {
        $member = User::factory()->create();
        $this->team->members()->attach($member, ['role' => TeamRole::Member->value]);
        $this->actingAs($this->user)->patchJson($this->url(), [
            ...$this->payload(), 'description' => null, 'due_date' => null, 'assigned_to' => $member->id,
        ])->assertRedirect($this->boardUrl());
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'description' => null, 'limit_date' => null, 'assigned_to' => $member->id]);
        $this->patchJson($this->url(), [...$this->payload(), 'assigned_to' => null])->assertRedirect($this->boardUrl());
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'assigned_to' => null]);
    }

    #[DataProvider('invalidMetadata')]
    public function test_invalid_edits_leave_all_task_data_unchanged(array $values, string $field): void
    {
        $before = $this->state();
        $this->actingAs($this->user)->patchJson($this->url(), [...$this->payload(), ...$values])
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertSame($before, $this->state());
    }

    public static function invalidMetadata(): array
    {
        return [
            [['title' => ''], 'title'],
            [['title' => str_repeat('x', 256)], 'title'],
            [['description' => ['invalid']], 'description'],
            [['priority' => 'urgent'], 'priority'],
            [['due_date' => 'invalid'], 'due_date'],
            [['assigned_to' => 999999], 'assigned_to'],
        ];
    }

    public function test_assignees_from_other_teams_are_rejected_without_writes(): void
    {
        $outsider = User::factory()->create();
        $before = $this->state();
        $this->actingAs($this->user)->patchJson($this->url(), [...$this->payload(), 'assigned_to' => $outsider->id])
            ->assertUnprocessable()->assertJsonValidationErrors('assigned_to');
        $this->assertSame($before, $this->state());
    }

    public function test_archive_hides_a_task_without_deleting_it_and_restore_returns_it_to_its_column(): void
    {
        $before = $this->task->fresh()->getAttributes();
        $this->actingAs($this->user)->patchJson($this->url('archive'))
            ->assertRedirect($this->boardUrl())->assertSessionHas('success', 'Tarea archivada.');
        $this->assertNotNull($this->task->fresh()->archived_at);
        $this->get($this->boardUrl())->assertInertia(fn (Assert $page) => $page
            ->component('projects/show')->has('columns.0.tasks', 0)->has('archivedTasks', 1)
            ->where('archivedTasks.0.id', $this->task->id));
        $archived = $this->task->fresh()->getAttributes();
        unset($archived['archived_at'], $archived['updated_at']);
        $expected = $before;
        unset($expected['archived_at'], $expected['updated_at']);
        $this->assertSame($expected, $archived);
        $this->patchJson($this->url('restore'))->assertRedirect($this->boardUrl())->assertSessionHas('success', 'Tarea restaurada.');
        $this->assertNull($this->task->fresh()->archived_at);
        $this->get($this->boardUrl())->assertInertia(fn (Assert $page) => $page
            ->has('columns.0.tasks', 1)->where('columns.0.tasks.0.id', $this->task->id)->has('archivedTasks', 0));
        foreach (['project_id', 'boards_column_id', 'order', 'title', 'description', 'priority', 'limit_date', 'assigned_to'] as $field) {
            $this->assertSame($before[$field], $this->task->fresh()->getAttributes()[$field]);
        }
    }

    public function test_archive_and_restore_are_idempotent(): void
    {
        $this->actingAs($this->user)->patchJson($this->url('archive'))->assertRedirect();
        $before = $this->state();
        $this->travel(2)->minutes();
        $this->patchJson($this->url('archive'))->assertRedirect();
        $this->assertSame($before, $this->state());
        $this->patchJson($this->url('restore'))->assertRedirect();
        $before = $this->state();
        $this->travel(2)->minutes();
        $this->patchJson($this->url('restore'))->assertRedirect();
        $this->assertSame($before, $this->state());
    }

    public function test_archived_tasks_cannot_be_moved_or_included_in_active_task_reordering(): void
    {
        $active = $this->project->task()->create(['boards_column_id' => $this->task->boards_column_id, 'title' => 'Active task']);
        $this->actingAs($this->user)->patchJson($this->url('archive'))->assertRedirect();
        $before = $this->state();
        $this->patchJson($this->url('move'), ['boards_column_id' => $this->task->boards_column_id, 'ordered_task_ids' => [$this->task->id]])->assertNotFound();
        $this->patchJson($this->url('move', $active), ['boards_column_id' => $active->boards_column_id, 'ordered_task_ids' => [$active->id, $this->task->id]])->assertUnprocessable();
        $this->assertSame($before, $this->state());
    }

    #[DataProvider('archiveStates')]
    public function test_deletion_removes_only_the_selected_task(bool $archived): void
    {
        $other = $this->project->task()->create(['boards_column_id' => $this->task->boards_column_id, 'title' => 'Keep task', 'order' => 5]);
        $otherBefore = $other->fresh()->getAttributes();
        $this->actingAs($this->user);
        if ($archived) {
            $this->patchJson($this->url('archive'))->assertRedirect();
        }
        $this->deleteJson($this->url())->assertRedirect($this->boardUrl())->assertSessionHas('success', 'Tarea eliminada.');
        $this->assertDatabaseMissing('tasks', ['id' => $this->task->id]);
        $this->assertSame($otherBefore, $other->fresh()->getAttributes());
        $this->assertDatabaseHas('projects', ['id' => $this->project->id]);
        $this->assertDatabaseCount('boards_columns', 4);
    }

    public static function archiveStates(): array
    {
        return [[false], [true]];
    }

    public function test_all_actions_reject_foreign_tasks_nonmembers_and_guests_without_writes(): void
    {
        $foreign = Team::factory()->create()->projects()->create(['created_by' => $this->user->id, 'name' => 'Foreign']);
        $column = $foreign->columns()->create(['name' => 'Private']);
        $task = $foreign->task()->create(['boards_column_id' => $column->id, 'title' => 'Private task']);
        $before = $this->state();
        foreach ([['PATCH', ''], ['PATCH', 'archive'], ['PATCH', 'restore'], ['DELETE', '']] as [$method, $suffix]) {
            $this->json($method, $this->url($suffix), [])->assertUnauthorized();
            $this->actingAs($this->user)->json($method, $this->url($suffix, $task), [])->assertNotFound();
            $this->actingAs(User::factory()->create())->json($method, $this->url($suffix), [])->assertForbidden();
            auth()->forgetGuards();
        }
        $this->assertSame($before, $this->state());
    }

    private function payload(): array
    {
        return ['title' => 'Original task', 'description' => 'Keep details', 'priority' => 'media', 'due_date' => '2020-01-01', 'assigned_to' => $this->user->id];
    }

    private function url(string $suffix = '', ?Task $task = null): string
    {
        return '/'.$this->team->slug.'/dev/tasks/'.($task ?? $this->task)->id.($suffix ? '/'.$suffix : '');
    }

    private function boardUrl(): string
    {
        return '/'.$this->team->slug.'/dev/projects/'.$this->project->id;
    }

    private function state(): string
    {
        return DB::table('tasks')->orderBy('id')->get()->toJson();
    }
}
