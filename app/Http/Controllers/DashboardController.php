<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * \App\Http\Controllers\DashboardController
 *
 * @description Presenta el resumen operativo del equipo y las invitaciones pendientes dirigidas al correo del usuario.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): Response
    {
        $team = $request->user()->currentTeam;
        abort_unless($team instanceof Team, 403);
        $email = strtolower($request->user()->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        return Inertia::render('dashboard', [
            'overview' => $dashboard->overview($team),
            'pendingInvitations' => $pendingInvitations,
        ]);
    }
}
