<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * \App\Http\Controllers\KanbanController
 *
 * @description Atiende la creación de tareas y columnas, y el movimiento de tareas entre columnas con validación del contexto del equipo y del proyecto.
 */
class KanbanController extends Controller
{
    public function storeTask(Team $current_team, Project $project, Request $request): RedirectResponse
    {
        abort_unless($project->team_id === $current_team->id, 403);
        $data = $request->validate([
            'boards_column_id' => ['required', Rule::exists('boards_columns', 'id')->where('project_id', $project->id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:baja,media,alta'],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', Rule::exists('team_members', 'user_id')->where('team_id', $current_team->id)],
        ]);

        $maxOrder = Task::where('boards_column_id', $data['boards_column_id'])->max('order') ?? -1;

        $project->task()->create([
            'boards_column_id' => $data['boards_column_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'],
            'limit_date' => $data['due_date'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'order' => $maxOrder + 1,
        ]);

        return redirect()->back()->with('success', 'Tarea creada.');
    }

    /**
     * Recibe la columna destino y el nuevo orden de TODAS las tarjetas de
     * esa columna tras un drag & drop, y lo persiste en un solo request.
     */
    public function moveTask(Team $current_team, Task $task, Request $request): RedirectResponse
    {
        abort_unless($task->project()->where('team_id', $current_team->id)->exists(), 403);
        abort_if($task->archived_at !== null, 404);
        $taskRule = Rule::exists('tasks', 'id')->where('project_id', $task->project_id);
        if (Task::supportsArchiving()) {
            $taskRule->whereNull('archived_at');
        }

        $data = $request->validate([
            'boards_column_id' => [
                'required',
                Rule::exists('boards_columns', 'id')->where('project_id', $task->project_id),
            ],
            'ordered_task_ids' => ['required', 'array', 'min:1'],
            'ordered_task_ids.*' => [
                'integer',
                'distinct',
                $taskRule,
            ],
        ]);

        $orderedTaskIds = array_map('intval', $data['ordered_task_ids']);
        $otherTaskIds = array_diff($orderedTaskIds, [$task->id]);

        if (! in_array($task->id, $orderedTaskIds, true)) {
            throw ValidationException::withMessages([
                'ordered_task_ids' => 'The moved task must be included in the ordered task list.',
            ]);
        }

        $hasTaskOutsideDestination = Task::query()
            ->where('project_id', $task->project_id)
            ->whereIn('id', $otherTaskIds)
            ->where('boards_column_id', '!=', $data['boards_column_id'])
            ->exists();

        if ($hasTaskOutsideDestination) {
            throw ValidationException::withMessages([
                'ordered_task_ids' => 'The ordered tasks must belong to the destination column.',
            ]);
        }

        DB::transaction(function () use ($data, $orderedTaskIds, $task): void {
            $task->update(['boards_column_id' => $data['boards_column_id']]);

            foreach ($orderedTaskIds as $index => $taskId) {
                Task::query()
                    ->where('project_id', $task->project_id)
                    ->where('boards_column_id', $data['boards_column_id'])
                    ->whereKey($taskId)
                    ->update(['order' => $index]);
            }
        });

        return back();
    }

    public function storeColumn(Team $current_team, Project $project, Request $request): RedirectResponse
    {
        abort_unless($project->team_id === $current_team->id, 403);

        $data = $request->validate(['name' => ['required', 'string', 'max:60']]);

        $maxOrder = $project->columns()->max('order') ?? -1;

        $project->columns()->create([...$data, 'order' => $maxOrder + 1]);

        return back();
    }
}
