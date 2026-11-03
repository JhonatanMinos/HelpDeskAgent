<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Edits, deletes and reversibly archives tasks belonging to the route's team.
 */
class TaskController extends Controller
{
    public function update(Team $current_team, Task $task, Request $request): RedirectResponse
    {
        $this->ensureTeam($current_team, $task);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(['baja', 'media', 'alta'])],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('team_members', 'user_id')->where('team_id', $current_team->id)],
        ]);

        $task->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'],
            'limit_date' => $data['due_date'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
        ]);

        return $this->toBoard($current_team, $task, 'Tarea actualizada.');
    }

    public function destroy(Team $current_team, Task $task): RedirectResponse
    {
        $this->ensureTeam($current_team, $task);
        $task->delete();

        return $this->toBoard($current_team, $task, 'Tarea eliminada.');
    }

    public function archive(Team $current_team, Task $task): RedirectResponse
    {
        $this->ensureTeam($current_team, $task);
        $this->ensureArchivingAvailable();
        Task::whereKey($task->id)->whereNull('archived_at')->update(['archived_at' => now()]);

        return $this->toBoard($current_team, $task, 'Tarea archivada.');
    }

    public function restore(Team $current_team, Task $task): RedirectResponse
    {
        $this->ensureTeam($current_team, $task);
        $this->ensureArchivingAvailable();
        Task::whereKey($task->id)->whereNotNull('archived_at')->update(['archived_at' => null]);

        return $this->toBoard($current_team, $task, 'Tarea restaurada.');
    }

    private function ensureArchivingAvailable(): void
    {
        if (! Task::supportsArchiving()) {
            throw ValidationException::withMessages([
                'task' => 'El archivado no está disponible hasta aplicar la migración de tareas.',
            ]);
        }
    }

    private function ensureTeam(Team $team, Task $task): void
    {
        abort_unless($task->project()->where('team_id', $team->id)->exists(), 404);
    }

    private function toBoard(Team $team, Task $task, string $message): RedirectResponse
    {
        return redirect()->route('dev.projects.show', ['current_team' => $team->slug, 'project' => $task->project_id])
            ->with('success', $message);
    }
}
