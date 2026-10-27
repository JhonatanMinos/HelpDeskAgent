<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

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
    public function store(Team $current_team, StoreProjectRequest $request)
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
    public function show(Team $current_team, Project $project)
    {
        abort_unless($project->team_id === $current_team->id, 404);

        return Inertia::render('projects/show', [
            'project' => $project,
            'columns' => $project->columns()->with([
                'tasks' => fn ($q) => $q->with('assignee:id,name')->orderBy('order'),
            ])->get(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        //
    }
}
