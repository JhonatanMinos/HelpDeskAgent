<?php

namespace Tests\Feature;

use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProjectActionsTest extends TestCase
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
            'name' => 'Existing project',
            'Initial_requirements' => 'Original requirements',
            'estimated_date_delivery' => '2020-01-01',
            'status' => 'pausado',
        ]);
        $this->project->seedDefaultColumns();
        $this->project->task()->create([
            'boards_column_id' => $this->project->columns()->first()->id,
            'title' => 'Existing task',
        ]);
    }

    public function test_members_can_edit_metadata_without_changing_status_ownership_or_board(): void
    {
        $board = $this->boardState();
        $this->actingAs($this->user)->patchJson($this->url(), [
            'name' => 'Renamed project',
            'requirements' => 'Updated requirements',
            'due_date' => '2020-01-01',
            'status' => 'completado',
            'team_id' => 999,
            'created_by' => 999,
        ])->assertRedirect($this->indexUrl())
            ->assertSessionHas('success', 'Proyecto actualizado.');

        $this->assertDatabaseHas('projects', [
            'id' => $this->project->id,
            'name' => 'Renamed project',
            'Initial_requirements' => 'Updated requirements',
            'status' => 'pausado',
            'team_id' => $this->team->id,
            'created_by' => $this->user->id,
        ]);
        $this->assertSame('2020-01-01', $this->project->fresh()->estimated_date_delivery->toDateString());
        $this->assertSame($board, $this->boardState());
    }

    public function test_optional_metadata_can_be_cleared(): void
    {
        $this->actingAs($this->user)->patchJson($this->url(), [
            'name' => 'Existing project', 'requirements' => null, 'due_date' => null,
        ])->assertRedirect($this->indexUrl());
        $this->assertDatabaseHas('projects', [
            'id' => $this->project->id, 'Initial_requirements' => null, 'estimated_date_delivery' => null,
        ]);
    }

    #[DataProvider('invalidMetadata')]
    public function test_invalid_metadata_is_rejected_without_writes(array $payload, string $field): void
    {
        $before = $this->state();
        $this->actingAs($this->user)->patchJson($this->url(), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertSame($before, $this->state());
    }

    public static function invalidMetadata(): array
    {
        return [
            [[], 'name'],
            [['name' => ''], 'name'],
            [['name' => str_repeat('x', 256)], 'name'],
            [['name' => 'Valid', 'requirements' => ['invalid']], 'requirements'],
            [['name' => 'Valid', 'due_date' => 'not-a-date'], 'due_date'],
        ];
    }

    public function test_deletion_removes_only_its_board_and_preserves_linked_tickets(): void
    {
        $other = $this->team->projects()->create(['created_by' => $this->user->id, 'name' => 'Keep']);
        $other->seedDefaultColumns();
        $otherTask = $other->task()->create([
            'boards_column_id' => $other->columns()->first()->id, 'title' => 'Keep task',
        ]);
        $ticket = Ticket::factory()->create([
            'team_id' => $this->team->id, 'created_by' => $this->user->id, 'project_id' => $this->project->id,
        ]);
        $ticketBefore = $ticket->fresh()->getAttributes();
        $otherBefore = $other->fresh()->getAttributes();
        $taskBefore = $otherTask->fresh()->getAttributes();
        $this->actingAs($this->user)->deleteJson($this->url())
            ->assertRedirect($this->indexUrl())->assertSessionHas('success', 'Proyecto eliminado.');
        $this->assertDatabaseMissing('projects', ['id' => $this->project->id]);
        $this->assertDatabaseMissing('boards_columns', ['project_id' => $this->project->id]);
        $this->assertDatabaseMissing('tasks', ['project_id' => $this->project->id]);
        $this->assertSame($otherBefore, $other->fresh()->getAttributes());
        $this->assertSame($taskBefore, $otherTask->fresh()->getAttributes());
        $ticketBefore['project_id'] = null;
        $this->assertSame($ticketBefore, $ticket->fresh()->getAttributes());
        $this->assertCount(4, $other->columns);
    }

    public function test_cross_team_actions_are_rejected_before_validation_without_writes(): void
    {
        $foreign = Team::factory()->create()->projects()->create([
            'created_by' => $this->user->id, 'name' => 'Foreign project',
        ]);
        $before = $this->state();
        $this->actingAs($this->user)->patchJson($this->url($foreign), [])->assertNotFound();
        $this->actingAs($this->user)->deleteJson($this->url($foreign))->assertNotFound();
        $this->assertSame($before, $this->state());
    }

    public function test_nonmembers_and_guests_cannot_mutate_projects(): void
    {
        $before = $this->state();
        $this->patchJson($this->url(), ['name' => 'No'])->assertUnauthorized();
        $this->deleteJson($this->url())->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->patchJson($this->url(), ['name' => 'No'])->assertForbidden();
        $this->deleteJson($this->url())->assertForbidden();
        $this->assertSame($before, $this->state());
    }

    private function url(?Project $project = null): string
    {
        return '/'.$this->team->slug.'/dev/projects/'.($project ?? $this->project)->id;
    }

    private function indexUrl(): string
    {
        return '/'.$this->team->slug.'/dev/projects';
    }

    private function boardState(): array
    {
        return array_map(fn ($table) => DB::table($table)->orderBy('id')->get()->toJson(), ['boards_columns', 'tasks']);
    }

    private function state(): array
    {
        return [DB::table('projects')->orderBy('id')->get()->toJson(), ...$this->boardState()];
    }
}
