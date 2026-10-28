<?php

namespace App\Services;

use App\Models\Store;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketActivity;

class DashboardService
{
    /** @return array<string, mixed> */
    public function overview(Team $team): array
    {
        $stores = Store::query()->where('team_id', $team->id);
        $counts = (clone $stores)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $tickets = Ticket::query()->where('team_id', $team->id)
            ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'branches' => [
                'total' => (int) $counts->sum(),
                'online' => (int) $counts->get('online', 0),
                'offline' => (int) $counts->get('offline', 0),
                'unknown' => (int) $counts->get('unknown', 0),
            ],
            'attention' => (clone $stores)->whereIn('status', ['offline', 'unknown'])
                ->orderByRaw("CASE WHEN status = 'offline' THEN 0 ELSE 1 END")
                ->orderBy('last_seen_at')->orderBy('id')->limit(6)
                ->get(['id', 'store_code', 'name', 'city', 'state', 'status', 'last_seen_at'])
                ->map(fn (Store $store) => [
                    'id' => $store->id, 'code' => $store->store_code, 'name' => $store->name,
                    'city' => $store->city, 'state' => $store->state, 'status' => $store->status,
                    'lastCheckedAt' => $store->last_seen_at?->toISOString(),
                ]),
            'tickets' => collect(['abierto', 'en_proceso', 'esperando_cliente', 'resuelto', 'cerrado'])
                ->mapWithKeys(fn (string $status) => [$status => (int) $tickets->get($status, 0)]),
            'activity' => TicketActivity::query()
                ->whereHas('ticket', fn ($query) => $query->where('team_id', $team->id))
                ->with('ticket:id,title')->latest()->orderByDesc('id')->limit(6)->get()
                ->map(fn (TicketActivity $activity) => [
                    'id' => $activity->id, 'ticketId' => $activity->ticket_id,
                    'ticketTitle' => $activity->ticket->title, 'type' => $activity->type,
                    'createdAt' => $activity->created_at?->toISOString(),
                ]),
        ];
    }
}
