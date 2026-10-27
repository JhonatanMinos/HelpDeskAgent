<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Team;
use App\Services\NetworkDiagnosticService;
use App\Services\StoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * \App\Http\Controllers\StoreController
 *
 * @description Usa StoreService para listados, estadísticas, actualizaciones y pings, y entrega detalles y registros recientes de las sucursales. checkPrintQueue es una acción pública auxiliar que delega el diagnóstico de impresión, pero no tiene una ruta registrada.
 */
class StoreController extends Controller
{
    public function __construct(
        protected StoreService $storeService
    ) {}

    public function index(Team $current_team, Request $request): Response
    {
        $stores = $this->storeService->getBranches(
            $request->search,
            $current_team,
        );
        $dashboard = $this->storeService->getDashboard($current_team);

        return Inertia::render('branches/stores', [
            'dashboard' => $dashboard,
            'stores' => $stores,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Team $current_team, Store $branch): Response
    {
        abort_unless($branch->team_id === $current_team->id, 404);

        return Inertia::render('branches/show', [
            'store' => $branch,
            'deviceIps' => $branch->device_ips,
            'printers' => $branch->device,
            'recentPings' => $branch->pingLogs()
                ->latest('checked_at')
                ->limit(10)
                ->get(['device_label', 'is_online', 'latency_ms', 'checked_at']),
        ]);
    }

    public function refreshStatus(Team $current_team): RedirectResponse
    {
        $this->storeService->refreshStatusNow(team: $current_team);

        return back()->with('success', 'Actualización de estatus en proceso...');
    }

    public function pingNow(Team $current_team, Store $store, NetworkDiagnosticService $diagnostics): RedirectResponse
    {
        abort_unless($store->team_id === $current_team->id, 404);

        $this->storeService->pingStoreSync($store, $diagnostics);

        if ($store->fresh()->status === 'online') {
            return redirect()->back()->with(
                'success',
                "La tienda {$store->name} está en línea."
            );
        }

        return redirect()->back()->with(
            'error',
            "La tienda {$store->name} no está disponible."
        );        /*return response()->json([
            'store_id' => $store->id,
            'status' => $store->fresh()->status,
            'last_seen_at' => $store->fresh()->last_seen_at,
            'devices' => collect($results)->map(fn ($log) => [
                'device' => $log->device_label,
                'ip' => $log->ip,
                'is_online' => $log->is_online,
                'latency_ms' => $log->latency_ms,
            ]),
        ]);*/
    }

    public function checkPrintQueue(Team $current_team, Store $branch, NetworkDiagnosticService $diagnostics): JsonResponse
    {
        abort_unless($branch->team_id === $current_team->id, 404);

        return response()->json(
            $diagnostics->checkPrintChain($branch)
        );
    }
}
