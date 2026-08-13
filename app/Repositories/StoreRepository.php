<?php

namespace App\Repositories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;

class StoreRepository
{
    private const REGIONS = [
        'north' => [],
        'south' => [],
    ];

    public function getAll(?string $search = null)
    {
        return Store::query()
            ->with('device')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('store_code', 'like', "%{$search}%");
            })
            ->paginate(8)
            ->withQueryString();
    }

    public function getDashboard(): array
    {
        return [
            'total' => Store::count(),
            'online' => Store::where('status', 'online')->count(),
            'company' => $this->countByCompany(),
            'states' => $this->countByStates(),
        ];
    }

    private function countByCompany(): array
    {
        return Store::selectRaw('company, COUNT(*) as total')
            ->groupBy('company')
            ->get()
            ->toArray();
    }

    private function countByStates(): Collection
    {
        return Store::query()
            ->selectRaw('state, COUNT(*) as total')
            ->groupBy('state')
            ->orderByDesc('total')
            ->get();
    }
}
