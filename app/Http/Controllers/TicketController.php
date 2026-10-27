<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tickets\StoreTicketRequest;
use App\Http\Requests\Tickets\UpdateTicketRequest;
use App\Models\Team;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function __construct(protected TicketService $ticketService) {}

    public function index(Team $current_team, Request $request): Response
    {
        $filters = $request->only([
            'search',
            'status',
            'priority',
            'category',
            'assigned_to',
            'store_id',
            'project_id',
        ]);

        $tickets = Ticket::query()
            ->where('team_id', $current_team->id)
            ->with(['store', 'project', 'creator', 'assignee'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['assigned_to'] ?? null, fn ($query, $assignedTo) => $query->where('assigned_to', $assignedTo))
            ->when($filters['store_id'] ?? null, fn ($query, $storeId) => $query->where('store_id', $storeId))
            ->when($filters['project_id'] ?? null, fn ($query, $projectId) => $query->where('project_id', $projectId))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('tickets/index', [
            'tickets' => $tickets,
            'filters' => $filters,
            'stores' => $current_team->stores()->select('id', 'name')->get(),
            'teamMembers' => $current_team->members()->select('users.id', 'users.name')->get(),
        ]);
    }

    public function show(Team $current_team, Ticket $ticket): Response
    {
        abort_unless($ticket->team_id === $current_team->id, 404);

        $ticket->load(['store', 'project', 'creator', 'assignee', 'comments.user', 'activities.user']);

        return Inertia::render('tickets/show', [
            'ticket' => [
                'id' => $ticket->id,
                'title' => $ticket->title,
                'description' => $ticket->description,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'category' => $ticket->category,
                'due_date' => $ticket->due_date?->toDateString(),
                'resolved_at' => $ticket->resolved_at?->toDateTimeString(),
                'created_at' => $ticket->created_at->toDateTimeString(),
                'store' => $ticket->store ? ['id' => $ticket->store->id, 'name' => $ticket->store->name] : null,
                'project' => $ticket->project ? ['id' => $ticket->project->id, 'name' => $ticket->project->name] : null,
                'creator' => $ticket->creator ? ['id' => $ticket->creator->id, 'name' => $ticket->creator->name] : null,
                'assignee' => $ticket->assignee ? ['id' => $ticket->assignee->id, 'name' => $ticket->assignee->name] : null,
                'comments' => $ticket->comments->map(fn ($comment) => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'created_at' => $comment->created_at->toDateTimeString(),
                    'user' => ['id' => $comment->user->id, 'name' => $comment->user->name],
                ]),
                'activities' => $ticket->activities->map(fn ($activity) => [
                    'id' => $activity->id,
                    'type' => $activity->type,
                    'field' => $activity->field,
                    'old_value' => $activity->old_value,
                    'new_value' => $activity->new_value,
                    'note' => $activity->note,
                    'created_at' => $activity->created_at->toDateTimeString(),
                    'user' => ['id' => $activity->user->id, 'name' => $activity->user->name],
                ]),
            ],
            'stores' => $current_team->stores()->select('id', 'name')->get(),
            'projects' => $current_team->projects()->select('id', 'name')->get(),
            'teamMembers' => $current_team->members()->select('users.id', 'users.name')->get(),
        ]);
    }

    public function store(Team $current_team, StoreTicketRequest $request): RedirectResponse
    {
        $ticket = $this->ticketService->createTicket($current_team, $request->validated(), $request->user());

        return redirect()->route('tickets.show', [
            'current_team' => $current_team->slug,
            'ticket' => $ticket->id,
        ])->with('success', 'Ticket creado correctamente.');
    }

    public function update(Team $current_team, Ticket $ticket, UpdateTicketRequest $request): RedirectResponse
    {
        abort_unless($ticket->team_id === $current_team->id, 404);

        $this->ticketService->updateTicket($ticket, $request->validated(), $request->user(), $current_team);

        return back()->with('success', 'Ticket actualizado.');
    }

    public function destroy(Team $current_team, Ticket $ticket, Request $request): RedirectResponse
    {
        abort_unless($ticket->team_id === $current_team->id, 404);

        $this->ticketService->deleteTicket($ticket, $request->user());

        return redirect()->route('tickets.index', ['current_team' => $current_team->slug])
            ->with('success', 'Ticket eliminado correctamente.');
    }
}
