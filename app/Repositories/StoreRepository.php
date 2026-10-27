<?php

namespace App\Repositories;

use App\Models\Store;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @phpstan-type StoreDashboard array{total: int, online: int, company: array<int, array<string, mixed>>, states: Collection<int, Store>}
 */
class StoreRepository
{
    /** @return LengthAwarePaginator<int, Store> */
    public function getAll(?string $search = null, ?Team $team = null): LengthAwarePaginator
    {
        return Store::query()
            ->when($team, fn ($query) => $query->where('team_id', $team->id))
            ->with('device')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('store_code', 'like', "%{$search}%");
                });
            })
            ->paginate(8)
            ->withQueryString();
    }

    /** @return StoreDashboard */
    public function getDashboard(?Team $team = null): array
    {
        return [
            'total' => Store::query()->when($team, fn ($query) => $query->where('team_id', $team->id))->count(),
            'online' => Store::query()->where('status', 'online')->when($team, fn ($query) => $query->where('team_id', $team->id))->count(),
            'company' => $this->countByCompany($team),
            'states' => $this->countByStates($team),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function countByCompany(?Team $team = null): array
    {
        return Store::selectRaw('company, COUNT(*) as total')
            ->when($team, fn ($query) => $query->where('team_id', $team->id))
            ->groupBy('company')
            ->get()
            ->toArray();
    }

    /** @return Collection<int, Store> */
    private function countByStates(?Team $team = null): Collection
    {
        return Store::query()
            ->when($team, fn ($query) => $query->where('team_id', $team->id))
            ->selectRaw('state, COUNT(*) as total')
            ->groupBy('state')
            ->orderByDesc('total')
            ->get();
    }
}
