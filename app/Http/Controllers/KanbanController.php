<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KanbanController extends Controller
{
    public function storeTask(Team $current_team, Project $project, Request $request): RedirectResponse
    {
        // abort_unless($project->team_id === $current_team->id, 403);
        $data = $request->validate([
            'boards_column_id' => ['required', 'exists:boards_columns,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:baja,media,alta'],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $maxOrder = Task::where('boards_column_id', $data['boards_column_id'])->max('order') ?? -1;

        $project->task()->create([...$data, 'order' => $maxOrder + 1]);

        return redirect()->back()->with('success', 'Tarea creada.');
    }

    /**
     * Recibe la columna destino y el nuevo orden de TODAS las tarjetas de
     * esa columna tras un drag & drop, y lo persiste en un solo request.
     */
    public function moveTask(Team $current_team, Task $task, Request $request): RedirectResponse
    {
        // abort_unless($task->project->team_id === $current_team->id, 403);

        $data = $request->validate([
            'boards_column_id' => ['required', 'exists:boards_columns,id'],
            'ordered_task_ids' => ['required', 'array'],
            'ordered_task_ids.*' => ['integer', 'exists:tasks,id'],
        ]);

        $task->update(['boards_column_id' => $data['boards_column_id']]);

        foreach ($data['ordered_task_ids'] as $index => $taskId) {
            Task::where('id', $taskId)->update(['order' => $index]);
        }

        return back();
    }

    public function storeColumn(Team $current_team, Project $project, Request $request): RedirectResponse
    {
        // abort_unless($project->team_id === $current_team->id, 403);

        $data = $request->validate(['name' => ['required', 'string', 'max:60']]);

        $maxOrder = $project->columns()->max('order') ?? -1;

        $project->columns()->create([...$data, 'order' => $maxOrder + 1]);

        return back();
    }
}
