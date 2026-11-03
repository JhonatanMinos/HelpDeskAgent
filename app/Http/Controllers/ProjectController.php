<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * \App\Http\Controllers\ProjectController
 *
 * @description Lista, crea, muestra, actualiza y elimina proyectos del equipo; la creación inicializa columnas predeterminadas dentro de una transacción. La edición se realiza desde el listado.
 */
class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Team $current_team, Request $request): Response
    {

        return Inertia::render('projects/index', [
            'projects' => Project::where('team_id', $current_team->id)
                ->withCount('task')
                ->with('creator:id,name')
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Team $current_team, StoreProjectRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $project = DB::transaction(function () use ($data, $current_team, $request) {
            $project = Project::create([
                ...$data,
                'team_id' => $current_team->id,
                'created_by' => $request->user()->id,
                'status' => 'propuesto',
            ]);

            $project->seedDefaultColumns();

            return $project;
        });

        return redirect()->route('dev.projects.show', [
            'current_team' => $current_team->slug,
            'project' => $project->id,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $current_team, Project $project): Response
    {
        abort_unless($project->team_id === $current_team->id, 404);
        $archivingAvailable = Task::supportsArchiving();

        return Inertia::render('projects/show', [
            'project' => $project,
            'columns' => $project->columns()->with([
                'tasks' => fn ($q) => $q->when($archivingAvailable, fn ($tasks) => $tasks->whereNull('archived_at'))
                    ->with('assignee:id,name')->orderBy('order'),
            ])->get(),
            'archivedTasks' => $archivingAvailable ? $project->task()->whereNotNull('archived_at')
                ->with(['assignee:id,name', 'column:id,name'])->latest('archived_at')->get() : [],
            'taskArchivingAvailable' => $archivingAvailable,
            'members' => $current_team->members()->select('users.id', 'users.name')->get(),
        ]);
    }

    public function updateStatus(Team $current_team, Project $project, Request $request): RedirectResponse
    {
        abort_unless($project->team_id === $current_team->id, 404);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:propuesto,activo,pausado,completado'],
        ]);

        $project->update(['status' => $data['status']]);

        return redirect()->route('dev.projects.show', [
            'current_team' => $current_team->slug,
            'project' => $project->id,
        ])->with('success', 'Estado del proyecto actualizado.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Team $current_team, Project $project, UpdateProjectRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $project->update([
            'name' => $data['name'],
            'Initial_requirements' => $data['requirements'] ?? null,
            'estimated_date_delivery' => $data['due_date'] ?? null,
        ]);

        return redirect()->route('dev.projects.index', ['current_team' => $current_team->slug])
            ->with('success', 'Proyecto actualizado.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $current_team, Project $project): RedirectResponse
    {
        abort_unless($project->team_id === $current_team->id, 404);

        $project->delete();

        return redirect()->route('dev.projects.index', ['current_team' => $current_team->slug])
            ->with('success', 'Proyecto eliminado.');
    }
}
