<?php

namespace App\Services;

use App\Jobs\RefreshStoreStatusJob;
use App\Models\PingLog;
use App\Models\Store;
use App\Models\Team;
use App\Repositories\StoreRepository;
use Illuminate\Pagination\LengthAwarePaginator;

/** @phpstan-import-type StoreDashboard from StoreRepository */
class StoreService
{
    public function __construct(protected StoreRepository $storeRepository) {}

    /** @return LengthAwarePaginator<int, Store> */
    public function getBranches(?string $search = null, ?Team $team = null): LengthAwarePaginator
    {
        return $this->storeRepository->getAll($search, $team);
    }

    /** @return StoreDashboard */
    public function getDashboard(?Team $team = null): array
    {
        return $this->storeRepository->getDashboard($team);
    }

    public function refreshStatusNow(?int $storeId = null, ?Team $team = null): void
    {
        $query = $team ? $team->stores() : Store::query();

        if ($storeId !== null) {
            $query->whereKey($storeId);
        }

        $query->each(fn (Store $store) => RefreshStoreStatusJob::dispatch($store));
    }

    /** @return array<string, PingLog> */
    public function pingStoreSync(Store $store, NetworkDiagnosticService $diagnostics): array
    {
        return $diagnostics->pingStore($store);
    }
}
