<?php

namespace App\Services;

use App\Jobs\RefreshStoreStatusJob;
use App\Models\Store;
use App\Models\Team;
use App\Repositories\StoreRepository;

class StoreService
{
    public function __construct(protected StoreRepository $storeRepository) {}

    public function getBranches(?string $search = null, ?Team $team = null)
    {
        return $this->storeRepository->getAll($search, $team);
    }

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

    public function pingStoreSync(Store $store, NetworkDiagnosticService $diagnostics): array
    {
        return $diagnostics->pingStore($store);
    }
}
