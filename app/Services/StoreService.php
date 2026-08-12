<?php

namespace App\Services;

use App\Repositories\StoreRepository;
use App\Jobs\RefreshStoreStatusJob;
use App\Models\Store;
use App\Services\NetworkDiagnosticService;

class StoreService
{

    public function __construct(protected StoreRepository $storeRepository) {}

    public function getBranches(?string $search = null)
    {
        return $this->storeRepository->getAll($search);
    }

    public function getDashboard(): array
    {
        return $this->storeRepository->getDashboard();
    }

    public function refreshStatusNow(?int $storeId = null): void
    {
        $query = $storeId ? Store::where('id', $storeId) : Store::query();
        $query->each(fn(Store $store) => RefreshStoreStatusJob::dispatch($store));
    }

    public function pingStoreSync(Store $store, NetworkDiagnosticService $diagnostics): array
    {
        return $diagnostics->pingStore($store);
    }
}
