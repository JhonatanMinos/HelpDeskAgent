<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tickets\StoreTicketCommentRequest;
use App\Models\Team;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;

/**
 * \App\Http\Controllers\TicketCommentController
 *
 * @description Valida la solicitud, confirma que el ticket pertenezca al equipo seleccionado y delega la creación del comentario a TicketService.
 */
class TicketCommentController extends Controller
{
    public function __construct(protected TicketService $ticketService) {}

    public function store(Team $current_team, Ticket $ticket, StoreTicketCommentRequest $request): RedirectResponse
    {
        abort_unless($ticket->team_id === $current_team->id, 404);

        $this->ticketService->addComment($ticket, $request->user(), $request->validated('body'));

        return back()->with('success', 'Seguimiento agregado.');
    }
}
