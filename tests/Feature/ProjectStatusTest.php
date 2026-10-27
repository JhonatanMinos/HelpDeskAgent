<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProjectStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Team $team;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->team = Team::factory()->create();
        $this->team->members()->attach($this->user, ['role' => TeamRole::Member->value]);
        $this->user->switchTeam($this->team);
        $this->project = $this->team->projects()->create([
            'created_by' => $this->user->id,
            'name' => 'Protected project',
            'status' => 'propuesto',
        ]);
    }

    #[DataProvider('allowedStatuses')]
    public function test_team_members_can_set_an_existing_project_status(string $status): void
    {
        $this->actingAs($this->user)
            ->patchJson($this->statusUrl(), ['status' => $status])
            ->assertRedirect(route('dev.projects.show', [
                'current_team' => $this->team->slug,
                'project' => $this->project->id,
            ]))
            ->assertSessionHas('success', 'Estado del proyecto actualizado.');

        $this->assertDatabaseHas('projects', ['id' => $this->project->id, 'status' => $status]);
        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseCount('boards_columns', 0);
        $this->assertDatabaseCount('tasks', 0);
    }

    public static function allowedStatuses(): array
    {
        return [['propuesto'], ['activo'], ['pausado'], ['completado']];
    }

    #[DataProvider('invalidStatuses')]
    public function test_invalid_status_values_are_rejected_without_changing_projects(mixed $status): void
    {
        $before = $this->projectState();

        $this->actingAs($this->user)
            ->patchJson($this->statusUrl(), ['status' => $status])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame($before, $this->projectState());
    }

    public static function invalidStatuses(): array
    {
        return [['active'], ['archivado'], [null], [['activo']]];
    }

    public function test_missing_status_is_rejected_without_changing_projects(): void
    {
        $before = $this->projectState();

        $this->actingAs($this->user)->patchJson($this->statusUrl(), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame($before, $this->projectState());
    }

    #[DataProvider('crossTeamPayloads')]
    public function test_other_team_projects_are_rejected_before_status_validation(mixed $status): void
    {
        $otherTeam = Team::factory()->create();
        $foreignProject = $otherTeam->projects()->create([
            'created_by' => $this->user->id,
            'name' => 'Foreign project',
            'status' => 'pausado',
        ]);
        $before = $this->projectState();

        $this->actingAs($this->user)
            ->patchJson($this->statusUrl($foreignProject), ['status' => $status])
            ->assertNotFound();

        $this->assertSame($before, $this->projectState());
    }

    public static function crossTeamPayloads(): array
    {
        return [['activo'], ['not-a-status'], [null], [['activo']]];
    }

    public function test_nonmembers_cannot_change_project_status(): void
    {
        $outsider = User::factory()->create();
        $before = $this->projectState();

        $this->actingAs($outsider)->patchJson($this->statusUrl(), ['status' => 'activo'])->assertForbidden();

        $this->assertSame($before, $this->projectState());
    }

    public function test_guests_cannot_change_project_status(): void
    {
        $before = $this->projectState();

        $this->patch($this->statusUrl(), ['status' => 'activo'])->assertRedirect('/login');

        $this->assertSame($before, $this->projectState());
    }

    public function test_json_guests_receive_unauthorized_without_changing_data(): void
    {
        $before = $this->projectState();

        $this->patchJson($this->statusUrl(), ['status' => 'activo'])->assertUnauthorized();

        $this->assertSame($before, $this->projectState());
    }

    public function test_status_updates_preserve_existing_columns_and_tasks(): void
    {
        $column = $this->project->columns()->create(['name' => 'Existing column', 'order' => 7]);
        $task = $this->project->task()->create([
            'boards_column_id' => $column->id,
            'title' => 'Existing task',
            'priority' => 'media',
            'order' => 5,
        ]);
        $columnBefore = $column->fresh()->getAttributes();
        $taskBefore = $task->fresh()->getAttributes();

        $this->actingAs($this->user)->patchJson($this->statusUrl(), ['status' => 'completado'])->assertRedirect();

        $this->assertSame($columnBefore, $column->fresh()->getAttributes());
        $this->assertSame($taskBefore, $task->fresh()->getAttributes());
        $this->assertDatabaseCount('boards_columns', 1);
        $this->assertDatabaseCount('tasks', 1);
    }

    public function test_the_request_cannot_modify_other_project_attributes(): void
    {
        $before = $this->project->fresh()->getAttributes();
        $otherTeam = Team::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($this->user)->patchJson($this->statusUrl(), [
            'status' => 'activo',
            'name' => 'Unexpected rename',
            'team_id' => $otherTeam->id,
            'created_by' => $otherUser->id,
            'Initial_requirements' => 'Unexpected requirements',
        ])->assertRedirect();

        $after = $this->project->fresh()->getAttributes();
        $this->assertSame('activo', $after['status']);
        unset($before['status'], $before['updated_at'], $after['status'], $after['updated_at']);
        $this->assertSame($before, $after);
    }

    private function statusUrl(?Project $project = null): string
    {
        return route('dev.projects.update-status', [
            'current_team' => $this->team->slug,
            'project' => ($project ?? $this->project)->id,
        ]);
    }

    private function projectState(): string
    {
        $state = [];

        foreach (DB::select("SELECT name FROM sqlite_master WHERE type = 'table' ORDER BY name") as $table) {
            $state[$table->name] = DB::table($table->name)->get()->toArray();
        }

        return hash('sha256', serialize($state));
    }
}
